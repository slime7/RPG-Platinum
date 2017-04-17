<?php
namespace slime7\rpgplatinum;

use slime7\rpgplatinum\module\comm;
use verot\upload\upload;

class order
{
  protected $oid;
  protected $orderList;
  protected $order_breif;
  protected $order_items;
  protected $order_progress;
  protected $count = 0;
  public $page = 1;
  public $pagesize = 20;
  public $sortby = 'oid';
  public $sort = 'DESC';
  public $filter;
  public $query;
  public $error;

  public function __construct($query = null) {
    if (!!$query) {
      $this->setQuery($query);
      if (stripos($query, 'oid:') >= 0) {
        $this->oid = $this->listOrderId();
      }
    }
  }

  public function newRpg($post, $files, $uid) {
    global $db;
    if (!isset($post['rpgdata'])) {
      $this->error = 'No data.';
      return false;
    }

    $rpgdata = [];
    $image_uploaded_attr = [];
    $now = time();
    $rpgdata['breif'] = $post['rpgdata']['breif'];
    if (!isset($rpgdata['breif']['title']) || !isset($rpgdata['breif']['description'])
      || strlen($rpgdata['breif']['title']) < 4 || strlen($rpgdata['breif']['description']) < 4
      || strlen($rpgdata['breif']['title']) > 60 || strlen($rpgdata['breif']['description']) > 500
    ) {
      $this->error = '标题或简介过长或过短。';
      return false;
    }
    if (!isset($post['rpgdata']['chapters']['sub'])) {
      $this->error = '没有发现内容。';
      return false;
    } else {
      $itemList = [];
      $this->decodeChapters($post['rpgdata']['chapters'], 0, $itemList);
      $rpgdata['chapters'] = $itemList;
    }
    if (count($rpgdata['chapters']) > 1000) {
      $this->error = '内容太长了。';
      return false;
    }

    $image_uploaded_attr['aid'] = 0;
    if (isset($files)) {
      if (!is_dir(ROOT . 'upload')) {
        mkdir(ROOT . 'upload');
      }
      if (!is_dir(ROOT . 'upload/image')) {
        mkdir(ROOT . 'upload/image');
      }

      $image_max_length = 128;
      $cover_handle = new upload($_FILES['cover'], 'zh_CN');
      $cover_handle->allowed = array('image/*');
      if ($cover_handle->uploaded) {
        $filename = md5($now);
        $image_width = $cover_handle->image_src_x;
        $image_height = $cover_handle->image_src_y;

        $cover_handle->file_new_name_body = $filename;
        if ($image_width > $image_max_length || $image_height > $image_max_length) {
          $cover_handle->image_resize = true;
          $cover_handle->image_x = $image_max_length;
          $cover_handle->image_y = $image_max_length;
          $cover_handle->image_ratio = true;
        }
        $cover_handle->process(ROOT . 'upload/image/');
        $success_main = $cover_handle->processed;
        if ($success_main) {
          $image_uploaded_attr = [
            'author' => (int)$uid,
            'path' => 'upload/image/' . $cover_handle->file_dst_name,
            'width' => $cover_handle->image_dst_x,
            'height' => $cover_handle->image_dst_y,
            'src_name' => $cover_handle->file_src_name,
            'mime' => $cover_handle->file_src_mime,
            'time' => $now
          ];

          $insert_image = $db->query("INSERT INTO `attachment`(`mime_type`, `width`, `height`, `upload_time`, `uid`, `path`) VALUES ('{$image_uploaded_attr['mime']}', '{$image_uploaded_attr['width']}', '{$image_uploaded_attr['height']}', {$image_uploaded_attr['time']}, {$image_uploaded_attr['author']}, '{$image_uploaded_attr['path']}')");
          if ($insert_image) {
            $image_uploaded_attr['aid'] = $db->insert_id;
          } else {
            $this->error = '保存图片信息出错。' . $db->errno . ': ' . $db->error;
            return false;
          }
        } else {
          $this->error = '图片处理出错。' . $db->errno . ': ' . $db->error;
          return false;
        }

        $cover_handle->clean();
      }
    }

    $db->autocommit(false);
    $baseinfoResult = $db->query("INSERT INTO `rpgp_order_breif` (`author`, `title`, `description`, `create_time`, `cover`) VALUES ('{$uid}', '{$rpgdata['breif']['title']}', '{$rpgdata['breif']['description']}', '{$now}', '{$image_uploaded_attr['aid']}')");
    $last_oid = $db->insert_id;

    $item_values = '';
    $item_values_temp = "('%d', '%d', '%d', '%d', '%s', '%s')";
    foreach ($rpgdata['chapters'] as $item) {
      $item_values .= $item_values === '' ? sprintf($item_values_temp, $last_oid, $item['self_id'], $item['parent_id'], $item['level'], $item['name'], $item['type']) : (', ' . sprintf($item_values_temp, $last_oid, $item['self_id'], $item['parent_id'], $item['level'], $item['name'], $item['type']));
    }
    unset($item);
    $itemResult = $db->query("INSERT INTO `rpgp_order_item` (`oid`, `self_id`, `parent_id`, `level`, `name`, `type`) VALUES {$item_values}");

    if ($baseinfoResult && $itemResult) {
      $db->commit();
      $db->autocommit(true);

      return true;
    } else {
      $db->rollback();
      $db->autocommit(true);
      $this->error = 'BaseInfo: ' . ($baseinfoResult ? 'true' : 'false') . ', item: ' . ($itemResult ? 'true' : 'false');

      return false;
    }
  }

