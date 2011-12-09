<?php
//open the database
$db = new PDO('sqlite:../data/db.sqlite');

$tables = array();
$sql = "
    SELECT name FROM sqlite_master
    WHERE type='table'
    ORDER BY name
    ";
$sth = $db->prepare($sql);
$sth->execute();
foreach ($sth->fetchAll(PDO::FETCH_COLUMN) as $table) {
//    if (false === strpos($table,'search')) {
        $tables[$table] = array();
        $sql = "PRAGMA table_info($table)";
        $sth = $db->prepare($sql);
        $sth->execute();
        while ($row = $sth->fetch()) {
            if ('id' != $row['name']) {
                $tables[$table][] = $row['name'];
            }
        }
 //   }
}

print_r($tables);

