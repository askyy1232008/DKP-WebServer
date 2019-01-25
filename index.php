<?php
/**
+-----------------------------------------------------------------------+
* @author 张涛 <tonera@gmail.com>;
* @since 2005-12-1
* @version $Id: index.php,v 1.3.7 tonera$
* @description	wowdkper 2007-11-23 last update at 2008-9-23
+-----------------------------------------------------------------------+
*/
error_reporting(0);
session_start();
header("Cache-control: private");

$cf		= dirname(__FILE__) . "/php/config.inc.php";
$dcf	= dirname(__FILE__) . "/php/module/config/config.inc.php";
//变量初始化
$_COOKIE['wow_cfg_lang']	= isset($_COOKIE['wow_cfg_lang'])?$_COOKIE['wow_cfg_lang']:'';
$_REQUEST['module']			= isset($_REQUEST['module'])?$_REQUEST['module']:'index';
$_REQUEST['act']			= isset($_REQUEST['act'])?$_REQUEST['act']:'news';
$_GET['offset']				= isset($_GET['offset'])?$_GET['offset']:0;
$_GET['keyword']			= isset($_GET['keyword'])?$_GET['keyword']:'';
$_GET['orderby']			= isset($_GET['orderby'])?$_GET['orderby']:'';
$_GET['obj']				= isset($_GET['obj'])?$_GET['obj']:'';

if(!file_exists($dcf)){
	header("Location: install.php");
}

include_once($cf);
include_once(CHINO_PATH . "/Chino.php");
unset($lang);

//语言包
if($_COOKIE['wow_cfg_lang']) {
	//判断语言包是否存在
	if(file_exists(CHINO_PHPPATH . "/lang/".$_COOKIE['wow_cfg_lang']."/lang.inc.php")) {
		include_once(CHINO_PHPPATH .  "/lang/".$_COOKIE['wow_cfg_lang']."/lang.inc.php");
	}else {
		include_once(CHINO_PHPPATH .  "/lang/en/lang.inc.php");
	}
}else {
	//按用户浏览器设定
	$cfg_lang       = 'en';
	if(eregi('cn',$_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		   $cfg_lang       = 'zh-cn';
	}elseif(eregi('tw',$_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		   $cfg_lang       = 'zh-tw';
	}elseif(eregi('hk',$_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		   $cfg_lang       = 'zh-tw';
	}else {
		   $cfg_lang       = 'en';
	}
	if(file_exists(CHINO_PHPPATH . "/lang/".$cfg_lang."/lang.inc.php")) {
		   include_once(CHINO_PHPPATH .  "/lang/".$cfg_lang."/lang.inc.php");
	}else {
		   include_once(CHINO_PHPPATH .  "/lang/en/lang.inc.php");
	}
}

/**
 *得到控制器实体
 */
$app = Chino::getApplication($_REQUEST['module'], $_REQUEST['act'], $moduleTable);

$app->run();
?>