  public function lists($page = 1, $pagesize = 20) {
    $pagesizeLimit = [5, 10, 20];
    $this->page = (int)$page;
    if (!in_array($pagesize, $pagesizeLimit)) {
      $this->pagesize = 20;
    } else {
      $this->pagesize = (int)$pagesize;
    }
    $oids = $this->listOrderId();

    $arrpage = comm::arraypage($oids, $this->page, $this->pagesize);
    $this->oid = $arrpage['thispage'];
    $this->count = $arrpage['pager']['count'];
    $this->get();

    return $this->data();
  }

  public function listOrderId() {
    global $db;
    $oids = [];
    $filter_enabled = [];
    $order_query = "ORDER BY `{$this->sortby}` {$this->sort}";
    if (isset($this->query)) {
      $filters = comm::parse_filter($this->query);
      foreach ($filters as $f) {
        switch ($f[0]) {
          case 'oid':
            $o_a = explode(',', $f[1]);
            foreach ($o_a as &$o) {
              $o = (int)$o;
            }
            unset($o);
            $o_q = implode(',', $o_a);
            $filter_enabled[] = "(`oid` IN ({$o_q}))";
            break;
          case 'query':
            $q = saddslashes($f[1]);
            $filter_enabled[] = "(`remark` LIKE '%{$q}%')";
            unset($q);
            break;
          case 'user':
            $isId = preg_match('#^(\d+[,])*(\d+)$#', $f[1]);
            $q = saddslashes($f[1]);
            $id_query = $isId ? $q : "SELECT `uid` FROM `member` WHERE `username` LIKE '%{$q}%'";
            $filter_enabled[] = "(`{$f[0]}_id` IN ({$id_query}))";
            break;
          case 'open':
            if ($f[1] == 'true') {
              $isopen = true;
            } else if ($f[1] == 'false') {
              $isopen = false;
            } else {
              $isopen = null;
            }
            if (isset($isopen)) {
              $filter_enabled[] = $isopen ? '(`state` = 1)' : '(`state` = 2)';
            }
            break;
          case 'date':
            $range = explode('..', $f[1]);
            if ($range[0] != '*') {
              $filter_enabled[] = '(`date` >= ' . strtotime($range[0]) . ')';
            }
            if ($range[1] != '*') {
              $filter_enabled[] = '(`date` <= ' . strtotime($range[1]) . ')';
            }
            break;
        }
      }

      $filter_query = count($filter_enabled) ? implode(' AND ', $filter_enabled) : '1';
      $getOids = $db->query("SELECT `oid` FROM `rpgp_order_breif` WHERE {$filter_query} {$order_query}");
      while ($getOids && $row = $getOids->fetch_assoc()) {
        $oids[] = (int)$row['oid'];
      }
      unset($row);
    } else {
      $getOids = $db->query("SELECT `oid` FROM `rpgp_order_breif` WHERE 1 {$order_query}");
      while ($getOids && $row = $getOids->fetch_assoc()) {
        $oids[] = (int)$row['oid'];
      }
      unset($row);
    }

    return array_unique($oids);
  }

  public function get($progress = false) {
    if (!isset($this->oid)) {
      return false;
    }
    $this->breif();
    $orderList = $this->order_breif;
    if (!(is_array($this->oid) && count($this->oid) > 1)) {
      $orderList[$this->oid[0]]['chapters'] = $this->getChapters();
      $orderList[$this->oid[0]]['progress'] = $this->order_progress;
    }

    $_orderList = array_values(comm::arraysort($orderList, $this->sortby, $this->sort));
    $this->orderList = $_orderList;
    return $this->orderList;
  }

