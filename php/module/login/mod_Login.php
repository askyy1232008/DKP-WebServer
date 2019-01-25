<?php
/**
+-----------------------------------------------------------------------+
* @autor уелн <tonera@gmail.com>;
* @since 2005-12-28
* @version $Id: mod_Login.php,v 1.3.1 tonera$
* @description	╣гб╪
+-----------------------------------------------------------------------+
*/
$GLOBALS['smarty']	= Chino::getObject('smarty');
$GLOBALS['smarty']->left_delimiter	= "{|";
$GLOBALS['smarty']->right_delimiter	= "|}";
require_once CHINO_MODPATH.'/config/config.inc.php';

$GLOBALS['adodb'] = Chino::getObject('adodb');
include_once CHINO_LIBPATH.'/db_mysql.inc.php';
$GLOBALS['adodb']->debug	= false;

class Cpage extends Base{
	var $alias		= 'act';
	var $loginop	= '';
	var $syslog		= '';
	var $gconfig	= array();

    function onDefault() {
        $this->onShowlogin();
    }

    function onHello() {
        echo "hello wowdkper";
    }
	function onLoad() {
		if(!empty($_SESSION['wowdkp']['user']) and $_SESSION['wowdkp']['usertype'] and  $_SESSION['wowdkp']['dkptable']==TABLEHEAD and $_SESSION['wowdkp']['db']==DBNAME) {
			header("Location: index.php?module=admin");
			exit;
		}
		$grs	= $GLOBALS['adodb']->execute("select * from ".TABLEHEAD."_config");
		if($grs) {
			while(!$grs->EOF) {
				$this->gconfig[trim($grs->fields['vname'])]	= trim($grs->fields['value']);
				$grs->MoveNext();
			}
		}
		$GLOBALS['smarty']->template_dir	= CHINO_WWWROOT.'/theme/'.$this->gconfig['dstyle'].'/index';
		//db op
		include_once(CURRENTPATH . "/class_login.php");
		$this->loginop	= new login();
		$GLOBALS['lang']['sitetitle']	= SITETITLE;
		$GLOBALS['smarty']->assign("lang",$GLOBALS['lang']);
		include_once(CHINO_LIBPATH . "/class_Syslog.php");
		if(!isset($_SESSION['wowdkp']['userid'])){
			$userid	= 0;
			$user	= '';
		}else {
			$userid	= $_SESSION['wowdkp']['userid'];
			$user	= $_SESSION['wowdkp']['user'];
		}

		$this->syslog	= new Syslog($GLOBALS['adodb'], $userid, $user, TABLEHEAD);
		$GLOBALS['smarty']->assign("theme",$this->gconfig['dstyle']);
    }
	//Showlogin form
	function onShowlogin() {
		$GLOBALS['smarty']->display("Showlogin.htm");
	}
	//act login
	function onLogin() {
		$username	= $_POST['username'];
		$password	= $_POST['password'];
		$rs			= $this->loginop->usercheck($username,$password);
		if($rs=='-1') {
            $this->syslog->writeLog($GLOBALS['oplog']['a_login'],$GLOBALS['lang']['managesys'],"Failed with $username ");
			$this->throwInfo($GLOBALS['lang']['note13'], $_SERVER["HTTP_REFERER"]);
		}elseif($rs=='-2') {
			$this->syslog->writeLog($GLOBALS['oplog']['a_login'],$GLOBALS['lang']['managesys'],"Failed with $username ");
			$this->throwInfo($GLOBALS['lang']['note14'], $_SERVER["HTTP_REFERER"]);
		}elseif($rs=='1') {
			$this->syslog->init($_SESSION['wowdkp']['userid'],$_SESSION['wowdkp']['user'],TABLEHEAD);
			$this->syslog->writeLog($GLOBALS['oplog']['a_login'],$GLOBALS['lang']['managesys'],$GLOBALS['lang']['login'].$GLOBALS['lang']['succeed']);
			$this->throwInfo($GLOBALS['lang']['login'].$GLOBALS['lang']['succeed'], "index.php?module=admin");
		}
	}
	//member login
	function onMemlogin() {
		$username	= $_POST['username'];
		$password	= $_POST['password'];
		$jumpurl	= $_POST['jumpurl'];
		$rs			= $this->loginop->membercheck($username,$password);
		if($rs=='1' and !empty($password)) {
			$uid	= $_SESSION['member']['uid'];
			$this->throwInfo($GLOBALS['lang']['login'].$GLOBALS['lang']['succeed'], "index.php?module=index&act=viewmember&id=$uid");
		}elseif($rs == '-3') {
			$this->throwInfo($GLOBALS['lang']['note50'], $jumpurl);
		}else {
			$this->throwInfo($GLOBALS['lang']['login'].$GLOBALS['lang']['faild'], $jumpurl);
		}
	}
	//member logout
	function onLogout() {
		unset($_SESSION['member']);
		header("Location: ".$_SERVER['HTTP_REFERER']);
	}
	/**
	 * login raise info 
	 *
	 * @param unknown_type $
	 */
	function throwInfo($info, $goto='', $target='self') {
		//var_dump($GLOBALS['smarty']);
        $GLOBALS['smarty']->assign('info', $info);
        $GLOBALS['smarty']->assign('goto', $goto);
        $GLOBALS['smarty']->assign('target', $target);
        $GLOBALS['smarty']->display('info.htm');
        exit;
	}
}




?>