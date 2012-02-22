<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	session_start();
	$this->load->view('public_user/includes/include_Header', $this->data);
	$this->load->view('public_user/includes/include_Functions', $this->data);
	
	if(!empty($_GET['dataType'])){
		$dataType = strtolower(strip_tags(substr($_GET['dataType'],0,20)));
	}
	else{
		$dataType = '';
	}

	//little function to allow others to see data.
	if(!empty($_GET['override'])){
		$collection = $mongo_db->am_users;
		$searchQuery = "function() {
			return this.User_ID == '08110296';
		}";
		$cursor = $collection->find(array('$where' => $searchQuery));
		$cursorArray = iterator_to_array($cursor, false); // 'use_keys' must be false
		$person = $cursorArray[0]; // Get the first instance
				
		$Person_ID = $person['User_ID'];
		$Person_Name = "Dale Mckeown";
		$Person_Access_Token = $person['Access_Token'];
		$Person_User_Type = "student";
	}
	if(!empty($_SESSION['User_ID'])){
		$Person_ID = $_SESSION['User_ID'];
		$Person_Name = $_SESSION['User_Name'];
		$Person_Access_Token = $_SESSION['Access_Token'];
		$Person_User_Type = $_SESSION['User_Type'];
	}
		
	//get the prototype id
	$prototype_id = getPrototypeID();
	
	if(!empty($_SESSION['Prototype_ID'])){
		unset($_SESSION['Prototype_ID']);
	}
?>	
	<title>AM - Attendance Monitor (Prototype 3)</title>
    <meta name="description" content="Attendance Monitor is an online tool designed to help students track their attendance performance.">
    <meta name="author" content="Dale Mckeown, www.dalemckeown.co.uk">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0">
    <script src="http://c94471.r71.cf3.rackcdn.com/modernizr.js"></script>
</head>
	
