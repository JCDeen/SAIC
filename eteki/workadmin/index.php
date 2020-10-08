<?php
    require_once ("db.php");
    //include_once ("login-check.php");
    include_once ("workadmin-header.php");
    include_once ("workadmin-nav.php");
    
    $debugDisplay = isset($_REQUEST['debug']) ? "block" : "none";
    $debugReports = ($debugDisplay == "none") ? "" : "?debug";
    $bLoggedIn = ( isset($_COOKIE['DCSATL_username']) ) ? true : false;
        
    $pageAction = ($_SERVER['REQUEST_METHOD'] == 'POST') ? 'process-form' : "";
    $formAction = (isset($_REQUEST['form-action'])) ? $_REQUEST['form-action'] : "login"; // default.
    $resultStr = "UNDEFINED";
    $loginMsg = "";
    $rowcount = 0;
    $userRole = "";
    //$bLoggedIn = false;
    
    switch ($pageAction)
    {
        case "process-form":
            if (mysqli_connect_errno())
            {
                $resultStr = "Failed to connect to MySQL: " . mysqli_connect_error();
            }
            
            if ($formAction == "login")
            {
                // Convert to lowercase ..........................
                $enteredUsername = isset( $_POST['username']) ? strtolower($_POST['username']) : "";
                $enteredPassword = isset( $_POST['password']) ? strtolower($_POST['password']) : "";
                
                // Add error handling here in case empty username/password, etc.
                
                $sql = "SELECT * FROM work_users WHERE username='$enteredUsername' AND password='$enteredPassword'";
                $result = mysqli_query($conn, $sql);
                
                if ($result)
                {
                    // Return the number of rows in result set
                    $rowcount = mysqli_num_rows($result);
                    $resultStr = sprintf("Result set has %d rows.\n", $rowcount);
                    
                    if ($pageAction == "process-form" && $result)
                    {
                        $obj = mysqli_fetch_object($result);
                        if ($rowcount && $obj->is_active)
                        {
                            $bLoggedIn = true;
                            $loginMsg = "$enteredUsername is logged in";
                            
                            //$cookieExpireTime = time() + 60*60*24*1; // one day
                            $cookieExpireTime = time() + 60*60*2*1; // 2 hours
                            setcookie("DCSATL_username", $enteredUsername, $cookieExpireTime);

                            $userRole = ($obj->is_admin) ? "admin" : "non-admin";
                            setcookie("DCSATL_user_role", $userRole, $cookieExpireTime);
                        }
                    }
                }
                else
                    $resultStr = "";
            } 
            else if ($formAction == "logout")
            {
                $bLoggedIn = false;
                setcookie("DCSATL_username", "", 1);  // Invalidate current cookie upon next page reload.
                setcookie("DCSATL_user_role", "", 1);  // Invalidate current cookie upon next page reload.
            }
            
            break;
            
        default:
            //$resultStr = "NO RESULTS";
    }
    
    if ( isset($_COOKIE['DCSATL_username']) && $bLoggedIn)
    {
        $loginMsg = $_COOKIE['DCSATL_username'] . " is logged in";
    }
    else
        $loginMsg = ($bLoggedIn) ? "You are logged in" : "You are NOT logged in";
        
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" href="workadmin.css">
</head>

<body>
	<?php ShowLogo("LOGIN Page"); ?>
    
    <!-- DEBUG DIV will be removed once its working smoothly -->
    <div id="debug-info" style="border: solid red 1px; width: 70%; display: <?php echo $debugDisplay; ?>;">
    <?php 
        echo '$pageAction=[' . $pageAction . "]<br/>"; 
        echo '$formAction=[' . $formAction . "]<br/>";
        
        echo "var_dump(\$_REQUEST):<br />\n";
        var_dump($_REQUEST);
        
        echo "var_dump(\$_COOKIE):<br />\n";
        var_dump($_COOKIE);
        
        echo "<p>Display Time: " . date('F d Y H:i:s A') . "</p>";
        
        if (isset($sql))
            echo "\$sql=[$sql]<br />";
        
        echo '$resultStr=[' . $resultStr . "]<br/>";
        echo '$_SERVER[\'REQUEST_METHOD\']=[' . $_SERVER['REQUEST_METHOD'] . "]<br/>";
        
    ?>
    </div>
    
    <?php echo $loginMsg; ?>
	<?php if (!$bLoggedIn) // If logged in then don't need to redisplay login form.
	{
	    // PASS hash + salt values here ?
	    ?>
        <form id="login-form" method="post" action="">
          <input type="hidden" name="process-form" value="login">
      	  <input type="hidden" name="form-action" value="login">
          <div class="inputDiv"><span class="input-label">UserName:</span> <input type="text" name="username" id="username-field" class="login-form-field" placeholder="Username" value="" required></span></div>
          <div class="inputDiv"><span class="input-label">Password:</span> <input type="password" name="password" id="password-field" class="login-form-field" placeholder="Password" value="" required></span></div>
          <div class="inputDiv"><input type="submit" value="Login" id="login-form-submit"></div>
        </form>
    <?php 
	}
	else
	{
    ?>
    	<!-- Give user chance to log out. -->
        <form id="logout-form" method="post" action="">
          <input type="hidden" name="process-form" value="logout">
          <input type="hidden" name="form-action" value="logout">
          <input type="submit" value="LOG OUT" id="logout-form-submit" title="Let's you explicitly log Out.">
        </form>
    	
        <p>
        
        <?php 
        
            $adminLinksShown = ShowNavLinks();
            if (($adminLinksShown!= true) && ($userRole == "admin"))
                ShowAdminLinks(true);
        ?>
        </p>
    <?php 
	}
    ?>
   
</body>
</html>
<?php 
    // Free result set
    if ($pageAction && isset($result) && isset($conn))
    {
        mysqli_free_result($result);
        mysqli_close($conn);
    }
?>

