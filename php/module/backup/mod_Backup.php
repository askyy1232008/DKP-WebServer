<?php
/**
+-----------------------------------------------------------------------+
* @autor 张涛 <tonera@gmail.com>;
* @since 2005-12-1
* @version $Id: mod_admin.php,v 1.3.1 tonera$
* @description	admin
* @last update 2006-5-18
+-----------------------------------------------------------------------+
*/
$GLOBALS['smarty'] = Chino::getObject('smarty');
$GLOBALS['smarty']->left_delimiter	= "{|";
$GLOBALS['smarty']->right_delimiter	= "|}";
require_once CHINO_MODPATH.'/config/config.inc.php';
$GLOBALS['adodb'] = Chino::getObject('adodb');
include_once CHINO_LIBPATH.'/db_mysql.inc.php';
$GLOBALS['adodb']->debug	= false;
set_time_limit(0);
class Cpage extends Base {

    var $alias	= 'act';	//动作变量名称
	var $dblink	= '';
	var $dbname	= '';
	var $path	= '';
	var $ismysql5	= false;
	var $syslog		= '';
	var $gconfig	= array();

	/**
     *页面载入时执行的动作
     *
     *@return  void
     *@access  public
     */
    function onLoad() {
		$this->_adminCheck(10);
		if(empty($_SESSION['wowdkp']['user']) or !$_SESSION['wowdkp']['usertype'] or $_SESSION['wowdkp']['dkptable']!=TABLEHEAD) {
			header("Location: index.php?module=login");
			exit;
		}
		$this->path	= CHINO_MODPATH."/config/dump/";
		//if dump can't be write ,exit
		if(!is_writable($this->path)) {
			$crs	= @chmod($this->path, 0777);
			if(!$crs) {
				exit("你的php\module\config\dump目录不允许写入文件,请将其权限设为可写.<br />Please set the directory : php\module\config\dump is been write!");
			}
		}
		$rs		= mysql_connect(DBHOST,DBUSER,DBPASS);

		$mysqlversion	= mysql_get_server_info();
		if($mysqlversion > '4.1') {
			$this->ismysql5	= true;
			mysql_query("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
			if($mysqlversion > '5.0.1') {
				mysql_query("SET sql_mode=''");
			}
		}

		if(!$rs) {
			$this->throwInfo('error');
		}else {
			$this->dblink	= $rs;
			mysql_select_db(DBNAME);
		}
		$sql	= "select * from ".TABLEHEAD."_config";
		$rs		= mysql_query($sql);
		while($row = mysql_fetch_array($rs)) 
		{
			$this->gconfig[trim($row['vname'])]	= trim($row['value']);
		}

		$GLOBALS['smarty']->assign("lang",$GLOBALS['lang']);
		include_once(CHINO_LIBPATH . "/class_Syslog.php");
		$this->syslog	= new Syslog($GLOBALS['adodb'],$_SESSION['wowdkp']['userid'],$_SESSION['wowdkp']['user'],TABLEHEAD);
		$GLOBALS['smarty']->template_dir	= CHINO_WWWROOT.'/theme/'.$this->gconfig['dstyle'].'/admin';
		$GLOBALS['smarty']->assign("theme",$this->gconfig['dstyle']);
    }

	function onDatabackup() {
		//取表信息$tableArray
		$sql		= "SHOW TABLE STATUS FROM `".DBNAME."`";
		$stats		= mysql_query ($sql) or die("stupid error");
		$num_tables = mysql_num_rows($stats);
		if ($num_tables==0) {
			die("ERROR: Database contains no tables");
		}
		$tabletotal	= array();
		$tabletotal['Rows']	= $tabletotal['Data_length'] = $tabletotal['Index_length'] = 0;
		while ($rows=mysql_fetch_array($stats) ) {
			if(strpos($rows['Name'],TABLEHEAD."_")===false) {
				continue;
			}else {
				$tableArray[]		= $rows;
				$tabletotal['Rows']	+= $rows['Rows'];
				$tabletotal['Data_length']	+= $rows['Data_length'];
				$tabletotal['Index_length']	+= $rows['Index_length'];
			}			
		}
		
		//备份文件
		$dir		= opendir($this->path);
		$file		= readdir($dir);
		$fileArray	= array();
		$i			= 0;
		while ($file	= readdir ($dir)) { 
			if ($file != "." && $file != ".." &&  (eregi("\.sql",$file) || eregi("\.gz",$file))){ 
				if (eregi("\.sql$",$file) ) {
					$fileArray[$i]['filetype']	= "sql";
				} else {
					$fileArray[$i]['filetype']	= "gz";
				}
				$fileArray[$i]['filename']	= $file;
				$fileArray[$i]['filesize']	= round(filesize($this->path.$file) / 1024, 2);
				$fileArray[$i]['filemtime']	= date("d-m-Y",filemtime($this->path.$file));
			}
			$i++;
		}
		closedir($dir);
		$GLOBALS['smarty']->assign('getval',$_GET);
		$GLOBALS['smarty']->assign('fileArray',$fileArray);
		$GLOBALS['smarty']->assign('tabletotal',$tabletotal);
		$GLOBALS['smarty']->assign('tableArray',$tableArray);
		$GLOBALS['smarty']->display('form_dbackup.htm');
	}

	//restore database
	function onRestore() {
		extract ($_REQUEST);
		$rers	= false;
		if ($file!="") {
		  if (eregi("gz",$file)) { //zip file decompress first than show only
			 eregi("((.)+).gz+",$file,$regs);
			 $sqlfile	= $this->path.$regs[1];
			 $fp = @fopen($sqlfile,"w");
			 $zp = @gzopen($this->path.$file, "rb");
			 if(!$fp) {
				  die("No sql file can be created"); 
			 }    
			 if(!$zp) {
				  die("Cannot read zip file");
			 }    
			 while(!gzeof($zp)){
			  $data	= gzgets($zp, 8192);// buffer php
			  $rers	= fwrite($fp,$data);
			 }
			 fclose($fp);
			 gzclose($zp);
			 $file='';
			 if(!$rers) {
			 	$info	= $GLOBALS['lang']['unzip'].$GLOBALS['lang']['backup'].$GLOBALS['lang']['faild'];
			 }else {
			 	$info	= $GLOBALS['lang']['unzip'].$GLOBALS['lang']['backup'].$GLOBALS['lang']['succeed'];
			 }
			 $this->syslog->writeLog($GLOBALS['oplog']['a_unzip'],$sqlfile);
		  }else {		//载入
				flush();
				set_time_limit(0);
				$file	= trim($file);
				$file	= fread(fopen($this->path.$file, "r"), filesize($this->path.$file));
				$query	= explode(";#%%\n",$file);
				for ($i=0;$i < count($query)-1;$i++) {
					$rers	= mysql_db_query(DBNAME,$query[$i],$this->dblink) or die(mysql_error());
				}			
				if(!$rers) {
					$info	= $GLOBALS['lang']['restore'].$GLOBALS['lang']['backup'].$GLOBALS['lang']['faild'];
				}else {
					$info	= $GLOBALS['lang']['restore'].$GLOBALS['lang']['backup'].$GLOBALS['lang']['succeed'];
				}
				$this->syslog->writeLog($GLOBALS['oplog']['a_restore'],$this->path.$file);
			}
		}
		$this->throwInfo($info,$_SERVER['HTTP_REFERER']);
	}

	//view viewdb
	function onViewdb() {
		//header("Content-type: text/html ;Charset=utf-8 ");
		$fp = @fopen($this->path.$_GET['file'],"r");
		while (!feof($fp)) {
			$contents = fread($fp, 8192);
			echo(nl2br($contents));
		}
		fclose($fp);
	}

	//deletefile
	function onDeletefile() {
		$file	= trim($_GET['file']);
		@unlink($this->path.$file);
		$this->syslog->writeLog($GLOBALS['oplog']['d_backup'],$this->path.$file);
		header("Location: ".$_SERVER['HTTP_REFERER']);
	}

	//downloadfile
	function onDownload() {
		$file	= trim($_GET['file']);
		$fp		= @fopen($this->path.$_GET['file'],"r");
		header('Cache-control: private');
		header('Content-Description: File Transfer');
		header('Content-Type: application/force-download');
		Header("Accept-Ranges: bytes");
		Header("Accept-Length: ".filesize($this->path.$file));
		Header("Content-Disposition: attachment; filename=" . $file);
		echo fread($fp,filesize($this->path.$file));
		fclose($fp);
		exit;
	}

	//create backup
	function onCreatebackup() {
		//var_dump($_POST);
		//exit;
		flush();
		if (!is_dir($this->path)) mkdir($this->path, 0766);
		chmod($this->path, 0777);
		$filetype		= "sql";
		$backupfilename	= $_POST['backupfilename'];
		$backfilename	= $this->path.$backupfilename.".".$filetype;
		$fp2 = fopen ($backfilename,"w");
			$copyr="# Table backup from MySql PHP Backup\n".
				   "# AB Webservices 1999-2004\n".
				   "# www.absoft-my.com/pondok\n".
				   "# Creation date: ".date("d-M-Y h:s",time())."\n".
				   "# Database: ".DBNAME."\n".
				   "# MySQL Server version: ".mysql_get_server_info()."\n\n" ;
		fwrite ($fp2,$copyr);
		fclose ($fp2);
		@chmod($backfilename, 0777);
		if(file_exists($this->path . $backupfilename.".gz"))
		{ 
		   @unlink($this->path . $backupfilename.".gz");
		} 
		$recreate = 0; 
		$cur_time=date("Y-m-d H:i");
		$i = 0;
		$numtables	= count($_POST['tables']);
		//2006-9-5去掉只备份表结构
		//$_POST['structonly']	= isset($_POST['structonly'])?$_POST['structonly']:'';
		
		if($numtables>0) {
			$newfile	= '';
			foreach($_POST['tables'] as $key=>$val) {
				if(!empty($val)) {
					$newfile .= $this->_get_def(DBNAME,trim($val));
					$newfile .= "\n\n";
					
					$fp		= fopen ($backfilename,"a");
					fwrite ($fp,$newfile);
					$newfile	= '';

					//2006-9-5去掉只备份表结构
					$newfile .= $this->_get_content(DBNAME,$val,$filetype,$backupfilename);
					$newfile .= "\n\n";
					//if ($_POST['structonly']!="Yes") {
					//	$newfile .= $this->_get_content(DBNAME,$val,$filetype,$backupfilename);
					//	$newfile .= "\n\n";
					//}
				}
			}
		}

		$fp		= fopen ($backfilename,"a");
		fwrite ($fp,$newfile);
		$wrs	= fwrite ($fp,"# Valid end of backup from MySql PHP Backup\n");
		fclose ($fp);
		//make a zip file
		if(get_extension_funcs('zlib')) {
			$this->_compress($backupfilename.".".$filetype);
		}

		if(!$wrs) {
			$this->throwInfo($GLOBALS['lang']['create'].$GLOBALS['lang']['backup'].$GLOBALS['lang']['faild'],$_SERVER["HTTP_REFERER"]);
		}else {
			$this->syslog->writeLog($GLOBALS['oplog']['a_backup'],$backfilename);
			$this->throwInfo($GLOBALS['lang']['create'].$GLOBALS['lang']['backup'].$GLOBALS['lang']['succeed'],$_SERVER["HTTP_REFERER"]);
		}
	}

	function _get_def($dbname, $table) {
		$def = "";
		$def .= "DROP TABLE IF EXISTS $table;#%%\n";
		$def .= "CREATE TABLE $table (\n";
		$result = mysql_db_query($dbname, "SHOW FIELDS FROM $table",$this->dblink) or die("Table $table not existing in database");
		while($row = mysql_fetch_array($result)) {
			$row['Extra']	= isset($row['Extra'])?$row['Extra']:'';
			$def .= "    $row[Field] $row[Type]";
			if ($row["Default"] != "") $def .= " DEFAULT '$row[Default]'";
			if ($row["Null"] != "YES") $def .= " NOT NULL";
			if ($row['Extra'] != "") $def .= " $row[Extra]";
				$def .= ",\n";
		 }
		 $def = ereg_replace(",\n$","", $def);
		 $result = mysql_db_query($dbname, "SHOW KEYS FROM $table",$this->dblink);
		 while($row = mysql_fetch_array($result)) {
			  $kname=isset($row['Key_name'])?$row['Key_name']:'';
			  if(($kname != "PRIMARY") && ($row['Non_unique'] == 0)) $kname="UNIQUE|$kname";
			  if(!isset($index[$kname])) $index[$kname] = array();
			  $index[$kname][] = $row['Column_name'];
		 }
		 while(list($x, $columns) = @each($index)) {
			  $def .= ",\n";
			  if($x == "PRIMARY") $def .= "   PRIMARY KEY (" . implode($columns, ", ") . ")";
			  else if (substr($x,0,6) == "UNIQUE") $def .= "   UNIQUE ".substr($x,7)." (" . implode($columns, ", ") . ")";
			  else $def .= "   KEY $x (" . implode($columns, ", ") . ")";
		 }
		 $def		.= "\n)";
		 if($this->ismysql5) {
		 	$def	.= " DEFAULT CHARSET=utf8";
		 }
		 $def .= ";#%%";
		 return (stripslashes($def));
	}

	function _get_content($dbname, $table,$filetype,$backupfilename) {
		 $content="";
		 $result = mysql_db_query($dbname, "SELECT * FROM $table",$this->dblink) or die("Cannot get content of table");
		 // after every 5000 rows we write than no memory troubles
		 $cnt=0;
		 while($row = mysql_fetch_row($result)) {
			 $insert = "INSERT INTO $table VALUES (";
			 for($j=0; $j<mysql_num_fields($result);$j++) {
				if(!isset($row[$j])) $insert .= "NULL,";
				else if($row[$j] != "") $insert .= "'".addslashes($row[$j])."',";
				else $insert .= "'',";
			 }
			 $insert  = ereg_replace(",$","",$insert);
			 $insert .= ");#%%\n";
			 $content.= $insert;
			 $cnt++;
			 if ($cnt==5000) {
				$fp = fopen ($this->path.$backupfilename.".$filetype","a");
				fwrite ($fp,$content);
				fclose ($fp);
				$cnt    = 0;
				$content= '';
			 }
			 
		 }
		 return $content;
	} // end ret_content

	//zip function
	function _compress($zip) {
		// compress a file without using shell
		$sqlfile	= $this->path.rtrim($zip);
		$zipfile	= $this->path.rtrim($zip).".gz";
		$fp = @fopen($sqlfile,"rb");
		if (file_exists($zipfile)) unlink($zipfile);
		$zp = @gzopen($zipfile, "wb9");
		if (!$fp) {
			die("No sql file found"); 
		}
		if(!$zp) {
			die("Cannot create zip file");
		}
		while(!feof($fp)){
		$data	= fgets($fp, 8192);	// buffer php
		gzwrite($zp,$data);
		}
		fclose($fp);
		gzclose($zp);
		return true;
	}
	//power check
	function _adminCheck($power) {
		if($_SESSION['wowdkp']['power']>=$power) {
			Return true;
		}else {
			exit("Error: Request denied!");
		}
	}

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
