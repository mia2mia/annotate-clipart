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

include '../libraries/utils.php';

session_start();
        
if (!isset($_SESSION['user_is_logged_in']) || !$_SESSION['user_is_logged_in']) {
    echo '<p>Not logged in! Please log in <a href="../index.php?redirect='. buildRedirectStr() . '">here</a>.</p>';
    die();
} elseif (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo '<p>Not in admin group! Please <a href="../index.php?action=logout&redirect=' . buildRedirectStr() . '">log in</a> as a different user or contact <a href="mailto:hsu@cs.umass.edu">hsu</a> to request access.</p>';
    die();
}

$db_type = "sqlite";
$db_sqlite_path = "../data.db";

// create connection
$db_connection = new PDO($db_type . ':' . $db_sqlite_path);

/* retrieve current jobs */
$sql = "SELECT count(*) AS cnt
        FROM annotations 
        WHERE quality IS NULL;";

// execute the above query
$query = $db_connection->prepare($sql);
$query->execute();
$result_row = $query->fetchObject();
$cnt = $result_row->cnt;

if (intval($cnt)>0) {
    echo "<p>" . $cnt . " uncommited annotations. <a href='_clean.php?rm_stale=1&timeout=0'>Clear all</a></p>";
}


/* info 1: users */

$sql = "SELECT user_name,is_admin, datetime(last_login_time,'unixepoch','localtime') AS last_login,count(time_start) AS num_tasks,sum(duration) AS total_time_spent,avg(quality) AS avg_quality
        FROM users left outer join annotations using (user_name) 
        GROUP BY user_name
        ORDER BY num_tasks DESC, avg_quality DESC, user_name ASC;
        ";
$query = $db_connection->prepare($sql);
$query->execute();

$results = $query->fetchAll();
echo "<h3>Users</h3>";
echo "<table>";
echo "<tr><th>user_name</th><th>is_admin</th><th>last_login_time</th><th>tasks_done</th><th>total_time_spent</th><th>avg_quality</th> <tr/>";
foreach($results as $user) {
    if (intval($user['is_admin'])==1) {
        $is_admin = "YES";
    } else {
        $is_admin = "NO";
    }
    echo "<tr>";
    echo    "<td>" . $user['user_name'] . "</td>" . 
            "<td>" . $is_admin . "</td>" . 
            "<td>" . $user['last_login'] . " ET" . "</td>" . 
            "<td>" . $user['num_tasks'] . "</td>" . 
            "<td>" . sprintf('%.2f',intval($user['total_time_spent'])/60) . ' minutes' . "</td>" . 
            "<td>" . sprintf('%.1f',100*floatval($user['avg_quality'])) . '%' . "</td>";
            
    echo "</tr>";
}
echo "</table>";

/* info 2: tasks */
$sql = "SELECT task_category, count(is_active) AS num_active, count(DISTINCT task_id) AS num_tasks, avg(num_annotations) AS progress
        FROM tasks 
        GROUP BY task_category
        ORDER BY progress DESC, task_category ASC;";
$query = $db_connection->prepare($sql);
$query->execute();

$results = $query->fetchAll();
echo "<h3>Tasks</h3>";
echo "<table>";
echo "<tr><th>category_name</th><th>num_tasks (active)</th><th>#good</th><th>progress</th><tr/>";
foreach($results as $category) {
    $sql = "SELECT avg_annotation 
            FROM tasks
            WHERE avg_annotation AND task_category=:task_category;";
    $query = $db_connection->prepare($sql);
    $query->bindValue(':task_category', $category['task_category']);
    $query->execute();
    $annoArray = $query->fetchAll(PDO::FETCH_COLUMN, 0);
    echo "<tr>";
    echo    "<td>" . $category['task_category'] . "</td>" . 
            "<td>" . $category['num_tasks'] . " (" . $category['num_active'] . ")" . "</td>" . 
            "<td><a href='../showGood.php?category=" . $category['task_category'] . "'>" . strval(countOnes($annoArray)) . "</a></td>" .
            "<td>" . sprintf('%.1f',100*floatval($category['progress'])) . '%' . "</td>";
            
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='_dump.php'>Database dump</a></p>";

?>

</body>
