<?php 

	spl_autoload_register(function($className){
		$path = strtolower($className) . ".php";
		//echo $path;
		if (file_exists($path)) 
		{
			require_once($path);
		}
		else
		{
			echo "File path not found.";
		}
	}) ;

 ?>