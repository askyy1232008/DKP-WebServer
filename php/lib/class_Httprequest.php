<?php
/**
 * ���� Httprequest �ͱ�
 * �ο� pfc
 * @copyright Copyright (c) 2004 dualface.com
 *@package chino
 * @author sunguang <sunguang@kingsoft.com>
 * @
 */

/**
 * Httprequest ��װ�� HTTP ����
 *
 * ��װ POST��GET��REQUEST �� COOKIE ���ݵ��Զ�ת�������
 *
 *@package chino
 * @version 1.0
 */
class Httprequest {
	/**
	 * ���캯��
	 *
	 * �� get_magic_quotes_gpc() ��� magic_quotes_gpc ѡ�����ò��� POST �� GET ������ͬ�Ĵ���
	 * Ĭ������ǲ�����û������ magic_quotes_gpc ��������ת�壨�� magic_quotes_gpc = On ���������
	 * ���ϣ���������ݶ��Զ�ת�壬��ôӦ������ $automatic_addslashes ����Ϊ true��
	 *
	 * һ����Ҫֱ�ӵ��� Httprequest �Ĺ��캯������Ϊ�п���ÿ�ε��ù��캯������� POST �� GET ��������ת�������
	 * ��ᵼ�����Է��ֵĴ���
	 *
	 * ��ȷ��������ʹ�� Httprequest::GetInstance() ��̬������� Httprequest �ͱ��Ψһʵ����
	 *
	 * @param boolean $automatic_addslashes ָʾ�Ƿ��� magic_quotes_gpc ѡ������Ϊ off ʱΪ�������ݽ���ת��
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
	 * ��ȡ Httprequest �ͱ��Ψһʵ��
	 *
	 * @param boolean $automatic_addslashes ָʾ�Ƿ��� magic_quotes_gpc ѡ������Ϊ off ʱΪ�������ݽ���ת��
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
	 * �ݹ鴦�������е�ÿһ��Ԫ��
	 *
	 * @param array $arr Ҫ���������
	 * @param boolean ָʾ�ǵ��� addslashes ���� stripslashes ��������Ԫ��
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
