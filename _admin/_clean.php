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

if (isset($_GET['rm_stale'])) {
	$rm_stale = intval($_GET['rm_stale']); 
} else {
	$rm_stale = 0; 
}

if (isset($_GET['timeout']) && strval($_GET['timeout'])!="") {
	$timeout = intval($_GET['timeout']);
} else {
	$timeout = 180;
}

if (isset($_GET['up_avg_anno'])) {
	$up_avg_anno = intval($_GET['up_avg_anno']); 
} else {
	$up_avg_anno = 0; 
}

if (isset($_GET['disable_category'])) {
	$disable_category = strval($_GET['disable_category']); 
}

if (isset($_GET['enable_category'])) {
	$enable_category = strval($_GET['enable_category']); 
}

if (!$rm_stale && !$up_avg_anno && empty($disable_category) && empty($enable_category)) {
    echo '<form action="_clean.php">
        <input type="checkbox" name="rm_stale" value="1">rm_stale 
        (timeout=<input type="text" name="timeout" value="180" size="4"> seconds)
        <br>
        <input type="checkbox" name="up_avg_anno" value="1">up_avg_anno
        <br><br>
        <input type="submit" value="clean">
        </form> 
    ';
    die();
}

$db_type = "sqlite";
$db_sqlite_path = "../data.db";

// create new connection
$db_connection = new PDO($db_type . ':' . $db_sqlite_path);
	
if(!empty($disable_category)) {
    $sql = "UPDATE tasks 
            SET is_active=0 
            WHERE task_category=:task_category;";
	// execute the above query
	$query = $db_connection->prepare($sql);
	$query->bindValue(':task_category', $disable_category);
	$query->execute();
	
	echo "<p>done! (" . $disable_category . " disabled)</p>";
}

if(!empty($enable_category)) {
    $sql = "UPDATE tasks 
            SET is_active=1 
            WHERE task_category=:task_category;";
	// execute the above query
	$query = $db_connection->prepare($sql);
	$query->bindValue(':task_category', $enable_category);
	$query->execute();
	
	echo "<p>done! (" . $enable_category . " enabled)</p>";
}

if ($rm_stale) { 

	echo "<p>Removing stale sessions more than " . strval($timeout) . " seconds old ...</p>";

	$sql = "SELECT count(*) AS cnt
		    FROM annotations 
		    WHERE quality IS NULL
		    AND time_submit<strftime('%s')-:timeout;";

	// execute the above query
	$query = $db_connection->prepare($sql);
	$query->bindValue(':timeout', $timeout);
	$query->execute();
	$result_row = $query->fetchObject();
	$cnt = $result_row->cnt;

	$sql = "DELETE FROM annotations 
		    WHERE quality IS NULL
		    AND time_submit<strftime('%s')-:timeout;";

	// execute the above query
	$query = $db_connection->prepare($sql);
	$query->bindValue(':timeout', $timeout);
	$query->execute();

	$sql = 'UPDATE tasks 
		    SET num_annotations = (
            SELECT count(*) 
            FROM annotations 
            WHERE task_id=tasks.task_id 
            AND task_category=tasks.task_category);';

	// execute the above query
	$query = $db_connection->prepare($sql);
	$query->execute();
	echo "<p>done! (" . strval($cnt) . " sessions removed)</p>";

}

if ($up_avg_anno) {

	if (isset($_GET['task_category']) && isset($_GET['task_id'])) {
		$task_list = array(array("task_category" => $_GET['task_category'], "task_id" => $_GET['task_id']));
	} else {
		$sql = "SELECT DISTINCT task_category, task_id
			    FROM annotations ;";
		$query = $db_connection->prepare($sql);
		$query->execute();
		$task_list = $query->fetchAll(PDO::FETCH_ASSOC);
		
		// clear existing avg_annotation
		$sql = 'UPDATE tasks 
			    SET avg_annotation=NULL;';
		$query = $db_connection->prepare($sql);
		$query->execute();
	}
	foreach ($task_list as $task) {
	        echo "<p>Averaging annotations of " . $task['task_category'] . "(#" . $task['task_id'] . ") ...";
            // update average annotation
            $sql = 'SELECT task_annotation FROM annotations 
                    WHERE task_id = :task_id AND task_category = :task_category;';
            $query = $db_connection->prepare($sql);
            $query->bindValue(':task_id', $task['task_id'], PDO::PARAM_INT);
            $query->bindValue(':task_category', $task['task_category']);
            $query->execute();
            $annotations = $query->fetchAll(PDO::FETCH_COLUMN, 0);
            $avg_annotation = "";
            for ($i=0;$i<strlen($annotations[0]);$i++) {
			    $cnt_total = 0;
                $cnt_good = 0;
                for ($j=0;$j<count($annotations);$j++) {
                    switch($annotations[$j][$i]) {
                        case "g":
                            $cnt_total+=1;
                            $cnt_good+=1;
                            break;
                        case "b":
                       	    $cnt_total+=1;
                       	    break;
                       	case "0":
                       	    $cnt_total+=2;
                       	    break;
                       	case "1":
                       	    $cnt_total+=2;
                       	    $cnt_good+=1;
                       	    break;
                       	case "2":
                       	    $cnt_total+=2;
                       	    $cnt_good+=2;
                       	    break;
                       	default: 
                       	    echo 'Error: corrupted annotation: ' . $annotations[$j];
                            die();
                    }
                }
                if($cnt_good>0.5*$cnt_total) {
                    $avg_annotation = $avg_annotation . "1";
                } else {
                    $avg_annotation = $avg_annotation . "0";
                }
            }
            $sql = 'UPDATE tasks 
                    SET avg_annotation=:avg_annotation 
                    WHERE task_id = :task_id AND task_category = :task_category;';
            $query = $db_connection->prepare($sql);
            $query->bindValue(':avg_annotation', $avg_annotation);
            $query->bindValue(':task_id', $task['task_id'], PDO::PARAM_INT);
            $query->bindValue(':task_category', $task['task_category']);
            $query->execute();
            echo " done! </p>";
    }
}

?>

<p><a href='_status.php'>Status</a></p>

</body>
