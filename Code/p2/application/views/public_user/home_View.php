<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	session_start();
	$this->load->view('public_user/includes/include_Header', $this->data);
	$start_time = microtime();
	//Set up some functions - should be put in seperate file in future versions
	function randomID($length){
		$random= "";
		srand(microtime()*1000000);
		$data = "AbcDE123IJKLMN67QRSTUVWXYZaBCdefghijklmn123opq45rs67tuv89wxyz0FGH45OP89";
	
		for($i = 0; $i < $length; $i++){
			$random .= substr($data, (rand()  % (strlen($data))), 1);
		}
		return $random;
	}
	function getPrototypeID(){
		$thisDir = getcwd();
		$lastInstance = strrpos($thisDir, "/") + 1;
		// Slice the directory name from the lastInstance and a length.
		$Prototype_ID = substr($thisDir, $lastInstance, 2);
		return $Prototype_ID;
	}
	$prototype_id = getPrototypeID();
	
	function reportError($db, $errorData){
		// Log the error and error message, along with IP address, time, page etc. 
		$collection = $db->am_error_monitor; // Change to relevant collection
		$insertQuery = array(
			'User_ID' => $_SESSION['User_ID'],
			'User_IP' => getIP(),
			'Script' => $_SERVER['PHP_SELF'],
			'Error_Timestamp' => time(),
			'Error_Type' => $errorData['Error_Type'],
			'Error_Code' => $errorData['Error_Code'],
			'Error_Message' => $errorData['Error_Message']
		);
		$collection->insert($insertQuery, array("safe" => true));
	}
	function resetSession(){
		$Application_State = $_SESSION['Application_State'];
		$_SESSION = array(); // Redefine session array, clearing it.
		session_destroy(); // Destroy old session.
		session_start(); // Start new session.
		$_SESSION['Application_State'] = $Application_State;
	}
	function getIP(){ 
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$TheIp=$_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		else{
			$TheIp=$_SERVER['REMOTE_ADDR'];
		}
		return trim($TheIp);
	}
	function getNucleusData($db, $data){
		$ssOAuth = "https://sso.lincoln.ac.uk" . $data['endPoint'];
		$nucleus = "https://nucleus.lincoln.ac.uk" . $data['endPoint'];
		$nucleusProxy = "https://nucleus-proxy.online.lincoln.ac.uk" . $data['endPoint'];
		$postdata = http_build_query($data['params']);
		
		if($data['requestType'] == "OAuth"){
			$opts = array('http' =>
				array(
					'method'  => 'POST',
					'header'  => 'Content-type: application/x-www-form-urlencoded\r\nContent-Length: ' . strlen($postdata),
					'content' => $postdata
				)
			);
			$context = stream_context_create($opts);				
			$result = file_get_contents($ssOAuth, false, $context);
			$return = json_decode($result);
			return $return;
		}
		else{
			//$_GET type
			$opts = array('http' =>
				array(
					'method' => 'GET',
					'header' => 'Content-Type: text/html; charset=utf-8'
				)
			);
			$context = stream_context_create($opts);			
			$result = file_get_contents($nucleusProxy . $postdata, false, $context);
			$json = json_decode($result, true); // Parse the returned data
			
			if(empty($json)){
				$return = array(
					'Success'  => false,
					'Error_Type'  => "Nucleus Request Error",
					'Error_Code'  => "404",
					'Error_Message' => "Nucleus API is unavailable",
					'Error_Image' => "http://httpcats.herokuapp.com/404",
				);
			}
			else if($json['error'] == true){
				$return = array(
					'Success'  => false,
					'Error_Type'  => "Nucleus Request Error",
					'Error_Code'  => $json['status_code'],
					'Error_Message' => $json['message'],
					'Error_Image' => $json['status_cat'],
					'Data' => $json
				);
			}
			else{
				$return = array(
					'Success'  => true,
					'Data' => $json
				);
			}
			if($return['Success'] == false){
				// Log the error and error message, along with IP address, Application details etc.
				$errorData = array(
					'Error_Type' => $return['Error_Type'],
					'Error_Code' => $return['Error_Code'],
					'Error_Message' => $return['Error_Message']
				);
				reportError($db, $errorData);
				resetSession();
			}
		return $return;
		}
	}
	function getAcademicDates(){
		
		//set the teaching weeks
		$teaching_weeks = array(
			'1','2','3','4','5','6','7','8','9','10','11','16','17',
			'18','19','20','21','22','23','24','25','26','31','32'
		);
		
		
		//calculate the start of the academic year
		$this_month = date('m');
		$this_year = date('y');
		
		if($this_month >= 9){
			$academic_year = date("Y");
			$year_code = $this_year . ($this_year+1);
		}
		else{
			$academic_year = ($this_year-1);
			$year_code = ($this_year-1) . $this_year;
		}
		
		//get the day for september 1st of the academic year
		$september = mktime(0, 0, 0, date(9), 1, $academic_year);
		//add two weeks
		$twoweeks = strtotime('+2 weeks' ,$september);
		$adddays = 0;
		$dayofweek = date('N', $twoweeks);
		//If the day is not a monday, work out how many days to add to get to monday (back to one)
		if($dayofweek != 1){
			$adddays = 8 - $dayofweek;
		}
		//generate a unix timestamp for the start of that day.
		$first_week = strtotime('+' . $adddays . ' days' ,$twoweeks);
		//add some weeks to populate the year 
		$last_week = strtotime('+32 weeks' ,$first_week);
		return array(
			'year_code' => $year_code,
			'academic_year' => $academic_year,
			'first_week_start' => $first_week,
			'last_week_start' => $last_week,
			'teaching_weeks' => $teaching_weeks 
		);
	}
	
	if(!empty($_SESSION['Prototype_ID'])){
		unset($_SESSION['Prototype_ID']);
	}
