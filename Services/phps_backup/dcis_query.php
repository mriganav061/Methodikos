<?php
	include('config.php');
	
	//connection String
	$connect = mysql_connect($dbhost, $dbuser, $dbpass) or die('Could not connect: ' . mysql_error());
	
	//select database
	mysql_select_db($dbname, $connect);
	
	//Select The database
	$bool = mysql_select_db($dbname, $connect);
	if ($bool === False){
	   print "can't find $dbname";
	}

	// get data and store in a json array
	if ($_GET['query_type'] == 'cols')
		$query = "SHOW FULL COLUMNS FROM dcis_project_information";
	else if ($_GET['query_type'] == 'grid')
		$query = "SELECT ".$_GET['cols']." FROM dcis_project_information WHERE DISTRICT_NUMBER=17 AND FISCAL_YEAR IN (".stripslashes($_GET['years']).")";
	else if ($_GET['query_type'] == 'years')
		$query = "SELECT DISTINCT FISCAL_YEAR FROM dcis_project_information WHERE DISTRICT_NUMBER=17 ORDER BY FISCAL_YEAR DESC";
	else
		$query = "SELECT ISN, LAYMAN_DESCRIPTION1, LAYMAN_DESCRIPTION2, EST_CONST_COST, TYPE_OF_WORK, DISTRICT_NUMBER, HIGHWAY_NUMBER, PROJ_CLASS, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP, FISCAL_YEAR, PROJ_LENGTH FROM dcis_project_information AS a WHERE BEG_REF_MARKER_NBR NOT LIKE ' ' AND END_REF_MARKER_NBR NOT LIKE ' ' AND DISTRICT_NUMBER=17 AND cast(FISCAL_YEAR AS DECIMAL(4,0)) < 2012 ORDER BY FISCAL_YEAR DESC;";
	 
	$result = mysql_query($query) or die("SQL Error 1: " . mysql_error());
	
	$rows = array();
	while($r = mysql_fetch_assoc($result)) {
		$rows[] = $r;
	}
	echo "{\"data\":" .json_encode($rows). "}";
?>