<?php
/**
+-----------------------------------------------------------------------+
* @autor 张涛 <tonera@gmail.com>;
* @since 2005-12-1
* @version $Id: mod_admin.php,v 1.3.7 tonera$
* @description	admin
* @last update 2008-8-24
+-----------------------------------------------------------------------+
*/
define('PAGE_PPL_DEF', 20); 
$GLOBALS['smarty']	= Chino::getObject('smarty');
$GLOBALS['smarty']->left_delimiter	= "{|";
$GLOBALS['smarty']->right_delimiter	= "|}";
require_once CHINO_MODPATH.'/config/config.inc.php';
$GLOBALS['itemquality']	= array('0'=>'e6cc80','1'=>'ff8000','2'=>'a335ee','3'=>'0070dd','4'=>'1eff00','5'=>'ffffff','6'=>'9d9d9d');

$GLOBALS['adodb'] = Chino::getObject('adodb');
include_once CHINO_LIBPATH.'/db_mysql.inc.php';
$GLOBALS['adodb']->debug	= false;
@set_time_limit(0);
class Cpage extends Base{
	var $alias		= 'act';
	var $objTable	= '';
	var $adm		= '';
	var $urlparam	= '';
	var $pageini	= array();
	var $gconfig	= array();
	var $sd			= 2;
	var $copyArr	= array();
	var $timezone	= 0;
	var $syslog		= '';

    function onDefault() {
        $this->onAdmin();
    }

    function onHello() {
        echo "hello tplbuider";
    }
	function onLoad() {
		
		if(empty($_SESSION['wowdkp']['user']) or !$_SESSION['wowdkp']['usertype'] or $_SESSION['wowdkp']['dkptable']!=TABLEHEAD or $_SESSION['wowdkp']['db']!=DBNAME) {
			//var_dump('gg');
			header("Location: index.php?module=index&act=login");
			exit;
		}

		$_GET['offset']	= isset($_GET['offset'])?$_GET['offset']:0;
		$this->copyArr	= explode(':',$_SESSION['wowdkp']['cid']);
        include_once(CURRENTPATH . "/class_admin.php");
        $this->adm = new admin;
		$gconfigarr	= $this->adm->getInfoByTable('config');
		if(is_array($gconfigarr)) {
			foreach($gconfigarr as $key=>$val) {
				$this->gconfig[trim($val['vname'])]	= trim($val['value']);
			}
		}
		$this->timezone	= (float)$this->gconfig['timezone'] * 3600 - date("Z");
		//保留小数
		$this->sd	= (int)$this->gconfig['sdecimal'];
		$this->sd	= $this->sd>=0||$this->sd<=2?$this->sd:2;
		unset($gconfigarr);
		$GLOBALS['lang']['sitetitle']	= $this->gconfig['guildname'];
		$GLOBALS['smarty']->assign("lang",$GLOBALS['lang']);
		$obj		= isset($_GET['obj'])?$_GET['obj']:'0';
		$act		= isset($_GET['act'])?$_GET['act']:"news";
		$orderby	= isset($_GET['orderby'])?$_GET['orderby']:'';
		$_GET['cid']= isset($_GET['cid'])?$_GET['cid']:'';
		$this->urlparam	= "module=admin&act=".$act."&obj=".$obj."&orderby=".$orderby."&cid=".$_GET['cid'];
		$pagecfgfile	= CHINO_MODPATH.'/config/page.inc';
		$pagecfg		= @parse_ini_file($pagecfgfile, true);
		if(!$pagecfg) {
			$this->pageini	= array('i_news'=>30,'i_user'=>30,'i_raids'=>30,'i_event'=>30,'i_item'=>30,'i_dis'=>30,'m_row'=>30);
		}else {
			$this->pageini	= $pagecfg['page'];
		}
		//2006-8-19
		//if(!defined(PAGE_RPP_DEF)) {
			define('PAGE_RPP_DEF', $this->pageini['m_row']); 
		//}
		//2008-9-16
		$this->adm->_htmlspecialchars($_POST, true);
		$this->adm->_htmlspecialchars($_GET, true);
		//2007-1-15
		include_once(CHINO_LIBPATH . "/class_Syslog.php");
		$this->syslog	= new Syslog($GLOBALS['adodb'],$_SESSION['wowdkp']['userid'],$_SESSION['wowdkp']['user'],TABLEHEAD);
		$GLOBALS['smarty']->template_dir	= CHINO_WWWROOT.'/theme/'.$this->gconfig['dstyle'].'/admin';
		$GLOBALS['smarty']->assign("theme",$this->gconfig['dstyle']);
		$GLOBALS['smarty']->assign("lng",$this->gconfig['langtype']);
		$GLOBALS['smarty']->assign("lo",$this->gconfig['location']);
    }

	//管理首页
	function onAdmin() {
		$GLOBALS['smarty']->display("frame.htm");
	}
	//上
	function onTop() {
		$GLOBALS['smarty']->assign("admininfo",$_SESSION['wowdkp']['user']);
		$GLOBALS['smarty']->display("top.htm");
	}
	//左
	function onMenu() {
		if($_SESSION['wowdkp']['power']>=10) {
			$GLOBALS['smarty']->display("menu.htm");
		}else {
			$GLOBALS['smarty']->display("menu_dkp.htm");
		}		
	}

	//list:用户列表
	function onUserlist() {
		$this->adm->_adminCheck(5);
		//种族
		$classArray	= $this->adm->getInfoByTable('work');
		if(is_array($classArray)) {
			$classArr	= array();
			$classArr[]	= $GLOBALS['lang']['pleaselect'];
			foreach($classArray as $key=>$val) {
				$classArr[$val['id']]	= $val['name'];
			}
		}
		$sql	= "select a.id,a.name,a.password,b.name as raceid,c.name as workid,a.level as level,a.notes,a.pic,d.name as groupid,a.stat as stat,a.regtime as regtime from ".TABLEHEAD."_user as a left join ".TABLEHEAD."_race as b on a.raceid=b.id left join ".TABLEHEAD."_work as c on a.workid=c.id left join ".TABLEHEAD."_group as d on a.groupid=d.id where 1 ";
		$keyword	= $_GET['keyword'];
		if(!empty($keyword)) {
			$sql	.= " and a.name like '%".$keyword."%' ";
		}
		$classid	= $_GET['obj']?$_GET['obj']:'0';
		if($classid) {
			$sql	.= " and a.workid='$classid' ";
		}
		$orderby	= !empty($_GET['orderby'])?$_GET['orderby']:"regtime";
		if(!empty($orderby)) {
			$sql		.= " order by $orderby desc";
		}
		$baseurl	= "index.php?module=admin&act=userlist&obj=".$classid;
		//var_dump($this->urlparam);
		$userdata	= $this->adm->getPerPageInfo($sql,$this->urlparam);
		$GLOBALS['smarty']->assign("classArr",$classArr);
		$GLOBALS['smarty']->assign("baseurl",$baseurl);
		$GLOBALS['smarty']->assign("getval",$_GET);
		$GLOBALS['smarty']->assign("userdata",$userdata[1]);
		$GLOBALS['smarty']->assign("pageText",$userdata[0]);
		$GLOBALS['smarty']->display("list_wowdkp_user.htm");
	}

	//form:添加用户表单
	function onAdduserform() {
		$this->adm->_adminCheck(5);
		$groupinfo	= $raceinfo = $workinfo = array();
		$racearray	= $this->adm->getInfoByTable("race");
		foreach($racearray as $key=>$val) {
			$raceinfo[$val['id']]	= $val['name'];
		}
		$workarray	= $this->adm->getInfoByTable("work");
		foreach($workarray as $key=>$val) {
			$workinfo[$val['id']]	= $val['name'];
		}
		$grouparray	= $this->adm->getInfoByTable("group");
		foreach($grouparray as $key=>$val) {
			$groupinfo[$val['id']]	= $val['name'];
		}
		$GLOBALS['smarty']->assign("act","adduser");
		$GLOBALS['smarty']->assign("raceinfo",$raceinfo);
		$GLOBALS['smarty']->assign("workinfo",$workinfo);
		$GLOBALS['smarty']->assign("groupinfo",$groupinfo);
		$GLOBALS['smarty']->display("add_wowdkp_user.htm");
	}
	//list:种族管理
	function onRacelist() {
		$this->adm->_adminCheck(10);
		$userinfo	= $this->adm->getInfoByTable("race");
		$GLOBALS['smarty']->assign("userdata",$userinfo);
		$GLOBALS['smarty']->display("manage_wowdkp_race.htm");
	}
	//list:管理员管理
	function onManagerlist() {
		$this->adm->_adminCheck(10);
		//副本
		$copyarrays	= $this->adm->getInfoByTable("copy");
		if(is_array($copyarrays)) {
			foreach($copyarrays as $key=>$val) {
				$copyarray[$val['id']]	= $val['name'];
			}
		}
		$userinfo	= $this->adm->getInfoByTable("admuser");
		$GLOBALS['smarty']->assign("userdata",$userinfo);
		$GLOBALS['smarty']->assign("copyarray",$copyarray);
		$GLOBALS['smarty']->display("manage_wowdkp_managerlist.htm");
	}
	//list:职业管理
	function onWorklist() {
		$this->adm->_adminCheck(10);
		$userinfo	= $this->adm->getInfoByTable("work");
		$GLOBALS['smarty']->assign("userdata",$userinfo);
		$GLOBALS['smarty']->display("manage_wowdkp_work.htm");
	}
	//list:事件类型管理
	function onEventtypelist() {
		$this->adm->_adminCheck(10);
		$userinfo	= $this->adm->getInfoByTable("eventtype");
		$GLOBALS['smarty']->assign("userdata",$userinfo);
		$GLOBALS['smarty']->display("manage_wowdkp_eventtype.htm");
	}
	//list:成员组
	function onUsergrouplist() {
		$this->adm->_adminCheck(5);
		$userinfo	= $this->adm->getInfoByTable("group");
		$GLOBALS['smarty']->assign("userdata",$userinfo);
		$GLOBALS['smarty']->display("manage_wowdkp_group.htm");
	}
	//list:mcopy多副本dkp
	function onMcopy() {
		$this->adm->_adminCheck(10);
		$userinfo	= $this->adm->getInfoByTable("copy");
		$GLOBALS['smarty']->assign("userdata",$userinfo);
		$GLOBALS['smarty']->display("manage_wowdkp_copy.htm");
	}

