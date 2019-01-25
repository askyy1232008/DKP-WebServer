<?php
/**
+-----------------------------------------------------------------------+
* @autor ���� <tonera@gmail.com>;
* @since 2005-12-1
* @version $Id: class_admin.php,v 1.3.7 tonera$
* @description	�������
* @last update 2008-8-24
+-----------------------------------------------------------------------+
*/


class admin {
	var $copyArr	= array();
	function admin() {
		$this->copyArr	= explode(':',$_SESSION['wowdkp']['cid']);
	}
	//ȡĳ��������Ϣ(����ҳ)
	function getInfoByTable($tablename,$id='',$orderby='') {
		
		$sql	= "select * from ".TABLEHEAD."_".$tablename;
		$sql	= $id?$sql." where id='$id'":$sql;
		$sql	= $orderby?$sql." order by ".$orderby." desc":$sql;
		//var_dump($sql);
		$rs = $GLOBALS['adodb']->execute($sql);
        
        return $rs->getAll();
	}
	//������ȡ��Ա��Ϣ
	function searchlikeuser($key) {
		
		$sql	= "select a.*,b.wcolor from ".TABLEHEAD."_user as a left join ".TABLEHEAD."_work as b on a.workid=b.id where a.name like '%".$key."%' order by a.workid desc";
		//var_dump($sql);
		$rs = $GLOBALS['adodb']->execute($sql);
		
        
        return $rs->getAll();
	}
	//ȡһ���Ա��Ϣ
	function getGroupUser($gid) {
		
		$sql	= "select a.*,b.wcolor from ".TABLEHEAD."_user as a left join ".TABLEHEAD."_work as b on a.workid=b.id  where a.groupid ='$gid' order by a.workid desc";
		$rs = $GLOBALS['adodb']->execute($sql);
        
        return $rs->getAll();
	}

	//ȡ��δ���������Ʒ�б� 
	function getItemNodis() {
		
		$copyArr	= explode(':',$_SESSION['wowdkp']['cid']);
		$cstr		= implode(',',$copyArr);
		$cstr		= $cstr?$cstr:0;
		$sql	= "select a.id as id,concat(a.name,'(',b.name,')') as name from ".TABLEHEAD."_item as a left join ".TABLEHEAD."_event as b on a.eid=b.id where a.stat='0' and b.cid in (".$cstr.") order by a.id desc";
		//var_dump($sql);
		$rs = $GLOBALS['adodb']->execute($sql);
        
        return $rs->getAll();
	}

