<?php
try {
    $data_dir = dirname(__FILE__).'/data';
    $db_file = $data_dir.'/db.sqlite';

    if (!is_writable($data_dir)) {
        echo "$data_dir is not writable.";
        exit;
    } else {
        if (!file_exists($db_file)) {
            touch($db_file);
        } else {
            echo "$db_file already exists";
            exit;
        }
    }
    //open the database
    $db = new PDO('sqlite:'.$db_file);

    //create the table 
    $db->exec("CREATE TABLE project (id INTEGER PRIMARY KEY, created TEXT, sort_order INTEGER, name TEXT)");    
    $db->exec("CREATE TABLE task (id INTEGER PRIMARY KEY, project_id INTEGER, date TEXT, count INTEGER)");    
    $db->exec("CREATE INDEX idx_project_id ON task (project_id)");
    $ts = date(DATE_ATOM);
    $db->exec("INSERT INTO project (name,created) VALUES ('test','$ts')");
    $db = NULL;
    echo "<html><body>database has been created <a href=\".\">continue</a></body></html>";
} catch(PDOException $e) {
    print 'Exception : '.$e->getMessage();
}

