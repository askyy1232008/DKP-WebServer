var currFocus;
var ExistAttaInfo = new Array();
var oldDelAttas = new Array();
var attaIdx = 1;
var IsIE;
if (navigator.userAgent.indexOf("MSIE") != -1){
	IsIE = true;
}else{
	IsIE = false;
}

//search,全局变量
//-----------------------------------------------------------------------------------------------------------
function IsIEBrowser() {
	if (navigator.userAgent.indexOf("MSIE") != -1) {
		return true;
		} else {
		return false;
		}
}

// 增加附件函数 ()，增加到 idfilespan,基数为 attaIdx 。
function add() {

	addfile("idfilespan",attaIdx);
	attaIdx++;
	return false;
}

//----------------------------------------fileexist()----------------------------------------------------------

//added by alun
function getfilename( attaName ) {
	var s = attaName.lastIndexOf( '\\' );
	return attaName.substr(s+1, attaName.length - s -1);
}
//added by alun
function existFile( file)
{
	var form = document.sendmail;
	for ( var i= 0 ; i < form.elements.length ; i ++ ) {				    
		if ( form.elements[i].type == "text" && form.elements[i].name != file.name ) {
			if ( file.value == form.elements[i].value ) {
				return true;
			}
		}
	}//for
	for (var i=0; i<ExistAttaInfo.length; i++) {
		var theName = ExistAttaInfo[i];
		if ( theName != null && theName != "" && theName == getfilename(file.name) ) {
			return true;
		}
	}
	return false;
}
//----------------------------------------addfile(spanId,index)----------------------------------------------
function addfile(spanId,index)
{
       var strIndex = "" + index;
	   var fileId = "attachfile"+ strIndex;
	   var brId = "idAttachBr" + strIndex;


	   addInputFile(spanId,fileId);
	   adddel(spanId,index);
	   //alert(spanId+'+'+index);

	   addbr(spanId,brId);
	   //document.getElementById( "attachfile"+ strIndex).click();
	   return;
}
//-------------------------------------------sub function----------------------------------------------------
function addInputFile(spanId,fileId)
{
	  var span = document.getElementById(spanId);
	  if ( span !=null ) {
	                if ( !IsIE ) {
						var fileObj = document.createElement("input");
						if ( fileObj != null ) {
							fileObj.type="text";
							fileObj.name = fileId;
							fileObj.id = fileId;
							fileObj.size="20";

							span.appendChild(fileObj);
						}//if fileObj
					}// !IsIE

					if ( IsIE ) {
						var fileTag = "<input type='text' id ='" + fileId + "' name='" + fileId + "' size=20>";
						var fileObj = document.createElement(fileTag); 
						span.appendChild(fileObj);
					}//IsIE if
			
	  }//if span
}

function addVal(spanId,fileId)
{
	  var span = document.getElementById(spanId);
	  fileId	= fileId+'Val';
	  if ( span !=null ) {
	                if ( !IsIE ) {
						var fileObj = document.createElement("input");
						if ( fileObj != null ) {
							fileObj.type="text";
							fileObj.name = fileId;
							fileObj.id = fileId;
							fileObj.size="10";

							span.appendChild(fileObj);
						}//if fileObj
					}// !IsIE

					if ( IsIE ) {
						var fileTag = "<input type='text' id ='" + fileId + "' name='" + fileId + "' size=10>";
						var fileObj = document.createElement(fileTag); 
						span.appendChild(fileObj);
					}//IsIE if
			
	  }//if span
}

function addbr(spanId,brId)
{
	  var span = document.getElementById(spanId);
	  if ( span !=null ) {
			var brObj = document.createElement("br");
			if ( brObj !=null ) {
				brObj.name = brId;
				brObj.id = brId;
				span.appendChild(brObj);
            }//if
     }//if
	 return;
}
function addInfo(spanId,index,info,tab)
{
      var strIndex = "" + index;
	  var delId = "idAttachOper" +tab+ strIndex;
	  var span = document.getElementById(spanId);
	  if ( span != null ) {
			var oTextNode = document.createElement("SPAN");
			oTextNode.style.width = "5px";
			span.appendChild(oTextNode);
			//alert(delId);
		    if ( IsIE ) {
	        var tag = "<input type='button' style='height:23px;padding-top:1px;' value="+info+" id="+delId +" ></input>";
			var delObj = document.createElement(tag);
			if ( delObj != null ) {
				span.appendChild(delObj);
			}//if

			}// Is IE
			
	        if ( !IsIE ) {
				var delObj = document.createElement("input");
				if ( delObj != null ) {
					delObj.name = delId;
					delObj.id = delId;
					delObj.value=info;
					delObj.type = "button";
					span.appendChild(delObj);
				}//if
			}// !IsIE if
			if( delObj != null) delObj.value = info;
		}//main if
		return;

}

function adddel(spanId,index)
{
	
      var strIndex = "" + index;
	  var delId = "idAttachOper" + strIndex;
	  var span = document.getElementById(spanId);
	  if ( span != null ) {
			var oTextNode = document.createElement("SPAN");
			oTextNode.style.width = "5px";
			span.appendChild(oTextNode);
		    if ( IsIE ) {
	        var tag = "<input type='button' style='height:23px;padding-top:1px;' id='" + delId + "' onclick=delfile('" + spanId + "',"+strIndex+")></input>";
			var delObj = document.createElement(tag);
			if ( delObj != null ) {
				span.appendChild(delObj);
			}//if

			}// Is IE
			
	        if ( !IsIE ) {
				var delObj = document.createElement("input");
				if ( delObj != null ) {
					delObj.name = delId;
					delObj.id = delId;
					delObj.type = "button";
					var clickEvent = "return delfile('" + spanId + "',"+strIndex+");";
					delObj.setAttribute("onclick",clickEvent);  
					span.appendChild(delObj);
				}//if
			}// !IsIE if
			if( delObj != null) delObj.value = " 删 除 ";
		}//main if
		return;
}


//-------------------------------------------------------------------------------------------------------------


//---------------------------------------------delete input file-----------------------------------------------
function delfile(spanId,index)
{
	   var strIndex = "" + index;
	   var fileId = "attachfile"+ strIndex;
	   var brId = "idAttachBr" + strIndex;
	   var delId = "idAttachOper" + strIndex;

	   var infoida	= "idAttachOpera" + strIndex;
	   var infoidb	= "idAttachOperb" + strIndex;
	   var valId	= "attachfile"+ strIndex+"Val";

	   //first,get the element
       var span = document.getElementById(spanId);
	   //alert(  "del span: " + span  );
	   if ( span == null ) return false;

	   var fileObj = document.getElementById(fileId);
	   if ( fileObj == null ) return false;

	   var brObj = document.getElementById(brId);
	   if ( brObj ==null ) return false;

	   var delObj = document.getElementById(delId );
	   //alert(  "del delId: " + delObj  );
	   if ( delObj == null ) return false;
		

       //second,create the replace element
	   var temp= document.createElement("SPAN");
	   //third,replace it
	    span.replaceChild(temp,fileObj);
		span.replaceChild(temp,brObj);
		
		// Added by Harry, Repair Remove attached bug 2005/04/04
		span.removeChild(delObj.previousSibling);
		

		var attach = document.getElementById("attach");
		if(span.getElementsByTagName("INPUT").length == 1) attach.childNodes[0].nodeValue='添加';
		// End
		span.replaceChild(temp,delObj);	
		
		return false;
}