	//ȡĳ���ҳ��Ϣ
	function getPerPageInfo($sql,$urlparam = '') {
		
		Chino::loadLib("browser");
		//ȡ����
		if(preg_match("/group/i",$sql)) {
			$rs				= $GLOBALS['adodb']->execute($sql);
			$totalRecords	= $rs->RecordCount();
		}else {
			$pattern		= "select(.*)from";
			$replacement	= "select count(*) from";
			$allsql			= eregi_replace($pattern,$replacement,$sql);
			$rs				= $GLOBALS['adodb']->execute($allsql);
			$totalRecords	= $rs?$rs->fields[0]:0;
		}
		$mypagenav		= new browser();
		$langArr		= array(
			$GLOBALS['lang']['page_first'],
			$GLOBALS['lang']['page_backward'],
			$GLOBALS['lang']['page_forward'],
			$GLOBALS['lang']['page_last'],
			$GLOBALS['lang']['current'],
			$GLOBALS['lang']['total'],
			$GLOBALS['lang']['goto'],
			$GLOBALS['lang']['pagenum'],
			$GLOBALS['lang']['pagename'],
			$GLOBALS['lang']['pageop']);
		$mypagenav->initialize($totalRecords,PAGE_RPP_DEF,$_GET['offset']);
		$mypagenav->setLang($langArr);
		$var_parameter	= $urlparam;
		$pageText		= $mypagenav->ActionPage($var_parameter,PAGE_PPL_DEF); 

		$offset			= $_GET['offset']?$_GET['offset']:0;
		//$sql			.= " limit $offset, ".PAGE_RPP_DEF;
		$rs				= $GLOBALS['adodb']->SelectLimit($sql,PAGE_RPP_DEF,$offset);
		if($mypagenav->c_totalpage <= 1) {
			$pageText	= '';
		}
        if($rs) {
        	return array($pageText,$rs->getAll());
        }
        else {
        	return array($pageText,array());
        }
	}
	//�û�sql
	function excuteSql($sql) {
		$rs = $GLOBALS['adodb']->execute($sql);
		if($rs->EOF) {
			Return;
		}else {
			return $rs->getAll();
		}        
	}
	//����¼�
	function addEvent($tablename,$postarray) {
		
		$inputarray	= array("name","notes","etid","raidtime","cid");
		$addarray	= array();
		foreach($postarray as $key=>$val) {
			if(in_array($key,$inputarray)) {
				$addarray[$key]	= $val;
			}
		}
		$keyarray	= array();
		$valarray	= array();
		foreach($addarray as $key=>$val) {
			$keyarray[]	= $key;
			$valarray[]	= "'".$val."'";
		}
		$fieldstr	= implode(",",$keyarray);
		$valuestr	= implode(",",$valarray);

		//��ʼһ������
		$sql	= "insert into ".TABLEHEAD."_".$tablename." (".$fieldstr.") values(".$valuestr.")";
		$rs		= $GLOBALS['adodb']->execute($sql);
		$eid	= $GLOBALS['adodb']->Insert_ID();

		//���¸�������raid����
		if($postarray['etid']==1) {
			$sql	= "update ".TABLEHEAD."_copy set raidtotal = raidtotal+1 where id ='$postarray[cid]'";
			$GLOBALS['adodb']->execute($sql);
		}	
		
		//�����¼����Ա��Ӧ��ϵ
		$userarray	= $_POST['uidselect'];
		$userarray	= is_array($userarray)?$userarray:array();
		if(!empty($userarray)) {
			foreach($userarray as $key=>$val) {
				//��ÿ����Ա���һ��raid����
				if($postarray['etid']==1) {
					$sql	= "update ".TABLEHEAD."_dkpvalues set raidnum=raidnum+1 where uid='$val' and copyid='$postarray[cid]'";
					$GLOBALS['adodb']->execute($sql);
				}
				//��Ա���raidʱ��
				$lastraidtime	= $_POST['raidtime'];
				$sql	= "update ".TABLEHEAD."_user set lastraidtime='$lastraidtime' where id='$val'";
				$GLOBALS['adodb']->execute($sql);
			}
		}
		//�����¼�����Ʒ��Ӧ��ϵ
		$itemarray	= $this->_getActiveArray($postarray,"attachfile");
		if(!empty($itemarray) and is_array($itemarray)) {
			foreach($itemarray as $key=>$val) {
				//������Ʒ��
				if(!empty($val)) {
					$intotime	= date("Y-m-d H:i:s");
					//�ҳ�����Ʒ�Ƿ�����ϸ��Ϣ
					$sql	= "select ipid from ".TABLEHEAD."_item where name='$val' and ipid!=0";
					$rs		= $GLOBALS['adodb']->SelectLimit($sql,1);
					$ipid	= $rs->fields['ipid'];
					$sql	= "insert into ".TABLEHEAD."_item(ipid,eid,name,intotime) values('$ipid','$eid','$val','$intotime')";
					$irs	= $GLOBALS['adodb']->execute($sql);
					$iid	= $GLOBALS['adodb']->Insert_ID();
					$sql	= "insert into ".TABLEHEAD."_eventitem(eid,iid) values('$eid','$iid')";
					$iers	= $GLOBALS['adodb']->execute($sql);
				}
			}
		}
		//��ʼ����dkp addDkpValue,
		$disdkp	= (float)$_POST['disdkp'];
		$this->addDkpValue($eid,$disdkp,$userarray);
		Return true;
	}
	//��ӵ�һ��¼��ĳ��
	function addRecord($tablename,$postarray) {
		
		foreach($postarray as $key=>$val) {
			$keyarray[]	= $key;
			$valarray[]	= "'".$val."'";
		}
		$sql	= "insert into ".TABLEHEAD."_".$tablename."(".implode(",",$keyarray).") values(".implode(",",$valarray).")";
		//exit($sql);
		$rs	= $GLOBALS['adodb']->execute($sql);
		$id	= $GLOBALS['adodb']->Insert_ID();
		
		if(!$rs) {
			Return false;
		}else {
			Return $id;
		}
	}
	//������Ʒ��ĳ��Ա
	function disitem($postarray) {
		$postarray['distime']	= date("Y-m-d H:i:s");
		$postarray['stat']		= "-1";	//1:�õ�dkp��-1��ʧȥdkp
		foreach($postarray as $key=>$val) {
			$keyarray[]	= $key;
			$valarray[]	= "'".$val."'";
		}
		$sql	= "insert into ".TABLEHEAD."_itemdis (".implode(",",$keyarray).") values(".implode(",",$valarray).")";
		//��ʼ����
		$GLOBALS['adodb']->execute("SET AUTOCOMMIT=0");
		$rs	= $GLOBALS['adodb']->execute($sql);
		//����ƷΪ�ѷ���
		$sql	= "update ".TABLEHEAD."_item set stat='1' where id='$postarray[iid]'";
		$irs	= $GLOBALS['adodb']->execute($sql);
		//�����Աdkp
		$this->_accountdkp($postarray['uid']);
		//�������COMMIT;
		$endrs	= $GLOBALS['adodb']->execute("COMMIT");
		if(!$endrs) {
			$GLOBALS['adodb']->execute("ROLLBACK");
			
			Return false;
		}else {
			
			Return true;
		}
	}
	//��һ���û�����dkp
	function addDkpValue($eid,$value,$uidarray) {
		$atime	= date("Y-m-d H:i:s");
		
		//ȡ�ô��¼������ĸ���
		$sql	= "select cid from ".TABLEHEAD."_event where id='$eid'";
		$rs		= $GLOBALS['adodb']->execute($sql);
		$cid	= $rs->fields['cid'];
		//��ʼ����
		foreach($uidarray as $key=>$val) {
			$sql	= "insert into ".TABLEHEAD."_itemdis(eid,uid,value,distime,stat,cid) values('$eid','$val','$value','$atime','1','$cid')";
			$rs		= $GLOBALS['adodb']->execute($sql);
		}
		//�����Աdkp
		foreach($uidarray as $key=>$val) {
			$endrs	= $this->_accountdkp($val);
		}
		if(!$endrs) {
			Return false;
		}else {
			Return true;
		}
	}

