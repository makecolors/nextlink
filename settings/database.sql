create database avater;
grant all on avater.* to makecolors@localhost identified by '1qaz"WSX';
use avater;

create table users(
	id int not null auto_increment primary key,
	username varchar(50),
	password varchar(255),
	friendlist text,
	position int,
	created datetime,
	modified datetime
);

create table chatlog(
	id int not null auto_increment primary key,
	roomid int,
	charid int,
	chatdata text,
	created datetime
);

CREATE TABLE image(
  `id` int UNSIGNED NOT NULL PRIMARY KEY COMMENT 'ID',
  `name` varchar(255) NOT NULL COMMENT 'ファイル名',
  `type` tinyint(2) NOT NULL COMMENT 'IMAGETYPE定数',
  `raw_data` mediumblob NOT NULL COMMENT '原寸大データ',
  `thumb_data` blob NOT NULL COMMENT 'サムネイルデータ',
  `date` datetime NOT NULL COMMENT '日付'
) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci 