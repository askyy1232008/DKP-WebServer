<?php
/**
+-----------------------------------------------------------------------+
* @autor 张涛 <tonera@gmail.com>;
* @since 2005-12-9
* @version $Id: mod_Index.php.php,v 1.3.7 tonera$
* @description	首页
* @function:news | Standings | raids | event | items | items dis 
* @last update 2008-8-24
+-----------------------------------------------------------------------+
*/
define('PAGE_PPL_DEF', 10); 
$GLOBALS['smarty']	= Chino::getObject('smarty');
$GLOBALS['smarty']->left_delimiter	= "{|";
$GLOBALS['smarty']->right_delimiter	= "|}";
$GLOBALS['smarty']->caching = false;
//$GLOBALS['smarty']->debug = true;
require_once CHINO_MODPATH.'/config/config.inc.php';

class Cpage extends Base{
	var $alias		= 'act';
	var $dbt		= '';
	var $urlparam	= '';
	var $pageini	= '';
	var $gconfig	= array();
	var $sd			= 2;
	var $timezone	= 0;

    function onDefault() {
        $this->onNews();
    }

    function onHello() {
        echo "hello tplbuider";
    }
	function onLoad() {
        include_once(CURRENTPATH . "/class_Index.php");
		$this->dbt	= new wow;
		$gconfigarr	= $this->dbt->getInfoByTable('config');
		if(is_array($gconfigarr)) {
			foreach($gconfigarr as $key=>$val) {
				$this->gconfig[trim($val['vname'])]	= trim($val['value']);
			}
		}
		$this->timezone	= (float)$this->gconfig['timezone'] * 3600 - date("Z");
		$GLOBALS['smarty']->template_dir	= CHINO_WWWROOT.'/theme/'.$this->gconfig['dstyle'].'/index';

		//保留小数
		$this->sd	= (int)$this->gconfig['sdecimal'];
		$this->sd	= $this->sd>=0||$this->sd<=2?$this->sd:2;
		unset($gconfigarr);
		$GLOBALS['lang']['sitetitle']	= $this->gconfig['guildname'];
		$GLOBALS['smarty']->assign("lang",$GLOBALS['lang']);
		$obj		= isset($_GET['obj'])?$_GET['obj']:'0';
		$act		= isset($_GET['act'])?$_GET['act']:"news";
		$orderby	= isset($_GET['orderby'])?$_GET['orderby']:'';
		$_GET['id']	= isset($_GET['id'])?$_GET['id']:'';
		$_GET['cid']= isset($_GET['cid'])?$_GET['cid']:'';
		$this->urlparam	= "module=index&act=".$act."&obj=".$obj."&orderby=".$orderby."&id=".$_GET['id']."&cid=".$_GET['cid'];
		$pagecfgfile	= CHINO_MODPATH.'/config/page.inc';
		$pagecfg		= @parse_ini_file($pagecfgfile, true);
		if(!$pagecfg) {
			$this->pageini	= array('i_news'=>30,'i_user'=>30,'i_raids'=>30,'i_event'=>30,'i_item'=>30,'i_dis'=>30,'m_row'=>30);
		}else {
			$this->pageini	= $pagecfg['page'];
		}
		//language set
		include_once(CHINO_PHPPATH . "/lang/lang_public.php");
		$langArr	= array();
		foreach($langTypeArray as $key=>$val) {
			$langArr[$key]	= $val;
		}
		$GLOBALS['smarty']->assign("theme",$this->gconfig['dstyle']);
		$GLOBALS['smarty']->assign("langArr",$langArr);
		$GLOBALS['smarty']->assign("islogin",$this->gconfig['islogin']);
		$GLOBALS['smarty']->assign("pagetitle",$this->gconfig['guildname']);
		$GLOBALS['smarty']->assign("lng",$this->gconfig['langtype']);
		$GLOBALS['smarty']->assign("lo",$this->gconfig['location']);
		
    }
	//新闻列表
	function onNews() {
		//2006-8-19
		define('PAGE_RPP_DEF', (int)$this->pageini['i_news']);
		$sql	= "select * from ".TABLEHEAD."_news order by posttime desc";
		$userdata	= $this->dbt->getPerPageInfo($sql,$this->urlparam);
		include CHINO_LIBPATH.'/class_Ubb.php';
		$eq		=  new Ubb();
		$eq->url= true;
		$imgJs	= $eq->getImageOpener();
		foreach($userdata[1] as $key=>$val) {
			$eq->setStr(stripslashes($val['content']));
			$val['content']	= $eq->ubbEncode();
			$userdata[1][$key]['content']	= nl2br($val['content']);
		}
		
		$GLOBALS['smarty']->assign("imgJs",$imgJs);
		$GLOBALS['smarty']->assign("userdata",$userdata[1]);
		$GLOBALS['smarty']->assign("pageText",$userdata[0]);
		$GLOBALS['smarty']->display("list_news.htm");
	}

	//成员dkp一览
	function onStandings() {
		if(!defined("PAGE_RPP_DEF")) {
			define('PAGE_RPP_DEF', $this->pageini['i_user']); 
		}
		//副本
		if(empty($this->gconfig['defaultcopy'])) {
			$this->gconfig['defaultcopy']	= 1;
		}
		$_GET['cid']	= $_GET['cid']?$_GET['cid']:(int)$this->gconfig['defaultcopy'];
		$cid			= (int)$_GET['cid'];
        $groupid        = isset($_GET['groupid'])?(int)$_GET['groupid']:0;

		$copyarrays	= $this->dbt->getInfoByTable("copy");
		if(is_array($copyarrays)) {
			foreach($copyarrays as $key=>$val) {
				$copyarray[$val['id']]	= $val['name'];
			}
		}
        $groupArray = $this->dbt->getUserGroup(true);
		//保留小数
		$sd					= $this->sd;
		//副本raid总数
		$raidarr			= $this->dbt->getCopyNums();
		$raidtotal			= isset($raidarr[$cid])?$raidarr[$cid]:1;
		//条件：成员
		//排序：dkp，组，race,work,raid数量
		//a:user b:_itemdis c:_race d:_work e:group
		$keyword	= addslashes(trim($_GET['keyword']));
		$orderby	= addslashes(trim($_GET['orderby']));
		$orderby	= $orderby?$orderby:"dkpvalue";
		//种族
		$classArray	= $this->dbt->getUserClass();
		if(is_array($classArray)) {
			$classArr	= array();
			$classArr[]	= $GLOBALS['lang']['pleaselect'];
			foreach($classArray as $key=>$val) {
				$classArr[$val['id']]	= $val['name'];
			}
		}
		$classid	= isset($_GET['obj'])?$_GET['obj']:'0';
		$baseurl	= "index.php?module=index&act=standings&obj=".$classid."&cid=$cid";
		//当前
		$sql	= "select a.uid as id,
						b.pic as pic,
						b.name as name,
						b.notes as notes,
						b.lastraidtime as lastraidtime,
						ROUND(a.dkpvalue,$sd) as dkpvalue,
						a.raidnum as rnum,
						a.raidnum/$raidtotal*100 as raidnum,
						b.level as level,
						c.name as racename,
						d.name as workname,
						d.wcolor as wcolor,
						e.name as groupname 
					FROM ".TABLEHEAD."_dkpvalues as a 
						left join ".TABLEHEAD."_user as b on a.uid = b.id  
						left join ".TABLEHEAD."_race as c on b.raceid=c.id 
						left join ".TABLEHEAD."_work as d on b.workid=d.id
						left join ".TABLEHEAD."_group as e on b.groupid=e.id";
		$sql		.= " where b.stat='1'";

		if($classid) {
			$sql	.= " and b.workid='$classid' ";
		}
		if(!empty($cid)) {
			$sql	.= " and a.copyid='$cid' ";
		}
        if(!empty($groupid)) {
        	$sql	.= " and b.groupid='$groupid' ";
        }
		$sql		.= $keyword?" and b.name like '%$keyword%'":"";
		$sql		.= " order by $orderby desc";
		//echo($sql);
		$userdata	= $this->dbt->getPerPageInfo($sql,$this->urlparam);
		//找到需计算的用户
		$userarray	= array();
		if(is_array($userdata[1])) {
			foreach($userdata[1] as $key=>$val) {
				$userarray[]	= (int)$val['id'];
			}
		}
		//var_dump($sql);
		//得到和花费
		if(count($userarray)>0) {
			$earnval	= $this->dbt->getUserDkp($userarray,$cid,$sd);
			$spentval	= $this->dbt->getUserDkp($userarray,$cid,$sd,false);
		}

		//rank count
		$rankArr		= array();
		$rankArray		= array();
		$rankuserstr	= implode(',',$userarray);
		$rankuserstr	= $rankuserstr?$rankuserstr:"0";
		//活跃时间$acttime个月(按活跃时间计算出勤率)
		$acttime		= $this->gconfig['acttime'];
		$acttimeBefore	= date("Y-m-d",time()-$acttime*2592000);
		$sql		= "select a.uid as uid,count(a.uid) as raidnum from ".TABLEHEAD."_itemdis as a left join ".TABLEHEAD."_event as b on a.eid=b.id where b.etid=1 and a.uid in($rankuserstr) and a.distime>='$acttimeBefore' group  by a.uid";
		$rankArr	= $this->dbt->executeSql($sql);
		$sql		= "SELECT uid, sum(raidnum) as raidcount FROM ".TABLEHEAD."_dkpvalues GROUP BY uid order by raidcount desc limit 1";
		$rankcountArr	= $this->dbt->executeSql($sql);
		$raidcount		= !empty($rankcountArr[0]['raidcount'])?$rankcountArr[0]['raidcount']:1;
		if(is_array($rankArr)) {
			foreach($rankArr as $key=>$val) {
				$rankArray[$val['uid']]	= $val['raidnum'];
			}
		}
		
		//var_dump($rankArray);
		foreach($userdata[1] as $key=>$val) {
			if(array_key_exists($val['id'],$earnval)) {
				$userdata[1][$key]['earn']	= $earnval[$val['id']];
			}else {
				$userdata[1][$key]['earn']	= "0";
			}
			if(array_key_exists($val['id'],$spentval)) {
				$userdata[1][$key]['spent']	= $spentval[$val['id']];
			}else {
				$userdata[1][$key]['spent']	= "0";
			}
			//用户armory 2008-8-24
			$userlocal	= isset($_COOKIE['wow_cfg_lang'])?$_COOKIE['wow_cfg_lang']:"cn";
			$userlocal	= $userlocal=='en'?'www':$userlocal;
			$userlocal	= $userlocal=='zh'?'cn':$userlocal;
			$val['id']	= (int)$val['id'];
			$rankArray[$val['id']]	= isset($rankArray[$val['id']])?$rankArray[$val['id']]:0;
			$rankArray[$val['id']]	= $rankArray[$val['id']]>$raidcount?$raidcount:$rankArray[$val['id']];
			$userrank		= ceil($rankArray[$val['id']]/$raidcount*105/7.5)+1;
			$userrank		= ($userrank>0 and $userrank<=15)?$userrank:1;
			$userdata[1][$key]['rank']		= $userrank;
			if(!empty($val['pic'])) {
				$userpic	=  "<img src=./img/".$val['pic']."><br />";
			}else {
				$userpic	= '';
			}
			$userdata[1][$key]['notes']		= $userpic."Rank".$userrank."<br>".$userdata[1][$key]['notes'];
		}
		$GLOBALS['smarty']->assign("getval",$_GET);
		$GLOBALS['smarty']->assign("raidtotal",$raidtotal);
		$GLOBALS['smarty']->assign("baseurl",$baseurl);
		$GLOBALS['smarty']->assign("classArr",$classArr);
        $GLOBALS['smarty']->assign("groupArray",$groupArray);
		$GLOBALS['smarty']->assign("userdata",$userdata[1]);
		$GLOBALS['smarty']->assign("pageText",$userdata[0]);
		$GLOBALS['smarty']->assign("copyarray",$copyarray);
		$GLOBALS['smarty']->display("list_standings.htm");

	}
	//raidslist raidtotal
	function onViewall() {
		//if(!defined(PAGE_RPP_DEF)) {
			define('PAGE_RPP_DEF', $this->pageini['i_raids']); 
		//}
		//保留小数
		$sd	= $this->sd;
		$classid	= $_GET['obj']?$_GET['obj']:'0';
		//种族
		$classArray	= $this->dbt->getUserClass();
		if(is_array($classArray)) {
			$classArr	= array();
			$classArr[]	= $GLOBALS['lang']['pleaselect'];
			foreach($classArray as $key=>$val) {
				$classArr[$val['id']]	= $val['name'];
			}
		}
		$keyword	= addslashes(trim($_GET['keyword']));
		$orderby	= addslashes(trim($_GET['orderby']));
		$orderarray	= array("name");
		if(!in_array($orderby,$orderarray) or empty($orderby)) {
			$orderby	= "workid";
		}
		$copyarr	= $this->dbt->getShowCopy();
		$copyarr	= is_array($copyarr)?$copyarr:array();
		$copykeyarr	= array_keys($copyarr);
		$copycon	= implode(',',$copykeyarr);
		$copycon	= $copycon?$copycon:1;
		$sql	= "select * from ".TABLEHEAD."_user where stat=1 ";
		if($classid) {
			$sql	.= " and workid='$classid' ";
		}
		$sql	.= $keyword?" and name like '%$keyword%'":"";		
		$sql	.= " order by $orderby desc";
		//echo($sql);
		$userdata	= $this->dbt->getPerPageInfo($sql,$this->urlparam);
		$userinfo	= array();
		foreach($userdata[1] as $key=>$val) {
			$userinfo[$val['id']]['uname']			= $val['name'];
			$userinfo[$val['id']]['id']				= $val['id'];
			$sql	= "select ROUND(dkpvalue,$sd) as dkpvalue,uid,copyid,raidnum from ".TABLEHEAD."_dkpvalues where copyid in($copycon) and uid='$val[id]'";
			$userarr= $this->dbt->executeSql($sql);
			$userarr	= is_array($userarr)?$userarr:array();
			foreach($userarr as $key1=>$val1) {
				$userinfo[$val['id']][$val1['copyid']]	= $val1['dkpvalue'];
			}
		}
		
		$contitle	= array('uname'=>$GLOBALS['lang']['user'])+$copyarr;

		$GLOBALS['smarty']->assign("contitle",$contitle);
		$GLOBALS['smarty']->assign("userinfo",$userinfo);
		$GLOBALS['smarty']->assign("pageText",$userdata[0]);
		$GLOBALS['smarty']->assign("classArr",$classArr);
		$GLOBALS['smarty']->assign("getval",$_GET);
		$GLOBALS['smarty']->display("list_viewall.htm");
	}
	//list:event
	function onEvents() {
		//if(!defined(PAGE_RPP_DEF)) {
			define('PAGE_RPP_DEF', $this->pageini['i_event']); 
		//}
		//保留小数
		$sd	= $this->sd;
		//副本
		$cid		= (int)$_GET['cid'];
		$cid		= $cid?$cid:0;
		$copyarrays	= $this->dbt->getInfoByTable("copy");
		$copyarray[]	= $GLOBALS['lang']['pleaselect'];
		if(is_array($copyarrays)) {
			foreach($copyarrays as $key=>$val) {
				$copyarray[$val['id']]	= $val['name'];
			}
		}
		$keyword	= addslashes(trim($_GET['keyword']));
		$orderby	= addslashes(trim($_GET['orderby']));
		$orderarray	= array("name","raidtime");
		if(!in_array($orderby,$orderarray) or empty($orderby)) {
			$orderby	= "raidtime";
		}
		$sql	= "select a.*,b.name as eventtype from ".TABLEHEAD."_event as a 
							left join ".TABLEHEAD."_eventtype as b on a.etid=b.id where 1 ";
		if(!empty($cid)) {
			$sql	.= " and a.cid='$cid' ";
		}
		$sql	.= $keyword?" and a.name like '%$keyword%'":"";		
		$sql	.= " order by $orderby desc";
		$userdata	= $this->dbt->getPerPageInfo($sql,$this->urlparam);
		//raid值
		$userdata[1]	= is_array($userdata[1])?$userdata[1]:array();
		foreach($userdata[1] as $key=>$val){
			$eid	= $val['id'];
			$sql	= "select ROUND(sum(value*stat),$sd) as value from ".TABLEHEAD."_itemdis where eid='$eid'";
			$vrs	= $this->dbt->executeSql($sql);
			$userdata[1][$key]['value']	= $vrs[0]['value'];
		}

		$GLOBALS['smarty']->assign("userdata",$userdata[1]);
		$GLOBALS['smarty']->assign("pageText",$userdata[0]);
		$GLOBALS['smarty']->assign("getval",$_GET);
		$GLOBALS['smarty']->assign("copyarray",$copyarray);

		$GLOBALS['smarty']->display("list_events.htm");
	}
	//list:items
	function onItems() {
		$_GET['iid']	= isset($_GET['iid'])?$_GET['iid']:0;
		//if(!defined(PAGE_RPP_DEF)) {
			define('PAGE_RPP_DEF', $this->pageini['i_item']); 
		//}
		//保留小数
		$sd	= $this->sd;
		$keyword	= addslashes(trim($_GET['keyword']));
		$iid		= addslashes(trim($_GET['iid']));
		$orderby	= addslashes(trim($_GET['orderby']));
		$orderby	= $orderby?$orderby:"intotime";
		$sql		= "select a.*, b.name as itemname from ".TABLEHEAD."_item as a left join ".TABLEHEAD."_itemproperty as b on a.ipid=b.id where 1 ";
		$sql		.= $iid?" and a.id = '$iid'":"";
		$sql		.= $keyword?" and a.name like '%$keyword%'":"";
		$sql		.= " order by a.".$orderby." desc";

		//var_dump($sql);
		$userdata	= $this->dbt->getPerPageInfo($sql,$this->urlparam);
		//get item's property
		if(is_array($userdata[1])) {
			foreach($userdata[1] as $key=>$val) {
				$ipid	= $val['ipid'];
				if(empty($val['icolor']))
				{
					$userdata[1][$key]['icolor']	= "FFFFFF";
				}
                preg_match("/(\d+):/i",$val['itemname'], $matches);
			    $userdata[1][$key]['itemid']	= isset($matches[1])?$matches[1]:0;
				$eid	= $val['eid'];
				$earr	= $this->dbt->executeSql("select name from ".TABLEHEAD."_event where id='$eid'");
				$userdata[1][$key]['eventname']	= $earr[0]['name'];
				$iid	= $val['id'];
				$disarr	= $this->dbt->executeSql("select ROUND(value,$sd) as value from ".TABLEHEAD."_itemdis where iid='$iid'");
				$userdata[1][$key]['value']	= isset($disarr[0]['value'])?$disarr[0]['value']:0;
			}
			
		}
		$GLOBALS['smarty']->assign("userdata",$userdata[1]);
		$GLOBALS['smarty']->assign("pageText",$userdata[0]);
		$GLOBALS['smarty']->assign("getval",$_GET);	
		$GLOBALS['smarty']->display("list_item.htm");
	}
	//list:itemdis
	function onItemdis() {
		//if(!defined(PAGE_RPP_DEF)) {
			define('PAGE_RPP_DEF', $this->pageini['i_dis']); 
		//}
		//保留小数
		$sd	= $this->sd;
		//副本
		$cid		= (int)$_GET['cid'];
		$cid		= $cid?$cid:0;
		$copyarrays	= $this->dbt->getInfoByTable("copy");
		$copyarray[]	= $GLOBALS['lang']['pleaselect'];
		if(is_array($copyarrays)) {
			foreach($copyarrays as $key=>$val) {
				$copyarray[$val['id']]	= $val['name'];
			}
		}
		$orderby	= addslashes(trim($_GET['orderby']));
		$orderby	= $orderby?$orderby:"distime";
		$_GET['obj']= urldecode($_GET['obj']);
		$obj		= $_GET['obj']?$_GET['obj']:"";
		$sql		= "select a.id as id,
							a.eid as eid,
							b.name as iid,
							b.ipid as ipid,
							b.icolor as icolor,
							c.name as uname,
							c.id   as uid,
							d.name as eventname,
							ROUND(a.value,$sd) as value,
							a.distime as distime, 
							e.name as itemname 
						from ".TABLEHEAD."_itemdis as a 
						left join ".TABLEHEAD."_item as b on a.iid=b.id 
						left join ".TABLEHEAD."_user as c on a.uid=c.id 
						left join ".TABLEHEAD."_event as d on a.eid=d.id 
						left join ".TABLEHEAD."_itemproperty as e on b.ipid=e.id 
						where a.iid is not null";
		if(!empty($obj)) {
			$obj	= addslashes(trim($obj));
			$sql	.= " and c.name like '%".$obj."%' ";
		}
		if(!empty($cid)) {
			$sql	.= " and a.cid='$cid' ";
		}
		$sql	.= " order by $orderby desc";
		//var_dump($sql);
		$_GET['obj']= urlencode($_GET['obj']);
		$urlparam	= "?module=index&act=itemdis&orderby=".$_GET['orderby']."&obj=".$_GET['obj']."&cid=".$_GET['cid'];
		$baseurl	= "index.php?module=index&act=itemdis&obj=".$obj."&cid=".$_GET['cid'];
		$userdata	= $this->dbt->getPerPageInfo($sql,$urlparam);
		foreach ($userdata[1] as $k => $v){
            preg_match("/(\d+):/i",$v['itemname'], $matches);
			$userdata[1][$k]['itemid']	= isset($matches[1])?$matches[1]:0;
		}
		
		$GLOBALS['smarty']->assign("obj",$obj);
		$GLOBALS['smarty']->assign("baseurl",$baseurl);
		$GLOBALS['smarty']->assign("userdata",$userdata[1]);
		$GLOBALS['smarty']->assign("pageText",$userdata[0]);
		$GLOBALS['smarty']->assign("getval",$_GET);
		$GLOBALS['smarty']->assign("copyarray",$copyarray);
		$GLOBALS['smarty']->display("list_itemdis.htm");
	}
	//语言设置
	function onSetlang() {
		if(empty($_GET['lang'])) {
			setcookie("wow_cfg_lang","en",time()+2592000);
		}else {
			if(file_exists(CHINO_PHPPATH . "/lang/".$_GET['lang']."/lang.inc.php")) {
				setcookie("wow_cfg_lang",$_GET['lang'],time()+2592000);
			}else {
				setcookie("wow_cfg_lang","en",time()+2592000);
			}
		}
		header("Location: ".$_SERVER["HTTP_REFERER"]);
	}

