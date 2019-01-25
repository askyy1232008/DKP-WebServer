<?php
/**
+-----------------------------------------------------------------------+
* @autor tonera <tonera@gmail.com>;
* @since 2005-12-28
* @version $Id: class_login.php,v 1.3.1 tonera$
* @description	login op
+-----------------------------------------------------------------------+
*/


class login {
	function usercheck($u,$p) {
		$p		= md5($p);
		$sql	= "select * from ".TABLEHEAD."_admuser where username='$u'";
		$rs		= $GLOBALS['adodb']->SelectLimit($sql,1);
		if(empty($rs->fields['username'])) {
			Return "-1";
		}elseif($p!=$rs->fields['password']) {
			Return "-2";
		}else {
			$_SESSION['wowdkp']['userid']	= $rs->fields['id'];
			$_SESSION['wowdkp']['user']		= $u;
			$_SESSION['wowdkp']['usertype']	= "1";	//admin user
			$_SESSION['wowdkp']['dkptable']	= TABLEHEAD;
			$_SESSION['wowdkp']['power']	= $rs->fields['power'];
			$_SESSION['wowdkp']['cid']		= $rs->fields['cid'];
			$_SESSION['wowdkp']['db']		= DBNAME;
			Return "1";
		}		
	}
	function membercheck($u,$p) {
		$p		= md5($p);
		$sql	= "select * from ".TABLEHEAD."_user where name='$u'";
		$rs		= $GLOBALS['adodb']->SelectLimit($sql,1);
		if(empty($rs->fields['name'])) {
			Return "-1";
		}elseif($p!=$rs->fields['password']) {
			Return "-2";
		}elseif($rs->fields['stat']==0) {
			Return "-3";
		}else {
			$_SESSION['member']['uid']			= $rs->fields['id'];
			$_SESSION['member']['username']		= $u;
			Return "1";
		}
	}
}


?>