<?php
/**
+-----------------------------------------------------------------------+
* @copyright	2.0
* @author       tonera
* @version      $Id: class_Armory.php,v 1.0 zhangtao$
* @update		2008-9-14
+ @description	
+-----------------------------------------------------------------------+
*/

class armory{
	var $characterUrl	= "";
	var $itemUrl		= "";
	var $iconUrl		= ".wowarmory.com/images/icons/64x64/";
	var $headUrl		= ".wowarmory.com/images/portraits/";
	var $memberInfo		= array();
	var $itemInfo		= array();
	var $langUrl		= 'cn';
	var $langType		= 'zh-cn';
	var $isphp5			= false;

	function armory($langUrl, $langType){
		$this->langUrl		= $langUrl;
		$this->langType		= $langType;
		$this->isphp5		= (phpversion() > "5");
		if(!$this->isphp5){
			$this->characterUrl	= "http://www.dkper.com/webservice/character-sheet.php?lng=".$this->langType;
			$this->itemUrl		= "http://www.dkper.com/webservice/item-tooltip.php?lng=".$this->langType;
		}else {
			$this->characterUrl	= "http://".$this->langUrl.".wowarmory.com/character-sheet.xml?";
			$this->itemUrl		= "http://".$this->langUrl.".wowarmory.com/item-tooltip.xml?";
		}
	}

	//创建角色的缓存信息
	function makeMemberCache($realm, $char){
		set_time_limit(3000);
		$url				= $this->characterUrl.'&r='.urlencode($realm).'&n='.urlencode($char);
		
		$xmlstr				= $this->fileGetContents($url, $this->langType);
		$xml				= new SimpleXMLElement($xmlstr);
		$this->memberInfo	= $xmlstr;
		$memberName			= (string)$xml->characterInfo->character['name'];
		if(empty($memberName)){
			return false;
		}
		$memberCacheFile	= CHINO_CACHEBASE."/member/".md5($char);
		$this->writeCache($memberCacheFile, serialize($xmlstr));

		//角色装备图片
		foreach ($xml->characterInfo->characterTab->items->item as $k =>$v){
			$imgUrl			= "http://".$this->langUrl.".wowarmory.com/images/icons/51x51/".(string)$v['icon'].".jpg";
			$imgFile		= CHINO_WWWROOT."/images/icons/51x51/".(string)$v['icon'].".jpg";
			if(!file_exists ($imgFile) or filesize($imgFile)<=0){
				$imgData	= file_get_contents($imgUrl);
				$this->writeCache($imgFile, $imgData);
			}
		}
		//角色头像信息
		$headInfo	= (string)$xml->characterInfo->character['genderId'].'-'.(string)$xml->characterInfo->character['raceId'].'-'.(string)$xml->characterInfo->character['classId'];
		$level		= (string)$xml->characterInfo->character['level'];
		if($level<70){
			$headPic		= "http://".$this->langUrl.$this->headUrl.'wow/'.$headInfo.".gif";
		}else {
			$headPic		= "http://".$this->langUrl.$this->headUrl.'wow-70/'.$headInfo.".gif";
		}
		$localHeadPic	= CHINO_CACHEBASE."/member/".$headInfo.".gif";
		if(!file_exists($localHeadPic) or filesize($localHeadPic)<=0){
			$imgData		= file_get_contents($headPic);
			$this->writeCache($localHeadPic, $imgData);
		}
	}

	//创建物品的缓存信息
	function makeItemCache($itemid){
		
		$url				= $this->itemUrl."&i=".$itemid;
		$xmlstr				= $this->fileGetContents($url, $this->langType);
		$xml				= new SimpleXMLElement($xmlstr);
		$icon				= (string)$xml->itemTooltips->itemTooltip->icon;
		if(empty ($icon)){
			return false;
		}
		$iconUrl			= "http://".$this->langUrl.$this->iconUrl.$icon.".jpg";
		$itemCacheFile		= CHINO_CACHEBASE."/item/".$itemid.".xml";
		if(!file_exists($itemCacheFile) or filesize($itemCacheFile)<=0){
			$this->writeCache($itemCacheFile, serialize($xmlstr));
		}
		//使用51X51图片.2008-9-17
		$iconFile			= CHINO_WWWROOT."/images/icons/64x64/".$icon.".jpg";
		if(!file_exists ($iconFile) or filesize($iconFile)<=0){
            $iconData		= file_get_contents($iconUrl);
			$this->writeCache($iconFile, $iconData);
		}
        return $xmlstr;
	}
	
	//写文件
	function writeCache($file, $data){
		$handle	= fopen($file, "w");
		if(!$handle or !is_writeable($file)){
			exit ("Please check ".$file." is writable.");
		}
		fwrite($handle, $data);
		fclose($handle);
	}

	//取得信息
	function fileGetContents($url, $langType) {
		// Request options.
		$options = stream_context_create(
			array(
				'http' => array(
					'method' => 'GET',
					'header' => "User-Agent: Mozilla/5.0 Gecko/20070515 Firefox/2.0.0.4\r\n".
								"Accept-Language: ".$langType."\r\n"
				)
			)
		); 
		if(!$this->isphp5){
			return file_get_contents($url);
		}else {
			return file_get_contents($url, false, $options);
		}
	}
};








?>