	//阅读新闻
	function onViewnews() {
		$id			= $_GET['id'];
		$newsinfo	= $this->dbt->readRecord("news",$id);
		include CHINO_LIBPATH.'/class_Ubb.php';
		$eq		=  new Ubb();
		$eq->url= true;
		$imgJs	= $eq->getImageOpener();
		$eq->setStr(stripslashes($newsinfo[0]['content']));
		$val	= $eq->ubbEncode();
		$newsinfo[0]['content']= nl2br($val);
		$GLOBALS['smarty']->assign("imgJs",$imgJs);
		$GLOBALS['smarty']->assign("userdata",$newsinfo[0]);
		$GLOBALS['smarty']->display("view_news.htm");
	}

	//raids详情
	function onViewraids() {
		//取事件信息
		$id					= (int)$_GET['id'];
		$_GET['orderby']	= $_GET['orderby']?$_GET['orderby']:"class";
		$eventinfo	= $this->dbt->readRecord("event",$id);
		//保留小数
		$sd	= $this->sd;
		//取事件人员信息
		$sql		= "select a.uid as uid,ROUND((a.value*a.stat),$sd) as value,a.stat as stat,b.name as name,c.name as class,d.name as gname,c.wcolor as wcolor,c.id as classid from ".TABLEHEAD."_itemdis as a left join ".TABLEHEAD."_user as b on a.uid=b.id left join ".TABLEHEAD."_work as c on b.workid=c.id left join ".TABLEHEAD."_group as d on b.groupid=d.id  where a.eid='$id'  and a.stat = 1 ";
		if(!empty($_GET['orderby']) and in_array($_GET['orderby'],array('class','gname'))) {
			$sql	.= " order by $_GET[orderby] desc ";
		}
		$userinfo	= $this->dbt->executeSql($sql);
		//参加人数
		$eventinfo[0]['joinnum']	= count($userinfo);
		$eventinfo[0]['joinnum']	= $eventinfo[0]['joinnum']>0?$eventinfo[0]['joinnum']:1;
		foreach($userinfo as $key=>$val) {
			$classcfg[$val['classid']]['num']	= isset($classcfg[$val['classid']]['num'])?$classcfg[$val['classid']]['num']:0;
			$userinfo[$key]['urlname']	= urlencode($val['name']);
			if(!isset($val['classid'])) {
				$val['classid']	= 'none';
				$val['class']	= 'none';
			}
            $classcfg[$val['classid']]  = isset($classcfg[$val['classid']])?$classcfg[$val['classid']]:array();
            $classcfg[$val['classid']]['num']  = isset($classcfg[$val['classid']]['num'])?$classcfg[$val['classid']]['num']:0;
			$classcfg[$val['classid']]['num']	+=1;
			$classcfg[$val['classid']]['uname']	= $val['class'];
			$classcfg[$val['classid']]['wcolor']= isset($val['wcolor'])?$val['wcolor']:"FFFF00";
		}
        
		if(is_array($classcfg)) {
			$cstr	= '<table width=100% class=navtext><tr>';
			$i		= 1;
			foreach($classcfg as $key=>$val) {
                $val['wcolor']  = isset($val['wcolor'])?$val['wcolor']:"FFFF00";
                $val['uname']   = isset($val['uname'])?$val['uname']:"none";
				$cstr	.= "<td><font color=".$val['wcolor'].">".$val['uname']."</font> : ".$val['num']."(".sprintf('%0.2f',$val['num']/$eventinfo[0]['joinnum']*100)."%)</td>";
				if($i%4==0) {
					$cstr	.= "</tr><tr>";
				}
				$i++;
			}
			$cstr	.= '</tr></table>';
		}

		//取事件产生的物品 sprintf("%01.2f", $money);
		$sql		= "select a.id, 
								a.eid, 
								a.name, 
								a.stat, 
								a.intotime, 
								ROUND(b.value,$sd) as value, 
								b.uid, 
								c.name as username,
								a.icolor as icolor,
								a.ipid as ipid, 
								d.name as itemname 
						from ".TABLEHEAD."_item as a 
						left join ".TABLEHEAD."_itemdis as b on a.id=b.iid 
						left join ".TABLEHEAD."_user as c on b.uid=c.id 
						left join ".TABLEHEAD."_itemproperty as d on a.ipid=d.id  
						where a.eid='$id'";
						
		$itemsArr	= $this->dbt->executeSql($sql);
		foreach($itemsArr as $key=>$val) {
			$itemsArr[$key]['urlusername']	= urlencode($val['username']);
			$itemsArr[$key]['urlitemname']	= urlencode($val['name']);
			$itemsArr[$key]['icolor']		= $itemsArr[$key]['icolor']?$itemsArr[$key]['icolor']:'D7CEA4';
            preg_match("/(\d+):/i",$val['itemname'], $matches);
			$itemsArr[1][$key]['itemid']	= isset($matches[1])?$matches[1]:0;
		}

		$GLOBALS['smarty']->assign("eventinfo",$eventinfo[0]);
		$GLOBALS['smarty']->assign("userinfo",$userinfo);
		$GLOBALS['smarty']->assign("itemsArr",$itemsArr);
		$GLOBALS['smarty']->assign("cstr",$cstr);
		$GLOBALS['smarty']->display("view_raids.htm");
	}

