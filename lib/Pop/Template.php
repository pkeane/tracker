<?php

class Pop_Template {

    public function __construct($request)
    {
        require_once 'smarty/libs/Smarty.class.php';
        $this->smarty = new Smarty();
        $this->smarty->template_dir = rtrim(TEMPLATE_PATH,'/').'/';
        $this->smarty->compile_dir = rtrim(TEMPLATE_COMPILE_PATH,'/').'/';
        $this->smarty->assign('main_title', MAIN_TITLE);
        $this->smarty->assign('request', $request);
        $this->smarty->assign('msg', $request->get('msg'));
        $this->smarty->assign('app_root', trim($request->app_root,'/').'/');
        $this->template_path = TEMPLATE_PATH;
    }

    public function assign($key,$val)
    {
        $this->smarty->assign($key,$val);
    }

    public function fetch($tpl_file)
    {
        return $this->smarty->fetch($tpl_file);
    }

}
