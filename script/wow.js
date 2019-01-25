function chkAll_onclick() {
  for (var i=0;i<document.ProList.elements.length;i++)
    {
    var e = document.ProList.elements[i];
    if (e.name != 'chkAll')
       e.checked = document.ProList.chkAll.checked;
        }
}
function B1_onclick(actdo) {
  var t=0;
  for (var i=0;i<document.ProList.elements.length;i++)
    {
    var e = document.ProList.elements[i];
    if (e.name != 'chkAll')
       if (e.checked) t=1;
    }
  if (t==1){
		   document.ProList.SiteAction.value=actdo;
           //alert(document.ProList.SiteAction.value);
		   document.ProList.submit();
        }
  else
     alert("你必须选择一条信息");

}

//======================选择
sortitems = 1;  // Automatically sort items within lists? (1 or 0)

function move(fbox,tbox) {
for(var i=0; i<fbox.options.length; i++) {
if(fbox.options[i].selected && fbox.options[i].value != "") {
var no = new Option();
no.value = fbox.options[i].value;
no.text = fbox.options[i].text;
tbox.options[tbox.options.length] = no;
fbox.options[i].value = "";
fbox.options[i].text = "";
   }
}
BumpUp(fbox);
if (sortitems) SortD(tbox);
}
function BumpUp(box)  {
for(var i=0; i<box.options.length; i++) {
if(box.options[i].value == "")  {
for(var j=i; j<box.options.length-1; j++)  {
box.options[j].value = box.options[j+1].value;
box.options[j].text = box.options[j+1].text;
}
var ln = i;
break;
   }
}
if(ln < box.options.length)  {
box.options.length -= 1;
BumpUp(box);
   }
}

function SortD(box)  {
var temp_opts = new Array();
var temp = new Object();
for(var i=0; i<box.options.length; i++)  {
temp_opts[i] = box.options[i];
}
for(var x=0; x<temp_opts.length-1; x++)  {
for(var y=(x+1); y<temp_opts.length; y++)  {
if(temp_opts[x].text > temp_opts[y].text)  {
temp = temp_opts[x].text;
temp_opts[x].text = temp_opts[y].text;
temp_opts[y].text = temp;
temp = temp_opts[x].value;
temp_opts[x].value = temp_opts[y].value;
temp_opts[y].value = temp;
      }
   }
}
for(var i=0; i<box.options.length; i++)  {
box.options[i].value = temp_opts[i].value;
box.options[i].text = temp_opts[i].text;
box.options[i].selected = 1;
   }
}


function confirmLink(theLink, theSqlQuery)
{
	if (confirmMsg == '' || typeof(window.opera) != 'undefined') {
		return true;
	}

	var is_confirmed = confirm(confirmMsg + ' :\n' + theSqlQuery);
	if (is_confirmed) {
		theLink.href += '&is_js_confirmed=1';
	}

	return is_confirmed;
} 
var confirmMsg  = 'Are you sure you want to ';