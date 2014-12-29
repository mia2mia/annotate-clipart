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
    <script>
        function annotationEncode(rawAnnotation) {
            var numImgsWithRep = rawAnnotation.length;
            var numImgs = (numImgsWithRep>=20) ? (numImgsWithRep-10) : (numImgsWithRep/2);
            var arr = rawAnnotation.split("");
            for(var i=0;i<(numImgsWithRep-numImgs);i++) {
                if(arr[i]===arr[i+numImgs]) {
                    if(arr[i]==="g") 
                        arr[i]="2";
                    else
                        arr[i]="0";                    
                } else {
                        arr[i]="1";
                }
            }
            return arr.slice(0,numImgs).join("");
        }
        function updateImages() {
            var annotation = $("#tmp_vars").attr("annotation");
            var numImg = annotation.length;
            for(var i=0;i<numImg;i++) {
                slct = ".imgGridBox#img_"+i.toString();
                var cross = $(slct).find(".cross");
                if (annotation.charAt(i)=='g') {
                    cross.css("opacity",0);
                    cross.css("visibility","hidden");
                } else {
                    cross.css("opacity",0.67);
                    cross.css("visibility","visible");
                }
            }
        }
        function updateAnnotation() {
            var raw_annotation = $("#tmp_vars").attr("annotation");
            $("#annotation_prev_annotation").attr("value",annotationEncode(raw_annotation));            
        }
        $(document).ready(function(){
            $("button#toggleAll").click(function(){
                $(".img").click();
            });
            $("button#clear").click(function(){
                var default_annotation = $("#tmp_vars").attr("defaultAnnotation");
                $("#tmp_vars").attr("annotation",default_annotation);
                updateImages();
                updateAnnotation();
            });
            $(".imgGridBox").click(function(){
                var annotation = $("#tmp_vars").attr("annotation");
                var img_id = $(this).attr("id");
                img_id = parseInt(img_id.slice(4,img_id.length));
                var cross = $(this).find(".cross");
                if (cross.css("opacity")==0) {
                    cross.css("opacity",0.67);
                    cross.css("visibility","visible");
                    annotation = annotation.slice(0,img_id)+"b"+annotation.slice(img_id+1,annotation.length);
                } else {
                    cross.css("opacity",0);
                    cross.css("visibility","hidden");
                    annotation = annotation.slice(0,img_id)+"g"+annotation.slice(img_id+1,annotation.length);
                }
                $("#tmp_vars").attr("annotation",annotation);
                updateAnnotation();
            });
            updateImages();
            updateAnnotation();
        });
    </script>
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

        include './libraries/utils.php';
                
        session_start();
        
        // check if logged in
        if (!isset($_SESSION['user_is_logged_in']) || !$_SESSION['user_is_logged_in']) {
            echo '<p>Not logged in! Please log in <a href="index.php?redirect='. buildRedirectStr() . '">here</a>.</p>';
            die();
        } 
        
        // from SESSION
        $categories         = $_SESSION['categories'];          //(category)
        $user_name          = $_SESSION['user_name'];           //string
        $is_admin           = $_SESSION['is_admin'];
        $db_sqlite_path     = $_SESSION['db_sqlite_path']; 
        
        // database connection
        $db_type            = "sqlite";
        $db_connection      = new PDO($db_type . ':' . $db_sqlite_path);
        
        $img_dir            = './data/imgs_rs256'; 
        $task_dir           = './data/tasks'; 
        $time_start         = time();
        
        // status banner
        echo '<div class="topMessage"> <a href="index.php">Home</a> | ';
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']===1) {
            echo '<a href="_admin/_status.php">Admin</a> | ';
            echo '<a href="review.php">Review</a> | ';
        } else {
            echo '<a href="review.php?user_name=' . urlencode($user_name) . '">Review</a> | ';
        }
        echo 'Logged in as: ' . $user_name . ' <a href="index.php?action=logout">Log out</a> </div><hr/><br/><br/>';
              
        // entrance 1: nothing specified
        if (!isset($_GET['user_name'])  && !(isset($_GET['task_category']) && isset($_GET['task_id']))) {
            if ($is_admin) {
                $sql = "SELECT user_name
                        FROM users;";
                $query = $db_connection->prepare($sql);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_COLUMN,0);
                echo "<table>";
                echo "<tr><th>user_name</th></tr>";
                foreach ($results as $user) {
                    echo "<tr><td>";
                    echo '<a href="review.php?user_name=' . urlencode($user) . '">' . $user . '</a>';
                    echo "</td></tr>";
                }
                echo "</table>";
                die();
            } else {
                echo '<p>No task specified. Please <a href="review.php?user_name=' . urlencode($user_name) . '">choose a task</a>.</p>';
                die();
            }
        }
        
        // entrance 2: user_name only
        if (isset($_GET['user_name']) && !(isset($_GET['task_category']) && isset($_GET['task_id']))) {
            if ($is_admin || ($_GET['user_name']==$user_name)) {
                $sql = "SELECT task_category, task_id 
                        FROM annotations 
                        WHERE user_name=:user_name AND quality IS NOT NULL 
                        ORDER BY task_category, task_id;";
                $query = $db_connection->prepare($sql);
                $query->bindValue(':user_name', $_GET['user_name']);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_ASSOC);
                echo "<table>";
                echo "<tr><th>task_category</th><th>task_id</th></tr>";
                for($i=0;$i<count($results);) {
                    echo "<tr>";
                    echo "<td>" . $results[$i]['task_category'] . "</td>";
                    echo "<td>";
                    $first_idx = $i;
                    while($i==$first_idx || ($i<count($results) && $results[$i]['task_category'] == $results[$i-1]['task_category'])) {
                        echo '<a href="review.php?user_name=' . urlencode($_GET['user_name']) 
                        . '&task_category='  . urlencode($results[$i]['task_category']) 
                        . '&task_id=' . urlencode($results[$i]['task_id']) . '">' . strval($results[$i]['task_id']) . '</a> ';
                        $i++;
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                die();
            } else {
                echo '<p>Permission denied! Please <a href="index.php?action=logout&redirect=' . buildRedirectStr() . '">log in</a> as an admin user or contact <a href="mailto:hsu@cs.umass.edu">hsu</a> to request access.</p>';
                die();
            }
        }
        
        // entrance 3: task_category, task_id provided
        if (!isset($_GET['user_name']) && (isset($_GET['task_category']) && isset($_GET['task_id']))) {
            if ($is_admin) {
                $sql = "SELECT user_name
                        FROM annotations 
                        WHERE task_category=:task_category AND task_id=:task_id 
                            AND quality IS NOT NULL;";
                $query = $db_connection->prepare($sql);
                $query->bindValue(':task_category', $_GET['task_category']);
                $query->bindValue(':task_id', $_GET['task_id']);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_COLUMN,0);
                echo "<table>";
                echo "<tr><th>user_name</th></tr>";
                foreach ($results as $user) {
                    echo "<tr><td>";
                    echo '<a class="userSelector" href="review.php?user_name=' . urlencode($user) 
                        . '&task_category='  . urlencode($_GET['task_category']) 
                        . '&task_id=' . urlencode($_GET['task_id']) . '">' . $user . '</a>';
                    echo "</td></tr>";
                }
                echo "</table>";
                if (count($results)==1) 
                    echo "<script>var href = $('.userSelector').attr('href');window.location.href = href;</script>";
                die();
            } else {
                echo '<p>Permission denied! Please <a href="index.php?action=logout&redirect=' . buildRedirectStr() . '">log in</a> as an admin user or contact <a href="mailto:hsu@cs.umass.edu">hsu</a> to request access.</p>';
                die();
            }
        }
        
        // entrance 4: user_name, task_category, task_id provided
        
        // from GET
        $task_user          = $_GET['user_name'];
        $task_category      = $_GET['task_category'];
        $task_id            = intval($_GET['task_id']);
        
        if (!$is_admin && ($task_user!=$user_name)) {
            echo '<p>Permission denied! Please <a href="index.php?action=logout&redirect=' . buildRedirectStr() . '">log in</a> as an admin user or contact <a href="mailto:hsu@cs.umass.edu">hsu</a> to request access.</p>';
            die();
        }
        
        // check if need to submit from previous page
        if (isset($_POST['prev_task_id']) 
        && isset($_POST['prev_task_category']) 
        && isset($_POST['prev_task_user']) 
        && isset($_POST['prev_time_start']) 
        && isset($_POST['prev_annotation'])) {
            
            $prev_task_id       = intval($_POST['prev_task_id']);
            $prev_task_category = $_POST['prev_task_category'];
            $prev_task_user     = $_POST['prev_task_user'];
            $prev_time_start    = intval($_POST['prev_time_start']);
            $prev_annotation    = $_POST['prev_annotation'];
            $prev_quality       = compute_quality($prev_annotation);
            
            $sql = 'SELECT duration FROM annotations 
                    WHERE user_name = :user_name AND task_id = :task_id AND task_category = :task_category
                    LIMIT 1;';
            $query = $db_connection->prepare($sql);
            $query->bindValue(':task_id', $prev_task_id, PDO::PARAM_INT);
            $query->bindValue(':task_category', $task_category);
            $query->bindValue(':user_name', $prev_task_user);
            $query->execute();
            $prev_record = $query->fetch(PDO::FETCH_ASSOC);
                          
            // update annotation
            $sql = 'INSERT INTO annotations (task_id, task_category, user_name, task_annotation, time_submit, duration, quality) 
                    VALUES (:task_id, :task_category, :user_name, :task_annotation, :time_submit, :duration, :quality);';
            $query = $db_connection->prepare($sql);
            $query->bindValue(':task_id', $prev_task_id, PDO::PARAM_INT);
            $query->bindValue(':task_category', $prev_task_category);
            $query->bindValue(':user_name', $prev_task_user);
            $query->bindValue(':task_annotation', $prev_annotation);
            $query->bindValue(':time_submit', $time_start, PDO::PARAM_INT);
            $query->bindValue(':quality', strval($prev_quality));
            if ($prev_task_user==$user_name) {
                $query->bindValue(':duration', $time_start-$prev_time_start+intval($prev_record['duration']), PDO::PARAM_INT);
            } else {
                $query->bindValue(':duration', intval($prev_record['duration']), PDO::PARAM_INT);
            }
            $query->execute();
                        
            // update average annotation
            $sql = 'SELECT task_annotation FROM annotations 
                    WHERE task_id = :task_id AND task_category = :task_category;';
            $query = $db_connection->prepare($sql);
            $query->bindValue(':task_id', $prev_task_id, PDO::PARAM_INT);
            $query->bindValue(':task_category', $task_category);
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
            $query->bindValue(':task_id', $prev_task_id, PDO::PARAM_INT);
            $query->bindValue(':task_category', $task_category);
            $query->execute();
            
            echo '<p>Successfully submitted.</p>';
            die();
        }
                
        /* current task */
                
        // original annotation 
        $sql = 'SELECT task_annotation FROM annotations 
                WHERE task_id = :task_id AND task_category = :task_category 
                    AND user_name = :task_user;';
        $query = $db_connection->prepare($sql);
        $query->bindValue(':task_id', $task_id, PDO::PARAM_INT);
        $query->bindValue(':task_category', $task_category);
        $query->bindValue(':task_user', $task_user);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        $task_annotation = annotationDecode($result['task_annotation']);
        
        // load task 
        $task_path  = $task_dir . '/' . $task_category . '-' . strval($task_id);
        $task_file  = fopen($task_path, "r") or die("<p>Error: Unable to load task!</p>");
        $img_list   = array();
        while(!feof($task_file)) {
            $img_path = trim(fgets($task_file));
            if($img_path)
                $img_list[] = str_replace('%','%25',$img_path);
        }
        fclose($task_file);
        $num_imgs = count($img_list);
        $num_imgs_w_reps = $num_imgs>=10 ? ($num_imgs+10):($num_imgs+$num_imgs);
        $default_annotation = "";
        for ($i=0;$i<$num_imgs_w_reps;$i++) {
            $default_annotation = $default_annotation . "g";
        }
        
