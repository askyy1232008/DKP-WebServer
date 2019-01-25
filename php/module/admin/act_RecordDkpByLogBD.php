<?php
/**
+-----------------------------------------------------------------------+
* @autor ���� <tonera@gmail.com>;
* @since 2006-8-17
* @version $Id: act_RecordDkpByLogBD.php,v 1.3.2 tonera$
* @description	admin
* @last update 2006-8-17
+-----------------------------------------------------------------------+
*/
include_once CHINO_LIBPATH.'/db_mysql.inc.php';

@set_time_limit(0);
class RecordDkpByLogBD extends Base {
	var $adm		= '';
	var $gconfig	= array();
	var $timezone	= 0;
	var $syslog		= '';
		//boss kill
	function RecordDkpByLogBD() {
		include_once(CHINO_LIBPATH . "/class_Syslog.php");
		$this->syslog	= new Syslog($GLOBALS['adodb'],$_SESSION['wowdkp']['userid'],$_SESSION['wowdkp']['user'],TABLEHEAD);
		//�Ƿ�ѡ aispt
		setcookie("wow_cfg_ispt",$_POST['aispt'],time()+2592000);
		include_once(CURRENTPATH . "/class_admin.php");
        $this->adm = new admin;
		$gconfigarr	= $this->adm->getInfoByTable('config');
		if(is_array($gconfigarr)) {
			foreach($gconfigarr as $key=>$val) {
				$this->gconfig[trim($val['vname'])]	= trim($val['value']);
			}
		}
		$this->timezone	= (float)$this->gconfig['timezone'] * 3600 - date("Z");
		//������Ʒ���
		$pitemdkp	= 0;
		$ptotal		= 0;
		$cid		= (int)$_POST['cid'];
		$etid		= (int)$_POST['etid'];
		$distime	= date("Y-m-d H:i:s",time() + $this->timezone);
		$members	= array();
		$lastraidtime	= date("Y-m-d",strtotime($_POST['eventTime']));
		$this->adm->_adminCheck(5);
		
		$_POST['aitemname']	= is_array($_POST['aitemname'])?$_POST['aitemname']:array();
		$i			= 0;
		$pitemdkp	= 0;
		$pubitem	= array();
		foreach($_POST['aitemname'] as $key=>$val) {
			//dkp
			$toboss	= $_POST['toboss'][$key];
			if($toboss == 'all') {
				$pitemdkp	+= (float)$_POST['aitemcosts'][$key];
				//��¼����������Ʒ
				$pubitem[$i]['itemname']	= $val;
				$pubitem[$i]['itemcount']	= $_POST['aitemcount'][$key];
				$pubitem[$i]['itemcolor']	= $_POST['aitemcolor'][$key];
				$pubitem[$i]['itemtooltip']	= $_POST['aitemtooltip'][$key];
				$pubitem[$i]['itemcosts']	= $_POST['aitemcosts'][$key];
				$pubitem[$i]['itemplayer']	= $_POST['aitemplayer'][$key];
				$pubitem[$i]['itemid']		= $_POST['aitemid'][$key];
				$i++;
			}else {
				$_POST['itemname'][$toboss][]		= $val;
				$_POST['itemcount'][$toboss][]		= $_POST['aitemcount'][$key];
				$_POST['itemcolor'][$toboss][]		= $_POST['aitemcolor'][$key];
				$_POST['itemtooltip'][$toboss][]	= $_POST['aitemtooltip'][$key];
				$_POST['itemboss'][$toboss][]		= $toboss;
				$_POST['itemcosts'][$toboss][]		= $_POST['aitemcosts'][$key];
				$_POST['itemplayer'][$toboss][]		= $_POST['aitemplayer'][$key];
			}
		}
		//����.������Ʒ�ܷ�
		if($_POST['aispt'] == '1') {
			$membertotal	= (int)$_POST['membertotal'];
			if($membertotal!=0) {
				$ptotal	= $pitemdkp/$membertotal;
			}else {
				$ptotal	= 0;
			}
		}

		//���������¼�->
		$countPubitem	= count($pubitem);
		if($countPubitem>0){
			$GLOBALS['adodb']->execute("insert into ".TABLEHEAD."_event (name,notes,etid,raidtime,cid) values('$_POST[allaverevent]','$_POST[notes]','$etid','$lastraidtime','$cid')");
			$eid		= $GLOBALS['adodb']->Insert_ID();
			$this->syslog->writeLog($GLOBALS['oplog']['a_event'],$_POST['allaverevent'],"ID:".$eid);
			//���¼�������+1
			$GLOBALS['adodb']->execute("update ".TABLEHEAD."_copy set raidtotal=raidtotal+1 where id = '$cid'");
		}
		//��Ա
		foreach($_POST['members'] as $key=>$val) {
			$val	= trim($val);
			$rs		= $GLOBALS['adodb']->SelectLimit("select id from ".TABLEHEAD."_user where name = '$val'",1);
			$uid	= $rs->fields['id'];
			if(!empty($uid)) {
				$members[$uid]	= $val;
				//���ʱ��
				$GLOBALS['adodb']->execute("update ".TABLEHEAD."_user set lastraidtime = '$lastraidtime' where id='$uid'");
				if($countPubitem>0){
					//�¼���Ա->��Ա����
					//�û�����
					$GLOBALS['adodb']->execute("insert into ".TABLEHEAD."_itemdis(eid,uid,value,distime,stat,cid) values('$eid','$uid','$ptotal','$distime','1','$cid')");
				}
				//�����Աû�д˸����ļ�¼��
				$rs	= $GLOBALS['adodb']->SelectLimit("select uid from ".TABLEHEAD."_dkpvalues where uid='$uid' and copyid='$cid'",1);
				$id	= $rs->fields['uid'];
				if(empty($id)) {
					$GLOBALS['adodb']->execute("insert into ".TABLEHEAD."_dkpvalues (dkpvalue,uid,copyid,raidnum) values('$ptotal','$uid','$cid',1)");
				}else {
					//���»�Ա�˸�������
					$GLOBALS['adodb']->SelectLimit("update ".TABLEHEAD."_dkpvalues set dkpvalue=dkpvalue+$ptotal where uid = '$uid' and copyid = '$cid'",1);
				}
			}
		}
		//��¼��Ʒ->�¼���Ʒ->��Ʒ����
		if(is_array($pubitem)){
			foreach($pubitem as $key=>$val){
				$itemname	= $val['itemname'];
				$num		= $val['itemcount'];
				$ipid		= $val['itemtooltip'];
				$lootdkp	= abs($val['itemcosts']);
				$icolor		= $val['itemcolor'];
				$sql		= "insert into ".TABLEHEAD."_item (eid,name,num,stat,intotime,ipid,icolor) values('$eid','$itemname','$num','1','$distime','$ipid','$icolor')";
				$GLOBALS['adodb']->execute($sql);
				$iid		= $GLOBALS['adodb']->Insert_ID();
				//�¼���Ʒ
				$sql	= "insert into ".TABLEHEAD."_eventitem (eid,iid) values('$eid','$iid')";
				$GLOBALS['adodb']->execute($sql);
				//����
				$itemuid	= array_search($val['itemplayer'],$members);
				//���ָ���߲�����.���¶�ȡ2006-9-3
				if(empty($itemuid)) {
					$itemuidrs	= $GLOBALS['adodb']->SelectLimit("select id from ".TABLEHEAD."_user where name = '$val[itemplayer]'",1);
					$itemuid	= $itemuidrs->fields['id'];
				}
				//��Ȼû�д��û��������Ʒ��û����
				if(empty($itemuid)) {
					$GLOBALS['adodb']->execute("update ".TABLEHEAD."_item set stat=0 where id='$iid'");
					continue;
				}
				$sql	= "insert into ".TABLEHEAD."_itemdis (iid,eid,uid,value,distime,stat,cid) values('$iid','$eid','$itemuid','$lootdkp','$distime','-1','$cid')";
				$GLOBALS['adodb']->execute($sql);
				//���»�Ա�˸�������
				$GLOBALS['adodb']->SelectLimit("update ".TABLEHEAD."_dkpvalues set dkpvalue=dkpvalue-$lootdkp where uid='$itemuid' and copyid='$cid'",1);
			}
		}

		//per boss event
		foreach($_POST['boss'] as $key=>$val) {
			$disdkp		= (float)$_POST['dis'][$key];
			$notes		= $_POST['enotes'][$key];
			$itemdkp	= 0;
			$peruserdkp	= 0;
			$eventuser	= array();
			$GLOBALS['adodb']->execute("insert into ".TABLEHEAD."_event (name,notes,etid,raidtime,cid) values('$val','$notes','$etid','$lastraidtime','$cid')");
			$eid		= $GLOBALS['adodb']->Insert_ID();
			$this->syslog->writeLog($GLOBALS['oplog']['a_event'],$val,"ID:".$eid);
			//���
			$eventusers	= preg_split('/\r\n/',$_POST['raidmember'][$key]);
			foreach($eventusers as $key4=>$val4) {
				if(!empty($val4)) {
					$eventuser[$key4]	= trim($val4);
				}
			}
			$i			= count($eventuser);
			$i			= $i>0?$i:0;

			//��Ʒ��
			$_POST['itemname'][$key]	= is_array($_POST['itemname'][$key])?$_POST['itemname'][$key]:array();
			foreach($_POST['itemname'][$key] as $key2=>$val2) {
				$lootdkp	= abs((float)$_POST['itemcosts'][$key][$key2]);
				//$property	= $_POST['itemtooltip'][$key][$key2];
				$ipid		= $_POST['itemtooltip'][$key][$key2];
				$itemcode	= $_POST['itemcolor'][$key][$key2];
				$zone		= $_POST['itemcosts'][$key][$key2];
				$boss		= $_POST['itemboss'][$key][$key2];
				$num		= $_POST['itemcount'][$key][$key2];
				$itemdkp	+= $lootdkp;
				//��Ʒ���ڼ�¼
				$itemname		= $val2;
				$sql		= "insert into ".TABLEHEAD."_item (eid,name,num,stat,intotime,ipid,icolor) values('$eid','$itemname','$num','1','$distime','$ipid','$itemcode')";
				$GLOBALS['adodb']->execute($sql);
				$iid		= $GLOBALS['adodb']->Insert_ID();
				//�¼���Ʒ
				$sql	= "insert into ".TABLEHEAD."_eventitem (eid,iid) values('$eid','$iid')";
				$GLOBALS['adodb']->execute($sql);
				//����
				$itemuid	= array_search($_POST['itemplayer'][$key][$key2],$members);
				
				//���ָ���߲�����.���¶�ȡ2006-9-3
				if(empty($itemuid)) {
					$tmpitem	= $_POST['itemplayer'][$key][$key2];
					$itemuidrs	= $GLOBALS['adodb']->SelectLimit("select id from ".TABLEHEAD."_user where name = '$tmpitem'",1);
					$itemuid	= $itemuidrs->fields['id'];
				}
				//��Ȼû�д��û��������Ʒ��û����
				if(empty($itemuid)) {
					$GLOBALS['adodb']->execute("update ".TABLEHEAD."_item set stat=0 where id='$iid'");
					continue;
				}

				$sql	= "insert into ".TABLEHEAD."_itemdis (iid,eid,uid,value,distime,stat,cid) values('$iid','$eid','$itemuid','$lootdkp','$distime','-1','$cid')";
				$GLOBALS['adodb']->execute($sql);
				//���»�Ա�˸�������
				$GLOBALS['adodb']->SelectLimit("update ".TABLEHEAD."_dkpvalues set dkpvalue=dkpvalue-$lootdkp where uid='$itemuid' and copyid='$cid'",1);
				//exit("exit!!!2006-8-16");
			}
			if($_POST['ispt'][$key] == '1') {
				if($i!=0) {
					$peruserdkp	= round($itemdkp/$i,2);
				}else {
					$peruserdkp	= 0;
				}
			}
			$peruserdkp	= (float)($peruserdkp+$disdkp);

			foreach($eventuser as $key3=>$val3) {
				if(!empty($val3)) {
					$userkey				= array_search($val3,$members);

					//���ָ���߲�����.���¶�ȡ2006-9-3
					if(empty($userkey)) {
						$itemuidrs	= $GLOBALS['adodb']->SelectLimit("select id from ".TABLEHEAD."_user where name = '$val3'",1);
						$userkey	= $itemuidrs->fields['id'];
					}
					//��Ȼû�д��û�����ֱ�Ӽ�����һ���û�
					if(empty($userkey)) {
						continue;
					}

					if(!empty($userkey)) {
						//�û�����
						$GLOBALS['adodb']->execute("insert into ".TABLEHEAD."_itemdis(eid,uid,value,distime,stat,cid) values('$eid','$userkey','$peruserdkp','$distime','1','$cid')");
						//��ÿ����Ա���һ��raid����+����
						$GLOBALS['adodb']->SelectLimit("update ".TABLEHEAD."_dkpvalues set dkpvalue=dkpvalue+$peruserdkp,raidnum=raidnum+1 where uid='$userkey' and copyid='$cid'",1);
					}
				}
			}
			//������+1
			$GLOBALS['adodb']->execute("update ".TABLEHEAD."_copy set raidtotal=raidtotal+1 where id = '$cid'");
		}
		$this->throwInfo($GLOBALS['lang']['note26'],"index.php?module=admin&act=ShowRaidLogForm");
	}//end function

	function throwInfo($info, $goto='', $target='self') {
		//var_dump($GLOBALS['smarty']);
        $GLOBALS['smarty']->assign('info', $info);
        $GLOBALS['smarty']->assign('goto', $goto);
        $GLOBALS['smarty']->assign('target', $target);
        $GLOBALS['smarty']->display('info.htm');
        exit;
	}
}