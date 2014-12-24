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
            $("#instructionToggle").click(function(){
                if(document.getElementById("instruction").style.display=="none") {
                    document.getElementById("instruction").style.display="block";
                    $("#instructionToggle").html("hide instruction");
                    $("#preference_show_instruction").attr("value","1");
                } else {
                    document.getElementById("instruction").style.display = "none";
                    $("#instructionToggle").html("show instruction");
                    $("#preference_show_instruction").attr("value","0");
                }
            });
            updateImages();
            updateAnnotation();
        });
    </script>
</head>

<body>

<?php   

        include './libraries/utils.php';
                
        session_start();
        
        // check if logged in
        if (!isset($_SESSION['user_is_logged_in']) || !$_SESSION['user_is_logged_in']) {
            echo '<p>Not logged in! Please log in <a href="index.php">here</a>.</p>';
            die();
        } 
        
        // check if entered the right way
        if (!isset($_POST['task_category'])  || !isset($_POST['task_num']) || !isset($_POST['task_idx'])) {
            echo '<p>No task assigned. Please enter <a href="index.php">here</a>.</p>';
            die();
        }
        
        /* local variables */
        // from SESSION
        $categories         = $_SESSION['categories'];          //(category)
        $user_name          = $_SESSION['user_name'];           //string
        $db_sqlite_path     = $_SESSION['db_sqlite_path']; 
                
        // from POST
        $task_category      = $_POST['task_category'];
        $task_num           = intval($_POST['task_num']);
        $task_idx           = intval($_POST['task_idx']);
        
        // database connection
        $db_type            = "sqlite";
        $db_connection      = new PDO($db_type . ':' . $db_sqlite_path);
        
        $img_dir            = './data/imgs_rs256'; 
        $task_dir           = './data/tasks'; 
        $time_start         = time();
        
        /* status banner */
        echo '<div class="topMessage"> <a href="index.php">Home</a> | ';
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']===1) 
            echo '<a href="_admin/_status.php">Admin</a> | ';
        echo 'Logged in as: ' . $user_name . ' <a href="index.php?action=logout">Log out</a> </div><hr/><br/><br/>';
        
        /* Annotations from last task */
        if ($task_idx>1) {          
            // write data to database with prev_task_id, prev_user_name, prev_time_start, prev_annotation
            $prev_task_id       = intval($_POST['prev_task_id']);
            $prev_user_name     = $_POST['prev_user_name'];
            $prev_time_start    = intval($_POST['prev_time_start']);
            $prev_annotation    = $_POST['prev_annotation'];
            $prev_quality       = compute_quality($prev_annotation);
            
            $sql = 'SELECT * FROM annotations 
                    WHERE user_name = :user_name AND task_id = :task_id AND task_category = :task_category
                    LIMIT 1;';
            $query = $db_connection->prepare($sql);
            $query->bindValue(':task_id', $prev_task_id, PDO::PARAM_INT);
            $query->bindValue(':task_category', $task_category);
            $query->bindValue(':user_name', $prev_user_name);
            $query->execute();
            $result_row = $query->fetchObject();
                          
            // TODO use transaction here? 
            $sql = 'INSERT INTO annotations (task_id, task_category, user_name, task_annotation, time_start, duration, quality) 
                    VALUES (:task_id, :task_category, :user_name, :task_annotation, :time_start, :duration, :quality);';
            $query = $db_connection->prepare($sql);
            $query->bindValue(':task_id', $prev_task_id, PDO::PARAM_INT);
            $query->bindValue(':task_category', $task_category);
            $query->bindValue(':user_name', $prev_user_name);
            $query->bindValue(':task_annotation', $prev_annotation);
            $query->bindValue(':time_start', $prev_time_start, PDO::PARAM_INT);
            $query->bindValue(':duration', $time_start-$prev_time_start, PDO::PARAM_INT);
            $query->bindValue(':quality', strval($prev_quality));
            $query->execute();
            
            // update count (check the potential NULL place holder)
            if (!$result_row) {
                $sql = 'UPDATE tasks
                        SET num_annotations=num_annotations+1
                        WHERE task_id=:task_id
                            AND task_category=:task_category;';
                $query = $db_connection->prepare($sql);
                $query->bindValue(':task_id', $prev_task_id, PDO::PARAM_INT);
                $query->bindValue(':task_category', $task_category);
                $query->execute();
            }
            
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
                        
            if(strcmp($prev_user_name,$user_name)!=0) {
                echo '<p>Another user just logged in, please close page.</p>';
                die();
            }
        }
        
        /* all tasks finished */
        if ($task_idx>$task_num) {
            echo '<p>All tasks done. Please close page or <a href="index.php">start a new round</a>.</p>';
            die();
        } 
        
        /* current task */
        if (strcmp($task_category,'random')==0) {
            // choose category based on annotation density
            
            $sql = 'SELECT DISTINCT task_category, task_id 
                    FROM tasks T 
                    WHERE T.is_active IS NOT 0
                        AND NOT EXISTS (
                        SELECT * FROM annotations A 
                        WHERE A.user_name=:user_name 
                            AND A.task_id=T.task_id 
                            AND A.task_category=T.task_category
                        )
                    ORDER BY T.num_annotations ASC
                    LIMIT 1;';
            $query = $db_connection->prepare($sql);
            $query->bindValue(':user_name', $user_name);
            $query->execute();
            $result_row = $query->fetchObject();
            if ($result_row) {
                $task_category = $result_row->task_category;
                $task_id = $result_row->task_id;
            } else {
                echo '<p>There is no more tasks to show you right now. Stay tunned!</p>';
                die();
            }
        }
        
        // Choose a task_id (randomly?) based on annotation density
        if (!isset($task_id)) {
            $sql = 'SELECT DISTINCT task_id 
                    FROM tasks T 
                    WHERE T.task_category=:task_category
                    	AND T.is_active IS NOT 0
                        AND NOT EXISTS (
                        SELECT * FROM annotations A 
                        WHERE A.user_name=:user_name 
                            AND A.task_id=T.task_id 
                            AND A.task_category=:task_category
                        )
                    ORDER BY T.num_annotations ASC
                    LIMIT 1;';
            $query = $db_connection->prepare($sql);
            $query->bindValue(':user_name', $user_name);
            $query->bindValue(':task_category', $task_category);
            $query->execute();
            $result_row = $query->fetchObject();
            if ($result_row) {
                $task_id = $result_row->task_id;
            } else {
                echo '<p>You\' finished all the task in current category. Please <a href="index.php"> choose a new category</a>. </p>';
                die();
            }
        }
        
        // reserve task
        $sql = 'INSERT INTO annotations (task_id, task_category, user_name, time_start) 
                VALUES (:task_id, :task_category, :user_name, :time_start);';
        $query = $db_connection->prepare($sql);
        $query->bindValue(':task_id', $task_id, PDO::PARAM_INT);
        $query->bindValue(':task_category', $task_category);
        $query->bindValue(':user_name', $user_name);
        $query->bindValue(':time_start', $time_start, PDO::PARAM_INT);
        $query->execute();
        $sql = 'UPDATE tasks
                SET num_annotations=num_annotations+1
                WHERE task_id=:task_id
                    AND task_category=:task_category;';
        $query = $db_connection->prepare($sql);
        $query->bindValue(':task_id', $task_id, PDO::PARAM_INT);
        $query->bindValue(':task_category', $task_category);
        $query->execute();
        
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
        $task_annotation = $default_annotation;
        
        // whether show instruction
        if(isset($_POST['show_instruction'])) {
            $show_instruction = intval($_POST['show_instruction']);
        } else {
            $sql = 'SELECT count(*) AS cnt
                    FROM annotations 
                    WHERE user_name=:user_name AND quality IS NOT NULL;';
            $query = $db_connection->prepare($sql);
            $query->bindValue(':user_name', $user_name);
            $query->execute();
            $result_row = $query->fetchObject();
            if (intval($result_row->cnt)>3) 
                $show_instruction = 0;
            else 
                $show_instruction = 1;
        }
?>

    <div id="taskBanner">
        <div id="taskTools">
            <button id="toggleAll" type="button">Toggle All</button>
            <button id="clear" type="button">Clear</button>
        </div>

        <p id="taskCounter">Task <?php echo strval($task_idx) . '/' . strval($task_num) . ' (' . $task_category . '#' . $task_id . ')';?> </p>
        <p id="taskTitle"><span class="crucial">Cross Out Images That Are <b>NOT <?php echo strtoupper($task_category)?> CLIPARTS</b></span> 
        <span id="instructionToggle"><?php if(!$show_instruction) echo "show instruction"; else echo "hide instruction"?></span></p>
    </div>
    <div id="instruction" <?php if(!$show_instruction) echo "style=\"display:none;\"";?>>
    
    <div class="instructionText topForm">
        <p><b>Tip</b>: If the majority of images seem to be unqualified, you can first cross out good ones instead and then use the "Toogle All" button. </p> 
        <table id="instructionTable">
            <tr>
                <th style="width:20%">GUIDELINES</th><th style="width:40%">GOOD</th><th style="width:40%">BAD</th>
            </tr>
            <tr>
                <td>(1) Keep only clipart images (incl. catoon-style ones), computer-made line drawings, </br><em class="emNot">NOT</em> photos, handrawings, paintings.</td>
                <td>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule1/good1.png" alt="good image"><p class="imgDesc">good</p></div>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule1/good2.png" alt="good image"><p class="imgDesc">good</p></div>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule1/good3.png" alt="good image"><p class="imgDesc">good</p></div>
                </td>
                <td>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule1/bad1_photo.png" alt="photo"><p class="imgDesc">Bad because it's a photo.</p></div>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule1/bad2_hand1.png" alt="hand drawing"><p class="imgDesc">Bad because it's a hand drawing.</p></div>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule1/bad2_hand2.png" alt="hand drawing"><p class="imgDesc">Bad because it's a hand drawing.</p></div>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule1/bad3_painting.png" alt="painting"><p class="imgDesc">Bad because it's a painting.</p></div>
                </td>
            </tr>
            <tr>
                <td>(2) Only a single, entire object of interest should be in the image, </br><em class="emNot">NOT</em> multiple objects or object parts.</td>
                <td> 
                </td>
                <td>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule2/bad1_multiple.png" alt="multiple objects"><p class="imgDesc">Bad because it contains multiple objects of interest.</p></div>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule2/bad2_partial.png" alt="partial object"><p class="imgDesc">Bad because doesn't contain an entire tiger.</p></div>
                </td>
            </tr>
            <tr>
                <td>(3) Object should <em class="emNot">NOT</em> be too small - its bounding box should occupy at least 1/3 area of image.</td>
                <td> 
                </td>
                <td>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule3/bad1_toosmall.png" alt="too small"><p class="imgDesc">Bad because the airplane is too small.</p></div>
                </td>
            </tr>
            <tr>
                <td>(4) Image can contain shadow, background, text, or other less significant types of things/stuff, but collectively they should <em class="emNot">NOT</em> exceed the size of the object of interest. <br/>Plain or gradient background, even when large, is fine.</td>
                <td>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule4/good1.png" alt="good image"><p class="imgDesc">Slight background, text, etc. is fine.</p></div>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule4/good2.png" alt="good image"><p class="imgDesc">Slight background, text, etc. is fine.</p></div>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule4/good3.png" alt="good image"><p class="imgDesc">Slight background, text, etc. is fine.</p></div>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule4/good4.png" alt="good image"><p class="imgDesc">Plain or gradient background, even when large, is fine.</p></div>
                </td>
                <td>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule4/bad1_other.png" alt="rider on a bike"><p class="imgDesc">Bad bike image because it contains a rider which occupies larger area than the bike.</p></div>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule4/bad2_bgTooLarge.png" alt="plane over earth"><p class="imgDesc">Bad because the background (earth) is too large.</p></div>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule4/bad3_bg.png" alt="plane over ground"><p class="imgDesc">Bad because the background (ground and sky) is too large.</p></div>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule4/bad4.png" alt="shadow"><p class="imgDesc">Bad because the whole image is covered with shadow.</p></div>
                </td>
            </tr>
            <tr>
                <td>(5) Image should <em class="emNot">NOT</em> be in unnatural orientations.</td>
                <td> 
                </td>
                <td>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule5/bad1.png" alt="unntatural orientation"><p class="imgDesc">Bad because the airplane is in unnatural orientation.</p></div>
                    <div class="exampleImgWrap"><img class="exampleImg" src="examples/rule5/bad2.png" alt="unntatural orientation"><p class="imgDesc">Bad because the airplane is in unnatural orientation.</p></div>
                </td>
            </tr>
        </table>
