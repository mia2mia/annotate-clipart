<?php

function countOnes($annoArray) {
	$cnt = 0;
	foreach($annoArray as $anno) {
		$stat = array_count_values(str_split($anno));
		$cnt += $stat['1'];
	}
	return $cnt;
}

function buildRedirectStr() {
	$result = "?";
	foreach($_GET as $param => $val) {
		$result = $result . $param . "=" . strval($val) . "&";
	}
	if($result[strlen($result)-1]=="&") {
		$result = substr($result,0,strlen($result)-1);
	}
	if (strlen($result)==1) {
		$result = "";
	}
	return urlencode($_SERVER['SCRIPT_NAME'] . $result);
}

function compute_quality($annotation) {
	$cnt_consistent = 0;
	$cnt_inconsistent = 0;
	$annotation = str_split($annotation);
	foreach ($annotation as $c) {
		if ($c=='1') {
			$cnt_inconsistent += 1;
		} elseif ($c=='0' || $c=='2') {
			$cnt_consistent += 1;
		}
	}
	if ($cnt_inconsistent+$cnt_consistent==0) { // no verification
		return -1;
	} else {
		return $cnt_consistent/($cnt_inconsistent+$cnt_consistent);
	}
}

function get_categories($db_sqlite_path) {
	$db_type            = "sqlite";
	$db_connection      = new PDO($db_type . ':' . $db_sqlite_path);
	$sql = 'SELECT DISTINCT task_category
		FROM tasks 
		WHERE is_active IS 1;';
	$query = $db_connection->prepare($sql);
	$query->execute();
	$results = $query->fetchAll(PDO::FETCH_COLUMN);
	return $results;            
}

// all categories (including inactive ones)
function get_all_categories($db_sqlite_path) {
	$db_type            = "sqlite";
	$db_connection      = new PDO($db_type . ':' . $db_sqlite_path);
	$sql = 'SELECT DISTINCT task_category
		FROM tasks;';
	$query = $db_connection->prepare($sql);
	$query->execute();
	$results = $query->fetchAll(PDO::FETCH_COLUMN);
	return $results;            
}

function get_images_by_category($db_sqlite_path, $task_dir, $task_category, $encodePercent) {
	$db_type            = "sqlite";
	$db_connection      = new PDO($db_type . ':' . $db_sqlite_path);
	$sql = 'SELECT task_id, avg_annotation 
		FROM tasks 
		WHERE task_category=:task_category AND avg_annotation;';
	$query = $db_connection->prepare($sql);
	$query->bindValue(':task_category', $task_category);
	$query->execute();
	$results = $query->fetchAll(PDO::FETCH_ASSOC);
	$img_list   = array();
	foreach ($results as $task) {
		$task_path  = $task_dir . '/' . $task_category . '-' . strval($task['task_id']);
		$task_file  = fopen($task_path, "r") or die("<p>Error: Unable to load task!</p>");
		$idx = 0;
		while(!feof($task_file)) {
			$img_path = trim(fgets($task_file));
			if($img_path) {
				if ($task['avg_annotation'][$idx]=='1') {
					if ($encodePercent)
						$img_list[] = str_replace('%','%25',$img_path);
					else 
						$img_list[] = $img_path;
				}
				$idx += 1;
			}
		}
		fclose($task_file);
	}
	return $img_list;
}

?>
