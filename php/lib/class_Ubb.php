<?php
/*
Text Encode Class
Write by q3boy 2003.3.10
usage:

$eq = new Ubb($str);初始化类

//以下为ubbEncode参数
$eq->url      = true;       //启用url自动解析   默认false
$eq->html     = true;       //启用HTML编码（处理<，>，全角/半角空格，制表符，换行符）默认true
$eq->image    = true;       //启用图象标签解析  默认true
$eq->font     = true;       //启用字体标签解析  默认true
$eq->element  = true;       //启用外部元素解析  默认true
$eq->flash    = true;       //启用Flash解析     默认true
$eq->php      = true;       //启用语法高亮显示  默认true
//ubbEncode参数结束

echo($eq->getImageOpener());//输出图片自动缩放所需js函数
echo $eq->htmlEncode();          //输出ubb编码后字符串
echo"<hr>";
echo $eq->ubbEncode();           //输出ubb编码后字符串
echo"<hr>";
echo $eq->removeHtml();          //输出移除html标签的字符串
echo"<hr>";
echo $eq->ubbEncode();           //输出移除ubb标签的字符串


支持ubb标签列表：

图片类：
[img]http://www.aaa.com/aaa.gif[/img]    插入图片
[limg]http://www.aaa.com/aaa.gif[/limg]  图片左绕排
[rimg]http://www.aaa.com/aaa.gif[/rimg]  图片右绕排
[cimg]http://www.aaa.com/aaa.gif[/cimg]  图片居中绕排

文本控制类：
[br] 换行符
[b]粗体字[b]
[i]斜体字[i]
[u]下划线[u]
[s]删除线[s]
[sub]文字下标[sub]
[sup]文字上标[sup]
[left]文字左对齐[left]
[right]文字右对齐[right]
[center]文字居中[center]
[align=(left|center|right)]文字对齐方式[align]
[size=([1-6])]文字大小[size]
[font=(字体)[font]
[color=(文字颜色)][color]
[list]无序列表[list]
[list=s]有序列表[list]
[list=(A|1|I)]有序列表（列表方式为（abc,123,I II III））[list]
[list=(num)]有序列表（自num开始计数）[list]
[li]列表单元项[li]

外部元素类：
[url]链接[/url]
[url=(链接)]链接文字[/url]
[email]邮件地址[/email]
[email=(邮件地址)]说明文字[/email]邮件地址
[quote]引用块[/quote]
[swf]flash动画地址[/swf]
[swf=宽度,高度]flash动画地址[/swf]

代码块:
[code][/code]
[php][/php]
[code 代码块名称][/code]
[php 代码块名称][/php]

如需使用php语法高亮请务必在代码块两端加上"<??>"标签
*/

class Ubb {
	var $str           = "";
	var $iconpath      = "http://web.iciba.com/sl/editor/face";//图标文件路径
	var $imagepath     = "upfiles/article";//图片文件默认路径
	var $tagfoot = ' border="0" onload="ImageLoad(this);" onClick="ImageOpen(this)" style="cursor: hand;zoom:0.1;" ';//图片文件附加属性

	var $media     = true;	//媒体文件解析
	var $url     = false;	//url自动解析
	var $html    = true;		//HTML编码
	var $smile   = true;		//解析表情
	var $image   = true;		//解析图象标签
	var $font    = true;		//字体标签
	var $element = true;		//外部元素
	var $flash   = true;		//Flash
	var $php     = true;		//语法高亮显示

	function Ubb($str='',$imgph='') {
		if($str) {
			$str = strtr($str,array("\n\r"=>"\n","\r\n"=>"\n","\r"=>"\n",""=>"　"));

            $pattern = "[\";&\$#`\),'\[\(|\)]+";

            $pattern = "[^\[]*" . $pattern . "[^\[]*";

            $p1 = "/\[[lrc]?img\]" . $pattern . "\[\/[lrc]?img\]/is";
            $str = preg_replace($p1, "", $str);

            $p2 = "/\[(url|email|swf)[^]]*\]" . $pattern . "\[\/(url|email|swf)\]/";
            $str = preg_replace($p2, "", $str);

            $p3 = "/\[(url|email)=" . $pattern . "\][^\[]*\[\/(url|email)\]/";
            $str = preg_replace($p3, "", $str);
            
            $this->str = $str;
		}
		if($imgph) $this->imagepath = $imgph;
	}

