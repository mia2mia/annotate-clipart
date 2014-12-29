<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">

    <!-- Enable responsive view on mobile devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" type="text/css" href="style.css">
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <!-- Enable media queries for old IE -->
    <!--[if lt IE 9]>
    <script src="http://css3-mediaqueries-js.googlecode.com/svn/trunk/css3-mediaqueries.js"></script>
    <![endif]-->
    <title>Clipart Annotation Tool</title>
</head>

<body>

<?php

        include './libraries/utils.php';
        
        session_start();
        
        if (!isset($_SESSION['user_is_logged_in']) || !$_SESSION['user_is_logged_in']) {
	        echo '<p>Not logged in! Please log in <a href="index.php?redirect='. buildRedirectStr() . '">here</a>.</p>';
	        die();
        } elseif (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
	        echo '<p>Not in admin group! Please <a href="index.php?action=logout&redirect=' . buildRedirectStr() . '">log in</a> as a different user or contact <a href="mailto:hsu@cs.umass.edu">hsu</a> to request access.</p>';
	        die();
        }
             
        $db_sqlite_path     = $_SESSION['db_sqlite_path']; 
        $user_name          = $_SESSION['user_name'];
        $categories         = get_all_categories($db_sqlite_path);  
        $task_dir           = './data/tasks'; 
        $img_dir            = './data/imgs_rs256'; 

        // choose a category
        if (!isset($_GET['category'])) {
            echo '<form action="showGood.php">
                  <select name="category">';
            foreach ($categories as $curr) {
                echo '<option value="' . $curr . '">' . $curr . '</option>';
            }
            echo '</select> 
                  <input type="radio" name="resultType" value="text">Text
                  <input type="radio" name="resultType" value="image" checked>Image <br/><br/>
                  <input type="submit" value="Show">
                  </form>
            ';
            die();
        }
        $category = $_GET['category'];
        
        if(isset($_GET['resultType']) && $_GET['resultType']=='text') {
            $img_list_raw = get_images_by_category($db_sqlite_path, $task_dir, $category,0);
            foreach($img_list_raw as $img=>$task_id) {
                echo $img . '<br/>';
            }
        } else {
            $img_list = get_images_by_category($db_sqlite_path, $task_dir, $category,1);
            
            //Responsive Image Grid-->
            echo '<div class="wrap">';
            foreach($img_list as $img_path=>$task_id) {
                echo '<div class="imgGridBox">
                        <a href="review.php?task_category=' . urlencode($category) . '&task_id=' . urlencode(strval($task_id)) . '"><img class="img" src="' . $img_dir . '/' . $img_path . '" alt="' . $img_dir . '/' . $img_path . '" /></a>
                      </div>';
            }
            echo '</div>';
	        //End Responsive Image Grid-->
        }   
        
?>
        
</body>

</html>
