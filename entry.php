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
            echo '<p>Not logged in! Please log in <a href="index.php">here</a>.</p>';
            die();
        } 
                
        $db_sqlite_path     = $_SESSION['db_sqlite_path']; 
        $user_name          = $_SESSION['user_name'];
     
        if (!isset($_SESSION['categories'])) {
            $_SESSION['categories'] = get_categories($db_sqlite_path);
        }
        $categories         = $_SESSION['categories'];   
        
        echo '<div class="topMessage"> <a href="index.php">Home</a> | ';
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']===1) {
            echo '<a href="_admin/_status.php">Admin</a> | ';
            echo '<a href="review.php">Review</a> | ';
        } else {
            echo '<a href="review.php?user_name=' . urlencode($user_name) . '">Review</a> | ';
        }
        echo 'Logged in as: ' . $user_name . ' <a href="index.php?action=logout">Log out</a> </div><hr/><br/><br/>';
        
?>
<div class="topForm">
    <h3 class="txtcenter">Task Configuration</h3>
    <form method="post" action="task.php" name="configform">
        <input type="hidden" id="config_input_hidden_idx" name="task_idx" value="1">
        <table>
            <tr><td><label for="config_select_category">Choose a category: </label></td></tr>
            <tr><td>
                    <select id="config_select_category" name="task_category">
							<option value="random" selected>random (recommended)</option>
							<?php 
							foreach ($categories as $curr) {
							?>
							<option value="<?php echo $curr; ?>"><?php echo $curr; ?></option>
							<?php 
							}
							?>
				    </select>
            </td></tr>
            <tr><td><label for="config_select_num">Number of tasks: </label></td></tr>
            <tr><td>
                    <select id="config_select_num" name="task_num">
                            <option value="1" >1</option>
                            <option value="3" >3</option>
                            <option value="5" >5</option>
							<option value="10" >10</option>
							<option value="20" selected>20</option>
				    </select>
            </td></tr>
            <tr><td><div class="txtcenter"><input type="submit" name="start" value="Start" /></div></td></tr>
        </table></form>
</div>
        
</body>

</html>
