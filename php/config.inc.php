<?php
/**
 *»·¾³ÉèÖÃ
 *
 *@package chino
 */

$thisDir	= dirname(__FILE__);
$upDir		= dirname($thisDir);

define("DEBUG", false);
define("CHINO_WWWROOT", $upDir);
define("CHINO_WWWURL", "./?");
define("CHINO_PHPPATH", $upDir . "/php");
define("CHINO_PATH", CHINO_PHPPATH . "/chino");
define("CHINO_LIBPATH", CHINO_PHPPATH . "/lib");
define("CHINO_MODPATH", CHINO_PHPPATH . "/module");
define("CHINO_CACHEBASE", $upDir . "/cache");
define("CHINO_LOGPATH", CHINO_PHPPATH . "/logs");
define("CHINO_DEFAULTENCODE", "UTF-8");
define("CHINO_DEFAULTMODULE", "index");
define("SMARTYPATH", CHINO_LIBPATH."/Smarty.class.php");
define("ADODBPATH",CHINO_LIBPATH."/adodb.inc.php");



$moduleTable = array();


?>