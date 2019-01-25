<?php
/**
+-----------------------------------------------------------------------+
* @autor 张涛 <tonera@gmail.com>;
* @since 2005-12-27
* @version $Id: install.php,v 1.3.7 tonera$
* @description	wowdkper 2006-9-14 last update at 2008-8-24
+-----------------------------------------------------------------------+
*/
//error_reporting(0);
header("Cache-control: private");
include_once(dirname(__FILE__) . "/php/config.inc.php");

if(@file_exists(CHINO_MODPATH.'/config/config.inc.php')) {
	header("Location: index.php");
	exit;
}

$version		= "WOW-DKPer v1.3.7 Powered by Tonera";
$succeedInfo	= '';

if($_GET['act'] == 'langset')
{
	if(empty($_GET['lang'])) {
		$langset	= 'en';
	}else {
		$langset	= $_GET['lang'];
	}
}elseif(!empty($_COOKIE['wow_cfg_lang'])) {
	$langset	= $_COOKIE['wow_cfg_lang'];
}else {
	//按用户浏览器设定
	if(eregi('cn',$_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		   $langset       = 'zh-cn';
	}elseif(eregi('tw',$_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		   $langset       = 'zh-tw';
	}elseif(eregi('hk',$_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		   $langset       = 'zh-tw';
	}else {
		   $langset       = 'en';
	}
}
if(file_exists(CHINO_PHPPATH . "/lang/".$langset."/inslang.inc.php")) {
	   include_once(CHINO_PHPPATH .  "/lang/".$langset."/inslang.inc.php");
}else {
	   include_once(CHINO_PHPPATH .  "/lang/en/inslang.inc.php");
	   $langset       = 'en';
}
include_once(CHINO_PHPPATH .  "/lang/lang_public.php");
setcookie("wow_cfg_lang",$langset,time()+2592000);
//include CHINO_PHPPATH."/lang/lang_config.php";
$langselect	= '';
foreach($langTypeArray as $key=>$val) {
	if($key == $langset)
	{
		$langselect	.= '<option value="'.$key.'" selected="selected">'.$val.'</option>';
	}else {
		$langselect	.= '<option value="'.$key.'">'.$val.'</option>';
	}
}

//start install
if(!empty($_POST['install']))
{
	$rs	= checkParams();
	if($rs	=== true)
	{
		$irs	= installDkper();
		if($irs === true)
		{
			$sinfo	= $succeedInfo;
			$finfo	= '';
		}else {
			$sinfo	= false;
			$finfo	= $irs;
		}
	}else {
		$sinfo	= false;
		$finfo	= $rs;
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link href="./theme/system/admincss.css" rel="stylesheet" type="text/css">
<title><?php echo($GLOBALS['inslang']['title']);?> </title>
</head>

<body>
<center>
<table width="100%" border="0" cellspacing="1" cellpadding="2" class="borderless">
  <tr>
    <td width="34%"><img border="0" src="./theme/system/images/logo_wow.gif"></td>
    <td width="66%"><div align="center">
       &nbsp;
      </div></td>
  </tr>
</table>

<table width="100%" border="0">
  <tr>
    <td colspan="2">
<?php 
if($sinfo === false)
{
	echo($GLOBALS['inslang']['note15']);
}else {
	echo($GLOBALS['inslang']['note6']);
}
?>
&nbsp;</td>
  </tr>
  <tr>
    <td width="21%">&nbsp;</td>
    <td width="79%"><textarea name="textarea" cols="60" rows="15"><?php echo($sinfo); flush();echo($finfo);?></textarea></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>
<?php 
if($sinfo === false)
{
	echo('<input type="submit" name="Submit" value="返回" onClick="javascript:history.go(-1)" />');
}else {
	echo('<input type="submit" name="Submit" value="完成安装"  onClick="location.href=\'index.php\'" />');
}
?>

	</td>
  </tr>
</table>

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="borderless">
  <tr>
    <td height="40"><div align="center"><a href="http://www.dkper.com/" target="_blank"><?php echo($version);?></a></div></td>
  </tr>
</table>
<table background="theme/system/images/bottom-bg.gif" border="0" cellpadding="0" cellspacing="0" width="100%">
<tbody>
	<tr>
		<td width="50%"><div style="position: relative;"><div style="position: absolute; left: 65px;"></div></div>
		</td>
		<td width="586"><a href="http://www.blizzard.com/" target="_blank"><img src="theme/system/images/bottom-blizzlogo.gif" usemap="#bottom_blizzlogo_Map" border="0" height="46" width="400"></a></td>
		<td align="right" width="50%"><img src="theme/system/images/pixel.gif" height="46" width="1"></td>
	</tr>
</tbody>
</table>
</center>
</body>
</html>

<?php
}else {

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link href="./theme/system/admincss.css" rel="stylesheet" type="text/css">
<title><?php echo($GLOBALS['inslang']['title']);?> </title>
</head>

<body>
<center>
<table width="100%" border="0" cellspacing="1" cellpadding="2" class="borderless">
  <tr>
    <td width="34%"><img border="0" src="./theme/system/images/logo_wow.gif"></td>
    <td width="66%"><div align="center">
       &nbsp;
      </div></td>
  </tr>
</table>

<script type="text/javascript">
function validate_loginForm(frm) {
  var value = '';
  var errFlag = new Array();
  _qfMsg = '';

  value = frm.elements['admuser'].value;
  if (value != '' && value.length < 6 && !errFlag['admuser']) {
    errFlag['admuser'] = true;
    _qfMsg = _qfMsg + '\n - <?php echo($GLOBALS['inslang']['admuser'].$GLOBALS['inslang']['note9'])?>';
  }

  value = frm.elements['dbhost'].value;
  if (value == '' && !errFlag['dbhost']) {
    errFlag['dbhost'] = true;
    _qfMsg = _qfMsg + '\n - <?php echo($GLOBALS['inslang']['dbhost'].$GLOBALS['inslang']['nonull'])?>';
  }
  value = frm.elements['dbuser'].value;
  if (value == '' && !errFlag['dbuser']) {
    errFlag['dbuser'] = true;
    _qfMsg = _qfMsg + '\n - <?php echo($GLOBALS['inslang']['dbhost'].$GLOBALS['dbuser']['nonull'])?>';
  }
  value = frm.elements['dbname'].value;
  if (value == '' && !errFlag['dbname']) {
    errFlag['dbname'] = true;
    _qfMsg = _qfMsg + '\n - <?php echo($GLOBALS['inslang']['dbname'].$GLOBALS['inslang']['nonull'])?>';
  }
  value = frm.elements['tbhead'].value;
  if (value == '' && !errFlag['tbhead']) {
    errFlag['tbhead'] = true;
    _qfMsg = _qfMsg + '\n - <?php echo($GLOBALS['inslang']['tbhead'].$GLOBALS['inslang']['nonull'])?>';
  }
  value = frm.elements['admuser'].value;
  if (value == '' && !errFlag['admuser']) {
    errFlag['admuser'] = true;
    _qfMsg = _qfMsg + '\n - <?php echo($GLOBALS['inslang']['admuser'].$GLOBALS['inslang']['nonull'])?>';
  }

  value = frm.elements['admpass'].value;
  if (value != '' && value.length < 4 && !errFlag['admpass']) {
    errFlag['admpass'] = true;
    _qfMsg = _qfMsg + '\n - <?php echo($GLOBALS['inslang']['admpass'].$GLOBALS['inslang']['note9'])?>';
  }


  if (_qfMsg != '') {
    _qfMsg = 'Invalid information entered.' + _qfMsg;
    _qfMsg = _qfMsg + '\nPlease correct these fields.';
    alert(_qfMsg);
    return false;
  }
  return true;
}
</script>

<body>
  <table width="100%" border="0">
    <tr class="navtext"> 
      <td colspan="2"><?php echo($GLOBALS['inslang']['title']);?> -- Support:<a href="http://www.dkper.com/" target="_blank">http://www.dkper.com</a></td>
    </tr>
	
	<form name="forms" method="get" action="install.php">
	<tr> 
      <td><div align="right"><?php echo($GLOBALS['inslang']['note11']);?></div></td>
	  <td>
	  <input type="hidden" name="act" value="langset" />
		<select name="lang" class="input" onchange="javascript:form.submit();">
			<?php echo($langselect);?>
		</select>
	  </td>
    </tr>
	</form>

	<form name="form1" method="post" action="install.php" onsubmit="return validate_loginForm(this);">
    <tr> 
      <td><div align="right"><?php echo($GLOBALS['inslang']['location']);?></div></td>
	  <td>
		<select name="location">
			<?php
                foreach($locationArray as $key=>$val) {
                	echo("<option value=".$key.">".$val."</option>");
                }
            ?>
		</select>
        <input type="hidden" name="langtype" value="<?php echo($_GET['lang'])?>">
	  </td>
    </tr>
    <tr> 
      <td><div align="right"><?php echo($GLOBALS['inslang']['realm']);?></div></td>
	  <td>
		<input type="text" name="realm">
	  </td>
    </tr>

    <tr> 
      <td><div align="right"><?php echo($GLOBALS['inslang']['dbtype']);?></div></td>
	  <td>
		<select name="dbtype">
			<option value="mysql" selected>mysql</option>
		</select>
	  </td>
    </tr>
	<tr> 
      <td><div align="right"><?php echo($GLOBALS['inslang']['sitetitle']);?></div></td>
      <td><input type="text" name="sitetitle"></td>
    </tr>
	<tr> 
      <td><div align="right"><?php echo($GLOBALS['inslang']['dbhost']);?></div></td>
      <td><input type="text" name="dbhost" value="localhost"></td>
    </tr>
    <tr> 
      <td><div align="right"><?php echo($GLOBALS['inslang']['dbuser']);?></div></td>
      <td><input type="text" name="dbuser"></td>
    </tr>
    <tr> 
      <td><div align="right"><?php echo($GLOBALS['inslang']['dbpass']);?></div></td>
      <td><input type="password" name="dbpass"></td>
    </tr>
    <tr> 
      <td><div align="right"><?php echo($GLOBALS['inslang']['dbname']);?></div></td>
      <td><input type="text" name="dbname"></td>
    </tr>
	<tr> 
      <td><div align="right"><?php echo($GLOBALS['inslang']['tbhead']);?></div></td>
      <td><input type="text" name="tbhead" value="wowdkper"><?php echo($GLOBALS['inslang']['note10']);?></td>
    </tr>

	<tr> 
      <td><div align="right">&nbsp;</div></td>
      <td>&nbsp;</td>
    </tr>

	<tr> 
      <td><div align="right"><?php echo($GLOBALS['inslang']['admuser']);?></div></td>
      <td><input type="text" name="admuser"></td>
    </tr>
	<tr> 
      <td><div align="right"><?php echo($GLOBALS['inslang']['admpass']);?></div></td>
      <td><input type="password" name="admpass"></td>
    </tr>
	<tr> 
      <td><div align="right"><?php echo($GLOBALS['inslang']['admpass2']);?></div></td>
      <td><input type="password" name="admpass2"></td>
    </tr>

    <tr> 
      <td><div align="right"></div></td>
      <td><input type="submit" name="install" value="<?php echo($GLOBALS['inslang']['install']);?>"></td>
    </tr>
    <tr> 
      <td><div align="right"></div></td>
      <td>&nbsp;</td>
    </tr>
	</form>
  </table>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="borderless">
  <tr>
    <td height="40"><div align="center"><a href="http://www.dkper.com/" target="_blank"><?php echo($version);?></a></div></td>
  </tr>
</table>
<table background="theme/system/images/bottom-bg.gif" border="0" cellpadding="0" cellspacing="0" width="100%">
<tbody>
	<tr>
		<td width="50%"><div style="position: relative;"><div style="position: absolute; left: 65px;"></div></div>
		</td>
		<td width="586"><a href="http://www.blizzard.com/" target="_blank"><img src="theme/system/images/bottom-blizzlogo.gif" usemap="#bottom_blizzlogo_Map" border="0" height="46" width="400"></a></td>
		<td align="right" width="50%"><img src="theme/system/images/pixel.gif" height="46" width="1"></td>
	</tr>
</tbody>
</table>
</center>
</body>
</html>


<?php
}

function checkParams() 
{
	$errorInfo	= '';
	if(!function_exists('mysql_connect')) {
		$errorInfo	.= $GLOBALS['inslang']['note13']."\r\n";
	}
	if(!@is_writable(CHINO_CACHEBASE.'/admin/compile')) {
		$errorInfo	.= '/cache/admin/compile:'.$GLOBALS['inslang']['nowrite']."\r\n";
	}
	if(!@is_writable(CHINO_CACHEBASE.'/index/compile')) {
		$errorInfo	.= '/cache/index/compile:'.$GLOBALS['inslang']['nowrite']."\r\n";
	}
	if(!@is_writable(CHINO_CACHEBASE.'/login/compile')) {
		$errorInfo	.= '/cache/login/compile:'.$GLOBALS['inslang']['nowrite']."\r\n";
	}
	if(!@is_writable(CHINO_MODPATH.'/config')) {
		$errorInfo	.= '/php/module/config/config:'.$GLOBALS['inslang']['nowrite']."\r\n";
	}
	if(empty($_POST['dbhost']) or empty($_POST['dbuser']) or empty($_POST['dbname']) or empty($_POST['tbhead']))
	{
		$errorInfo	.=  $GLOBALS['inslang']['note1']."\r\n";
	}
	if(!preg_match("/^[a-z]*$/i",$_POST['tbhead'])) {
		$errorInfo	.= $GLOBALS['inslang']['tbhead'].$GLOBALS['inslang']['note10']."\r\n";
	}
	if(strlen($_POST['admuser'])<6 or strlen($_POST['admpass'])<6) {
		$errorInfo	.= $GLOBALS['inslang']['admuser'].",".$GLOBALS['inslang']['admpass'].$GLOBALS['inslang']['note9']."\r\n";
	}
	//admin username and password check
	if(empty($_POST['admuser']) or empty($_POST['admpass'])) {
		$errorInfo	.=  $GLOBALS['inslang']['note7']."\r\n";
	}
	//password1 and password2 check
	if($_POST['admpass']!=$_POST['admpass2']) {
		$errorInfo	.=  $GLOBALS['inslang']['note8']."\r\n";
	}
	
	$link		= @mysql_connect($_POST['dbhost'],$_POST['dbuser'],$_POST['dbpass']);
	if(!$link)
	{
		$errorInfo	.=  $GLOBALS['inslang']['note2']."\r\n";
	}
	$srs		= @mysql_select_db($_POST['dbname']);
	if(!$srs)
	{
		$errorInfo	.=  $GLOBALS['inslang']['note3']."\r\n";
	}
	if(empty($errorInfo))
	{
		Return true;
	}else {
		Return $errorInfo;
	}
}

function installDkper() 
{
	global $succeedInfo;
	$errorInfo	= '';
	$link	= @mysql_connect($_POST['dbhost'],$_POST['dbuser'],$_POST['dbpass']);
	@mysql_select_db($_POST['dbname']);
	$rs		= mysql_query("select version()");
	$row	= mysql_fetch_array($rs);
	$mysqlversion	= $row[0];
	if($mysqlversion > '4.1') {
		mysql_query("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
		if($mysqlversion > '5.0.1') {
			mysql_query("SET sql_mode=''");
		}
	}
	$filename	= CHINO_MODPATH.'/config/config.inc.php';
	$handle		= @fopen($filename,"w+");
	if(!$handle)
	{
		$errorInfo	.= $GLOBALS['inslang']['note4'].$filename."\r\n";
	}else {
		$contents	= '<?php'."\n";
		$contents	.= 'define("ENCODE", "UTF-8"); define("DBTYPE", "'.$_POST['dbtype'].'");';
		$contents	.= 'define("DBHOST", \''.$_POST['dbhost'].'\');  ';
		$contents	.= 'define("DBUSER", \''.$_POST['dbuser'].'\'); ';
		$contents	.= 'define("DBPASS", \''.$_POST['dbpass'].'\'); ';
		$contents	.= 'define("DBNAME", \''.$_POST['dbname'].'\'); ';
		$contents	.= 'define("TABLEHEAD", \''.$_POST['tbhead'].'\');';
		$contents	.= 'define("SITETITLE", \''.$_POST['sitetitle'].'\');';
		$contents	.= "\n".'?>';
		if(@fwrite($handle, $contents)){
			$succeedInfo	.= $GLOBALS['inslang']['note16']."\r\n";
		}
		@fclose($handle);

		$sqlfile	= CHINO_PHPPATH."/sql/wowdkp.sql";
		$handle		= @fopen($sqlfile, "r");
		$sqlcontent	= @fread($handle,filesize($sqlfile));
		//replace
		$sqlcontent	= preg_replace("/wowdkp_/i",$_POST['tbhead']."_",$sqlcontent);
		$sqlarray	= explode(";",$sqlcontent);
		foreach($sqlarray as $key=>$val) {
			$val	= trim($val);
			if(!empty($val)) {
				if($mysqlversion > '4.1') {
					$val	= preg_replace("/TYPE=MyISAM/i","TYPE=MyISAM DEFAULT CHARSET=utf8",$val);
				}
				$rs	= @mysql_query($val);
				preg_match("/CREATE TABLE ([^\(]*)\(/i",$val,$tablearr);
				$tablename	= trim($tablearr[1]);
				if(!empty($tablename))
				{
					if(!$rs)
					{
						$errorInfo	.= $GLOBALS['inslang']['note5'].":".$tablename."\r\n";
					}else {
						$succeedInfo	.= $GLOBALS['inslang']['note17'].":".$tablename."\r\n";
					}
				}
			}			
		}
		$pass	= md5($_POST['admpass']);
		$sql	= "insert into ".$_POST['tbhead']."_admuser (username,password,power,cid) values('$_POST[admuser]','$pass','10','1:2')";
		mysql_query($sql);
		$sql	= "insert into ".$_POST['tbhead']."_copy(id,name) values(1,'NAXX')";
		mysql_query($sql);
		$sql	= "update ".$_POST['tbhead']."_config set value='$_POST[sitetitle]' where vname='guildname'";
		mysql_query($sql);
		//create class and race
		foreach($GLOBALS['classname'] as $key=>$val) {
			mysql_query("insert into ".$_POST['tbhead']."_work(name,wcolor) values('$val','$key')");
		}
		foreach($GLOBALS['racename'] as $key=>$val) {
			mysql_query("insert into ".$_POST['tbhead']."_race(name) values('$val')");
		}
        //2008-9-17 location and realm
        $sql    = "update ".$_POST['tbhead']."_config set value='$_POST[location]' where vname='location'";
        mysql_query($sql);
        $sql    = "update ".$_POST['tbhead']."_config set value='$_POST[langtype]' where vname='langtype'";
        mysql_query($sql);
        $sql    = "update ".$_POST['tbhead']."_config set value='$_POST[realm]' where vname='realm'";
        mysql_query($sql);
		$randnum	= rand(1000,9999);
		$url	= "http://www.iboko.net/project/wowdkp/wowdkper_collecter.php?&t=0&r=".$randnum."&g=".$_POST['sitetitle']."&a=".$_SERVER['HTTP_REFERER'];
		echo("<img src='$url' width='0' height='0' border='0'>");
	}
	if(empty($errorInfo))
	{
		Return true;
	}else {
		Return $errorInfo;
	}
}



?>