?>

    <div id="taskBanner">
        <div id="taskTools">
            <button id="toggleAll" type="button">Toggle All</button>
            <button id="clear" type="button">Clear</button>
        </div>

        <p id="taskCounter">Task <?php echo strval($task_idx) . '/' . strval($task_num) . ' (' . $task_category . '#' . $task_id . ')';?> </p>
        <p id="taskTitle"><span class="crucial">REVIEW</span></p>
    </div>

    
    <!--Responsive Image Grid-->
    <div class="wrap">
    
    <?php 
        $idxs = range(0,$num_imgs_w_reps-1);
        shuffle($idxs);
        for($i=0;$i<$num_imgs_w_reps;$i++):
            $idx = $idxs[$i];
            $idx_img = ($idx>=$num_imgs) ? ($idx-$num_imgs):($idx) ;
    ?>
    <div class="imgGridBox" id ="<?php echo 'img_' . strval($idx)?>" >
            <img class="img" src="<?php echo $img_dir . '/' . $img_list[$idx_img];?>" alt="<?php echo $img_dir . '/' . $img_list[$idx_img];?>" />
            <img class="cross" src="data/redcross.png" alt="CROSSED OUT"/>
    </div>
    <?php endfor;?>
    
    </div>
	<!--End Responsive Image Grid-->
    
    <div id="taskBanner">
        <div id="taskTools">
            <button id="toggleAll" type="button">Toggle All</button>
            <button id="clear" type="button">Clear</button>
        </div>
    </div>
    
    <input type="hidden" id="tmp_vars"  name="tmp_vars" value="" defaultAnnotation="<?php echo $default_annotation?>" annotation="<?php echo $task_annotation; ?>">
    
    <form method="post" action="<?php echo 'review.php?user_name=' . urlencode($task_user) . '&task_category=' . urlencode($task_category) . '&task_id=' . urlencode($task_id); ?>" name="annotationform">
            <input type="hidden" id="annotation_prev_task_id"       name="prev_task_id"         value="<?php echo $task_id; ?>">
            <input type="hidden" id="annotation_prev_task_category" name="prev_task_category"   value="<?php echo $task_category; ?>">
            <input type="hidden" id="annotation_prev_task_user"     name="prev_task_user"       value="<?php echo $task_user; ?>">
            <input type="hidden" id="annotation_prev_time_start"    name="prev_time_start"      value="<?php echo $time_start; ?>">
            <input type="hidden" id="annotation_prev_annotation"    name="prev_annotation"      value="" >
            <div class="txtcenter">
            <input type="submit" id="annotation_submit"             name="next"                 value="Submit" />
            
            </div>
    </form>

</body>

</html>
