<?php
/**
+-----------------------------------------------------------------------+
* @��ɽ���::��ɽ����::��ɽ�̳�
* @copyright	1.0
* @author       ����<zhangtao2@kingsoft.com> 
* @version      $Id: .php,v 1.0 zhangtao$
* @time			2004-10-12
+-----------------------------------------------------------------------+
*/
include '../Smarty.class.php';
$smarty=new Smarty();
$smarty->assign("contacts", array(array("phone" => "1", "fax" => "2", "cell" => "3"),
      array("phone" => "555-4444", "fax" => "555-3333", "cell" => "760-1234")));

$smarty->display('login.htm');

?>