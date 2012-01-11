<?php
/****************************************/
/*      Copyright (c) Dale Mckeown		*/
/*       Last edited: 06/01/2012        */
/*        www.dalemckeown.co.uk  		*/
/****************************************/

// Set Application parameters
$Application_ID = "6i8bfIJ5oUHUykZ2FG8c9Md44bP8Ezdq";
$Application_Secret = "Q4EGw3rRv7OY691B4BVp186SdS8KoyUi";
$Application_Scope = "user.basic,user.courses,user.contact,user.calendars,calendars.crud";

function mongoConnect(){
	$username = "amApp"; //set database username
	$password = "rocketshipam"; //set database password
	$m = new Mongo("mongodb://$username:$password@31.222.170.131"); //connect to the database
	return $m->am; //select a database
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
function randomID($length){
	$random= "";
	srand(microtime()*1000000);
	$data = "AbcDE123IJKLMN67QRSTUVWXYZaBCdefghijklmn123opq45rs67tuv89wxyz0FGH45OP89";

	for($i = 0; $i < $length; $i++){
		$random .= substr($data, (rand()  % (strlen($data))), 1);
	}
	return $random;
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
function getPrototypes(){
	// Get current directory
	$thisDir = getcwd();
	// Get subdirectories of this directory
	$directories = glob($thisDir . '/*' , GLOB_ONLYDIR);
	
	// Define a prototype array & current prototype number
	$prototypeArray = array();

	$currentPrototype = 0;
	//Loop through each directory
	foreach($directories as $directory){
		// Get the position of the last folder, plus 2 (for '/p')
		$lastInstance = strrpos($directory, "/") + 2;
		// Slice the directory name from the lastInstance and a length.
		$dirName = substr($directory, $lastInstance, 2);
		//Check that the dirName is numberic
		if(is_numeric($dirName)){
			//if so, add it to our prototype array and increment the current prototype number
			$prototypeArray[] = $dirName;
			$currentPrototype++;	
		}
	}
	//Set the link to the current prototype.
	$currentPrototypeLink = "http://www.am.dalemckeown.co.uk/p" . $currentPrototype;
	$returnArray = array('prototypeArray' => $prototypeArray, 'currentPrototype' => $currentPrototype, 'currentPrototypeLink' => $currentPrototypeLink);
	return $returnArray;		
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

?>