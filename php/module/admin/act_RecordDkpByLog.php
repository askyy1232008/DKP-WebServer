<?php
/**
+-----------------------------------------------------------------------+
* @autor ���� <tonera@gmail.com>;
* @since 2006-8-17
* @version $Id: act_RecordDkpByLog.php,v 1.3.6 tonera$
* @description	admin
* @last update 2007-9-20
+-----------------------------------------------------------------------+
*/
include_once CHINO_LIBPATH.'/db_mysql.inc.php';

class RecordDkpByLog extends Base {
	var $adm		= '';
	var $gconfig	= array();
	var $timezone	= 0;
	var $syslog		= '';
	//��¼
	function RecordDkpByLog() {
		include_once(CHINO_LIBPATH . "/class_Syslog.php");
		$this->syslog	= new Syslog($GLOBALS['adodb'],$_SESSION['wowdkp']['userid'],$_SESSION['wowdkp']['user'],TABLEHEAD);
		include_once(CURRENTPATH . "/class_admin.php");
        $this->adm = new admin;
		$this->adm->_adminCheck(5);
		$gconfigarr	= $this->adm->getInfoByTable('config');
		if(is_array($gconfigarr)) {
			foreach($gconfigarr as $key=>$val) {
				$this->gconfig[trim($val['vname'])]	= trim($val['value']);
			}
		}
		$this->timezone	= (float)$this->gconfig['timezone'] * 3600 - date("Z");
		//�Ƿ�Ĭ�Ϲ�ѡ istimeline=on ispt=1
		setcookie("wow_cfg_istimeline",$_POST['istimeline'],time()+2592000);
		setcookie("wow_cfg_ispt",$_POST['ispt'],time()+2592000);
		
		//ȫ�̲μӺ�;���뿪�Ļ�Ա�ȸ��»�Ա��Ϣ.�ټ�¼dkp.ʰȡ��ֻ��¼dkp
		//var_dump($_POST['aj_isesc']);
		$distime	= date("Y-m-d H:i:s",time() + $this->timezone);
		//�Ȳ���raid�¼�.�����¼��ͳ�Ա�Ķ�Ӧ��ϵ
		$eventUser	= array();	//�¼���Ա
		$_POST['aj_uid']	= is_array($_POST['aj_uid'])?$_POST['aj_uid']:array();
		$_POST['lv_uid']	= is_array($_POST['lv_uid'])?$_POST['lv_uid']:array();
		$_POST['aj_isesc']	= is_array($_POST['aj_isesc'])?$_POST['aj_isesc']:array();
		$_POST['lv_isesc']	= is_array($_POST['lv_isesc'])?$_POST['lv_isesc']:array();
		$_POST['lo_isesc']	= is_array($_POST['lo_isesc'])?$_POST['lo_isesc']:array();
		$_POST['lo_dkp']	= is_array($_POST['lo_dkp'])?$_POST['lo_dkp']:array();
		$joinUid	= array_merge($_POST['aj_uid'], $_POST['lv_uid']);
		$escUid		= array_merge($_POST['aj_isesc'], $_POST['lv_isesc']);
		$eventUser	= array_unique(array_diff($joinUid,$escUid));
		$cid		= (int)$_POST['cid'];
		//ȡdkpֵ$_POST['lo_dkp']
		$itemtotal	= 0;
		$sitemtotal	= array();		//�����Ʒѡ���������ĸ���
		foreach($_POST['lo_dkp'] as $key=>$val) {
			if(!in_array($_POST['lo_ITEMNAME'][$key],$_POST['lo_isesc']) and $_POST['lo_cid'][$key]==$cid) {
				$itemtotal	+= $val;
			}
			if(!in_array($_POST['lo_ITEMNAME'][$key],$_POST['lo_isesc']) and $_POST['lo_cid'][$key]!=$cid){
				$sitemtotal[$_POST['lo_cid'][$key]]	+= $val;
			}
		}

		//����
		$totalmember	= count($_POST['aj_uid'])+count($_POST['lv_uid'])-count($_POST['aj_isesc'])-count($_POST['lv_isesc']);
		if($totalmember<=0) {
			$this->throwInfo($GLOBALS['lang']['note25'],"index.php?module=admin&act=ShowRaidLogForm");
		}
		//ÿ�˷���
		if(empty($_POST['ispt'])) {
			$persondkp	= 0;
		}else {
			$persondkp	= round($itemtotal/$totalmember,2);
		}

		$sql		= "insert into ".TABLEHEAD."_event(name,etid,raidtime,notes,cid) values('$_POST[name]','$_POST[etid]','$_POST[raidtime]','$_POST[notes]','$cid')";
		$GLOBALS['adodb']->execute($sql);
		$eid		= $GLOBALS['adodb']->Insert_ID();
		//�����Ʒѡ��ั��
		if(is_array($sitemtotal)) {
			foreach($sitemtotal as $key=>$val) {
				$GLOBALS['adodb']->execute("insert into ".TABLEHEAD."_event(name,etid,raidtime,notes,cid) values('$_POST[name]','$_POST[etid]','$_POST[raidtime]','$_POST[notes]','$key')");
				$earrays[$key]	= $GLOBALS['adodb']->Insert_ID();
				//2006-9-5����raid��ͬ������
				if($_POST['etid'] == '1') {
					$sql	= "update ".TABLEHEAD."_copy set raidtotal = raidtotal+1 where id ='$key'";
					$GLOBALS['adodb']->execute($sql);
				}
			}
		}
		$earrays[$cid]	= $eid;

		//���´˸�������raid��
		if($_POST['etid'] == '1') {
			$sql	= "update ".TABLEHEAD."_copy set raidtotal = raidtotal+1 where id ='$cid'";
			$GLOBALS['adodb']->execute($sql);
		}
		//����������Ӧ��Ա
		foreach($sitemtotal as $key=>$val){
			foreach($eventUser as $key1=>$val1) {
				$tmpeid	= $earrays[$key];
			}
		}

		//ȫ�̲μ�raid�û���Ϣ����
		$_POST['aj_uid']	= is_array($_POST['aj_uid'])?$_POST['aj_uid']:array();
		foreach($_POST['aj_uid'] as $key=>$val) {
			$dkp		= $_POST['aj_dkp'][$key]+$persondkp;
			$sql	= "update ".TABLEHEAD."_user set lastraidtime='$_POST[raidtime]' where id='$val'";
			$GLOBALS['adodb']->execute($sql);
			if(!@in_array($val,$_POST['aj_isesc'])) {	//����ÿ���û�dkpֵ
				$sql	= "insert into ".TABLEHEAD."_itemdis(eid,uid,value,distime,stat,cid) values('$eid','$val','$dkp','$distime','1','$cid')";
				$GLOBALS['adodb']->execute($sql);
				foreach($sitemtotal as $skey=>$sval) {
					$dkp	= round($sval/$totalmember,2);
					if($dkp>0 and !empty($skey)) {
						$tmpeid	= $earrays[$skey];
						$GLOBALS['adodb']->execute("insert into ".TABLEHEAD."_itemdis(eid,uid,value,distime,stat,cid) values('$tmpeid','$val','$dkp','$distime','1','$skey')");
					}
				}
				$this->adm->_accountdkp($val);
				//��ÿ����Ա���һ��raid����
				if($_POST['etid'] == '1') {
					$sql	= "update ".TABLEHEAD."_dkpvalues set raidnum=raidnum+1 where uid='$val' and copyid='$cid'";
					$GLOBALS['adodb']->execute($sql);
					foreach($sitemtotal as $skey=>$sval) {
						$sql	= "update ".TABLEHEAD."_dkpvalues set raidnum=raidnum+1 where uid='$val' and copyid='$skey'";
						$GLOBALS['adodb']->execute($sql);
					}
				}
			}
		}
		//��̲μ�raid�û���Ϣ����
		$_POST['lv_uid']	= is_array($_POST['lv_uid'])?$_POST['lv_uid']:array();
		foreach($_POST['lv_uid'] as $key=>$val) {
			$dkp		= $_POST['lv_dkp'][$key]+$persondkp;
			$sql	= "update ".TABLEHEAD."_user set lastraidtime='$_POST[raidtime]' where id='$val'";
			$GLOBALS['adodb']->execute($sql);
			if(!@in_array($val,$_POST['lv_isesc'])) {	//����ÿ���û�dkpֵ
				$sql	= "insert into ".TABLEHEAD."_itemdis(eid,uid,value,distime,stat,cid) values('$eid','$val','$dkp','$distime','1','$cid')";
				$GLOBALS['adodb']->execute($sql);
				foreach($sitemtotal as $skey=>$sval) {
					$dkp	= round($sval/$totalmember,2);
					if($dkp>0 and !empty($skey)) {
						$tmpeid	= $earrays[$skey];
						$GLOBALS['adodb']->execute("insert into ".TABLEHEAD."_itemdis(eid,uid,value,distime,stat,cid) values('$tmpeid','$val','$dkp','$distime','1','$skey')");
					}
				}
				$this->adm->_accountdkp($val);
				//��ÿ����Ա���һ��raid����
				if($_POST['etid'] == '1') {
					$sql	= "update ".TABLEHEAD."_dkpvalues set raidnum=raidnum+1 where uid='$val' and copyid='$cid'";
					$GLOBALS['adodb']->execute($sql);
					foreach($sitemtotal as $skey=>$sval) {
						$sql	= "update ".TABLEHEAD."_dkpvalues set raidnum=raidnum+1 where uid='$val' and copyid='$skey'";
						$GLOBALS['adodb']->execute($sql);
					}
				}
			}
		}
		//��Ʒʰȡ��Աdkp:������Ʒ,����Ʒ�������Ա
		$_POST['lo_uid']	= is_array($_POST['lo_uid'])?$_POST['lo_uid']:array();
		foreach($_POST['lo_uid'] as $key=>$val) {
			$cid		= $_POST['lo_cid'][$key];
			$eid		= $earrays[$cid];
			$itemdkp	= abs($_POST['lo_dkp'][$key]);
			$ipid		= (int)$_POST['lo_ITEMPROPERTY'][$key];
			$itemname	= $_POST['lo_ITEMNAME'][$key];
			$icolor		= $_POST['lo_COLOR'][$key];
			if(!@in_array($itemname,$_POST['lo_isesc'])) {
				$sql		= "insert into ".TABLEHEAD."_item (eid,name,stat,intotime,ipid,icolor) values('$eid','$itemname','1','$distime','$ipid','$icolor')";
			}else {
				$sql		= "insert into ".TABLEHEAD."_item (eid,name,stat,intotime,ipid,icolor) values('$eid','$itemname','0','$distime','$ipid','$icolor')";
			}
			$GLOBALS['adodb']->execute($sql);
			$iid		= $GLOBALS['adodb']->Insert_ID();
			//����û�û��ȡ������
			if(!@in_array($itemname,$_POST['lo_isesc'])) {
				//�������Աû�иı���Ʒ���л�Ա����ֱ�Ӳ���������ȡ��Աid�ٲ���
				if($_POST['lo_player'][$key] == $_POST['lo_player_old'][$key]) {
					$sql	= "insert into ".TABLEHEAD."_itemdis (iid,eid,uid,value,distime,stat,cid) values('$iid','$eid','$val','$itemdkp','$distime','-1','$cid')";
					$GLOBALS['adodb']->execute($sql);
				}else {
					$uname	= $_POST['lo_player'][$key];
					$sql	= "select id from ".TABLEHEAD."_user where name='$uname'";
					$urs	= $GLOBALS['adodb']->execute($sql);
					$val	= $urs->fields['id'];
					if(empty($val)) {
						continue;
					}
					$sql	= "insert into ".TABLEHEAD."_itemdis (iid,eid,uid,value,distime,stat,cid) values('$iid','$eid','$val','$itemdkp','$distime','-1','$cid')";
					$GLOBALS['adodb']->execute($sql);
				}
				$this->adm->_accountdkp($val);
			}
		}
		$this->syslog->writeLog($GLOBALS['oplog']['a_event'],$_POST['name'],"ID:".$eid);
		$this->throwInfo($GLOBALS['lang']['note26'],"index.php?module=admin&act=ShowRaidLogForm");
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