	//����(��������name�ֶ�)
	function updateRecord($tablename,$updatearray,$idarray) {
		
		foreach($idarray as $key=>$val) {
			$sql	= "update ".TABLEHEAD."_".$tablename." set name='".$updatearray[$val]."' where id='$val'";
			$rs = $GLOBALS['adodb']->execute($sql);
		}		
		
		if(!$rs) {
			Return false;
		}else {
			Return true;
		}
	}
	//����(��������ְҵɫ��)
	function updateWcolor($tablename,$updatearray,$idarray) {
		
		foreach($idarray as $key=>$val) {
			$sql	= "update ".TABLEHEAD."_".$tablename." set wcolor='".$updatearray[$val]."' where id='$val'";
			$rs = $GLOBALS['adodb']->execute($sql);
		}		
		
		if(!$rs) {
			Return false;
		}else {
			Return true;
		}
	}
	//����ĳ��ĳ����¼
	//$infoarray�ļ���ʾ�ֶΣ�ֵ��ʾҪ���µ�ֵ
	function updateInfo($tablename,$id,$infoarray,$lostuid='') {
		
		$carray		= array();
		foreach($infoarray as $key=>$val) {
			$carray[]	= $key."='".$val."'";
		}
		$cstr	= implode(',',$carray);
		$sql	= "update ".TABLEHEAD."_".$tablename." set ".$cstr." where id='$id'";
		//var_dump($sql);
		//exit;
		$rs = $GLOBALS['adodb']->execute($sql);
		//���������Ʒdkpֵ�������¼����û���dkpֵ
		if($tablename=='itemdis') {
			$this->_accountdkp($infoarray['uid']);
			//����Ǹ���Ʒ����,�����¼���ʧȥ��Ʒ�ĳ�Ա��dkp
			if(!empty($lostuid)) {
				$this->_accountdkp($lostuid);
			}
		}
		
		if(!$rs) {
			Return false;
		}else {
			Return true;
		}
	}
	//ɾ��
	function deleteRecord($tablename,$idarray='') {
		
		//����ɾ���¼������û�
		if($tablename=='user') {		
			//ɾ���û�->ɾ���û��������¼�����Ʒ�����ϵ.ɾ��dkp��¼
			foreach($idarray as $key=>$val) {
				$GLOBALS['adodb']->execute("delete from ".TABLEHEAD."_itemdis where uid='$val'");
				$GLOBALS['adodb']->execute("delete from ".TABLEHEAD."_dkpvalues where uid='$val'");
			}		
			$uid	= array();
		}elseif($tablename=='item') {	//ɾ����Ʒ->ɾ����Ʒ�¼���ϵ����Ʒ�����ϵ
			$userarr	= array();
			foreach($idarray as $key=>$val) {
				//�õ�����Ʒ������Ա
				$sql		= "select uid from ".TABLEHEAD."_itemdis where iid='$val'";
				$rs			= $GLOBALS['adodb']->SelectLimit($sql,1);
				$userarr[]	= $rs->fields['uid'];
				$GLOBALS['adodb']->execute("delete from ".TABLEHEAD."_eventitem where iid='$val'");
				$GLOBALS['adodb']->execute("delete from ".TABLEHEAD."_itemdis where iid='$val'");
			}
			$uid	= $userarr;
		}elseif($tablename=='event') {		//ɾ���¼�������Ա->ɾ���¼�dkp���ڹ�ϵ,ɾ���¼�������Ʒ
			$userarr	= array();
			foreach($idarray as $key=>$val) {
				//�õ����¼���Ϣ
				$sql	= "select * from ".TABLEHEAD."_event where id='$val'";
				$ers	= $GLOBALS['adodb']->SelectLimit($sql,1);
				$cid	= $ers->fields['cid'];
				$etid	= $ers->fields['etid'];
				//�õ����¼�������Ա
				$sql	= "select uid from ".TABLEHEAD."_itemdis where eid='$val'";
				$rs		= $GLOBALS['adodb']->execute($sql);
				while(!$rs->EOF) {
					$userarr[]	= $rs->fields['uid'];
					$rs->MoveNext();
				}
				$deleteUserNum	= array_unique($userarr);
				$GLOBALS['adodb']->execute("delete from ".TABLEHEAD."_itemdis where eid='$val'");
				$GLOBALS['adodb']->execute("delete from ".TABLEHEAD."_eventitem where eid='$val'");
				$GLOBALS['adodb']->execute("delete from ".TABLEHEAD."_item where eid='$val'");
				//���¸���raid����
				if($etid == '1'){
					$GLOBALS['adodb']->execute("update ".TABLEHEAD."_copy set raidtotal=raidtotal-1 where id='$cid'");
					//ɾ�����¼�������Ա�ĳ�ϯ 2006-09-29
					foreach ($deleteUserNum as $k2=>$v2){
						$this->excuteSql("update ".TABLEHEAD."_dkpvalues set raidnum=raidnum-1 where uid='$v2' and copyid='$cid'");
					}
				}
			}
			$uid	= $deleteUserNum;
		}elseif($tablename=='itemdis') {		//ɾ����Ʒ�����ϵ->��Ʒ״̬��Ϊδ����
			$userarr	= array();
			foreach($idarray as $key=>$val) {
				//�õ���Ʒ�����ϵ�еĳ�Ա
				$sql	= "select * from ".TABLEHEAD."_itemdis where id='$val'";
				$rs		= $GLOBALS['adodb']->SelectLimit($sql,1);
				$userarr[]	= $rs->fields['uid'];
				$itemid		= $rs->fields['iid'];
				//��Ʒ״̬����
				$GLOBALS['adodb']->execute("update ".TABLEHEAD."_item set stat='0' where id='$itemid'");
			}
			$uid	= $userarr;
		}else {
			$uid	= array();
		}
		
		$sql	= "delete from ".TABLEHEAD."_".$tablename;
		if($idarray!=='') {
			$csql	= '';
			foreach($idarray as $key=>$val) {
				$csql	= $sql." where id='$val'";
				$rs = $GLOBALS['adodb']->execute($csql);
			}
		}else {
			$rs = $GLOBALS['adodb']->execute($sql);
		}
		//echo($sql);
		//���û�����dkp���¼���
		foreach($uid as $key=>$val) {
			$this->_accountdkp($val);
		}
		
		if(!$rs) {
			Return false;
		}else {
			Return true;
		}
	}

