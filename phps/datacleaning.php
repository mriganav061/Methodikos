<?php
	//ini_set("memory_limit","9000M");
	
	// this code will make row pmis database table be able to have other data columns available in order to process
	// future analysis	
	include_once('session.php');
	global $database;
	global $session;
	set_time_limit(0);
	$table = $_SESSION['pmis'] = "pmis_condition_summary_".$_SESSION['district'];
	$srctable = "pmis_data_collection_section_".$_SESSION['district'];
	
	$session->updateprogress(0, 'initializing...');
	// some parameters
	$min_score = $_POST['mincs'];
	$min_ride_score = $_POST['minuride'];
	$base_year = $_SESSION['baseyear'];



	// constants
	$reset_score = 100;
	$reset_ride = $_POST['resetride'];
	
	$result = mysql_query("SHOW COLUMNS FROM $table");
	if (!$result) {
		echo 'Could not run query: ' . mysql_error();
		exit;
	}
	
	if (mysql_num_rows($result) > 0) {
		while ($row = mysql_fetch_assoc($result)) {
			$field_array[] = $row['Field'];
		}
	}
	
	$where = "WHERE a.BEG_REF_MARKER_DISP = b.BEG_REF_MARKER_DISP AND a.BEG_REF_MARKER_NBR = b.BEG_REF_MARKER_NBR AND a.FISCAL_YEAR = b.FISCAL_YEAR AND a.RESPONSIBLE_DISTRICT = b.RESPONSIBLE_DISTRICT AND a.SIGNED_HIGHWAY_RDBD_ID = b.SIGNED_HIGHWAY_RDBD_ID";
	


	/** 
	 * add NUMBER_THRU_LANES column if not exist
	 */
	$session->updateprogress(5, 'Update ZONE_NUMBER Column...');
	if (in_array("ZONE_NUMBER", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD ZONE_NUMBER char(2)") or die(mysql_error());
	}
	$query = "UPDATE $table AS a SET a.ZONE_NUMBER = '04'";
	mysql_query($query) or die(mysql_error());

	/** 
	 * add NUMBER_THRU_LANES column if not exist
	 */
	$session->updateprogress(5, 'Update NUMBER_THRU_LANES Column...');
	if (in_array("NUMBER_THRU_LANES", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD NUMBER_THRU_LANES smallint(5)") or die(mysql_error());
	}
	$query = "UPDATE $table AS a SET a.NUMBER_THRU_LANES = (SELECT b.NUMBER_THRU_LANES FROM $srctable AS b $where);";
	mysql_query($query) or die(mysql_error());
	
	/**
	 * add DISTRICT_NAME column if not exist
	 */ 
	$session->updateprogress(10, 'Update DISTRICT_NAME Column...');
	if (in_array("DISTRICT_NAME", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD DISTRICT_NAME varchar(48)") or die(mysql_error());
	}
	$district = ucwords(str_replace("_"," ",$_SESSION['district']));
	$query = "UPDATE $table AS a SET a.DISTRICT_NAME = '$district'";
	mysql_query($query) or die(mysql_error());
	
	/**
	 *  add SECT_LENGTH column if not exist
	 */
	$session->updateprogress(15, 'Update SECT_LENGTH Column...');
	if (in_array("SECT_LENGTH", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD SECT_LENGTH Double(15,5)") or die(mysql_error());
	}
	$query = "UPDATE $table AS a SET a.SECT_LENGTH = (SELECT b.SECT_LENGTH_CENTERLINE_MEAS FROM $srctable AS b $where);";
	mysql_query($query) or die(mysql_error());
	
	/**
	 * add AADT column if not exist
	 */ 
	$session->updateprogress(20, 'Update AADT Column...');
	if (in_array("AADT", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD AADT Int(10)") or die(mysql_error());
	}
	$query = "UPDATE $table AS a SET a.AADT = (SELECT b.AADT_CURRENT FROM $srctable AS b $where);";
	mysql_query($query) or die(mysql_error());
	
	/**
	 * add PVMNT_TYPE_BROAD_CODE column if not exist
	 */
	$session->updateprogress(25, 'Update PVMNT_TYPE_BROAD_CODE Column...');
	if (in_array("PVMNT_TYPE_BROAD_CODE", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD PVMNT_TYPE_BROAD_CODE Varchar(1)") or die(mysql_error());
	}
	$query = "UPDATE $table AS a SET a.PVMNT_TYPE_BROAD_CODE = (SELECT b.PVMNT_TYPE_BROAD_CODE FROM $srctable AS b $where);";
	mysql_query($query) or die(mysql_error());
	
	/**
	 *  add PVMNT_TYPE_DTL_RD_LIFE_CODE column if not exist
	 */
	$session->updateprogress(30, 'Update PVMNT_TYPE_DTL_RD_LIFE_CODE Column...');
	if (in_array("PVMNT_TYPE_DTL_RD_LIFE_CODE", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD PVMNT_TYPE_DTL_RD_LIFE_CODE Varchar(2)") or die(mysql_error());
	}
	$query = "UPDATE $table AS a SET a.PVMNT_TYPE_DTL_RD_LIFE_CODE = (SELECT b.PVMNT_TYPE_DTL_RD_LIFE_CODE FROM $srctable AS b $where);";
	mysql_query($query) or die(mysql_error());
	
	/**
	 *  add TRUCK_AADT_PCT column if not exist
	 */
	$session->updateprogress(35, 'Update TRUCK_AADT_PCT Column...');
	if (in_array("TRUCK_AADT_PCT", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD TRUCK_AADT_PCT Double(15,5)") or die(mysql_error());
	}	
	$query = "UPDATE $table AS a SET a.TRUCK_AADT_PCT = (SELECT b.TRUCK_AADT_PCT FROM $srctable AS b $where);";
	mysql_query($query) or die(mysql_error());
	
	/**
	 * add CURRENT_18KIP_MEAS column if not exist
	 */ 
	$session->updateprogress(40, 'Update CURRENT_18KIP_MEAS Column...');
	if (in_array("CURRENT_18KIP_MEAS", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD CURRENT_18KIP_MEAS Int(10)") or die(mysql_error());
	}
	$query = "UPDATE $table AS a SET a.CURRENT_18KIP_MEAS = (SELECT b.CURRENT_18KIP_MEAS FROM $srctable AS b $where);";
	mysql_query($query) or die(mysql_error());
	
	/**
	 * add SPEED_LIMIT_MIX column if not exist
	 */
	$session->updateprogress(45, 'Update SPEED_LIMIT_MAX Column...');
	if (in_array("SPEED_LIMIT_MAX", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD SPEED_LIMIT_MAX Smallint(5)") or die(mysql_error());
	}
	$query = "UPDATE $table AS a SET a.SPEED_LIMIT_MAX = (SELECT b.SPEED_LIMIT_MAX FROM $srctable AS b $where);";
	mysql_query($query) or die(mysql_error());
	
	/**
	 * add PRIOR_TREATMENT column if not exist
	 * add YEAR_OF_PRIOR_TREATMENT column if not exist
	 */ 
	$session->updateprogress(50, 'Update PRIOR_TREATMENT and YEAR_OF_PRIOR_TREATMENT Columns...');


	if (in_array("PRIOR_TREATMENT", $field_array) == false &&
		in_array("YEAR_OF_PRIOR_TREATMENT", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD PRIOR_TREATMENT varchar(3)") or die(mysql_error());
		mysql_query("ALTER TABLE $table ADD YEAR_OF_PRIOR_TREATMENT varchar(4)") or die(mysql_error());
	}

	switch ($_SESSION['district']) {
		case "bryan":
			$district_number = 17;
			break;
		case "fort_worth":
			$district_number = 2;
			break;
		case "san_antonio":
			$district_number = 15;
			break;
		case "lubbock":
			$district_number = 5;
			break;
		case "tyler":
			$district_number = 10;
			break;
		default:
			$district_number = 17;	
	}
	
	$query = "SELECT ISN, LAYMAN_DESCRIPTION1, LAYMAN_DESCRIPTION2, EST_CONST_COST, TYPE_OF_WORK, DISTRICT_NUMBER, HIGHWAY_NUMBER, PROJ_CLASS, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP, FISCAL_YEAR, PROJ_LENGTH FROM dcis_project_information AS a WHERE BEG_REF_MARKER_NBR NOT LIKE ' ' AND END_REF_MARKER_NBR NOT LIKE ' ' AND DISTRICT_NUMBER=$district_number AND cast(FISCAL_YEAR AS DECIMAL(4,0)) <= $base_year ORDER BY FISCAL_YEAR DESC;";
	$projects = $database->Query($query);
	$query = "SELECT FISCAL_YEAR, RATING_CYCLE_CODE, SIGNED_HIGHWAY_RDBD_ID, DISTRESS_SCORE,
	CONDITION_SCORE, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP, NUMBER_THRU_LANES FROM $table WHERE RATING_CYCLE_CODE='P' AND FISCAL_YEAR=$base_year";
	$sections = $database->Query($query);


	for ($i=0 ; $i<count($projects) ; $i++) {
		$highway = $projects[$i]['HIGHWAY_NUMBER'];
		$highway = str_replace(array(" ", "-"), "", $highway);
		$prefix = substr($projects[$i]['HIGHWAY_NUMBER'], 0, 2);
		if (preg_match("/[^A-Za-z\s]/", $prefix) == 1)
			continue;
		$number = substr($highway, 2);
		if (is_numeric($number[0]) == false)
			continue;
		if (is_numeric(substr($number, -1)) == false) {
			$number = substr($number, 0, -1);
		}
		$highway = $prefix.str_pad($number, 4, "0", STR_PAD_LEFT);
		$beg = intval($projects[$i]['BEG_REF_MARKER_NBR']) + floatval($projects[$i]['BEG_REF_MARKER_DISP']);
		$end = intval($projects[$i]['END_REF_MARKER_NBR']) + floatval($projects[$i]['END_REF_MARKER_DISP']);
		$projects[$i]['NEW_HIGHWAY_NUMBER'] = $highway;
		$projects[$i]['BEG_POINT'] = $beg;
		$projects[$i]['END_POINT'] = $end;
	}
	
	for ($i=0 ; $i<count($sections) ; $i++) {
		for ($j=0 ; $j<count($projects) ; $j++) {
			$beg = intval($sections[$i]['BEG_REF_MARKER_NBR']) + floatval($sections[$i]['BEG_REF_MARKER_DISP']);
			$end = intval($sections[$i]['END_REF_MARKER_NBR']) + floatval($sections[$i]['END_REF_MARKER_DISP']);
			if (substr($sections[$i]['SIGNED_HIGHWAY_RDBD_ID'], 0, -2) == $projects[$j]['NEW_HIGHWAY_NUMBER']) {
				if (($beg <= $projects[$j]['BEG_POINT'] && $projects[$j]['END_POINT'] <= $end) ||
					($projects[$j]['BEG_POINT'] <= $beg &&  $end <= $projects[$j]['END_POINT']) ||
					($projects[$j]['BEG_POINT'] <= $beg && $projects[$j]['END_POINT'] <= $end) ||
					($beg <= $projects[$j]['BEG_POINT'] && $end <= $projects[$j]['END_POINT'])) {

					// need to identify PRIOR_TREATMENT
					$sections[$i]['LAST_TREATMENT'] = $projects[$j]['PROJ_CLASS'];
					$sections[$i]['YEAR_OF_PRIOR_TREATMENT'] = $projects[$j]['FISCAL_YEAR'];
					break;
				}
			}
		}
	}
	// update
	// update database
	//09/23/2015 only update base year, k == 7377
	$query = "SELECT FISCAL_YEAR, RATING_CYCLE_CODE, SIGNED_HIGHWAY_RDBD_ID, DISTRESS_SCORE,
	CONDITION_SCORE, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP, NUMBER_THRU_LANES FROM $table WHERE RATING_CYCLE_CODE='P' AND FISCAL_YEAR=$base_year";
	$sections = $database->Query($query);
	//ECHO("<script>console.log( 'ZZZZZZ' );</script>");
	for ($k=0 ; $k<count($sections) ; $k++) {
		$year_of_prior_treatment = $sections[$k]['YEAR_OF_PRIOR_TREATMENT'];
		//$CS = ".$sections[$k]['RATING_CYCLE_CODE'].";
		
			

			//09/23/2015
			$CS = $sections[$k]['CONDITION_SCORE'];

			$query = "SELECT CONDITION_SCORE FROM $table WHERE FISCAL_YEAR='".($base_year-1)."' 
			AND SIGNED_HIGHWAY_RDBD_ID = '".$sections[$k]['SIGNED_HIGHWAY_RDBD_ID']."' AND BEG_REF_MARKER_NBR = '".$sections[$k]['BEG_REF_MARKER_NBR']."'
			AND BEG_REF_MARKER_DISP = '".$sections[$k]['BEG_REF_MARKER_DISP']."' AND RATING_CYCLE_CODE = '".$sections[$k]['RATING_CYCLE_CODE']."'";

			$CS_2 = mysql_query($query);
			$CS_delta = $CS - $CS_2;

			$value = 'HR';
			if($CS_delta > 40){
				$value = 'HR';
			}elseif($CS_delta > 30){
				$value = 'MR';
			}elseif($CS_delta > 15){
				$value = 'LR';
			}elseif($CS_delta > 5){
				$value = 'PM';
			}elseif($sections[$k]['FISCAL_YEAR'] == 1993){
				$value = 'MR';
			}else{
				$value = 'HR';
			}

			$query = "UPDATE $table SET PRIOR_TREATMENT= '$value' WHERE FISCAL_YEAR='".($base_year)."'
			AND SIGNED_HIGHWAY_RDBD_ID = '".$sections[$k]['SIGNED_HIGHWAY_RDBD_ID']."' AND BEG_REF_MARKER_NBR = '".$sections[$k]['BEG_REF_MARKER_NBR']."'
			AND BEG_REF_MARKER_DISP = '".$sections[$k]['BEG_REF_MARKER_DISP']."' AND RATING_CYCLE_CODE = '".$sections[$k]['RATING_CYCLE_CODE']."'";
			mysql_query($query) or die('mysql_error: '.mysql_error());


		if ($year_of_prior_treatment != "") {

			$query = "UPDATE $table SET YEAR_OF_PRIOR_TREATMENT='$year_of_prior_treatment' WHERE FISCAL_YEAR = '".$sections[$k]['FISCAL_YEAR']."' 
			AND SIGNED_HIGHWAY_RDBD_ID = '".$sections[$k]['SIGNED_HIGHWAY_RDBD_ID']."' AND BEG_REF_MARKER_NBR = '".$sections[$k]['BEG_REF_MARKER_NBR']."'
			AND BEG_REF_MARKER_DISP = '".$sections[$k]['BEG_REF_MARKER_DISP']."' AND RATING_CYCLE_CODE = '".$sections[$k]['RATING_CYCLE_CODE']."'";
			mysql_query($query) or die('mysql_error: '.mysql_error());
		}

	}
	
	// this will handle the cases where year_of_prior_treatment is empty (orginal)
	//09/23/2015: only update base year
	$query = "SELECT DISTINCT CONDITION_SCORE, FISCAL_YEAR, BEG_REF_MARKER_DISP, SIGNED_HIGHWAY_RDBD_ID, BEG_REF_MARKER_NBR, RATING_CYCLE_CODE FROM $table WHERE YEAR_OF_PRIOR_TREATMENT IS NULL AND FISCAL_YEAR=$base_year;";
	$base = $database->Query($query);
	for ($k=0 ; $k<count($base) ; $k++) {


							//09/23/2015
		$CS = $base[$k]['CONDITION_SCORE'];

		$query = "SELECT CONDITION_SCORE FROM $table WHERE FISCAL_YEAR = '".$base[$k]['FISCAL_YEAR']." - 1' 
		AND SIGNED_HIGHWAY_RDBD_ID = '".$base[$k]['SIGNED_HIGHWAY_RDBD_ID']."' AND BEG_REF_MARKER_NBR = '".$base[$k]['BEG_REF_MARKER_NBR']."'
		AND BEG_REF_MARKER_DISP = '".$base[$k]['BEG_REF_MARKER_DISP']."' AND RATING_CYCLE_CODE = '".$base[$k]['RATING_CYCLE_CODE']."'";

		$CS_2 = mysql_query($query);
		$CS_delta = $CS - $CS_2;

		$value = 'HR';
		if($CS_delta > 40){
			$value = 'HR';
		}elseif($CS_delta > 30){
			$value = 'MR';
		}elseif($CS_delta > 15){
			$value = 'LR';
		}elseif($CS_delta > 5){
			$value = 'PM';
		}elseif($base[$k]['FISCAL_YEAR'] == 1993){
			$value = 'MR';
		}else{
			$value = 'HR';
		}

		$query = "UPDATE $table SET PRIOR_TREATMENT= '$value' WHERE FISCAL_YEAR = '$base_year'
		AND SIGNED_HIGHWAY_RDBD_ID = '".$base[$k]['SIGNED_HIGHWAY_RDBD_ID']."' AND BEG_REF_MARKER_NBR = '".$base[$k]['BEG_REF_MARKER_NBR']."'
		AND BEG_REF_MARKER_DISP = '".$base[$k]['BEG_REF_MARKER_DISP']."' AND RATING_CYCLE_CODE = '".$base[$k]['RATING_CYCLE_CODE']."'";
		mysql_query($query) or die('mysql_error: '.mysql_error());


		$query = "UPDATE $table SET YEAR_OF_PRIOR_TREATMENT=(SELECT * FROM (SELECT FISCAL_YEAR FROM $table WHERE SIGNED_HIGHWAY_RDBD_ID='".$base[$k]['SIGNED_HIGHWAY_RDBD_ID']."' ORDER BY FISCAL_YEAR ASC LIMIT 1) AS T) WHERE YEAR_OF_PRIOR_TREATMENT IS NULL AND FISCAL_YEAR=$base_year AND SIGNED_HIGHWAY_RDBD_ID='".$base[$k]['SIGNED_HIGHWAY_RDBD_ID']."';";
		mysql_query($query) or die('mysql_error: '.mysql_error());
	}
	


	/**
	 * add ADJ_CONDITION_SCORE, ADJ_DISTRESS_SCORE, ADJ_RIDE_SCORE column if not exist
	 */
	$session->updateprogress(60, 'Update ADJ_CONDITION_SCORE, ADJ_DISTRESS_SCORE and ADJ_RIDE_SCORE Columns...');
	$query = "SELECT FISCAL_YEAR, RATING_CYCLE_CODE, SIGNED_HIGHWAY_RDBD_ID, PRIOR_TREATMENT, YEAR_OF_PRIOR_TREATMENT, " 
	."CONDITION_SCORE, DISTRESS_SCORE, RIDE_SCORE, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, "
	."END_REF_MARKER_NBR, END_REF_MARKER_DISP FROM $table WHERE FISCAL_YEAR='".$base_year."' AND RATING_CYCLE_CODE='P'";
	//." AND PVMNT_TYPE_BROAD_CODE = 'A'";
		
	$sections = $database->Query($query);
	$adj_sections = $sections;
	$n_sections = count($sections);
	
	// for each section
	for ($i = 0; $i < $n_sections; $i++) {
		
		// neighbor's avg value pre-calculation
		$rear_neighbor_cs = -1;
		$front_neighbor_cs = -1;
		$rear_neighbor_ds = -1;
		$front_neighbor_ds = -1;
		$rear_neighbor_ride = -1;
		$front_neighbor_ride = -1;
		
		// does rear section belong to the road section?
		if ($sections[$i]['SIGNED_HIGHWAY_RDBD_ID'] == $sections[$i-1]['SIGNED_HIGHWAY_RDBD_ID'] &&
				$sections[$i]['BEG_REF_MARKER_NBR'] == $sections[$i-1]['END_REF_MARKER_NBR']/* &&
				$sections[$i]['BEG_REF_MARKER_DISP'] == $sections[$i-1]['END_REF_MARKER_DISP']*/) {
			$rear_neighbor_cs = $sections[$i-1]['CONDITION_SCORE'];
			$rear_neighbor_ds = $sections[$i-1]['DISTRESS_SCORE'];
			$rear_neighbor_ride = $sections[$i-1]['RIDE_SCORE'];
		}
		
		if ($sections[$i+1]['SIGNED_HIGHWAY_RDBD_ID'] == $sections[$i]['SIGNED_HIGHWAY_RDBD_ID'] &&
				$sections[$i+1]['BEG_REF_MARKER_NBR'] == $sections[$i]['END_REF_MARKER_NBR']/* &&
				$sections[$i+1]['BEG_REF_MARKER_DISP'] == $sections[$i]['END_REF_MARKER_DISP']*/) {
			$front_neighbor_cs = $sections[$i+1]['CONDITION_SCORE'];
			$front_neighbor_ds = $sections[$i+1]['DISTRESS_SCORE'];
			$front_neighbor_ride = $sections[$i+1]['RIDE_SCORE'];
		}
		
		$avg_cs = 0;
		$avg_ds = 0;
		$avg_ride = 0;
		
		// load previous year's data
		$query = "SELECT DISTRESS_SCORE, CONDITION_SCORE, RIDE_SCORE FROM $table WHERE FISCAL_YEAR='".($base_year-1)."' AND "
		."RATING_CYCLE_CODE='".$sections[$i]['RATING_CYCLE_CODE']."' AND SIGNED_HIGHWAY_RDBD_ID='".$sections[$i]['SIGNED_HIGHWAY_RDBD_ID']."'"
		." AND BEG_REF_MARKER_NBR='".$sections[$i]['BEG_REF_MARKER_NBR']."' AND BEG_REF_MARKER_DISP=".$sections[$i]['BEG_REF_MARKER_DISP'];
		
		$result = mysql_query($query) or die("SQL Error 1: ".mysql_error());
		$r = mysql_fetch_row($result);
		
		// condition check
		$avg_cs += ($rear_neighbor_cs!=-1?$rear_neighbor_cs:0) + ($front_neighbor_cs!=-1?$front_neighbor_cs:0);
		if ($rear_neighbor_cs!=-1 && $front_neighbor_cs!= -1 && $rear_neighbor_cs!=0 && $front_neighbor_cs!=0)
			$avg_cs = round($avg_cs/2);
		else if (($rear_neighbor_cs == -1 || $rear_neighbor_cs == 0) && ($front_neighbor_cs==-1 || $front_neighbor_cs==0))
			$avg_cs = $r[1];
			
		$avg_ds += ($rear_neighbor_ds!=-1?$rear_neighbor_ds:0) + ($front_neighbor_ds!=-1?$front_neighbor_ds:0);
		if ($rear_neighbor_ds!=-1 && $front_neighbor_ds!= -1 && $rear_neighbor_ds!=0 && $front_neighbor_ds!= 0)
			$avg_ds = round($avg_ds/2);
		else if (($rear_neighbor_ds == -1 || $rear_neighbor_ds == 0) && ($front_neighbor_ds==-1 || $front_neighbor_ds==0))
			$avg_ds = $r[0]; 
		
		$avg_ride += ($rear_neighbor_ride!=-1?$rear_neighbor_ride:0) + ($front_neighbor_ride!=-1?$front_neighbor_ride:0);
		if ($rear_neighbor_ride!=-1 && $front_neighbor_ride!= -1 && $rear_neighbor_ride!=0 && $front_neighbor_ride!= 0)
			$avg_ride = $avg_ride/2;
		else if (($rear_neighbor_ride == -1 || $rear_neighbor_ride == 0) && ($front_neighbor_ride==-1 || $front_neighbor_ride==0))
			$avg_ride = $r[2];

		// avg calculation is done
		
		// suspicious sections
		if ($sections[$i]['CONDITION_SCORE'] < $min_score) {
			// is zero condition score?			
			if ($sections[$i]['CONDITION_SCORE'] == 0) {
				// there is construction
				if ($sections[$i]['PRIOR_TREATMENT'] != '' && $sections[$i]['YEAR_OF_PRIOR_TREATMENT'] != '') {
					// under M&R (Reset to 100)
					if ($base_year-2 <= $sections[$i]['YEAR_OF_PRIOR_TREATMENT'] &&
						$sections[$i]['YEAR_OF_PRIOR_TREATMENT'] <= $base_year) {
						$adj_sections[$i]['CONDITION_SCORE'] = $reset_score;	
						$adj_sections[$i]['DISTRESS_SCORE'] = $reset_score;
						$adj_sections[$i]['RIDE_SCORE'] = $reset_ride;
					}
					// neighbors are under M&R (Copy from previous)
					else if ($rear_neighbor_cs == 0 ||
								$front_neighbor_cs == 0) { 	
						$query = "SELECT DISTRESS_SCORE, CONDITION_SCORE, RIDE_SCORE FROM $table WHERE FISCAL_YEAR='".($base_year-1)."' AND "
						."RATING_CYCLE_CODE='".$sections[$i]['RATING_CYCLE_CODE']."' AND SIGNED_HIGHWAY_RDBD_ID='".$sections[$i]['SIGNED_HIGHWAY_RDBD_ID']."'"
						." AND BEG_REF_MARKER_NBR='".$sections[$i]['BEG_REF_MARKER_NBR']."' AND BEG_REF_MARKER_DISP=".$sections[$i]['BEG_REF_MARKER_DISP'];
						
						$result = mysql_query($query) or die("SQL Error 1: ".mysql_error());
						$r = mysql_fetch_row($result);
						
						$adj_sections[$i]['DISTRESS_SCORE'] = $r[0];
						$adj_sections[$i]['CONDITION_SCORE'] = $r[1];
						$adj_sections[$i]['RIDE_SCORE'] = $r[2];
					}
					// isolated	(average from neighbors)
					else {
						$adj_sections[$i]['CONDITION_SCORE'] = $avg_cs;
						$adj_sections[$i]['DISTRESS_SCORE'] = $avg_ds;
						$adj_sections[$i]['RIDE_SCORE'] = $avg_ride;
					}						
				}
				// no treatment (average from neighbors)
				else {
					$adj_sections[$i]['CONDITION_SCORE'] = $avg_cs;
					$adj_sections[$i]['DISTRESS_SCORE'] = $avg_ds;
					$adj_sections[$i]['RIDE_SCORE'] = $avg_ride;
				}
			}
			// non-zero condition score (errorneous) : average from neighbors case
			else {
				$adj_sections[$i]['CONDITION_SCORE'] = $avg_cs;
				$adj_sections[$i]['DISTRESS_SCORE'] = $avg_ds;
				$adj_sections[$i]['RIDE_SCORE'] = $avg_ride;
			}	

			// final layer
			$adj_sections[$i]['DISTRESS_SCORE'] = ($adj_sections[$i]['DISTRESS_SCORE']<$min_score)?100:$adj_sections[$i]['DISTRESS_SCORE'];
			
			if ($adj_sections[$i]['CONDITION_SCORE'] < $min_score || $adj_sections[$i]['CONDITION_SCORE'] > $adj_sections[$i]['DISTRESS_SCORE'])
				$adj_sections[$i]['CONDITION_SCORE'] = round(0.90 * $adj_sections[$i]['DISTRESS_SCORE']);
			
			if ($adj_sections[$i]['RIDE_SCORE'] == 0) {
				if ($adj_sections[$i]['CONDITION_SCORE']/$adj_sections[$i]['DISTRESS_SCORE'] >= 0.5)
					$adj_sections[$i]['RIDE_SCORE'] = 3;
				else
					$adj_sections[$i]['RIDE_SCORE'] = 2;
			}
		}
		// no adjustment
		else {
		}
	}
	
	/**
	 * add ADJ_CONDITION_SCORE column if not exist
	 */
	if (in_array("ADJ_CONDITION_SCORE", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD ADJ_CONDITION_SCORE Smallint(5)") or die(mysql_error());
	}
	$query = "UPDATE $table SET ADJ_CONDITION_SCORE=CONDITION_SCORE";
	mysql_query($query) or die(mysql_error());
	
	/**
	 * add ADJ_DISTRESS_SCORE column if not exist
	 */
	if (in_array("ADJ_DISTRESS_SCORE", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD ADJ_DISTRESS_SCORE Smallint(5)") or die(mysql_error());
	}
	$query = "UPDATE $table SET ADJ_DISTRESS_SCORE=DISTRESS_SCORE";
	mysql_query($query) or die(mysql_error());
	
	/**
	 * add ADJ_RIDE_SCORE column if not exist
	 */	
	if (in_array("ADJ_RIDE_SCORE", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD ADJ_RIDE_SCORE Double(15,5)") or die(mysql_error());
	}
	$query = "UPDATE $table SET ADJ_RIDE_SCORE=RIDE_SCORE";
	mysql_query($query) or die(mysql_error());
	
	// update database
	for ($k=0 ; $k<count($adj_sections) ; $k++) {
		$query = "UPDATE $table SET ADJ_CONDITION_SCORE = '".$adj_sections[$k]['CONDITION_SCORE']."', ADJ_DISTRESS_SCORE = '"
		.$adj_sections[$k]['DISTRESS_SCORE']."', ADJ_RIDE_SCORE = '".$adj_sections[$k]['RIDE_SCORE'].
		"' WHERE FISCAL_YEAR = '".$adj_sections[$k]['FISCAL_YEAR']."' AND SIGNED_HIGHWAY_RDBD_ID = '".$adj_sections[$k]['SIGNED_HIGHWAY_RDBD_ID']."' AND BEG_REF_MARKER_NBR = '".$adj_sections[$k]['BEG_REF_MARKER_NBR']."'
		AND BEG_REF_MARKER_DISP = '".$adj_sections[$k]['BEG_REF_MARKER_DISP']."' AND RATING_CYCLE_CODE = '".$adj_sections[$k]['RATING_CYCLE_CODE']."'";
		mysql_query($query) or die('mysql_error: '.mysql_error());
	}
	
	/**
	 * calculate rate of deterioration
	 */
	$session->updateprogress(75, 'Update RATE_OF_DETERIORATION Column...');
	if (in_array("RATE_OF_DETERIORATION", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD RATE_OF_DETERIORATION double(15,5)") or die(mysql_error());
	}
	
	$howmanyyears = 3;
	$query = "SELECT SIGNED_HIGHWAY_RDBD_ID, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP FROM $table WHERE FISCAL_YEAR=$base_year AND RATING_CYCLE_CODE='P'";
	$base = $database->Query($query);
	for ($j=0 ; $j<count($base) ; $j++) {
		$signed_highway_rdbd_id = $base[$j]['SIGNED_HIGHWAY_RDBD_ID'];
		$beg_ref_marker_nbr = $base[$j]['BEG_REF_MARKER_NBR'];
		$beg_ref_marker_disp = $base[$j]['BEG_REF_MARKER_DISP'];
		for ($i=0 ; $i<$howmanyyears ; $i++) {
			$cur_year = $base_year-$i;
			$prev_year = $base_year-$i-1;
			$cur = $database->Query("SELECT ADJ_CONDITION_SCORE FROM $table WHERE FISCAL_YEAR=$cur_year AND SIGNED_HIGHWAY_RDBD_ID='$signed_highway_rdbd_id' AND BEG_REF_MARKER_NBR='$beg_ref_marker_nbr' AND BEG_REF_MARKER_DISP=$beg_ref_marker_disp AND RATING_CYCLE_CODE='P'");
			$prev = $database->Query("SELECT ADJ_CONDITION_SCORE FROM $table WHERE FISCAL_YEAR=$prev_year AND SIGNED_HIGHWAY_RDBD_ID='$signed_highway_rdbd_id' AND BEG_REF_MARKER_NBR='$beg_ref_marker_nbr' AND BEG_REF_MARKER_DISP=$beg_ref_marker_disp AND RATING_CYCLE_CODE='P'");
			if (count($prev)>0 && count($cur)>0) {
				$drop[$i] = $prev[0]['ADJ_CONDITION_SCORE']-$cur[0]['ADJ_CONDITION_SCORE'];
				$drop[$i] = $drop[$i]>=0?$drop[$i]:'';
			}
			else {
				$drop[$i] = '';
			}
		}
		$sum = 0;
		for ($i=0 ; $i<$howmanyyears ; $i++) {
			if (is_numeric($drop[$i]))
				$sum += $drop[$i];
			else {
				break;
			}
		}
		$rate_of_deterioration = $i==0?0:$sum/$i;
		// update table
		$query = "UPDATE $table SET RATE_OF_DETERIORATION = $rate_of_deterioration WHERE FISCAL_YEAR=$base_year AND SIGNED_HIGHWAY_RDBD_ID='$signed_highway_rdbd_id' AND BEG_REF_MARKER_NBR='$beg_ref_marker_nbr' AND BEG_REF_MARKER_DISP=$beg_ref_marker_disp AND RATING_CYCLE_CODE='P'";
	
		mysql_query($query) or die(mysql_error());
	}
	
	unset($drop);
	
	/**
	 * add DROP_IN_DISTRESS column if not exist
	 */
	$session->updateprogress(90, 'Update DROP_IN_DISTRESS Column...');
	if (in_array("DROP_IN_DISTRESS", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD DROP_IN_DISTRESS Smallint(5)") or die(mysql_error());
	}
	$query = "SELECT ADJ_DISTRESS_SCORE, SIGNED_HIGHWAY_RDBD_ID, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP FROM $table WHERE FISCAL_YEAR=$base_year AND RATING_CYCLE_CODE='P'";
	$base = $database->Query($query);
	for ($j=0 ; $j<count($base) ; $j++) {
		$signed_highway_rdbd_id = $base[$j]['SIGNED_HIGHWAY_RDBD_ID'];
		$beg_ref_marker_nbr = $base[$j]['BEG_REF_MARKER_NBR'];
		$beg_ref_marker_disp = $base[$j]['BEG_REF_MARKER_DISP'];
		$prev_year = $base_year-1;
		$prev = $database->Query("SELECT ADJ_DISTRESS_SCORE FROM $table WHERE FISCAL_YEAR=$prev_year AND SIGNED_HIGHWAY_RDBD_ID='$signed_highway_rdbd_id' AND BEG_REF_MARKER_NBR='$beg_ref_marker_nbr' AND BEG_REF_MARKER_DISP=$beg_ref_marker_disp AND RATING_CYCLE_CODE='P'");
		if (count($prev)>0)
			$drop = $base[$j]['ADJ_DISTRESS_SCORE']-$prev[0]['ADJ_DISTRESS_SCORE'];
		else
			$drop = 0;
		// update table
		$query = "UPDATE $table SET DROP_IN_DISTRESS = $drop WHERE FISCAL_YEAR=$base_year AND SIGNED_HIGHWAY_RDBD_ID='$signed_highway_rdbd_id' AND BEG_REF_MARKER_NBR='$beg_ref_marker_nbr' AND BEG_REF_MARKER_DISP=$beg_ref_marker_disp AND RATING_CYCLE_CODE='P'";
	
		mysql_query($query) or die(mysql_error());
	}
	
	/**
	 * add PROB_STRUCTURAL_WEAKNESS column if not exist
	 */
	$session->updateprogress(95, 'Update PROB_STRUCTURAL_WEAKNESS Column...');
	if (in_array("PROB_STRUCTURAL_WEAKNESS", $field_array) == false) {
		mysql_query("ALTER TABLE $table ADD PROB_STRUCTURAL_WEAKNESS Double(15,5)") or die(mysql_error());
	}
	$query = "SELECT DROP_IN_DISTRESS, ADJ_DISTRESS_SCORE, SIGNED_HIGHWAY_RDBD_ID, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP FROM $table WHERE FISCAL_YEAR=$base_year AND RATING_CYCLE_CODE='P'";
	$base = $database->Query($query);
	for ($j=0 ; $j<count($base) ; $j++) {
		$signed_highway_rdbd_id = $base[$j]['SIGNED_HIGHWAY_RDBD_ID'];
		$beg_ref_marker_nbr = $base[$j]['BEG_REF_MARKER_NBR'];
		$beg_ref_marker_disp = $base[$j]['BEG_REF_MARKER_DISP'];
		$drop = $base[$j]['DROP_IN_DISTRESS'];
		$distress = $base[$j]['ADJ_DISTRESS_SCORE'];
		$psw = 0;
		if ($distress<70) {
			if ($drop<5 and $drop>=0)
				$psw = 55;
			else if ($drop>=5 and $drop<=10)
				$psw = 67;
			else if ($drop>10)
				$psw = 82;
		}
		else {
			if ($drop<5 and $drop>=0)
				$psw = 32;
			else if ($drop>=5 and $drop<=10)
				$psw = 40;
			else if ($drop>10)
				$psw = 68;
		}
		// update table
		$query = "UPDATE $table SET PROB_STRUCTURAL_WEAKNESS = $psw WHERE FISCAL_YEAR=$base_year AND SIGNED_HIGHWAY_RDBD_ID='$signed_highway_rdbd_id' AND BEG_REF_MARKER_NBR='$beg_ref_marker_nbr' AND BEG_REF_MARKER_DISP=$beg_ref_marker_disp AND RATING_CYCLE_CODE='P'";
		mysql_query($query) or die(mysql_error());
	}


	//update CURRENT_18KIP_MEAS column, replace 0 data with previous year's data
	//07/27/2015
	$session->updateprogress(99, 'Replace 0s in CURRENT_18KIP_MEAS Column');
	$query = "UPDATE $table AS a, $table AS b Set b.CURRENT_18KIP_MEAS = a.CURRENT_18KIP_MEAS
	WHERE b.CURRENT_18KIP_MEAS = 0 AND b.FISCAL_YEAR = $base_year and a.FISCAL_YEAR = b.FISCAL_YEAR - 1 AND a.SIGNED_HIGHWAY_RDBD_ID = b.SIGNED_HIGHWAY_RDBD_ID
	AND a.BEG_REF_MARKER_NBR = b.BEG_REF_MARKER_NBR AND a.BEG_REF_MARKER_DISP = b.BEG_REF_MARKER_DISP
	AND a.RATING_CYCLE_CODE = b.RATING_CYCLE_CODE";
	mysql_query($query) or die(mysql_error());
	
	
	//sleep(10);
	$session->updateprogress(100, 'Finishing...');
?>