	//list:事件管理
	function onEventlist() {
		$this->adm->_adminCheck(5);
		$copyarray	= $this->adm->_getcopy();
		array_unshift($copyarray,$GLOBALS['lang']['all']);
		$cstr		= implode(',',$this->copyArr);
		$cstr		= $cstr?$cstr:0;
		$sql	= "select a.id as id,a.name as name,c.name as copy,a.raidtime as raidtime,b.name as etid from ".TABLEHEAD."_event as a left join ".TABLEHEAD."_eventtype as b on a.etid=b.id left join ".TABLEHEAD."_copy as c on a.cid=c.id where c.id in($cstr) ";
		if(!empty($_GET['cid'])) {
			$sql		.= " and a.cid='$_GET[cid]' ";
		}
		if(!empty($_GET['keyword']))
		{
			$sql	.= " and a.name = '$_GET[keyword]' ";
		}
		$orderby	= $_GET['orderby']?addslashes(trim($_GET['orderby'])):"raidtime";
		if(!empty($orderby)) {
			$sql		.= " order by $orderby desc";
		}
		$userdata	= $this->adm->getPerPageInfo($sql,$this->urlparam);
		$baseurl	= "index.php?module=admin&act=".$_GET['act']."&obj=".$_GET['obj'];
		$GLOBALS['smarty']->assign("baseurl",$baseurl);
		$GLOBALS['smarty']->assign("userdata",$userdata[1]);
		$GLOBALS['smarty']->assign("pageText",$userdata[0]);
		$GLOBALS['smarty']->assign("copyarray",$copyarray);
		$GLOBALS['smarty']->assign("getval",$_GET);
		$GLOBALS['smarty']->display("list_wowdkp_event.htm");
	}
	//form:添加事件
	function onAddeventform() {
		$this->adm->_adminCheck(5);
        $userinfo   = array();
		$eventtypearray	= $this->adm->getInfoByTable("eventtype");
		if(is_array($eventtypearray)) {
			foreach($eventtypearray as $key=>$val) {
				$eventtype[$val['id']]	= $val['name'];
			}
		}
		//副本_getCopy
		$copyarray	= $this->adm->_getcopy();
		//var_dump($copyarray);
		//用户信息
		$classarr	= $this->adm->getInfoByTable("work");
		foreach($classarr as $key=>$val) {
			$classarray[$val['id']]	= $val['name'];
		}
		$classarray['unk']			= 'unkown';
		
		$sql	= "select a.id as id,a.name as name,b.id as classid,b.wcolor as wcolor from ".TABLEHEAD."_user as a left join ".TABLEHEAD."_work as b on a.workid=b.id order by workid desc";
		$rs		= $GLOBALS['adodb']->execute($sql);
        $userdata   = array();
		while(!$rs->EOF) {
			if(empty($rs->fields['classid'])) {
				$classkey	= 'unk';
			}else {
				$classkey	= $rs->fields['classid'];
			}
			//$userinfo[$classkey][0]	= $classarray[$classkey];
			$userdata[$classkey][]	= $rs->fields;
			$rs->MoveNext();
		}
		$userdata	= is_array($userdata)?$userdata:array();
		foreach($userdata as $key=>$val) {
			$userselect	= '<table border=0 width=90%>';
			$userselect	.= "<tr class=title><td colspan=5>".$classarray[$key]."</td></tr>";
			$i		= 1;
			$istr	= 1;
			foreach($val as $key1=>$val1) {
				if($istr) {
					$bgcolor	= $val1['wcolor']+3333;
					$userselect	.= "<tr bgcolor='#".$bgcolor."'>";
					$istr		= 0;
				}
				$userselect	.= "<td width=20%><input name='uidselect[]' type='checkbox' value='".$val1['id']."' checked><font color=".$val1['wcolor'].">".$val1['name']."</font></td>";
				if($i%5==0) {
					$userselect	.= "</tr>";
					$istr		= 1;
				}
				$i++;
			}
			$userselect	.= "</table><br />";
			$userinfo[$key]	= $userselect;
		}

		$GLOBALS['smarty']->assign("userinfo",$userinfo);
		$GLOBALS['smarty']->assign("eventtype",$eventtype);
		$GLOBALS['smarty']->assign("copyarray",$copyarray);
		$GLOBALS['smarty']->assign("act","addevent");
		$GLOBALS['smarty']->assign("dTime",date("Y-m-d",time() + $this->timezone));
		$GLOBALS['smarty']->display("add_wowdkp_event.htm");
	}
	//添加表单
	function onAdd() {
		if(isset($_POST['jumpurl'])) {
			$jumpurl	= $_POST['jumpurl'];
		}else {
			$jumpurl	= $_SERVER["HTTP_REFERER"];
		}
		switch($_POST['obj']) {
			case 'raidcfg':
				$this->adm->_adminCheck(5);
				//classreq:2|classreq:3
				$classcfg	= array();
				if(is_array($_POST['class'])) {
					foreach($_POST['class'] as $key=>$val) {
						$classcfg[]	= (int)$key.':'.(int)$val;
					}
				}
				$classreq	= implode('|',$classcfg);
				$resistance	= (int)$_POST['arcane'].':'.(int)$_POST['fire'].':'.(int)$_POST['frost'].':'.(int)$_POST['nature'].':'.(int)$_POST['shadow'];
				$_POST['autoqueue']	= (int)$_POST['autoqueue'];
				$insarr	= array('name'=>$_POST['name'],
								'maxnum'=>$_POST['raidmax'],
								'classreq'=>$classreq,
								'resistance'=>$resistance,
								'minlevel'=>$_POST['minlevel'],
								'maxlevel'=>$_POST['maxlevel'],
								'autoqueue'=>$_POST['autoqueue']);
				$insid	= $this->adm->addRecord('raidcfg',$insarr);
				break;
			case 'raidlog':
				$this->adm->_adminCheck(5);
				$_POST['invitetime']	= $_POST['invitedate']." ".$_POST['invitetime1'].":".$_POST['inviteminutes'].":00";
				$_POST['starttime']	= $_POST['invitedate']." ".$_POST['starttime1'].":".$_POST['startminutes'].":00";
				$_POST['circaendtime']	= $_POST['invitedate']." ".$_POST['circaendtime1'].":".$_POST['circaendminutes'].":00";
				
				if(empty($_POST['name'])) {
					$this->throwInfo($GLOBALS['lang']['name'].$GLOBALS['lang']['nonull'], $jumpurl);
				}
				if(empty($_POST['rid'])) {
					$this->throwInfo($GLOBALS['lang']['pleaselect'].$GLOBALS['lang']['configure'], $jumpurl);
				}
				if(strtotime($_POST['invitetime']) >= strtotime($_POST['starttime'])) {
					$this->throwInfo($GLOBALS['lang']['note45'], $jumpurl);
				}
				
				$insarr	= array('name'=>$_POST['name']
					,'rid'=>$_POST['rid']
					,'invitetime'=>$_POST['invitetime']
					,'starttime'=>$_POST['starttime']
					,'circaendtime'=>$_POST['circaendtime']
					,'freezelimit'=>$_POST['freezelimit']
					,'notes'=>$_POST['notes']);
				$insid	= $this->adm->addRecord('raidlog',$insarr);
				break;			
		}
		if($insid) {
			$this->throwInfo($GLOBALS['lang']['opertion'].$GLOBALS['lang']['succeed'], $jumpurl);
		}else {
			$this->throwInfo($GLOBALS['lang']['opertion'].$GLOBALS['lang']['faild'],$jumpurl);
		}
	}

