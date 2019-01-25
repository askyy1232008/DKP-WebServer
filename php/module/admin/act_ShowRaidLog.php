<?php
/**
+-----------------------------------------------------------------------+
* @autor 张涛 <tonera@gmail.com>;
* @since 2006-8-17
* @version $Id: act_ShowRaidLog.php,v 1.3.7 tonera$
* @description	admin
* @last update 2008-9-17
+-----------------------------------------------------------------------+
*/
include_once CHINO_LIBPATH.'/db_mysql.inc.php';
$GLOBALS['adodb']->debug	= false;
class ShowRaidLog extends Base {
	var $adm		= '';
	var $gconfig	= array();
	var $timezone	= 0;
		//ShowRaidLog 
	function ShowRaidLog() {
		//var_dump($GLOBALS['itemquality']);
		$tpl	= "ShowRaidLog.htm";
		include_once(CURRENTPATH . "/class_admin.php");
        $this->adm = new admin;
		$this->adm->_htmlspecialchars($_POST['raidlog'],false);
		
		
		setcookie("wow_cfg_ctmode",trim($_POST['mode']),time()+2592000);
		$replaceArr			= array("encoding=\"gb2312\"","encoding=gb2312","encoding='gb2312'");
		$_POST['raidlog']	= str_replace($replaceArr,"encoding=\"UTF-8\"",stripslashes($_POST['raidlog']));

		$_POST['raidlog']	= str_replace('&',"",$_POST['raidlog']);

		//time convert
		$_POST['raidlog']	= preg_replace_callback("/[0-9]{10}/",create_function('$matches','return date("m/d/y H:i:s",$matches[0]);'),$_POST['raidlog']);
        $userGroupID        = (int)$_POST['groupid'];

		//物品品质
		$gconfigarr	= $this->adm->getInfoByTable('config');
		if(is_array($gconfigarr)) {
			foreach($gconfigarr as $key=>$val) {
				$this->gconfig[trim($val['vname'])]	= trim($val['value']);
			}
		}
		$itemcfg	= $this->gconfig['itemquality'];
		$this->timezone	= (float)$this->gconfig['timezone'] * 3600 - date("Z");

		$data = $this->GetXMLTree ($_POST['raidlog']);

		//raid起始时间
		if(!empty($data['RaidInfo']['start'])) {
			$minJoinTime	= strtotime($data['RaidInfo']['start']);
		}
		if(!empty($data['RaidInfo']['end'])) {
			$maxLeaveTime	= strtotime($data['RaidInfo']['end']);
		}
		$lastraidtime	= date("Y-m-d",$maxLeaveTime);
		$zone		= $data['RaidInfo']['zone'];
		if(is_array($data['RaidInfo']['note'])) {
			$notes		= $data['RaidInfo']['note'][0];
		}else {
			$notes		= $data['RaidInfo']['note'];
		}

				//取所有种族名称
				$racearray	= $workarray	= $ugrouparray = array();
				$race	= $this->adm->getInfoByTable("race");
				foreach($race as $key=>$val) {
					$racearray[$val['id']]	= $val['name'];
				}
				//取所有职业名称
				$work	= $this->adm->getInfoByTable("work");
				foreach($work as $key=>$val) {
					$workarray[$val['id']]	= $val['name'];
				}
				//取所有会员组
				$ugroup	= $this->adm->getInfoByTable("group");
				$ugroup	= is_array($ugroup)?$ugroup:array();
				foreach($ugroup as $key=>$val) {
					$ugrouparray[$val['id']]	= $val['name'];
				}

		//2007-11-23 update :添加merRT的算好分的字符串
		if($_POST['mode']=='3')
		{
			//var_dump($data);
			//exit;
			$cid		= $_POST['cid'];
			$distime	= $data['RaidInfo']['end'];
			$postarray	= array("name"=>$data['RaidInfo']['name'],
								"notes"=>$data['RaidInfo']['notes'],
								"etid"=>$_POST['etid'],
								"raidtime"=>$lastraidtime,
								"cid"=>$cid);
			$eid	= $this->adm->addRecord('event',$postarray);
			//更新副本的总raid次数
			if($postarray['etid'] == '1') {
				$sql	= "update ".TABLEHEAD."_copy set raidtotal = raidtotal+1 where id ='$cid'";
				$GLOBALS['adodb']->execute($sql);
			}	

			foreach($data['RaidInfo']['PlayerInfos'] as $key=>$val) 
			{
				$value			= (float)$val['dkp'];
				$raceid			= array_search($val['race'],$racearray);
				$raceid			= $raceid===false?"0":$raceid;
				$classid		= array_search($val['class'],$workarray);
				$classid		= $classid===false?"0":$classid;
				$level			= $val['level'];
				$name			= $val['name'];

				$sql	= "select * from ".TABLEHEAD."_user where name='".$name."'";
				$rs		= $GLOBALS['adodb']->SelectLimit($sql,1);
				$uid	= $rs->fields['id'];
				if(!empty($rs->fields['name'])) {
					if(empty($rs->fields['raceid']) or empty($rs->fields['workid']) or empty($rs->fields['level'])){
						$sql	= "update ".TABLEHEAD."_user set raceid='$raceid',workid='$classid',level='$level',groupid = '$userGroupID', lastraidtime='$lastraidtime' where id='$uid'";
						$GLOBALS['adodb']->execute($sql);
					}
				}else {
					$sql	= "insert into ".TABLEHEAD."_user (name,raceid,workid,level,groupid,regTime,stat,lastraidtime) values('$name','$raceid','$classid','$level', '$userGroupID', '$regTime','1','$lastraidtime')";
					$GLOBALS['adodb']->execute($sql);
					$insid	= $GLOBALS['adodb']->Insert_ID();
					$uid	= $insid;
				}
				
				//物品
				$j	= 1;
				if(is_array($val['loot'])){
					foreach($val['loot'] as $key2=>$val2) 
					{
						//var_dump($val2);
						//exit;
						$item		= $val2['itemname'];
						$itemDkp	= abs($val2['value']);
						$sql	= "insert into ".TABLEHEAD."_item(eid,name,intotime,stat) values('$eid','$item','$lastraidtime','1')";
						$irs	= $GLOBALS['adodb']->execute($sql);
						$iid	= $GLOBALS['adodb']->Insert_ID();
						$sql	= "insert into ".TABLEHEAD."_eventitem(eid,iid) values('$eid','$iid')";
						$iers	= $GLOBALS['adodb']->execute($sql);
						$value	+= $itemDkp;
						$sql	= "insert into ".TABLEHEAD."_itemdis(iid,eid,uid,value,distime,stat,cid) values('$iid','$eid','$uid','$itemDkp','$distime','-1','$cid')";
						$GLOBALS['adodb']->execute($sql);
					}
				}
				//给每个成员添加一次raid次数
				if($postarray['etid'] == '1') {
					$sql	= "update ".TABLEHEAD."_dkpvalues set raidnum=raidnum+1 where uid='$uid' and copyid='$cid'";
					$GLOBALS['adodb']->execute($sql);
				}
				$sql	= "insert into ".TABLEHEAD."_itemdis(eid,uid,value,distime,stat,cid) values('$eid','$uid','$value','$distime','1','$cid')";
				$GLOBALS['adodb']->execute($sql);
				$this->adm->_accountdkp($uid);
			}
			$this->throwInfo($GLOBALS['lang']['note26'], $_SERVER['HTTP_REFERER']);
			exit;
		}

		//取会员的最早加入时间
		$joinarray	= array();
		$tmparray	= array();
		$firstJoinTime	= time() + $this->timezone;
		if(is_array($data['RaidInfo']['Join'])) {
			foreach($data['RaidInfo']['Join'] as $key=>$val) {
				$vTime	= strtotime($val['time']);
				$firstJoinTime	= $firstJoinTime < $vTime?$firstJoinTime:$vTime;
				if(in_array($val['player'],$tmparray)) {
					//比较时间
					if($vTime < strtotime($joinarray[$val['player']]['time'])) {
						$joinarray[$val['player']]['time']	= $val['time'];
					}else {
						continue;
					}
				}else {
					$tmparray[]	= $val['player'];
					$joinarray[$val['player']]	= $val;
				}
			}
		}

		//取会员的最后退出时间
		$leavearray	= array();
		$tmparray	= array();
		$lastJoinTime	= 0;
		if(is_array($data['RaidInfo']['Leave'])) {
			foreach($data['RaidInfo']['Leave'] as $key=>$val) {
				$vTime	= strtotime($val['time']);
				$lastJoinTime	= $lastJoinTime > $vTime?$lastJoinTime:$vTime;
				if(in_array($val['player'],$tmparray)) {
					//比较时间
					if($vTime > strtotime($leavearray[$val['player']]) ) {
						$leavearray[$val['player']]	= $val['time'];
					}else {
						continue;
					}
				}else {
					$tmparray[]	= $val['player'];
					$leavearray[$val['player']]	= $val['time'];
				}
			}
		}

		//如果没有开始时间，以第一个参加者时间，如果没有结束时间，取最后一个参加者时间
		$minJoinTime	= isset($minJoinTime)?$minJoinTime:$firstJoinTime;
		$maxLeaveTime	= isset($maxLeaveTime)?$maxLeaveTime:$lastJoinTime;
		$totalTime		= $maxLeaveTime - $minJoinTime;
		$totalTime		= $totalTime?$totalTime:1;

		//如果有PlayerInfos,则用户更新信息从PlayerInfos取。否则从joinarray取
		//如果会员没有开始时间，则取raid开始时间，如果没有结束时间，取最后结束时间
		$validMember	= array();
		if(is_array($data['RaidInfo']['PlayerInfos'])) {
			$i			= 0;
			foreach($data['RaidInfo']['PlayerInfos'] as $key=>$val) {
				$validMember[$i]	= $val;
				//加入和离开时间
				if(empty($joinarray[$val['name']]['time'])) {
					$joinarray[$val['name']]['time']	= date("m/d/y H:i:s",$minJoinTime);
				}
				if(empty($leavearray[$val['name']])) {
					$leavearray[$val['name']]	= date("m/d/y H:i:s",$maxLeaveTime);
				}
				$validMember[$i]['jtime']	= $joinarray[$val['name']]['time'];
				$validMember[$i]['ltime']	= $leavearray[$val['name']];
				//时间线 200px
				$validMember[$i]['raidtime1']	= round((strtotime($validMember[$i]['jtime']) - $minJoinTime)/$totalTime*200);
				$validMember[$i]['raidtime2']	= round((strtotime($validMember[$i]['ltime']) - strtotime($validMember[$i]['jtime']))/$totalTime*200);
				$validMember[$i]['raidtime3']	= round(($maxLeaveTime - strtotime($validMember[$i]['ltime']))/$totalTime*200);
                $validMember[$i]['groupid']     = $userGroupID;
				//会员入库
				$i++;
			}
			$totalMember	= count($data['RaidInfo']['PlayerInfos']);
		}else {
			$i			= 0;
			foreach($joinarray as $key=>$val) {
				$validMember[$i]	= $val;
				$validMember[$i]['name']	= $val['player'];
				$validMember[$i]['jtime']	= $val['time'];
				if(empty($leavearray[$val['player']])) {
					$leavearray[$val['player']]	= date("m/d/y H:i:s",$maxLeaveTime);
				}
				$validMember[$i]['ltime']	= $leavearray[$val['player']];
				//时间线 200px
				$validMember[$i]['raidtime1']	= round((strtotime($validMember[$i]['jtime']) - $minJoinTime)/$totalTime*200);
				$validMember[$i]['raidtime2']	= round((strtotime($validMember[$i]['ltime']) - strtotime($validMember[$i]['jtime']))/$totalTime*200);
				$validMember[$i]['raidtime3']	= round(($maxLeaveTime - strtotime($validMember[$i]['ltime']))/$totalTime*200);
                $validMember[$i]['groupid']     = $userGroupID;
				$i++;
			}
			$totalMember	= count($joinarray);
		}
		//入库
		$dataarr	= array();
		$regTime		= date("Y-m-d H:i:s",time() + $this->timezone);
		//var_dump($ugrouparray);
		foreach($validMember as $key=>$val) {
			$val['rank']	= isset($val['rank'])?$val['rank']:0;
			$raceid			= array_search($val['race'],$racearray);
			$raceid			= $raceid===false?"0":$raceid;
			$classid		= array_search($val['class'],$workarray);
			$classid		= $classid===false?"0":$classid;
			$groupid		= $val['groupid'];
			$level			= isset($val['level'])?$val['level']:0;
			$name			= $val['name'];
			$sql	= "select * from ".TABLEHEAD."_user where name='".$name."'";
			$rs		= $GLOBALS['adodb']->SelectLimit($sql,1);
			$uid	= $rs->fields['id'];
			if(!empty($rs->fields['name'])) {
				if(empty($rs->fields['raceid']) or empty($rs->fields['workid']) or empty($rs->fields['level'])){
					$sql	= "update ".TABLEHEAD."_user set raceid='$raceid',workid='$classid',level='$level',groupid='$groupid' where id='$uid'";
					$GLOBALS['adodb']->execute($sql);
				}
			}else {
				
				$sql	= "insert into ".TABLEHEAD."_user (name,raceid,workid,level,groupid,regTime,stat) values('$name','$raceid','$classid','$level','$groupid','$regTime','1')";
				$rs		= $GLOBALS['adodb']->execute($sql);
				$insid	= $GLOBALS['adodb']->Insert_ID();
				$uid	= $insid;
			}
			//人员名单
			$raidUser[$uid]				= $val['name'];
			$validMember[$key]['uid']	= $uid;
		}


		//拾取者uidTOOLTIP
		$totalItemdkp	= 0;
		$lootarray	= array();
		$bosslost	= array();	//boss掉落物品
		$bosscost	= array();	//boss掉落价值
		if(is_array($data['RaidInfo']['Loot'])) {
			foreach($data['RaidInfo']['Loot'] as $key=>$val) {
				$val['Color']				= substr($val['Color'],2);
				if (!$this->isValidItem($val['Color'],$itemcfg)) {
					continue;
				}
				$val['Costs']	= isset($val['Costs'])?$val['Costs']:0;
				$bosscost[$val['Boss']]	= isset($bosscost[$val['Boss']])?$bosscost[$val['Boss']]:0;
				$bosscost[$val['Boss']]	+= $val['Costs'];
				$lootarray[$i]	= $val;
				$lootarray[$i]['uid']	= array_search($val['Player'],$raidUser);
				$totalItemdkp		+= $lootarray[$i]['Costs'];

				//物品属性入库
				$itemname	= $val['ItemName']."||".$val['ItemID'];
				$property	= $val['tooltip'];
				$itemcode	= $lootarray[$i]['Color'];
				$zone		= $val['Zone'];
				$boss		= $val['Boss'];
				//判断此物品是否出现在属性表里，如果是，得到其id，如果不是，插入到物品属性表
				$sql			= "select * from ".TABLEHEAD."_itemproperty where name='$itemname'";
				$irs			= $GLOBALS['adodb']->execute($sql);
				$ipid			= $irs->fields['id'];
				if(empty($irs->fields['name'])) {
					if(!empty($property)) {
						$sql		= "insert into ".TABLEHEAD."_itemproperty (name,property,itemcode,istat,intotime,itemfrom,itemhost) values('$itemname','$property','$itemcode','1','$regTime','$zone','$boss')";
						$GLOBALS['adodb']->execute($sql);
						$ipid		= $GLOBALS['adodb']->Insert_ID();
					}else {
						$ipid		= 0;
					}
				}
				$val['tooltip']				= $ipid;
				$bosslost[$val['Boss']][]	= $val;
				$lootarray[$i]['tooltip']	= $ipid;
				$i++;
			}
		}
		//var_dump($lootarray);
		//副本选择
		$copyarray	= $this->adm->_getcopy();

		//如果是boss击杀模式 .加上if语句以提交效率
		if($_POST['mode']=='2') {
			$tpl	= "ShowRaidLogBD.htm";
			$BossKillsarray	= array();
			$bossArray		= array();
			$bossKillTime	= array();
			$nobossLoot		= array();
			$bossArray['all']		= $GLOBALS['lang']['note43'];
			$nobossdkp		= 0;
			if(is_array($data['RaidInfo']['BossKills'])) {
				$i	= 0;
				foreach($data['RaidInfo']['BossKills'] as $key=>$val) {
					$bosskey		= trim($val['name']);
					$BossKillsarray[$bosskey]	= $val;
					$bossKillTime[$bosskey]		= strtotime($val['time']);
					$BossKillsarray[$bosskey]['usernum']	= 0;
					$bossArray[$bosskey]	= $bosskey;
					//如果记录了bosskill在场人数，则按当时在线人员。否则按时间
					$BossKillsarray[$bosskey]['members']	= '';
					if(isset($val['attendees']) and is_array($val['attendees'])) {
						foreach($val['attendees'] as $key2=>$val2) {
							$BossKillsarray[$bosskey]['members']	.= $val2['name']."\r\n";
						}
						$BossKillsarray[$bosskey]['usernum']	= count($val['attendees']);
					}else {
						//按时间
						foreach($validMember as $key2=>$val2) {
							if(strtotime($val2['jtime']) <= strtotime($val['time']) and strtotime($val2['ltime']) >= strtotime($val['time'])) {
								$BossKillsarray[$bosskey]['members']	.= $val2['name']."\r\n";
								$BossKillsarray[$bosskey]['usernum']	+= 1;
							}
						}
					}
					//boss物品，分数
					$BossKillsarray[$bosskey]['item']	= isset($bosslost[$bosskey])?$bosslost[$bosskey]:'';
					$BossKillsarray[$bosskey]['lootcosts']	= isset($bosscost[$bosskey])?$bosscost[$bosskey]:0;
					$i++;
				}
			}
			//找出小怪掉落物品
			$i		= 0;
			asort($bossKillTime);
			foreach($lootarray as $key=>$val) {
				if(!in_array($val['Boss'],$bossArray)) {
					$nobossLoot[$i]	= $val;
					//如果属于某boss死亡前物品，则自动分配到某boss
					foreach($bossKillTime as $key2=>$val2) {
						if(strtotime($val['Time']) < $val2) {
							$nobossLoot[$i]['toboss']	= $key2;
							break;
						}
					}
					$nobossdkp	+= $val['Costs'];
				}
				$i++;
			}
			$GLOBALS['smarty']->assign("nobossLoot",$nobossLoot);
			$GLOBALS['smarty']->assign("nobossdkp",$nobossdkp);
			$GLOBALS['smarty']->assign("bossArray",$bossArray);
			$GLOBALS['smarty']->assign("BossKillsarray",$BossKillsarray);
		}

		//结果
		$GLOBALS['smarty']->assign("zone",$zone);
		$GLOBALS['smarty']->assign("notes",$notes);
		$GLOBALS['smarty']->assign("etid",$_POST['etid']);
		$GLOBALS['smarty']->assign("cid",$_POST['cid']);

		$GLOBALS['smarty']->assign("startTime",$minJoinTime);
		$GLOBALS['smarty']->assign("endTime",$maxLeaveTime);
		$GLOBALS['smarty']->assign("totalTime",round($totalTime/60));
		$GLOBALS['smarty']->assign("validMember",$validMember);
		$GLOBALS['smarty']->assign("lootarray",$lootarray);

		$GLOBALS['smarty']->assign("startDate",date("Y-m-d",$minJoinTime));
		$GLOBALS['smarty']->assign("startHours",date("H:i",$minJoinTime));
		$GLOBALS['smarty']->assign("endHours",date("H:i",$maxLeaveTime));

		$GLOBALS['smarty']->assign("copyarray",$copyarray);
		$GLOBALS['smarty']->assign("totalMember",$totalMember);
		$GLOBALS['smarty']->assign("totalItemdkp",$totalItemdkp);
		$GLOBALS['smarty']->display($tpl);
	}

