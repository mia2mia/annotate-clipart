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

// create new database file / connection (the file will be automatically created the first time a connection is made up)
$db_connection = new PDO($db_type . ':' . $db_sqlite_path);

$sql = 'ALTER TABLE `annotations` 
        RENAME TO `annotations_old`;';
$query = $db_connection->prepare($sql);
$query->execute();

$sql = 'CREATE TABLE `annotations` (
        `task_id`               INTEGER,
        `task_category`         VARCHAR(64),
        `user_name`             VARCHAR(64),
        `task_annotation`       VARCHAR(255),
        `time_submit`           INTEGER,
        `duration`              INTEGER, 
        `quality`               REAL,
        PRIMARY KEY (`task_id`,`task_category`,`user_name`) ON CONFLICT REPLACE);
        ';
$query = $db_connection->prepare($sql);
$query->execute();

$sql = 'INSERT INTO `annotations` 
            SELECT task_id, task_category, user_name, task_annotation, time_start+duration, duration, quality 
            FROM annotations_old;';
$query = $db_connection->prepare($sql);
$query->execute();


echo "<p>Finished!</p>";

?>

<p><a href='_status.php'>Status</a></p>
</body>
