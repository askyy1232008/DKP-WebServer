/******************************************************************************
  Crossday Discuz! Board - BB Code Insert
  Modified by: Comsenz Technology Ltd (http://www.comsenz.com), Weiming Bianzhou
  Based upon:  XMB CodeInsert (http://www.xmbforum.com), matt
*******************************************************************************/

defmode = "normalmode";	// default mode (normalmode, advmode, helpmode)

if (defmode == "advmode") {
	helpmode = false;
	normalmode = false;
	advmode = true;
} else if (defmode == "helpmode") {
	helpmode = true;
	normalmode = false;
	advmode = false;
} else {
	helpmode = false;
	normalmode = true;
	advmode = false;
}

function chmode(swtch){
	if (swtch == 1){
		advmode = false;
		normalmode = false;
		helpmode = true;
		alert(help_mode);
	} else if (swtch == 0) {
		helpmode = false;
		normalmode = false;
		advmode = true;
		alert(adv_mode);
	} else if (swtch == 2) {
		helpmode = false;
		advmode = false;
		normalmode = true;
		alert(normal_mode);
	}
}

function AddText(NewCode) {
	document.all ? insertAtCaret(document.input.message, NewCode) : document.input.message.value += NewCode;
	setfocus();
}

function storeCaret(textEl){
	if(textEl.createTextRange){
		textEl.caretPos = document.selection.createRange().duplicate();
	}
}

function insertAtCaret(textEl, text){
	if (textEl.createTextRange && textEl.caretPos){
		var caretPos = textEl.caretPos;
		caretPos.text += caretPos.text.charAt(caretPos.text.length - 2) == ' ' ? text + ' ' : text;
	} else if(textEl) {
		textEl.value += text;
	} else {
		textEl.value = text;
	}
}

function getSelectedText() {
        var post = document.input.message;
        var selected = '';
        if(post.isTextEdit){ 
                post.focus();
                var sel= document.selection;
                var rng= sel.createRange();
                rng.colapse;
                if((sel.type =="Text" || sel.type == "None") && rng !=null){
                        if(rng.text.length > 0)        selected = rng.text;
                }
        }        
        return selected;
}

function alipay() {
	if (helpmode) {
		alert(alipay_help);
	} else if (advmode) {
		AddTxt = "\r[payto]\r[seller][/seller]\r[subject][/subject]\r[body][/body]\r[url][/url]\r[type][/type]\r[/payto][list]";
		AddText(AddTxt);
	} else {
		var re=/^[\w.-]+@([0-9a-z][\w-]+\.)+[a-z]{2,3}$/i;
		txt = prompt(alipay_normal_account,"");
		if(txt != null) {
			while(!re.test(txt)) {
				txt = prompt(alipay_normal_account_error,"");
				if(txt == null) return;
			}
    			var promptHint = new Array(alipay_normal_merchandise, alipay_normal_description, alipay_normal_gross, alipay_normal_homepage, alipay_normal_type);
    			var promptValue = new Array("subject", "body", "gross", "url", "type");
    			var AddTxt = "[payto]\r\n(seller)" + txt + "(/seller)\r\n";
    			for(i = 0; i < promptHint.length; i++) {
        			var txt = prompt(promptHint[i], "");
        			if(txt == null) break;
        			AddTxt+="("+promptValue[i]+")"+txt+"(/"+promptValue[i]+")\r\n";
    			}
    			if(txt!=null) {
    				AddTxt+="[/payto]";
    				AddText(AddTxt);
    			}
		}
	}
}

function email() {
	if (helpmode) {
		alert(email_help);
	} else if (getSelectedText()) {
		var range = document.selection.createRange();
		range.text = "[email]" + range.text + "[/email]";
	} else if (advmode) {
		AddTxt="[email] [/email]";
		AddText(AddTxt);
	} else {
		txt2=prompt(email_normal,"");
		if (txt2!=null) {
			txt=prompt(email_normal_input,"name@domain.com");
			if (txt!=null) {
				if (txt2=="") {
					AddTxt="[email]"+txt+"[/email]";
				} else {
					AddTxt="[email="+txt+"]"+txt2+"[/email]";
				}
				AddText(AddTxt);
			}
		}
	}
}


function chsize(size) {
	if (helpmode) {
		alert(fontsize_help);
	} else if (getSelectedText()) {
		var range = document.selection.createRange();
		range.text = "[size=" + size + "]" + range.text + "[/size]";
	} else if (advmode) {
		AddTxt="[size="+size+"] [/size]";
		AddText(AddTxt);
	} else {
		txt=prompt(fontsize_normal,text_input);
		if (txt!=null) {
			AddTxt="[size="+size+"]"+txt;
			AddText(AddTxt);
			AddText("[/size]");
		}
	}
}

function chfont(font) {
	if (helpmode){
		alert(font_help);
	} else if (getSelectedText()) {
		var range = document.selection.createRange();
		range.text = "[font=" + font + "]" + range.text + "[/font]";
	} else if (advmode) {
		AddTxt="[font="+font+"] [/font]";
		AddText(AddTxt);
	} else {
		txt=prompt(font_normal,text_input);
		if (txt!=null) {
			AddTxt="[font="+font+"]"+txt;
			AddText(AddTxt);
			AddText("[/font]");
		}
	}
}


function bold() {
	if (helpmode) {
		alert(bold_help);
	} else if (getSelectedText()) {
		var range = document.selection.createRange();
		range.text = "[b]" + range.text + "[/b]";
	} else if (advmode) {
		AddTxt="[b] [/b]";
		AddText(AddTxt);
	} else {
		txt=prompt(bold_normal,text_input);
		if (txt!=null) {
			AddTxt="[b]"+txt;
			AddText(AddTxt);
			AddText("[/b]");
		}
	}
}

