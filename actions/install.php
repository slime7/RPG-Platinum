<?php

include_once(dirname(__FILE__) . '/../data/config.php');

use slime7\rpgplatinum\user;

$sql_file = DATA_ROOT . 'init.sql';
if (!file_exists($sql_file)) {
  exit();
}
$sql_content = file_get_contents($sql_file);
$sql_content = str_replace("\r", "\n", $sql_content);
foreach (explode(";\n", trim($sql_content)) as $query) {
  $query = trim($query);
  if ($query) {
    $db->query($query);
  } else {
    exit('error.');
  }
}

user::register([
  //设置初始账号的登录名和密码
  'username' => 'admin',
  'password' => 'password'
]);

rename($sql_file, DATA_ROOT . 'init.installed.sql');

exit('done.');
