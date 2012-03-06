<?php
//Functions required for the application to work, varying levels of complication and use.
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
				'Error_Image' => "http://httpcats.herokuapp.com/404"
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
	
	//Calculations for months within academic year
	$monthArray = array();
	$month = date('F', $september);
	$month_end = strtotime('+1 month -1 second' ,$september);//add month, minus 1 second
	$monthArray[] = array(
			'Month' => $month,
			'Month_Start' => $september,
			'Month_End' => $month_end
		);
	$month_start = $september;
	for ($x = 0; $x <= 6; $x++){
		$month_start = strtotime('+1 month' ,$month_start);
		$month_end = strtotime('+1 month -1 second' ,$month_start);//add month, minus 1 second
		$month = date('F', $month_start);
		$monthArray[] = array(
			'Month' => $month,
			'Month_Start' => $month_start,
			'Month_End' => $month_end
		);
	}
	return array(
		'year_code' => $year_code,
		'academic_year' => $academic_year,
		'first_week_start' => $first_week,
		'last_week_start' => $last_week,
		'teaching_weeks' => $teaching_weeks,
		'month_dates' => $monthArray
	);
}
function generateAttendanceProfile(){
	$profiles = array("Awful", "Average", "Good", "Awesomes");
	if(!empty($_GET['studentProfile'])){
		$studentProfile = ucfirst(strip_tags(substr($_GET['studentProfile'],0,20)));
		if(in_array($studentProfile, $profiles)){
			$profile = array_search($studentProfile, $profiles);
		}
		else{
			$profile = rand(0,3);
		}
	}
	else{
		$profile = rand(0,3);
	}
	return $profile;
}
function generateAttendanceData($studentProfile){
	$attendanceProbability = (($studentProfile * 10) * 2) + rand(10,45);
	
	$prob_min = $attendanceProbability - rand(0,5);
	$prob_max = $attendanceProbability + rand(15,20);
	
	$randomNumber = rand($prob_min,100);

	//echo "range: " . $prob_min . " - " . $prob_max . " probability: " . $attendanceProbability . " random: " . $randomNumber . " ";
	if(($randomNumber >= $prob_min) && ($randomNumber <= $prob_max)){
		//echo "attended<br>";
		$attendance = 1;
	}
	else{
		//echo "absent<br>";
		$attendance = 0;
	}
	return $attendance;
}

