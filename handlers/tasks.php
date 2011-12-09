<?php

class Pop_Handler_Tasks extends Pop_Handler
{
    public $resource_map = array(
        '/' => 'form',
        '{year}/{mo}/{dt}' => 'form',
        'tasks' => 'tasks',
    );

    protected function setup($r)
    {
    }

    public function getForm($r) 
    {
        $t = new Pop_Template($r);

        if ($r->get('year')) {
        $year = $r->get('year');
        $mo = $r->get('mo');
        $date = $r->get('dt');
        $ymd = $year.'-'.$mo.'-'.$date;
        } else {
            $ymd = date('Y-m-d');
        }
        $tasks = new Task();
        $tasks->date = $ymd;
        $counts = array();
        foreach ($tasks->findAll(1) as $task) {
            $counts[$task->project_id] = $task->count;
        }
        $p = new Project();
        $p->orderBy('name');
        $projects = $p->findAll(1);
        foreach ($projects as $proj) {
            if (!isset($counts[$proj->id])) {
                $counts[$proj->id] = 0;
            }
        }

        $t->assign('counts',$counts);


        $ts = strtotime($ymd);

        $t->assign('ymd',$ymd);
        $t->assign('yest',date('Y/m/d',$ts-86400));
        $t->assign('tomm',date('Y/m/d',$ts+86400));


        $t->assign('ts',$ts);
        $t->assign('projects',$projects);
        $r->renderResponse($t->fetch('new_task_form.tpl'));
    }

    public function postToTasks($r) 
    {
        $task = new Task();
        $task->date = $r->get('ymd');
        $task->project_id = $r->get('project_id');
        if (!$task->findOne()) {
            $task->insert();
        }
        $task->count = $r->get('count');
        $task->update();
        $r->renderResponse('updated '.$task->id);
    }

}

