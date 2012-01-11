<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	session_start();
	$this->load->view('public_user/includes/include_Header', $this->data);
	
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
	
	if(!empty($_SESSION['Prototype_ID'])){
		unset($_SESSION['Prototype_ID']);
	}
?>	
	<title>AM - Attendance Monitor (Prototype 1)</title>
    <meta name="description" content="Attendance Monitor is an online tool designed to help students track their attendance performance.">
    <meta name="author" content="Dale Mckeown, www.dalemckeown.co.uk">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0">
    <script src="http://c94471.r71.cf3.rackcdn.com/modernizr.js"></script>
</head>
	
<body>
    <header id="cwd_header" role="banner">
        <section class="cwd_container">
            <hgroup class="grid_12" id="cwd_hgroup">
                <a href="<?php echo $base_url; ?>"><h1>Prototype 1</h1></a>
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
		
		<p>The first major prototype. It is concerned with the following tasks:
		<ul>
			<li>Setting up CodeIgniter and transferring all previous work to this framework.</li>
			<li>Structuring and styling each page following the <a href=\"http://cwd.online.lincoln.ac.uk\">Common Web Design</a>.</li>
			<li>Connecting to <a href=\"http://data.lincoln.ac.uk\">data.lincoln.ac.uk</a> using the OAuth protocol to log-in students and staff members.</li>
			<li>Setting up a remote server to host a  <a href=\"http://mongodb.org\">MongoDB</a> NoSQL database.</li>
		</ul>
		</p>";
	} ?>
    </div>
    </section>
    
 <?php $this->load->view('public_user/includes/include_Footer'); ?>

<!-- Put all JavaScript code below this line -->
<script src="http://c94471.r71.cf3.rackcdn.com/jquery.js" type="text/javascript"></script>
<!--[if (lt IE 9) & (!IEMobile)]>
<script src="http://c94471.r71.cf3.rackcdn.com/selectivizr-1.0.1.js"></script>
<![endif]-->
<script src="http://c94471.r71.cf3.rackcdn.com/cwd.js" type="text/javascript"></script>
</body>
</html>
<?php ob_end_flush(); ?>