	function GetXMLTree ($xmldata)
	{
		// we want to know if an error occurs
		ini_set ('track_errors', '1');

		$xmlreaderror = false;
		//2006-9-11 attributes不初始化会在某些php版本中不可用
		$attributes		= array();

		$parser = xml_parser_create ('UTF-8');
		xml_parser_set_option ($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parser_set_option ($parser, XML_OPTION_CASE_FOLDING, 0);
		if (!xml_parse_into_struct ($parser, $xmldata, $vals, $index)) {
			$xmlreaderror = true;
			echo "error log.";
			exit;
		}
		xml_parser_free ($parser);

		if (!$xmlreaderror) {
			$result = array ();
			$i = 0;
			if (isset ($vals[$i]['attributes']))
				foreach (array_keys ($vals[$i]['attributes']) as $attkey)
				$attributes[$attkey] = $vals[$i]['attributes'][$attkey];
			$result [$vals [$i]['tag']] = array_merge ($attributes, $this->GetChildren ($vals, $i, 'open'));

		}
		ini_set ('track_errors', '0');
		return $result;
	}

	function GetChildren ($vals, &$i, $type)
	{
		if ($type == 'complete') {
			if (isset ($vals [$i]['value']))
				return ($vals [$i]['value']);
			else
				return '';
		}

		$children = array (); // Contains node data

		/* Loop through children */
		while ($vals [++$i]['type'] != 'close') {
			$type = $vals [$i]['type'];
			// first check if we already have one and need to create an array
			if (isset ($children [$vals [$i]['tag']])) {
				if (is_array ($children [$vals [$i]['tag']])) {
					$temp = array_keys ($children [$vals [$i]['tag']]);
					// there is one of these things already and it is itself an array
					if (is_string ($temp [0])) {
						$a = $children [$vals [$i]['tag']];
						unset ($children [$vals [$i]['tag']]);
						$children [$vals [$i]['tag']][0] = $a;
					}
				} else {
					$a = $children [$vals [$i]['tag']];
					unset ($children [$vals [$i]['tag']]);
					$children [$vals [$i]['tag']][0] = $a;
				}

				$children [$vals [$i]['tag']][] = $this->GetChildren ($vals, $i, $type);
			} else
				$children [$vals [$i]['tag']] = $this->GetChildren ($vals, $i, $type);
			// I don't think I need attributes but this is how I would do them:
			if (isset ($vals [$i]['attributes'])) {
				$attributes = array ();
				foreach (array_keys ($vals [$i]['attributes']) as $attkey)
				$attributes [$attkey] = $vals [$i]['attributes'][$attkey];
				// now check: do we already have an array or a value?
				if (isset ($children [$vals [$i]['tag']])) {
					// case where there is an attribute but no value, a complete with an attribute in other words
					if ($children [$vals [$i]['tag']] == '') {
						unset ($children [$vals [$i]['tag']]);
						$children [$vals [$i]['tag']] = $attributes;
					}
					// case where there is an array of identical items with attributes
					elseif (is_array ($children [$vals [$i]['tag']])) {
						$index = count ($children [$vals [$i]['tag']]) - 1;
						// probably also have to check here whether the individual item is also an array or not or what... all a bit messy
						if ($children [$vals [$i]['tag']][$index] == '') {
							unset ($children [$vals [$i]['tag']][$index]);
							$children [$vals [$i]['tag']][$index] = $attributes;
						}
						$children [$vals [$i]['tag']][$index] = array_merge ($children [$vals [$i]['tag']][$index], $attributes);
					} else {
						$value = $children [$vals [$i]['tag']];
						unset ($children [$vals [$i]['tag']]);
						$children [$vals [$i]['tag']]['value'] = $value;
						$children [$vals [$i]['tag']] = array_merge ($children [$vals [$i]['tag']], $attributes);
					}
				} else
					$children [$vals [$i]['tag']] = $attributes;
			}
		}
		return $children;
	}
	
	/**
	 * 判断物品品质
	 */
	function isValidItem($itemcolor,$itemcfg){
		//$GLOBALS['itemquality']
		$itemcolor	= strtolower($itemcolor);
		switch ($itemcolor){
			case 'e6cc80':
				$v	= 0;
			break;
			case 'ff8000':
				$v	= 1;
			break;
			case 'a335ee':
				$v	= 2;
			break;
			case '0070dd':
				$v	= 3;
			break;
			case '1eff00':
				$v	= 4;
			break;
			case 'ffffff':
				$v	= 5;
			break;
			case '9d9d9d':
				$v	= 6;
			break;
			default:
				$v	= 4;
		}
		if ($v <= $itemcfg) {
			return true;
		}else {
			return false;
		}
	}

	/**
	 *
	 *
	 * @param unknown_type $
	 */
	function throwInfo($info, $goto='', $target='self') {
		//var_dump($GLOBALS['smarty']);
        $GLOBALS['smarty']->assign('info', $info);
        $GLOBALS['smarty']->assign('goto', $goto);
        $GLOBALS['smarty']->assign('target', $target);
        $GLOBALS['smarty']->display('info.htm');
        exit;
	}
}




?>