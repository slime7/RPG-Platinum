<?php
namespace slime7\rpgplatinum\module;


class comm
{
  static public function parseBody() {
    $data = NULL;
    $files = NULL;

    if (isset($_POST) AND !empty($_POST)) {
      $data = $_POST;
    }

    if (isset($_FILES) AND !empty($_FILES)) {
      $files = $_FILES;
    }

    if ($data === NULL) {
      if (isset($_SERVER["CONTENT_TYPE"]) AND strpos($_SERVER["CONTENT_TYPE"], 'application/json') !== false) {
        $input = file_get_contents('php://input');

        $data = json_decode($input, true);
      } else {
        $data = ['stream'];
        $files = [];
      }
    }

    return [
      'data' => $data,
      'files' => $files
    ];
  }

  public static function ssetcookie($name, $value = '', $exp = 2592000) {
    $exp = $value ? time() + $exp : '1';
    setcookie(cookieSuffix . $name, $value, $exp, '/');
  }

  public static function arraypage($arr, $page, $pagesize) {
    $offset = ($page - 1) * $pagesize;
    $thispage = [];
    $count = count($arr);

    if ($offset > $count) {
      return false;
    }

    for ($i = $offset; $i < $offset + $pagesize; $i++) {
      if (isset($arr[$i])) {
        $thispage[] = $arr[$i];
      }
    }
    unset($i);

    $pager = [
      'page' => $page,
      'pagesize' => $pagesize,
      'count' => $count,
    ];

    return ['thispage' => $thispage, 'pager' => $pager];
  }

  public static function arraysort($array, $keys, $type = 'asc') {
    $keysvalue = $new_array = [];
    foreach ($array as $k => $v) {
      $keysvalue[$k] = $v[$keys];
    }
    if (strtolower($type) == 'asc') {
      asort($keysvalue);
    } else {
      arsort($keysvalue);
    }
    reset($keysvalue);
    foreach ($keysvalue as $k => $v) {
      $new_array[$k] = $array[$k];
    }
    unset($k, $v);

    return $new_array;
  }

  public static function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    $ckey_length = 4;
    $key = md5($key ? $key : authcodeKey);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    for ($i = 0; $i <= 255; $i++) {
      $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    for ($j = $i = 0; $i < 256; $i++) {
      $j = ($j + $box[$i] + $rndkey[$i]) % 256;
      $tmp = $box[$i];
      $box[$i] = $box[$j];
      $box[$j] = $tmp;
    }
    for ($a = $j = $i = 0; $i < $string_length; $i++) {
      $a = ($a + 1) % 256;
      $j = ($j + $box[$a]) % 256;
      $tmp = $box[$a];
      $box[$a] = $box[$j];
      $box[$j] = $tmp;
      $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if ($operation == 'DECODE') {
      if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
        return substr($result, 26);
      } else {
        return '';
      }
    } else {
      return $keyc . str_replace('=', '', base64_encode($result));
    }
  }

  public static function saddslashes($string, $strip = FALSE) {
    if (is_array($string)) {
      foreach ($string as $key => $val) {
        $string[$key] = saddslashes($val, $strip);
      }
    } else {
      $string = addslashes($strip ? stripslashes($string) : $string);
    }
    return $string;
  }

  public static function debugLog($msg) {
    $handle = fopen(ROOT . 'debug_log.txt', 'a');
    fwrite($handle, sprintf("%s : ", date('Y-m-d H:i:s')));
    fwrite($handle, "\r\n");
    fwrite($handle, $msg . "\r\n\r\n");
    fclose($handle);
  }

  public static function parse_filter($filter_string) {
    $filter_pair = [];
    if (!!$filter_string) {
      $filter_a = explode(' ', trim($filter_string));
      foreach ($filter_a as $f) {
        if (!$f) {
          continue;
        }
        $f_a = explode(':', $f, 2);
        if (count($f_a) > 1) {
          if (!$f_a[1]) {
            continue;
          }
          $filter_pair[] = $f_a;
        } else {
          $filter_pair[] = ['query', $f];
        }
      }
      unset($f);
    }

    return $filter_pair;
  }

}
