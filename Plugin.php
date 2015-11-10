<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 评论工具箱
 * 
 * @package TeComment
 * @author 绛木子
 * @version 1.0.0
 * @link http://lixianhua.com
 * Smilies	评论表情
 * 
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
		Typecho_Plugin::factory('Widget_Archive')->header = array('TeComment_Plugin','insertCss');
		Typecho_Plugin::factory('Widget_Archive')->footer = array('TeComment_Plugin','insertJs');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
		$smiliesset= new Typecho_Widget_Helper_Form_Element_Select('smiliesset',
			self::parseFolders(),'qq-smilies',_t('表情风格'),_t('插件目录下若新增表情风格文件夹可刷新本页在下拉菜单中选择. <br/>注意图片名须参考其他文件夹保持一致, 如icon_cry.gif对应哭泣表情等'));
		$form->addInput($smiliesset);
		
		// jquery地址
        $jquery = new Typecho_Widget_Helper_Form_Element_Text('jquery', NULL, 'http://apps.bdimg.com/libs/jquery/1.11.1/jquery.min.js', _t('Jquery地址'),_t('引用的Jquery地址，当页面未加载jquery时自动加载，默认为（ http://apps.bdimg.com/libs/jquery/1.11.1/jquery.min.js ）'));
        $form->addInput($jquery);
		
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
	
	public static function insertCss($header,$widget){
		if(!$widget->is('single')) return;
		$css = <<<EOT
<style type="text/css">
	#te-cmt-tool{display:none;list-style:none;margin:0;padding:10px 0;overflow:hidden;}
	#te-cmt-cmd li{padding:0;margin:0;float:left;border:1px solid #ccc;border-right:none;list-style:none;}
	#te-cmt-cmd li:first-child{border-radius: 3px 0 0 3px;}
	#te-cmt-cmd li:last-child{border-right:1px solid #ccc;border-radius: 0 3px 3px 0;}
	#te-cmt-cmd li a{height:24px;line-height:24px;padding:0 10px;}
	.te-cmt-smilies{display:none;}
	.te-cmt-smilies span{cursor:pointer;}
</style>		
EOT;
	echo $css;
	}
	public static function insertJs($widget){
		if(!$widget->is('single')) return;
		$options = Helper::options();
        $plugin_path = Typecho_Common::url('TeComment', $options->pluginUrl);
		$jquery = $options->plugin('TeComment')->jquery;
		echo '<script>!window.jQuery && document.write("<script src=\"'.$jquery.'\">"+"</scr"+"ipt>");</script><script src="'.$plugin_path.'/tecomment.js"></script>';
	}
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
			<div class="te-cmt-smilies">'.$smilies.'</div>
			<ul id="te-cmt-cmd">
				<li><a href="#" data-cmd="signin">签到</a></li>
				<li><a href="#" data-cmd="smilies">表情</a></li>
				<li><a href="#" data-cmd="bold">粗体</a></li>
				<li><a href="#" data-cmd="italic">斜体</a></li>
				<li><a href="#" data-cmd="quote">引用</a></li>
				<li><a href="#" data-cmd="underline">下划线</a></li>
				<li><a href="#" data-cmd="deline">删除线</a></li>
				<li><a href="#" data-cmd="code">插代码</a></li>
				<li><a href="#" data-cmd="img">插图片</a></li>
			</ul>
		</div>';
	}
}