function generateModuleAndAttendance($mongo_db, $Person_ID, $person_units, $event_results){
	
	$sessionArray = array();
	foreach($event_results as $event_result){
		//var_dump($event_result);
		//echo "<br>";
		if($event_result['event_type'] == "academic_timetable"){
			$timeStart = $event_result['event_unixstart'];
			$timeEnd = $event_result['event_unixend'];
			$day = date('N', $timeStart);
			$start_hour = date('H', $timeStart);
			$start_minutes = date('i', $timeStart);
			$start_time = $start_hour . ":" . $start_minutes;
			$end_hour = date('H', $timeEnd);
			$end_minutes = date('i', $timeEnd);
			$end_time = $end_hour . ":" . $end_minutes;
			
			$searchArray = array(
				'id' => $event_result['cmis_course']['id'],
				'title' => $event_result['event_title'],
				'location' => key($event_result['event_location_raw']),
				'event_type' => $event_result['cmis_event_type'],
				'day' => $day,
				'start_time' => $start_time,
				'end_time' => $end_time
			);
			if(!in_array($searchArray, $sessionArray)){
				$sessionArray[] = $searchArray;
			}
		}
	}
	
	//generate attendance profile
	$studentProfile = generateAttendanceProfile();
	
	if(!empty($_GET['overwrite']))
		$overwrite = true;
	else
		$overwrite = false;
		
	//generate year code, academic year start, first and last week timestamps.
	$dateDetails = getAcademicDates();
	
	$first_week_start = $dateDetails['first_week_start'];
	$last_week_start = $dateDetails['last_week_start'];
	$teaching_weeks = $dateDetails['teaching_weeks'];
	$month_dates = $dateDetails['month_dates'];
	
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
		//add a new array to the week array using relevant data
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

	$collection = $mongo_db->am_user_events; // Change to MongoDB relevant collection
	$current_time = time();
	foreach($weekArray as $week){		
		if($current_time >= $week['week_start']){
			if($week['teaching_week'] == 1){ //If week is a teaching week
				foreach($sessionArray as $session){
					
					//work out timestamps for each session (essential for DB query)
					$session_timestamp = $week['week_start'];
					for($dayCount = 1; $dayCount < $session['day']; $dayCount++){
						$session_timestamp = strtotime('+1 day' ,$session_timestamp);
					}
					//calculate timestamp for session start
					$session_start_needle = strpos($session['start_time'], ':');
					$session_start_hour = substr($session['start_time'],0,$session_start_needle);
					$session_start_minute = substr($session['start_time'], $session_start_needle+1,2);
					$session_start = strtotime("+" . $session_start_hour . " hours" , $session_timestamp);
					$session_start = strtotime("+" . $session_start_minute . " minutes" , $session_start);
					//calculate timestamp for session end
					$session_end_needle = strpos($session['end_time'], ':');
					$session_end_hour = substr($session['end_time'],0,$session_end_needle);
					$session_end_minute = substr($session['end_time'], $session_end_needle+1,2);
					$session_end = strtotime("+" . $session_end_hour . " hours" , $session_timestamp);
					$session_end = strtotime("+" . $session_end_minute . " minutes" , $session_end);
					
					//only insert data if the session has ended
					if($current_time >= $session_end){
					
						//replace with timestamps
						//***** Search for module in mongo collection, insert data as required *****/
						
						$searchQuery = "function() {
							return this.Module_ID == '" . $session['id'] . "' && this.Start_Time ==  '" . $session_start . "';
						}";
						$cursor = $collection->find(array('$where' => $searchQuery));
						$cursorArray = iterator_to_array($cursor, false); // 'use_keys' must be false.
						$result = $cursorArray; // Get the first instance
						
						$id = $Person_ID;
						//Generate whether the user attended this session
						$attended_bool = generateAttendanceData($studentProfile);
						$attendance = array($id => $attended_bool);
						
						//if no entry is found, make one and insert data
						if(empty($result)){				
							$insertQuery = array(
								'Module_ID' => $session['id'],
								'Module_Title' => $session['title'],
								'Event_Location' => $session['location'],
								'Event_Type' => $session['event_type'],
								'Start_Time' => $session_start,
								'End_Time' => $session_end,
								'Attended' => $attendance
							);
							//insert if not found
							$collection->insert($insertQuery, array("safe" => true));
						}								
						else{ //else retrieve array, see if user is in the array, and if they are not, insert them.
							$result = $cursorArray[0]; // Get the first instance
							//if the user_id does notexists as an array key
							if(!array_key_exists($id, $result['Attended'])){
								//define update query
								$updateQuery = array(
									"Module_ID" => $session['id'],
									"Start_Time" => $session_start
								);
								$insert = $result['Attended'] + $attendance;
								$collection->update($updateQuery, array('$set' => array('Attended' => $insert)), array("safe" => true));
							}
							else if($overwrite == true){
								//define update query
								$updateQuery = array(
									"Module_ID" => $session['id'],
									"Start_Time" => $session_start
								);
								$insert = $attendance + $result['Attended'];
								$collection->update($updateQuery, array('$set' => array('Attended' => $insert)), array("safe" => true));
							}
							//else array key already exists
						}//End else
					}//End if($current_time >= $session_end){
				}//End foreach($sessionArray as $session){
			}//End if($week['teaching_week'] == 1){ //If week is a teaching week
		}//End if($current_time >= $week['week_start']){
	}//End foreach($weekArray as $week){
	return array( 
		'weekArray' => $weekArray,
		'month_dates' => $month_dates
	); //return the week array vital to data analysis by week.
}
//Function to compare vars in multidimensional arrays
function compareSort($a, $b) {
	return $a['Start_Time']>$b['Start_Time'];
}
function customDateShort($start, $end){
	return date('d/n', $start) ." ". date('g', $start) . "-" . date('g', $end);
}
function customDateLong($start, $end, $type){
	return date('D jS', $start) ." ". date('ga', $start) . " - " . date('ga', $end);
}
function attendanceByWeek($mongo_db, $Person_ID, $moduleArray, $weekArray){
	/****** Start Week by Week Analysis ******/
	$collection = $mongo_db->am_user_events; // Change to MongoDB relevant collection
	$current_time = time();
	echo "<h2>Week by Week Attendance</h2>";
	//Variables to control overall figures
	$overallLectureCount = 0;
	$overallAttendedCount = 0;
	$overallAbsentCount = 0;
	$weekDataArray = array();
	$weekDivCounter = 0;
	
	foreach($weekArray as $week){
		//Variables to control weekly figures
		$weekLectureCount = 0;
		$weekAttendedCount = 0;
		$weekAbsentCount = 0;
		if($current_time >= $week['week_start']){
			//DIV creation
			if($weekDivCounter == 2){
				echo "<div class=\"grid_4 last dataBox\">";
				$weekDivCounter = 0;
			}
			else{
				echo "<div class=\"grid_4 dataBox\">";
				$weekDivCounter++;
			}
			
			if($week['teaching_week'] == 1){ //If week is a teaching week
				$startDay = date('j/n/y', $week['week_start']);
				$endDay = date('j/n/y', $week['week_end']);
				echo "<h3 title=\"" . $startDay  . " - " . $endDay . "\">Week " . $week['week_id'] . "</h3>";
				
				
				$sessionArray = array();
				//search for all lecture data for this week
				foreach($moduleArray as $module){
					//***** Pull data from database, increment counters, output result. *****/
					$cursor = $collection->find(array('Module_ID' => $module['Module_ID'], 'Start_Time' => array('$gte' => $week['week_start'], '$lte' => $week['week_end']), 'Attended.' .$Person_ID => array('$exists' => true)));
					$cursorArray = iterator_to_array($cursor, false); // 'use_keys' must be false.						

					if(!empty($cursorArray)){
						foreach($cursorArray as $lecture){
							$sessionArray[] = $lecture;
						}
					}
				}//End foreach($moduleArray as $module){

				//sort the array by attendance day
				usort($sessionArray, 'compareSort');
				
				//output each session and increment counters
				foreach ($sessionArray as $session){	
					if($session['Attended'][$Person_ID] == 1){
						echo "<div class=\"att attGreen\">" . $session['Module_ID'] . " " . customDateLong($session['Start_Time'], $session['End_Time'], $session['Event_Type']) . "</div>";
						$weekAttendedCount++;
					}
					else{
						echo "<div class=\"att attRed\">" . $session['Module_ID'] . " " . customDateLong($session['Start_Time'], $session['End_Time'], $session['Event_Type']) . "</div>";
						$weekAbsentCount++;
					}
					echo "</br>";
					$weekLectureCount++;
				}	
				echo "<p>" . $weekLectureCount . " sessions (<font class=\"Green\">" . $weekAttendedCount . "</font> - <font class=\"Red\">" . $weekAbsentCount . "</font>)</p>";	
				
				//graph stuff
				echo "<div class=\"dataGraph\" id=\"week_" . $week['week_id'] . "_graph\"></div>";
				?>
				<script language="javascript">
				plot = jQuery.jqplot('<?php echo "week_" . $week['week_id'] . "_graph"; ?>',				
					[[['Attended', <?php echo $weekAttendedCount; ?>],['Absent', <?php echo $weekAbsentCount; ?>]]],
					{
						title: {
							show: false
						},
						seriesDefaults: {
							shadow: true,
							renderer: $.jqplot.PieRenderer,
							rendererOptions: {
								highlightMouseOver: 1,
								sliceMargin: 3,
								padding: 10,
								shadowOffset: 2,
								startAngle: 30,
								showDataLabels: true
							}
						},
						grid: {
							background: '#FFFFFF', 
							borderColor: '#FFF',
							shadow: false
						},
						seriesColors: ["#71BC78", "#DC143C"],
						legend: {
							show: true
						}
					}
				);
				</script>
				<?php
				//add counters
				$overallLectureCount += $weekLectureCount;
				$overallAttendedCount += $weekAttendedCount;
				$overallAbsentCount += $weekAbsentCount;
				
				//Calculate percentage
				$attendancePercentage = ($weekAttendedCount / $weekLectureCount) * 100;
				$absentPercentage = 100 - $attendancePercentage;
				
				//final graph data array
				$weekDataArray[] = array(
					'week_id' => $week['week_id'],
					'attendance_percentage' => $attendancePercentage,
					'absent_percentage' => $absentPercentage
				);
			}// End if($week['teaching_week'] == 1){
			else{
				$startDay = date('j/n/y', $week['week_start']);
				$endDay = date('j/n/y', $week['week_end']);
				echo "<h3 title=\"" . $startDay  . " - " . $endDay . "\">Week " . $week['week_id'] . "</h3>";
				echo " This week was not a teaching week. Lucky you!<br>";

				$weekDataArray[] = array(
					'week_id' => $week['week_id'],
					'attendance_percentage' => 0,
					'absent_percentage' => 0
				);
			}
			echo "</div>";
		}// End if($current_time >= $week['week_start']){
		else{
			//final graph data array
			$weekDataArray[] = array(
				'week_id' => $week['week_id'],
				'attendance_percentage' => 0,
				'absent_percentage' => 0
			);
		}
	}// End foreach($weekArray as $week){	
	echo "<div class=\"grid_12\">";
	echo "<h2>Overall Attendance Data</h2>";
	echo "</div>";
	
	echo "<div class=\"grid_12  dataBox\">";
	echo "<h2>Overall Data</h2>";
	$attendancePercentage = round(($overallAttendedCount / $overallLectureCount) * 100, 2);
	echo "<p>" . $overallLectureCount . " sessions (<font class=\"Green\">" . $overallAttendedCount . "</font> - <font class=\"Red\">" . $overallAbsentCount . "</font>) " . $attendancePercentage . "%</p>";
	
	//graph stuff
	echo "<div class=\"largePie\" id=\"finalPieChart\"></div>";
	
	echo "</div>"; //end overall column
	//Final pie chart
	?>
	<script language="javascript">
	plot = jQuery.jqplot('finalPieChart',				
		[[['Attended', <?php echo $overallAttendedCount; ?>],['Absent', <?php echo $overallAbsentCount; ?>]]],
		{
			title: {
				show: false
			},
			seriesDefaults: {
				shadow: true,
				renderer: $.jqplot.PieRenderer,
				rendererOptions: {
					highlightMouseOver: 1,
					sliceMargin: 10,
					padding: 20,
					shadowOffset: 2,
					startAngle: 0,
					showDataLabels: true
				}
			},
			grid: {
				background: '#FFFFFF', 
				borderColor: '#FFF',
				shadow: false
			},
			seriesColors: ["#71BC78", "#DC143C"],
			legend: {
				show: true,
				placement: 'outside'
			}
		}
	);
	</script>
	<?php
	//final bar chart
	echo "<div class=\"largeGraph\" id=\"finalBarChart\"></div>";
	
	$attendedString = '';
	$absentString = '';
	$id_string = '';
	foreach ($weekDataArray as $array){
		$id_string .= "'week " . $array['week_id'] . "', ";
		$attendedString .= $array['attendance_percentage'] . ", ";
		$absentString .=  $array['absent_percentage'] . ", ";
	}
	$id_pos = strrpos($id_string, ',');
	$attended_pos = strrpos($attendedString, ',');
	$absent_pos = strrpos($absentString, ',');
	
	$id_string = substr($id_string, 0, $id_pos);
	$attendedString = substr($attendedString, 0, $attended_pos);
	$absentString = substr($absentString, 0, $absent_pos);
	?>
	<script language="javascript">
	var attendedSeries = [<?php echo $attendedString; ?>];
	var absentSeries = [<?php echo $absentString; ?>];
	var axisLabels = [<?php echo $id_string; ?>];
	plot2 = $.jqplot('finalBarChart', [attendedSeries, absentSeries], {
		title: {
			text: 'Week by Week Attendance Overview',
			fontSize: '12pt',
			fontFamily: 'Helmet, Freesans, sans-serif'
		},
		stackSeries: true,
		seriesDefaults: {
			renderer:$.jqplot.BarRenderer,
			rendererOptions: {
				barMargin: 5
			},
			pointLabels: {
				show: true
			}
		},
		series:[{label:'Attended'},{label:'Absent'},{label:'Break'}],
		seriesColors: ["#71BC78", "#DC143C"],
		axes: {
			xaxis: {
				renderer: $.jqplot.CategoryAxisRenderer,
				label: 'Academic Week',
				labelOptions:{
					fontSize: '10pt',
					fontFamily: 'Helmet, Freesans, sans-serif'
				},
				tickRenderer: $.jqplot.CanvasAxisTickRenderer,
				tickOptions: {
					fontFamily: 'Helmet, Freesans, sans-serif',
					angle: -40
				},
				ticks: axisLabels
			},
			yaxis: {
				min: 0,
				max: 100,
				tickInterval: 10,
				padMin: 0,
				label: 'Attendance Percentage',
				labelOptions:{
					fontSize: '10pt',
					fontFamily: 'Helmet, Freesans, sans-serif'
				}
			}
		},
		grid: {
			background: '#FFFFFF', 
			gridLineColor: '#FFFFFF'
		},
		legend: {
			show: true,
			location: 'ne',
			placement: 'outside'
		}     
	});
    </script>
	<?php
}
function attendanceByMonth($mongo_db, $Person_ID, $moduleArray, $monthArray){
	/****** Start Week by Week Analysis ******/
	$collection = $mongo_db->am_user_events; // Change to MongoDB relevant collection
	$current_time = time();
	echo "<h2>Month by Month Attendance</h2>";
	//Variables to control overall figures
	$overallLectureCount = 0;
	$overallAttendedCount = 0;
	$overallAbsentCount = 0;
	$monthDataArray = array();
	$monthDivCounter = 0;
	
	foreach($monthArray as $month){
		//Variables to control monthly figures
		$monthLectureCount = 0;
		$monthAttendedCount = 0;
		$monthAbsentCount = 0;
		if($current_time >= $month['Month_Start']){
			//DIV creation
			if($monthDivCounter == 2){
				echo "<div class=\"grid_4 last dataBox\">";
				$monthDivCounter = 0;
			}
			else{
				echo "<div class=\"grid_4 dataBox\">";
				$monthDivCounter++;
			}
			
			
			echo "<h3>" . $month['Month'] . "</h3>";

			
			$sessionArray = array();
			//search for all lecture data for this month
			foreach($moduleArray as $module){
				//***** Pull data from database, increment counters, output result. *****/
				$cursor = $collection->find(array('Module_ID' => $module['Module_ID'], 'Start_Time' => array('$gte' => $month['Month_Start'], '$lte' => $month['Month_End']), 'Attended.' .$Person_ID => array('$exists' => true)));
				$cursorArray = iterator_to_array($cursor, false); // 'use_keys' must be false.						

				if(!empty($cursorArray)){
					foreach($cursorArray as $lecture){
						$sessionArray[] = $lecture;
					}
				}
			}//End foreach($moduleArray as $module){

			//sort the array by attendance day
			usort($sessionArray, 'compareSort');

			//output each session and increment counters
			foreach ($sessionArray as $session){	
				if($session['Attended'][$Person_ID] == 1){
					echo "<div class=\"att attGreen\">" . $session['Module_ID'] . " " . customDateLong($session['Start_Time'], $session['End_Time'], $session['Event_Type']) . "</div>";
					$monthAttendedCount++;
				}
				else{
					echo "<div class=\"att attRed\">" . $session['Module_ID'] . " " . customDateLong($session['Start_Time'], $session['End_Time'], $session['Event_Type']) . "</div>";
					$monthAbsentCount++;
				}
				echo "</br>";
				$monthLectureCount++;
			}	
			echo "<p>" . $monthLectureCount . " sessions (<font class=\"Green\">" . $monthAttendedCount . "</font> - <font class=\"Red\">" . $monthAbsentCount . "</font>)</p>";
			
			//graph stuff
			echo "<div class=\"dataGraph\" id=\"month_" . $month['Month'] . "_graph\"></div>";
			?>
			<script language="javascript">
			plot = jQuery.jqplot('<?php echo "month_" . $month['Month'] . "_graph"; ?>',				
				[[['Attended', <?php echo $monthAttendedCount; ?>],['Absent', <?php echo $monthAbsentCount; ?>]]],
				{
					title: {
						show: false
					},
					seriesDefaults: {
						shadow: true,
						renderer: $.jqplot.PieRenderer,
						rendererOptions: {
							highlightMouseOver: 1,
							sliceMargin: 3,
							padding: 10,
							shadowOffset: 2,
							startAngle: 30,
							showDataLabels: true
						}
					},
					grid: {
						background: '#FFFFFF', 
						borderColor: '#FFF',
						shadow: false
					},
					seriesColors: ["#71BC78", "#DC143C"],
					legend: {
						show: true
					}
				}
			);
			</script>
			<?php
			//add counters
			$overallLectureCount += $monthLectureCount;
			$overallAttendedCount += $monthAttendedCount;
			$overallAbsentCount += $monthAbsentCount;
			
			//Calculate percentage
			$attendancePercentage = ($monthAttendedCount / $monthLectureCount) * 100;
			$absentPercentage = 100 - $attendancePercentage;
			
			//final graph data array
			$monthDataArray[] = array(
				'month_id' => $month['Month'],
				'attendance_percentage' => $attendancePercentage,
				'absent_percentage' => $absentPercentage
			);
			echo "</div>";
		}// End if($current_time >= $month['Month_Start']){
		else{
			//final graph data array
			$monthDataArray[] = array(
				'month_id' => $month['Month'],
				'attendance_percentage' => 0,
				'absent_percentage' => 0
			);
		}
	}// End foreach($monthArray as $month){	
	echo "<div class=\"grid_12\">";
	echo "<h2>Overall Attendance Data</h2>";
	echo "</div>";
	
	echo "<div class=\"grid_12  dataBox\">";
	echo "<h2>Overall Data</h2>";
	$attendancePercentage = round(($overallAttendedCount / $overallLectureCount) * 100, 2);
	echo "<p>" . $overallLectureCount . " sessions (<font class=\"Green\">" . $overallAttendedCount . "</font> - <font class=\"Red\">" . $overallAbsentCount . "</font>) " . $attendancePercentage . "%</p>";
	
	//graph stuff
	echo "<div class=\"largePie\" id=\"finalPieChart\"></div>";
	
	echo "</div>"; //end overall column
	//Final pie chart
	?>
	<script language="javascript">
	plot = jQuery.jqplot('finalPieChart',				
		[[['Attended', <?php echo $overallAttendedCount; ?>],['Absent', <?php echo $overallAbsentCount; ?>]]],
		{
			title: {
				show: false
			},
			seriesDefaults: {
				shadow: true,
				renderer: $.jqplot.PieRenderer,
				rendererOptions: {
					highlightMouseOver: 1,
					sliceMargin: 10,
					padding: 20,
					shadowOffset: 2,
					startAngle: 0,
					showDataLabels: true
				}
			},
			grid: {
				background: '#FFFFFF', 
				borderColor: '#FFF',
				shadow: false
			},
			seriesColors: ["#71BC78", "#DC143C"],
			legend: {
				show: true,
				placement: 'outside'
			}
		}
	);
	</script>
	<?php
	//final bar chart
	echo "<div class=\"largeGraph\" id=\"finalBarChart\"></div>";
	
	$attendedString = '';
	$absentString = '';
	$id_string = '';
	foreach ($monthDataArray as $array){
		$id_string .= "'" . $array['month_id'] . "', ";
		$attendedString .= $array['attendance_percentage'] . ", ";
		$absentString .=  $array['absent_percentage'] . ", ";
	}
	$id_pos = strrpos($id_string, ',');
	$attended_pos = strrpos($attendedString, ',');
	$absent_pos = strrpos($absentString, ',');
	
	$id_string = substr($id_string, 0, $id_pos);
	$attendedString = substr($attendedString, 0, $attended_pos);
	$absentString = substr($absentString, 0, $absent_pos);
	?>
	<script language="javascript">
	var attendedSeries = [<?php echo $attendedString; ?>];
	var absentSeries = [<?php echo $absentString; ?>];
	var axisLabels = [<?php echo $id_string; ?>];
	plot2 = $.jqplot('finalBarChart', [attendedSeries, absentSeries], {
		title: {
			text: 'Month by Month Attendance Overview',
			fontSize: '12pt',
			fontFamily: 'Helmet, Freesans, sans-serif'
		},
		stackSeries: true,
		seriesDefaults: {
			renderer:$.jqplot.BarRenderer,
			rendererOptions: {
				barMargin: 5
			},
			pointLabels: {
				show: true
			}
		},
		series:[{label:'Attended'},{label:'Absent'},{label:'Break'}],
		seriesColors: ["#71BC78", "#DC143C"],
		axes: {
			xaxis: {
				renderer: $.jqplot.CategoryAxisRenderer,
				label: 'Month',
				labelOptions:{
					fontSize: '10pt',
					fontFamily: 'Helmet, Freesans, sans-serif'
				},
				tickRenderer: $.jqplot.CanvasAxisTickRenderer,
				tickOptions: {
					fontFamily: 'Helmet, Freesans, sans-serif',
					angle: -40
				},
				ticks: axisLabels
			},
			yaxis: {
				min: 0,
				max: 100,
				tickInterval: 10,
				padMin: 0,
				label: 'Attendance Percentage',
				labelOptions:{
					fontSize: '10pt',
					fontFamily: 'Helmet, Freesans, sans-serif'
				}
			}
		},
		grid: {
			background: '#FFFFFF', 
			gridLineColor: '#FFFFFF'
		},
		legend: {
			show: true,
			location: 'ne',
			placement: 'outside'
		}     
	});
    </script>
	<?php
}
function attendanceByModule($mongo_db, $Person_ID, $moduleArray){
	$collection = $mongo_db->am_user_events; // Change to MongoDB relevant collection
	$current_time = time();
	echo "<h2>Attendance by Module</h2>";
	//Variables to control overall figures
	$overallLectureCount = 0;
	$overallAttendedCount = 0;
	$overallAbsentCount = 0;
	
	//search for all lecture data for this  
	foreach($moduleArray as $module){
		//Variables to control module figures
		$moduleLectureCount = 0;
		$moduleAttendedCount = 0;
		$moduleAbsentCount = 0;
		
		//DIV creation
		echo "<div class=\"grid_12 dataBox\">";
		echo "<h3>" . $module['Module_Title'] . "</h3>";

		//***** Pull data from database, increment counters, output result. *****/
		$cursor = $collection->find(array('Module_ID' => $module['Module_ID'], 'Attended.' .$Person_ID => array('$exists' => true)));
		$cursorArray = iterator_to_array($cursor, false); // 'use_keys' must be false.						
		
		$sessionArray = array();
		if(!empty($cursorArray)){
			foreach($cursorArray as $lecture){
				$sessionArray[] = $lecture;
			}
			
			usort($sessionArray, 'compareSort');
			
			//output each session and increment counters
			$lastMonth = date("F", $sessionArray[0]['Start_Time']);
			echo "<div class=\"grid_3 first\">";
			echo "<h4>" . $lastMonth . "</h4>";
			$count = 1; //1 because of the above div creation
			foreach ($sessionArray as $session){
				if($lastMonth != date("F", $session['Start_Time'])){
					echo "</div>";
						if($count == 3){
							echo "<div class=\"grid_3 last\">";
							$count = 0;
						}
						else if($count == 0){
							echo "<div class=\"grid_3 first clear\">";
							$count++;
						}
						else{
							echo "<div class=\"grid_3\">";
							$count++;
						}
					$lastMonth = date("F", $session['Start_Time']);
					echo "<h4>" . $lastMonth . "</h4>";
				}
				if($session['Attended'][$Person_ID] == 1){
					echo "<div class=\"att attGreen\">" . customDateLong($session['Start_Time'], $session['End_Time'], $session['Event_Type']) . "</div>";
					$moduleAttendedCount++;
				}
				else{
					echo "<div class=\"att attRed\">" .  customDateLong($session['Start_Time'], $session['End_Time'], $session['Event_Type']) . "</div>";
					$moduleAbsentCount++;
				}
				echo "<br>";
				$moduleLectureCount++;
			}//End foreach ($sessionArray as $session){	
			echo "</div>";
			
			echo "<div class=\"overallStatContainer\">" .
			"<h4>Module Analysis</h4>" .
			"<p>" . $moduleLectureCount . " sessions (<font class=\"Green\">" . $moduleAttendedCount . "</font> - <font class=\"Red\">" . $moduleAbsentCount . "</font>)</p>";
			
			//graph stuff
			echo "<div class=\"smallDataGraph\" id=\"module_" . $module['Module_ID'] . "_graph\"></div>";
			?>
			<script language="javascript">
			plot = jQuery.jqplot('<?php echo "module_" . $module['Module_ID'] . "_graph"; ?>',				
				[[['Attended', <?php echo $moduleAttendedCount; ?>],['Absent', <?php echo $moduleAbsentCount; ?>]]],
				{
					title: {
						show: false
					},
					seriesDefaults: {
						shadow: true,
						renderer: $.jqplot.PieRenderer,
						rendererOptions: {
							highlightMouseOver: 1,
							sliceMargin: 3,
							padding: 10,
							shadowOffset: 2,
							startAngle: 30,
							showDataLabels: true
						}
					},
					grid: {
						background: '#FFFFFF', 
						borderColor: '#FFF',
						shadow: false
					},
					seriesColors: ["#71BC78", "#DC143C"],
					legend: {
						show: true
					}
				}
			);
			</script>
			<?php
			//add counters
			$overallLectureCount += $moduleLectureCount;
			$overallAttendedCount += $moduleAttendedCount;
			$overallAbsentCount += $moduleAbsentCount;
			
			//Calculate percentage
			$attendancePercentage = ($moduleAttendedCount / $moduleLectureCount) * 100;
			$absentPercentage = 100 - $attendancePercentage;
			
			//final graph data array
			$moduleDataArray[] = array(
				'module_id' => $module['Module_Title'],
				'attendance_percentage' => $attendancePercentage,
				'absent_percentage' => $absentPercentage
			);
		}//End if(!empty($cursorArray)){
		else{
			echo "No data could be found for this module.";
			$moduleDataArray[] = array(
				'module_id' => $module['Module_Title'],
				'attendance_percentage' => 0,
				'absent_percentage' => 0
			);
		}
		echo "</div></div>"; //close opened Div's
	}//End foreach($moduleArray as $module){
    
	echo "<div class=\"grid_12\">";
	echo "<h2>Overall Attendance Data</h2>";
	echo "</div>";
	
	echo "<div class=\"grid_12  dataBox\">";
	echo "<h2>Overall Data</h2>" .
	"<p>" . $overallLectureCount . " sessions (<font class=\"Green\">" . $overallAttendedCount . "</font> - <font class=\"Red\">" . $overallAbsentCount . "</font>)</p>";
	
	//graph stuff
	echo "<div class=\"largePie\" id=\"finalPieChart\"></div>";
	
	echo "</div>"; //end overall column
	//Final pie chart
	?>
	<script language="javascript">
	plot = jQuery.jqplot('finalPieChart',				
		[[['Attended', <?php echo $overallAttendedCount; ?>],['Absent', <?php echo $overallAbsentCount; ?>]]],
		{
			title: {
				show: false
			},
			seriesDefaults: {
				shadow: true,
				renderer: $.jqplot.PieRenderer,
				rendererOptions: {
					highlightMouseOver: 1,
					sliceMargin: 10,
					padding: 20,
					shadowOffset: 2,
					startAngle: 0,
					showDataLabels: true
				}
			},
			grid: {
				background: '#FFFFFF', 
				borderColor: '#FFF',
				shadow: false
			},
			seriesColors: ["#71BC78", "#DC143C"],
			legend: {
				show: true,
				placement: 'outside'
			}
		}
	);
	</script>
	<?php
	//final bar chart
	echo "<div class=\"grid_12 center\">";
	echo "<div class=\"smallGraph\" id=\"finalBarChart\"></div>";
	echo "</div>";
	
	$attendedString = '';
	$absentString = '';
	$id_string = '';
	foreach ($moduleDataArray as $array){
		$id_string .= "'" . $array['module_id'] . "', ";
		$attendedString .= $array['attendance_percentage'] . ", ";
		$absentString .=  $array['absent_percentage'] . ", ";
	}
	$id_pos = strrpos($id_string, ',');
	$attended_pos = strrpos($attendedString, ',');
	$absent_pos = strrpos($absentString, ',');
	
	$id_string = substr($id_string, 0, $id_pos);
	$attendedString = substr($attendedString, 0, $attended_pos);
	$absentString = substr($absentString, 0, $absent_pos);
	?>
	<script language="javascript">
	var attendedSeries = [<?php echo $attendedString; ?>];
	var absentSeries = [<?php echo $absentString; ?>];
	var axisLabels = [<?php echo $id_string; ?>];
	plot2 = $.jqplot('finalBarChart', [attendedSeries, absentSeries], {
		title: {
			text: 'Module Attendance Overview',
			fontSize: '12pt',
			fontFamily: 'Helmet, Freesans, sans-serif'
		},
		stackSeries: true,
		seriesDefaults: {
			renderer:$.jqplot.BarRenderer,
			rendererOptions: {
				barMargin: 20
			},
			pointLabels: {
				show: true
			}
		},
		series:[{label:'Attended'},{label:'Absent'}],
		seriesColors: ["#71BC78", "#DC143C"],
		axes: {
			xaxis: {
				renderer: $.jqplot.CategoryAxisRenderer,
				label: 'Module',
				labelOptions:{
					fontSize: '10pt',
					fontFamily: 'Helmet, Freesans, sans-serif'
				},
				tickRenderer: $.jqplot.CanvasAxisTickRenderer,
				tickOptions: {
					fontFamily: 'Helmet, Freesans, sans-serif',
					angle: -20
				},
				ticks: axisLabels
			},
			yaxis: {
				min: 0,
				max: 100,
				tickInterval: 10,
				padMin: 0,
				label: 'Attendance Percentage',
				labelOptions:{
					fontSize: '10pt',
					fontFamily: 'Helmet, Freesans, sans-serif'
				}
			}
		},
		grid: {
			background: '#FFFFFF', 
			gridLineColor: '#FFFFFF'
		},
		legend: {
			show: true,
			location: 'ne',
			placement: 'outside'
		}     
	});
    </script>
	<?php
	/****** END Module Analysis ******/
}
?>