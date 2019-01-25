<?php
header('content-type: text/html; charset=utf-8');
include_once(dirname(__FILE__) . "/php/config.inc.php");
include_once(CHINO_LIBPATH."/class_Armory.php");

$langType			= $_GET['lng'];
$lo					= $_GET['lo'];
$iname				= $_GET['iname'];
$id					= (int)$_GET['id'];
if(!in_array($lo, array('cn', 'tw', 'kr', 'eu', 'www'))){
	$lo				= 'cn';
}
if(!in_array($langType, array('zh-cn', 'en','de_de','es','fr_fr','ko_kr','zh-tw'))){
	$langType		= 'zh-cn';
}
$isphp5		= (phpversion() > "5");
$isItemName	= false;
if(empty($id)){
	if(!isset($iname) or empty($iname)){
		exit("Sorry, not found.");
	}
	$itemCacheFile		= CHINO_CACHEBASE."/item/item_".md5($iname).".xml";
	if(file_exists($itemCacheFile) and filesize($itemCacheFile)>0){
		echo(file_get_contents($itemCacheFile));
		exit;
	}else {
		//find item's id
		if($isphp5){
			$itemUrl	= "http://www.wowarmory.com/search.xml?searchQuery=".$iname."&searchType=items";
			$xmlstr		= fileGetContents($itemUrl, $langType);
			$xml		= new SimpleXMLElement($xmlstr);
			if(!$xml){
				exit("Sorry, not found.");
			}else {
				$id		= (string)$xml->armorySearch->searchResults->items->item['id'];
			}
		}else {
			$itemUrl	= "http://www.dkper.com/webservice/search.php?searchQuery=".$iname."&searchType=items&lng=".$langType;
			$id			= trim(file_get_contents($itemUrl));
		}
		$isItemName		= true;
	}
}
if(empty($id)){
	exit("Sorry, not found.");
}
if($isphp5) {
	$itemCacheFile		= CHINO_CACHEBASE."/item/".$id.".xml";
    if(file_exists($itemCacheFile) and filesize($itemCacheFile)>0){
        $xmlstr			= unserialize(file_get_contents($itemCacheFile));
    }else {
        $armory	        = new armory($lo, $langType);
        $xmlstr			= $armory->makeItemCache($id);
    }
    include CHINO_WWWROOT.'/aiTemplate.php';
    if (!$xmlstr) exit(0);
    $xml = new SimpleXMLElement($xmlstr);
	$icon				= (string)$xml->itemTooltips->itemTooltip->icon;
	$iconUrl			= "http://cn.wowarmory.com/images/icons/64x64/".$icon.".jpg";
	$imgFile			= "./images/icons/64x64/".$icon.".jpg";
	if(!file_exists($imgFile) or filesize($imgFile)<=0){
		$imgData	= file_get_contents($iconUrl);
		writeCache($imgFile, $imgData);
	}
    $con	= new itemInfo();
    $outstr	= $con->outputInfoBox($xml, "ai/", $langType, "languages/", "templates/", true);
	$result	= "<div class=\"ai\">".$outstr."</div>";
}else {
	$itemCacheFile		= CHINO_CACHEBASE."/item/dkper_".$id.".xml";
    $matches            = array();
    $pic                = array();
    if(file_exists($itemCacheFile) and filesize($itemCacheFile)>0){
        $result = file_get_contents($itemCacheFile);
    }else {
    	//http://www.dkper.com/webservice/item-tooltip.php?lng=zh-cn&i=19375
        $url    = "http://www.dkper.com/webservice/item-tooltip.php?lng=".$langType."&i=".$id;
        $result = file_get_contents($url);
        writeCache($itemCacheFile, $result);
        preg_match("/<img src=\"\.([^\"]*)\"/i",$result, $matches);
        preg_match("/\/([^\/]*\.jpg)$/i",$matches[1], $pic);
        if(!empty($matches)) {
            $imgPath    = CHINO_WWWROOT."/images/icons/64x64/".$pic[1];
        	$imgUrl     = "http://www.dkper.com/webservice".$matches[1];
            $imgData	= file_get_contents($imgUrl);
			writeCache($imgPath, $imgData);
        }
    }
}
echo($result);

if($isItemName){
	$itemCacheFile		= CHINO_CACHEBASE."/item/item_".md5($iname).".xml";
	writeCache($itemCacheFile, $result);
}

function writeCache($file, $data){
    $handle	= fopen($file, "w");
    if(!$handle or !is_writeable($file)){
        exit ("Please check ".$file." is writable.");
    }
    fwrite($handle, $data);
    fclose($handle);
}

