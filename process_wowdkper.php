<?php
/**
+-----------------------------------------------------------------------+
* @autor 张涛 <tonera at gmail.com>;
* @since 2006-4-7
* @version $Id: process_wowdkper.php,v 1.3 tonera$
* @description	return member's dkp values on line
+-----------------------------------------------------------------------+
page=get_current_dkp&f=4&fdays=30&l=4&ldays=15&c=x
2006-5-18添加c参加.表示副本ID.将下载此副本的会员dkp
*/
error_reporting(0);

header("Content-Type: text/html; charset=UTF-8");
if(file_exists("./php/config.inc.php")) {
	$cfile	= "./php/config.inc.php";
}elseif(file_exists("../php/config.inc.php")) {
	$cfile	= "../php/config.inc.php";
}else {
	echo "请先安装wowdkper 1.3或以上版本.";
}
require_once $cfile;
require_once CHINO_MODPATH.'/config/config.inc.php';

(intval($_GET['f']) == 1) ? $fi=true : $fi=false;
$fid	= intval($_GET['fdays']);		//最近N天活动的会员
(intval($_GET['l']) == 1) ? $lr=true : $lr=false;
$ln		= intval($_GET['ldays']);		//最近N次raid
$link	= mysql_connect(DBHOST, DBUSER, DBPASS);
mysql_select_db(DBNAME);

$mysqlversion	= mysql_get_server_info();