  public function breif() {
    global $db;
    $where = '';
    $orderList = [];
    if (!$this->oid) {
      $this->order_breif = $orderList;
      return $this->order_breif;
    }
    if (!is_array($this->oid)) {
      $where .= "`rpgp_order_breif`.`oid` = {$this->oid}";
    } else {
      $where .= '`rpgp_order_breif`.`oid` IN (' . implode(',', $this->oid) . ')';
    }
    $get_breif_sql = <<<sql
SELECT
	`rpgp_order_breif`.`oid`,
	`rpgp_order_breif`.`author`,
	`rpgp_order_breif`.`title`,
	`rpgp_order_breif`.`description`,
	`a_name`.`username` AS `author_name`,
	`rpgp_order_breif`.`create_time`,
	`rpgp_order_breif`.`cover`,
	`cover_info`.`path` AS `cover_path`,
	`cover_info`.`mime_type` AS `cover_mime`,
	`cover_info`.`width` AS `cover_width`,
	`cover_info`.`height` AS `cover_height`,
	`cover_info`.`upload_time` AS `cover_time`,
	`rpgp_order_breif`.`origin_oid`,
	`rpgp_order_breif`.`state`
FROM
	`rpgp_order_breif`
	JOIN `user` AS `a_name` ON `rpgp_order_breif`.`author` = `a_name`.`uid`
	LEFT JOIN `attachment` AS `cover_info` ON `rpgp_order_breif`.`cover` = `cover_info`.`aid`
WHERE
 {$where}
sql;
    $get_breif_result = $db->query($get_breif_sql);
    while ($get_breif_result && $row = $get_breif_result->fetch_assoc()) {
      $orderList[(int)$row['oid']] = [
        'oid' => (int)$row['oid'],
        'status' => $row['state'],
        'origin_oid' => $row['origin_oid'] ? (int)$row['origin_oid'] : null,
        'create_time' => (int)$row['create_time'] * 1000,
        'create_time_unix' => (int)$row['create_time'],
        'author' => [
          'uid' => (int)$row['author'],
          'username' => $row['author_name'],
        ],
        'cover' => [
          'aid' => (int)$row['cover'],
          'mime' => $row['cover_mime'],
          'path' => $row['cover_path'],
          'width' => $row['cover_width'],
          'height' => $row['cover_height'],
          'time' => $row['cover_time']
        ],
        'title' => $row['title'],
        'description' => $row['description'],
        'chapters' => []
      ];
    }
    unset($row);
    $this->order_breif = $orderList;
    return $this->order_breif;
  }

  public function getChapters() {
    global $db;
    $rawChapters = [];
    $rawChaptersRoot = [];
    $items_pair = [];
    $where = '';
    if (!$this->oid) {
      $this->error = '';
      return false;
    }

    if (!is_array($this->oid)) {
      $where .= "`oid` = {$this->oid}";
    } else {
      $where .= '`oid` IN (' . implode(',', $this->oid) . ')';
    }

    $getChapter = $db->query("SELECT `oiid`, `oid`, `self_id`, `parent_id`, `level`, `name`, `type` FROM `rpgp_order_item` WHERE $where");
    while ($getChapter && $row = $getChapter->fetch_assoc()) {
      $row['oiid'] = (int)$row['oiid'];
      $row['oid'] = (int)$row['oid'];
      $row['self_id'] = (int)$row['self_id'];
      $row['parent_id'] = (int)$row['parent_id'];
      $row['level'] = (int)$row['level'];
      if ($row['level'] === 0) {
        $rawChaptersRoot = $row;
      } else {
        $rawChapters[] = $row;
      }
      if ($row['type'] != 'cat') {
        $items_pair[(int)$row['oiid']] = false;
      }
    }
    unset($row);

    user::check_login();
    global $uid;
    //get progress here
    if ($uid) {
      $existProgress = $this->loadProgress($uid);
      if (!!$existProgress) {
        $this->order_progress = $existProgress['progress'];
      } else {
        $this->order_progress = $items_pair;
      }
    }
    $rawChaptersRoot['sub'] = $this->chapterMakeTree($rawChapters, 0);
    $this->order_items = $rawChaptersRoot;
    return $this->order_items;
  }

