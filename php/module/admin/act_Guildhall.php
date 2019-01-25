<?php
/**
+-----------------------------------------------------------------------+
* @autor 张涛 <tonera@gmail.com>;
* @since 2006-8-17
* @version $Id: act_OtherSet.php.php,v 1.3.2 tonera$
* @description	admin
* @last update 2006-8-17
+-----------------------------------------------------------------------+
*/
class Guildhall extends Base {
	var $gconfig	= array();
	var $sd			= 2;
	//guild hall
	function Guildhall() {
		include_once(CURRENTPATH . "/class_admin.php");
        $this->adm = new admin;
		$this->adm->_adminCheck(5);
		$gconfigarr	= $this->adm->getInfoByTable('config');
		if(is_array($gconfigarr)) {
			foreach($gconfigarr as $key=>$val) {
				$this->gconfig[trim($val['vname'])]	= trim($val['value']);
			}
		}
		//保留小数
		$this->sd	= (int)$this->gconfig['sdecimal'];
		$this->sd	= $this->sd>=0||$this->sd<=2?$this->sd:2;
		unset($gconfigarr);
		//副本
		$_GET['cid']	= $_GET['cid']?$_GET['cid']:(int)$this->gconfig['defaultcopy'];
		$cid		= (int)$_GET['cid'];
		$cid		= $cid?$cid:1;
		$copyarrays	= $this->adm->getInfoByTable("copy");
		if(is_array($copyarrays)) {
			foreach($copyarrays as $key=>$val) {
				$copyarray[$val['id']]	= $val['name'];
			}
		}
		$tmpval	= '';
		$userdata['guildname']	= $this->gconfig['guildname'];
		$sql	= "select count(*) as num,ROUND(sum(dkpvalue),$this->sd) as dkpvalue from ".TABLEHEAD."_dkpvalues where copyid='$cid'";
		$tmpval	= $this->adm->excuteSql($sql);
		$userdata['members']	= $tmpval[0]['num'];
		$userdata['totaldkp']	= $tmpval[0]['dkpvalue'];
		//当前总dkp
		$sql	= "select ROUND(sum(dkpvalue),$this->sd) as dkpvalue from ".TABLEHEAD."_dkpvalues where copyid='$cid' and dkpvalue>0";
		$tmpval	= $this->adm->excuteSql($sql);
		$userdata['ctotaldkp']	= $tmpval[0]['dkpvalue'];

		$sql	= "select count(*) as num from ".TABLEHEAD."_event where etid=1 and cid='$cid'";
		$tmpval	= $this->adm->excuteSql($sql);
		$userdata['raids']	= $tmpval[0]['num'];
		//近期活跃会员数
		$lasttime	= date("Y-m-d",time()-($this->gconfig['acttime']*30*24*3600));
		$sql		= "select count(*) as num from ".TABLEHEAD."_user where lastraidtime>='$lasttime' and  stat=1";
		$tmpval		= $this->adm->excuteSql($sql);
		$userdata['actmems']	= (int)$tmpval[0]['num'];
		//总会员数
		$sql		= "select count(*) as num from ".TABLEHEAD."_user where stat=1";
		$tmpval		= $this->adm->excuteSql($sql);
		$userdata['totalmembers']	= (int)$tmpval[0]['num'];
		if($userdata['totalmembers']==0) {
			$userdata['actrate']	= "0%";
		}else {
			$userdata['actrate']	= round($userdata['actmems']/$userdata['totalmembers']*100,2)."%";
		}
		$sql	= "select ROUND(sum(value),$this->sd) as num from ".TABLEHEAD."_itemdis where iid is not null and cid='$cid'";
		$tmpval	= $this->adm->excuteSql($sql);
		$userdata['itemdkp']	= $tmpval[0]['num'];

		$sql	= "select ROUND(sum(a.value),$this->sd) as dkp,c.name as wname,a.stat as stat,c.id as wid,count(distinct b.id) as cnum from ".TABLEHEAD."_itemdis as a left join ".TABLEHEAD."_user as b on a.uid=b.id left join ".TABLEHEAD."_work as c on b.workid=c.id where a.cid='$cid' group by b.workid,a.stat";
		$tmpval	= $this->adm->excuteSql($sql);
		//var_dump($sql);
		$classdata	= array();
		if(is_array($tmpval)) {
			$userdata['ctotalearn']			= isset($userdata['ctotalearn'])?$userdata['ctotalearn']:0;
			$userdata['ctotalspent']		= isset($userdata['ctotalspent'])?$userdata['ctotalspent']:0;
			foreach($tmpval as $key=>$val) {
				$classdata[$val['wid']]['dkp']		= isset($classdata[$val['wid']]['dkp'])?$classdata[$val['wid']]['dkp']:0;
				$classdata[$val['wid']]['cnum']		= isset($classdata[$val['wid']]['cnum'])?$classdata[$val['wid']]['cnum']:0;
				$classdata[$val['wid']]['wid']		= $val['wid'];
				$classdata[$val['wid']]['wname']	= $val['wname'];
				$classdata[$val['wid']]['dkp']		+= $val['dkp']*$val['stat'];
				if($val['stat']=='1') {
					$classdata[$val['wid']]['earn']	= $val['dkp'];
					$classdata[$val['wid']]['cnum']		+= $val['cnum'];
					$userdata['ctotalearn']		+= $classdata[$val['wid']]['earn'];
				}elseif($val['stat']=='-1') {
					$classdata[$val['wid']]['spent']= $val['dkp'];
					$userdata['ctotalspent']	+= $classdata[$val['wid']]['spent'];
				}else {
					;
				}
			}			
		}

		$sql	= "select ROUND(sum(a.value),$this->sd) as dkp,c.name as rname,a.stat as stat,c.id as rid,count(distinct b.id) as cnum from ".TABLEHEAD."_itemdis as a left join ".TABLEHEAD."_user as b on a.uid=b.id left join ".TABLEHEAD."_race as c on b.raceid=c.id where a.cid='$cid' group by b.raceid,a.stat";
		$tmpval	= $this->adm->excuteSql($sql);
		$racedata	= array();
		if(is_array($tmpval)) {
			$rcnum	= 0;
			$userdata['rtotalearn']			= isset($userdata['rtotalearn'])?$userdata['rtotalearn']:0;
			$userdata['rtotalspent']		= isset($userdata['rtotalspent'])?$userdata['rtotalspent']:0;
			foreach($tmpval as $key=>$val) {
				$racedata[$val['rid']]['dkp']		= isset($racedata[$val['rid']]['dkp'])?$racedata[$val['rid']]['dkp']:0;
				$racedata[$val['rid']]['cnum']		= isset($racedata[$val['rid']]['cnum'])?$racedata[$val['rid']]['cnum']:0;
				$racedata[$val['rid']]['rid']		= $val['rid'];
				$racedata[$val['rid']]['rname']	= $val['rname'];
				$racedata[$val['rid']]['dkp']		+= $val['dkp']*$val['stat'];
				if($val['stat']=='1') {
					$racedata[$val['rid']]['earn']	= $val['dkp'];
					$racedata[$val['rid']]['cnum']		+= (int)$val['cnum'];	//只能在此统计
					$userdata['rtotalearn']		+= $racedata[$val['rid']]['earn'];
				}elseif($val['stat']=='-1') {
					$racedata[$val['rid']]['spent']= $val['dkp'];
					$userdata['rtotalspent']	+= $racedata[$val['rid']]['spent'];
				}else {
					;
				}
				$rcnum	+= $racedata[$val['rid']]['cnum'];
			}
			$racedata['']['cnum']	= $userdata['members']-$rcnum;
		}
		$userdata['totaldkp']	= $userdata['totaldkp']?$userdata['totaldkp']:1;
		$userdata['members']	= $userdata['members']?$userdata['members']:1;
		$userdata['ctotalearn']	= isset($userdata['ctotalearn'])?$userdata['ctotalearn']:1;
		$userdata['ctotalspent']= isset($userdata['ctotalspent'])?$userdata['ctotalspent']:1;
		$userdata['rtotalearn']	= isset($userdata['rtotalearn'])?$userdata['rtotalearn']:1;
		$userdata['rtotalspent']= isset($userdata['rtotalspent'])?$userdata['rtotalspent']:1;
		$userdata['ctotaldkp']	= $userdata['ctotaldkp']?$userdata['ctotaldkp']:1;

		$GLOBALS['smarty']->assign("userdata",$userdata);
		$GLOBALS['smarty']->assign("gconfig",$this->gconfig);
		$GLOBALS['smarty']->assign("classdata",$classdata);
		$GLOBALS['smarty']->assign("racedata",$racedata);
		$GLOBALS['smarty']->assign("getval",$_GET);
		$GLOBALS['smarty']->assign("copyarray",$copyarray);

		$tplcontent		= $GLOBALS['smarty']->display("view_guildhall.htm");
	}
}