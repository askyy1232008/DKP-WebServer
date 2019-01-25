<?php
/**
+-----------------------------------------------------------------------+
* @autor 张涛 <tonera@gmail.com>;
* @since 2006-8-17
* @version $Id: act_OtherSet.php.php,v 1.3.7 tonera$
* @description	admin
* @last update 2008-8-24
+-----------------------------------------------------------------------+
*/
include_once CHINO_LIBPATH.'/db_mysql.inc.php';

class OtherSet extends Base {
	var $adm		= '';
	var $syslog		= '';
	//杂项设置
	function OtherSet() {
		include_once(CURRENTPATH . "/class_admin.php");
        $this->adm = new admin;
		$this->adm->_adminCheck(10);
		include_once(CHINO_LIBPATH . "/class_Syslog.php");
		$this->syslog	= new Syslog($GLOBALS['adodb'],$_SESSION['wowdkp']['userid'],$_SESSION['wowdkp']['user'],TABLEHEAD);
		switch($_GET['obj']) {
			case 'update':
				$ver	= @file("http://www.dkper.com/webservice/wowdkper/version.php");
				if(!$ver) {
					$GLOBALS['smarty']->assign("info",$GLOBALS['lang']['note16']);
				}else {
					$localver	= $this->onVersion(false);
					if($ver[0]==$localver) {
						$GLOBALS['smarty']->assign("info",sprintf($GLOBALS['lang']['note17'],$localver));
					}else {
						$GLOBALS['smarty']->assign("info",sprintf($GLOBALS['lang']['note18'],$localver,$ver[0]));
					}
				}
				break;
			case 'pageset':		//各页面显示数设定
				$pagecfgfile	= CHINO_MODPATH.'/config/page.inc';
				$pagecfg		= @parse_ini_file($pagecfgfile, true);
				$str			= '';
				$inArray	= array('i_news','i_user','i_raids','i_event','i_item','i_dis','m_row');
				if(is_array($_POST)) {
					$pageArr	= array();
					$str		= '';
					foreach($_POST as $key=>$val) {
						if(in_array($key,$inArray)) {
							$pageArr[$key]	= $val;
						}						
					}
					$pagecfg['page']	= $pageArr;
				}
				foreach($pagecfg as $key=>$val) {
					$str	.= "[$key]\n";
					if(is_array($val)) {
						foreach($val as $key1=>$val1) {
							$str	.= $key1."=".$val1.";\n";
						}
					}
				}
				$rs		= $this->adm->writeInfoToFile($pagecfgfile,$str);
				if($rs) {
					$this->syslog->writeLog($GLOBALS['oplog']['m_pageset'],$str);
					$info	= $GLOBALS['lang']['pagenav'].$GLOBALS['lang']['succeed'];
				}else {
					$info	= $GLOBALS['lang']['pagenav'].$GLOBALS['lang']['faild'];
				}
				$GLOBALS['smarty']->assign("pagesetinfo",$info);
				break;
			case 'userpass':
				$pw		= md5($_POST['password']);
				$sql	= "update ".TABLEHEAD."_user set password='$pw' where password=''";
				$rs		= $GLOBALS['adodb']->execute($sql);
				$GLOBALS['adodb']->close();
				if($rs) {
					$this->syslog->writeLog($GLOBALS['oplog']['m_member_passwd'],$_POST['password']);
					$info	= $GLOBALS['lang']['modify'].$GLOBALS['lang']['succeed'];
				}else {
					$info	= $GLOBALS['lang']['modify'].$GLOBALS['lang']['faild'];
				}
				$GLOBALS['smarty']->assign("userpassinfo",$info);
				break;
			case 'datafix':
				//会员出席raid，公会副本总raid
				$sql	= "select a.uid as uid,b.cid as cid,count(distinct a.eid) as num from ".TABLEHEAD."_itemdis as a left join ".TABLEHEAD."_event as b on a.eid=b.id where b.etid=1 group by a.uid,b.cid ";
				$rs		= $GLOBALS['adodb']->execute($sql);
				while(!$rs->EOF) {
					$uid	= $rs->fields['uid'];
					$cid	= $rs->fields['cid'];
					$num	= $rs->fields['num'];
					$sql	= "update ".TABLEHEAD."_dkpvalues set raidnum='$num' where uid='$uid' and copyid='$cid'";
					$GLOBALS['adodb']->execute($sql);
					$rs->MoveNext();
				}
				//各副本总raid数
				$sql	= "select cid,count(*) as num from ".TABLEHEAD."_event where etid=1 group by cid";
				$rs		= $GLOBALS['adodb']->execute($sql);
				while(!$rs->EOF) {
					$cid	= $rs->fields['cid'];
					$num	= $rs->fields['num'];
					$sql	= "update ".TABLEHEAD."_copy set raidtotal = '$num' where id='$cid'";
					$GLOBALS['adodb']->execute($sql);
					$rs->MoveNext();
				}
				if($rs) {
					$info	= $GLOBALS['lang']['note37'].$GLOBALS['lang']['succeed'];
				}else {
					$info	= $GLOBALS['lang']['note37'].$GLOBALS['lang']['faild'];
				}
				$GLOBALS['adodb']->close();
				$GLOBALS['smarty']->assign("datafixinfo",$info);
				break;
			default:
		}
		//取分页设置
		$pagecfgfile	= CHINO_MODPATH.'/config/page.inc';
		$pagecfg		= @parse_ini_file($pagecfgfile, true);
		$pageini		= $pagecfg['page'];
		$GLOBALS['smarty']->assign("pageinc",$pageini);
		$GLOBALS['smarty']->display("otherset.htm");
	}
	//ver
	function onVersion($show=true) {
		if($show) {
			echo("WOW-DKPer v1.3.7\n");
			echo("Author: tonera\n");
			echo("Last update: 2008-9-23\n");
			echo("470730f5cd682089dfdd53c668310ad2\n");
		}else {
			Return "1.3.7";
		}		
	}
}