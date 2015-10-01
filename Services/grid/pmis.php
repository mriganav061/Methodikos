<?php	
	include_once '../../phps/session.php';
	global $session;
	global $database;
	$district = $_SESSION['district'];
	$baseyear = $_SESSION['baseyear'];
	// xdebug_break();
	switch ($_GET['querytype']) {		
		case 'etc':	
			// xdebug_break();		
			$baseyear = $_SESSION['baseyear'];
			if ($_GET['params']['doseg']) {
				include_once 'segmentation.php';
				if ($_GET['params']['method'] == 1) {
					$segmentation->do_segmentation_method1($_GET['params']['csthreshold'], $_GET['params']['dsthreshold'], 
							$_GET['params']['minseglen'], $_GET['params']['maxseglen'], $_GET['params']['zvalue']);
				}
				else {					
					$segmentation->do_segmentation_method2(null, $_GET['params']['mnrtriggervalue'], 
						$_GET['params']['mnrtriggerparam'], $_GET['params']['minseglen'], $_GET['params']['maxseglen']);
				}
			}
			$table = $_GET['params']['table'];
			if ($_GET['params']['loadseg']) {
				if ($database->QueryCount("show tables like '1_$table'")>=1)
					$table = '1_'.$table;
				else if ($database->QueryCount("show tables like '2_$table'")>=1)
					$table = '2_'.$table;
				else
					break;
			}	
			// xdebug_break();
			$fiscalyears = $database->Query("SELECT DISTINCT FISCAL_YEAR FROM $table WHERE FISCAL_YEAR<=$baseyear ORDER BY FISCAL_YEAR DESC");
			$fullcolumns = $database->Query("SHOW FULL COLUMNS FROM $table");
			$exist = $database->QueryCount("show tables like '$table'");
			$queryyears = "(".stripslashes(isset($_GET['fiscalyears'])?$_GET['fiscalyears']:$baseyear).")";
			$foundRows = $database->Query("SELECT count(*) as `found_rows` FROM $table where FISCAL_YEAR in $queryyears");
			$totalrows = $foundRows[0]['found_rows'];
			$result = array(
				'exist' => $exist>=1?true:false,
				'fullcolumns' => $fullcolumns,
				'fiscalyears' => $fiscalyears,
				'totalrecords' => $totalrows
			);
			break;
			
		case 'cs_chart':
			// include_once '../../phps/database.php';
			// global $database;
			$section = $_GET['section'];
			$result = $database->QueryPMIS("FISCAL_YEAR, CONDITION_SCORE", 
					$district, "", $section['SIGNED_HIGHWAY_RDBD_ID'], 
					$section['BEG_REF_MARKER_NBR'], $section['BEG_REF_MARKER_DISP'], $section['RATING_CYCLE_CODE'], " ORDER BY FISCAL_YEAR ASC");
			break;
			
		case 'grid':
			// include_once '../../phps/session.php';
			// global $session;
			// global $database;
			$params = $_GET['params'];
			$table = $_GET['params']['table'];
			if ($_GET['params']['loadseg']) {
				if ($database->QueryCount("show tables like '1_$table'")>=1)
					$table = '1_'.$table;
				else if ($database->QueryCount("show tables like '2_$table'")>=1)
					$table = '2_'.$table;
			}
			$pagenum = isset($_GET['pagenum'])?$_GET['pagenum']:0;
			$pagesize = isset($_GET['pagesize'])?$_GET['pagesize']:null;
			$start = $pagenum * $pagesize;
			$fiscalyears = $database->Query("SELECT DISTINCT FISCAL_YEAR FROM $table ORDER BY FISCAL_YEAR DESC");
			$fullcolumns = $database->Query("SHOW FULL COLUMNS FROM $table");
			$queryyears = "(".stripslashes(isset($_GET['fiscalyears'])?$_GET['fiscalyears']:$baseyear).")";
			if (isset($params['asseessedonly']))
				$where = "where (VISUAL_ASSESSMENT IS NOT NULL) OR (FORCED IS NOT NULL) OR (SKID_RESIST IS NOT NULL) OR (SCI IS NOT NULL)";
			else
				$where = "where fiscal_year IN $queryyears";
			if (isset($_GET['pagesize']))
				$query = "SELECT SQL_CALC_FOUND_ROWS * FROM $table $where LIMIT $start, $pagesize";
			else
				$query = "SELECT SQL_CALC_FOUND_ROWS * FROM $table $where";
			if (isset($_GET['sortdatafield']))
			{
				$sortfield = $_GET['sortdatafield'];
				$sortorder = $_GET['sortorder'];
				$griddata = $database->Query($query);
				$foundRows = $database->Query("SELECT FOUND_ROWS() AS `found_rows`;");
				$totalrows = $foundRows[0]['found_rows'];
			
				if ($sortfield != NULL)
				{
			
					if ($sortorder == "desc")
					{
						if (isset($_GET['pagesize']))
							$query = "SELECT * FROM $table where fiscal_year IN $queryyears ORDER BY" . " " . $sortfield . " DESC LIMIT $start, $pagesize";
						else
							$query = "SELECT * FROM $table where fiscal_year IN $queryyears ORDER BY" . " " . $sortfield . " DESC";
					}
					else if ($sortorder == "asc")
					{
						if (isset($_GET['pagesize']))
							$query = "SELECT * FROM $table where fiscal_year IN $queryyears ORDER BY" . " " . $sortfield . " ASC LIMIT $start, $pagesize";
						else
							$query = "SELECT * FROM $table where fiscal_year IN $queryyears ORDER BY" . " " . $sortfield . " ASC";
					}
					$griddata = $database->Query($query);
				}
			}
			else
			{
				$griddata = $database->Query($query);
				$foundRows = $database->Query("SELECT FOUND_ROWS() AS `found_rows`;");
				$totalrows = $foundRows[0]['found_rows'];
			}
			$result = array(
					'griddata' => $griddata,
					'fullcolumns' => $fullcolumns,
					'fiscalyears' => $fiscalyears,
					'totalrecords' => $totalrows
			);
			break;
			
		case 'all':
			// include_once 'database.php';
			// global $database;
			$result = $database->QueryPMIS("FISCAL_YEAR, RATING_CYCLE_CODE, SIGNED_HIGHWAY_RDBD_ID, DISTRESS_SCORE,
		CONDITION_SCORE, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP, NUMBER_THRU_LANES", 
			$district, $baseyear, "", "", "", "P", "");
			break;
			
		case 'combo-highway':
			// include_once '../../phps/database.php';
			// global $database;
			$highway = $database->QueryPMIS("DISTINCT SIGNED_HIGHWAY_RDBD_ID",
					$district, $baseyear, "", "", "", "P", "");
			$result = array(
				'comboboxdata' => $highway,
			);
			break;
			
		case 'combo-brm':
			// include_once '../../phps/database.php';
			// global $database;
		    $_GET['SIGNED_HIGHWAY_RDBD_ID']= str_replace(" ", "%", $_GET['SIGNED_HIGHWAY_RDBD_ID']);
			$brm = $database->QueryPMIS("BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP",
					$district, $baseyear, $_GET['SIGNED_HIGHWAY_RDBD_ID'], "", "", "P", "");
			for ($i=0 ; $i<count($brm) ; $i++) { 
				$brm[$i]['BEG_REF_MARKER'] = $brm[$i]['BEG_REF_MARKER_NBR'].'+'.$brm[$i]['BEG_REF_MARKER_DISP'];
				$brm[$i]['BEG_REF_MARKER_VAL'] = $brm[$i]['BEG_REF_MARKER']; //$brm[$i]['BEG_REF_MARKER_NBR']+$brm[$i]['BEG_REF_MARKER_DISP'];
			}
			$result = array(
					'comboboxdata' => $brm,
			);
			break;
			
		case 'combo-erm':
			// include_once '../../phps/database.php';
			// global $database;
			$piece = explode("+", $_GET['brm']);
			$brm = $piece[0]+$piece[1];
			// print $brm;

			$query = "SELECT BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP FROM pmis_condition_summary_$district WHERE"
			." SIGNED_HIGHWAY_RDBD_ID='".$_GET['SIGNED_HIGHWAY_RDBD_ID']."' AND CAST(END_REF_MARKER_NBR AS SIGNED)+END_REF_MARKER_DISP>=".$brm." AND FISCAL_YEAR=$baseyear AND RATING_CYCLE_CODE='P' AND PVMNT_TYPE_BROAD_CODE='A'";
			$result = $database->Query($query);
			
			$temp = array();
			for ($i=0 ; $i<count($result) ; $i++) {
// 				if ($temp[count($temp)-1]['END_REF_MARKER_VAL'] != $result[$i]['BEG_REF_MARKER_NBR']+$result[$i]['BEG_REF_MARKER_DISP']
// 						&& $result[$i]['BEG_REF_MARKER_NBR']+$result[$i]['BEG_REF_MARKER_DISP'] > $_GET['brm'])
// 				array_push($temp, array("END_REF_MARKER"=>$result[$i]['BEG_REF_MARKER_NBR'].'+'.$result[$i]['BEG_REF_MARKER_DISP'],
// 						"END_REF_MARKER_VAL"=>$result[$i]['BEG_REF_MARKER_NBR']+$result[$i]['BEG_REF_MARKER_DISP']));
				if ($temp[count($temp)-1]['END_REF_MARKER_VAL'] != $result[$i]['END_REF_MARKER_NBR']+$result[$i]['END_REF_MARKER_DISP']
						&& $result[$i]['END_REF_MARKER_NBR']+$result[$i]['END_REF_MARKER_DISP'] > $_GET['brm'])
				array_push($temp, array("END_REF_MARKER"=>$result[$i]['END_REF_MARKER_NBR'].'+'.$result[$i]['END_REF_MARKER_DISP'],
						"END_REF_MARKER_VAL"=>$result[$i]['END_REF_MARKER_NBR'].'+'.$result[$i]['END_REF_MARKER_DISP']));
			}
			$result = $temp;
			$result = array(
					'comboboxdata' => $result,
			);
			break;	
			
		case 'seg_agg':
			// xdebug_break();
			// include_once '../../phps/session.php';
			// global $database;
			// global $session;
			if ($database->QueryCount("show tables like '1_segmented_pmis_".$session->user_id."'")>=1)
				$query = "SELECT * FROM 1_segmented_pmis_aggregated_".$session->user_id;
			else if ($database->QueryCount("show tables like '2_segmented_pmis_".$session->user_id."'")>=1)
				$query = "SELECT * FROM 2_segmented_pmis_aggregated_".$session->user_id;

			$result = $database->Query($query);
			break;
			
		case 'seg_table':
			// include_once '../../phps/session.php';
			// global $database;
			// global $session;
			if ($database->QueryCount("show tables like '1_segmented_pmis_".$session->user_id."'")>=1) {
				$result["EXIST"]=true;
				$result["SEG_TYPE"]=1;
			}
			else if ($database->QueryCount("show tables like '2_segmented_pmis_".$session->user_id."'")>=1) {
				$result["EXIST"]=true;
				$result["SEG_TYPE"]=2;
			}
			else
				$result["EXIST"]=false;
			break;
			
		case 'prev_seg_table':
			// include_once 'session.php';
			// global $database;
			// global $session;
			$query = "SELECT FISCAL_YEAR, SIGNED_HIGHWAY_RDBD_ID, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, RATING_CYCLE_CODE, SEGMENT_ID FROM segmented_pmis_".$session->user_id;
			$result = $database->Query($query);
			break;
			
		case 'assessed_table':
			include_once 'session.php';
			global $database;
			global $session;
			
			$result = $database->Query($query);
			break;
		
	}
	
	switch ($_POST['querytype']) {
		case 'assessment':
			// include_once '../../phps/session.php';
			include_once 'segmentation.php';
			$json = $_POST['assessment_rows'];
			$threshold = $_POST['assessment_threshold'];
			// $pmis_table = $district = $_SESSION['district'];
			$json_string = stripslashes($json);
			$data = json_decode($json_string, true);
			$seg_method;
			// global $session;
			// global $database;
			// update database based on $data
			$updated = array();
			if ($database->QueryCount("show tables like '1_segmented_pmis_$session->user_id'")>=1) {
				$table = '1_segmented_pmis_'.$session->user_id;
				$agg_table = '1_segmented_pmis_aggregated_'.$session->user_id;
				$seg_method = 1;
			}
			else if ($database->QueryCount("show tables like '2_segmented_pmis_$session->user_id'")>=1) {
				$table = '2_segmented_pmis_'.$session->user_id;
				$agg_table = '2_segmented_pmis_aggregated_'.$session->user_id;
				$seg_method = 2;
			}
			else
				return;

			for ($i=0 ; $i<count($data) ; $i++) {
				$brm = explode("+", $data[$i]['BRM']);
				$brn = $brm[0];
				$brd = $brm[1];

				$erm = explode("+", $data[$i]['ERM']);
				$ern = $erm[0];
				$erd = $erm[1];

				// xdebug_break();

				// GRAP BEG ID
				$query = "SELECT ID FROM $table WHERE SIGNED_HIGHWAY_RDBD_ID='".$data[$i]['Highway ID']."' AND"
						." BEG_REF_MARKER_NBR='".$brn."' AND BEG_REF_MARKER_DISP=".$brd." AND RATING_CYCLE_CODE='P' AND"
						." FISCAL_YEAR=$baseyear";
				$result = $database->Query($query);
				$us = $result[0]['ID'];

				$query = "SELECT ID FROM $table WHERE SIGNED_HIGHWAY_RDBD_ID='".$data[$i]['Highway ID']."' AND"
						." END_REF_MARKER_NBR='".$ern."' AND END_REF_MARKER_DISP=".$erd." AND RATING_CYCLE_CODE='P' AND"
						." FISCAL_YEAR=$baseyear";
				$result = $database->Query($query);
				$ue = $result[0]['ID'];

				
				$query = "UPDATE $table SET ";
				switch($data[$i]['Type']) {
					case 'Forced Projects':
						$where = "FORCED";
						break;
					case 'Visual':
						$where = "VISUAL_ASSESSMENT";
						break;
					case 'Skid':
						$where = "SKID_RESIST";
						break;
					case 'Structural':
						$where = "SCI";
						break;
				}
				switch($data[$i]['Value']) {
					case 'Inadequate':
						$value = false;
						break;
					case 'Adequate':
						$value = true;
						break;
					default:
						$value = $data[$i]['Value'];
						break;
				}

				// $query .= $where."='".$value."' WHERE SIGNED_HIGHWAY_RDBD_ID='".$data[$i]['Highway ID']."' AND"
				// ." CAST(BEG_REF_MARKER_NBR AS SIGNED)+BEG_REF_MARKER_DISP>=".$data[$i]['BRM']." AND"
				// ." CAST(END_REF_MARKER_NBR AS SIGNED)+END_REF_MARKER_DISP<=".$data[$i]['ERM'];
				$query .= $where."='".$value."' WHERE ID>=$us AND ID<=$ue";
				// print $query;
				mysql_query($query) or die(mysql_error());
				// $query = "SELECT SEGMENT_ID FROM $table  WHERE SIGNED_HIGHWAY_RDBD_ID='".$data[$i]['Highway ID']."' AND"
				// ." CAST(BEG_REF_MARKER_NBR AS SIGNED)+BEG_REF_MARKER_DISP>=".$data[$i]['BRM']." AND"
				// ." CAST(END_REF_MARKER_NBR AS SIGNED)+END_REF_MARKER_DISP<=".$data[$i]['ERM'];
				$query = "SELECT DISTINCT SEGMENT_ID FROM $table WHERE ID>=$us AND ID<=$ue AND SEGMENT_ID<>''";
				$result = $database->Query($query);
				
				for ($j=0 ; $j<count($result) ; $j++) {
					$segment_id = $result[$j]['SEGMENT_ID'];
					// check threshold
					$query = "SELECT SUM(SECT_LENGTH) AS TOTAL FROM $table WHERE SEGMENT_ID='".$segment_id."'";
					$count_result = $database->Query($query);
					$total = $count_result[0]['TOTAL'];
					
					$query = "SELECT SUM(SECT_LENGTH) AS RATED FROM $table WHERE ".$where." IS NOT NULL AND SEGMENT_ID='".$segment_id."'";
					$count_result = $database->Query($query);
					$rated = $count_result[0]['RATED'];
					
					// above the threshold
					if ($rated/$total*100 >= $threshold) {
						// compute weighted
						$query = "SELECT ".$where.", SECT_LENGTH FROM $table WHERE SEGMENT_ID='".$segment_id."'";
						$sections = $database->Query($query);
						if ($where != "FORCED") {
							$sum = 0;
							$sum_length = 0;
							for ($k=0 ; $k<count($sections) ; $k++) {
								$sum_length += $sections[$k]['SECT_LENGTH'];
								if ($sections[$k][$where] == 1)
									$sum += $sections[$k][$where]*$sections[$k]['SECT_LENGTH'];
							}
							$code = round($sum/$sum_length);
							$query = "UPDATE $agg_table SET GP_".$where."='".$code."' WHERE SEGMENT_ID='".$segment_id."'";
							mysql_query($query) or die(mysql_error());
						}
						else {
							$sum = 0;
							$sum_length = 0;
							for ($k=0 ; $k<count($sections) ; $k++) {
								$sum_length += $sections[$k]['SECT_LENGTH'];
								if ($sections[$k][$where] == "PM")
									$sum += 1*$sections[$k]['SECT_LENGTH'];
								else if ($sections[$k][$where] == "LR")
									$sum += 2*$sections[$k]['SECT_LENGTH'];
								else if ($sections[$k][$where] == "MR")
									$sum += 3*$sections[$k]['SECT_LENGTH'];
								else if ($sections[$k][$where] == "HR")
									$sum += 4*$sections[$k]['SECT_LENGTH'];
							}
							// FIXED
							$code = round($sum/$rated);
							if ($code == 1)
								$code = "PM";
							else if ($code == 2)
								$code = "LR";
							else if ($code == 3)
								$code = "MR";
							else if ($code == 4)
								$code = "HR";
							$query = "UPDATE $agg_table SET GP_".$where."='".$code."' WHERE SEGMENT_ID='".$segment_id."'";
							mysql_query($query) or die(mysql_error());
						}						
					}					
				}
			}
			// $result = $database->Query($query);	
			break;
	}
	// get data and store in a json array
	ob_start('ob_gzhandler');
	header('Content-Type: application/json');
	echo json_encode($result);
?>