<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 评论增强插件
 * 
 * @package TeComment
 * @author 绛木子
 * @version 2.0.0
 * @link http://lixianhua.com
 * 
 * 1、Ajax评论支持
 * 2、评论异步加载
 * 3、评论工具栏
 */
class TeComment_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Comments')->contentEx = array('TeComment_Plugin','parseContent');

		Typecho_Plugin::factory('Widget_Abstract_Comments')->gravatar = array('TeComment_Plugin', 'gravatar');

		Typecho_Plugin::factory('Widget_Feedback')->finishComment = array('TeComment_Plugin','finishComment');

		Typecho_Plugin::factory('Widget_Archive')->header = array('TeComment_Plugin','insertCss');
		Typecho_Plugin::factory('Widget_Archive')->footer = array('TeComment_Plugin','insertJs');

		Helper::addAction('TeComment','TeComment_Action');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
		Helper::removeAction('TeComment');
	}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
		$gravatarDomain = new Typecho_Widget_Helper_Form_Element_Text('gravatarDomain', NULL, 'http://cn.gravatar.com', _t('头像地址'),_t('替换Typecho使用的Gravatar头像地址（ www.gravatar.com ）'));
		$form->addInput($gravatarDomain);

		$commentAjaxPost = new Typecho_Widget_Helper_Form_Element_Radio('commentAjaxPost', array(
        1=>'启用',0=>'禁用',), 0, _t('是否启用Ajax提交评论'),_t('启用Ajax提交评论后，评论信息将通过Ajax提交'));
		$form->addInput($commentAjaxPost);

		$commentAjaxLoad = new Typecho_Widget_Helper_Form_Element_Radio('commentAjaxLoad', array(
        1=>'启用',0=>'禁用',), 0, _t('评论异步加载'),_t('启用后评论列表将通过Ajax异步加载'));
		$form->addInput($commentAjaxLoad);

		$commentPjax = new Typecho_Widget_Helper_Form_Element_Radio('commentPjax', array(
		1=>'兼容',0=>'不需要兼容',), 0, _t('是否兼容Pjax'),_t('是否需要兼容Pjax'));
		$form->addInput($commentPjax);
		
		$commentAjaxLoadElement = new Typecho_Widget_Helper_Form_Element_Text('commentAjaxLoadElement', NULL, '#comment-ajax-list', _t('异步加载元素ID'),_t('默认为<code>#comment-ajax-list</code>,可根据模板具体内容进行xiug'));
        $form->addInput($commentAjaxLoadElement);

		// 评论分页
        $commentPrev = new Typecho_Widget_Helper_Form_Element_Text('commentPrev', NULL, '&laquo; 前一页', _t('上一页'),_t('启用评论异步加载时，评论分页的“上一页”文字'));
        $form->addInput($commentPrev);
		$commentNext = new Typecho_Widget_Helper_Form_Element_Text('commentNext', NULL, '后一页 &raquo;', _t('下一页'),_t('启用评论异步加载时，评论分页的“下一页”文字'));
        $form->addInput($commentNext);

		$commentAjaxHtml = new Typecho_Widget_Helper_Form_Element_Textarea('commentAjaxHtml', NULL, NULL, _t('评论模板'),
		_t('在这里填入评论模板;可用参数:<code>{theId}</code> 锚点ID,<code>{commentClass}</code> 列表样式,<code>{authorAvatar}</code>用户头像,
		<code>{authorName}</code>用户名称,<code>{authorUrl}</code>用户主页,<code>{authorUrl}</code>用户邮箱,<code>{created}</code>发布时间,
		<code>{replyLink}</code>回复链接,<code>{content}</code>内容,<code>{children}</code>子评论'));
		$form->addInput($commentAjaxHtml);

		$smiliesset= new Typecho_Widget_Helper_Form_Element_Select('smiliesset',
			self::parseFolders(),'qq-smilies',_t('表情风格'),_t('插件目录下若新增表情风格文件夹可刷新本页在下拉菜单中选择. <br/>注意图片名须参考其他文件夹保持一致, 如icon_cry.gif对应哭泣表情等'));
		$form->addInput($smiliesset);
		
		// jquery地址
        $jquery = new Typecho_Widget_Helper_Form_Element_Text('jquery', NULL, 'http://apps.bdimg.com/libs/jquery/1.11.1/jquery.min.js', _t('Jquery地址'),_t('引用的Jquery地址，当页面未加载jquery时自动加载，默认为（ http://apps.bdimg.com/libs/jquery/1.11.1/jquery.min.js ）'));
        $form->addInput($jquery);
		
		$iconfont = new Typecho_Widget_Helper_Form_Element_Text('iconfont', NULL, '//at.alicdn.com/t/font_m7mp27xfc0jp2e29.css', _t('评论增强插件图标'), _t('默认使用<a href="http://www.iconfont.cn" target="_blank">iconfont</a>提供的在线图标服务'));
		$form->addInput($iconfont);
		
		$style = new Typecho_Widget_Helper_Form_Element_Textarea('style', NULL, NULL, _t('评论增强插件样式'), _t('直接填写css样式；不需要使用 <code>style</code>标签'));
		$form->addInput($style);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
	/**
	 * 获取gravatar头像地址
	 *
	 * @param string $mail
	 * @param int $size
	 * @param string $rating
	 * @param string $default
	 * @param bool $isSecure
	 * @return string
	 */
	public static function gravatarUrl($mail, $size=32, $rating=null, $default=null, $isSecure = false)
	{
		$options = Helper::options();
	    $url = $options->plugin('TeComment')->gravatarDomain;
	    $url .= '/avatar/';
		$gravatarDefault = $options->gravatarDefault;
		$default = empty($default) ? $gravatarDefault : (!empty($gravatarDefault) ? $gravatarDefault : $default);
		if(!empty($default)) $default = urlencode($default);
	    if (!empty($mail)) {
	        $url .= md5(strtolower(trim($mail)));
	    }
	    $url .= '?s=' . $size;
	    $url .= '&amp;r=' . ($rating==null? $options->commentsAvatarRating : $rating);
	    $url .= '&amp;d=' . $default;
	    return $url;
	}

	/**
	 * 评论列表用户头像接口
	 *
	 * @param int $size
	 * @param string $rating
	 * @param string $default
	 * @param Widget_Comments_Archive $comments
	 * @return void
	 */
	public static function gravatar($size, $rating, $default, $comments){
	    $url = self::gravatarUrl($comments->mail, $size, $rating, $default, $comments->request->isSecure());
	    echo '<img class="avatar" src="' . $url . '" alt="' . $comments->author . '" width="' . $size . '" height="' . $size . '" />';
	}
	
	/**
	 * 评论发布成功插件接口
	 *
	 * @param Widget_Feedback $feedback
	 * @return void
	 */
	public static function finishComment($feedback){
		if(Helper::options()->plugin('TeComment')->commentAjaxPost){
			$html = self::parseCommentHtml($feedback,'replyHidden=1');
			$html = str_replace('>{children}<','><',$html);
			$feedback->response->throwJson(array('status'=>1,'msg'=>'回复成功!','body'=>$html));
		}else{
			$feedback->response->goBack('#' . $feedback->theId);
		}
	}

	/**
	 * 解析评论为html
	 *
	 * @param Widget_Comments_Archive/Widget_Feedback $comments
	 * @param mix $commentOptions
	 * @return string
	 */
	public static function parseCommentHtml($comments,$commentOptions = null){
		$html ='<li id="{theId}" class="widget {commentClass}">
		<div class="comment-meta">
			<div class="comment-meta-avatar">{authorAvatar}</div>
			<div class="comment-meta-author">
				<strong>{beforeAuthor}<a href="{authorUrl}" rel="external nofollow" target="_blank">{authorName}</a>{afterAuthor}{commentStatus}</strong></div>
			<div class="comment-meta-time">{beforeDate}{created}{afterDate}</div>
				<div class="comment-meta-reply">{replyLink}</div>
			</div>
		<div class="comment-content">{content}</div>
		<div class="comment-children">{children}</div>
		</li>';
		$options = Typecho_Widget::widget('Widget_Options');
		if(!($commentOptions instanceof Typecho_Config)){
			$commentOptions = new Typecho_Config($commentOptions);
		}
		$commentOptions->setDefault(array(
			'before'        =>  '<ol class="comment-list">',
			'after'         =>  '</ol>',
			'beforeAuthor'  =>  '',
			'afterAuthor'   =>  '',
			'beforeDate'    =>  '',
			'afterDate'     =>  '',
			'dateFormat'    =>  $options->commentDateFormat,
			'replyWord'     =>  _t('回复'),
			'commentStatus' =>  _t('您的评论正等待审核！'),
			'avatarSize'    =>  48,
			'defaultAvatar' =>  NULL,
			'replyHidden'	=>  false
		));
		
		if(!empty($options->plugin('TeComment')->commentAjaxHtml)){
			$html = $options->commentAjaxHtml;
		}
		
		if($commentOptions->replyHidden){
			$html = str_replace('{replyLink}','',$html);
		}
		$commentClass = 'comment-body';
		if ($comments->levels > 0) {
			$commentClass .= ' comment-child';
		} else {
			$commentClass .= ' comment-parent';
		}
		if ($comments->authorId) {
			if ($comments->authorId == $comments->ownerId) {
				$commentClass .= ' comment-by-author';
			} else {
				$commentClass .= ' comment-by-user';
			}
		}
		
		$tmp = array(' comment-odd', ' comment-even');
		$sequence = $comments->levels <= 0 ? $comments->sequence : $comments->order;
		$split = $sequence % 2;
		$commentClass .= $tmp[(0 == $split ? 2 : $split) -1];
		$tmp = array(' comment-level-odd', ' comment-level-even');
		$split = $comments->levels % 2;
		$commentClass .= $tmp[(0 == $split ? 2 : $split) -1];
		
		$theId = $comments->type . '-' . $comments->coid;
		$authorAvatar = self::commentAvatar($comments,$commentOptions->avatarSize);
		$authorName = $comments->author;
		$authorUrl = empty($comments->url) ? '#' : $comments->url;
		$authorMail = $comments->mail;
		$commentStatus = '';
		if ('waiting' == $comments->status) {
			$commentStatus = $commentOptions->commentStatus;
        }
		$created = $comments->date->format($commentOptions->dateFormat);
		if ($options->commentsThreaded && !$comments->isTopLevel) {
			$replyLink = '<a href="' . substr($comments->permalink, 0, - strlen($theId) - 1) . '?replyTo=' . $comments->coid .
				'#' . $comments->parameter->respondId . '" rel="nofollow" onclick="return TypechoComment.reply(\'' .
				$theId . '\', ' . $comments->coid . ');">' . $commentOptions->replyWord . '</a>';
		}else{
			$replyLink = '';
		}
		$content = $comments->content;

		$html = str_replace(
				array('{theId}','{commentClass}','{beforeAuthor}','{afterAuthor}','{beforeDate}','{afterDate}','{commentStatus}',
				'{authorAvatar}','{authorName}','{authorUrl}','{authorMail}','{created}','{replyLink}','{content}'),
				array($theId,$commentClass,$commentOptions->beforeAuthor,$commentOptions->afterAuthor,$commentOptions->beforeDate,$commentOptions->afterDate,
				$commentStatus,$authorAvatar,$authorName,$authorUrl,$authorMail,$created,$replyLink,$content),
				$html);
		return $html;
	}

	/**
	 * 评论列表用户头像
	 *
	 * @param Widget_Comments_Archive $comments
	 * @param int $size
	 * @param string $rating
	 * @param string $default
	 * @return mix
	 */
	private static function commentAvatar($comment, $size = 48, $rating=NULL, $default = null, $output = false){
		$options = Typecho_Widget::widget('Widget_Options');
		$html = '';
		if ($options->commentsAvatar && 'comment' == $comment->type) {
			$url = self::gravatarUrl($comment->mail, $size, $rating, $default, $comment->request->isSecure());
				$html = '<img class="avatar" src="' . $url . '" alt="' .
				$comment->author . '" width="' . $size . '" height="' . $size . '" />';
			if($output){
				echo $html;
			}
		}
		return $html;
	}

	/**
	 * 扫描表情文件夹
	 *
	 * @access private
	 * @return array
	 */
	private static function parseFolders()
	{
		$results = glob(__TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/TeComment/*',GLOB_ONLYDIR);

		foreach ($results as $result) {
			$name = iconv('gbk','utf-8',
				str_replace(__TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/TeComment/','',$result)
				);
			$folders[$name]= $name;
		}

		return $folders;
	}
	/**
	 * 解析表情图片
	 *
	 * @access public
	 * @param string $text 评论内容
	 * @return string
	 */
    public static function parseContent($text,$widget,$lastResult){
		
		$text = empty($lastResult)?$text:$lastResult;

		Helper::options()->commentsHTMLTagAllowed .= '<img src="" alt=""/><blockquote><strong><em><del><u><pre>';

		$arrays = self::parseSmilies();

		if ($widget instanceof Widget_Abstract_Comments) {
			return str_replace($arrays[1],$arrays[2],$text);
		} else {
			return $text;
		}
	}
	
	/**
	 * 整理表情数据
	 *
	 * @access private
	 * @return array
	 */
	private static function parseSmilies()
	{
		$options = Helper::options();
		$settings = $options->plugin('TeComment');

		$smiliestrans = array(
			':?:'		=> 'icon_question.gif',
			':razz:'	=> 'icon_razz.gif',
			':sad:'		=> 'icon_sad.gif',
			':evil:'	=> 'icon_evil.gif',
			':!:'		=> 'icon_exclaim.gif',
			':smile:'	=> 'icon_smile.gif',
			':oops:'	=> 'icon_redface.gif',
			':grin:'	=> 'icon_biggrin.gif',
			':eek:'		=> 'icon_surprised.gif',
			':shock:'	=> 'icon_eek.gif',
			':???:'		=> 'icon_confused.gif',
			':cool:'	=> 'icon_cool.gif',
			':lol:'		=> 'icon_lol.gif',
			':mad:'		=> 'icon_mad.gif',
			':twisted:' => 'icon_twisted.gif',
			':roll:'	=> 'icon_rolleyes.gif',
			':wink:'	=> 'icon_wink.gif',
			':idea:'	=> 'icon_idea.gif',
			':arrow:'	=> 'icon_arrow.gif',
			':neutral:' => 'icon_neutral.gif',
			':cry:'		=> 'icon_cry.gif',
			':mrgreen:' => 'icon_mrgreen.gif',
			'8-)'		=> 'icon_cool.gif',
			'8-O'		=> 'icon_eek.gif',
			':-('		=> 'icon_sad.gif',
			':-)'		=> 'icon_smile.gif',
			':-?'		=> 'icon_confused.gif',
			':-D'		=> 'icon_biggrin.gif',
			':-P'		=> 'icon_razz.gif',
			':-o'		=> 'icon_surprised.gif',
			':-x'		=> 'icon_mad.gif',
			':-|'		=> 'icon_neutral.gif',
			';-)'		=> 'icon_wink.gif',
			'8)'		=> 'icon_cool.gif',
			'8O'		=> 'icon_eek.gif',
			':('		=> 'icon_sad.gif',
			':)'		=> 'icon_smile.gif',
			':?'		=> 'icon_confused.gif',
			':D'		=> 'icon_biggrin.gif',
			':P'		=> 'icon_razz.gif',
			':o'		=> 'icon_surprised.gif',
			':x'		=> 'icon_mad.gif',
			':|'		=> 'icon_neutral.gif',
			';)'		=> 'icon_wink.gif',
		);
		//
		$smiliesurl = Typecho_Common::url('TeComment/'.urlencode($settings->smiliesset).'/',$options->pluginUrl);
		$smiled = array();

		foreach ($smiliestrans as $tag=>$grin) {
			
			if (!in_array($grin,$smiled)) {
				$smiled[] = $grin;
				$smiliesicon[] = '<span data-tag=" '.$tag.' "><img style="margin:2px;" src="'.$smiliesurl.$grin.'" alt="'.$grin.'"/></span>';
			}

			$smiliestag[] = $tag;

			$smiliesimg[] = '<img class="smilies" src="'.$smiliesurl.$grin.'" alt="'.$grin.'"/>';
		}

		return array($smiliesicon,$smiliestag,$smiliesimg);
	}
	
	// 插入插件样式
	public static function insertCss($header,$widget){
		if(Helper::options()->plugin('TeComment')->commentPajx && !$widget->is('single')){
			return;
		}
		$commentStyle = Helper::options()->plugin('TeComment')->style;
		$iconfont = Helper::options()->plugin('TeComment')->iconfont;
		if(!empty($iconfont)){
			$iconfont = '<link rel="stylesheet" href="'.$iconfont.'">';
		}
		$defaultStyle = <<<EOT
#te-cmt-tool{display:none;list-style:none;padding:0;margin:10px 0;}
#te-cmt-cmd{padding:0;margin:0;overflow:hidden;list-style:none;border:1px solid #969696;border-radius:3px;}
#te-cmt-cmd li{padding:0;margin:0;float:left;border:none;}
#te-cmt-cmd li a{height:32px;line-height:32px;padding:0 10px;display:block;}
.te-cmt-smilies{display:none;padding: 8px 5px 5px;margin-top:-3px;border:1px solid #969696;border-top:none;border-radius: 0 0 3px 3px;}
.te-cmt-smilies span{cursor:pointer;}
.te-cmt-smilies span img{vertical-align:middle}
.te-cmt-ban{height:20px;line-height:20px;}
.te-cmt-ban input[type="checkbox"]{margin:0;padding:0;vertical-align:middle;}
EOT;
		echo $iconfont.'<style type="text/css">'.(empty($commentStyle) ? $defaultStyle : $commentStyle).'</style>';
	}
	
	// 插入插件脚本
	public static function insertJs($widget){
		if(Helper::options()->plugin('TeComment')->commentPajx && !$widget->is('single')){
			return;
		}
		$options = Helper::options();
        $plugin_path = Typecho_Common::url('TeComment', $options->pluginUrl);
		$jquery = $options->plugin('TeComment')->jquery;
		$commentAjaxPost = $options->plugin('TeComment')->commentAjaxPost ? 1 : 0;
		$commentAjaxLoad = $options->plugin('TeComment')->commentAjaxLoad ? 1 : 0;
		$commentAjaxLoadElement = $options->plugin('TeComment')->commentAjaxLoadElement;
		$action = Typecho_Common::url('action',$options->index);
		// 引入Jquery
		echo '<script>!window.jQuery && document.write("<script src=\"'.$jquery.'\">"+"</scr"+"ipt>");</script><script src="'.$plugin_path.'/tecomment.js"></script>';
		// 初始化脚本
		$commentToken = Typecho_Common::shuffleScriptVar(
							Helper::security()->getToken($widget->request->getRequestUrl()));
		
		echo '<script>$(document).ready(function(){
			window.token = '.$commentToken.'
			TeCmt.init({action:"'.$action.'",commentAjaxPost:'.$commentAjaxPost.',commentAjaxLoad:'.$commentAjaxLoad.',commentAjaxLoadElement:"'.$commentAjaxLoadElement.'"});
		});</script>';
	}

	// 显示插件
	public static function showTool(){
		$options = Typecho_Widget::widget('Widget_Options');
		if(!isset($options->plugins['activated']['TeComment'])) return;
		$smilies = '';
		$arrays = self::parseSmilies();
		$icons = array_unique($arrays[0]);
		foreach ($icons as $icon) {
			$smilies .= $icon;
		}
		echo 
		'<div id="te-cmt-tool">
			<ul id="te-cmt-cmd">
				<li><a class="te-font te-icon-emoji" href="#" data-cmd="smilies" title="表情"></a></li>
				<li><a class="te-font te-icon-bold" href="#" data-cmd="bold" title="粗体"></a></li>
				<li><a class="te-font te-icon-italic" href="#" data-cmd="italic" title="斜体"></a></li>
				<li><a class="te-font te-icon-quote" href="#" data-cmd="quote" title="引用"></a></li>
				<li><a class="te-font te-icon-underline" href="#" data-cmd="underline" title="下划线"></a></li>
				<li><a class="te-font te-icon-deline" href="#" data-cmd="deline" title="删除线"></a></li>
				<li><a class="te-font te-icon-code" href="#" data-cmd="code" title="插入代码"></a></li>
				<li><a class="te-font te-icon-image" href="#" data-cmd="img" title="插入图片"></a></li>
			</ul>
			<div class="te-cmt-smilies">'.$smilies.'</div>
		</div>';
	}
}
