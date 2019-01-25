<?php
/**
+-----------------------------------------------------------------------+
* @autor 张涛 <tonera@gmail.com>;
* @since 2005-12-9
* @version $Id: class_Index.php,v 1.3.7 tonera$
* @description	操作类
* @last update 2008-8-24
+-----------------------------------------------------------------------+
*/
$GLOBALS['adodb'] = Chino::getObject('adodb');
include_once CHINO_LIBPATH.'/db_mysql.inc.php';
$GLOBALS['adodb']->debug	= false;
class wow {
	//取某表分页信息
	function getPerPageInfo($sql,$urlparam = '') {
		Chino::loadLib("browser");
		//取总数
		if(preg_match("/group/i",$sql)) {
			$rs				= $GLOBALS['adodb']->execute($sql);
			$totalRecords	= $rs->RecordCount();
		}else {
			$pattern		= "select(.*)from";
			$replacement	= "select count(*) from";
			$allsql			= eregi_replace($pattern,$replacement,$sql);
			$rs				= $GLOBALS['adodb']->execute($allsql);
			$totalRecords	= $rs->fields[0];
		}
		$mypagenav		= new browser();
		$langArr		= array(
			$GLOBALS['lang']['page_first'],
			$GLOBALS['lang']['page_backward'],
			$GLOBALS['lang']['page_forward'],
			$GLOBALS['lang']['page_last'],
			$GLOBALS['lang']['current'],
			$GLOBALS['lang']['total'],
			$GLOBALS['lang']['goto'],
			$GLOBALS['lang']['pagenum'],
			$GLOBALS['lang']['pagename'],
			$GLOBALS['lang']['pageop']);
		$mypagenav->initialize($totalRecords,PAGE_RPP_DEF,$_GET['offset']);
		$mypagenav->setLang($langArr);
		$var_parameter	= $urlparam;
		$pageText		= $mypagenav->ActionPage($var_parameter,PAGE_PPL_DEF); 
		$offset			= $_GET['offset']?$_GET['offset']:0;
		$rs				= $GLOBALS['adodb']->SelectLimit($sql,PAGE_RPP_DEF,$offset);
		if($mypagenav->c_totalpage <= 1) {
			$pageText	= '';
		}
        if($rs) {
        	return array($pageText,$rs->getAll());
        }else {
        	return array($pageText,array());
        }
	}
	//得到一组用户获得的dkp或花费的dkp
	function getUserDkp($uidarray,$cid,$sd,$isspent=true) {
		if(!is_array($uidarray)) {
			Return array();
		}
		$exp	= implode(',',$uidarray);
		if(empty($exp)) {
			Return array();
		}
		if($isspent) {
			$sql	= "select uid,ROUND(sum(value),$sd) as value from ".TABLEHEAD."_itemdis where uid in(".$exp.") and value > 0 and stat='1' and cid='$cid' group by uid";
		}else {
			$sql	= "select uid,ROUND(sum(ABS(value)),$sd) as value from ".TABLEHEAD."_itemdis where uid in(".$exp.") and (value < 0 or stat='-1') and cid='$cid' group by uid";
		}
		//echo($sql);
		$rs		= $GLOBALS['adodb']->execute($sql);
		$rearray	= array();
		while(!$rs->EOF) {
			$rearray[$rs->fields['uid']]	= $rs->fields['value'];
			$rs->MoveNext();
		}
		Return $rearray;
	}
	//取记录信息
	function readRecord($tablename,$id) {
		$sql	= "select * from ".TABLEHEAD."_".$tablename." where id='$id'";
		$rs		= $GLOBALS['adodb']->execute($sql);
		Return $rs->getAll();
	}

	//取种族
	function getUserClass() {
		$sql		= "select id,name from ".TABLEHEAD."_work";
		$rs		= $GLOBALS['adodb']->execute($sql);
		Return $rs->getAll();
	}
	//自定义sql
	function executeSql($sql) {
		$rs		= $GLOBALS['adodb']->execute($sql);
		Return $rs->getAll();
	}
	//取某表所有信息(不分页)
	function getInfoByTable($tablename,$id='',$orderby='') {
		$sql	= "select * from ".TABLEHEAD."_".$tablename;
		$sql	= $id?$sql." where id='$id'":$sql;
		$sql	= $orderby?$sql." order by ".$orderby." desc":$sql;
		//var_dump($sql);
		$rs = $GLOBALS['adodb']->execute($sql);
        return $rs->getAll();
	}
	//取副本的raid数
	function getCopyNums() {
		$raidarr	= $this->getInfoByTable('copy');
		$raidarr	= is_array($raidarr)?$raidarr:array();
		foreach($raidarr as $key=>$val) {
			$randnum[$val['id']]	= $val['raidtotal'];
		}
        return $randnum;
	}
	//取显示配置的副本
	function getShowCopy() {
		$copyarr	= $this->executeSql("select * from ".TABLEHEAD."_config where vname='showcopy'");
		$copyarr	= is_array($copyarr)?$copyarr:array();
		$copystr	= $copyarr[0]['value'];
		$copyarr	= array();
		$sql		= "select * from ".TABLEHEAD."_copy where id in(".$copystr.")";
		$copyarr	= $this->executeSql($sql);
		$copyarr	= is_array($copyarr)?$copyarr:array();
		foreach($copyarr as $key=>$val) {
			$rearr[$val['id']]	= $val['name'];
		}
		Return $rearr;
	}
    //get user group
	function getUserGroup($isSelect=false) {
		$grouparrays	= $this->getInfoByTable("group");
		$grouparray	    = array();
		if($isSelect) {
			$grouparray[]	= $GLOBALS['lang']['pleaselect'];
		}		
		if(is_array($grouparrays)) {
			foreach($grouparrays as $key=>$val) {
				$grouparray[$val['id']]	= $val['name'];
			}
		}
		Return $grouparray;
	}
}

?>