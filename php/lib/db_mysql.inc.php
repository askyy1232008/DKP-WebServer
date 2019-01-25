<?php
/**
* DB process
* 2006-9-2
*/
$GLOBALS['adodb']->connect(DBHOST, DBUSER, DBPASS, DBNAME);
$mysqlvrs		= $GLOBALS['adodb']->execute("select version()");
$mysqlversion	= $mysqlvrs->fields[0];

if($mysqlversion > '4.1') {
	$GLOBALS['adodb']->execute("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
	if($mysqlversion > '5.0.1') {
		$GLOBALS['adodb']->execute("SET sql_mode=''");
	}
}



?>