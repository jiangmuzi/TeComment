<?php 
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 发送邮件操作
 * @author 绛木子 <master@lixianhua.com>
 */
class TeComment_Action extends Typecho_Widget implements Widget_Interface_Do{

    public function comment($cid){
        $setting = Helper::options()->plugin('TeComment');
        $this->request->setParam('cid',$cid);
        $archive = $this->widget('Widget_Archive', array('type'=>'single'));
        if(!$archive->have() || !$archive->is('single')){
            $this->response->throwJson(array('status'=>0,'msg'=>_t('内容不存在!')));
        }
        $functionsFile = $archive->getThemeDir() . 'functions.php';
        if (file_exists($functionsFile)) {
            require_once $functionsFile;
            if (function_exists('themeInit')) {
                themeInit($this);
            }
        }
        $parameter = array(
            'parentId'      => $archive->hidden ? 0 : $archive->cid,
            'parentContent' => $archive->row,
            'respondId'     => $archive->respondId,
            'commentPage'   => $this->request->filter('int')->commentPage,
            'allowComment'  => $archive->allow('comment'),
        );
        $commentArchive = $this->widget('Widget_Comments_Archive', $parameter);
        $data = array();
        ob_start();
        $commentArchive->listComments();
        $data['comments'] = ob_get_clean();
        ob_start();
        $commentArchive->pageNav($setting->commentPrev, $setting->commentNext);
        $data['pageNav'] = ob_get_clean();
        $data['status'] = 1;

        $commentToken = Typecho_Common::shuffleScriptVar(
            Helper::security()->getToken($this->request->getReferer()));
        $data['token'] = '<script id="tecmt-token">$(document).ready(function(){
            window.token = '.$commentToken.'
            });</script>';
        $this->response->throwJson($data);
    }
    public function action(){
        $this->on($this->request->is('comment'))->comment($this->request->filter('int')->get('comment'));
        $this->response->redirect(Helper::options()->index);
    }
}