<?php
session_start();
header("Cache-Control: no-cache"); // Ensures IE does not cache the page
ob_start();
/****************************************/
/*      Copyright (c) Dale Mckeown		*/
/*       Last edited: 15/01/2012        */
/*        www.dalemckeown.co.uk  		*/
/****************************************/

// Include a commons file. (common variable definitions and functions)
include_once $_SERVER['DOCUMENT_ROOT']."/_common.php";

/*Mongo connection*/
$db = mongoConnect();

// Detect $_GET parameters
if(!empty($_GET['code'])){
	$code = strtolower(substr($_GET['code'],0,32));
	$state = substr($_GET['state'],0,15);
	$explode = explode("/", $state);
	$state = $explode[0];
	if(!empty($explode[1])){
		$_SESSION['Prototype_ID'] = $explode[1];
	}
}
else if(!empty($_GET['error']) || (!empty($_GET['error_message']))){ //0Auth Error
	$error = strtolower(substr($_GET['error'],0,32));
	$error_code = strtolower(substr($_GET['error_message'],0,200));
}

// Get prototypes
$prototypes = getPrototypes();
$prototypeArray = $prototypes['prototypeArray'];
$currentPrototypeLink = $prototypes['currentPrototypeLink'];

?>
<!doctype html>
<!--[if IEMobile 7 ]><html class="no-js iem7" lang="en"><![endif]-->
<!--[if lt IE 7 ]><html class="no-js ie ie6" lang="en"><![endif]-->
<!--[if IE 7 ]><html class="no-js ie ie7" lang="en"><![endif]-->
<!--[if IE 8 ]><html class="no-js ie ie8" lang="en"><![endif]-->
<!--[if IE 9 ]><html class="no-js ie ie9" lang="en"><![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html class="no-js not-ie" lang="en"><!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <link rel="dns-prefetch" href="//am.dalemckeown.co.uk">
    
    <?php echo "
    <link rel=\"shortcut icon\" href=\"" . $currentPrototypeLink . "/application/third_party/images/favicon.ico\">
    <link rel=\"apple-touch-icon\" href=\"" . $currentPrototypeLink . "/application/third_party/images/appleIcon.png\"> 
    <link rel=\"stylesheet\" href=\"http://c94471.r71.cf3.rackcdn.com/cwd.css\">
    <link rel=\"stylesheet\" href=\"" . $currentPrototypeLink . "/application/third_party/css/override_Styles.css\">";
    ?>
    <title>AM - Attendance Monitor Sign In</title>
    <meta name="description" content="Attendance Monitor is an online tool designed to help students track their attendance performance">
    <meta name="author" content="Dale Mckeown, www.dalemckeown.co.uk">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0">
    <script src="http://c94471.r71.cf3.rackcdn.com/modernizr.js"></script>
</head>

<body>
    <header id="cwd_header" role="banner">
        <section class="cwd_container">
            <hgroup class="grid_12" id="cwd_hgroup">
                <h1>Sign In</h1>
                <a href="http://www.am.dalemckeown.co.uk/"><h3 class="white">AM Home</h3></a> 	 			
            </hgroup>
        </section>	
    </header>
    
    <nav class="cwd_container" role="navigation">
        <ul id="cwd_navigation" class="grid_12" role="navigation">
            <li><a href="/">Home</a></li>
            <li class="dropdown"><a href="#">AM Prototypes</a>
                <ul>
                    <?php
                    // Output a list of all prototypes
                    foreach ($prototypeArray as $prototype){
                        echo "<li><a href=\"http://www.am.dalemckeown.co.uk/p" . $prototype . "/\">Prototype $prototype</a></li>";
                    }
                    ?> 
                </ul>
            </li>
            <?php
			if(!empty($_SESSION['User_Name'])){
				echo "<li><a href=\"#\">Welcome, " . $_SESSION['User_Name'] . "</a></li>";
				echo "<li><a href=\"https://sso.lincoln.ac.uk/sign_out?redirect_uri=http://www.am.dalemckeown.co.uk/?signOut=true\">Sign Out</li></a>";
			}
			?>
        </ul>  
    </nav>
    
    <section class="cwd_container" id="cwd_content" role="main">
        <div class="grid_12">
            <h1>Attendance Monitor</h1>
            <?php 
			// Start outputting to screen based on session variables and events
			if((!empty($error)) || (!empty($error_message))) {
				// OAuth Error
				echo "<h2>UoL OAuth Error</h2>
				<p>There was an error authenticating the use of this application. The following error was returned by the UoL OAuth server:" .
				"<br>$error : $error_message" .
				"</p>";
				// Log the error and error message, along with IP address, Application details etc.
				$errorData = array(
					'Error_Type' => "OAuth Request",
					'Error_Code' => $error,
					'Error_Message' => $error_message
				);
				reportError($db, $errorData);
				resetSession(); //reset the session to aviod loggin in a user if a login error occurs.
			}
			else if(!empty($code)){			
				if($state == $_SESSION['Application_State']){
					if((!isset($_SESSION['Access_Token'])) || (empty($_SESSION['Access_Token']))){
						// If the states match and no access token is set, build a $_POST request and sent it off to get an access token
						//Authenticate user
						$params = array(
							'grant_type' => 'authorization_code',
							'client_id' => $Application_ID,
							'client_secret' => $Application_Secret,
							'redirect_uri' => 'http://www.am.dalemckeown.co.uk/signIn.php',
							'code' => $code
						);
						$data = array(
							'requestType' => 'OAuth',
							'endPoint' => '/oauth/access_token/',
							'params' => $params
						);								
						$result = getNucleusData($db, $data);
						$_SESSION['Access_Token'] = $result->{'access_token'};
						$accessTokenJustSet = true; // Var to detect if user has just logged on
					}
					// Data request from nucleus
					$params = array('access_token' => $_SESSION['Access_Token']);
					
					$data = array(
						'requestType' => 'Nucleus_Data',
						'endPoint' => '/v1/people/user?',
						'params' => $params
					);	
					$result = getNucleusData($db, $data);
					if($result['Success'] == true){ // If getNucleusData returned an error
						
						$json = $result['Data'];
						// Set some session data
						
						//Chop all all middle names out.
						if(substr_count($json['results'][0]['name'], " ") > 1){
							$f = strpos($json['results'][0]['name'], " ");
							$l = strrpos($json['results'][0]['name'], " ");
							$first = substr($json['results'][0]['name'], 0, $f);
							$sur = substr($json['results'][0]['name'], $l+1);						
						}					
						$_SESSION['User_ID'] = $json['results'][0]['id'];
						$_SESSION['User_Name'] = $first . " " . $sur;
						$_SESSION['User_Type'] = $json['results'][0]['type'];
						
						if($accessTokenJustSet == true){ // If the access token was just set in this script (prevents re-authenticating user if they return ti this script)
							// Search the mongodb "am_users" collection for this user id
							$collection = $db->am_users;
							$searchQuery = "function() {
								return this.User_ID == '" . $_SESSION['User_ID'] . "';
							}";
							$cursor = $collection->find(array('$where' => $searchQuery));

							$cursorArray = iterator_to_array($cursor, false); // 'use_keys' must be false
							$person = $cursorArray[0]; // Get the first instance
							if(empty($person)){
								// New user detected!!!!111
								$_SESSION['New_User'] = true; // Set session variable so the application can pick up a new user.
								
								// Add to database tables
								$insertQuery = array(
								'User_ID' => $_SESSION['User_ID'],
								'Access_Token' => $_SESSION['Access_Token'],
								'Last_Sign-in' => $empty,
								'Last_Sign-out' => $empty
								);
								$collection->insert($insertQuery, array("safe" => true));
							}
							// Insert new login event into am_events_monitor
							$collection = $db->am_event_monitor; // Change to relevant collection
							// Insert into am_events_monitor, returning a mongo_id
							$insertQuery = array(
								'User_ID' => $_SESSION['User_ID'],
								'User_IP' => getIP(),
								'Event_Timestamp' => time(),
								'Event_Type' => "Sign-in",
								'Event_Message' => "User signed in at this time"
							);
							$collection->insert($insertQuery, array("safe" => true));
							$mongo_id = $insertQuery['_id'];

							// Update  am_users 'Last_Sign-in' and 'Access_Token' with new mongo_id and access token
							$collection = $db->am_users; // Change to relevant collection
							$collection->update(array("User_ID" => $_SESSION['User_ID']), array('$set' => array("Access_Token" => $_SESSION['Access_Token'], "Last_Sign-in" => $mongo_id)), array("safe" => true));
							
						}// End if($accessTokenJustSet == true){
						// Redirect the user to the prototype if specified, or home if empty
						header("Location: http://www.am.dalemckeown.co.uk/" . $_SESSION['Prototype_ID']);
					}// End if($result['Success'] == true){
					else{
						echo "<h2>" . $result['Error_Type'] . "</h2>";
						echo "<h4>Error Code: " . $result['Error_Code'] . "</h4>";
						echo "<h4>Error Message: " . $result['Error_Message'] . "</h4>";
						echo "<img src =\"" . $result['Error_Image'] . ".jpg\">";
					}
				}// End if($state == $_SESSION['Application_State']){
				else{
					echo "Security code did not match. Please try again.";
				}
			}// End else if(!empty($code)){	
			else{
				if(empty($_SESSION['Access_Token'])){
					if(empty($_SESSION['Application_State'])){
						$_SESSION['Application_State'] = randomID(10);
					}
					header("Location: https://sso.lincoln.ac.uk/oauth?response_type=code&client_id=" . $Application_ID . "&redirect_uri=http://www.am.dalemckeown.co.uk/signIn.php&scope=" . $Application_Scope . "&state=" . $_SESSION['Application_State']);
				}
				else{
					header("Location: http://www.am.dalemckeown.co.uk/" . $_SESSION['Prototype_ID']);
				}
			}
			?>
		</div>
    </section>
    <footer class="cwd_container" id="cwd_footer" role="contentinfo">
        <section class="push_6 grid_6 last">
            <p class="align-right">
                <small>
                    Content &copy; <a href="http://www.dalemckeown.co.uk" target="_blank">Dale Mckeown</a><br>
                    Design &copy; <a href="http://www.lincoln.ac.uk" target="_blank">University of Lincoln</a>
                </small>
            </p>
        </section>		
    </footer>
    
    <!-- Put all JavaScript code below this line -->
    <script src="http://c94471.r71.cf3.rackcdn.com/jquery.js" type="text/javascript"></script>
    <!--[if (lt IE 9) & (!IEMobile)]>
        <script src="http://c94471.r71.cf3.rackcdn.com/selectivizr-1.0.1.js"></script>
    <![endif]-->
    <script src="http://c94471.r71.cf3.rackcdn.com/cwd.js" type="text/javascript"></script>
	
</body>
</html>
<?php ob_end_flush(); ?>