<body>
    <header id="cwd_header" role="banner">
        <section class="cwd_container">
            <hgroup class="grid_12" id="cwd_hgroup">
                <a href="<?php echo $base_url; ?>"><h1>Prototype 3</h1></a>
                <a href="<?php echo $am_url; ?>"><h3 class="white">AM Home</h3></a>		
            </hgroup>
        </section>
    </header>
	
    <nav class="cwd_container" role="navigation">
        <ul id="cwd_navigation" class="grid_12">  
            <li class="current"><a href="<?php echo $base_url; ?>">Home</a></li>
            <?php 
			if(empty($Person_Access_Token)){
				
				$_SESSION['Application_State'] = randomID(10);
				$sign_in = "<a href=\"https://sso.lincoln.ac.uk/oauth?response_type=code&client_id=" . $application_id . "&redirect_uri=http://www.am.dalemckeown.co.uk/signIn.php&scope=" . $application_scope . "&state=" . $_SESSION['Application_State'] . "/" . $prototype_id . "\">Sign In</a>";
				echo "<li>$sign_in</li>";
			}
			else{
				echo "<li><a href=\"#\">Welcome, " . $Person_Name . "</a></li>";
				echo "<li><a href=\"https://sso.lincoln.ac.uk/sign_out?redirect_uri=http://www.am.dalemckeown.co.uk/?signOut=true\">Sign Out</a></li>";
			}
			?>
        </ul>  
    </nav>
    
    <section class="cwd_container" id="cwd_content" role="main">	
    <div class="grid_12"><h1>Attendance Monitor</h1></div>
    <?php
	if(empty($Person_Access_Token)){
		echo "<div class=\"grid_12\"><div class=\"notice\">You must $sign_in to see the contents of this page....</div></div>";
	}
	else{
		echo "<div class=\"grid_12\">" .
		"<div class=\"notice\">" .
		"<p>Prototype 3 will analyse intuitive ways to turn raw data into visual objects which may help users to interpret and understand the raw data, 
while making the process fun. The jquery library <a href=\"http://www.jqplot.com\">jqPlot</a> will be used to represent the student attendance data.
</p>" .
		"</div>" . 
		"</div>";
		
		if($Person_User_Type == "student"){
			echo "<div class=\"grid_12\"><h2>General Information</h2></div>";
			// Get data from Nucleus
			$params = array('access_token' => $Person_Access_Token);
			$data = array(
				'requestType' => 'Nucleus_Data',
				'endPoint' => '/v1/people/user?',
				'params' => $params
			);	
			$result = getNucleusData($mongo_db, $data);
			if($result['Success'] == true){ // If getNucleusData does not return an error
				$json = $result['Data'];
				//$person_faculty = $json['results'][0]['groups']['faculty'];
				$level = $json['results'][0]['groups'][13];
				$needle = strpos($level, "=") +1;
				$person_course_level = substr($level, $needle);
				$person_course_id = $json['results'][0]['course']['id'];
				$person_course_title = $json['results'][0]['course']['title'];
				$person_units = $json['results'][0]['units']; //further array of arrays
				echo "<div class=\"grid_4 dataBox\">";
				echo "<h3>Course Information</h3>";
				echo "<p>Course ID: " . $person_course_id . "<br>" .
				"Course Title: " . $person_course_title . "<br>" .
				"Course level: " . $person_course_level . "</p>";
				echo "</div>";
				
				echo "<div class=\"grid_8 last dataBox\">";
				echo "<h3>Module Information</h3>";
				asort($person_units);
				echo "<p>";
				$moduleArray = array(); //array of modules, used to retrieve lecture data
				foreach ($person_units as $unit){
					echo "<b>" . $unit['id'] . "</b> - " . $unit['title'] . "<br>";
					$moduleArray[] = array(
					'Module_ID' => $unit['id'],
					'Module_Title' => $unit['title']
					);
				}
				echo "</p>";
				echo "</div>";
				// Get event data from nucleus
				$params = array(
					'access_token' => $Person_Access_Token
				);
				$data = array(
					'requestType' => 'Nucleus_Data',
					'endPoint' => '/v1/events/agenda?',
					'params' => $params
				);
				$result2 = getNucleusData($mongo_db, $data);
				if($result2['Success'] == true){ // If getNucleusData does not return an error
					$json = $result2['Data'];
					$event_results = $json['results'];
							
					//generate all the session and attendance data....
					$result = generateModuleAndAttendance($mongo_db, $Person_ID, $person_units, $event_results);
					
					$weekArray = $result['weekArray'];
					$month_dates = $result['month_dates'];
					
					if($dataType == "bymodule"){
						//Module analysis
						attendanceByModule($mongo_db, $Person_ID, $moduleArray);
					}
					else if($dataType == "bymonth"){
						//Month by month analysis
						attendanceByMonth($mongo_db, $Person_ID, $moduleArray, $month_dates);
					}
					else{
						//Week by week analysys
						attendanceByWeek($mongo_db, $Person_ID, $moduleArray, $weekArray);
					}			
				}
				else{		
					echo "<h2>" . $result2['Error_Type'] . "</h2>";
					echo "<h4>Error Code: " . $result2['Error_Code'] . "</h4>";
					echo "<h4>Error Message: " . $result2['Error_Message'] . "</h4>";
					echo "<img src =\"" . $result2['Error_Image'] . ".jpg\">";
				}
			}// End if($result['Success'] == true){
			else{		
				echo "<h2>" . $result['Error_Type'] . "</h2>";
				echo "<h4>Error Code: " . $result['Error_Code'] . "</h4>";
				echo "<h4>Error Message: " . $result['Error_Message'] . "</h4>";
				echo "<img src =\"" . $result['Error_Image'] . ".jpg\">";
			}
		}// End if($Person_User_Type == "student"){
			
		/*else if($Person_User_Type == "staff"){
			echo $person_title = $json['results'][0]['title'];
			echo $person_department = $json['results'][0]['department'];
			echo $person_job = $json['results'][0]['job'];
			echo $person_jobtitle = $json['results'][0]['jobtitle'];
		}*/
		else{
			echo "Other Set";
		}
	} ?>
    </section>
    
	<?php $this->load->view('public_user/includes/include_Footer'); ?>

<!-- Put all JavaScript code below this line -->
<!--[if (lt IE 9) & (!IEMobile)]>
<script src="http://c94471.r71.cf3.rackcdn.com/selectivizr-1.0.1.js"></script>
<![endif]-->
<script src="http://c94471.r71.cf3.rackcdn.com/cwd.js" type="text/javascript"></script>
</body>
</html>