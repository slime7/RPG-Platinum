<?php

//连接数据库
$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_USER = 'root';
$DB_PASSWORD = 'root';
$DB_NAME = 'rpgplatinum';

$db = @new \mysqli($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_PORT);
if ($db->connect_errno) {
  $json = new slime7\rpgplatinum\module\jsonpack();
  $json->setMsg("MySQL error {$db->connect_errno}: {$db->connect_error}");
  $json->setStatus(500, 'Internal Server Error');
  $json->header();
  exit($json);
}
$db->query("set names 'utf8'");
