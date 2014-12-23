<!DOCTYPE HTML>
<html>
<head>

    <meta http-equiv="content-type" content="text/html; charset=UTF-8">

    <!-- Enable responsive view on mobile devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" type="text/css" href="style.css">
    
    <!-- Enable media queries for old IE -->
    <!--[if lt IE 9]>
    <script src="http://css3-mediaqueries-js.googlecode.com/svn/trunk/css3-mediaqueries.js"></script>
    <![endif]-->
    <title>Clipart Annotation Tool</title>
    
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <style>
        table, td, th {
            border: 1px solid black;
            border-collapse: collapse;
            text-align: center;
        }

        th {
            background-color: black;
            color: white;
        }
    </style>
</head>

<body>

<?php

/**
 * This is the installation file for the 0-one-file version of the php-login script.
 * It simply creates a new and empty database.
 */

/* config */
error_reporting(E_ALL);

$db_type = "sqlite";
$db_sqlite_path = "../data.db";
$data_dir = "../data";
$task_dir = $data_dir . "/tasks";

// create new database file / connection (the file will be automatically created the first time a connection is made up)
$db_connection = new PDO($db_type . ':' . $db_sqlite_path);

/* table 1: users */

// create new empty table inside the database (if table does not already exist)
$sql = 'CREATE TABLE IF NOT EXISTS `users` (
        `user_id`               INTEGER PRIMARY KEY,
        `user_name`             VARCHAR(64),
        `user_password_hash`    VARCHAR(255),
        `user_email`            VARCHAR(64),
        `user_in_lab`           VARCHAR(4),
        `last_login_time`       INTEGER, 
        `is_admin`              INTEGER DEFAULT 0);
        CREATE UNIQUE INDEX `user_name_UNIQUE` ON `users` (`user_name` ASC);
        CREATE UNIQUE INDEX `user_email_UNIQUE` ON `users` (`user_email` ASC);
        ';

// execute the above query
$query = $db_connection->prepare($sql);
$query->execute();

/* table 2: annotations */

// create new empty table inside the database (if table does not already exist)
$sql = 'CREATE TABLE IF NOT EXISTS `annotations` (
        `task_id`               INTEGER,
        `task_category`         VARCHAR(64),
        `user_name`             VARCHAR(64),
        `task_annotation`       VARCHAR(255),
        `time_start`            INTEGER,
        `duration`              INTEGER, 
        `quality`               REAL,
        PRIMARY KEY (`task_id`,`task_category`,`user_name`) ON CONFLICT REPLACE);
        ';

// execute the above query
$query = $db_connection->prepare($sql);
$query->execute();

/* table 3: tasks */

// create new empty table inside the database (if table does not already exist)
$sql = 'CREATE TABLE IF NOT EXISTS `tasks` (
        `task_category`         VARCHAR(64),
        `task_id`               INTEGER,
        `is_active`             INTEGER NOT NULL, 
        `num_annotations`       INTEGER,
        `avg_annotation` 		  VARCHAR(255), 
        PRIMARY KEY (`task_id`,`task_category`));
        ';

// execute the above query
$query = $db_connection->prepare($sql);
$query->execute();

// add tasks
$sql = 'SELECT * FROM tasks 
        LIMIT 1;';
$query = $db_connection->prepare($sql);
$query->execute();
$result_row = $query->fetchObject();
if ($result_row) {
    echo '<p>tasks already exists.</p>'; 
} else {
    $files_tasks = array_diff(scandir($task_dir),array('..','.'));
    foreach ($files_tasks as $task_name) {
        $task_name = trim($task_name);
        if(!is_file($task_dir . "/" . $task_name)) {
            continue;
        }
        $sepLoc = strrpos($task_name,'-');
        $task_category = substr($task_name,0,$sepLoc);
        $task_id = intval(substr($task_name,$sepLoc+1));
        $is_active = 1;
        $num_annotations = 0;
        
        $sql = 'INSERT INTO tasks (task_category, task_id, is_active, num_annotations) 
                VALUES (:task_category, :task_id, :is_active, :num_annotations);';
        $query = $db_connection->prepare($sql);
        $query->bindValue(':task_id', $task_id, PDO::PARAM_INT);
        $query->bindValue(':task_category', $task_category);
        $query->bindValue(':is_active', $is_active, PDO::PARAM_INT);
        $query->bindValue(':num_annotations', $num_annotations, PDO::PARAM_INT);
        $query->execute();
    }
}
        
/* check for success */

if (file_exists($db_sqlite_path)) {
    echo "<p>Database $db_sqlite_path was created.</p>";
    echo "<p>Installation was successful.</p>";
} else {
    echo "<p>Database $db_sqlite_path was not created.</p>";
    echo "<p>Installation was NOT successful. Missing folder write rights ?</p>";
}

?>

<p><a href='_status.php'>Status</a></p>
</body>
