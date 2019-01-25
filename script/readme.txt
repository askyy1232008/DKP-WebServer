getUserDkp.php
--------------------------------------------
author:tonera
update:2006-8-9

[注意]
请先将此接口程序放在wowdkper的script目录下. 1.3.0-1.3.1用户请将它放在wwwroot目录就行了,也就是script目录的上一级.

此wowdkper的接口程序将根据用户传递的会员名参数,返回一个此会员在所有副本的dkp字符串,格式形如: 

熔火核心:123.45
NAXX:100.23

这种字符格式.根据参数将进行横排与竖排.

调用形式: http://yoursitename/wowdkper/script/getUserDkp.php?fc=1&tc=1&u=Tonera&co=2&c=00ffff&f=12&bg=ffffff&m=1-2-3
调用参数说明如下:
fc=1:我的调用网页是gb2312或gbk编码的(中国大陆简体中文);
fc=2:我的调用网页是utf8编码的;
fc=3:我的调用网页是big5编码的(中国香港,中国台湾繁体中文) 

tc=1:我希望输出的信息是gbk/gb2312编码;
tc=2:我希望输出的信息是utf8编码;
tc=3:我希望输出的信息是big5编码;

u=会员的名称(注意,要从论坛显示会员的dkp,一定要让会员在论坛的名称和dkp系统中的名称一致,否则无法取得此会员的dkp).

co=1:返回字符串排成一排;
co=2:返回字符串按副本分成多排.

c=00ffff:输出文字显示的色彩,默认是黑色.

f=12:输入文字的字号.默认是12

bg=ffffff:背景色彩,默认白色

m=1-2-3:要显示的副本编号 每个副本编号中间使用 - 隔开.

[示例]
拿Discuz! 5.0.0 RC1论坛来说,我的wowdkper地址是:http://192.168.11.230/wowdkper1.3.2/
我想在dz论坛的会员名称下显示此会员在各副本的dkp值,可以这样做.
1.打开discuz论坛的模版目录:\templates\default 找到 viewthread.htm 这个文件.
2.寻找 <br>{eval showstars($post['stars']);}<br> (在我的dz中它在第131行) 这句是显示会员名称下面有多少个星星的,我希望dkp显示在星星下面.
3.在这句后面我加入:
<iframe frameborder="0" style="width: 100%; height: 30px;" marginheight="0" marginwidth="0" hspace=0 vspace=0 scrolling=no  src="http://192.168.11.230/wowdkper1.3.2/script/getUserDkp.php?fc=1&tc=1&u=$post[author]"></iframe>
这一行 (注: $post[author] 是会员名称的变量).存盘退出.
4.访问看看.你会惊奇地发现在会员名称下面会实时显示出这个会员的dkp了.心动不如行动,动手试试吧.等等,如果你对wowdkper和discuz不熟悉的话,建议你先在本地测试一下.