?>	
	<title>AM - Attendance Monitor (Prototype 2)</title>
    <meta name="description" content="Attendance Monitor is an online tool designed to help students track their attendance performance.">
    <meta name="author" content="Dale Mckeown, www.dalemckeown.co.uk">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0">
    <script src="http://c94471.r71.cf3.rackcdn.com/modernizr.js"></script>
</head>
	
<body>
    <header id="cwd_header" role="banner">
        <section class="cwd_container">
            <hgroup class="grid_12" id="cwd_hgroup">
                <a href="<?php echo $base_url; ?>"><h1>Prototype 2</h1></a>
                <a href="<?php echo $am_url; ?>"><h3 class="white">AM Home</h3></a>		
            </hgroup>
        </section>
    </header>
	
    <nav class="cwd_container" role="navigation">
        <ul id="cwd_navigation" class="grid_12">  
            <li class="current"><a href="<?php echo $base_url; ?>">Home</a></li>
            <?php 
			if(empty($_SESSION['Access_Token'])){
				
				$_SESSION['Application_State'] = randomID(10);
				
				echo "<li><a href=\"https://sso.lincoln.ac.uk/oauth?response_type=code&client_id=" . $application_id . "&redirect_uri=http://www.am.dalemckeown.co.uk/signIn.php&scope=" . $application_scope . "&state=" . $_SESSION['Application_State'] . "/" . $prototype_id . "\">Sign In</a></li>";
			}
			else{
				echo "<li><a href=\"#\">Welcome, " . $_SESSION['User_Name'] . "</a></li>";
				echo "<li><a href=\"https://sso.lincoln.ac.uk/sign_out?redirect_uri=http://www.am.dalemckeown.co.uk/?signOut=true\">Sign Out</a></li>";
			}
			?>
        </ul>  
    </nav>
    
    <section class="cwd_container" id="cwd_content" role="main">
    <div class="grid_12">	
    <h1>Attendance Monitor</h1>
    <?php
	if(empty($_SESSION['Access_Token'])){
		echo "You must sign in to see the contents of this page....";
	}
	else{ 
		echo "<h2>Prototype Description</h2>
			
			<p>The second major prototype, focussing on development of the application, including:
			<ul>
				<li>Design & implementation of the <a href=\"http://mongodb.org\">MongoDB</a> database structure.</li>
				<li>Design & implementation of persona's data sets for use as test data within the application.</li>
			</ul>
			</p>";
			
		echo "<h2>Attendance Data</h2>";
		if($_SESSION['User_Type'] == "student"){
			echo "Your personal student attendance data...";
			
			// Get data from Nucleus
			$params = array('access_token' => $_SESSION['Access_Token']);
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
				echo "<br>Units: <br>";
				asort($person_units);
				foreach ($person_units as $unit){
					echo "ID: " . $unit['id'] . " Title: " . $unit['title'];
					echo "<br>";
				}
				
				// Get data from Nucleus
				/*$params = array(
					'access_token' => $_SESSION['Access_Token']
				);
				$data = array(
					'requestType' => 'Nucleus_Data',
					'endPoint' => '/v1/events/agenda/',
					'params' => $params
				);	
				$result2 = getNucleusData($mongo_db, $data);
				if($result2['Success'] == true){ // If getNucleusData does not return an error
					$json = $result2['Data'];
					var_dump($json);
				}
				else{		
					echo "<h2>" . $result2['Error_Type'] . "</h2>";
					echo "<h4>Error Code: " . $result2['Error_Code'] . "</h4>";
					echo "<h4>Error Message: " . $result2['Error_Message'] . "</h4>";
					echo "<img src =\"" . $result2['Error_Image'] . ".jpg\">";
				}*/
				
				//generate some data to implement nucleus result (looseley)
				$timeStart = array();
				$timeEnd = array();
				
				//software development
				$timeStart[] = 1327489200;
				$timeEnd[] = 1327496400;
				//professional practice
				$timeStart[] = 0;
				$timeEnd[] = 0;			
				//project
				$timeStart[] = 0;
				$timeEnd[] = 0;
				//project preparation
				$timeStart[] = 0;
				$timeEnd[] = 0;
				//computer vision and robotics
				$timeStart[] = 1327413600;
				$timeEnd[] = 1327417200;
				//create new array with all data
				$lectureArray = array();
				$count = 0;
				foreach($person_units  as $unit){
					if($timeStart[$count] != 0){
						$day = date('d', $timeStart[$count]);
						$start_hour = date('h', $timeStart[$count]);
						$start_minutes = date('i', $timeStart[$count]);
						$start_time = $start_hour . ":" . $start_minutes;
						$end_hour = date('h', $timeEnd[$count]);
						$end_minutes = date('i', $timeEnd[$count]);
						$end_time = $end_hour . ":" . $end_minutes;
						$lectureArray[] = array(
							'id' => $unit['id'],
							'title' => $unit['title'],
							'day' => $day,
							'start_time' => $start_time,
							'end_time' => $end_time
						);
					}
					$count++;
				}
				
				//generate year code, academic year start, first and last week timestamps.
				$dateDetails = getAcademicDates();
				
				$first_week_start = $dateDetails['first_week_start'];
				$last_week_start = $dateDetails['last_week_start'];
				$teaching_weeks = $dateDetails['teaching_weeks'];
				
				$iterate_week = $first_week_start;
				$week_seconds = ((60 * 60) * 24) * 7; //full week
				$week_end_seconds = (((60 * 60) * 24) * 7) - 1; //7 full week - 1 second
				$week_id = 0;
				
				$weekArray = array();
				while($iterate_week <= $last_week_start){
					
					//perform some vital calculations.
					//calculate the end of the week unix timestamp
					$week_end = $iterate_week + $week_end_seconds;
					//if the current week is in the teaching weeks array
					if(in_array($week_id, $teaching_weeks)){
						$teaching_week = 1;
					}
					else{
						$teaching_week = 0;
					}
					//add a new array to the week array using relevnt data
					$weekArray[] = array(
						'week_id' => $week_id,
						'teaching_week' => $teaching_week,
						'week_start' => $iterate_week,
						'week_end' => $week_end
					);
					//increment conditions
					$week_id++;
					$iterate_week = $iterate_week + $week_seconds;
				}
				
				$current_time = time();
				echo "<h2>Weekly Lecture Data</h2>";
				foreach($weekArray as $week){
					if($current_time >= $week['week_start']){
						if($week['teaching_week'] == 1){
							echo "<h3>Week " . $week['week_id'] . "</h3>";
							foreach($lectureArray as $lecture){
								var_dump($lecture);
								echo "<br>";
							}
							var_dump($week);
							echo "<br><br>";
						}
						else{
							echo "<h3>Week " . $week['week_id'] . "</h3> This week was not a teaching week. Lucky you!<br>";
						}
					}
					else{
						echo "Week " . $week['week_id'] . " has not yet started!<br>";
					}
				}
				
			}// End if($result['Success'] == true){
			else{		
				echo "<h2>" . $result['Error_Type'] . "</h2>";
				echo "<h4>Error Code: " . $result['Error_Code'] . "</h4>";
				echo "<h4>Error Message: " . $result['Error_Message'] . "</h4>";
				echo "<img src =\"" . $result['Error_Image'] . ".jpg\">";
			}
		}// End if($_SESSION['User_Type'] == "student"){
			
		/*else if($_SESSION['User_Type'] == "staff"){
			echo $person_title = $json['results'][0]['title'];
			echo $person_department = $json['results'][0]['department'];
			echo $person_job = $json['results'][0]['job'];
			echo $person_jobtitle = $json['results'][0]['jobtitle'];
		}*/
		else{
			echo "Other Set";
		}
	} ?>
    </div>
    </section>
    
	<?php 
 
	$end_time = microtime();
	echo "Generated in: " . ($end_time  - $start_time) . " seconds.";
 
	$this->load->view('public_user/includes/include_Footer'); ?>

<!-- Put all JavaScript code below this line -->
<script src="http://c94471.r71.cf3.rackcdn.com/jquery.js" type="text/javascript"></script>
<!--[if (lt IE 9) & (!IEMobile)]>
<script src="http://c94471.r71.cf3.rackcdn.com/selectivizr-1.0.1.js"></script>
<![endif]-->
<script src="http://c94471.r71.cf3.rackcdn.com/cwd.js" type="text/javascript"></script>
</body>
</html>