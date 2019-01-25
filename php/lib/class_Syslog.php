<?php
/**
+-----------------------------------------------------------------------+
* @autor 张涛 <tonera@gmail.com>;
* @since 2006-11-29
* @version $Id: class_Syslog.php,v 1.0 tonera$
* @description 系统日志类，依存于ADODBO类和日志表kad_syslog
+-----------------------------------------------------------------------+
*/

class Syslog {
	var $uid		= 0;
	var $username	= '';
	var $tablehead	= 'sys';		//日志表前缀
	var $dbo		= '';
	
	/**
	 * 构造
	 *
	 * @param obj $dbo
	 * @return none
	 */
	function Syslog($dbo,$uid,$username,$tablehead=''){
		$this->dbo	= $dbo;
		$this->init($uid,$username,$tablehead);
	}
	
	/**
	 * 初始化
	 *
	 * @param int $uid
	 * @param string $username
	 * @param string $tablehead
	 */
	function init($uid,$username,$tablehead=''){
		$this->uid			= (int)$uid;
		$this->username		= (string)$username;
		$this->tablehead	= $tablehead;
	}
	
	/**
	 * 记录日志
	 *
	 * @param string $op 描述符在配置文件init.inc.php中
	 * @param string $obj
	 * @param string $des
	 * @return boolean
	 */
	function writeLog($op,$obj,$des=''){
		$tb			= $this->tablehead.'_syslog';
		$optime		= time();
		$insarray	= array('uid'=>$this->uid,'uname'=>$this->username,'op'=>$op,'obj'=>$obj,'des'=>$des,'optime'=>$optime);
		//2007-1-15更改以符合dkper
		$sql		= "select * from $tb where id = -1";
		$rs			= $this->dbo->Execute($sql);
		$insertSQL	= $this->dbo->GetInsertSQL($rs, $insarray);
		$rs			= $this->dbo->Execute($insertSQL);
		return $rs;
	}
}


?>
