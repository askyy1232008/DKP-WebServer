<?php
/**
+-----------------------------------------------------------------------+
* @autor tonera <tonera at gmail.com>;
* @since 2006-8-9
* @version $Id: getUserDkp.php,v 1.0 beta tonera$
* @packge wowdkper
* @description	service. get member's dkp.仅适用于1.3及以后版本
2006-9-20背景色设置为透明。bg参数取消
+-----------------------------------------------------------------------+
*/
error_reporting(0);
set_time_limit(5);
$output	= "<body STYLE='background-color: transparent'>";
header("Cache-control: private");
if(empty($_GET['u']) or !isset($_GET['u'])) {
	exit($output."</body>");
}
$_GET['co']	= $_GET['co']?$_GET['co']:2;
$_GET['c']	= $_GET['c']?$_GET['c']:'000000';
$_GET['f']	= $_GET['f']?$_GET['f']:12;
//$_GET['bg']	= $_GET['bg']?$_GET['bg']:'FFFFFF';
$isicvon	= function_exists("iconv");
$ismbstring	= function_exists("mb_convert_encoding");
if($ismbstring) {
	$func	= "mb_convert_encoding";
}elseif($isicvon) {
	$func	= "iconv";
}else {
	exit("Error:您的服务器环境不支持字符集转换,请配置服务器支持多字节字符转换");
}

if(file_exists("../php/config.inc.php")) {
	$cfile	= "../php/config.inc.php";
}elseif(file_exists("../../php/config.inc.php")) {
	$cfile	= "../../php/config.inc.php";
}else {
	exit("请先安装wowdkper 1.3或以上版本.");
}

if($_GET['tc'] == 1) {
	header("Content-Type: text/html; charset=GB2312");
}else {
	header("Content-Type: text/html; charset=UTF-8");
}

if(!isset($_GET['fc'])) {
	$_GET['fc']	= 1;
}else {
	$_GET['fc']	= (int)$_GET['fc'];
}
if(!isset($_GET['tc'])) {
	$_GET['tc']	= 1;
}else {
	$_GET['tc']	= (int)$_GET['fc'];
}
if(!get_magic_quotes_gpc()) {
	$_GET['u']	= addslashes($_GET['u']);
}

if($func == "mb_convert_encoding") {
	if($_GET['fc'] == 1) {
		$member	= mb_convert_encoding($_GET['u'],"UTF-8","GBK");
	}elseif($_GET['fc'] == 2) {
		$member	= $_GET['u'];
	}elseif($_GET['fc'] == 3) {
		$member	= mb_convert_encoding($_GET['u'],"UTF-8","BIG5");
	}else {
		$member	= mb_convert_encoding($_GET['u'],"UTF-8","GBK");
	}
}elseif($func == "iconv") {
	if($_GET['fc'] == 1) {
		$member	= iconv("GBK","UTF-8",$_GET['u']);
	}elseif($_GET['fc'] == 2) {
		$member	= $_GET['u'];
	}elseif($_GET['fc'] == 3) {
		$member	= iconv("BIG5","UTF-8",$_GET['u']);
	}else {
		$member	= iconv("GBK","UTF-8",$_GET['u']);
	}
}

require_once $cfile;
require_once CHINO_MODPATH.'/config/config.inc.php';
mysql_connect(DBHOST, DBUSER, DBPASS);
$mysqlversion	= mysql_get_server_info();
if($mysqlversion > '4.1') {
	mysql_query("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
	if($mysqlversion > '5.0.1') {
		mysql_query("SET sql_mode=''");
	}
}
mysql_select_db(DBNAME);

//member
$sql	= "select id from ".TABLEHEAD."_user where name = '$member' limit 1";
$rs		= @mysql_query($sql);
$row	= mysql_fetch_array($rs);
$uid	= $row['id'];
if(empty($uid)) {
	exit($output."</body>");
}

$copyarr	= array();
$sql	= "select * from ".TABLEHEAD."_copy";
$rs		= @mysql_query($sql);
while($row	= mysql_fetch_array($rs)) {
	$copyarr[$row['id']]	= $row['name'];
}
$userdkp	= array();
$sql	= "select * from ".TABLEHEAD."_dkpvalues where uid = '$uid' ";
if(!empty($_GET['m'])) {
	$mcopyarrar	= explode("-",$_GET['m']);
	if(is_array($mcopyarrar)) {
		$sqlstr	= implode(',',$mcopyarrar);
		$sql	.= " and copyid in(".$sqlstr.")";
	}
}
$sql	.= " order by copyid ";

$rs		= @mysql_query($sql);
while($row	= mysql_fetch_array($rs)) {
	$userdkp[$row['copyid']]	= $row['dkpvalue'];
}
mysql_close();

if(is_array($userdkp)) {
	$outarr		= array();
	foreach($userdkp as $key=>$val) {
		if(!empty($copyarr[$key])) {
			$outarr[]	= $copyarr[$key].":".$val;
		}
	}
	if($_GET['co'] == '1') {
			$outstr	= implode(' ',$outarr);
		}else {
			$outstr	= implode('<br />',$outarr);
		}
		if($func == "mb_convert_encoding") {
		if($_GET['tc'] == 1) {
			$ostr	= mb_convert_encoding($outstr,"GBK","UTF-8");
		}elseif($_GET['tc'] == 2) {
			$ostr	= $outstr;
		}elseif($_GET['tc'] == 3) {
			$ostr	= mb_convert_encoding($outstr,"BIG5","UTF-8");
		}else {
			$ostr	= mb_convert_encoding($outstr,"GBK","UTF-8");
		}
	}elseif($func == "iconv") {
		if($_GET['tc'] == 1) {
			$ostr	= iconv("UTF-8","GBK",$outstr);
		}elseif($_GET['tc'] == 2) {
			$ostr	= $outstr;
		}elseif($_GET['tc'] == 3) {
			$ostr	= iconv("UTF-8","BIG5",$outstr);
		}else {
			$ostr	= iconv("UTF-8","GBK",$outstr);
		}
	}
	$output		.= "<span style='font-size: ".$_GET['f']."px;color: #".$_GET['c'].";'>".$ostr."</span>";
}else {
	$output	.= '';
}

$output	.= "</body>";
echo($output);



?>