	//list:物品管理
	function onItemlist() {
		$this->adm->_adminCheck(5);
		$eventarray	= $this->adm->_getEventBysess(true);
		$cstr		= implode(',',$this->copyArr);
		$cstr		= $cstr?$cstr:0;
		$sql		= "select a.*, 
							b.name as eid ,
							d.name as itemname  
						from ".TABLEHEAD."_item as a 
						left join ".TABLEHEAD."_event as b on a.eid=b.id  
						left join ".TABLEHEAD."_itemproperty as d on a.ipid=d.id 
						where b.cid in(".$cstr.") ";
		if(!empty($_GET['eid'])) {
			$sql	.= " and b.id='$_GET[eid]' ";
		}
		$orderby	= addslashes(trim($_GET['orderby']));
		$orderby	= $orderby?$orderby:"intotime";
		if(!empty($orderby)) {
			$sql		.= " order by $orderby desc";
		}
		$userdata	= $this->adm->getPerPageInfo($sql,$this->urlparam);
        $matches    = array();
		foreach ($userdata[1] as $k => $v){
			preg_match("/(\d+):/i",$v['itemname'], $matches);
			$userdata[1][$k]['itemid']	= isset($matches[1])?$matches[1]:0;
		}
		$_GET['eid']	= isset($_GET['eid'])?$_GET['eid']:'';
		$baseurl	= "index.php?module=admin&act=".$_GET['act']."&obj=".$_GET['obj']."&eid=".$_GET['eid'];
		$GLOBALS['smarty']->assign("baseurl",$baseurl);
		$GLOBALS['smarty']->assign("getval",$_GET);
		$GLOBALS['smarty']->assign("eventarray",$eventarray);
		$GLOBALS['smarty']->assign("userdata",$userdata[1]);
		$GLOBALS['smarty']->assign("pageText",$userdata[0]);
		$GLOBALS['smarty']->display("list_wowdkp_item.htm");
	}
	//list:新闻管理
	function onNewslist() {
		$this->adm->_adminCheck(5);
		$sql	= "select * from ".TABLEHEAD."_news order by posttime desc";
		$userdata	= $this->adm->getPerPageInfo($sql,$this->urlparam);
		//var_dump($userdata);
		$GLOBALS['smarty']->assign("userdata",$userdata[1]);
		$GLOBALS['smarty']->assign("pageText",$userdata[0]);
		$GLOBALS['smarty']->display("list_wowdkp_news.htm");
	}
	//list:raidcfg 活动配置管理
	function onRaidcfg() {
		$this->adm->_adminCheck(5);
		//职业
		$classinfo	= $this->adm->getInfoByTable("work");
		foreach($classinfo as $key=>$val) {
			$classarr[$val['id']]	= $val['name'];
		}
		$sql	= "select * from ".TABLEHEAD."_raidcfg";
		$userdata	= $this->adm->getPerPageInfo($sql,$this->urlparam);
		foreach($userdata[1] as $key=>$val) {
			$racearray	= explode('|',$val['classreq']);
			foreach($racearray as $key2=>$val2) {
				$racetmp	= explode(':',$val2);
				$raceid		= $racetmp[0];
				$userdata[1][$key]['class'][$raceid]	= $racetmp[1];
			}
			$resistancearr	= explode(':',$val['resistance']);
			$userdata[1][$key]['resistance']	= $val['name']."<br />";
			foreach($GLOBALS['lang']['resistance'] as $key3=>$val3) {
				$userdata[1][$key]['resistance']	.= $val3.":".$resistancearr[$key3]."<br />";
			}
			$userdata[1][$key]['notes']			= $userdata[1][$key]['resistance'];
			if($userdata[1][$key]['autoqueue'] == '1') {
				$userdata[1][$key]['notes']		.= $GLOBALS['lang']['autoqueue'];
			}
		}
		$GLOBALS['smarty']->assign("userdata",$userdata[1]);
		$GLOBALS['smarty']->assign("pageText",$userdata[0]);
		$GLOBALS['smarty']->assign("classarr",$classarr);
		$GLOBALS['smarty']->display("list_raidcfg.htm");
	}
	//list:列表
	function onList() {
		$obj			= $_GET['obj'];
		$_GET['rid']	= isset($_GET['rid'])?$_GET['rid']:0;
		switch($obj) {
			case 'raidlog':
				$rid		= (int)$_GET['rid'];
				$raidcfg	= array();
				//职业
				$classarr[0]= 'Unknow';
				$classinfo	= $this->adm->getInfoByTable("work");
				foreach($classinfo as $key=>$val) {
					$classarr[$val['id']]	= $val['name'];
				}
				$sql	= "select a.*,b.name as cfg,b.maxnum as maxnum,b.classreq as classreq,b.resistance as resistance,b.minlevel as minlevel,b.maxlevel as maxlevel,b.autoqueue as autoqueue from ".TABLEHEAD."_raidlog as a left join ".TABLEHEAD."_raidcfg as b on a.rid=b.id order by a.starttime,a.stat desc ";
				//echo($sql);
				$userdata	= $this->adm->getPerPageInfo($sql,$this->urlparam);
				//配置属性
				if(is_array($userdata[1])) {
					foreach($userdata[1] as $key=>$val) {
						$userdata[1][$key]['cfgnotes']	= '<font color=blue>'.$val['cfg']."</font><br>";
						$userdata[1][$key]['cfgnotes']	.= $GLOBALS['lang']['raidmax'].':'.$val['maxnum']."<br>";
						$userdata[1][$key]['cfgnotes']	.= $GLOBALS['lang']['minlevel'].':'.$val['minlevel']."<br>";
						$userdata[1][$key]['cfgnotes']	.= $GLOBALS['lang']['maxlevel'].':'.$val['maxlevel']."<br>";
						$tmpclass	= explode('|',$val['classreq']);
						if(is_array($tmpclass)) {
							$signnum[0]	= 0;
							foreach($tmpclass as $key2=>$val2) {
								$tc		= explode(':',$val2);
								$signnum[$tc[0]]	= $tc[1];
								$userdata[1][$key]['cfgnotes']	.= $classarr[$tc[0]].':'.$tc[1].'<br>';
							}
						}
						$userdata[1][$key]['starttime']	= date("H:i",strtotime($userdata[1][$key]['starttime']));
						$userdata[1][$key]['circaendtime']	= date("m-d H:i",strtotime($userdata[1][$key]['circaendtime']));
						$tmpresistance	= explode(':',$val['resistance']);
						if(is_array($tmpresistance)) {
							foreach($tmpresistance as $key3=>$val3) {
								$userdata[1][$key]['cfgnotes']	.= $GLOBALS['lang']['resistance'][$key3].':'.$val3.'<br>';
							}
						}
						//此raid活动的报名情况 stat 0:取消,1:正式,2:候补
						$raid	= $val['id'];
						$sql	= "select count(id) as num,workid from ".TABLEHEAD."_signup where raid= '$raid' and (stat=1 or stat=2) group by workid";
						//echo($sql);
						$signclass	= $this->adm->excuteSql($sql);
						$userdata[1][$key]['signupnotes']	= $GLOBALS['lang']['action'].$GLOBALS['lang']['signup'].'<br />';
						if(is_array($signclass)) {
							foreach($signclass as $key3=>$val3) {
								$userdata[1][$key]['signupnotes']	.= $classarr[$val3['workid']].':'.$val3['num'].'/'.$signnum[$val3['workid']].'<br />';
							}
						}
						$userdata[1][$key]['signupnotes']	.= $GLOBALS['lang']['freezelimit'].':'.$val['freezelimit'].'<br />';
						$userdata[1][$key]['signupnotes']	.= $val['notes'];
					}
				}
				//var_dump($signnum);
				//config
				$configarr	= $this->adm->getInfoByTable("raidcfg");
				$configure[]= $GLOBALS['lang']['pleaselect'];
				$calsscfg	= array();
				if(is_array($configarr)) {
					foreach($configarr as $key=>$val) {
						$configure[$val['id']]	= $val['name'];
						if($val['id'] == $rid) {
							$raidcfg	= $val;
							$classinfo	= explode('|',$raidcfg['classreq']);
							$calsscfg	= array();
							if(is_array($classinfo)) {
								foreach($classinfo as $key=>$val) {
									$tmpclass	= explode(':',$val);
									$calsscfg[$tmpclass[0]]	= $tmpclass[1];
								}
							}
							
							$resistance	= explode(':',$raidcfg['resistance']);
							$GLOBALS['smarty']->assign("resistance",$resistance);
						}
					}
				}
				
				$itime	= date("Y-m-d H:i:s",time() + $this->timezone);
				$pageinfo['hours']	= array();
				$pageinfo['minutes']	= array();
				$pageinfo['invitedate']	= date("Y-m-d",time() + $this->timezone);
				for($i=0; $i<24; $i++) {
					$pageinfo['hours'][]	= $i;
				}
				for($i=0; $i<60; $i++) {
					$pageinfo['minutes'][]	= $i;
				}

				$tpl	= "list_raidlog.htm";
				$GLOBALS['smarty']->assign("configure",$configure);
				$GLOBALS['smarty']->assign("itime",$itime);
				$GLOBALS['smarty']->assign("raidcfg",$raidcfg);
				$GLOBALS['smarty']->assign("classarr",$classarr);
				$GLOBALS['smarty']->assign("calsscfg",$calsscfg);
				$GLOBALS['smarty']->assign("pageinfo",$pageinfo);
				$GLOBALS['smarty']->assign("getval",$_GET);
			break;
			case 'showsignup':
				$id			= (int)$_GET['id'];
				$members	= $this->adm->excuteSql("select a.*,b.name as username,b.workid as workid,b.level as level from ".TABLEHEAD."_signup as a left join ".TABLEHEAD."_user as b on a.uid=b.id where a.raid='$id'");
				$classes	= $this->adm->getInfoByTable('work');
				$members	= is_array($members)?$members:array();
				$classes	= is_array($classes)?$classes:array();
				$classcolor[0]	= "FFFFFF";
				$classname[0]	= "Unknow";
				foreach($classes as $key=>$val) {
					$classcolor[$val['id']]	= $val['wcolor'];
					$classname[$val['id']]	= $val['name'];
				}
				$fullmembers	= array();
				$recruits		= array();
				foreach($members as $key=>$val) {
					$val['classname']	= $classname[$val['workid']];
					$val['color']		= $classcolor[$val['workid']];
					if($val['stat'] == '1') {
						$fullmembers[$val['workid']]['member'][]	= $val;
						$fullmembers[$val['workid']]['color']		= $classcolor[$val['workid']];
						$fullmembers[$val['workid']]['cname']		= $classname[$val['workid']];
					}elseif($val['stat'] == '2') {
						$recruits[]	= $val;
					}else {
						$cancels[]	= $val;
					}					
				}
				$tpl	= "list_showsignup.htm";
				$GLOBALS['smarty']->assign("fullmembers",$fullmembers);
				$GLOBALS['smarty']->assign("recruits",$recruits);
				$GLOBALS['smarty']->assign("cancels",$cancels);
			break;
			case 'syslog':
				$_GET['uid']	= isset($_GET['uid'])?$_GET['uid']:0;
				$this->adm->_adminCheck(10);
				$sql	= "select * from  ".TABLEHEAD."_syslog where 1 ";
				if(!empty($_GET['uid']))
				{
					$sql	.= " and uid = ". (int)$_GET['uid'];
				}
				$sql	.= " order by optime desc";
				//echo($sql);
				$userdata	= $this->adm->getPerPageInfo($sql,$this->urlparam."&uid=".$_GET['uid']);
				$manager	= $this->adm->excuteSql("select * from ".TABLEHEAD."_admuser");
				$manager	= is_array($manager)?$manager:array();
				$userarray[0]	= "None";
				foreach($manager as $key=>$val) 
				{
					$userarray[$val['id']]	= $val['username'];
				}				
				$tpl	= "list_syslog.htm";
				$GLOBALS['smarty']->assign("userarray",$userarray);
				$GLOBALS['smarty']->assign("getval",$_GET);
				break;
			case 'dkpunite':
				$this->adm->_adminCheck(10);
				$copyarray	= $this->adm->_getcopy();
				$GLOBALS['smarty']->assign("copyarray",$copyarray);
				if(!empty($_POST['dkpunite']))
				{
					$dkpvalue		= (float)$_POST['dkpvalue'];
					$fcid			= (int)$_POST['fromcid'];
					$tcid			= (int)$_POST['tocid'];
					$raidtime		= date("Y-m-d");
					if($fcid == $tcid)
					{
						$this->throwInfo($GLOBALS['lang']['note55'], $_SERVER["HTTP_REFERER"]);
                        exit;
					}
					$eventArray	= array('name'=>'To transfer dkp','etid'=>2,'raidtime'=>$raidtime,'cid'=>$tcid);
					$eid		= $this->adm->addRecord('event',$eventArray);
					//开始DKP转移
					$sql	= "select a.*,b.name as username from ".TABLEHEAD."_dkpvalues as a left join  ".TABLEHEAD."_user as b on a.uid=b.id where a.copyid = $fcid";
					$fdkp	= $this->adm->excuteSql($sql);
					$sql	= "select * from ".TABLEHEAD."_dkpvalues where copyid = $tcid";
					$tdkp	= $this->adm->excuteSql($sql);
					$fdkp	= is_array($fdkp)?$fdkp:array();
					$tdkp	= is_array($tdkp)?$tdkp:array();
					$fuser	= array();
					$tuser	= array();
					$dfuid	= array();
					//如果目标副本没此会员记录
					foreach($fdkp as $key=>$val) 
					{
						$fuser[]			= $val['uid'];
					}
					foreach($tdkp as $key=>$val) 
					{
						$tuser[]			= $val['uid'];
					}
					$dfuid	= array_diff($fuser,$tuser);
					foreach($dfuid as $key=>$valuid) 
					{
						$insArray			= array();
						$insArray['uid']	= $valuid;
						$insArray['copyid']	= $tcid;
						$this->adm->addRecord('dkpvalues',$insArray);
					}
					//开始转换
					$disresult	= '';
					foreach($fdkp as $key=>$val) 
					{
						if($_POST['operator'] == '1')
						{
							$tdkp	= $val['dkpvalue'] + $dkpvalue;
						}elseif($_POST['operator'] == '2')
						{
							$tdkp	= $val['dkpvalue'] - $dkpvalue;
						}elseif($_POST['operator'] == '3')
						{
							$tdkp	= $val['dkpvalue'] * $dkpvalue;
						}elseif($_POST['operator'] == '4')
						{
							if($dkpvalue == 0)
							{
								$tdkp	= $val['dkpvalue'];
							}else {
								$tdkp	= $val['dkpvalue'] / $dkpvalue;
							}							
						}else {
							$tdkp	= $val['dkpvalue'];
						}
						//调节的dkp与原来之间的变化值
						$disdkp		= $tdkp - $val['dkpvalue'];
						$GLOBALS['adodb']->execute("insert into ".TABLEHEAD."_itemdis(eid,uid,value,distime,stat,cid) values('$eid','$val[uid]','$tdkp','$raidtime','1','$tcid')");
						$sql	= "update ".TABLEHEAD."_dkpvalues set dkpvalue=dkpvalue+$tdkp where uid='$val[uid]' and copyid= '$tcid'";
						$GLOBALS['adodb']->execute($sql);
						$sql	= "update ".TABLEHEAD."_dkpvalues set dkpvalue=0 where uid='$val[uid]' and copyid= '$fcid'";
						$GLOBALS['adodb']->execute($sql);
                        //原副本事件的分数置为0
                        $sql    = "update ".TABLEHEAD."_itemdis set value=0 where cid='$fcid'";
                        $GLOBALS['adodb']->execute($sql);
						$disresult	.= "User:".$val['username'].":".$val['dkpvalue']."->".$tdkp."<br />";
					}
					$GLOBALS['smarty']->assign("disresult",$disresult);
					$GLOBALS['smarty']->assign("postval",$_POST);
				}
				$tpl	= "form_dkpunite.htm";
				break;
		}
		$userdata[1]	= isset($userdata[1])?$userdata[1]:array();
		$userdata[0]	= isset($userdata[0])?$userdata[0]:'';
		$GLOBALS['smarty']->assign("userdata",$userdata[1]);
		$GLOBALS['smarty']->assign("pageText",$userdata[0]);
		$GLOBALS['smarty']->display($tpl);
	}