	//view member
	function onViewmember() {
		if(!defined("PAGE_RPP_DEF")) {
			define('PAGE_RPP_DEF', $this->pageini['i_news']); 
		}
		//保留小数
		$sd	= $this->sd;
		//副本
		if(empty($this->gconfig['defaultcopy'])) {
			$this->gconfig['defaultcopy']	= 1;
		}
		$_GET['cid']	= $_GET['cid']?$_GET['cid']:(int)$this->gconfig['defaultcopy'];
		$cid			= (int)$_GET['cid'];

		$copyarr	= $this->dbt->getShowCopy();
		$copyarr	= is_array($copyarr)?$copyarr:array();
		foreach($copyarr as $key=>$val) {
			$copyarray[$key]	= $val;
		}

		$uid	= (int)$_GET['id'];
		$itemarray	= $userdata	= $prostratearray = '';
		$sql	= "select a.name as member, a.raceid as raceid, a.workid as workid, a.level as level, a.groupid as groupid, a.stat as stat, a.regtime as regtime,a.pic as pic, b.name as race, c.name as class, d.name as gname from ".TABLEHEAD."_user as a left join ".TABLEHEAD."_race as b on a.raceid = b.id left join ".TABLEHEAD."_work as c on a.workid=c.id left join ".TABLEHEAD."_group as d on a.groupid =d.id where a.id='$uid'";
		$userdata	= $this->dbt->executeSql($sql);
		//取得用户当前副本的dkp
		$udkparr	= $this->dbt->executeSql("select ROUND(dkpvalue,$sd) as dkpvalue from ".TABLEHEAD."_dkpvalues where uid='$uid' and copyid='$cid'");
		$udkparr[0]['dkpvalue']		= isset($udkparr[0]['dkpvalue'])?$udkparr[0]['dkpvalue']:0;
		$userdata[0]['dkpvalue']	= $udkparr[0]['dkpvalue'];
		//prize itemdis
		$sql	= "select a.iid as iid, ROUND(a.value,$sd) as dkp, DATE_FORMAT(a.distime,'%Y-%m-%d') as time,a.eid as eid,b.name as item,b.ipid as ipid,b.icolor as icolor,c.name as ename, d.name as itemname from ".TABLEHEAD."_itemdis as a left join ".TABLEHEAD."_item as b on a.iid=b.id left join ".TABLEHEAD."_event as c on a.eid=c.id left join ".TABLEHEAD."_itemproperty as d on b.ipid=d.id where a.uid='$uid' and a.iid is not null order by a.distime desc ";
		$itemarray	= $this->dbt->getPerPageInfo($sql,$this->urlparam);
		$sql	= "select a.id as id,(ROUND(a.value,$sd)*stat) as value,b.name as name,b.raidtime as raidtime, b.notes as notes,b.id as eid from ".TABLEHEAD."_itemdis as a left join ".TABLEHEAD."_event as b on a.eid=b.id where a.uid='$uid' and a.cid='$cid' and iid is null order by b.raidtime desc";
		$prostratearray	= $this->dbt->getPerPageInfo($sql,$this->urlparam);

		if(!empty($_SESSION['member']['uid'])) {
			//所在活动队列
			$userid	= $_SESSION['member']['uid'];
			$sql	= "select a.id,a.name from ".TABLEHEAD."_raidlog as a left join ".TABLEHEAD."_signup as b on a.id=b.raid where b.uid='$userid' and (b.stat=1 or b.stat=2) and a.stat=0";
			$actioninfo	= $this->dbt->executeSql($sql);
			$actioninfo	= is_array($actioninfo)?$actioninfo:array();
			$GLOBALS['smarty']->assign("actioninfo",$actioninfo);
			$GLOBALS['smarty']->assign("showactioninfo",count($actioninfo));
		}

		//2008-9-14armory属性加入
        if((phpversion() > "5") and $this->gconfig['isarmory']) {
            $memberCacheFile	= CHINO_CACHEBASE."/member/".md5($userdata[0]['member']);
            if(file_exists($memberCacheFile)){
                $memberInfo		= unserialize(file_get_contents($memberCacheFile));

            }else {
                Chino::loadLib("armory");
                //var_dump($this->gconfig);
                $armory	= new armory($this->gconfig['location'], $this->gconfig['langtype']);
                $armory->makeMemberCache($this->gconfig['realm'], $userdata[0]['member']);
                $memberInfo	= $armory->memberInfo;
            }
            $xml				= new SimpleXMLElement($memberInfo);
            $character			= array();
            $itemsets			= array();
            $professions		= array();	//商业技能
            $errorCode			= (string)$xml->characterInfo['errCode'];
            if(empty($errorCode)){
                foreach ($xml->characterInfo->character->attributes() as $k =>$v){
                    $ckey	= (string)$k;
                    $cval	= (string)$v;
                    $character[$ckey]	= $cval;
                }
                $i	= 0;
                foreach ($xml->characterInfo->characterTab->items->item as $k =>$v){
                    foreach ($v->attributes() as $ik=>$iv){
                        $sik	= (string)$ik;
                        $siv	= (string)$iv;
                        $itemsets[$i][$sik]	= $siv;
                    }
                    $i++;
                }
                $i	= 0;
                foreach ($xml->characterInfo->characterTab->professions->skill as $k =>$v){
                    foreach ($v->attributes() as $ik=>$iv){
                        $sik	= (string)$ik;
                        $siv	= (string)$iv;
                        $professions[$i][$sik]	= $siv;
                    }
                    $i++;
                }
                //pvp
                $character['lifetimehonorablekills']	= (string)$xml->characterInfo->characterTab->pvp->lifetimehonorablekills['value'];
                $character['arenacurrency']				= (string)$xml->characterInfo->characterTab->pvp->arenacurrency['value'];
                //生命和第二槽
                $character['health']				= (string)$xml->characterInfo->characterTab->characterBars->health['effective'];
                $character['secondBar']				= (string)$xml->characterInfo->characterTab->characterBars->secondBar['effective'];
                //天赋
                $talentSpec		= array();
				if(!empty($xml->characterInfo->characterTab->talentSpec)){
					foreach ($xml->characterInfo->characterTab->talentSpec->attributes() as $ik=>$iv){
						$talentSpec[]	= (string)$iv;
					}
				}
                
                //头像
                $headInfo	= (string)$xml->characterInfo->character['genderId'].'-'.(string)$xml->characterInfo->character['raceId'].'-'.(string)$xml->characterInfo->character['classId'];
                $character['talentSpec']	= implode('/',$talentSpec);
                $GLOBALS['smarty']->assign("character",$character);
                $GLOBALS['smarty']->assign("itemsets",$itemsets);
                $GLOBALS['smarty']->assign("professions",$professions);
                $GLOBALS['smarty']->assign("headInfo",$headInfo);
            }
        }

		//var_dump($itemarray);
		$itemarray[1]	= is_array($itemarray[1])?$itemarray[1]:array();
		foreach ($itemarray[1] as $k => $v){
			preg_match("/(\d+):/i",$v['itemname'], $matches);
			$itemarray[1][$k]['itemid']	= isset($matches[1])?$matches[1]:0;
		}
		$GLOBALS['smarty']->assign("getval",$_GET);
		$GLOBALS['smarty']->assign("userdata",$userdata[0]);
		$GLOBALS['smarty']->assign("itemarray",$itemarray[1]);
		$GLOBALS['smarty']->assign("itempagetext",$itemarray[0]);
		$GLOBALS['smarty']->assign("prostratearray",$prostratearray[1]);
		$GLOBALS['smarty']->assign("prostpagetext",$prostratearray[0]);
		$GLOBALS['smarty']->assign("copyarray",$copyarray);

		$GLOBALS['smarty']->display("view_member.htm");
	}