  public function saveProgress($post, $uid) {
    global $db;
    if (!isset($post['progress'])) {
      $this->error = 'No data.';
      return false;
    }

    $this->getChapters();
    $now = time();
    $hasSaveData = false;
    $hasSaveDataNew = false;
    $newProgress = [];

    $this->getChapters();
    foreach ($this->order_progress as $oiid => $v) {
      if (!$hasSaveData && $v) {
        $hasSaveData = true;
      }
      if (isset($post['progress'][$oiid])) {
        $newProgress[$oiid] = !!$post['progress'][$oiid];
        if (!$hasSaveDataNew && !!$post['progress'][$oiid]) {
          $hasSaveDataNew = true;
        }
      } else {
        $newProgress[$oiid] = $this->progress[$oiid];
      }
    }
    unset($oiid, $v);
    $newProgressJson = json_encode($newProgress, JSON_UNESCAPED_UNICODE);

    if ($hasSaveData && $hasSaveDataNew) {
      //update
      $result = $db->query("UPDATE `rpgp_progress` SET `progress` = '{$newProgressJson}', `update_date` = '{$now}' WHERE `oid` = '{$this->oid[0]}' AND `uid` = '{$uid}'");
    } else if (!$hasSaveData && $hasSaveDataNew) {
      //insert
      $result = $db->query("INSERT INTO `rpgp_progress` (`oid`, `uid`, `create_date`, `update_date`, `progress`) VALUES ('{$this->oid[0]}', '{$uid}', '{$now}', '{$now}', '{$newProgressJson}')");
    } else if ($hasSaveData && !$hasSaveDataNew) {
      //delete
      $result = $db->query("DELETE FROM `rpgp_progress` WHERE `oid` = '{$this->oid[0]}' AND `uid` = '{$uid}'");
    } else {
      //do nothing
      $result = true;
    }

    return !!$result;
  }

  public function loadProgress($uid) {
    global $db;
    $getProgressResult = $db->query("SELECT `pid`, `create_date`, `update_date`, `progress` FROM `rpgp_progress` WHERE `uid` = '{$uid}' AND `oid` = '{$this->oid[0]}' LIMIT 1");
    if ($getProgressResult && $row = $getProgressResult->fetch_assoc()) {
      $progressInfo = [
        'pid' => (int)$row['pid'],
        'uid' => $uid,
        'oid' => $this->oid[0],
        'create_date' => (int)$row['create_date'],
        'update_date' => (int)$row['update_date'],
        'progress' => json_decode($row['progress'], true)
      ];
    } else {
      $progressInfo = null;
    }

    return $progressInfo;
  }

  private function chapterMakeTree(&$source, $pid) {
    $temp = [];
    foreach ($source as $key => $item) {
      if ($item['parent_id'] == $pid) {
        $temp[] = $item;
        $temp = array_values(comm::arraysort($temp, 'oiid', 'asc'));
        unset($source[$key]);
      }
    }

    if (count($temp)) {
      foreach ($temp as $key => $item) {
        $temp[$key]['sub'] = $this->chapterMakeTree($source, $item['self_id']);
      }
      return $temp;
    }
    return [];
  }

  private function decodeChapters($p, $parent_id, &$itemList) {
    $item_parent_id = count($itemList);
    if ($p['type'] !== 'item') {
      $itemList[] = [
        'name' => $p['name'],
        'level' => (int)$p['level'],
        'self_id' => $item_parent_id,
        'parent_id' => $parent_id,
        'type' => 'cat'
      ];
      if (isset($p['sub']) && count($p['sub'])) {
        foreach ($p['sub'] as $c) {
          $this->decodeChapters($c, $item_parent_id, $itemList);
        }
        unset($c);
      }
    } else if ($p['type'] === 'item') {
      $itemList[] = [
        'name' => $p['name'],
        'level' => (int)$p['level'],
        'self_id' => $item_parent_id,
        'parent_id' => $parent_id,
        'type' => 'cat'
      ];
      if (isset($p['sub']) && count($p['sub'])) {
        foreach ($p['sub'] as $i) {
          $itemList[] = [
            'name' => $i,
            'level' => (int)$p['level'] + 1,
            'self_id' => count($itemList),
            'parent_id' => $item_parent_id,
            'type' => 'item'
          ];
        }
        unset($i, $item_parent_id);
      }
    }
  }

  public function data() {
    return [
      'orderList' => $this->orderList,
      //'order_breif' => $this->order_breif,
      //'order_items' => $this->order_items,
      'pager' => [
        'count' => $this->count,
        'page' => $this->page,
        'pagesize' => $this->pagesize,
      ]
    ];
  }

  public function setQuery($query) {
    $this->query = $query;
  }

  public function setSort($order = 'oid', $sort = 'ASC') {
    $this->sortby = $order;
    $this->sort = $sort;
    if (!in_array(strtolower($this->sort), ['asc', 'desc'])) {
      $this->sort = 'ASC';
    }
  }
}