	//form:添加物品
	function onAdditemform() {
		$this->adm->_adminCheck(5);
		$eventarray	= $this->adm->_getEventBysess();
		$userdata['num']	= 1;
		$userarray			= array();
		$userinfo	= $this->adm->getInfoByTable("user",'',"workid");
		$userarray[0]	= $GLOBALS['lang']['doesnot'];
		foreach($userinfo as $key=>$val) {
			$userarray[$val['id']]	= '['.$val['workid'].']'.$val['name'];
		}
		$GLOBALS['smarty']->assign("userarray",$userarray);
		$GLOBALS['smarty']->assign("eventarray",$eventarray);
		$GLOBALS['smarty']->assign("act","additem");
		$GLOBALS['smarty']->assign("userdata",$userdata);
		$GLOBALS['smarty']->display("add_wowdkp_item.htm");
	}
	//act:添加物品
	function onAdditem() {
		$this->adm->_adminCheck(5);
		if(empty($_POST['name'])) {
			$this->throwInfo($GLOBALS['lang']['item'].$GLOBALS['lang']['name'].$GLOBALS['lang']['nonull'], $_SERVER["HTTP_REFERER"]);
		}
		$inputarray	= array("eid","name","num","notes","icolor");
		foreach($_POST as $key=>$val) {
			if(in_array($key,$inputarray)) {
				$addarray[$key]	= $val;
			}
		}
		$addarray['intotime']	= date("Y-m-d H:i:s",time() + $this->timezone);
		$itemid		= $this->adm->addRecord("item",$addarray);
		$this->syslog->writeLog($GLOBALS['oplog']['a_item'],$_POST['name']);
		//2006-9-7如果物品被分配
		if(!empty($_POST['uid'])) {
			$value	= abs((float)$_POST['value']);
			$cids	= $this->adm->excuteSql("select cid from ".TABLEHEAD."_event where id='$_POST[eid]'");
			$cid	= $cids[0]['cid'];
			$inarr	= array('iid'=>$itemid,'eid'=>$_POST['eid'],'uid'=>$_POST['uid'],'value'=>$value,'distime'=>$addarray['intotime'],'stat'=>'-1','cid'=>$cid);
			$this->adm->addRecord("itemdis",$inarr);
			//置物品已分配
			$GLOBALS['adodb']->execute("update ".TABLEHEAD."_item set stat=1 where id='$itemid'");
			$this->adm->_accountdkp($_POST['uid']);
		}elseif(!empty($_POST['uname'])) {
			//手动添加的会员
			$value	= abs((float)$_POST['value']);
			$cids	= $this->adm->excuteSql("select cid from ".TABLEHEAD."_event where id='$_POST[eid]'");
			$cid	= $cids[0]['cid'];
			//查会员id号
			$uarr	= $this->adm->excuteSql("select id from ".TABLEHEAD."_user where name='$_POST[uname]'");
			if(empty($uarr[0]['id'])) {
				$this->throwInfo($GLOBALS['lang']['note7'], $_SERVER["HTTP_REFERER"]);
			}
			$_POST['uid']	= $uarr[0]['id'];
			$inarr	= array('iid'=>$itemid,'eid'=>$_POST['eid'],'uid'=>$_POST['uid'],'value'=>$value,'distime'=>$addarray['intotime'],'stat'=>'-1','cid'=>$cid);
			$this->adm->addRecord("itemdis",$inarr);
			//置物品已分配
			$GLOBALS['adodb']->execute("update ".TABLEHEAD."_item set stat=1 where id='$itemid'");
			$this->adm->_accountdkp($_POST['uid']);
		}else {
			;
		}

		$rs		= $this->adm->addRecord("eventitem",array('eid'=>$_POST['eid'],'iid'=>$itemid));
		if(!$rs) {
			$this->throwInfo($GLOBALS['lang']['add'].$GLOBALS['lang']['item'].$GLOBALS['lang']['faild'], $_SERVER["HTTP_REFERER"]);
		}else {
			$this->throwInfo($GLOBALS['lang']['add'].$GLOBALS['lang']['item'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
		}
	}
	//list:物品分配列表
	function onItemdislist() {
		$this->adm->_adminCheck(5);
		//种族
		$classArray	= $this->adm->getInfoByTable('work');
		if(is_array($classArray)) {
			$classArr	= array();
			$classArr[]	= $GLOBALS['lang']['pleaselect'];
			foreach($classArray as $key=>$val) {
				$classArr[$val['id']]	= $val['name'];
			}
		}
		$cstr		= implode(',',$this->copyArr);
		$cstr		= $cstr?$cstr:0;
		$sql		= "select a.id as id ,
								b.name as iid ,
								c.name as uid ,
								d.name as eid ,
								a.value as value ,
								a.distime as distime ,
								b.icolor as icolor ,
								e.name as itemname 
						from ".TABLEHEAD."_itemdis as a 
						left join ".TABLEHEAD."_item as b on a.iid=b.id 
						left join ".TABLEHEAD."_user as c on a.uid=c.id 
						left join ".TABLEHEAD."_event as d on a.eid=d.id 
						left join ".TABLEHEAD."_itemproperty as e on b.ipid=e.id 
						where a.iid is not null and d.cid in(".$cstr.") ";
		//var_dump($sql);
		$classid	= $_GET['obj']?$_GET['obj']:'0';
		if($classid) {
			$sql	.= " and c.workid='$classid' ";
		}
		if(!empty($_GET['keyword']))
		{
			$sql	.= " and c.name='$_GET[keyword]' ";
		}
		$userdata	= $this->adm->getPerPageInfo($sql,$this->urlparam."&keyword=".urlencode($_GET['keyword']));
        $matches    = array();
		foreach ($userdata[1] as $k => $v){
			preg_match("/(\d+):/i",$v['itemname'], $matches);
			$userdata[1][$k]['itemid']	= isset($matches[1])?$matches[1]:0;
		}
		$GLOBALS['smarty']->assign("classArr",$classArr);
		$GLOBALS['smarty']->assign("getval",$_GET);
		$GLOBALS['smarty']->assign("userdata",$userdata[1]);
		$GLOBALS['smarty']->assign("pageText",$userdata[0]);
		$GLOBALS['smarty']->display("list_wowdkp_itemdis.htm");
	}
	//list:dkp调节列表
	function onDkpdislist() {
		$this->adm->_adminCheck(5);
		$copyarray	= $this->adm->_getcopy();
		$cstr		= implode(',',$this->copyArr);
		$classArray	= $this->adm->getInfoByTable('work');
		if(is_array($classArray)) {
			$classArr	= array();
			$classArr[]	= $GLOBALS['lang']['pleaselect'];
			foreach($classArray as $key=>$val) {
				$classArr[$val['id']]	= $val['name'];
			}
		}
		$sql		= "select a.id as id,b.name as iid,c.name as uid,d.name as eid,ROUND(a.value,$this->sd) as value,a.distime as distime from ".TABLEHEAD."_itemdis as a left join ".TABLEHEAD."_item as b on a.iid=b.id left join ".TABLEHEAD."_user as c on a.uid=c.id left join ".TABLEHEAD."_event as d on a.eid=d.id where d.cid in ($cstr) and a.iid is null ";
		$keyword	= $_GET['keyword'];
		if(!empty($keyword)) {
			$sql	.= " and c.name like '%".$keyword."%' ";
		}
		$classid	= $_GET['obj']?$_GET['obj']:'0';
		if($classid) {
			$sql	.= " and c.workid='$classid' ";
		}
		$cid		= $_GET['cid']?$_GET['cid']:0;
		if(!empty($cid)) {
			$sql	.= " and d.cid='$cid' ";
		}
		$sql	.= " order by distime desc ";
		//var_dump($sql);
		$userdata	= $this->adm->getPerPageInfo($sql,$this->urlparam."&keyword=".urlencode($keyword));
		$GLOBALS['smarty']->assign("classArr",$classArr);
		$GLOBALS['smarty']->assign("getval",$_GET);
		$GLOBALS['smarty']->assign("userdata",$userdata[1]);
		$GLOBALS['smarty']->assign("pageText",$userdata[0]);
		$GLOBALS['smarty']->assign("copyarray",$copyarray);
		$GLOBALS['smarty']->display("list_wowdkp_dkpdis.htm");
	}
	//form:分配物品
	function onItemdisform() {
		$this->adm->_adminCheck(5);
		$iteminfo	= $this->adm->getItemNodis();
		$iteminfo	= is_array($iteminfo)?$iteminfo:array();

		if(is_array($iteminfo)) {
			$itemarray	= array();
			foreach($iteminfo as $key=>$val) {
				$itemarray[$val['id']]	= $val['name'];
			}
		}
		$_GET['id']	= isset($_GET['id'])?$_GET['id']:0;
		//var_dump($iteminfo);
		//var_dump($itemarray);
		$GLOBALS['smarty']->assign("itemarray",$itemarray);
		$GLOBALS['smarty']->assign("itemid",$_GET['id']);

		$GLOBALS['smarty']->assign("act","additemdis");
		$GLOBALS['smarty']->display("add_wowdkp_itemdis.htm");
	}
	//act:分配物品
	function onAdditemdis() {
		$this->adm->_adminCheck(5);
		if(empty($_POST['value'])) {
			$this->throwInfo($GLOBALS['lang']['pleaseinput'].$GLOBALS['lang']['item'].$GLOBALS['lang']['value'], $_SERVER["HTTP_REFERER"]);
		}
		if(empty($_POST['iid'])) {
			$this->throwInfo($GLOBALS['lang']['pleaselect'].$GLOBALS['lang']['item'], $_SERVER["HTTP_REFERER"]);
		}
		if(empty($_POST['keyword'])) {
			$this->throwInfo($GLOBALS['lang']['pleaseinput'].$GLOBALS['lang']['user'], $_SERVER["HTTP_REFERER"]);
		}
		//取出用户id
		$sql		= "select id from ".TABLEHEAD."_user where name = '$_POST[keyword]' limit 1";
		$userinfo	= $this->adm->excuteSql($sql);

		//取得物品所属的事件标识2006-5-17 及副本标识
		$sql	= "select a.eid as eid,b.cid as cid,a.name as iname from ".TABLEHEAD."_item as a left join ".TABLEHEAD."_event as b on a.eid=b.id where a.id='$_POST[iid]'";
		$ecinfo	= $this->adm->excuteSql($sql);
		$eid		= $ecinfo[0]['eid'];
		$cid		= $ecinfo[0]['cid'];
		$eventarray	= array("iid"=>$_POST['iid'],"eid"=>$eid,"uid"=>$userinfo[0]['id'],"value"=>$_POST['value'],'cid'=>$cid);
		$rs			= $this->adm->disitem($eventarray);
		if(!$rs) {
			$this->throwInfo($GLOBALS['lang']['dis'].$GLOBALS['lang']['item'].$GLOBALS['lang']['faild'], $_SERVER["HTTP_REFERER"]);
		}else {
			$this->syslog->writeLog($GLOBALS['oplog']['a_item_dis'],$ecinfo[0]['iname'],"to userid:".$_POST['uidselect'][0]);			$this->throwInfo($GLOBALS['lang']['dis'].$GLOBALS['lang']['item'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
		}
	}

	//list:dkp管理
	function onDkplist() {
		$this->adm->_adminCheck(5);
		//种族
		$cid	= (int)$_GET['cid'];
		$cid	= $cid?$cid:1;
		$classArray	= $this->adm->getInfoByTable('work');
		if(is_array($classArray)) {
			$classArr	= array();
			$classArr[]	= $GLOBALS['lang']['pleaselect'];
			foreach($classArray as $key=>$val) {
				$classArr[$val['id']]	= $val['name'];
			}
		}
		//副本
		$copyarrays	= $this->adm->getInfoByTable("copy");
		if(is_array($copyarrays)) {
			foreach($copyarrays as $key=>$val) {
				$copyarray[$val['id']]	= $val['name'];
			}
		}
		
		$sql	= "select a.uid as id
						,b.level as level
						,b.name as name 
						,ROUND(a.dkpvalue,$this->sd) as value
						, c.name as racename
						,d.name as workname from ".TABLEHEAD."_dkpvalues as a left join ".TABLEHEAD."_user as b on a.uid=b.id  
					left join ".TABLEHEAD."_race as c on b.raceid=c.id 
					left join ".TABLEHEAD."_work as d on b.workid=d.id where a.copyid='$cid' ";
		//echo($sql);
		//exit;
		$classid	= $_GET['obj']?$_GET['obj']:'0';
		if($classid) {
			$sql	.= " and b.workid='$classid' ";
		}
		$orderbyarray	= array("value","racename","workname","level","name","id");
		if($_GET['orderby'] and in_array($_GET['orderby'],$orderbyarray)) {
			$sql	.= " order by '$_GET[orderby]' desc";
		}
		$userdata	= $this->adm->getPerPageInfo($sql,$this->urlparam);
		$baseurl	= "index.php?module=admin&act=".$_GET['act']."&obj=".$_GET['obj']."&cid=".$cid;
		$GLOBALS['smarty']->assign("baseurl",$baseurl);
		$GLOBALS['smarty']->assign("classArr",$classArr);
		$GLOBALS['smarty']->assign("copyarray",$copyarray);
		$GLOBALS['smarty']->assign("getval",$_GET);
		$GLOBALS['smarty']->assign("userdata",$userdata[1]);
		$GLOBALS['smarty']->assign("pageText",$userdata[0]);
		$GLOBALS['smarty']->display("list_wowdkp.htm");
	}
	//form,act:dkp调节
	function onDkpAdjustmentsform() {
		$this->adm->_adminCheck(5);
		$searchinfo	= '';
		//如果是提交，则插入数据库
		if(!empty($_POST['adddkp'])) {
			//检查
			if(empty($_POST['uidselect'])) {
				$this->throwInfo($GLOBALS['lang']['note5'], $_SERVER["HTTP_REFERER"]);
			}
			if(empty($_POST['value']) or !preg_match("/^[\-0-9]+[\.]?[0-9]*$/",$_POST['value'])) {
				$this->throwInfo($GLOBALS['lang']['note6'], $_SERVER["HTTP_REFERER"]);
			}
			if(empty($_POST['eid'])) {
				$this->throwInfo($GLOBALS['lang']['note2'], $_SERVER["HTTP_REFERER"]);
			}
			//var_dump(preg_match("/^[\-0-9]+[0-9]$/",$_POST['value']));
			//写入
			$rs		= $this->adm->addDkpValue($_POST['eid'],$_POST['value'],$_POST['uidselect']);
			if(!$rs) {
				$this->throwInfo($GLOBALS['lang']['dkp'].$GLOBALS['lang']['value'].$GLOBALS['lang']['dis'].$GLOBALS['lang']['faild'], $_SERVER["HTTP_REFERER"]);
			}else {
				$this->syslog->writeLog($GLOBALS['oplog']['a_dkp_event'],$GLOBALS['lang']['event'].'ID:'.$_POST['eid'],"DKP:".$_POST['value'].$GLOBALS['lang']['user']."ID:".implode(',',$_POST['uidselect']));
				$this->throwInfo($GLOBALS['lang']['dkp'].$GLOBALS['lang']['value'].$GLOBALS['lang']['dis'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
			}
		}
		$grouparray	= $this->adm->getInfoByTable("group");
		$eventarray	= $this->adm->_getEventBysess();
		$usergroup	= array();
		$usergroup['all']	= $GLOBALS['lang']['all'];
		foreach($grouparray as $key=>$val) {
			$usergroup[$val['id']]	= $val['name'];
		}
		//var_dump($usergroup);
		//查询匹配uid的会员
		$_POST['ugid']	= isset($_POST['ugid'])?$_POST['ugid']:'';
		$userarray		= array();
		if(!empty($_POST['uid'])) {
			$userarray	= $this->adm->searchlikeuser($_POST['uid']);
			if(empty($userarray)) {
				$searchinfo	= $GLOBALS['lang']['note7'];
			}else {
				$searchinfo	= $GLOBALS['lang']['pleaselect'].$GLOBALS['lang']['user'];
			}
		}elseif(!empty($_POST['ugid']) and $_POST['ugid']!='all') {		//查找一类会员
			$userarray	= $this->adm->getGroupUser($_POST['ugid']);
		}elseif($_POST['ugid']=='all') {
			$userarray	= $this->adm->excuteSql("select a.*,b.wcolor from ".TABLEHEAD."_user as a left join ".TABLEHEAD."_work as b on a.workid=b.id order by a.workid desc ");
		}else {
			$searchinfo	= $GLOBALS['lang']['pleaselect'].$GLOBALS['lang']['user'];
		}
		$classarr	= $this->adm->getInfoByTable("work");
		foreach($classarr as $key=>$val) {
			$classarray[$val['id']]	= $val['name'];
		}
		$classarray['unk']			= 'unkown';
		

		$userdata	= array();
		$userarray	= is_array($userarray)?$userarray:array();
		foreach($userarray as $key=>$val) {
			if(empty($val['workid'])) {
				$classkey	= 'unk';
			}else {
				$classkey	= $val['workid'];
			}
			$userdata[$classkey][]	= $val;
		}
		$userinfo	= array();
		foreach($userdata as $key=>$val) {
			$userselect	= '<table border=0 width=100%>';
			$userselect	.= "<tr class=title><td colspan=5>".$classarray[$key]."</td></tr>";
			$i		= 1;
			$istr	= 1;
			foreach($val as $key1=>$val1) {
				if($istr) {
					$userselect	.= "<tr>";
					$istr		= 0;
				}
				$userselect	.= "<td width=20%><input name='uidselect[]' type='checkbox' value='".$val1['id']."' checked><font color=".$val1['wcolor'].">".$val1['name']."</font></td>";
				if($i%5==0) {
					$userselect	.= "</tr>";
					$istr		= 1;
				}
				$i++;
			}
			$userselect	.= "</table><br />";
			$userinfo[$key]	= $userselect;
		}
		

		$GLOBALS['smarty']->assign("eventarray",$eventarray);
		$GLOBALS['smarty']->assign("searchinfo",$searchinfo);
		$GLOBALS['smarty']->assign("userinfo",$userinfo);
		$GLOBALS['smarty']->assign("usergroup",$usergroup);
		$GLOBALS['smarty']->assign("post",$_POST);
		$GLOBALS['smarty']->assign("act","dkpAdjustmentsform");
		$GLOBALS['smarty']->display("add_dkpAdjustmentsform.htm");
	}

	//form:dkp事件调节
	function onDkpeventdisform() {
		$this->adm->_adminCheck(5);
		$eid			= isset($_GET['eid'])?(int)$_GET['eid']:0;
		$eventarray		= $this->adm->_getEventBysess();
		$GLOBALS['smarty']->assign("eventarray",$eventarray);
		$GLOBALS['smarty']->assign("act","dkpeventdis");
		//此事件调节值及用户情况$eventuser
		$eventuser		= array();
		if (!empty($eid)) {
			$eventuser	= $this->adm->excuteSql("select a.id,a.uid,a.value,a.distime,b.name,c.name as class,c.wcolor from ".TABLEHEAD."_itemdis as a left join ".TABLEHEAD."_user as b on a.uid=b.id left join ".TABLEHEAD."_work as c on b.workid=c.id where a.eid='$eid' and a.stat=1 order by b.workid,b.name ");
		}
		//var_dump($eventuser);
		$GLOBALS['smarty']->assign("usercount",count($eventuser));
		$GLOBALS['smarty']->assign("eid",$eid);
		$GLOBALS['smarty']->assign("eventuser",$eventuser);
		$GLOBALS['smarty']->display("add_dkpeventdisform.htm");
	}
	//form:新闻news
	function onAddnewsform() {
		$this->adm->_adminCheck(5);
		$act	= "addnews";
		$GLOBALS['smarty']->assign("act",$act);
		$GLOBALS['smarty']->display("add_wowdkp_news.htm");
	}

	//act:发布新闻addnews
	function onAddnews() {
		$this->adm->_adminCheck(5);
		$_POST['content']	= $_POST['message'];
		if(empty($_POST['title']) or empty($_POST['content'])) {
			$this->throwInfo($GLOBALS['lang']['title'].",".$GLOBALS['lang']['content'].$GLOBALS['lang']['nonull'], $_SERVER["HTTP_REFERER"]);
		}
		$_POST['posttime']	= date("Y-m-d H:i:s",time() + $this->timezone);
		$_POST['postuid']	= $_SESSION['wowdkp']['userid'];
		$inputarray	= array("posttime","content","title","postuid");
		foreach($_POST as $key=>$val) {
			if(in_array($key,$inputarray)) {
				$userarray[$key]	= $val;
			}
		}
		$rs		= $this->adm->addRecord("news",$userarray);
		if(!$rs) {
			$this->throwInfo($GLOBALS['lang']['add'].$GLOBALS['lang']['news'].$GLOBALS['lang']['faild'], $_SERVER["HTTP_REFERER"]);
		}else {
			$this->syslog->writeLog($GLOBALS['oplog']['a_news'],$GLOBALS['lang']['news'],$_POST['title']);
			$this->throwInfo($GLOBALS['lang']['add'].$GLOBALS['lang']['news'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
		}
	}

	//act:dkp事件调节
	function onDkpeventdis() {
		$this->adm->_adminCheck(5);
		//if(empty($_POST['value']) or !preg_match("/^[\-0-9]+[\.]?[0-9]*$/",$_POST['value'])) {
			//$this->throwInfo($GLOBALS['lang']['note6'], $_SERVER["HTTP_REFERER"]);
		//}
		if(empty($_POST['eid'])) {
			$this->throwInfo($GLOBALS['lang']['note2'], $_SERVER["HTTP_REFERER"]);
		}
		//按人员调节
		$_POST['udkp']	= is_array($_POST['udkp'])?$_POST['udkp']:array();
		foreach ($_POST['udkp'] as $key=>$val){ 
			$val	= (float)$val;
			$sql	= "update ".TABLEHEAD."_itemdis set value='$val' where id='$key'";
			$this->adm->excuteSql($sql);
		}
		//计算dkp
		foreach ($_POST['uid'] as $key=>$val){ 
			$rs	= $this->adm->_accountdkp($val);
		}
		if(!$rs) {
			$this->throwInfo($GLOBALS['lang']['dkp'].$GLOBALS['lang']['dis'].$GLOBALS['lang']['faild'], $_SERVER["HTTP_REFERER"]);
		}else {
			$this->syslog->writeLog($GLOBALS['oplog']['m_dkp_event'],$GLOBALS['lang']['event'].'ID:'.$_POST['eid']);
			$this->throwInfo($GLOBALS['lang']['dkp'].$GLOBALS['lang']['dis'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
		}	
	}


	//act:添加用户
	function onAdduser() {
		$this->adm->_adminCheck(5);
		if(empty($_POST['name'])) {
			$this->throwInfo($GLOBALS['lang']['user'].$GLOBALS['lang']['name'].$GLOBALS['lang']['nonull']);

		}
		$inputarray	= array("name","password","raceid","workid","level","notes","pic","groupid","stat");
		foreach($_POST as $key=>$val) {
			if(in_array($key,$inputarray)) {
				$userarray[$key]	= $val;
				if($key=='password') {
					$userarray[$key]	= md5($val);
				}
			}
		}
		$userarray['regtime']	= date("Y-m-d H:i:s",time() + $this->timezone);
		$rs		= $this->adm->addRecord("user",$userarray);
		if(!$rs) {
			$this->throwInfo($GLOBALS['lang']['add'].$GLOBALS['lang']['user'].$GLOBALS['lang']['faild'], $_SERVER["HTTP_REFERER"]);
		}else {

			$this->syslog->writeLog($GLOBALS['oplog']['a_member'],$GLOBALS['lang']['user'].':'.$_POST['name']);
			$this->throwInfo($GLOBALS['lang']['add'].$GLOBALS['lang']['user'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
		}
	}
	//act:配置管理
	function onRacemanage() {
		$this->adm->_adminCheck(5);
		$obj		= $_GET['obj'];
		$objarray	= array("race","group","eventtype","work","copy");
		
		if(!in_array($obj,$objarray)) {
			$this->throwInfo($GLOBALS['lang']['opertion'].$GLOBALS['lang']['faild'], $_SERVER["HTTP_REFERER"]);
		}
		if($_POST['SiteAction']=='add') {
			if(empty($_POST['name'])) {
				$this->throwInfo($GLOBALS['lang'][$obj].$GLOBALS['lang']['name'].$GLOBALS['lang']['nonull'], $_SERVER["HTTP_REFERER"]);
			}
			//log str
			if($obj == 'race') {
				$logkey	= 'a_race';
			}elseif($obj == 'group') {
				$logkey	= 'a_group';
			}elseif($obj == 'eventtype') {
				$logkey	= 'a_eventtype';
			}elseif($obj == 'work') {
				$logkey	= 'a_class';
			}elseif($obj == 'copy') {
				$logkey	= 'a_instance';
			}else {
				;
			}
			//添加
			$addarray	= array("name"=>$_POST['name'],"notes"=>$_POST['notes']);
			$rs			= $this->adm->addRecord($obj,$addarray);
			if($obj == 'copy'){
				//添加此管理员副本权限	2008-9-16
				$cidstr	= $_SESSION['wowdkp']['cid'].':'.$rs;
				$_SESSION['wowdkp']['cid']		= $cidstr;
				$this->adm->updateInfo("admuser", $_SESSION['wowdkp']['userid'], array('cid'=>$cidstr));
			}
			$this->syslog->writeLog($GLOBALS['oplog'][$logkey],$GLOBALS['oplog'][$logkey]);
		}elseif($_POST['SiteAction']=='delete') {
			if(empty($_POST['checkbox'])) {
				$this->throwInfo($GLOBALS['lang']['note8'], $_SERVER["HTTP_REFERER"]);
			}
			if($obj == 'race') {
				$logkey	= 'd_race';
			}elseif($obj == 'group') {
				$logkey	= 'd_group';
			}elseif($obj == 'eventtype') {
				$logkey	= 'd_eventtype';
			}elseif($obj == 'work') {
				$logkey	= 'd_class';
			}elseif($obj == 'copy') {
				$logkey	= 'd_instance';
				//删除的是默认副本时提示无法删除
				if(in_array($this->gconfig['defaultcopy'], $_POST['checkbox'])){
					$this->throwInfo($GLOBALS['lang']['note57'], $_SERVER["HTTP_REFERER"]);
					exit;
				}
			}else {
				;
			}
			//删除
			$this->syslog->writeLog($GLOBALS['oplog'][$logkey],$GLOBALS['oplog'][$logkey]);
			$rs			= $this->adm->deleteRecord($obj,$_POST['checkbox']);
		}else {
			//log str
			if($obj == 'race') {
				$logkey	= 'm_race';
			}elseif($obj == 'group') {
				$logkey	= 'm_group';
			}elseif($obj == 'eventtype') {
				$logkey	= 'm_eventtype';
			}elseif($obj == 'work') {
				$logkey	= 'm_class';
			}elseif($obj == 'copy') {
				$logkey	= 'm_instance';
			}else {
				;
			}
			//更新
			if(empty($_POST['checkbox'])) {
				$this->throwInfo($GLOBALS['lang']['note9'], $_SERVER["HTTP_REFERER"]);
			}
			//如果是职业，则更新色彩
			if($obj=='work') {
				$updatearray	= $_POST['wcolor'];
				$this->adm->updateWcolor($obj,$updatearray,$_POST['checkbox']);
			}
			//update
			$updatearray= $_POST['type'];
			$this->syslog->writeLog($GLOBALS['oplog'][$logkey],$GLOBALS['oplog'][$logkey]);
			$rs			= $this->adm->updateRecord($obj,$updatearray,$_POST['checkbox']);
		}
		$this->throwInfo($GLOBALS['lang']['opertion'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
	}
	//act:admusermanage 管理员管理
	function onAdmusermanage() {
		$this->adm->_adminCheck(10);
		$obj		= "admuser";
		if($_POST['SiteAction']=='add') {
			if(empty($_POST['username']) or empty($_POST['password'])) {
				$this->throwInfo($GLOBALS['lang']['manager'].$GLOBALS['lang']['name'].",".$GLOBALS['lang']['password'].$GLOBALS['lang']['nonull'], $_SERVER["HTTP_REFERER"]);
			}
			//如果没有指定副本.则不允许添加
			if(empty($_POST['cid'])) {
				$this->throwInfo($GLOBALS['lang']['pleaselect'].$GLOBALS['lang']['manage'].$GLOBALS['lang']['copy'], $_SERVER["HTTP_REFERER"]);
			}
			$_POST['cid']	= is_array($_POST['cid'])?$_POST['cid']:array();
			$cidstr	= implode(":",$_POST['cid']);
			//添加
			$addarray	= array("username"=>$_POST['username'],"password"=>md5($_POST['password']),"power"=>$_POST['power'],"notes"=>$_POST['notes'],'cid'=>$cidstr);
			$this->syslog->writeLog($GLOBALS['oplog']['a_manager'],$_POST['username']);
			$rs			= $this->adm->addRecord($obj,$addarray);
		}elseif($_POST['SiteAction']=='delete') {
			//只有一个管理员时不可删除
			$admuser	= $this->adm->getInfoByTable($obj);
			if(count($admuser)<=1) {
				$this->throwInfo($GLOBALS['lang']['note15'], $_SERVER["HTTP_REFERER"]);
			}
			if(empty($_POST['checkbox'])) {
				$this->throwInfo($GLOBALS['lang']['note13'], $_SERVER["HTTP_REFERER"]);
			}
			//删除
			$this->syslog->writeLog($GLOBALS['oplog']['d_manager'],$_POST['username']);
			$rs			= $this->adm->deleteRecord($obj,$_POST['checkbox']);
		}elseif($_POST['SiteAction']=='update') {
			//var_dump($_POST);
			$admid	= (int)$_POST['id'];
			$cidstr	= implode(':',$_POST['cid']);
			$_SESSION['wowdkp']['cid']	= $cidstr;
			$this->syslog->writeLog($GLOBALS['oplog']['m_manager'],$_POST['id']);
			$this->adm->updateInfo('admuser',$admid,array('cid'=>$cidstr));
		}else {
			;
		}	$this->throwInfo($GLOBALS['lang']['opertion'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
	}
	//act:添加事件
	function onAddevent() {
		$this->adm->_adminCheck(5);
		if(empty($_POST['name'])) {
			$this->throwInfo($GLOBALS['lang']['pleaseinput'].$GLOBALS['lang']['event'].$GLOBALS['lang']['name'], $_SERVER["HTTP_REFERER"]);
		}
		$rs			= $this->adm->addEvent("event",$_POST);
		if(!$rs) {
			$this->throwInfo($GLOBALS['lang']['opertion'].$GLOBALS['lang']['faild'], $_SERVER["HTTP_REFERER"]);
		}else {
			$this->syslog->writeLog($GLOBALS['oplog']['a_event'],$_POST['name']);
			$this->throwInfo($GLOBALS['lang']['opertion'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
		}
	}

	//act:删除
	function onDelete() {
		$this->adm->_adminCheck(5);
		$array	= array($_GET['id']);
		$tablename	= $_GET['obj'];
		$rs			= $this->adm->deleteRecord($tablename,$array);
		if(!$rs) {
			$this->throwInfo($GLOBALS['lang']['delete'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
		}else {
			//log str
			if($obj == 'dkpvalues') {
				$logkey	= 'd_dkp_member';
			}elseif($obj == 'event') {
				$logkey	= 'd_event';
			}elseif($obj == 'news') {
				$logkey	= 'd_news';
			}elseif($obj == 'item') {
				$logkey	= 'd_item';
			}elseif($obj == 'itemdis') {
				$logkey	= 'd_item_dis';
			}elseif($obj == 'raidcfg') {
				$logkey	= 'd_actions';
			}elseif($obj == 'user') {
				$logkey	= 'd_user';
			}else {
				;
			}
			$this->syslog->writeLog($GLOBALS['oplog'][$logkey],$GLOBALS['oplog'][$logkey],"ID:".$_GET['id']);
			$this->throwInfo($GLOBALS['lang']['delete'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
		}
	}
	//批量post删除
	function onFulldelete() {
		$this->adm->_adminCheck(5);
		if(!isset($_POST['uidselect'])){
			return false;
		}
		$obj	= $_POST['obj'];	
		$array	= $_POST['uidselect'];
		$tablename	= $obj;
		if($obj == 'dkpvalues') {
			$logkey	= 'd_dkp_member';
		}elseif($obj == 'event') {
			$logkey	= 'd_event';
		}elseif($obj == 'news') {
			$logkey	= 'd_news';
		}elseif($obj == 'item') {
			$logkey	= 'd_item';
		}elseif($obj == 'itemdis') {
			$logkey	= 'd_item_dis';
		}elseif($obj == 'raidcfg') {
			$logkey	= 'd_actions';
		}elseif($obj == 'user') {
			$logkey	= 'd_user';
		}elseif($obj == 'raidcfg') {
			$logkey	= 'm_actions_cfg';
		}else {
			;
		}
		if(!is_array($array)) {
			header("Location: ".$_SERVER["HTTP_REFERER"]);
			exit;
		}
		$destr		= implode(',',$array);
		$rs			= $this->adm->deleteRecord($tablename,$array);
		if(!$rs) {
			$this->throwInfo($GLOBALS['lang']['delete'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
		}else {
			$this->syslog->writeLog($GLOBALS['oplog'][$logkey],$destr,"more");
			$this->throwInfo($GLOBALS['lang']['delete'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
		}
	}

	//form:更新
	function onModify() {
		$id		= $_GET['id'];
		$obj	= $_GET['obj'];
		$act	= "update";
		$updatearray	= array();
		switch($obj) {
			case "user":
				$racearray	= $this->adm->getInfoByTable("race");
				foreach($racearray as $key=>$val) {
					$raceinfo[$val['id']]	= $val['name'];
				}
				$workarray	= $this->adm->getInfoByTable("work");
				foreach($workarray as $key=>$val) {
					$workinfo[$val['id']]	= $val['name'];
				}
				$grouparray	= $this->adm->getInfoByTable("group");
				foreach($grouparray as $key=>$val) {
					$groupinfo[$val['id']]	= $val['name'];
				}
				$GLOBALS['smarty']->assign("jumpurl",$_SERVER['HTTP_REFERER']);
				$GLOBALS['smarty']->assign("raceinfo",$raceinfo);
				$GLOBALS['smarty']->assign("workinfo",$workinfo);
				$GLOBALS['smarty']->assign("groupinfo",$groupinfo);
				$tpl	= "add_wowdkp_user.htm";
				break;
			case "item":
				$eventarray		= $this->adm->_getEventBysess();
				$GLOBALS['smarty']->assign("eventarray",$eventarray);
				$GLOBALS['smarty']->assign("showUser",'no');
				$tpl	= "add_wowdkp_item.htm";
				break;
			case "event":
				$eventtypearray	= $this->adm->getInfoByTable("eventtype");
				if(is_array($eventtypearray)) {
					foreach($eventtypearray as $key=>$val) {
						$eventtype[$val['id']]	= $val['name'];
					}
				}

				//副本
				$copyarray	= $this->adm->_getcopy();
				$GLOBALS['smarty']->assign("userinfo",$userinfo);
				$GLOBALS['smarty']->assign("copyarray",$copyarray);
				$GLOBALS['smarty']->assign("eventtype",$eventtype);
				$tpl	= "modify_wowdkp_event.htm";
				break;
			case "dkpdis":
				$iteminfo	= $this->adm->getInfoByTable("item");
				if(is_array($iteminfo)) {
					foreach($iteminfo as $key=>$val) {
						$itemarray[$val['id']]	= $val['name'];
					}
				}
				$cstr		= implode(',',$this->copyArr);
				$cstr		= $cstr?$cstr:0;
				$eventinfo	= $this->adm->excuteSql("select b.name from ".TABLEHEAD."_itemdis as a  left join ".TABLEHEAD."_event as b on a.eid=b.id where a.id='$id' and b.cid in($cstr)");
				if(empty($eventinfo)) {
					$this->throwInfo($GLOBALS['lang']['nopower'], $_SERVER["HTTP_REFERER"]);
				}
				$userinfo	= $this->adm->getInfoByTable("user",'',"workid");
				foreach($userinfo as $key=>$val) {
					$userarray[$val['id']]	= '['.$val['workid'].']'.$val['name'];
				}
				$GLOBALS['smarty']->assign("eventinfo",$eventinfo[0]);
				$GLOBALS['smarty']->assign("itemarray",$itemarray);
				$GLOBALS['smarty']->assign("userarray",$userarray);
				$tpl	= "modify_wowdkp_itemdis.htm";
				$obj	= "itemdis";
				break;
			case "news":
				//var_dump($obj);
				$tpl	= "add_wowdkp_news.htm";
				break;
			case 'raidcfg':
				$this->adm->_adminCheck(5);
				$info		= $this->adm->getInfoByTable("raidcfg");
				$classinfo	= explode('|',$info[0]['classreq']);
				$calsscfg	= array();
				if(is_array($classinfo)) {
					foreach($classinfo as $key=>$val) {
						$tmpclass	= explode(':',$val);
						$calsscfg[$tmpclass[0]]	= $tmpclass[1];
					}
				}
				$resistance	= explode(':',$info[0]['resistance']);
				//职业
				$classinfo	= $this->adm->getInfoByTable("work");
				foreach($classinfo as $key=>$val) {
					$classarr[$val['id']]	= $val['name'];
				}
				$GLOBALS['smarty']->assign("jumpurl", $_SERVER["HTTP_REFERER"]);
				$GLOBALS['smarty']->assign("classarr",$classarr);
				$GLOBALS['smarty']->assign("calsscfg",$calsscfg);
				$GLOBALS['smarty']->assign("resistance",$resistance);
				$tpl	= "modify_raidcfg.htm";
				$obj	= "raidcfg";
				break;
			case 'raidlog':
				$configarr	= $this->adm->getInfoByTable("raidcfg");
				$configure	= array();
				$configure[]= $GLOBALS['lang']['pleaselect'];
				if(is_array($configarr)) {
					foreach($configarr as $key=>$val) {
						$configure[$val['id']]	= $val['name'];
					}
				}
				$GLOBALS['smarty']->assign("jumpurl", $_SERVER["HTTP_REFERER"]);
				$tpl	= "modify_raidlog.htm";
				$obj	= "raidlog";
				break;			
			default :
				$this->throwInfo($GLOBALS['lang']['note10'], $_SERVER["HTTP_REFERER"]);
		}
		$info	= $this->adm->getInfoByTable($obj,$id);
		//var_dump($info);
		$configure	= isset($configure)?$configure:array();
		$GLOBALS['smarty']->assign("userdata",$info[0]);
		$GLOBALS['smarty']->assign("configure",$configure);
		$GLOBALS['smarty']->assign("act",$act);
		$GLOBALS['smarty']->assign("obj",$obj);
		$GLOBALS['smarty']->display($tpl);
	}
	//act:更新修改
	function onUpdate() {
		$obj	= $_POST['obj'];
		$id		= isset($_POST['id'])?$_POST['id']:0;
		$jumpurl	= $_SERVER['HTTP_REFERER'];
		$des	= '';
		//var_dump($_POST);jumpurl
		switch($obj) {
			case "user":
				$logkey		= 'm_member';
				if(empty($_POST['password'])) {
					$inputarray	= array("name","raceid","workid","level","notes","pic","groupid","stat");
				}else {
					$inputarray	= array("name","password","raceid","workid","level","notes","pic","groupid","stat");
				}
				if(!empty($_POST['jumpurl'])) $jumpurl	= $_POST['jumpurl'];
				break;
			case "item":
				$logkey			= 'm_item';
				$inputarray	= array("eid","name","notes");
				break;
			case "event":
				$logkey			= 'm_event';
				$inputarray	= array('name');
				$uparray	= array("name"=>$_POST['name'],"notes"=>$_POST['notes'],"etid"=>$_POST['etid'],"cid"=>$_POST['cid'],"raidtime"=>$_POST['raidtime']);
				$this->adm->updateInfo($obj,$id,$uparray);
				//如果管理员修改了事件所在副本:更改调节记录dis->重新计算会员dkp
				if($_POST['ocid'] != $_POST['cid']) {
					$newcid	= (int)$_POST['cid'];
					//更新调节记录此事件副本
					$this->adm->excuteSql("update ".TABLEHEAD."_itemdis set cid='$newcid' where eid='$id'");
					//找出此事件调节关联的副本和会员
					$eventUser	= $this->adm->excuteSql("select uid from ".TABLEHEAD."_itemdis where eid='$id'");
					$eventUser	= is_array($eventUser)?$eventUser:array();
					foreach ($eventUser as $key=>$val){
						$this->adm->_accountdkp($val['uid']);
					}
				}
				break;
			case "itemdis":
				$logkey			= 'm_dkp_member';
				$inputarray	= array("iid","uid","value","distime");
				$des			= "dkp:".$_POST['value'];
				break;
			case "news":
				$logkey			= 'm_news';
				$_POST['content']	= $_POST['message'];
				$inputarray	= array("title","posttime","content");
				break;
			case "admuser":
				$logkey			= 'm_manager';
				//检查原密码是否正确.现密码是否合要求
				$userinfo	= $this->adm->getInfoByTable("admuser",$_SESSION['wowdkp']['userid']);
				if($userinfo[0]['password']!=md5($_POST['current'])) {
					$this->throwInfo($GLOBALS['lang']['note14'], $_SERVER["HTTP_REFERER"]);
				}
				if(empty($_POST['password'])) {
					$this->throwInfo($GLOBALS['lang']['password'].$GLOBALS['lang']['nonull'], $_SERVER["HTTP_REFERER"]);
				}
				if($_POST['password']!=$_POST['repasswd']) {
					$this->throwInfo($GLOBALS['lang']['repasswd'].$GLOBALS['lang']['faild'], $_SERVER["HTTP_REFERER"]);
				}
				$inputarray	= array("password");
				break;
			case 'config':
				$logkey			= 'm_sys_cfg';
				//时间参数检查
				if(!preg_match("/^[1-9]$/",$_POST['acttime'])) {
					$this->throwInfo($GLOBALS['lang']['acttime'].$GLOBALS['lang']['note31'], $_SERVER["HTTP_REFERER"]);
				}
				//公会名称检查
				if(empty($_POST['guildname'])) {
					$this->throwInfo($GLOBALS['lang']['guild'].$GLOBALS['lang']['name'].$GLOBALS['lang']['nonull'], $_SERVER["HTTP_REFERER"]);
				}
				$inputarray	= array("guildname","acttime","islogin","sdecimal","defaultcopy","itemquality","timezone","dstyle","location","langtype","realm","isarmory");
				foreach($inputarray as $key=>$val) {
					$sql	= "update ".TABLEHEAD."_config set value='$_POST[$val]' where vname='$val'";
					$rs		= $GLOBALS['adodb']->execute($sql);
				}

				$deleteDir	= array(CHINO_CACHEBASE . '/admin/compile'
									,CHINO_CACHEBASE . '/index/compile'
									,CHINO_CACHEBASE . '/login/compile');
				foreach($deleteDir as $key=>$val) 
				{
					if ($handle = @opendir($val)) {
						while (false !== ($file = @readdir($handle))) {
							if($file != '.' and $file != '..')
							{
								@unlink($val.'/'.$file);
							}
						}
					}
					@closedir($handle);
				}

				if($rs) {
					$this->throwInfo($GLOBALS['lang']['modify'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
				}else {
					$this->throwInfo($GLOBALS['lang']['modify'].$GLOBALS['lang']['faild'], $_SERVER["HTTP_REFERER"]);
				}
				break;
			case 'raidcfg':
				$logkey			= 'm_actions_cfg';
				$this->adm->_adminCheck(5);
				//classreq:2|classreq:3
				$classcfg	= array();
				if(is_array($_POST['class'])) {
					foreach($_POST['class'] as $key=>$val) {
						$classcfg[]	= (int)$key.':'.(int)$val;
					}
				}
				$classreq	= implode('|',$classcfg);
				$resistance	= (int)$_POST['arcane'].':'.(int)$_POST['fire'].':'.(int)$_POST['frost'].':'.(int)$_POST['nature'].':'.(int)$_POST['shadow'];
				$_POST['autoqueue']	= (int)$_POST['autoqueue'];
				$insarr	= array('name'=>$_POST['name'],
								'maxnum'=>$_POST['raidmax'],
								'classreq'=>$classreq,
								'resistance'=>$resistance,
								'minlevel'=>$_POST['minlevel'],
								'maxlevel'=>$_POST['maxlevel'],
								'autoqueue'=>$_POST['autoqueue']);
				$insid	= $this->adm->updateInfo('raidcfg',$id,$insarr);
				if(!empty($_POST['jumpurl'])) $jumpurl	= $_POST['jumpurl'];
				$inputarray	= array('autoqueue');
				break;
			case 'raidlog':
				$logkey			= 'm_actions';
				if(!empty($_POST['jumpurl'])) $jumpurl	= $_POST['jumpurl'];
				$inputarray	= array('name','rid','invitetime','starttime','circaendtime','freezelimit','notes');
				break;			
			default :
				$this->throwInfo($GLOBALS['lang']['note10'], $_SERVER["HTTP_REFERER"]);
		}
		
		foreach($_POST as $key=>$val) {
			if(in_array($key,$inputarray)) {
				$updatearray[$key]	= $val;
				if($key=='password') {
					$updatearray[$key]	= md5($val);
				}
			}
		}
		$rs		= $this->adm->updateInfo($obj,$id,$updatearray,$_POST['lostuid']);
		$this->syslog->writeLog($GLOBALS['oplog'][$logkey],$id,$des);
		if(!$rs) {
			$this->throwInfo($GLOBALS['lang']['update'].$GLOBALS['lang']['faild'], $jumpurl);
		}else {
			$this->throwInfo($GLOBALS['lang']['update'].$GLOBALS['lang']['succeed'], $jumpurl);
		}
	}

	//act:getupdate get方式提交的修改
	function onUpdateByGet() {
		$jumpurl	= $_SERVER['HTTP_REFERER'];
		$id		= (int)$_GET['id'];
		$obj	= $_GET['obj'];
		switch($obj) {
			case 'signup':
				$logkey			= "m_signup";
				$updatearray	= array('stat'=>'1');
				break;
			case 'endraidlog':
				$logkey			= "m_endraidlog";
				$obj	= 'raidlog';
				$updatearray	= array('stat'=>'1');
				break;			
			default:
				header("Location: ".$jumpurl);
				exit;
		}
		$rs		= $this->adm->updateInfo($obj,$id,$updatearray);
		if(!$rs) {
			$this->throwInfo($GLOBALS['lang']['update'].$GLOBALS['lang']['faild'], $jumpurl);
		}else {
			$this->syslog->writeLog($GLOBALS['oplog'][$logkey],$id);
			$this->throwInfo($GLOBALS['lang']['update'].$GLOBALS['lang']['succeed'], $jumpurl);
		}
	}

	//act:置用户为停权状态
	function onUserstop() {
		$this->adm->_adminCheck(5);
		$id		= (int)$_GET['id'];
		$stat	= $_GET['stat']?"1":"0";
		$updatearray	= array("stat"=>$stat);
		
		$this->adm->_accountdkp($id);
		//如果是启动.则初始化dkp状态
		if($stat=='1') {
			//找到当前的副本.初始化用户的dkp
			$copyarr	= $this->adm->getInfoByTable("copy");
			foreach($copyarr as $key=>$val) {
				//如果没有用户则插入
				$sql	= "select count(*) from ".TABLEHEAD."_dkpvalues where uid='$id' and copyid='$val[id]'";
				$isuser	= $this->adm->excuteSql($sql);
				if($isuser[0][0]=='0') {
					$sql	= "insert into ".TABLEHEAD."_dkpvalues(dkpvalue,uid,copyid) values('0','$id','$val[id]')";
					$this->adm->excuteSql($sql);
				}				
			}
		}
		$rs	= $this->adm->updateInfo("user",$id,$updatearray);
		if(!$rs) {
			$this->throwInfo($GLOBALS['lang']['opertion'].$GLOBALS['lang']['faild'], $_SERVER["HTTP_REFERER"]);
		}else {
			$this->syslog->writeLog($GLOBALS['oplog']['m_user_stat'],$id);
			$this->throwInfo($GLOBALS['lang']['opertion'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
		}
	}
	//adt:logout
	function onLogout() {
		unset($_SESSION['wowdkp']);	
		$this->throwInfo($GLOBALS['lang']['logout'].$GLOBALS['lang']['succeed'], "index.php");
	}

	//show:raid log
	function onShowRaidLogForm() {
		$_COOKIE['wow_cfg_ctmode']	= isset($_COOKIE['wow_cfg_ctmode'])?$_COOKIE['wow_cfg_ctmode']:1;
		$this->adm->_adminCheck(5);
		$eventtypearray	= $this->adm->getInfoByTable("eventtype");
		if(is_array($eventtypearray)) {
			foreach($eventtypearray as $key=>$val) {
				$eventtype[$val['id']]	= $val['name'];
			}
		}
		//副本
		$copyarray	= $this->adm->_getcopy();
        //group 
        $groupArray = $this->adm->_getUserGroup(true);
		$defRaidTime	= date("Y-m-d",time() + $this->timezone);
		$mode		= array('1'=>$GLOBALS['lang']['note40'],'2'=>$GLOBALS['lang']['note41'],'3'=>$GLOBALS['lang']['note56']);
		$GLOBALS['smarty']->assign("copyarray",$copyarray);
        $GLOBALS['smarty']->assign("groupArray",$groupArray);
		$GLOBALS['smarty']->assign("eventtype",$eventtype);
		$GLOBALS['smarty']->assign("defRaidTime",$defRaidTime);
		$GLOBALS['smarty']->assign("mode",$mode);
		$GLOBALS['smarty']->assign("cookiectmode",$_COOKIE['wow_cfg_ctmode']);
		$GLOBALS['smarty']->display("ShowRaidLogForm.htm");
	}

	//下载会员信息
	function onDownloadUserInfo() {
		$outstr		= '';
		$userinfo	= $this->adm->getInfoByTable("user");
		foreach($userinfo as $key=>$val) {
			$j	= count($val);
			for($i=0; $i<$j; $i++) {
				if($i<($j-1)) {
					$outstr	.= $val[$i].",";
				}else {
					$outstr	.= $val[$i];
				}
			}
			$outstr	.= "\n";
		}
		header('Cache-control: private');
		header('Content-Description: File Transfer');
		header('Content-Type: application/force-download');
		Header("Accept-Ranges: bytes");
		Header("Accept-Length: ".strlen($outstr));
		Header("Content-Disposition: attachment; filename=members.csv");
		echo $outstr;
		//var_dump($userinfo);
	}

	//modify the admin's password
	function onUppasswd() {
		//var_dump($_SESSION);
		$GLOBALS['smarty']->assign('uid',$_SESSION['wowdkp']['userid']);
		$GLOBALS['smarty']->display("form_uppasswd.htm");
	}
	
	//分配管理员权限
	function onChangepower() {
		//副本
		$copyarrays	= $this->adm->getInfoByTable("copy");
		if(is_array($copyarrays)) {
			foreach($copyarrays as $key=>$val) {
				$copyarray[$val['id']]	= $val['name'];
			}
		}
		$adminid	= (int)$_GET['id'];
		$admininfo	= $this->adm->getInfoByTable("admuser","$adminid");
		$upower		= @explode(':',$admininfo[0]['cid']);
		$GLOBALS['smarty']->assign("upower",$upower);
		$GLOBALS['smarty']->assign("admininfo",$admininfo[0]);
		$GLOBALS['smarty']->assign("copyarray",$copyarray);
		$GLOBALS['smarty']->display("form_changepower.htm");
	}
	//view all copy configure
	function onViewall() {
		if(!empty($_POST['copyset'])) {
			if(empty($_POST['checkbox'])) {
				$this->throwInfo($GLOBALS['lang']['note35'],$_SERVER["HTTP_REFERER"]);
			}
			foreach($_POST['checkbox'] as $key=>$val) {
				$_POST['checkbox'][$key]	= (int)trim($val);
			}
			$copystr	= implode(',',$_POST['checkbox']);
			
			$sql	= "update ".TABLEHEAD."_config set value='$copystr' where vname='showcopy'";
			$rs		= $GLOBALS['adodb']->execute($sql);
			
			if(!$rs) {
				$this->throwInfo($GLOBALS['lang']['opertion'].$GLOBALS['lang']['faild'],$_SERVER["HTTP_REFERER"]);
			}else {
				$this->throwInfo($GLOBALS['lang']['opertion'].$GLOBALS['lang']['succeed'],$_SERVER["HTTP_REFERER"]);
			}
		}
		$copyarr	= $this->adm->getInfoByTable('copy');
		$showcopy	= explode(',',$this->gconfig['showcopy']);
		if(is_array($copyarr)) {
			foreach($copyarr as $key=>$val) {
				if(in_array($val['id'],$showcopy)) {
					$copyarr[$key]['vstat']	= 1;
				}else {
					$copyarr[$key]['vstat']	= 0;
				}
			}
		}
		$GLOBALS['smarty']->assign("userdata",$copyarr);
		$GLOBALS['smarty']->display("manage_viewall.htm");
	}
	//基本配置管理 $islogin
	function onBaseinfo() {
		$GLOBALS['smarty']->assign("udata",$this->gconfig);
		$islogin	= array('0'=>$GLOBALS['lang']['stop'],'1'=>$GLOBALS['lang']['start']);
		$sdecimal	= array('0'=>'0','1'=>'1','2'=>'2');
		//defaultcopy
		$GLOBALS['smarty']->assign("islogin",$islogin);
		$GLOBALS['smarty']->assign("sdecimal",$sdecimal);
		$GLOBALS['smarty']->assign("defaultcopy",$this->adm->_getCopy());
		//itemquality
		$GLOBALS['smarty']->assign("itemquality",$GLOBALS['itemquality']);
		//timeline
        if(function_exists("iconv")) {
        	$cnTimeZone = iconv("GBK","UTF-8",'(GMT+8:00) 北京, 珀斯, 新加坡, 香港, 乌鲁木齐, 台北');
        }elseif(function_exists("mb_convert_encoding")) {
        	$cnTimeZone = mb_convert_encoding('(GMT+8:00) 北京, 珀斯, 新加坡, 香港, 乌鲁木齐, 台北',"UTF-8","GBK");
        }else {
        	$cnTimeZone = "(GMT+8:00) BeiJing, HongKong, TaiBei";
        }
        
		$timezoneArray	= array('-12'=>'(GMT-12:00) Eniwetok, Kwajalein',
							'-11'=>'(GMT-11:00) Midway Island, Samoa',
							'-10'=>'(GMT-10:00) Hawaii',
							'-9'=>'(GMT-9:00) Alaska',
							'-8'=>'(GMT-8:00) Pacific Time (US &amp; Canada)',
							'-7'=>'(GMT-7:00) Mountain Time (US &amp; Canada)',
							'-6'=>'(GMT-6:00) Central Time (US &amp; Canada), Mexico City',
							'-5'=>'(GMT-5:00) Eastern Time (US &amp; Canada), Bogota, Lima, Quito',
							'-4'=>'(GMT-4:00) Atlantic Time (Canada), Caracas, La Paz',
							'-3.5'=>'(GMT-3:30) Newfoundland',
							'-3'=>'(GMT-3:00) Brasilia, Buenos Aires, Georgetown',
							'-2'=>'(GMT-2:00) Mid-Atlantic',
							'-1'=>'(GMT-1:00) Azores, Cape Verde Islands',
							'0'=>'(GMT) Greenwich Mean Time, London, Dublin, Lisbon, Casablanca, Monrovia',
							'1'=>'(GMT+1:00) Amsterdam, Berlin, Rome, Copenhagen, Brussels, Madrid, Paris',
							'2'=>'(GMT+2:00) Athens, Istanbul, Minsk, Helsinki, Jerusalem, South Africa',
							'3'=>'(GMT+3:00) Baghdad, Kuwait, Riyadh, Moscow, St. Petersburg',
							'3.5'=>'(GMT+3:30) Tehran',
							'4'=>'(GMT+4:00) Abu Dhabi, Muscat, Baku, Tbilisi',
							'4.5'=>'(GMT+4:30) Kabul',
							'5'=>'(GMT+5:00) Ekaterinburg, Islamabad, Karachi, Tashkent',
							'5.5'=>'(GMT+5:30) Bombay, Calcutta, Madras, New Delhi',
							'6'=>'(GMT+6:00) Almaty, Dhaka, Colombo',
							'7'=>'(GMT+7:00) Bangkok, Hanoi, Jakarta',
							'8'=>$cnTimeZone,
							'9'=>'(GMT+9:00) Tokyo, Seoul, Osaka, Sapporo, Yakutsk',
							'9.5'=>'(GMT+9:30) Adelaide, Darwin',
							'10'=>'(GMT+10:00) Brisbane, Canberra, Melbourne, Sydney, Guam,Vlasdiostok',
							'11'=>'(GMT+11:00) Magadan, Solomon Islands, New Caledonia',
							'12'=>'(GMT+12:00) Auckland, Wellington, Fiji, Kamchatka, Marshall Island');
		$GLOBALS['smarty']->assign("timezoneArray",$timezoneArray);
		include_once CHINO_PHPPATH.'/lang/lang_public.php';
		$GLOBALS['smarty']->assign("langTypeArray",$langTypeArray);
		$GLOBALS['smarty']->assign("locationArray",$locationArray);
		//主题$stylearray
		$stylearray	= array();
		if ($handle = opendir(CHINO_WWWROOT.'/theme')) {
			/* 这是正确地遍历目录方法 */
			while (false !== ($file = readdir($handle))) {
				if($file!='.' and $file!='..') {
					$stylearray[$file]	= $file;
				}
			}
		closedir($handle);
		}
		$GLOBALS['smarty']->assign("stylearray",$stylearray);
		$GLOBALS['smarty']->display("manage_baseinfo.htm");
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


?>