class itemInfo {
	var $name;
	var $desc;
	var $icon;
	var $quality;
	var $inventoryType;
	var $bonding;
	var $equipName;
	var $bonus;
	var $armor;
	var $socket;
	var $socketBonus;
	var $durabilityMax;
	var $requiredLevel;
	var $spells;
	var $damageMin;
	var $damageMax;
	var $damageDps;
	var $damageSpeed;
	var $sourceArea;
	var $sourceName;
	var $sourceDroprate;
	var $setName;
	var $itemAttributes;
	var $setItems;
	var $bonusAttributes;
	var $set_bonus;
	var $armoryPath	= './';
	var $imagePath	= 'images/';
	var $id;
	var $tpl;


	function & parseXml($xml) {
		$attributes = $xml->itemTooltips->itemTooltip;
		$this->id	= $id;
		// Name.
		if(isset($attributes->name)) {
			$this->name = (string)$attributes->name;
		}
		// Description.
		if(isset($attributes->desc)) {
			$this->desc = (string)$attributes->desc;
		}

		// Icon.
		if(isset($attributes->icon)) {
			$this->icon = (string)$attributes->icon;
		}
		// Quality.
		if(isset($attributes->overallQualityId)) {
			switch($attributes->overallQualityId) {
				case 0: $this->quality = 'poor'; break;
				case 1: $this->quality = 'common'; break;
				case 2: $this->quality = 'uncommon'; break;
				case 3: $this->quality = 'rare'; break;
				case 4: $this->quality = 'epic'; break;
				case 5: $this->quality = 'legendary'; break;
				case 6: $this->quality = 'artifact '; break;
			}
		}

		// Inventory type.
		$inventoryType = (int)$attributes->equipData->inventoryType;

		if(is_int($inventoryType)) {
			switch($inventoryType) {
				case 1: $this->inventoryType = 'head'; break;
				case 2: $this->inventoryType = 'neck'; break;
				case 3: $this->inventoryType = 'shoulders'; break;
				case 4: $this->inventoryType = 'shirt'; break;
				case 5: $this->inventoryType = 'chest'; break;
				case 6: $this->inventoryType = 'waist'; break;
				case 7: $this->inventoryType = 'legs'; break;
				case 8: $this->inventoryType = 'feet'; break;
				case 9: $this->inventoryType = 'wrist'; break;
				case 10: $this->inventoryType = 'hand'; break;
				case 11: $this->inventoryType = 'finger'; break;
				case 12: $this->inventoryType = 'trinket'; break;
				case 13: $this->inventoryType = 'one-hand'; break;
				case 14: $this->inventoryType = 'off hand'; break;
				case 15: $this->inventoryType = 'ranged'; break;
				case 16: $this->inventoryType = 'back'; break;
				case 17: $this->inventoryType = 'two-hand'; break;
				case 18: $this->inventoryType = 'bag'; break;
				case 19: $this->inventoryType = 'tabard'; break;
				case 20: $this->inventoryType = 'main hand'; break;
				case 21: $this->inventoryType = 'off hand'; break;
				case 22: $this->inventoryType = 'held in off hand'; break;
				case 23: $this->inventoryType = 'projectile'; break;
				case 24: $this->inventoryType = 'thrown'; break;
				case 25: $this->inventoryType = 'ranged'; break;
				case 26: $this->inventoryType = 'quiver'; break;
				case 27: $this->inventoryType = 'relic'; break;
				default: $this->inventoryType = 'non-equip';
			}
		}

		// Bonding.
		if(isset($attributes->bonding)) {
			switch($attributes->bonding) {
				case 0: $this->bonding = 'none'; break;
				case 1: $this->bonding = 'bop'; break;
				case 2: $this->bonding = 'boe'; break;
			}
		}

		// Equip type.
		if(isset($attributes->equipData->subclassName)) {
			$this->equipName = (string)$attributes->equipData->subclassName;
		}

		// Bonus.
		if(isset($attributes->bonusAgility)) {
			$this->bonus['agility'] = (int)$attributes->bonusAgility;
		}
		if(isset($attributes->bonusStamina)) {
			$this->bonus['stamina'] = (int)$attributes->bonusStamina;
		}
		if(isset($attributes->bonusIntellect)) {
			$this->bonus['intellect'] = (int)$attributes->bonusIntellect;
		}
		if(isset($attributes->bonusSpirit)) {
			$this->bonus['spirit'] = (int)$attributes->bonusSpirit;
		}
		if(isset($attributes->bonusStrength)) {
			$this->bonus['strength'] = (int)$attributes->bonusStrength;
		}

		// Armor.
		if(isset($attributes->armor)) {
			$this->armor = (int)$attributes->armor;
		}

		// Sockets.
		if(isset($attributes->socketData->socket)) {
			// Go thru all sockets.
			foreach($attributes->socketData->socket as $socket) {
			// Attributes of the socket XML element.
			$socket = (string)$socket->attributes();
			// Increment socket number of sockets in this color.
			switch($socket) {
				case 'Red': $this->sockets['red']++; break;
				case 'Blue': $this->sockets['blue']++; break;
				case 'Yellow': $this->sockets['yellow']++; break;
				case 'Meta': $this->sockets['meta']++; break;
			}
		  }
		}

		// Socket bonus.
		if(isset($attributes->socketData->socketMatchEnchant)) {
			$this->socketBonus = (string)$attributes->socketData->socketMatchEnchant;
		}

		// Durability.
		if(isset($attributes->durability)) {
			foreach($attributes->durability->attributes() as $attribute => $value) {
				if($attribute == 'current') $this->durabilityCur = (int)$value;
				else if($attribute == 'max') $this->durabilityMax = (int)$value;

			}
		}

		// Required level.
		if(isset($attributes->requiredLevel)) {
			$this->requiredLevel = (int)$attributes->requiredLevel;
		}

		// Spells.
		if(isset($attributes->spellData->spell)) {
			foreach($attributes->spellData->spell as $spell) {
			$this->spells[] = (string)$spell->desc;
			}
		}

		// Damage minimum.
		if(isset($attributes->damageData->damage->min)) {
			$this->damageMin = (int)$attributes->damageData->damage->min;
		}

		// Damage maximum.
		if(isset($attributes->damageData->damage->max)) {
			$this->damageMax = (int)$attributes->damageData->damage->max;
		}

		// Damage dps.
		if(isset($attributes->damageData->dps)) {
			$this->damageDps = round((float)$attributes->damageData->dps, 1);
		}

		// Damage speed.
		if(isset($attributes->damageData->speed)) {
			$this->damageSpeed = (float)$attributes->damageData->speed;
		}

		// Source is given.
		if(isset($attributes->itemSource)) {
			// Go thru every source attribute.
			foreach($attributes->itemSource->attributes() as $key => $value) {
				// Area name.
				if($key == 'areaName') {
					$this->sourceArea = (string)$value;
				// Boss name.
				} else if($key == 'creatureName') {
					$this->sourceName = (string)$value;
				// Drop rate.
				} else if($key == 'dropRate') {
					switch((int)$value) {
						case 1: $this->sourceDroprate = 'damn bad'; break;
						case 2: $this->sourceDroprate = 'bad'; break;
						case 3: $this->sourceDroprate = 'mid'; break;
						case 4: $this->sourceDroprate = 'good'; break;
						case 5: $this->sourceDroprate = 'very good'; break;
					}
				}
			}
		}

		// Set bonus.
		if(isset($attributes->setData->name)) {
			$this->setName = (string)$attributes->setData->name;
		}

		// Set items.
		if(isset($attributes->setData->item) && is_array($attributes->setData->item)) {
			// Go thru every element in setData.
			foreach($attributes->setData->item as $item) {
				$itemAttributes = $item->attributes();
				$this->setItems[] = (string)$itemAttributes['name'];
			}
		}

		// Set bonus.
		if(isset($attributes->setData->setBonus) && is_array($attributes->setData->setBonus)) {
			// Go thru every element in setData.
			foreach($attributes->setData->setBonus as $bonus) {
				$bonusAttributes = $bonus->attributes();
				$this->set_bonus[] = array('descr' => (string)$bonusAttributes['desc'], 'threshold' => (int)$bonusAtributes['threshold']);
			}

		}
	}


