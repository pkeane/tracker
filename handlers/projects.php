<?php

class Pop_Handler_Projects extends Pop_Handler
{
    public $resource_map = array(
        '/' => 'list',
    );

    protected function setup($r)
    {
    }

    public function getList($r) 
    {
        $t = new Pop_Template($r);
        $p = new Project();
        $t->assign('projects',$p->findAll(1));
        $r->renderResponse($t->fetch('projects.tpl'));
    }

    public function postToList($r) 
    {
        $t = new Pop_Template($r);
        $name = $r->get('name');
        if ($name) {
            $p = new Project();
            $p->name = $name;
            $p->created = date(DATE_ATOM);
            $p->insert();
        }
        $r->renderRedirect('projects');
    }

}

