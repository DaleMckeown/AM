<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>

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
    <!-- CWD includes -->
    <link rel="shortcut icon" href="<?php echo $base_url . $images; ?>favicon.ico">
    <link rel="apple-touch-icon" href="<?php echo $base_url . $images; ?>appleIcon.png">
	<link rel="stylesheet" href="http://c94471.r71.cf3.rackcdn.com/cwd.css">
    <script src="http://c94471.r71.cf3.rackcdn.com/jquery.js" type="text/javascript"></script>
    
    <!-- Other includes -->
    <link rel="stylesheet" href="<?php echo $base_url . $css; ?>override_Styles.css">
    
    <!-- jqPlot includes -->
    <script language="javascript" type="text/javascript" src="<?php echo $base_url . $javascript; ?>jquery.jqplot.min.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo $base_url . $javascript; ?>jqplot.barRenderer.min.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo $base_url . $javascript; ?>jqplot.canvasAxisTickRenderer.min.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo $base_url . $javascript; ?>jqplot.canvasTextRenderer.min.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo $base_url . $javascript; ?>jqplot.categoryAxisRenderer.min.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo $base_url . $javascript; ?>jqplot.pieRenderer.min.js"></script>
    
    
	
    <link rel="stylesheet" href="<?php echo $base_url . $css; ?>jquery.jqplot.min.css">
	
    <script language="javascript">
	$(document).ready(function(){
		jQuery.jqplot.config.enablePlugins = true;
		//do javascrip stuff
	});
	</script>