	function outputInfoBox(&$xml, $aiPath, $language, $languagePath, $templatePath, $showErrors) {
		// Namespace for the template class.
		$this->parseXml($xml);
		$tpl = new aiTemplate($aiPath, $language, $languagePath, $templatePath, $showErrors);
		// Load  the language file.
		$tpl->LoadLanguage();
		// If an item id ist set.
		// Set the item informations as template vars.
		// Some times it fetch other templtes for this method.
		// Name.
		$tpl->SetVar('name', $this->name);
		// Quality.
		$tpl->SetVar('quality', $this->quality);
		// Icon.
		$tpl->SetVar('icon', $this->armoryPath.$this->imagePath.'icons/64x64/'.$this->icon.'.jpg');
		// Bonding.
		if($this->bonding == 'none') {
			$tpl->SetVar('bonding', '');
		} else {
			$tpl->SetVar('bonding', $tpl->GetLanguage($this->bonding));
			$tpl->SetVar('bonding', $tpl->TplFetch('bonding'));
		}

		// Inventory type.
		if($this->inventoryType == 'non-equip') {
			$tpl->SetVar('inventoryType', '');
		} else {
			$tpl->SetVar('inventoryType', $tpl->GetLanguage($this->inventoryType));
			$tpl->SetVar('inventoryType', $tpl->TplFetch('inventoryType'));
		}

		// Equip name.
		if(is_string($this->equipName) && $this->equipName != '') {
			$tpl->SetVar('equipName', $this->equipName);
			$tpl->SetVar('equipName', $tpl->TplFetch('equipName'));
		} else {
			$tpl->SetVar('equipName', '');
		}

		// Armor.
		if(is_int($this->armor)) {
			$tpl->SetVar('armor', $this->armor.' '.$tpl->GetLanguage('armor'));
			$tpl->SetVar('armor', $tpl->TplFetch('armor'));
		} else {
			$tpl->SetVar('armor', '');
		}

		// Attributes / Item bonus.
		if(is_array($this->ItemBonus())) {
			// Set temporary variable.
			$attributes = $this->ItemBonus();
			// Fetch template.
			$TmpTpl = $tpl->TplFetch('attributesItems');
			// Output of attributes.
			$output = '';
			// Go thru every attribute.
			foreach($attributes as $attr => $val) {
				// Only if val > 0.
				if($val > 0) {
					$tpl->SetVar('attributesItems', '+'.$val.' '.$tpl->GetLanguage($attr));
					$output .= $tpl->TplParse($TmpTpl)."\n";
				}
			}

			// Set tpl variables.
			$tpl->SetVar('attributesItems', $output);
			$tpl->SetVar('attributeDescr', $tpl->GetLanguage('attributes'));
			$tpl->SetVar('attriutes', $tpl->TplFetch('attributes'));
			// No item bonus.
			} else {
				$tpl->SetVar('attriutes', '');
			}

			// Socket.
			$sockets = $this->ItemSockets();
			if(is_array($sockets) && ($sockets['red'] > 0 || $sockets['blue'] || $sockets['yellow'] || $sockets['yellow'])) {
				// Fetch template.
				$TmpTpl = $tpl->TplFetch('socketsItems')."\n";
				// Output of sockets.
				$output = '';
				// Go thru every socket.
				foreach($sockets as $sock => $val) {
				// Repeat so often like the number of val.
				for($i = 0; $i < $val; $i++) {
					$tpl->SetVar('socketType', $sock);
					$tpl->SetVar('socketName', $tpl->GetLanguage($sock.'Sock'));
					$output .= $tpl->TplParse($TmpTpl)."\n";
				}
			}

			// Socket bonus.
			if($this->socket_bonus) {
				$tpl->SetVar('socketType', 'noIcon');
				$tpl->SetVar('socketName', $tpl->GetLanguage('socketBonus').': '.$this->socket_bonus);
				$output .= $tpl->TplParse($TmpTpl)."\n";
			}

			// Set tpl variable.
			$tpl->SetVar('socketsItems', $output);
			$tpl->SetVar('socketsDescr', $tpl->GetLanguage('sockets'));
			$tpl->SetVar('sockets', $tpl->TplFetch('sockets'));
			// No sockets.
		} else {
			$tpl->SetVar('sockets', '');
		}

		// Required level is bigger than 0.
		if(is_int($this->requiredLevel) && $this->requiredLevel > 0) {
			$tpl->SetVar('requiredLevel', $tpl->GetLanguage('requiredLevel').' '.$this->requiredLevel);
			$tpl->SetVar('requiredLevel', $tpl->TplFetch('requiredLevel'));
		// Not int or 0.
		} else {
			$tpl->SetVar('requiredLevel', '');
		}

		// Spells.
		$spells = $this->spells;
		if(is_array($spells) && count($spells) > 0) {
			// Fetch template.
			$TmpTpl = $tpl->TplFetch('spellsItems')."\n";
			// Output of sockets.
			$output = '';
			// Go thru every spell.
			foreach($spells as $spell) {
				$tpl->SetVar('spellsItems', $tpl->GetLanguage('equip').': '.$spell);
				$output .= $tpl->TplParse($TmpTpl)."\n";
			}
			// set tpl var.
			$tpl->SetVar('spellsItems', $output);
			$tpl->SetVar('spells', $tpl->TplFetch('spells'));
		// No spels.
		} else {
			$tpl->SetVar('spells', '');
		}

		// Item set name.
		if(is_string($this->setName) && $this->setName != '') {
			$tpl->SetVar('set', $this->setName);
			$tpl->SetVar('set', $tpl->TplFetch('set'));
		// No set.
		} else {
			$tpl->SetVar('set', '');
		}

	  // Durability.
		if(is_int($this->durabilityCur) && is_int($this->durabilityMax)) {
			$tpl->SetVar('durabilityDesc', $tpl->GetLanguage('durability'));
			$tpl->SetVar('durabilityCur', $this->durabilityCur);
			$tpl->SetVar('durabilityMax', $this->durabilityMax);
			$tpl->SetVar('durability', $tpl->TplFetch('durability'));
		// No durability.
		} else {
			$tpl->SetVar('durability', '');
		}

		// Damage.
		if(is_int($this->damageMin) && is_int($this->damageMax) && is_float($this->damageDps)  && is_float($this->damageSpeed)) {
			$tpl->SetVar('dmgMin', $this->damageMin);
			$tpl->SetVar('dmgMax', $this->damageMax);
			$tpl->SetVar('dmgSpeed', $this->damageSpeed);
			$tpl->SetVar('dmgDps', $this->damageDps);
			$tpl->SetVar('dmgDesc', $tpl->GetLanguage('damage'));
			$tpl->SetVar('dmgDpsDesc', $tpl->GetLanguage('dps'));
			$tpl->SetVar('dmgSpeedDesc', $tpl->GetLanguage('damageSpeed'));
			$tpl->SetVar('damage', $tpl->TplFetch('damage'));
		// No damage informations.
		} else {
			$tpl->SetVar('damage', '');
		}
		// Return trimed and tabbed html.
		return $tpl->TplFetch('content');
	}

