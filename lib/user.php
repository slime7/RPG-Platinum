<?php

namespace slime7\rpgplatinum;

use slime7\rpgplatinum\module\comm;

/**
 * member
 */
class user
{

  private static $salt;
  public static $error = null;

  /**
   * 登录
   *
   * @global int $uid
   * @global string $username
   * @param string $un
   * @param string $password
   * @return boolean
   */
  public static function login($un, $password) {
    global $uid, $username, $db;

    $username = comm::saddslashes($un);
    self::salt($username);
    $result = $db->query("SELECT `uid`, `hash` FROM `user` WHERE `username` = '{$username}' LIMIT 1");
    if ($result && $result->num_rows) {
      $row = $result->fetch_assoc();
      $uid = $row['uid'];
      $db_password = $row['hash'];
      $auth_password = self::password_auth($password);

      if ($db_password === $auth_password) {
        $uid = preg_replace('/[^0-9]+/', '', $uid);
        $login_string = self::login_string($auth_password);
        $exptime = time() + 900;
        comm::ssetcookie('token', comm::authcode("{$uid}\t{$username}\t{$exptime}\t{$login_string}", 'ENCODE'), 3600 * 24 * 365);

        return true;
      } else {
        comm::ssetcookie('token', '');
        unset($uid, $username, $exptime);

        return false;
      }
    } else {
      unset($uid, $username);

      return false;
    }
  }

  /**
   *
   * @global int $uid
   * @global string $username
   * @return boolean
   */
  public static function check_login() {
    global $uid, $username, $db;

    if (!empty($_COOKIE[cookieSuffix . 'token'])) {

      list($uid, $username, $exptime, $login_string) = explode("\t", comm::authcode($_COOKIE[cookieSuffix . 'token'], 'DECODE'));
      if (!$uid) {
        unset($uid, $username, $exptime);
        comm::ssetcookie('token', '');

        return false;
      } elseif ($exptime < time()) {
        self::salt($username);

        $result = $db->query("SELECT `hash` FROM `user` WHERE `uid` = {$uid} LIMIT 1");
        if ($result && $result->num_rows) {
          $row = $result->fetch_array();
          $db_password = $row['hash'];
          $login_check = self::login_string($db_password);

          if ($login_check === $login_string) {
            $exptime = time() + 900;
            comm::ssetcookie('token', comm::authcode("{$uid}\t{$username}\t{$exptime}\t{$login_string}", 'ENCODE'), 3600 * 24 * 365);

            return true;
          }
        } else {
          unset($uid, $username);

          return false;
        }
      } else {
        return true;
      }
    } else {
      unset($uid, $username);

      return false;
    }
  }

  /**
   * 执行注册
   * @param array $info
   * @return $uid if success,false when fail
   */
  public static function register(array $info) {
    global $uid, $username, $db;

    if (!self::checkusername($info['username'])) {
      return false;
    }

    $now = time();
    $username = comm::saddslashes(strtolower($info['username']));
    $password = $info['password'];

    self::salt($username);
    $auth_password = self::password_auth($password);

    $insertUser = $db->query("INSERT INTO `user`(`username`, `hash`, `create_time`) VALUES ('{$username}', '{$auth_password}', {$now})");
    $insertUser = 1;
    if ($insertUser) {
      $uid = $db->insert_id;

      return $uid;
    } else {
      unset($uid, $username);
      self::$error = '可能没注册成功。';
      return false;
    }
  }

  /**
   * 修改密码
   *
   * @param string $oldpassword 原密码
   * @param string $newpassword 新密码
   * @param string $err_msg 错误信息
   * @return int 错误代码:
   * 0 success
   * 1 2次密码相同
   * 2 密码不正确
   * 3 数据库出了点问题
   */
  public static function changepassword($oldpassword, $newpassword, &$err_msg = null) {
    global $uid, $username, $db;

    if ($oldpassword == $newpassword) {
      if (isset($err_msg)) {
        $err_msg = '新旧密码不能相同';
      }
      return 1;
    } else {
      self::salt($username);

      $result = $db->query("SELECT `hash` FROM `user` WHERE `uid` = '{$uid}' LIMIT 1");
      $row = $result->fetch_assoc();
      $db_password = $row['hash'];
      $auth_oldpassword = self::password_auth($oldpassword);
      if ($db_password == $auth_oldpassword) {
        $auth_newpassword = self::password_auth($newpassword);
        $result = $db->query("UPDATE `user` SET `hash` = '{$auth_newpassword}' WHERE `uid` = {$uid}");

        return $result && $db->affected_rows ? 0 : 3;
      } else {
        if (isset($err_msg)) {
          $err_msg = '原密码不正确';
        }
        return 2;
      }
    }
  }

