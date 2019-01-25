DROP TABLE IF EXISTS wowdkp_admuser;
CREATE TABLE wowdkp_admuser (
  id int(8) NOT NULL auto_increment,
  username varchar(40) NOT NULL default '',
  password varchar(32) NOT NULL default '',
  power smallint(4) NOT NULL default '0',
  notes varchar(255) NOT NULL default '',
  cid varchar(100) NOT NULL default '',
  PRIMARY KEY  (id),
  UNIQUE KEY username (username)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wowdkp_config;
CREATE TABLE wowdkp_config (
  vname varchar(128) NOT NULL default '',
  value varchar(255) NOT NULL default '',
  UNIQUE KEY vname (vname)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT IGNORE INTO wowdkp_config (vname, value) VALUES ('guildname', '');
INSERT IGNORE INTO wowdkp_config (vname, value) VALUES ('showcopy', '1,2');
INSERT IGNORE INTO wowdkp_config (vname, value) VALUES ('acttime', '3');
INSERT IGNORE INTO wowdkp_config (vname, value) VALUES ('islogin', '1');
INSERT IGNORE INTO wowdkp_config (vname, value) VALUES ('sdecimal', '2');
INSERT IGNORE INTO wowdkp_config (vname, value) VALUES ('defaultcopy', '1');
INSERT IGNORE INTO wowdkp_config (vname, value) VALUES ('itemquality', '2');
INSERT IGNORE INTO wowdkp_config (vname, value) VALUES ('timezone', '8');
INSERT IGNORE INTO wowdkp_config (vname, value) VALUES ('dstyle', 'system');
INSERT IGNORE INTO wowdkp_config (vname, value) VALUES ('location', 'cn');
INSERT IGNORE INTO wowdkp_config (vname, value) VALUES ('langtype', 'zh-cn');
INSERT IGNORE INTO wowdkp_config (vname, value) VALUES ('realm', '');
INSERT IGNORE INTO wowdkp_config (vname, value) VALUES ('isarmory', '1');

DROP TABLE IF EXISTS wowdkp_copy;
CREATE TABLE wowdkp_copy (
  id smallint(6) NOT NULL auto_increment,
  name varchar(40) default NULL,
  notes text,
  raidtotal int(10) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wowdkp_dkpvalues;
CREATE TABLE wowdkp_dkpvalues (
  dkpvalue decimal(16,2) NOT NULL default '0.00',
  uid int(10) unsigned NOT NULL default '0',
  copyid smallint(6) NOT NULL default '1',
  raidnum int(10) NOT NULL default '0',
  KEY uid (uid),
  KEY copyid (copyid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wowdkp_event;
CREATE TABLE wowdkp_event (
  id int(10) unsigned NOT NULL auto_increment,
  name varchar(255) default NULL,
  notes text,
  etid tinyint(2) default NULL,
  raidtime date default NULL,
  cid smallint(6) NOT NULL default '1',
  PRIMARY KEY  (id),
  KEY etid (etid),
  KEY cid (cid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wowdkp_eventitem;
CREATE TABLE wowdkp_eventitem (
  id int(10) unsigned NOT NULL auto_increment,
  eid int(10) default NULL,
  iid int(10) default NULL,
  PRIMARY KEY  (id),
  KEY eid (eid),
  KEY iid (iid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wowdkp_eventtype;
CREATE TABLE wowdkp_eventtype (
  id tinyint(2) NOT NULL auto_increment,
  name varchar(40) NOT NULL default '',
  notes text NOT NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT IGNORE INTO wowdkp_eventtype (id, name, notes) VALUES (1, 'Raid', '大型raid活动');
INSERT IGNORE INTO wowdkp_eventtype (id, name, notes) VALUES (2, '管理员dkp调节', '管理员dkp调节');
INSERT IGNORE INTO wowdkp_eventtype (id, name, notes) VALUES (3, '事故惩罚', '事故惩罚');

DROP TABLE IF EXISTS wowdkp_group;
CREATE TABLE wowdkp_group (
  id tinyint(3) unsigned NOT NULL auto_increment,
  name varchar(128) default NULL,
  power varchar(10) default NULL,
  notes text,
  stat tinyint(1) default '0',
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wowdkp_item;
CREATE TABLE wowdkp_item (
  id int(10) unsigned NOT NULL auto_increment,
  ipid int(8) default NULL,
  eid int(10) default NULL,
  name varchar(255) default NULL,
  num smallint(4) NOT NULL default '1',
  notes text,
  stat tinyint(1) unsigned default '0',
  intotime datetime NOT NULL default '0000-00-00 00:00:00',
  icolor varchar(6) default NULL,
  PRIMARY KEY  (id),
  KEY ipid (ipid),
  KEY eid (eid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wowdkp_itemdis;
CREATE TABLE wowdkp_itemdis (
  id int(10) unsigned NOT NULL auto_increment,
  iid int(10) unsigned default NULL,
  eid int(10) NOT NULL default '0',
  uid int(10) unsigned default NULL,
  value decimal(10,2) NOT NULL default '0.00',
  distime datetime NOT NULL default '0000-00-00 00:00:00',
  stat tinyint(2) NOT NULL default '1',
  cid smallint(6) NOT NULL default '1',
  PRIMARY KEY  (id),
  KEY iid (iid),
  KEY eid (eid),
  KEY uid (uid),
  KEY cid (cid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wowdkp_news;
CREATE TABLE wowdkp_news (
  id int(10) unsigned NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  content text NOT NULL,
  posttime datetime NOT NULL default '0000-00-00 00:00:00',
  postuid smallint(8) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wowdkp_race;
CREATE TABLE wowdkp_race (
  id tinyint(2) unsigned NOT NULL auto_increment,
  name varchar(20) NOT NULL default '',
  notes text,
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wowdkp_raidcfg;
CREATE TABLE wowdkp_raidcfg (
  id smallint(6) NOT NULL auto_increment,
  name varchar(50) NOT NULL default '',
  maxnum smallint(4) NOT NULL default '0',
  classreq varchar(100) NOT NULL default '',
  resistance varchar(100) NOT NULL default '',
  minlevel smallint(4) NOT NULL default '0',
  maxlevel smallint(4) NOT NULL default '0',
  autoqueue tinyint(2) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wowdkp_raidlog;
CREATE TABLE wowdkp_raidlog (
  id int(10) NOT NULL auto_increment,
  rid smallint(6) NOT NULL default '0',
  invitetime datetime NOT NULL default '0000-00-00 00:00:00',
  starttime datetime NOT NULL default '0000-00-00 00:00:00',
  circaendtime datetime NOT NULL default '0000-00-00 00:00:00',
  freezelimit decimal(4,1) NOT NULL default '0.0',
  name varchar(100) NOT NULL default '',
  notes text NOT NULL,
  stat tinyint(2) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY rid (rid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wowdkp_signup;
CREATE TABLE wowdkp_signup (
  id int(11) NOT NULL auto_increment,
  uid int(10) NOT NULL default '0',
  workid tinyint(2) NOT NULL default '0',
  raid int(10) NOT NULL default '0',
  signtime datetime NOT NULL default '0000-00-00 00:00:00',
  stat tinyint(2) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY uid (uid,raid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wowdkp_user;
CREATE TABLE wowdkp_user (
  id int(10) unsigned NOT NULL auto_increment,
  name varchar(128) default '0',
  password varchar(32) NOT NULL default '',
  raceid tinyint(2) unsigned default NULL,
  workid tinyint(2) unsigned default NULL,
  level tinyint(2) unsigned default '0',
  notes varchar(255) default NULL,
  pic varchar(255) default NULL,
  groupid tinyint(3) default '0',
  stat tinyint(1) default '0',
  regtime datetime default NULL,
  dkpvalue smallint(8) default '0',
  lastraidtime date NOT NULL default '0000-00-00',
  PRIMARY KEY  (id),
  UNIQUE KEY name (name)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wowdkp_work;
CREATE TABLE wowdkp_work (
  id tinyint(2) unsigned NOT NULL auto_increment,
  name varchar(20) NOT NULL default '',
  notes text,
  wcolor varchar(15) default NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS wowdkp_syslog;
CREATE TABLE `wowdkp_syslog` (
  `id` int(11) NOT NULL auto_increment,
  `uid` int(11) default NULL,
  `uname` varchar(100) NOT NULL default '',
  `op` varchar(50) default NULL,
  `obj` varchar(255) default NULL,
  `des` varchar(255) default NULL,
  `optime` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `wowdkp_itemproperty`;
CREATE TABLE `wowdkp_itemproperty` (
  `id` int(8) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `property` text NOT NULL,
  `itemcode` varchar(40) NOT NULL default '',
  `istat` tinyint(2) NOT NULL default '0',
  `intotime` datetime NOT NULL default '0000-00-00 00:00:00',
  `itemid` varchar(50) default NULL,
  `dkperid` int(10) default NULL,
  `itemfrom` varchar(100) default NULL,
  `itemhost` varchar(100) default NULL,
  `isup` tinyint(2) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;