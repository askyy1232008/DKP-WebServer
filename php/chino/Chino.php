<?php
/**
 *控制器入口文件
 *
 *@author sunguang 
 *@package chino
 */
if(PHP_VERSION < '4.1.0') {
	$_GET = &$HTTP_GET_VARS;
	$_POST = &$HTTP_POST_VARS;
	$_COOKIE = &$HTTP_COOKIE_VARS;
	$_SERVER = &$HTTP_SERVER_VARS;
	$_ENV = &$HTTP_ENV_VARS;
	$_FILES = &$HTTP_POST_FILES;
}

include_once(dirname(__FILE__) . "/class_Application.php");

/**
 *基本工具集合
 *
 *@author sunguang 
 *@package chino
 *@access public
 */
class Chino {

    /**
     * 声明application实体
     *
     *@param string $module 模块名称/编号
     *@param string $act 动作名称/编号
     *@param mixed $moduleTable 模块名称对应表变量, 不使用编号时为NULL
     *@return object Application实体
     *@access  public
     */
    function getApplication($module, $act, $moduleTable=NULL) {
        return new Application($module, $act, $moduleTable);
    }

    /**
     * 类文件导入
     *
     *@param string $class 类名
     *@return bool 导入结果, 成功返回true, 失败抛出异常返回false
     *@access  public
     */
    function loadLib($class) {
        $file = CHINO_LIBPATH . "/class_" . ucfirst($class) . ".php";
        if(file_exists($file)) {
            include_once($file);
            return true;
        } else {
            Chino::raiseError("功能类 {" . $class . "} 不存在");
            return false;
        }
    }

    /**
     * 生成smarty/adodb实体
     *
     *@param string $class 类名称
     *@return mixed 成功返回生成的实体, 失败返回false
     *@access  public
     */
    function & getObject($class) {
        $class = strtolower($class);
        if($class == "smarty") {
            include_once(SMARTYPATH);
            $object = new Smarty;
            $object->debugging      = false;
            $object->template_dir   = TPLPATH; // template dir
            //$object->cache_dir      = CACHEPATH; // cache dir
			$object->cache_dir      = CMPPATH; // cache dir
            $object->compile_dir    = CMPPATH; // template dir
            $object->config_dir     = CFGPATH; // config dir
        } elseif($class == "adodb") {
            include_once(ADODBPATH);
            $object = ADONewConnection(DBTYPE);
        } else {
            return false;
        }
        return $object;
    }

    /**
    * 报告错误
    *
    *@param  string $errInfo 错误信息
    *@param  string $template 使用模板
    *@return  void
     *@access  public
     */
    function raiseError($errInfo, $template='debug.htm') {
        if(!isset($GLOBALS['smarty'])) {
            $GLOBALS['smarty'] = Chino::getObject('smarty');
        }
        header("Content-type: text/html; charset=UTF-8");
        $array = debug_backtrace();
        $array = $array[1];
        $array["module"] = $_REQUEST['module'];
        $array["message"] = $errInfo;
        $array["time"] = time();
        error_log("<error>\r\n\t<module>" . $array["module"] . "</module>\r\n\t<message><![CDATA[" . $array["message"] . "]]></message>\r\n\t<file>" . $array["file"] . "</file>\r\n\t<line>" . $array['line'] . "</line>\r\n\t<method>" . $array['class'] . $array['type'] . $array['function'] . "</method>\r\n\t<time>" . date("Y-m-d H:i:s", $array['time']) . "</time>\r\n</error>\r\n", 3, CHINO_MODPATH . "/error." . date("Ymd", $array['time']) . ".log");
        $GLOBALS['smarty']->assign('array', $array);
        if($template == "debug.htm") {
            $template = CHINO_PHPPATH . '/template/debug.htm';
        }
        $GLOBALS['smarty']->display($template);
        exit;
    }