  public static function detail($uid) {
    global $db;

    $wherequery = "";
    $user = [];
    if (is_array($uid)) {
      $wherequery .= !!count($uid) ? " IN ( " . implode(',', $uid) . " )" : " = 0";
    } else {
      $wherequery .= " = {$uid}";
    }
    $sql = "SELECT `uid`, `username`, `create_time` FROM `user` WHERE `uid` {$wherequery}";
    $result = $db->query($sql);
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $user[(int)$row['uid']] = [
          'uid' => (int)$row['uid'],
          'username' => $row['username'],
          'create_time' => (int)$row['create_time']
        ];
      }
    }

    return $user;
  }

  public static function detailbyusername($username) {
    global $db;
    $_username = comm::saddslashes($username);
    $user_result = $db->query("SELECT `uid`, `username`, `create_time`, (SELECT COUNT(`oid`) FROM `rpgp_order_breif` WHERE `author` = `uid`) AS `order_count` FROM `user` WHERE `username` = '{$_username}' LIMIT 1");
    if ($user_result && $user_result->num_rows) {
      $user = $user_result->fetch_assoc();
      $user['uid'] = (int)$user['uid'];
      $user['create_time'] = (int)$user['create_time'];
      $user['order_count'] = (int)$user['order_count'];
    } else {
      $user = null;
    }

    return $user;
  }

  public static function lists($page = 1, $sortby = 'uid', $filter = null) {
    $pagesize = 20;
    $uids = self::uid_source($sortby, $filter);

    $arrpage = comm::arraypage($uids, $page, $pagesize);
    if ($arrpage) {
      $_user = self::detail($arrpage['thispage']);
      $user = array_values(comm::arr_sort($_user, $sortby, 'asc'));
    } else {
      $user = [];
    }

    return ['list' => $user, 'pager' => $arrpage['pager']];
  }

  private static function uid_source($sortby = 'uid', $filter = null) {
    global $db;

    $uids = [];
    $filter_a = !!$filter ? explode(':', $filter) : $filter;
    if (!!$filter_a) {
      $filter_type = $filter_a[0];
      $filter_content = isset($filter_a[1]) ? $filter_a[1] : '';
      switch ($filter_type) {
        case 'uid':
          $uids[] = (int)$filter_content;
          break;
      }
    } else {
      $getUids = $db->query("SELECT `uid` FROM `user` WHERE 1 ORDER BY `{$sortby}` ASC");
      while ($getUids && $row = $getUids->fetch_assoc()) {
        $uids[] = (int)$row['uid'];
      }
    }

    return $uids;
  }

  /**
   *
   * @param string $username
   */
  public static function salt($username) {
    $salt = substr(md5(strlen($username) . strtolower($username)), 8, 16);
    self::$salt = $salt;
  }

  public static function password_auth($password) {
    $password_auth = hash('sha256', md5($password) . self::$salt);

    return $password_auth;
  }

  public static function login_string($auth_password) {
    $login_string = substr(hash('sha256', $auth_password . self::$salt), 8, 8);

    return $login_string;
  }

  /**
   *
   * @param string $username
   * @return boolean is success
   */
  public static function checkusername($username) {
    global $db;

    $len = mb_strlen($username, 'utf8');
    if ($len > 16 || $len < 1) {
      self::$error = '用户名过长。';
      return false;
    }

    if (!preg_match("/^[A-Za-z_][A-Za-z0-9_]+$/", $username)) {
      self::$error = '只允许使用字母、数字、下划线且不以数字开头。';
      return false;
    }

    $isexists = $db->query("SELECT `uid` FROM `user` WHERE `username` = '{$username}'");
    if ($isexists->num_rows > 0) {
      self::$error = '该用户名已存在。';
      return false;
    }

    return true;
  }

}
