<?php
/**
 *������
 *
 *@author sunguang
 *@package chino
 */

/**
 *������
 *
 *@author sunguang 
 *@package chino
 *@access public
 */
class Application {

    var $_module=NULL;                           //ʹ��ģ��
    var $_act=NULL;                                 //Ŀǰ����
    var $_page=NULL;                              //ģ��ʵ��

    /**
     *���캯��
     *
     *@param string $module ģ������/���
     *@param string $act ��������/���
     *@param mixed $moduleTable ģ�����ƶ�Ӧ�����, ��ʹ�ñ��ʱΪNULL
     *@return  void
     */
    function Application($module='', $act='', $moduleTable=NULL) {
        Chino::loadLib('Httprequest');
        $HttpGetObj	= Httprequest::GetInstance(true);
        $this->setModule($module, $moduleTable);
        $realAct = $this->_page->getAlias();
        if(!is_null($realAct)) {
            $act = $_REQUEST[$realAct];
        }
        $this->setAct($act);
    }

    /**
     *����ģ�黷������
     */
    function setModuleEnv() {
        define("TPLPATH", CHINO_MODPATH . "/" . $this->_module ."/template");
        define("CACHEPATH", CHINO_CACHEBASE . "/" . $this->_module ."/cache");
        define("CMPPATH", CHINO_CACHEBASE . "/" . $this->_module ."/compile");
        define("CFGPATH", CHINO_MODPATH . "/" . $this->_module ."/config");
        define("LANGPATH", CHINO_MODPATH . "/" . $this->_module ."/language");
        define("CURRENTPATH", CHINO_MODPATH . "/" . $this->_module);
        if(is_file(CFGPATH. "/config.inc.php")) {
            include_once(CFGPATH. "/config.inc.php");
        }
    }

    /**
     *���ݵõ���ģ������/�������ʹ��ģ��
     *
     *@param string $module ģ������/���
     *@param mixed $moduleTable ģ�����ƶ�Ӧ�����, ��ʹ�ñ��ʱΪNULL
     *@return void 
     */
    function setModule($module, $moduleTable=NULL) {
        //��������ģ������ΪNULL, ��ʹ��Ĭ��ģ��.
        if(is_null($module)) {
            $module = CHINO_DEFAULTMODULE;
        }
        //�������ģ���Ӧ��, ��ʹ��ģ���Ӧ��
        if(is_array($moduleTable) && isset($moduleTable[$module])) {
            $module = $moduleTable[$module];
        }
        $moduleFile = CHINO_MODPATH . "/" . strtolower($module) . "/mod_" . ucfirst($module) . ".php";
		//var_dump($moduleFile);
        if(is_file($moduleFile)) {
            $this->_module = $module;
        } else {
            $module = $this->_module = CHINO_DEFAULTMODULE;
            $moduleFile = CHINO_MODPATH . "/" . strtolower($module) . "/mod_" . ucfirst(CHINO_DEFAULTMODULE) . ".php";
        }
        $this->_module = strtolower($this->_module);
        $this->setModuleEnv();
        include_once($moduleFile);
        $this->_page = new Cpage;
    }

    /**
     *���ݴ���Ķ�������/������ö���
     *
     *@param string $act ��������/���
     *@return void
     */
    function setAct($act) {
        $this->_page->setAct($act);
        //ȡ�ö�������б�
        $actTable = $this->_page->getActTable();
        if(count($actTable) > 0) {
            if(!is_null($actTable[$act])) {
                $act = $actTable[$act];
            }
        }
        $act = ucfirst($act);
        $this->_act = $act;
    }

    /**
     *����ʹ��ģ����
     *
     *@return string ģ������
     */
     function getModule() {
         return $this->_module;
     }

    /**
     *����ʹ�ö�������
     *
     *@return string ��������
     */
     function getAct() {
         return $this->_act;
     }

    /**
     *ִ��ҳ��
     *
     *return void
     */
    function run() {
        //ȷ��ҳ�����
        if(method_exists($this->_page, "initEnv")) {
            $this->_page->initEnv();
        }
        //ִ��Ԥ������
        if(method_exists($this->_page, "onLoad")) {
            $this->_page->onLoad();
        }
        //ִ�ж���
        $this->_page->doAct($this->_act);
        //ִ�н�������
        if(method_exists($this->_page, "onUnload")) {
            $this->_page->onUnload();
        }
        exit;
    }

}
?>