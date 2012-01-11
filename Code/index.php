<?php
session_start();
header("Cache-Control: no-cache"); // Ensures IE does not cache the page

/****************************************/
/*      Copyright (c) Dale Mckeown		*/
/*       Last edited: 30/12/2011        */
/*        www.dalemckeown.co.uk  		*/
/****************************************/

// Include a commons file. (common variable definitions and functions)
include_once $_SERVER['DOCUMENT_ROOT']."/_common.php";

/*Mongo connection*/
$db = mongoConnect();

// If the $_GET array is not empty, sign the user out.
if(!empty($_GET)){
	// Insert new sign-out event to am_event_monitor
	$collection = $db->am_event_monitor; // Change to relevant collection
	// Insert into am_events_monitor, returning a mongo_id
	$insertQuery = array(
		'User_ID' => $_SESSION['User_ID'],
		'User_IP' => getIP(),
		'Event_Timestamp' => time(),
		'Event_Type' => "Sign-out",
		'Event_Message' => "User signed out at this time"
	);
	$collection->insert($insertQuery, array("safe" => true));
	$mongo_id = $insertQuery['_id'];

	// Update  am_users 'Last_Sign-out' with new mongo_id 
	$collection = $db->am_users; // Change to relevant collection
	$collection->update(array("User_ID" => $_SESSION['User_ID']), array('$set' => array("Last_Sign-out" => $mongo_id)), array("safe" => true));
	
	resetSession(); //reset the session to aviod loggin in a user if a login error occurs.
}

// Get prototypes, set array data as descriptions
$prototypes = getPrototypes();
$prototypeArray = $prototypes['prototypeArray'];
$currentPrototypeLink = $prototypes['currentPrototypeLink'];

$prototypeDescriptionsArray = array();
//Set a descriptions array that will be looped through for each prototype's description.
$prototypeDescriptionsArray[] = "<p>The first major prototype. It is concerned with the following tasks:
	<ul>
		<li>Setting up CodeIgniter and transferring all previous work to this framework.</li>
		<li>Structuring and styling each page following the <a href=\"http://cwd.online.lincoln.ac.uk\">Common Web Design</a>.</li>
		<li>Connecting to <a href=\"http://data.lincoln.ac.uk\">data.lincoln.ac.uk</a> using the OAuth protocol to log-in students and staff members.</li>
		<li>Setting up a remote server to host a  <a href=\"http://mongodb.org\">MongoDB</a> NoSQL database.</li>
	</ul>
	</p>";	
$prototypeDescriptionsArray[] = "<p>The second major prototype, focussing on development of the application, including:
	<ul>
		<li>Design & implementation of the <a href=\"http://mongodb.org\">MongoDB</a> database structure.</li>
		<li>Design & implementation of persona's data sets for use as test data within the application.</li>
	</ul>
	</p>";
$prototypeDescriptionsArray[] = "<p>The third major prototype, focussing on attendance data assessment, including:
	<ul>
		<li>Design & implementation of </li>
		<li>Design & implementation of </li>
	</ul>
	</p>";
	
$futurePrototypeArray = array();
$futurePrototypeArray[] = "<h3>Prototype 3 (offline)</h3>
	<p>This prototype is designed to connect AM users to the pseudo data sets created in prototype 2. The following tasks are neccessary:
	<ul>
		<li>Map AM Student users to individual data sets to create a pseudo attendance assessment based on raw data.</li>
		<li>Map AM Staff users to grouped data sets to create a pseudo attendance assessment based on raw data.</li>
	</ul>
	</p>";
	
$futurePrototypeArray[] = "<h3>Prototype 4 (offline)</h3>
<p>Prototype 4 will analyse intuitive ways to turn raw data into visual objects which may help users to interpret and understand the raw data, 
while making the process fun. TBC. 