function italicize() {
	if (helpmode) {
		alert(italicize_help);
	} else if (getSelectedText()) {
		var range = document.selection.createRange();
		range.text = "[i]" + range.text + "[/i]";
	} else if (advmode) {
		AddTxt="[i] [/i]";
		AddText(AddTxt);
	} else {
		txt=prompt(italicize_normal,text_input);
		if (txt!=null) {
			AddTxt="[i]"+txt;
			AddText(AddTxt);
			AddText("[/i]");
		}
	}
}

function quote() {
	if (helpmode){
		alert(quote_help);
	} else if (getSelectedText()) {
		var range = document.selection.createRange();
		range.text = "[quote]" + range.text + "[/quote]";
	} else if (advmode) {
		AddTxt="\r[quote]\r[/quote]";
		AddText(AddTxt);
	} else {
		txt=prompt(quote_normal,text_input);
		if(txt!=null) {
			AddTxt="\r[quote]\r"+txt;
			AddText(AddTxt);
			AddText("\r[/quote]");
		}
	}
}

function chcolor(color) {
	if (helpmode) {
		alert(color_help);
	} else if (getSelectedText()) {
		var range = document.selection.createRange();
		range.text = "[color=" + color + "]" + range.text + "[/color]";
	} else if (advmode) {
		AddTxt="[color="+color+"] [/color]";
		AddText(AddTxt);
	} else {
		txt=prompt(color_normal,text_input);
		if(txt!=null) {
			AddTxt="[color="+color+"]"+txt;
			AddText(AddTxt);
			AddText("[/color]");
		}
	}
}

function center() {
	if (helpmode) {
		alert(center_help);
	} else if (getSelectedText()) {
		var range = document.selection.createRange();
		range.text = "[align=center]" + range.text + "[/align]";
	} else if (advmode) {
		AddTxt="[align=center] [/align]";
		AddText(AddTxt);
	} else {
		txt=prompt(center_normal,text_input);
		if (txt!=null) {
			AddTxt="\r[align=center]"+txt;
			AddText(AddTxt);
			AddText("[/align]");
		}
	}
}

function hyperlink() {
	if (helpmode) {
		alert(link_help);
	} else if (advmode) {
		AddTxt="[url] [/url]";
		AddText(AddTxt);
	} else {
		txt2=prompt(link_normal,"");
		if (txt2!=null) {
			txt=prompt(link_normal_input,"http://");
			if (txt!=null) {
				if (txt2=="") {
					AddTxt="[url]"+txt;
					AddText(AddTxt);
					AddText("[/url]");
				} else {
					AddTxt="[url="+txt+"]"+txt2;
					AddText(AddTxt);
					AddText("[/url]");
				}
			}
		}
	}
}

function image() {
	if (helpmode){
		alert(image_help);
	} else if (advmode) {
		AddTxt="[img] [/img]";
		AddText(AddTxt);
	} else {
		txt=prompt(image_normal,"http://");
		if(txt!=null) {
			AddTxt="\r[img]"+txt;
			AddText(AddTxt);
			AddText("[/img]");
		}
	}
}

function flash() {
	if (helpmode){
		alert(flash_help);
	} else if (advmode) {
		AddTxt="[swf] [/swf]";
		AddText(AddTxt);
	} else {
		txt=prompt(flash_normal,"http://");
		if(txt!=null) {
			AddTxt="\r[swf]"+txt;
			AddText(AddTxt);
			AddText("[/swf]");
		}
	}
}

function code() {
	if (helpmode) {
		alert(code_help);
	} else if (getSelectedText()) {
		var range = document.selection.createRange();
		range.text = "[code]" + range.text + "[/code]";
	} else if (advmode) {
		AddTxt="\r[code]\r[/code]";
		AddText(AddTxt);
	} else {
		txt=prompt(code_normal,"");
		if (txt!=null) {
			AddTxt="\r[code]"+txt;
			AddText(AddTxt);
			AddText("[/code]");
		}
	}
}

function list() {
	if (helpmode) {
		alert(list_help);
	} else if (advmode) {
		AddTxt="\r[list]\r[*]\r[*]\r[*]\r[/list]";
		AddText(AddTxt);
	} else {
		txt=prompt(list_normal,"");
		while ((txt!="") && (txt!="A") && (txt!="a") && (txt!="1") && (txt!=null)) {
			txt=prompt(list_normal_error,"");
		}
		if (txt!=null) {
			if (txt=="") {
				AddTxt="\r[list]\r\n";
			} else {
				AddTxt="\r[list="+txt+"]\r";
			}
			txt="1";
			while ((txt!="") && (txt!=null)) {
				txt=prompt(list_normal_input,"");
				if (txt!="") {
					AddTxt+="[*]"+txt+"\r";
				}
			}
			AddTxt+="[/list]\r\n";
			AddText(AddTxt);
		}
	}
}

function underline() {
	if (helpmode) {
		alert(underline_help);
	} else if (getSelectedText()) {
		var range = document.selection.createRange();
		range.text = "[u]" + range.text + "[/u]";
	} else if (advmode) {
		AddTxt="[u] [/u]";
		AddText(AddTxt);
	} else {
		txt=prompt(underline_normal,text_input);
		if (txt!=null) {
			AddTxt="[u]"+txt;
			AddText(AddTxt);
			AddText("[/u]");
		}
	}
}

function setfocus() {
	document.input.message.focus();
}