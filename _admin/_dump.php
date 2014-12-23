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
 * This is a helper file that simply outputs the content of the users.db file.
 * Might be useful for your development.
 */

/* config */
error_reporting(E_ALL);

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

// create new database connection
$db_connection = new PDO($db_type . ':' . $db_sqlite_path);

/* table 1: users */

// query
$sql = 'SELECT * FROM users';

// execute query
$query = $db_connection->prepare($sql);
$query->execute();

// show all the data from the "users" table inside the database
echo "<div>";
var_dump($query->fetchAll());
echo "</div>";

/* table 2: annotations */

// query
$sql = 'SELECT * FROM annotations';

// execute query
$query = $db_connection->prepare($sql);
$query->execute();

// show all the data from the "annotations" table inside the database
echo "<div>";
var_dump($query->fetchAll());
echo "</div>";


/* table 3: tasks */

// query
$sql = 'SELECT * FROM tasks';

// execute query
$query = $db_connection->prepare($sql);
$query->execute();

// show all the data from the "annotations" table inside the database
echo "<div>";
var_dump($query->fetchAll());
echo "</div>";


?>

</body>