	//更新角色armory信息
	function onUpdateMember(){
        if((phpversion() > "5") and $this->gconfig['isarmory']) {
        	$character	= $_POST['memberName'];
            Chino::loadLib("armory");
            $armory	= new armory($this->gconfig['location'], $this->gconfig['langtype']);
            $armory->makeMemberCache($this->gconfig['realm'], $character);
            Chino::raiseInfo($GLOBALS['smarty'],$GLOBALS['lang']['updateInfo'], $_SERVER["HTTP_REFERER"]);
        }else {
        	Chino::raiseInfo($GLOBALS['smarty'],'Nothing to do.', $_SERVER["HTTP_REFERER"]);
        }
	}

	//show member's login form
	function onShowlogin() {
		if(!empty($_SESSION['member']['username'])) {
			$uid	= $_SESSION['member']['uid'];
			header("Location: index.php?module=index&act=viewmember&id=$uid");
			exit;
		}
		if($this->gconfig['islogin']=='0') {
			header("Location: index.php");
			exit;
		}
		$GLOBALS['smarty']->assign("jumpurl",$_SERVER['HTTP_REFERER']);
		$GLOBALS['smarty']->display("Showlogin.htm");
	}
	//show administrator's form
	function onLogin(){
		$GLOBALS['smarty']->display("LoginAdmin.htm");
	}
	//modify member's info 
	function onModifymeminfo() {
		$userarray		= $this->dbt->readRecord('user',$_SESSION['member']['uid']);
		$GLOBALS['smarty']->assign("userinfo",$userarray[0]);
		//race info
		$sql			= "select * from ".TABLEHEAD."_race";
		$racearr		= $this->dbt->executeSql($sql);
		if(is_array($racearr)) {
			foreach($racearr as $key=>$val) {
				$racearray[$val['id']]	= $val['name'];
			}
		}
		$GLOBALS['smarty']->assign("racearray",$racearray);
		
		$workarr		= $this->dbt->getUserClass();
		if(is_array($workarr)) {
			foreach($workarr as $key=>$val) {
				$workarray[$val['id']]	= $val['name'];
			}
		}
		$GLOBALS['smarty']->assign("workarray",$workarray);
		$GLOBALS['smarty']->display("Modifymeminfo.htm");
	}
	//updatememinfo
	function onUpdatememinfo() {
		$uid	= $_SESSION['member']['uid'];
		if(empty($uid)) {
			unset($_SESSION['member']);
			header("Location: index.php");
			exit;
		}
		$mempic	= '';
		if(!empty($_FILES['photofile']['tmp_name'])) {
			//检查文件类型是否合要求
			$picPostFix		= $this->_checkFileName($_FILES['photofile']['name']);
			if(!$picPostFix) {
				Chino::raiseInfo($GLOBALS['smarty'],$GLOBALS['lang']['pic'].$GLOBALS['lang']['note31'], $_SERVER["HTTP_REFERER"]);
			}
			$post_time		= date("Y-m-d H:i:s",time() + $this->timezone);
			$updateFilePath	= CHINO_WWWROOT."/img/mem_".$uid.".".$picPostFix;
			if (!move_uploaded_file($_FILES['photofile']['tmp_name'], $updateFilePath)) {
				Chino::raiseInfo($GLOBALS['smarty'],$GLOBALS['lang']['modify'].$GLOBALS['lang']['faild'], $_SERVER["HTTP_REFERER"]);
			}else {
				$mempic	= "mem_".$uid.".".$picPostFix;
			}
		}
		//200
		$_POST['notes']	= preg_replace("/\r|\n|<|>/i","",substr($_POST['notes'],0,200).chr(0));
		$sql	= "update ".TABLEHEAD."_user set raceid='$_POST[raceid]',workid='$_POST[workid]',level='$_POST[level]',notes='$_POST[notes]' ";
		if(!empty($mempic)) {
			$sql	.= ",pic='$mempic' ";
		}
		if(!empty($_POST['password'])) {
			$password	= md5($_POST['password']);
			$sql	.= ", password='$password' ";
		}
		$sql	.= " where id='$uid'";
		$rs		= $GLOBALS['adodb']->execute($sql);
		if($rs) {
			Chino::raiseInfo($GLOBALS['smarty'],$GLOBALS['lang']['modify'].$GLOBALS['lang']['succeed'], "index.php?module=index&act=viewmember&id=$uid");
		}else {
			Chino::raiseInfo($GLOBALS['smarty'],$GLOBALS['lang']['modify'].$GLOBALS['lang']['faild'], $_SERVER["HTTP_REFERER"]);
		}
	}
	
