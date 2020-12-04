<?php 
	
	require_once('include_required_file.php');
	//api.php rest.php dbconnect.php jwt.php
	//require_once('jwt.php');
	//require_once('api.php');
	//require_once('rest.php');
	//require_once('dbconnect.php');
	//die();
	$api = new Api();
	$api->processApi();

	
 ?>