	/**
	* Return the item bonus.
	* All (default) or only agility/stamina/intellect/spirit/strength
	*
	* @return integer/array Item bonus
	* @access public
	* @see $bonus
	*/
	function ItemBonus($type='all') {
		// All bonus.
		if($type == 'all') {
			return array(
			'agility' => $this->bonus['agility'],
			'stamina' => $this->bonus['stamina'],
			'intellect' => $this->bonus['intellect'],
			'spirit' => $this->bonus['spirit'],
			'strength' => $this->bonus['strength']
		);
		// Agility bonus.
		} else if($type == 'agility') {
			if(is_int($this->bonus['agility'])) {
				return $this->bonus['agility'];
			} else {
				return false;
			}
		// Stamina bonus.
		} else if($type == 'stamina') {
			if(is_int($this->bonus['stamina'])) {
				return $this->bonus['stamina'];
			} else {
				return false;
			}
		// Intellect bonus.
		} else if($type == 'intellect') {
			if(is_int($this->bonus['intellect'])) {
				return $this->bonus['intellect'];
			} else {
				return false;
			}
		// Spirit bonus.
		} else if($type == 'spirit') {
			if(is_int($this->bonus['agility'])) {
				return $this->bonus['agility'];
			} else {
			return false;
		}
		// Strength bonus.
		} else if($type == 'strength') {
			if(is_int($this->bonus['strength'])) {
				return $this->bonus['strength'];
			} else {
				return false;
			}
		// Else it's an unknow bonus, return false.
		} else {
			return false;
		}
	}

	/**
	* Return the number of sockets.
	* All (default) or only of the color red/blue/yellow/meta.
	*
	* @return integer Item sockets
	* @access public
	* @see $sockets
	*/
	function ItemSockets($type='all') {
		// All sockets.
		if($type == 'all') {
			return $this->sockets;
		// Red sockets.
		} else if($type == 'red') {
			return $this->sockets['red'];
		// Blue sockets.
		} if($type == 'blue') {
			return $this->sockets['blue'];
		// Yellow sockets.
		} if($type == 'yellow') {
			return $this->sockets['yellow'];
		// Meta sockets.
		} if($type == 'meta') {
			return $this->sockets['meta'];
		// Else return false.
		} else {
			return false;
		}
	}

}

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
	return file_get_contents($url, false, $options);
}

?>
