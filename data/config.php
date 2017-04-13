<?php

define('DATA_ROOT', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('ROOT', dirname(DATA_ROOT) . DIRECTORY_SEPARATOR);

define('version', '17.04.01');
define('cookieSuffix', 'rpgp_');
define('authcodeKey', 'A66K6ozVsK6g6bZ6E7hohnvhh1Eh6kf6');
define('usesourcecode', false);
$sitepath = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
$siteurl = htmlspecialchars('//' . $_SERVER['HTTP_HOST'] . $sitepath . '/');

function s_autoload($class) {
  $class_dir = 'lib/';
  if (false !== stripos($class, 'slime7\rpgplatinum')) {
    $file_path = $class_dir . str_replace('\\', '/', substr($class, 18)) . '.php';
  } else {
    $file_path = $class_dir . "{$class}.php";
  }
  $real_path = ROOT . strtolower($file_path);
  if (!file_exists($real_path)) {
    throw new Exception('Ooops, system file is losing: ' . strtolower($file_path));
  } else {
    require_once($real_path);
  }
}

spl_autoload_register('s_autoload', true);

require_once(DATA_ROOT . 'conn.php');

//设置时区
@date_default_timezone_set('Asia/Shanghai');
@ini_set('date.timezone', 'Asia/Shanghai');

$page_config = [
  'version' => version,
  'cookieSuffix' => cookieSuffix
];
