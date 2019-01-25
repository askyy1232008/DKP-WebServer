<?php
/**

 * @description   xajax service

 * @author    tonera@gmail.com

 * @version   $Revision: 1.0 $

 */
require_once ('./php/config.inc.php');
require_once CHINO_MODPATH.'/config/config.inc.php';
require_once (CHINO_LIBPATH.'/xajax.inc.php');

include_once (CHINO_PATH.'/Chino.php');
$GLOBALS['adodb'] = Chino::getObject('adodb');
include_once CHINO_LIBPATH.'/db_mysql.inc.php';
//$GLOBALS['adodb']->debug	= true;

function getInfo($skeyword,$tableid = 'user',$elid	= '') {
	$sOut	= '';
	$elid	= empty($elid)?"keyword":$elid;
	if($tableid == 'item') {
		$tableName	= 'item';
	}elseif($tableid == 'user') {
		$tableName	= 'user';
	}elseif($tableid == 'event') {
		$tableName	= 'event';
	}else {
		$tableName	= 'user';
	}
	
	if(strlen($skeyword) <= 1) {
		$sOut	= '';
	}else {
		$sql	= "select name from ".TABLEHEAD."_".$tableName." where name like '%".$skeyword."%' limit 30";
		$rs		= $GLOBALS['adodb']->execute($sql);
		$userArray	= $rs->getAll();
		$userArray	= is_array($userArray)?$userArray:array();
		foreach($userArray as $key=>$val) {
			$sOut .= "<li><a href='#' onclick=\"javascript:document.getElementById('".$elid."').value='".$val['name']."';document.getElementById('citybox').style.display = 'none';\">".$val['name']."</a></li>";
			$sLastHit = $val['name'];
			$nCount++;
		}
	}
	//var_dump($sOut);
	$objResponse = new xajaxResponse();
	if(strlen($sOut) > 0)
	{
		$sOut = "<ul>".$sOut."</ul>";
		$objResponse->addScript("document.getElementById('citybox').style.display = 'block'");
	}
	else
	{
		$objResponse->addScript("document.getElementById('citybox').style.display = 'none'");
	}

	$objResponse->addAssign("citybox", "innerHTML", $sOut);
	return $objResponse->getXML();
}

//getEvent('ol');
$objAjax = new xajax();
$objAjax->registerFunction('getInfo');
$objAjax->processRequests();
?>