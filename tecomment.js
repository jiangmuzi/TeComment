var TeCmt = {
	text:null,
	tool:null,
	cmd:null,
	init:function(){
		TeCmt.text = $('#textarea');
		TeCmt.tool = $('#te-cmt-tool');
		//显示工具栏
		TeCmt.text.focus(function(){
			TeCmt.tool.slideDown();
		});
		//点击文本框时关闭
		TeCmt.text.click(function(){
			TeCmt.tool.find('.te-cmt-smilies').slideUp();
		});
		//解析工具栏命令
		$('#te-cmt-cmd a').click(function(){
			TeCmt.cmd = $(this).data('cmd');
			TeCmt.parseCmd();
			return false;
		});
		TeCmt.tool.find('.te-cmt-smilies span').click(function(){
			var tag = $(this).data('tag');
			TeCmt.write(tag);
			return false;
		});
	},
	parseCmd:function(){
		if(TeCmt.cmd==null || TeCmt.cmd===undefined){
			return false;
		}
		switch(TeCmt.cmd){
			case 'signin':TeCmt.write('签到成功！每日签到，生活更精彩哦~');break;
			case 'smilies':TeCmt.tool.find('.te-cmt-smilies').slideToggle();break;
			case 'bold':TeCmt.write("<strong>", "</strong>");break;
			case 'italic':TeCmt.write("<em>", "</em>");break;
			case 'quote':TeCmt.write("<blockquote>", "</blockquote>");break;
			case 'underline':TeCmt.write("<u>", "</u>");break;
			case 'deline':TeCmt.write("<del>", "</del>");break;
			case 'code':TeCmt.write('<pre>', '</pre>');break;
			case 'img':TeCmt.insestImg();break;
		}
	},

	insestImg:function(){
		var a = prompt("请输入图片地址", "http://");
		if (a) {
			TeCmt.write('<img src="' + a + '" rel="external nofollow" id="comments-img" alt="评论贴图" />', "")
		}
	},
	write:function(l,r){
		if(l===undefined) return false;
		var el = TeCmt.text[0];
		if (document.selection) {
            el.focus();
            sel = document.selection.createRange();
            r ? sel.text = l + sel.text + r : sel.text = l;
            el.focus();
        } else {
            if (el.selectionStart || el.selectionStart == "0") {
                var d = el.selectionStart;
                var e = el.selectionEnd;
                var f = e;
                r ? el.value = el.value.substring(0, d) + l + el.value.substring(d, e) + r + el.value.substring(e, el.value.length) : el.value = el.value.substring(0, d) + l + el.value.substring(e, el.value.length);
                r ? f += l.length + r.length : f += l.length - e + d;
                if (d == e && r) {
                    f -= r.length;
                }
                el.focus();
                el.selectionStart = f;
                el.selectionEnd = f;
            } else {
                el.value += l + r;
                el.focus();
            }
        }
	},
}
$(document).ready(function(){
	TeCmt.init();
});