	//���붯̬��������е�����(��arr��Ѱ�Һ�����keywordΪ������Ԫ�أ��γ�һ�µ�����)
	function _getActiveArray($arr,$keyword) {
		$newarray	= array();
		foreach($arr as $key=>$val) {
			if(strpos($key,$keyword)===false) {
				;
			}else {
				$newarray[]	= $val;
			}
		}
		Return $newarray;
	}
	//���û��Ӽ�dkp
	function _accountdkp($uid) {
		$sql	= "select cid,sum(value*stat) as v from ".TABLEHEAD."_itemdis where uid='$uid' group by cid ";
		//var_dump($sql);
		$rs		= $GLOBALS['adodb']->execute($sql);
		$okcid	= array();
		while(!$rs->EOF) {
			$value	= $rs->fields['v'];
			$cid	= $rs->fields['cid'];
			$okcid[]= $cid;
			//���û���û������
			$sql	= "select count(*) from ".TABLEHEAD."_dkpvalues where uid='$uid' and copyid='$cid'";
			
			$isuser	= $GLOBALS['adodb']->execute($sql);
			if($isuser->fields[0]=='0') {
				$sql	= "insert into ".TABLEHEAD."_dkpvalues(dkpvalue,uid,copyid) values('$value','$uid','$cid')";
			}else {
				$sql	= "update ".TABLEHEAD."_dkpvalues set dkpvalue = '$value' where uid='$uid' and copyid='$cid' ";
			}
			$GLOBALS['adodb']->execute($sql);
			$rs->MoveNext();
		}
		//�ҵ�û�д˸�����¼�ĸ���id
		$sql	= "select id from ".TABLEHEAD."_copy ";
		$rs		= $GLOBALS['adodb']->execute($sql);
		$cidArr	= array();
		while(!$rs->EOF) {
			$cidArr[]	= $rs->fields['id'];
			$rs->MoveNext();
		}
		$diffCidArr	= @array_diff($cidArr,$okcid);
		$diffsql	= @implode(',',$diffCidArr);
		if(empty($diffsql)) {
			$diffsql	= 0;
		}
		$sql	= "update ".TABLEHEAD."_dkpvalues set dkpvalue = '0' where uid='$uid' and copyid in($diffsql) ";
		$rs		= $GLOBALS['adodb']->execute($sql);
		//var_dump($sql);
		if(!$rs) {
			Return false;
		}else {
			Return true;
		}
	}
	function writeInfoToFile($filename,$somecontent) {
		if (@is_writable($filename)) {
			if (!$handle = fopen($filename, 'w+')) {
				 Return false;
			}
			if (@fwrite($handle, $somecontent) === FALSE) {
				Return false;
			}
			@fclose($handle);
			Return true;
		} else {
			Return false;
		}
	}
	//get the copy's key and value by session
	function _getCopy($isSelect=false) {
		$copyarrays	= $this->getInfoByTable("copy");
		$copyarray	= array();
		if($isSelect) {
			$copyarray[]	= $GLOBALS['lang']['pleaselect'];
		}		
		if(is_array($copyarrays)) {
			foreach($copyarrays as $key=>$val) {
				if(in_array($val['id'],$this->copyArr)) {
					$copyarray[$val['id']]	= $val['name'];
				}				
			}
		}
		//var_dump($copyarrays);
		Return $copyarray;
	}
    //get user group
	function _getUserGroup($isSelect=false) {
		$grouparrays	= $this->getInfoByTable("group");
		$grouparray	    = array();
		if($isSelect) {
			$grouparray[]	= $GLOBALS['lang']['pleaselect'];
		}		
		if(is_array($grouparrays)) {
			foreach($grouparrays as $key=>$val) {
				if(in_array($val['id'],$this->copyArr)) {
					$grouparray[$val['id']]	= $val['name'];
				}				
			}
		}
		Return $grouparray;
	}
	//ȡ��Ȩ���ڵ��¼�
	function _getEventBysess($isSelect=false,$li=300) {
		$eventarray = array();
		$copystr	= implode(',',$this->copyArr);
		$copystr	= $copystr?$copystr:0;
		$sql		= "select * from ".TABLEHEAD."_event where cid in (".$copystr.") order by raidtime desc ";
		$rs			= $GLOBALS['adodb']->SelectLimit($sql,$li);
		$eventinfo	= $rs->getAll();
		$eventinfo	= is_array($eventinfo)?$eventinfo:array();
		if($isSelect) {
			$eventarray[]	= $GLOBALS['lang']['pleaselect'];
		}	
		foreach($eventinfo as $key=>$val) {
			$eventarray[$val['id']]	= "[".$val['raidtime']."]".$val['name'];
		}
		
		Return $eventarray;
	}
	//power check
	function _adminCheck($power) {
		if($_SESSION['wowdkp']['power']>=$power) {
			Return true;
		}else {
			exit("Error: Request denied!");
		}
	}
	
	//params check
	function & _htmlspecialchars(& $arr, $is=true) {
        if(is_array($arr)) {
            foreach($arr as $key=>$val) {
                $arr[$key] = $this->_htmlspecialchars($val);
            }
        } else {
			if($is){
				$arr = htmlspecialchars($arr);
			}else {
				$arr = html_entity_decode($arr);
			}
        }
		return $arr;
	}
}



?>