	/**
	* 注册
	*/
	function onRegiste() {
		if($this->gconfig['islogin']=='0') {
			header("Location: index.php");
		}
		$GLOBALS['smarty']->display("show_registe.htm");
	}
	function onDoRegiste() {
		if($this->gconfig['islogin']=='0') {
			header("Location: index.php");
			exit;
		}
		$password	= md5($_POST['password']);
		$regtime	= date("Y-m-d H:i:s",time() + $this->timezone);
		if($_POST['password']!=$_POST['repasswd'] or empty($_POST['repasswd'])) {
			Chino::raiseInfo($GLOBALS['smarty'],$GLOBALS['lang']['note14'], "index.php?module=index&act=registe");
		}
		if(empty($_POST['username'])) {		
			Chino::raiseInfo($GLOBALS['smarty'],$GLOBALS['lang']['user'].$GLOBALS['lang']['name'].$GLOBALS['lang']['nonull'], "index.php?module=index&act=registe");
		}
		//检查此用户是否存在
		$sql	= "select * from ".TABLEHEAD."_user where name='$_POST[username]'";
		$rs		= $this->dbt->executeSql($sql);
		if(!empty($rs[0]['id'])) {
			$this->onShowlogin();
		}else {
			$sql	= "insert into ".TABLEHEAD."_user(name,password,regtime) values('$_POST[username]','$password','$regtime')";
			$rs		= $GLOBALS['adodb']->execute($sql);			
			$this->onShowlogin();
		}		
	}