if($mysqlversion > '4.1') {
	mysql_query("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
	if($mysqlversion > '5.0.1') {
		mysql_query("SET sql_mode=''");
	}
}


$pname			= 'process_wowdkper.php';
$version		= '0.37';
$dkper_version	= '1.3';
$op			= strtolower($_GET['page']);
$className	= getClassName($link);
$c			= $_GET['c']?(int)$_GET['c']:1;

$arr	= array();
switch($op) {
	case 'get_current_dkp':
		//玩家物品积分
		$sql			= "select count(*) as num from ".TABLEHEAD."_user";
		$arr			= executeSql($link,$sql);
		$totalPlayer	= $arr[0]['num'];
		$sql			= "select count(*) as num from ".TABLEHEAD."_item";
		$arr			= executeSql($link,$sql);
		$totalItems		= $arr[0]['num'];
		$sql			= "select sum(dkpvalue) as num from ".TABLEHEAD."_dkpvalues where copyid='$c'";
		$arr			= executeSql($link,$sql);
		$totalPoints	= $arr[0]['num'];
		echo "--NFO:NAME:".$pname."\n";
		echo "--NFO:VERSION:". $version ."\n";		
		echo "--NFO:EQDKP_VERSION:WOWDKPER". $dkper_version ."\n";	
		echo "--NFO:PAGE:summary_info\n";
		echo "--NFO:PLAYERS:$totalPlayer\n";
		echo "--NFO:ITEMS:$totalItems\n";	
		echo "--NFO:POINTS:$totalPoints\n\n";
		break;
	case 'get_sqlvars':
		$dc = date("m.d.y g:i a"); 
		echo "--==========================================<BR>\n";
		echo "-- Created by $pname (Version ".$version.")<BR>\n";
		echo "--                                          <BR>\n";
		echo "-- File Created at $dc            <BR>\n";
		echo "--==========================================<BR><BR>\n\n";
		echo "--NFO:NAME:process_wowdkper.php\n";
		echo "--NFO:VERSION:". $version ."\n";		
		echo "--NFO:WOW-DKPer_VERSION:". $dkper_version ."\n";
		echo "--NFO:PAGE:sqlvars\n";
		//DKP_ROLL_PLAYERS->玩家->积分\种族\物品->物品名称->分值\时间
		$sql		= "select a.id as id,a.name as name,a.workid as workid,c.dkpvalue as dkpvalue,b.name as c from ".TABLEHEAD."_user as a left join ".TABLEHEAD."_work as b on a.workid=b.id left join ".TABLEHEAD."_dkpvalues as c on a.id=c.uid where c.copyid='$c'";
		$arr		= executeSql($link,$sql);
		$playerArr	= array();
		foreach($arr as $key=>$val) {
			$playerArr[$val['id']]['id']		= $val['id'];
			$playerArr[$val['id']]['name']		= strtolower($val['name']);
			$playerArr[$val['id']]['Class']		= $className[$val['workid']];
			$playerArr[$val['id']]['RaidPoints']= $val['dkpvalue'];
		}
		//取每玩家获得的物品信息
		$sql	= "select a.uid as uid, a.iid as iid, a.distime as distime, a.value as value,b.name as name from ".TABLEHEAD."_itemdis as a left join ".TABLEHEAD."_item as b on a.iid=b.id where a.iid is not null and b.stat=1 and a.cid='$c' order by a.uid";
		$arr		= executeSql($link,$sql);
		$playerDisArr	= array();
		foreach($arr as $key=>$val) {
			$playerDisArr[$val['uid']][$val['iid']]['name']	= strtolower($val['name']);
			$playerDisArr[$val['uid']][$val['iid']]['cost']	= $val['value'];
			$playerDisArr[$val['uid']][$val['iid']]['date']	= $val['distime'];
		}
		//print_r($playerDisArr);
		//exit;
		
		echo "DKP_ROLL_PLAYERS = {\n";
		foreach($playerArr as $key=>$val) {
			echo " [\"". $val['name'] ."\"] = {\n";
			echo "  [\"RaidPoints\"] = \"".$val['RaidPoints']."\",\n";
			echo "  [\"Class\"] = \"".$val['Class']."\",\n";

			if(is_array($playerDisArr[$val['id']])) {
				echo "   [\"Items\"] = {\n";
				foreach($playerDisArr[$val['id']] as $key2=>$val2) {
					echo "   [\"".$val2['name']."\"] = {\n";	
					echo "    [\"cost\"] = ".$val2['cost'].",\n";
					echo "    [\"date\"] = \"". date("M j, y", strtotime($val2['date'])) ."\",\n";
					echo "   },\n";	
				}
				echo "  },\n";
			}

			echo " },\n";
		}
		echo "}\n\n";

		//DKP_ROLL_RAIDS 每次raid产生的物品/所有者/值/值累加/参与者/职业分类及各类职业总分和人数
		echo "DKP_ROLL_RAIDS = {\n";
		$sql	= "select * from ".TABLEHEAD."_event where cid='$c' order by raidtime desc";
		if($lr) {
			$sql	.= " limit 0,".$ln;
		}
		$rs		= mysql_query($sql) or die(mysql_error());
		$total_items	= 0;
		$total_points	= 0;
		while($row	= mysql_fetch_array($rs)) {
			$raid_id = $row['id'];
			$raid_timestamp = strtotime($row['raidtime']);
			$raid_name = $row['name'];
			//$raid_worth = $row['raid_value'];
			$raid_date = date("M j, y", $raid_timestamp);
			
			// Raid Information essentials
			echo " [\"$raid_timestamp-$raid_id\"] = {\n";
			echo "  [\"raid_id\"] = $raid_id,\n";
			echo "  [\"raid_name\"] = \"$raid_name\",\n";
			echo "  [\"raid_date\"] = \"$raid_date\",\n";
			//echo "  [\"raid_worth\"] = $raid_worth,\n";
			//取此raid物品总dkp值
			$sql		= "select sum(value) as num from ".TABLEHEAD."_itemdis where eid='$raid_id' and cid='$c' and iid is not null";
			$arr		= executeSql($link,$sql);
			$totalDkp	= $arr[0]['num'];
			$totalDkp	= $totalDkp?$totalDkp:0;
			echo "  [\"raid_worth\"] = $totalDkp,\n";
			//取此raid物品及值.所有者

			$sql	= "select a.iid as iid, a.uid as uid, a.value as value,b.name as itemname,c.name as username from ".TABLEHEAD."_itemdis as a left join ".TABLEHEAD."_item as b on a.iid=b.id left join ".TABLEHEAD."_user as c on a.uid=c.id where a.iid is not null and a.eid='$raid_id' and a.cid='$c' ";
			$arr		= executeSql($link,$sql);
			if (count($arr)>0) {
					echo "  [\"raid_items\"] = {\n";
			}
			foreach($arr as $key=>$val) {
				echo "   [\"".$val['itemname']."\"] = {\n";											
				echo "    ['buyer'] = \"".$val['username']."\",\n";
				echo "    ['value'] = ".$val['value'].",\n";
				echo "   },\n";
				$total_items++;
				$total_points	+= $val['value'];
			}
			if (count($arr)>0) {
					echo "  },\n";				
			}

			//参与者
			echo "  [\"raid_attendees\"] = {\n";
			$sql	= "select a.uid as uid, b.name as username,b.workid as workid from ".TABLEHEAD."_itemdis as a left join ".TABLEHEAD."_user as b on a.uid=b.id where a.eid='$raid_id' group by a.uid";
			$arr		= executeSql($link,$sql);
			//各职业数目和总数
			$classnum	= array();
			$classtotal	= 0;
			foreach($arr as $key=>$val) {
				echo "   \"".strtolower($val['username'])."\",\n";
				$classnum[$val['workid']]++;
				$classtotal++;
			}
			echo "  },\n";

			//职业细分所占人数比.人数
			echo "  ['class_distribution'] = {\n";
			foreach($classnum as $key=>$val) {
				echo "   ['".$className[$key]."'] = {\n";
				$p_class = number_format(($val/$classtotal)*100, 1, '.', '');
				echo "    ['percent'] = $p_class,\n";
				echo "    ['total'] = $val,\n";
				echo "   },\n";	
			}
			echo "  },\n";
		echo " },\n";
		}
		echo "}\n\n";

		//更新时间信息
		echo "DKP_ROLL_UPDATE_INFO = {\n";
		echo " [\"date\"] = \"$dc\",\n";
		echo " [\"process_dkp_ver\"] = $version,\n";
		echo " [\"total_players\"] = $classtotal,\n";
		echo " [\"total_items\"] = $total_items,\n";
		echo " [\"total_points\"] = $total_points,\n";
		echo "}";
		break;
	case 'download':
		$dc = date("m.d.y g:i a"); 
		$outstr ='';
		$outstr.= "--==========================================<BR>\n";
		$outstr.= "-- Created by $pname (Version ".$version.")<BR>\n";
		$outstr.= "--                                          <BR>\n";
		$outstr.= "-- File Created at $dc            <BR>\n";
		$outstr.= "--==========================================<BR><BR>\n\n";
		$outstr.= "--NFO:NAME:process_wowdkper.php\n";
		$outstr.= "--NFO:VERSION:". $version ."\n";		
		$outstr.= "--NFO:WOW-DKPer_VERSION:". $dkper_version ."\n";
		$outstr.= "--NFO:PAGE:sqlvars\n";
		//DKP_ROLL_PLAYERS->玩家->积分\种族\物品->物品名称->分值\时间
		$sql		= "select a.id as id,a.name as name,a.workid as workid,c.dkpvalue as dkpvalue,b.name as c from ".TABLEHEAD."_user as a left join ".TABLEHEAD."_work as b on a.workid=b.id left join ".TABLEHEAD."_dkpvalues as c on a.id=c.uid where c.copyid='$c'";
		$arr		= executeSql($link,$sql);
		$playerArr	= array();
		foreach($arr as $key=>$val) {
			$playerArr[$val['id']]['id']		= $val['id'];
			$playerArr[$val['id']]['name']		= strtolower($val['name']);
			$playerArr[$val['id']]['Class']		= $className[$val['workid']];
			$playerArr[$val['id']]['RaidPoints']= $val['dkpvalue'];
		}
		//取每玩家获得的物品信息
		$sql	= "select a.uid as uid, a.iid as iid, a.distime as distime, a.value as value,b.name as name from ".TABLEHEAD."_itemdis as a left join ".TABLEHEAD."_item as b on a.iid=b.id where a.iid is not null and b.stat=1 and a.cid='$c' order by a.uid";
		$arr		= executeSql($link,$sql);
		$playerDisArr	= array();
		foreach($arr as $key=>$val) {
			$playerDisArr[$val['uid']][$val['iid']]['name']	= $val['name'];
			$playerDisArr[$val['uid']][$val['iid']]['cost']	= $val['value'];
			$playerDisArr[$val['uid']][$val['iid']]['date']	= $val['distime'];
		}
		//print_r($playerDisArr);
		//exit;
		
		$outstr.= "DKP_ROLL_PLAYERS = {\n";
		foreach($playerArr as $key=>$val) {
			$outstr.= " [\"". $val['name'] ."\"] = {\n";
			$outstr.= "  [\"RaidPoints\"] = \"".$val['RaidPoints']."\",\n";
			$outstr.= "  [\"Class\"] = \"".$val['Class']."\",\n";

			if(is_array($playerDisArr[$val['id']])) {
				$outstr.= "   [\"Items\"] = {\n";
				foreach($playerDisArr[$val['id']] as $key2=>$val2) {
					$outstr.= "   [\"".$val2['name']."\"] = {\n";	
					$outstr.= "    [\"cost\"] = ".$val2['cost'].",\n";
					$outstr.= "    [\"date\"] = \"". date("M j, y", strtotime($val2['date'])) ."\",\n";
					$outstr.= "   },\n";	
				}
				$outstr.= "  },\n";
			}

			$outstr.= " },\n";
		}
		$outstr.= "}\n\n";

		//DKP_ROLL_RAIDS 每次raid产生的物品/所有者/值/值累加/参与者/职业分类及各类职业总分和人数
		$outstr.= "DKP_ROLL_RAIDS = {\n";
		$sql	= "select * from ".TABLEHEAD."_event where cid='$c' order by raidtime desc";
		if($lr) {
			$sql	.= " limit 0,".$ln;
		}
		$rs		= mysql_query($sql) or die(mysql_error());
		$total_items	= 0;
		$total_points	= 0;
		while($row	= mysql_fetch_array($rs)) {
			$raid_id = $row['id'];
			$raid_timestamp = strtotime($row['raidtime']);
			$raid_name = $row['name'];
			//$raid_worth = $row['raid_value'];
			$raid_date = date("M j, y", $raid_timestamp);
			
			// Raid Information essentials
			$outstr.= " [\"$raid_timestamp-$raid_id\"] = {\n";
			$outstr.= "  [\"raid_id\"] = $raid_id,\n";
			$outstr.= "  [\"raid_name\"] = \"$raid_name\",\n";
			$outstr.= "  [\"raid_date\"] = \"$raid_date\",\n";
			//$outstr.= "  [\"raid_worth\"] = $raid_worth,\n";
			//取此raid物品总dkp值
			$sql		= "select sum(value) as num from ".TABLEHEAD."_itemdis where eid='$raid_id' and cid='$c' and iid is not null";
			$arr		= executeSql($link,$sql);
			$totalDkp	= $arr[0]['num'];
			$totalDkp	= $totalDkp?$totalDkp:0;
			$outstr.= "  [\"raid_worth\"] = $totalDkp,\n";
			//取此raid物品及值.所有者

			$sql	= "select a.iid as iid, a.uid as uid, a.value as value,b.name as itemname,c.name as username from ".TABLEHEAD."_itemdis as a left join ".TABLEHEAD."_item as b on a.iid=b.id left join ".TABLEHEAD."_user as c on a.uid=c.id where a.iid is not null and a.eid='$raid_id' and a.cid='$c' ";
			$arr		= executeSql($link,$sql);
			if (count($arr)>0) {
					$outstr.= "  [\"raid_items\"] = {\n";
			}
			foreach($arr as $key=>$val) {
				$outstr.= "   [\"".$val['itemname']."\"] = {\n";											
				$outstr.= "    ['buyer'] = \"".$val['username']."\",\n";
				$outstr.= "    ['value'] = ".$val['value'].",\n";
				$outstr.= "   },\n";
				$total_items++;
				$total_points	+= $val['value'];
			}
			if (count($arr)>0) {
					$outstr.= "  },\n";				
			}

			//参与者
			$outstr.= "  [\"raid_attendees\"] = {\n";
			$sql	= "select a.uid as uid, b.name as username,b.workid as workid from ".TABLEHEAD."_itemdis as a left join ".TABLEHEAD."_user as b on a.uid=b.id where a.eid='$raid_id' group by a.uid";
			$arr		= executeSql($link,$sql);
			//各职业数目和总数
			$classnum	= array();
			$classtotal	= 0;
			foreach($arr as $key=>$val) {
				$outstr.= "   \"".strtolower($val['username'])."\",\n";
				$classnum[$val['workid']]++;
				$classtotal++;
			}
			$outstr.= "  },\n";

			//职业细分所占人数比.人数
			$outstr.= "  ['class_distribution'] = {\n";
			foreach($classnum as $key=>$val) {
				$outstr.= "   ['".$className[$key]."'] = {\n";
				$p_class = number_format(($val/$classtotal)*100, 1, '.', '');
				$outstr.= "    ['percent'] = $p_class,\n";
				$outstr.= "    ['total'] = $val,\n";
				$outstr.= "   },\n";	
			}
			$outstr.= "  },\n";
		$outstr.= " },\n";
		}
		$outstr.= "}\n\n";

		//更新时间信息
		$outstr.= "DKP_ROLL_UPDATE_INFO = {\n";
		$outstr.= " [\"date\"] = \"$dc\",\n";
		$outstr.= " [\"process_dkp_ver\"] = $version,\n";
		$outstr.= " [\"total_players\"] = $classtotal,\n";
		$outstr.= " [\"total_items\"] = $total_items,\n";
		$outstr.= " [\"total_points\"] = $total_points,\n";
		$outstr.= "}";
		header('Cache-control: private');
		header('Content-Description: File Transfer');
		header('Content-Type: application/force-download');
		Header("Accept-Ranges: bytes");
		Header("Accept-Length: ".strlen($outstr));
		Header("Content-Disposition: attachment; filename=sqlvars.lua");
		echo $outstr;
		exit;
		break;
	case 'copy':
		$sql		= "select id,name from ".TABLEHEAD."_copy";
		$copyarr	= executeSql($link,$sql);
		$copyarr	= is_array($copyarr)?$copyarr:array();
		$cr			= array();
		foreach($copyarr as $key=>$val) {
			$cr[]	= $val['name']."|||".$val['id'];
		}
		echo(implode("***",$cr));
		break;	
	default:
		echo("<h><b>WOW-DKPER's interface for DKP System </b></h><br />");
		echo "ScriptName:".$pname."<br />";
		echo("Version:1.0<br />");
		echo("Author:tonera<br />");
		echo("Last update time:2006-5-28<br />");
		echo("Link:<a href='http://www.iboko.net/project/' target='_blank'>http://www.iboko.net/project/</a><br />");
}



function executeSql($link,$sql) {
	$rs		= mysql_query($sql,$link) or die(mysql_error());
	$reArr	= array();
	while($row	= mysql_fetch_array($rs,MYSQL_ASSOC)) {
		$reArr[]	= $row;
	}
	Return $reArr;
}

function getClassName($link) {
	$sql	= "select id,name from ".TABLEHEAD."_work";
	$rs		= mysql_query($sql,$link) or die(mysql_error());
	$reArr	= array();
	while($row	= mysql_fetch_array($rs,MYSQL_ASSOC)) {
		$reArr[$row['id']]	= $row['name'];
	}
	Return $reArr;
}


?>