	function setStr($str='',$imgph='') {
		if($str) {
			$str = strtr($str,array("\n\r"=>"\n","\r\n"=>"\n","\r"=>"\n",""=>"　"));

            $pattern = "[\";\$#`\),'~\[\(|\)]+";

            $pattern = "[^\[]*" . $pattern . "[^\[]*";

            $p1 = "/\[[lrc]?img\]" . $pattern . "\[\/[lrc]?img\]/is";
            $str = preg_replace($p1, "", $str);

            $p2 = "/\[(url|email|swf)[^]]*\]" . $pattern . "\[\/(url|email|swf)\]/";
            $str = preg_replace($p2, "", $str);

            $p3 = "/\[(url|email)=" . $pattern . "\][^\[]*\[\/(url|email)\]/";
            $str = preg_replace($p3, "", $str);
            
            $this->str = $str;
		}
		if($imgph) $this->imagepath = $imgph;
	}

    
    function getImageOpener() {
		Return "<script language=\"javascript\" type=\"text/javascript\">\r\nfunction ImageLoad(img) {\r\nimg.style.zoom='1';\r\nif(img.width>600) img.width=600;\r\n}\r\nfunction ImageOpen(img) {\r\nwindow.open(img.src,'','menubar=no,scrollbars=yes,width='+(screen.width-8)+',height='+(screen.height-74)+',left=0,top=0');\r\n}\r\n</script>";
	}

	function removeHtml($str='') {
		if(!$str) $str = $this->str;
		return strip_tags($str);
	}

	function removeUbb($str='') {
		if(!$str) $str = $this->str;
		return preg_replace("/\[\/?\w+(\s+[^\]\s]+)*\s*\]/is","",$str);
	}