	/**
	* 列表
	*/
	function onList() {
		$obj		= $_GET['obj'];
		$nowtime	= date("Y-m-d H:i:s",time() + $this->timezone);
		define('PAGE_RPP_DEF', $this->pageini['i_news']); 
		switch($obj) {
			case 'actionlist':
				$tpl	= 'list_actionlist.htm';
				//cid变义为是否历史记录
				$raidcfg	= array();
				//职业
				$classarr[0]= 'Unknow';
				$classinfo	= $this->dbt->getInfoByTable("work");
				foreach($classinfo as $key=>$val) {
					$classarr[$val['id']]	= $val['name'];
				}
				//var_dump($classarr);
				//取活动列表
				$sql	= "select a.*,b.name as cfg,b.maxnum,b.classreq,b.resistance,b.minlevel,b.maxlevel,b.autoqueue from ".TABLEHEAD."_raidlog as a left join ".TABLEHEAD."_raidcfg as b on a.rid=b.id where a.stat = 0 and starttime > '$nowtime' order by starttime desc";
				if($_GET['cid'] == '1') {
					$sql	= "select a.*,b.name as cfg,b.maxnum,b.classreq,b.resistance,b.minlevel,b.maxlevel,b.autoqueue from ".TABLEHEAD."_raidlog as a left join ".TABLEHEAD."_raidcfg as b on a.rid=b.id where a.stat = 1 or starttime < '$nowtime' order by starttime desc";
					$tpl	= 'list_historyAction.htm';
				}
				$userdata	= $this->dbt->getPerPageInfo($sql,$this->urlparam);
				//配置属性
				if(is_array($userdata[1])) {
					foreach($userdata[1] as $key=>$val) {
						$userdata[1][$key]['date']		= date("m-d",strtotime($userdata[1][$key]['starttime']));
						$userdata[1][$key]['starttime']		= date("H:i",strtotime($userdata[1][$key]['starttime']));
						$userdata[1][$key]['invitetime']		= date("H:i",strtotime($userdata[1][$key]['invitetime']));

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
						$signclass	= $this->dbt->executeSql($sql);
						$userdata[1][$key]['signupnotes']	= $GLOBALS['lang']['action'].$GLOBALS['lang']['signup'].'<br />';
						$signtotal	= 0;
						if(is_array($signclass)) {
							foreach($signclass as $key3=>$val3) {
								$signtotal	+= $val3['num'];
								$userdata[1][$key]['signupnotes']	.= $classarr[$val3['workid']].':'.$val3['num'].'/'.$signnum[$val3['workid']].'<br />';
							}
						}
						$userdata[1][$key]['signupnotes']	.= "Total:".$signtotal.'/'.$val['maxnum'].'<br />';
						$userdata[1][$key]['signupnotes']	.= $val['notes'];
					}
				}
			break;
			case 'showsignup':
				$id			= (int)$_GET['id'];
				//职业
				$classarr[0]= 'Unknow';
				$classinfo	= $this->dbt->getInfoByTable("work");
				foreach($classinfo as $key=>$val) {
					$classarr[$val['id']]	= $val['name'];
				}
				//活动信息
				$sql	= "select a.*,b.name as cfg,b.maxnum,b.classreq,b.resistance,b.minlevel,b.maxlevel,b.autoqueue from ".TABLEHEAD."_raidlog as a left join ".TABLEHEAD."_raidcfg as b on a.rid=b.id where a.id='$id'";
				$ars	= $this->dbt->executeSql($sql);
				//职业需求
				$actioninfo	= $ars[0];
				$tmpclass	= explode('|',$actioninfo['classreq']);
				if(is_array($tmpclass)) {
					foreach($tmpclass as $key2=>$val2) {
						$tc		= explode(':',$val2);
						$actioninfo['classes'][$tc[0]]	= $tc[1];
					}
				}
				//抗性需求
				$tmpresistance	= explode(':',$actioninfo['resistance']);
				if(is_array($tmpresistance)) {
					foreach($tmpresistance as $key3=>$val3) {
						$actioninfo['resistancereq'][]	= $val3;
					}
				}
				$actioninfo['currenttime']	= date("Y-m-d H:i:s",time() + $this->timezone);

				//报名会员
				$members	= $this->dbt->executeSql("select a.*,b.name as username,b.workid as workid,b.level as level from ".TABLEHEAD."_signup as a left join ".TABLEHEAD."_user as b on a.uid=b.id where a.raid='$id'");
				$classes	= $this->dbt->getInfoByTable('work');
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
				$issignup		= '0';
				//是否过期
				$isshow			= '1';
				foreach($members as $key=>$val) {
					$val['classname']	= $classname[$val['workid']];
					$val['color']		= $classcolor[$val['workid']];
					if($val['stat'] == '1') {
						$fullmembers[$val['workid']]['member'][]	= $val;
						$fullmembers[$val['workid']]['color']		= $classcolor[$val['workid']];
						$fullmembers[$val['workid']]['cname']		= $classname[$val['workid']];
						if($_SESSION['member']['uid'] == $val['uid']) {
							$issignup		= '1';
						}
					}elseif($val['stat'] == '2') {
						$recruits[]	= $val;
						if($_SESSION['member']['uid'] == $val['uid']) {
							$issignup		= '2';
						}
					}else {
						$cancels[]	= $val;
						if($_SESSION['member']['uid'] == $val['uid']) {
							$issignup		= '3';
						}
					}
				}
				$endtime		= strtotime($actioninfo['invitetime']) - $actioninfo['freezelimit']*3600;
				$ctime			= time() + $this->timezone;
				if($ctime > $endtime) {
					$isshow		= '0';
				}
				if(empty($_SESSION['member']['uid'])) {
					$issignup		= '3';
				}

				$GLOBALS['smarty']->assign("fullmembers",$fullmembers);
				$GLOBALS['smarty']->assign("recruits",$recruits);
				$GLOBALS['smarty']->assign("cancels",$cancels);
				$GLOBALS['smarty']->assign("actioninfo",$actioninfo);
				$GLOBALS['smarty']->assign("classarr",$classarr);
				$GLOBALS['smarty']->assign("issignup",$issignup);
				$GLOBALS['smarty']->assign("isshow",$isshow);
				$tpl	= 'list_showsignup.htm';
			break;
		}
		$GLOBALS['smarty']->assign("userdata",$userdata[1]);
		$GLOBALS['smarty']->assign("pageText",$userdata[0]);
		$GLOBALS['smarty']->display($tpl);
	}

