<?php
/**
+-----------------------------------------------------------------------+
* @copyright	2.0
* @author       张涛<zhangtao2@kingsoft.com> 
* @version      $Id: class_browser.php,v 1.0 zhangtao$
* @update		2006-4-8
+ @description	分页类[去掉db操作的逻辑]
+-----------------------------------------------------------------------+
*/

class browser{ 
   var $c_rows		= 30;   //要显示的行数 
   var $c_fields	= '';   //要显示的字段 
   var $c_error		= '';   //错误收集器 
   var $c_offset	= 0;   //分页显示的偏移量 
   var $c_totalpage	= 1;   //总页数 
   var $c_nowpage	= 1;      //当前页数 
   var $total		= '';      //结果集的总数 
   var $lang		= '';

   //初始化 
   function initialize($totalrows,$rows ='',$offset=''){ 
      //行数 偏移量 
	  if (!empty($rows)) $this->c_rows=$rows; 
      if (!empty($offset)) $this->c_offset=$offset; 
	  $this->total	= $totalrows;
      //计算总页数 
      $this->c_totalpage=ceil($this->total/$rows); 
      //计算当前页码 
      $this->c_nowpage=ceil(($offset+$rows)/$rows); 
	  $this->setLang('');
   }

   //文字
   function setLang($langArray='') {
	   if(!empty($langArray)) {
		   $this->lang	= $langArray;
	   }else {
		   $this->lang	= array("[First]",
								"[Backward]",
								"[Forward]",
								"[Last]",
								"Current",
								"Total",
								"Go to",
			   					"NO.",
								"",
								"GO");
	   }
   }

   /**
   翻页链接（风格可以自定） 
   $var_p:需要附加的参数
   $page_num是显示的页次数目，比如显示5个页码
   */
   function ActionPage($var_p,$page_num){ 
	  if($this->c_nowpage>$this->c_totalpage) $this->c_nowpage=$this->c_totalpage;
	  if($this->c_totalpage<=0){
		  return '';
	  }
	  $phpself	= $_SERVER['PHP_SELF'];
      $string="<form methed=GET name=PostAp action=$phpself?&$var_p>";
	  $array_user_var=explode("&",$var_p);
	  foreach($array_user_var as $p_name => $p_value){
		  $array_user_p=explode("=",$p_value);
		  $string.="<input type=hidden name=".$array_user_p[0]." value=".$array_user_p[1].">";
	  }
	  
      $pre_page=$this->c_offset-$this->c_rows; 
      $nex_page=$this->c_offset+$this->c_rows; 
	  $string.="<a href=?offset=0&$var_p>".$this->lang[0]."</a>&nbsp;|&nbsp;";
      if($pre_page>=0)
            $string.="<a href=?offset=$pre_page&$var_p>".$this->lang[1]."</a>|"; 
	  //显示页码
	  $end_page_num=ceil($this->c_nowpage/$page_num)*$page_num;
	  if($end_page_num>$this->c_totalpage)
		  $end_page_num=$this->c_totalpage;
	  if($end_page_num<0)
		  $end_page_num=$page_num;
	  for($i=$end_page_num-$page_num+1;$i<=$end_page_num;$i++){
		  if($i<0)
			  $i=$this->c_nowpage;
		  if($i===$this->c_nowpage)
			  $string.="&nbsp;<b>[".$i."]</b>";
		  else
			  $string.="&nbsp;<a href=?offset=".($i-1)*$this->c_rows."&$var_p>".$i."</a>";
	  }
      if ($nex_page<$this->total && $this->total!=0)
         $string.="&nbsp;<a href=?offset=$nex_page&$var_p>".$this->lang[2]."</a>"; 
	  $string.="&nbsp;|&nbsp;<a href=?offset=".($this->c_totalpage-1)*$this->c_rows."&$var_p>".$this->lang[3]."</a>";
      $string.="&nbsp;&nbsp;".$this->lang[4].":" .$this->c_nowpage."/".$this->c_totalpage."&nbsp;".$this->lang[5].":".$this->total."&nbsp;".$this->lang[6]." "; 
	  //生成下拉列表
      //2008-9-18   翻页链接显示20条
      $startPage    = $this->c_nowpage-10;
      $endPage      = $this->c_nowpage+10;
      $startPage    = $startPage < 1?0:$startPage;
      $startPage    = $startPage > $this->c_totalpage?$this->c_totalpage:$startPage;
      $endPage      = $endPage < 1?0:$endPage;
      $endPage      = $endPage > $this->c_totalpage?$this->c_totalpage:$endPage;
	  if($this->c_totalpage > 0){
		  $string.="<select name=offset>";
		  for($i=$startPage;$i < $endPage;){
			  if($this->c_offset == $i*$this->c_rows) {
			  		$string.="<option value=".$i*$this->c_rows." selected>".$this->lang[7]." ".++$i.$this->lang[8];
			  }else {
					$string.="<option value=".$i*$this->c_rows.">".$this->lang[7]."".++$i.$this->lang[8];
			  }			  
		  }
		  $string.="</select>&nbsp;<input type=submit value='".$this->lang[9]."'></form>";
	  }
      return $string; 
   } 

} 
//END 

////例子 
//if(empty($_GET[offset])) $_GET[offset]=0;
//$gggg=new browser(); 
//$gggg->initialize(100,10,$_GET[offset]);   //初始化 
//$var_parameter="sort=sell";
//$tempvar=$gggg->ActionPage($var_parameter,10);   //显示分页符 
//echo $tempvar."<br>"; 



?>