</p>";
$futurePrototypeArray[] = "<h3>Prototype 5 (offline)</h3>
<p>Prototype 5 will involve two aspects. The first is social media connectivity (facebook, twitter, etc). The second aspects will involve a
 clean-up of the application, testing and bug fixing.

</p>";
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
    
    <title>AM - Attendance Monitor Home</title>
    <meta name="description" content="Attendance Monitor is an online tool designed to help students track their attendance performance">
    <meta name="author" content="Dale Mckeown, www.dalemckeown.co.uk">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0">
    <script src="http://c94471.r71.cf3.rackcdn.com/modernizr.js"></script>
</head>

<body>
    <header id="cwd_header" role="banner">
        <section class="cwd_container">
            <hgroup class="grid_12" id="cwd_hgroup">
                <a href="<?php echo $base_url; ?>"><h1>Information</h1></a>
                <a href="<?php echo $am_url; ?>"><h3 class="white">AM Home</h3></a> 			
            </hgroup>
        </section>	
    </header>
    
    <nav class="cwd_container" role="navigation">
        <ul id="cwd_navigation" class="grid_12" role="navigation">
            <li ><a href="/">Home</a></li>
            <li class="dropdown"><a href="#">AM Prototypes</a>
                <ul>
                    <?php
                    //Output a list of all prototypes
                    foreach ($prototypeArray as $prototype){
                        echo "<li><a href=\"http://www.am.dalemckeown.co.uk/p" . $prototype . "/\">Prototype $prototype</a></li>";
                    }
                    ?> 
                </ul>
            </li>
            <?php 
			if(empty($_SESSION['Access_Token'])){
				
				$_SESSION['Application_State'] = randomID(10);
				
				echo "<li><a href=\"https://sso.lincoln.ac.uk/oauth?response_type=code&client_id=" . $Application_ID . "&redirect_uri=http://www.am.dalemckeown.co.uk/signIn.php&scope=" . $Application_Scope . "&state=" . $_SESSION['Application_State'] . "/" . $Prototype_ID . "\">Sign In</a></li>";
			}
			else if(!empty($_SESSION['User_ID'])){
				echo "<li><a href=\"#\">Welcome, " . $_SESSION['User_Name'] . "</a></li>";
				echo "<li><a href=\"https://sso.lincoln.ac.uk/sign_out?redirect_uri=http://www.am.dalemckeown.co.uk/?signOut=true\">Sign Out</a></li>";
			}
			?>
        </ul>  
    </nav>
    
    <section class="cwd_container" id="cwd_content" role="main">
        <div class="grid_8">
            <h1>Attendance Monitor</h1>
            <h2>About</h2>
            <p>Welcome to Attendance Monitor(AM). The dissertation project of a third year student at the 
            <a href="http://lincoln.ac.uk">University of Lincoln (UoL)</a>, AM is intended to ba a proof-of-concept application, allowing 
            UoL students to view their own university attendance profiles. Because this is a proof-of-concept, no real student attendance
            data is allowed to be used. For development purposes, several 'pseudo attendance profiles' will be used to demonstrate the feasability 
            behind this application.</p>
		</div>
         <div class="grid_4">
            <div class="box bg_dark">
                <h3>Questions? Comments? Ideas?</h3>
                <p>Drop me a line by email and I will get back to you asap.</p>
                <p>Email: <a href="mailto:08110296@students.lincoln.ac.uk">08110296@students.lincoln.ac.uk</a></p>
            </div>
        </div>
        <div class="grid_12 margin_top border_top padding_top">
            <h2 id="Prototypes">AM Prototypes</h2>
            <p>Below is a description of each active prototype, describing the tasks for each.</p>
            <?php
            foreach ($prototypeArray as $prototype){
                echo "<h3><a href=\"http://www.am.dalemckeown.co.uk/p" . $prototype . "/\">Prototype $prototype</a></h3>";
                echo $prototypeDescriptionsArray[$prototype -1];
            }
			foreach ($futurePrototypeArray as $prototype){
				echo $prototype;
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