	//signup
	function onSignup() {
		$id		= (int)$_POST['logid'];
		$uid	= $_SESSION['member']['uid'];
		if(empty($_SESSION['member']['uid'])) {
			Chino::raiseInfo($GLOBALS['smarty'],"Request error!", $_SERVER["HTTP_REFERER"]);
		}
		$signtime	= date("Y-m-d H:i:s",time() + $this->timezone);
		//报名时间是否已过
		$actioninfos	= $this->dbt->executeSql("select a.*,b.autoqueue as autoqueue,b.maxnum as maxnum from ".TABLEHEAD."_raidlog as a left join ".TABLEHEAD."_raidcfg as b on a.rid=b.id where a.id='$id'");
		$endtime		= strtotime($actioninfos[0]['invitetime']) - $actioninfos[0]['freezelimit']*3600;
		$ctime			= time() + $this->timezone;
		$maxnum			= (int)$actioninfos[0]['maxnum'];
		if($ctime > $endtime) {
			Chino::raiseInfo($GLOBALS['smarty'],$GLOBALS['lang']['note47'], $_SERVER["HTTP_REFERER"]);
		}
		//是否已报名
		$issignup	= $this->dbt->executeSql("select uid from ".TABLEHEAD."_signup where uid='$uid' and raid='$id'");
		if(!empty($issignup[0]['uid'])) {
			Chino::raiseInfo($GLOBALS['smarty'],$GLOBALS['lang']['note48'], $_SERVER["HTTP_REFERER"]);
		}
		//职业
		$classes	= $this->dbt->executeSql("select workid from ".TABLEHEAD."_user where id='$uid'");
		$workid		= $classes[0]['workid'];
		//自动候补还是正式(如果人已够，自动进入候补2006-9-5)
		$cmaxnums	= $this->dbt->executeSql("select count(*) as cnum from ".TABLEHEAD."_signup where raid = '$id' and stat = '1'");
		if($maxnum <= $cmaxnums[0]['cnum']) {
			$actioninfos[0]['autoqueue'] = '1';
		}

		if($actioninfos[0]['autoqueue'] == '1') {
			$sql	= "insert into ".TABLEHEAD."_signup (uid,workid,raid,signtime,stat) values('$uid','$workid','$id','$signtime','2')";
		}else {
			$sql	= "insert into ".TABLEHEAD."_signup (uid,workid,raid,signtime,stat) values('$uid','$workid','$id','$signtime','1')";
		}
		$rs		= $GLOBALS['adodb']->execute($sql);
		if(!$rs) {
			Chino::raiseInfo($GLOBALS['smarty'],$GLOBALS['lang']['signup'].$GLOBALS['lang']['faild'], $_SERVER["HTTP_REFERER"]);
		}else {
			Chino::raiseInfo($GLOBALS['smarty'],$GLOBALS['lang']['signup'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
		}
	}
	//取消报名:只有当为候补时可取消
	function onUnsignup() {
		$id		= (int)$_POST['logid'];
		$uid	= $_SESSION['member']['uid'];
		if(empty($_SESSION['member']['uid'])) {
			Chino::raiseInfo($GLOBALS['smarty'],"Request error!", $_SERVER["HTTP_REFERER"]);
		}
		$sql	= "update ".TABLEHEAD."_signup set stat = 0 where uid='$uid' and raid='$id' and stat =2";
		$rs		= $GLOBALS['adodb']->execute($sql);
		if(!$rs) {
			Chino::raiseInfo($GLOBALS['smarty'],$GLOBALS['lang']['cancle'].$GLOBALS['lang']['faild'], $_SERVER["HTTP_REFERER"]);
		}else {
			Chino::raiseInfo($GLOBALS['smarty'],$GLOBALS['lang']['cancle'].$GLOBALS['lang']['succeed'], $_SERVER["HTTP_REFERER"]);
		}
	}
	function onAuthor() 
	{
		exit("Tonera");
	}
	/**
	* 检查文件名是否合法
	* param string $file
	* return mix 文件后缀或false
	*/
	function _checkFileName($file) {
		$imgTypeArray	= array("jpeg","gif","png","jpg");
		$typeArray	= @explode('.',trim($file));
		$num		= @count($typeArray)-1;
		if(@in_array(@strtolower($typeArray[$num]),$imgTypeArray)) {
			Return $typeArray[$num];
		}else {
			Return false;
		}
	}
}
?>