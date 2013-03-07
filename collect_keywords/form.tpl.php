<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>雅虎关键词抓取</title>
<style type="text/css">
#loading{color: red;font-size: 12px;height: 40px;line-height:40px;margin: 20px auto 0;display:none;width: 920px;}
#msg{border: 1px dashed #999999;margin: 10px auto 100px;padding: 10px;width: 900px}
#datebox,#key_form{width:920px;margin:0 auto 10px auto;}
.loadgif{position:relative;top:10px;margin-right:5px}
.topic{font-size:12px;color:red}
</style>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script type="text/javascript">
$(function(){
	var submit = $("#submit");

	submit.bind("click",function(){
		organize_data();
		//$(this).attr("disabled", true);
	});
	
	$("#date").html(new Date().toLocaleString());
	setInterval("$('#date').html(new Date().toLocaleString());",1000);
})

//整理关键词
function organize_data() {
	var s = $("#s").val();
	
	if (s == '') {
		put_msg('关键词不能为空！');
		return false;
	} else {
		var form = $("#key_form");
		var loading = $("#loading");
		form.hide();
		loading.show();
		$.ajax({
			type:'POST',
			url:'?a=organize_user_key',
			data:'s='+s,
			success:function(d){
				var result = eval("("+d+")");
				if (result.msg == 'true') {
					put_msg('关键词搜集完成，3秒后开始采集！' + result.time);
					setTimeout(function(){
						do_keyword();
					},3000);
				} else {
					put_msg(result + result.time);
				}
			}
		});
	}
} 

function do_keyword() {
	var loading = $("#loading");
	$.ajax({
		type:'POST',
		url:'?a=do_keyword',
		success:function(d){
			var result = eval("("+d+")");
			put_msg(result.msg + result.time);
			if (result.goon == 1) {
				setTimeout(function(){do_keyword()},1000);
			} else {
				loading.html('所有操作执行完毕！' + result.time);
			}
		},
		error:function(d) {
			put_msg('请求超时或连接失败，3秒后重试！');
			setTimeout(function(){do_keyword()},3000);
		}
	});
}

function getDate(){   
	var date=new Date();   
	var month=date.getMonth()+1;   
	var day=date.getDate();   
  
	if(month.toString().length == 1){
		month='0'+month;
	}   
	if(day.toString().length == 1){   
		day='0'+day;
	}   
	return date.getYear()+'/'+month+'/'+day+'  '+date.toLocaleString().substring(date.toLocaleString().length-10)+'  '+'星期'+'日一二三四五六'.charAt(date.getDay());   
}

function put_msg(contents) {
	var msgbox = $("#msgbox");
	msgbox.prepend(contents+'<br/>').show();
}
</script>
</head>

<body>
<div id="datebox"><span>当前时间：</span><span id="date"></span></div>
<div id="loading">
	<img src="001.gif" class='loadgif' />
	程序正在执行，请不要关闭本窗口，所有操作执行完毕会有提示......超过30秒没有新日志产生，
	<a href="javascript:;" onclick="put_msg('重试中...');do_keyword();">请尝试点击这里重试</a>
	<a href="?a=show_result_list" target="_blank">新窗口查看结果</a>
</div>
<form id="key_form" name="form1" method="get">
	<textarea id="s" name="s" style="width:400px;height:200px"></textarea><br/>
	<input type="hidden" id="setup" name="setup" value="1" />
	<input type="button" id="submit" name="submit" value="搜索" />
	<span class='topic'>开始新的搜索会清空所有缓存，请确保之前的搜索结果已经无效！</span>
</form>
<div id="msg"><h1>执行日志</h1><div id='msgbox'></div></div>
</body>
</html>