    /**
     *显示提示信息
     *
     *@param object $smarty smarty模板实体
     *@param string $info 提示信息
     *@param string $goto 跳转地址
     *@param string $target 跳转目标
     *@return void
     *@access  public
     */
    function raiseInfo(& $smarty, $info, $goto='', $target='self') {
		echo "<script language=javascript>alert('".$info."');</script>";
		echo("<script language=javascript>window.location.href=\"".$goto."\";</script>");
		exit;
//        $smarty->assign('info', $info);
//        $smarty->assign('goto', $goto);
//        $smarty->assign('target', $target);
//        $smarty->display(CHINO_PHPPATH . '/template/info.htm');
//        echo Chino::getTimer();
//        exit;
    }

    /**
     *得到程序执行时间
     *
     *@return  string  上一次调用此方法到目前的时间
     *@access  public
     */
    function getTimer() {
        static $initTime=0;
        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $timeDiff = $mtime - $initTime;
        $initTime = $mtime;
        return sprintf("\n<!--%1.6fs-->", $timeDiff);
    }
}

/**
 *sql使用变量防注入
 */
function sqlDef($var) {
    return $var;
    if(is_array($var)) {
        return array_map("sqlDef", $var);
    } else {
        return strtr($var, array("%"=>"\%", "_"=>"\_"));
    }      
}

/**
 *页面基本类
 *
 *@author sunguang <sunguang@kingsoft.com>
 *@package chino
 *@access  public
 */

class Base {

    /**
     *动作编号列表
     */
    var $actTable = array();
    
    /**
     *当前使用动作
     */
    var $act;

    /**
     *act参数别名
     */
    var $alias = "kact";

    /**
     *语言种类
     */
    var $language = "zh";


    /**
     *构造函数
     */
    function Base() {
    }

    /**
     *初始化环境(待改进)
     *
     *@return void
     *@access public
     */
    function initEnv() {
        Chino::getTimer();
        //声明页面编码

        if(!defined(ENCODE)) {
            header("Content-type: text/html; charset=" . CHINO_DEFAULTENCODE);
			//2006-8-19
            //define("ENCODE", CHINO_DEFAULTENCODE);
        } else {
            header("Content-type: text/html; charset=" . ENCODE);
        }

        if(isset($_GET['language'])) {
            $language = $_GET['language'];
            setcookie('language', $_GET['language'], time() + 3600 * 24);
        } elseif(isset($_COOKIE['language'])) {
            $language = $_COOKIE['language'];
        } else {
            $language = $this->language;
        }
        $this->language = $language;
        $langFile = LANGPATH. "/" . strtolower($language . "-" . ENCODE . ".php");
        
        if(is_file($langFile)) {
            include_once($langFile);
        }
    }

    /**
     *取得动作别名
     *
     *@return  string 动作别名
     *@access  public
     */
    function getAlias() {
    	return $this->alias;
    }

    /**
     *设置当前动作
     *
     *@param string $act 动作名称
     *@return  void
     *@access  public
     */
    function setAct($act) {
        $this->act = $act;
    }

    /**
     *页面载入时执行的动作
     *
     *@return  void
     *@access  public
     */
    function onLoad() {
    }

    /**
     *页面载入完成时执行的动作
     *
     *@return  void
     *@access  public
     */
    function onUnload() {
        echo Chino::getTimer();
    }

    /**
     *默认动作
     *
     *@return  void 抛出异常
     *@access  public
     */
    function onDefault() {
        Chino::raiseError("动作 {" . strtolower(substr($this->act, 2)) . "} 未定义");
    }

    /**
     *获得动作编号列表
     *
     *@return  array $actTable 动作列表
     *@access  public
     */
    function getActTable() {
        return $this->actTable;
    }

    /**
     *执行指定动作
     *
     *@return  void
     *@access  public
     */
    function doAct($act) {
        $this->_act = $act;
        if(is_file(CURRENTPATH . "/act_" . ucfirst($act) . ".php")) {
            include_once(CURRENTPATH . "/act_" . ucfirst($act) . ".php");
            new $act($this);
        } elseif(method_exists($this, "on" . ucfirst($act))) {
            $this->{"on" . ucfirst($act)}();
        } else {
            $this->onDefault();
        }
    }

}

?>