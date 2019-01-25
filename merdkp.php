<?php
/**
+-----------------------------------------------------------------------+
* @autor tonera <tonera at gmail.com>;
* @since 2006-8-8
* @version $Id: merdkp.php,v 1.0 tonera$
* @description	MerDKP 插件数据下载 
* @last update 2008-9-15
+-----------------------------------------------------------------------+
*/
error_reporting(0);
header("Content-Type: text/html; charset=UTF-8");
if($_GET['do']!='download') {
	echo("WOW-DKPer plugin for MerDKP (<a href=http://www.dkper.com/ target=_blank>http://www.dkper.com/</a>)<br />");
	echo "<a href=".$_SERVER['PHP_SELF']."?do=download>点此下载 dkp_list.lua</a> 文件到您的插件目录下.<br />";
	echo "<a href=".$_SERVER['PHP_SELF']."?do=download>download  dkp_list.lua</a> to your MerDKP's diretory.";
	exit;
}

if(file_exists("./php/config.inc.php")) {
	$cfile	= "./php/config.inc.php";
}elseif(file_exists("../php/config.inc.php")) {
	$cfile	= "../php/config.inc.php";
}else {
	echo "请先安装wowdkper 1.3或以上版本.";
}
require_once $cfile;
require_once CHINO_MODPATH.'/config/config.inc.php';
require_once CHINO_LIBPATH.'/adodb.inc.php';
$object = ADONewConnection(DBTYPE);
$object->connect(DBHOST, DBUSER, DBPASS, DBNAME);
$mysqlvrs		= $object->execute("select version()");
$mysqlversion	= $mysqlvrs->fields[0];
if($mysqlversion > '4.1') {
	$object->execute("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
	if($mysqlversion > '5.0.1') {
		$object->execute("SET sql_mode=''");
	}
}
$copyarray		= array();
$userdata		= array();
$membercount	= array();

$outstr			= "-- <Mer_DKP> ;\r\n";
$outstr			.= "-- for wowdkper by tonera ;\r\n";
$outstr	.= "Mer_DKP_NumTable = {};\r\n";
$outstr	.= "Mer_DKP_KeywordsTable = {};\r\n";
$outstr	.= "Mer_DKP_Table = {};\r\n\r\n";

$sql	= "select * from ".TABLEHEAD."_copy";
$rs		= $object->execute($sql);
$i		= 1;
while(!$rs->EOF) {
	$cid	= (int)$rs->fields['id'];
	$cname	= addslashes($rs->fields['name']);
	$outstr	.= "Mer_DKP_Table[\"".$cname."\"] = {\r\n";
	//查询此副本会员dkp
	$sql	= "select a.dkpvalue as dkp, a.uid as uid, b.name as uname, c.name as class from ".TABLEHEAD."_dkpvalues as a left join ".TABLEHEAD."_user as b on a.uid=b.id left join ".TABLEHEAD."_work as c on b.workid = c.id where a.copyid='$cid'";
	$urs	= $object->execute($sql);
	while(!$urs->EOF) {
		$outstr	.= "{ name = \"".addslashes($urs->fields['uname'])."\", class = \"".addslashes($urs->fields['class'])."\", dkp = ".$urs->fields['dkp'].", online = 1 },\n";
		$urs->MoveNext();
	}
	$outstr	.= "};\n";
	$outstr	.= "Mer_DKP_NumTable[".$i."] = \"".$cname."\"\n";
	$outstr	.= "Mer_DKP_KeywordsTable[".$i."] = \"".$cname."\"\n\n";
	$i++;
	$rs->MoveNext();
}
$outstr		.= "if Mer_DKP_Table then \n";
$outstr		.= "	MerDKP_Table = {} \n";
$outstr		.= "		for k, v in pairs(Mer_DKP_Table) do \n";
$outstr		.= "			v.title = k \n";
$outstr		.= "			v.key = k \n";
$outstr		.= "			tinsert(MerDKP_Table, v) \n";
$outstr		.= "		end \n";
$outstr		.= "end \n";

header('Cache-control: private');
header('Content-Description: File Transfer');
header('Content-Type: application/force-download');
Header("Accept-Ranges: bytes");
Header("Accept-Length: ".strlen($outstr));
Header("Content-Disposition: attachment; filename=dkp_list.lua");
echo $outstr;
exit;

?>