	function htmlEncode($str='') {
		if(!$str) $str = $this->str;
		$ary = array(
			'<'=>'&lt;',
			'>'=>'&gt;',
			"  "=>"&nbsp;&nbsp;",
			);
		$str = preg_replace("/\n{2,}/s","\n\n",strtr($str,$ary));
		Return str_replace("\n","\n<br />",$str);
	}
	function ubbEncode($str='') {
        $reg_ary = array();
		if(!$str) $str = $this->str;
		$rpl_ary = array();
		$rpl_ary = array();
		if($this->html) $str = $this->htmlEncode($str,true);
		$tagfoot = $this->tagfoot;
		$icon    = $this->iconpath;
		$image   = $this->imagepath;
        if($this->media) {
			$reg_ary = array_merge($reg_ary,array(
                '/\[wmv=(\d+)\,(\d+)\]\s*(.+?)\s*\[\/wmv\]/i',
                '/\[real=(\d+)\,(\d+)\]\s*(.+?)\s*\[\/real\]/i',
                '/\[wmv\]\s*([^\[\s]+)\s*\[\/wmv\]/i',
                '/\[real\]\s*([^\[\s]+)\s*\[\/real\]/i',
                '/\[mp3\]\s*([^\[\s]+)\s*\[\/mp3\]/i',
			));
			$rpl_ary = array_merge($rpl_ary,array(
                '<object classid="CLSID:22d6f312-b0f6-11d0-94ab-0080c74c7e95" class="OBJECT" id="MediaPlayer" width="\\1" height="\\2" ><param name="ShowStatusBar" value="-1"><param name="Filename" value="\\3"><embed type="application/x-oleobject" codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=5,1,52,701" flename="wmv" src="\\3" width="\\1" height="\\2"></embed></object>',
                '<br /><OBJECT ID="RealPlayer" CLASSID="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA" HEIGHT="\\2" WIDTH="\\1" class="border"><PARAM NAME="controls" VALUE="ImageWindow"><PARAM NAME="console" VALUE="audio-mtv"><PARAM NAME="autostart" VALUE="-1"><PARAM NAME="src" VALUE="\\3"><param name="_ExtentX" value="9313"><param name="_ExtentY" value="6350"><param name="SHUFFLE" value="0"><param name="PREFETCH" value="0"><param name="NOLABELS" value="0"><param name="LOOP" value="0"><param name="NUMLOOP" value="0"><param name="CENTER" value="0"><param name="MAINTAINASPECT" value="0"><param name="BACKGROUNDCOLOR" value="#000000"><EMBED SRC="\\1" type="audio/x-pn-realaudio-plugin" CONSOLE="net-audio-mtv" CONTROLS="ImageWindow" HEIGHT=288 WIDTH="\\1" AUTOSTART=ture></OBJECT><br><OBJECT ID="RealPlayer" CLASSID="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA" HEIGHT=90 WIDTH="\\1"><PARAM NAME="controls" VALUE="all"><PARAM NAME="console" VALUE="audio-mtv"><param name="_ExtentX" value="9313"><param name="_ExtentY" value="2381"><param name="AUTOSTART" value="0"><param name="SHUFFLE" value="0"><param name="PREFETCH" value="0"><param name="NOLABELS" value="0"><param name="LOOP" value="0"><param name="NUMLOOP" value="0"><param name="CENTER" value="0"><param name="MAINTAINASPECT" value="0"><param name="BACKGROUNDCOLOR" value="#000000"><EMBED type="audio/x-pn-realaudio-plugin" CONSOLE="Chinawj_net-audio-mtv" CONTROLS="all" HEIGHT=90 WIDTH="\\1" AUTOSTART=false></EMBED></OBJECT>',
                '<object classid="CLSID:22d6f312-b0f6-11d0-94ab-0080c74c7e95" class="OBJECT" id="MediaPlayer" width="500" height="350" ><param name="ShowStatusBar" value="-1"><param name="Filename" value="\\1"><embed type="application/x-oleobject" codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=5,1,52,701" flename="wmv" src="\\1" width="500" height="350"></embed></object>',
                '<OBJECT ID="mtv" CLASSID="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA" HEIGHT=240 WIDTH=352 class="border"><PARAM NAME="controls" VALUE="ImageWindow"><PARAM NAME="console" VALUE="audio-mtv"><PARAM NAME="autostart" VALUE="-1"><PARAM NAME="src" VALUE="\\1"><param name="_ExtentX" value="9313"><param name="_ExtentY" value="6350"><param name="SHUFFLE" value="0"><param name="PREFETCH" value="0"><param name="NOLABELS" value="0"><param name="LOOP" value="0"><param name="NUMLOOP" value="0"><param name="CENTER" value="0"><param name="MAINTAINASPECT" value="0"><param name="BACKGROUNDCOLOR" value="#000000"><EMBED SRC="\\1" type="audio/x-pn-realaudio-plugin" CONSOLE="Chinawj_net-audio-mtv" CONTROLS="ImageWindow" HEIGHT=288 WIDTH=352 AUTOSTART=ture></OBJECT><br><OBJECT ID=mtv CLASSID="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA" HEIGHT=90 WIDTH=352><PARAM NAME="controls" VALUE="all"><PARAM NAME="console" VALUE="audio-mtv"><param name="_ExtentX" value="9313"><param name="_ExtentY" value="2381"><param name="AUTOSTART" value="0"><param name="SHUFFLE" value="0"><param name="PREFETCH" value="0"><param name="NOLABELS" value="0"><param name="LOOP" value="0"><param name="NUMLOOP" value="0"><param name="CENTER" value="0"><param name="MAINTAINASPECT" value="0"><param name="BACKGROUNDCOLOR" value="#000000"><EMBED type="audio/x-pn-realaudio-plugin" CONSOLE="Chinawj_net-audio-mtv" CONTROLS="all" HEIGHT=90 WIDTH=275 AUTOSTART=false></EMBED></OBJECT>',
                '<embed name="rplayer" type="audio/x-pn-realaudio-plugin" src="\\1" controls="ControlPanel,StatusBar" width=460 height=68 border=0 autostart=true loop=true></embed>',
			));            
        }
		if($this->smile) {
			$reg_ary = array_merge($reg_ary,array(
				'/\[(em[0-9]+)\]/i',
			));
			$rpl_ary = array_merge($rpl_ary,array(
                '<img src="' . $icon . '/\\1.gif" onerror="this.src=\'' . $icon . '/em01.gif\'">',
			));
		}
		if($this->php) {
			preg_match_all('/(\n\<br \/\>)*\[(php|code)\s*(.*?)\]\s*(.+?)\s*\[\/(php|code)\](\n\<br \/\>)*/is',$str,$ary);
			$str = preg_split('/(\n\<br \/\>)*\[(php|code)\s*(.*?)\]\s*(.+?)\s*\[\/(php|code)\](\n\<br \/\>)*/is',$str);
		}
		if($this->image) {
			$reg_ary = array_merge($reg_ary,array(
			'/\[img\]\s*http(s?):\/\/([^\[\s]+)\s*\[\/img\]/i',
			'/\[limg\]\s*http(s?):\/\/([^\[\s]+)\s*\[\/limg\]/i',
			'/\[rimg\]\s*http(s?):\/\/([^\[\s]+)\s*\[\/rimg\]/i',
			'/\[cimg\]\s*http(s?):\/\/([^\[\s]+)\s*\[\/cimg\]/i',
			'/\[img\]\s*([^\[\s]+)\s*\[\/img\]/i',
			'/\[limg\]\s*([^\[\s]+)\s*\[\/limg\]/i',
			'/\[rimg\]\s*([^\[\s]+)\s*\[\/rimg\]/i',
			'/\[cimg\]\s*([^\[\s]+)\s*\[\/cimg\]/i',
			));
			$rpl_ary = array_merge($rpl_ary,array(
			'<img src="http\1://\2"'.$tagfoot.'><br />',
			'<img src="http\1://\2"'.$tagfoot.' align="left">',
			'<img src="http\1://\2"'.$tagfoot.' align="right">',
			'<div align="center"><img src="http\1://\2"'.$tagfoot.'></div>',
			'<img src="\1"'.$tagfoot.'><br />',
			'<img src="\1"'.$tagfoot.' align="left">',
			'<img src="\1"'.$tagfoot.' align="right">',
			'<div align="center"><img src="\1"'.$tagfoot.'></div>',
			));
		}
		if($this->font) {
			$reg_ary = array_merge($reg_ary,array(
			'/\[br\]/i',
			'/\[b\]\s*(.+?)\s*\[\/b\]/is',
			'/\[i\]\s*(.+?)\s*\[\/i\]/is',
			'/\[u\]\s*(.+?)\s*\[\/u\]/is',
			'/\[s\]\s*(.+?)\s*\[\/s\]/is',
            '/\[sub\]\s*(.+?)\s*\[\/sub\]/is',
			'/\[sup\]\s*(.+?)\s*\[\/sup\]/is',
			'/\[left\]\s*(.+?)\s*\[\/left\]/is',
			'/\[right\]\s*(.+?)\s*\[\/right\]/is',
			'/\[center\]\s*(.+?)\s*\[\/center\]/is',
			'/\[align=\s*(left|center|right)\]\s*(.+?)\s*\[\/align\]/is',
			'/\[size=\s*([+-]?[\.|\d])\s*\]\s*(.*?)\s*\[\/size\]/is',
			'/\[font=\s*(.*?)\s*\]\s*(.*?)\s*\[\/font\]/is',
			'/\[color=\s*(.*?)\s*\]\s*(.*?)\s*\[\/color\]/is',
			'/\[list\]\s*(<br \/>)?\s*(.+?)\s*\[\/list\]/is',
			'/\[list=s\]\s*(<br \/>)?\s*(.+?)\s*\[\/list\]/is',
			'/\[list=(A|1|I)\]\\s*(<br \/>)?\s*(.+?)\s*\[\/list\]/is',
			'/\[list=([^\[\s]+?)\]\s*(<br \/>)?\s*(.+?)\s*\[\/list\]/is',
			'/\[li\]\s*(.+?)\s*\[\/li\]/is',
            '/\[fly\]\s*(.+?)\s*\[\/fly\]/is',
			));
			$rpl_ary = array_merge($rpl_ary,array(
			'<br />',
			'<b>\\1</b>',
			'<i>\\1</i>',
			'<u>\\1</u>',
			'<s>\\1</s>',
			'<sub>\\1</sub>',
			'<sup>\\1</sup>',
			'<div align="left">\\1</div>',
			'<div align="right">\\1</div>',
			'<div align="center">\\1</div>',
			'<div align="\\1">\\2</div>',
			'<font size=\\1pt;">\\2</font>',
			'<font face="\\1">\\2</font>',
			'<font color="\\1">\\2</font>',
			'<ul>\\2</ul>',
			'<ol>\\2</ol>',
			'<ol type="\\1">\\3</ol>',
			'<ol start="\\1">\\3</ol>',
			'<li>\\1</li>',
            '<marquee>\\1</marquee>',
			));
		}
        if($this->url){
			$reg_ary = array_merge($reg_ary,array(
                "/(?<=[^\]a-z0-9-=\"'\\/]|^)((https?|ftp|gopher|news|telnet|mms|rtsp):\/\/|www\.)([a-z0-9\/\-_+=.~!%@?#%&;:$\\()|]+)/i",
                '/(?!<!\]|\=[\'"]?)\s*(\w+@(?:\w+\.)+\w{2,3})\b(?!<|\[)/i',
			));
			$rpl_ary = array_merge($rpl_ary,array(
                "[url]\\1\\3[/url]",
                "[email]\\1[/email]",
			));
		}
 		if($this->element){
			$reg_ary = array_merge($reg_ary,array(
                '/\[url=\s*(.+?)\s*\]\s*(.+?)\s*\[\/url\]/i',
                '/\[url]\s*(.+?)\s*\[\/url\]/i',
                '/\[email=\s*(.+?)\s*\]\s*(.+?)\s*\[\/email\]/i',
                '/\[email]\s*(.+?)\s*\[\/email\]/i',
                '/\[quote\]\s*(<br \/>)?\s*(.+?)\s*\[\/quote\]/is',
   			));
			$rpl_ary = array_merge($rpl_ary,array(
                '<a href="\1" target="_blank">\2</a> ',
                '<a href="\1" target="_blank">\1</a> ',
                '<a href="mailto:\1">\2</a> ',
                '<a href="mailto:\1">\1</a> ',
                '<table cellpadding="0" cellspacing="0" border="0" width="90%" align="center" style="border:1px gray solid;"><tr><td><table width="100%" cellpadding="5" cellspacing="1" border="0"><tr><td width="100%">\2</td></tr></table></td></tr></table>',
			));
		}
		if($this->flash){
			$reg_ary = array_merge($reg_ary,array(
			'/\[swf\]\s*(.+?)\s*\[\/swf\]/i',
			'/\[swf=(\d+)\,(\d+)\]\s*(.+?)\s*\[\/swf\]/i'
			));
			$rpl_ary = array_merge($rpl_ary,array(
			'<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0"><param name="movie" value="\1" /><param name="quality" value="high" /><embed src="\1" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash"></embed></object>',
			'<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0" width="\1" height="\2"><param name="movie" value="\3" /><param name="quality" value="high" /><embed src="\3" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="\1" height="\2"></embed></object>'
			));
		}
		if(sizeof($reg_ary)&&sizeof($rpl_ary))$str = preg_replace($reg_ary,$rpl_ary,$str);
		if($this->php) {
			$tmp = $str[0];
			for($i=0; $i<sizeof($ary[4]); $i++) {
				$hightlight = highlight_string(trim(strtr($ary[4][$i],array('&lt;'=>'<','&gt;'=>'>',"&nbsp;"=>" ","<br />"=>""))), true);
				$tmp .= '<table border=1 cellpadding="0" cellspacing="0" style="border-collapse: collapse;width:580px;word-wrap: break-word; word-break: break-all;" bordercolor="#055AA0" width=95%><tr><td><code>'.(trim($ary[3][$i])?trim($ary[3][$i]):'代码片段:').'</code><br /><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td width="100%" class="code">'.$hightlight.'</td></tr></table></td></tr></table>'.$str[$i+1];
			}
			$str = $tmp;
			unset($tmp);
		}
		Return $str;
	}
}
/**
//ubb使用的类
$eq = new Ubb("[img]a.gif[/img][img]b.gif[/img]");//初始化类

//以下为ubbEncode参数
$eq->url      = true;       //启用url自动解析   默认false
$eq->html     = true;       //启用HTML编码（处理<，>，全角/半角空格，制表符，换行符）默认true
$eq->image    = true;       //启用图象标签解析  默认true
$eq->font     = true;       //启用字体标签解析  默认true
$eq->element  = true;       //启用外部元素解析  默认true
$eq->flash    = true;       //启用Flash解析     默认true
//$eq->media    = false;       //启用Flash解析     默认true
$eq->php      = true;       //启用语法高亮显示  默认true
//ubbEncode参数结束

echo $eq->getImageOpener();//输出图片自动缩放所需js函数
var_dump($eq->ubbEncode());           //输出ubb编码后字符串
*/
?>
