<?php
/**
 *控制器
 *
 *@author sunguang
 *@package chino
 */

/**
 *控制器
 *
 *@author sunguang 
 *@package chino
 *@access public
 */
class Application {

    var $_module=NULL;                           //使用模块
    var $_act=NULL;                                 //目前动作
    var $_page=NULL;                              //模块实体

    /**
     *构造函数
     *
     *@param string $module 模块名称/编号
     *@param string $act 动作名称/编号
     *@param mixed $moduleTable 模块名称对应表变量, 不使用编号时为NULL
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
     *设置模块环境常量
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
     *根据得到的模块名称/编号设置使用模块
     *
     *@param string $module 模块名称/编号
     *@param mixed $moduleTable 模块名称对应表变量, 不使用编号时为NULL
     *@return void 
     */
    function setModule($module, $moduleTable=NULL) {
        //如果传入的模块名称为NULL, 则使用默认模块.
        if(is_null($module)) {
            $module = CHINO_DEFAULTMODULE;
        }
        //如果传入模块对应表, 则使用模块对应表
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
     *根据传入的动作名称/编号设置动作
     *
     *@param string $act 动作名称/编号
     *@return void
     */
    function setAct($act) {
        $this->_page->setAct($act);
        //取得动作编号列表
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
     *返回使用模块名
     *
     *@return string 模块名称
     */
     function getModule() {
         return $this->_module;
     }

    /**
     *返回使用动作名称
     *
     *@return string 动作名称
     */
     function getAct() {
         return $this->_act;
     }

    /**
     *执行页面
     *
     *return void
     */
    function run() {
        //确定页面编码
        if(method_exists($this->_page, "initEnv")) {
            $this->_page->initEnv();
        }
        //执行预处理动作
        if(method_exists($this->_page, "onLoad")) {
            $this->_page->onLoad();
        }
        //执行动作
        $this->_page->doAct($this->_act);
        //执行结束动作
        if(method_exists($this->_page, "onUnload")) {
            $this->_page->onUnload();
        }
        exit;
    }

}
?>