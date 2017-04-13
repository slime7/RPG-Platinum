SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `uid` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(16) NOT NULL DEFAULT '0',
  `hash` VARCHAR(64) NOT NULL DEFAULT '0',
  `create_time` INT(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  UNIQUE INDEX `username` (`username`)
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB
;

CREATE TABLE `rpgp_order_breif` (
	`oid` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`author` INT(10) UNSIGNED NOT NULL,
	`title` VARCHAR(64) NOT NULL,
	`description` VARCHAR(512) NOT NULL,
	`create_time` INT(10) UNSIGNED NOT NULL,
	`cover` INT(10) UNSIGNED NOT NULL,
	`origin_oid` INT(10) UNSIGNED NULL DEFAULT NULL,
	`state` INT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '{1:"open",2:"close"}',
	PRIMARY KEY (`oid`),
	INDEX `uid` (`author`),
	INDEX `aid` (`cover`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=5
;

CREATE TABLE `rpgp_order_item` (
	`oiid` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`oid` INT(10) UNSIGNED NOT NULL,
	`self_id` INT(10) UNSIGNED NOT NULL,
	`parent_id` INT(10) UNSIGNED NOT NULL,
	`level` INT(1) UNSIGNED NOT NULL,
	`name` VARCHAR(64) NOT NULL,
	`type` VARCHAR(8) NOT NULL,
	PRIMARY KEY (`oiid`),
	INDEX `oid` (`oid`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
;

CREATE TABLE `attachment` (
	`aid` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`uid` INT(10) UNSIGNED NOT NULL,
	`mime_type` VARCHAR(64) NOT NULL,
	`upload_time` INT(10) UNSIGNED NOT NULL,
	`width` INT(6) UNSIGNED NOT NULL,
	`height` INT(6) UNSIGNED NOT NULL,
	`path` VARCHAR(256) NOT NULL,
	PRIMARY KEY (`aid`),
	INDEX `uid` (`uid`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
;

CREATE TABLE `rpgp_progress` (
	`pid` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`oid` INT(10) UNSIGNED NOT NULL,
	`uid` INT(10) UNSIGNED NOT NULL,
	`create_date` INT(10) UNSIGNED NOT NULL,
	`update_date` INT(10) UNSIGNED NOT NULL,
	`progress` TEXT NOT NULL,
	PRIMARY KEY (`pid`),
	INDEX `oid` (`oid`),
	INDEX `uid` (`uid`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
;
