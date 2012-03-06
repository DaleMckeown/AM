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
		$override = true;
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
	else{
		$override = false;
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
		"<p>Prototype 3 builds on the raw data provided in prototype 2, and analyses and visualises the students personal attendance profile. The jQuery
		 library <a href=\"http://www.jqplot.com\">jqPlot</a> is used to generate graphs to better visualise this attendance data.
</p>" .
		"</div>" . 
		"</div>";
		
		if($Person_User_Type == "student"){
			echo "<div class=\"grid_12\"><h2>View your attendance data by.....</h2></div>";
			if($override == true){
				$linkString = "?override=true&dataType=";
			}
			else{
				$linkString = "?dataType=";
			}
			echo "<a href=\"" . $base_url . $linkString . "byWeek\"><div class=\"grid_4 customLinks\"><h3>Week</h3></div></a>";
			echo "<a href=\"" . $base_url . $linkString . "byMonth\"><div class=\"grid_4 customLinks\"><h3>Month</h3></div></a>";
			echo "<a href=\"" . $base_url . $linkString . "byModule\"><div class=\"grid_4 last customLinks\"><h3>Module</h3></div></a>";
			
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
				asort($person_units); //sort the array
				echo "<div class=\"grid_4 dataBox\">";
				echo "<h3>Course Information</h3>";
				echo "<p>Course ID: " . $person_course_id . "<br>" .
				"Course Title: " . $person_course_title . "<br>" .
				"Course level: " . $person_course_level . "</p>";
				echo "</div>";
				//Get future events for both next module info and to work out attendance data later on
				$params = array(
					'access_token' => $Person_Access_Token
				);
				$data = array(
					'requestType' => 'Nucleus_Data',
					'endPoint' => '/v1/events/agenda?',
					'params' => $params
				);
				$result2 = getNucleusData($mongo_db, $data);
				
				echo "<div class=\"grid_4 dataBox\">";
				echo "<h3>Module Information</h3><p>";
				$moduleArray = array(); //array of modules, used to retrieve lecture data
				//Output module info
				foreach ($person_units as $unit){
					echo "<b>" . $unit['id'] . "</b> - " . $unit['title'] . "<br>";
					//Add module data to moduleArray
					$moduleArray[] = array(
						'Module_ID' => $unit['id'],
						'Module_Title' => $unit['title']
					);
				}
				echo "</p></div>";
				
				//next module info.
				//first build two arrays, one to check and provide an index for, and the other to store data.
				$moduleCount = count($person_units); //var to check 
				$pDataChecker = array(); //array to hold data to check against
				$pData = array(); //array to store actual data
				foreach($result2['Data']['results'] as $res){
					if(!in_array($res['cmis_course']['id'], $pDataChecker)){
						$pDataChecker[] = $res['cmis_course']['id'];
						$pData[] = array(
							'Module_ID' => $res['cmis_course']['id'],
							'Event_Type' => $res['cmis_event_type'],
							'Event_Start' => $res['event_unixstart'],
							'Event_End' => $res['event_unixend'],
							'Event_Location' => key($res['event_location_raw'])
						);
						if(count($pData) == $moduleCount){ //if the array is the size of the module count, break from the loop to save computation.
							break;
						}
					}
				}
				echo "<div class=\"grid_4 last dataBox\">";
				echo "<h3>Upcoming Events</h3><p>";
				foreach ($person_units as $unit){
					echo "<b>" . $unit['id'] . "</b> - ";
					//Check for future data in our above pData array
					if(in_array($unit['id'], $pDataChecker)){
						$key = array_search($unit['id'], $pDataChecker);
						echo $pData[$key]['Event_Type'] . " in " . $pData[$key]['Event_Location'] . " (" . customDateShort($pData[$key]['Event_Start'], $pData[$key]['Event_End']) . ")<br>";
					}
					else{
						echo "<font class=\"Grey\">(no upcoming event data found)</font><br>";
					}

				}
				echo "</p></div>";
				
				// Get event data from nucleus
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
			echo "Eh? You don't appear to be a student or member of staff at the University of Lincoln.";
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