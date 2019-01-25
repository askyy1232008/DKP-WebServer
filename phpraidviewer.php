<?php
/**
+-----------------------------------------------------------------------+
* @autor tonera <tonera at gmail.com>;
* @since2006-9-3
* @version $Id: phpraidviewer.php,v 1.3.2 tonera$
* @description	phpraidviewer 数据下载器.仅适用于wowdkper 1.3.2以后版本
+-----------------------------------------------------------------------+
*/
error_reporting(0);
header("Content-Type: text/html; charset=UTF-8");
if($_GET['do']!='download') {
	echo("WOW-DKPer1.3.2's plugin for phpRaidViewer <br />");
	echo "请点击<a href=".$_SERVER['PHP_SELF']."?do=download>".$_SERVER['PHP_SELF']."?do=download</a>下载 phpRaid_Data.lua 文件到您的插件目录下.<br />";
	echo "Please click <a href=".$_SERVER['PHP_SELF']."?do=download>".$_SERVER['PHP_SELF']."?do=download</a> to download 'phpRaid_Data.lua' under your phpRaidViewer dir.<br />";
	exit;
}
if(file_exists("./php/config.inc.php")) {
	$cfile	= "./php/config.inc.php";
}else {
	echo "请先安装wowdkper 1.3.2 或以上版本.";
}
require_once $cfile;
require_once CHINO_MODPATH.'/config/config.inc.php';
require_once CHINO_LIBPATH.'/adodb.inc.php';
$raidid	= (int)$_GET['raidid'];
$ctime	= date("Y-m-d H:i:s");
$dbo = ADONewConnection(DBTYPE);
$dbo->connect(DBHOST, DBUSER, DBPASS, DBNAME);
$mysqlvrs		= $dbo->execute("select version()");
$mysqlversion	= $mysqlvrs->fields[0];
if($mysqlversion > '4.1') {
	$dbo->execute("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
	if($mysqlversion > '5.0.1') {
		$dbo->execute("SET sql_mode=''");
	}
}
//$object->debug	= true;

//职业
$sql	= "select * from ".TABLEHEAD."_work";
$rs		= $dbo->execute($sql);
$workarray		= array();
$workarray[0]	= "others";
while(!$rs->EOF) {
	$workarray[$rs->fields['id']]	= $rs->fields['name'];
	$rs->MoveNext();
}
$outstr	= '';

$sql	= "select count(*) as num from ".TABLEHEAD."_raidlog where stat=0 and starttime >= '$ctime'";
if(!empty($raidid)) {
	$sql	.= " and id = '$raidid'";
}
$rcrs	= $dbo->execute($sql);
$raid_count	= $rcrs->fields['num'];

$outstr	.= "phpRaid_Data = { \n";
$outstr	.= "[\"lua_version\"] = \"17B2\", \n";
$outstr	.= "[\"raid_count\"] = \"".$raid_count."\", \n";
$outstr	.= "[\"raids\"] = { \n";

$sql	= "select * from ".TABLEHEAD."_raidlog where stat=0 and starttime >= '$ctime'";
if(!empty($raidid)) {
	$sql	.= " and id = '$raidid'";
}
$rs		= $dbo->execute($sql);

$i		= 0;
while(!$rs->EOF) {
	$rid		= $rs->fields['id'];
	$location	= $rs->fields['name'];
	$date		= date("m.d.Y",strtotime($rs->fields['invitetime']));//07.29.2006 - 19:30:00
	$invite_time= date("H:i:s",strtotime($rs->fields['invitetime']));
	$start_time	= date("H:i:s",strtotime($rs->fields['starttime']));

	$queue_count	= 0;
	$druids_count	= 0;
	$hunters_count	= 0;
	$mages_count	= 0;
	$others_count	= 0;
	$priests_count	= 0;
	$rogues_count	= 0;
	$warlocks_count	= 0;
	$warriors_count	= 0;
	$shamans_count	= 0;
	$paladins_count	= 0;

	//报名情况
	$sql	= "select a.stat as stat,a.signtime as signtime,b.name as uname,b.level as level,c.name as class,d.name as race from ".TABLEHEAD."_signup as a left join  ".TABLEHEAD."_user as b on a.uid=b.id left join  ".TABLEHEAD."_work as c on b.workid=c.id left join  ".TABLEHEAD."_race as d on b.raceid=d.id where a.raid='$rid' and a.stat != 0";
	$srs	= $dbo->execute($sql);
	$userdata	= array();
	$usercount	= array();
	$usercount['queue']		= 0;
	$usercount['druids']	= 0;
	$usercount['hunters']	= 0;
	$usercount['mages']		= 0;
	$usercount['others']	= 0;
	$usercount['priests']	= 0;
	$usercount['rogues']	= 0;
	$usercount['warlocks']	= 0;
	$usercount['warriors']	= 0;

	$userdata['queue']		= array();
	$userdata['druids']		= array();
	$userdata['hunters']	= array();
	$userdata['mages']		= array();
	$userdata['others']		= array();
	$userdata['priests']	= array();
	$userdata['rogues']		= array();
	$userdata['warlocks']	= array();
	$userdata['warriors']	= array();

	while(!$srs->EOF) {
		//不是候补也不是正式
		if(!in_array($srs->fields['stat'],array(2,1))) {
			continue;
		}
		//取英文职业标识符
		$cuclass	= getEnClass($srs->fields['class']);
		if($srs->fields['stat'] == '2') {
			$cuclass	= 'queue';
		}
		$srs->fields['timestamp']	= date("m.d.Y - H:i:s",strtotime($srs->fields['signtime']));
		$srs->fields['comment']	= '';
		$userdata[$cuclass][]		= $srs->fields;
		if(!isset($usercount[$cuclass])) {
			$usercount[$cuclass]	= 0;
		}
		$usercount[$cuclass]	+= 1;
		$srs->MoveNext();
	}
	//echo($sql);  $outstr	.= " \n";
	//var_dump($userdata);
	//var_dump($usercount);
	$outstr	.= "[".$i."] = { \n";
	$outstr	.= "[\"location\"] = \"".$location."\", \n";
	$outstr	.= "[\"date\"] = \"".$date."\", \n";
	$outstr	.= "[\"invite_time\"] = \"".$invite_time."\", \n";
	$outstr	.= "[\"start_time\"] = \"".$start_time."\", \n";
	foreach($usercount as $key=>$val) {
		$outstr	.= "[\"".$key."_count\"] = \"".$val."\", \n";
	}
	foreach($userdata as $key=>$val) {
		$outstr	.= "[\"".$key."\"] = { \n";
		foreach($val as $key2=>$val2) {
			$outstr	.= "[".$key2."] = { \n";
			$outstr	.= "[\"name\"] = \"".$val2['uname']."\", \n";
			$outstr	.= "[\"level\"] = \"".$val2['level']."\", \n";
			$outstr	.= "[\"class\"] = \"".$val2['class']."\", \n";
			$outstr	.= "[\"race\"] = \"".$val2['race']."\", \n";
			$outstr	.= "[\"comment\"] = \"".$val2['comment']."\", \n";
			$outstr	.= "[\"timestamp\"] = \"".$val2['timestamp']."\", \n";
			$outstr	.= "}, \n";
		}
		$outstr	.= "}, \n";
	}
	$outstr	.= "}, \n";

	$i++;
	$rs->MoveNext();
}
$outstr	.= "} \n";
$outstr	.= "}";

//取英文职业
function getEnClass($classid) {
	switch($classid) {
		case '盗贼':
		case 'rogue':
		case '盜賊':
			$class	= 'rogues';
		break;
		case '战士':
		case 'warrior':
		case '戰士':
			$class	= 'warriors';
			break;
		case '法师':
		case 'mage':
		case '法師':
			$class	= 'mages';
			break;
		case '牧师':
		case 'priest':
		case '牧師':
			$class	= 'priests';
			break;	
		case '德鲁伊':
		case 'druid':
		case '德魯伊':
			$class	= 'druids';
			break;
		case '猎人':
		case 'hunter':
		case '獵人':
			$class	= 'hunters';
			break;
		case '术士':
		case 'warlock':
		case '術士':
			$class	= 'warlocks';
			break;
		case '萨满':
		case '萨满祭司':
		case 'shaman':
		case '薩滿':
		case '薩滿祭司':
			$class	= 'others';
			break;	
		case '圣骑士':
		case 'paladin':
		case '聖騎士':
			$class	= 'others';
			break;
		default:
			$class	= 'others';
	}
	Return $class;
}

header('Cache-control: private');
header('Content-Description: File Transfer');
header('Content-Type: application/force-download');
Header("Accept-Ranges: bytes");
Header("Accept-Length: ".strlen($outstr));
Header("Content-Disposition: attachment; filename=phpRaid_Data.lua");
echo $outstr;
exit;

?>