</div>
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
    
    <input type="hidden" id="tmp_vars"  name="tmp_vars" value="" defaultAnnotation="<?php echo $default_annotation?>" annotation="<?php echo $task_annotation; ?>">
    
    <form method="post" action="task.php" name="annotationform">
            <input type="hidden" id="annotation_prev_task_id"       name="prev_task_id"     value="<?php echo $task_id; ?>">
            <input type="hidden" id="annotation_prev_time_start"    name="prev_time_start"  value="<?php echo $time_start; ?>">
            <input type="hidden" id="annotation_prev_user_name"     name="prev_user_name"   value="<?php echo $user_name; ?>">
            <input type="hidden" id="annotation_prev_annotation"    name="prev_annotation"  value="" >
            <input type="hidden" id="annotation_task_idx"           name="task_idx"         value="<?php echo $task_idx+1; ?>">
            <input type="hidden" id="annotation_task_category"      name="task_category"    value="<?php echo $task_category; ?>">
            <input type="hidden" id="annotation_task_num"           name="task_num"         value="<?php echo $task_num; ?>">
            <div class="txtcenter">
            <input type="submit" id="annotation_submit"             name="next"             value="<?php if($task_idx==$task_num) echo 'Finish'; else echo 'Next'; ?>" />
            
            <input type="hidden" id="preference_show_instruction"   name="show_instruction" value="">
            </div>
    </form>

</body>

</html>
