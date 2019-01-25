<?php
/**
 * 定义 Httprequest 型别
 * 参考 pfc
 * @copyright Copyright (c) 2004 dualface.com
 *@package chino
 * @author sunguang <sunguang@kingsoft.com>
 * @
 */

/**
 * Httprequest 封装了 HTTP 请求
 *
 * 封装 POST、GET、REQUEST 和 COOKIE 数据的自动转义操作，
 *
 *@package chino
 * @version 1.0
 */
class Httprequest {
	/**
	 * 构造函数
	 *
	 * 用 get_magic_quotes_gpc() 检查 magic_quotes_gpc 选项设置并对 POST 和 GET 等做不同的处理。
	 * 默认情况是不管有没有设置 magic_quotes_gpc 都将数据转义（既 magic_quotes_gpc = On 的情况）。
	 * 如果希望所有数据都自动转义，那么应该设置 $automatic_addslashes 参数为 true。
	 *
	 * 一定不要直接调用 Httprequest 的构造函数。因为有可能每次调用构造函数都会对 POST 和 GET 等数据做转义操作。
	 * 这会导致难以发现的错误。
	 *
	 * 正确的做法是使用 Httprequest::GetInstance() 静态方法获得 Httprequest 型别的唯一实例。
	 *
	 * @param boolean $automatic_addslashes 指示是否在 magic_quotes_gpc 选项设置为 off 时为所有数据进行转换
	 *
	 * @return Httprequest
	 *
	 * @access private
	 */
    function Httprequest($is_added = true) {
        if(($is_added && !get_magic_quotes_gpc()) || (!$is_added && get_magic_quotes_gpc())) {
            $this->WalkArray($_POST, 	$is_added);
            $this->WalkArray($_GET, 	$is_added);
            $this->WalkArray($_REQUEST, $is_added);
            $this->WalkArray($_COOKIE, 	$is_added);
        }
    }

	
	/**
	 * 获取 Httprequest 型别的唯一实例
	 *
	 * @param boolean $automatic_addslashes 指示是否在 magic_quotes_gpc 选项设置为 off 时为所有数据进行转换
	 *
	 * @return Httprequest
	 *
	 * @access public
	 */
	function & GetInstance($is_added = true) {
		static $instance;
		if (is_null($instance)) {
			$instance = new Httprequest($is_added);
		}
		return $instance;
	}
	
	/**
	 * 递归处理数组中的每一个元素
	 *
	 * @param array $arr 要处理的数组
	 * @param boolean 指示是调用 addslashes 还是 stripslashes 处理数组元素
	 *
	 * @return array
	 *
	 * @access private
	 */
	function & WalkArray(& $arr, $addslashes = true) {
        if(is_array($arr)) {
            foreach($arr as $key=>$val) {
                $arr[$key] = $this->WalkArray($val, $addslashes);
            }
        } else {
            if ($addslashes) {
                $arr = addslashes($arr); 
            } else {
                $arr = stripslashes($arr);
            }
        }
		return $arr;
	}
}
?>
