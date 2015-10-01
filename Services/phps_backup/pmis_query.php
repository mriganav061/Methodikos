<?php
	$district = "bryan";
	switch ($_GET['query_type']) {
		case 'cols':
			include_once 'database.php';
			global $database;
			$result = $database->GetColumns($district);
			break;
			
		case 'cs_chart':
			include_once 'database.php';
			global $database;
			$section = $_GET['section'];
			$result = $database->QueryPMIS("FISCAL_YEAR, CONDITION_SCORE", 
					$district, "", $section['SIGNED_HIGHWAY_RDBD_ID'], 
					$section['BEG_REF_MARKER_NBR'], $section['BEG_REF_MARKER_DISP'], $section['RATING_CYCLE_CODE'], " ORDER BY FISCAL_YEAR ASC");
			break;
			
		case 'grid':
			include_once 'database.php';
			global $database;
			$result = $database->QueryPMIS($_GET['cols'], $district, "(".stripslashes($_GET['years']).")", "", "", "", "", "");
			break;
									
		case 'years':
			include_once 'database.php';
			global $database;
			$result = $database->QueryPMIS("DISTINCT FISCAL_YEAR", $district, "", "", "", "", "", " ORDER BY FISCAL_YEAR DESC");
			break;
			
		case 'all':
			include_once 'database.php';
			global $database;
			$result = $database->QueryPMIS("FISCAL_YEAR, RATING_CYCLE_CODE, SIGNED_HIGHWAY_RDBD_ID, DISTRESS_SCORE,
		CONDITION_SCORE, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP, NUMBER_THRU_LANES", 
			$district, "2011", "", "", "", "P", "");
			break;
			
		case 'seg':
// 			xdebug_break();
			include_once 'session.php';
			include_once 'segmentation.php';
			global $session;
			global $database;
			$segmentation->do_segmentation($_GET['cs_threshold'], $_GET['ds_threshold'], $_GET['min_seg_len'], $_GET['max_seg_len']);
			$query = "SELECT FISCAL_YEAR, SIGNED_HIGHWAY_RDBD_ID, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, RATING_CYCLE_CODE, SEGMENT_ID FROM segmented_pmis_".$session->user_id;
			$result = $database->Query($query);
			break;
			
		case 'highway':
			include_once 'database.php';
			global $database;
			$result = $database->QueryPMIS("DISTINCT SIGNED_HIGHWAY_RDBD_ID",
					$district, "2011", "", "", "", "P", "");
			break;
			
		case 'brm':
			include_once 'database.php';
			global $database;
		    $_GET['highway']= str_replace(" ", "%", $_GET['highway']);
			$result = $database->QueryPMIS("BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP",
					$district, "2011", $_GET['highway'], "", "", "P", "");
			for ($i=0 ; $i<count($result) ; $i++) { 
				$result[$i]['BEG_REF_MARKER'] = $result[$i]['BEG_REF_MARKER_NBR'].'+'.$result[$i]['BEG_REF_MARKER_DISP'];
				$result[$i]['BEG_REF_MARKER_VAL'] = $result[$i]['BEG_REF_MARKER_NBR']+$result[$i]['BEG_REF_MARKER_DISP'];
			}			
			break;
			
		case 'erm':
			include_once 'database.php';
			global $database;
			$query = "SELECT BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP FROM pmis_condition_summary_bryan WHERE"
			." SIGNED_HIGHWAY_RDBD_ID='".$_GET['highway']."' AND CAST(END_REF_MARKER_NBR AS SIGNED)+END_REF_MARKER_DISP>=".$_GET['brm']." AND FISCAL_YEAR=2011 AND RATING_CYCLE_CODE='P'";
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
						"END_REF_MARKER_VAL"=>$result[$i]['END_REF_MARKER_NBR']+$result[$i]['END_REF_MARKER_DISP']));
			}
			$result = $temp;
			break;	
			
		case 'seg_agg':
// 			xdebug_break();
			include_once 'session.php';
			global $database;
			global $session;
			$query = "SELECT * FROM segmented_pmis_aggregated_".$session->user_id;
			$result = $database->Query($query);
			break;
			
		case 'seg_table':
			include_once 'session.php';
			global $database;
			global $session;
			$query = "show tables like 'segmented_pmis_".$session->user_id."'";
			if ($database->Query_Count($query) >= 1)
				$result["EXIST"]=true;
			else
				$result["EXIST"]=false;
			break;
			
		case 'prev_seg_table':
			include_once 'session.php';
			global $database;
			global $session;
			$query = "SELECT FISCAL_YEAR, SIGNED_HIGHWAY_RDBD_ID, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, RATING_CYCLE_CODE, SEGMENT_ID FROM segmented_pmis_".$session->user_id;
			$result = $database->Query($query);
			break;
			
		case 'assessed_table':
			include_once 'session.php';
			global $database;
			global $session;
			$query = "SELECT * FROM segmented_pmis_".$session->user_id." WHERE (VISUAL_ASSESSMENT IS NOT NULL) OR (FORCED IS NOT NULL) OR (SKID_RESIST IS NOT NULL) OR (SCI IS NOT NULL)";
			$result = $database->Query($query);
			break;
		
	}
	
	switch ($_POST['query_type']) {
		case 'assessment':
			include_once 'session.php';
			include_once 'segmentation.php';
			$json = $_POST['assessment_rows'];
			$threshold = $_POST['assessment_threshold'];
			$json_string = stripslashes($json);
			$data = json_decode($json_string, true);
			global $session;
			global $database;
			// update database based on $data
			$updated = array();
			for ($i=0 ; $i<count($data) ; $i++) {
				$query = "UPDATE segmented_pmis_".$session->user_id." SET ";
				switch($data[$i]['type']) {
					case 'Forced':
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
				switch($data[$i]['value']) {
					case 'Inadequate':
						$value = false;
						break;
					case 'Adequate':
						$value = true;
						break;
					default:
						$value = $data[$i]['value'];
						break;
				}
				$query .= $where."=".$value." WHERE SIGNED_HIGHWAY_RDBD_ID='".$data[$i]['highway']."' AND"
				." CAST(BEG_REF_MARKER_NBR AS SIGNED)+BEG_REF_MARKER_DISP>=".$data[$i]['brm']." AND"
				." CAST(END_REF_MARKER_NBR AS SIGNED)+END_REF_MARKER_DISP<=".$data[$i]['erm'];
				$result = $database->Query($query);
				$query = "SELECT SEGMENT_ID FROM segmented_pmis_".$session->user_id."  WHERE SIGNED_HIGHWAY_RDBD_ID='".$data[$i]['highway']."' AND"
				." CAST(BEG_REF_MARKER_NBR AS SIGNED)+BEG_REF_MARKER_DISP>=".$data[$i]['brm']." AND"
				." CAST(END_REF_MARKER_NBR AS SIGNED)+END_REF_MARKER_DISP<=".$data[$i]['erm'];
				$result = $database->Query($query);
				
				for ($j=0 ; $j<count($result) ; $j++) {
					$segment_id = $result[$j]['SEGMENT_ID'];
					// check threshold
					$query = "SELECT SECT_LENGTH AS TOTAL FROM segmented_pmis_".$session->user_id." WHERE SEGMENT_ID='".$segment_id."'";
					$count_result = $database->Query($query);
					$total = $count_result[0]['TOTAL'];
					
					$query = "SELECT SECT_LENGTH AS RATED FROM segmented_pmis_".$session->user_id." WHERE ".$where." IS NOT NULL AND SEGMENT_ID='".$segment_id."'";
					$count_result = $database->Query($query);
					$rated = $count_result[0]['RATED'];
					
					// above the threshold
					xdebug_break();
					if ($rated/$total*100 >= $threshold) {
						// compute weighted
						$query = "SELECT ".$where.", SECT_LENGTH FROM segmented_pmis_".$session->user_id." WHERE SEGMENT_ID='".$segment_id."'";
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
							$query = "UPDATE segmented_pmis_aggregated".$session->user_id." SET ".$where."=".$code." WHERE SEGMENT_ID='".$segment_id;
							$database->Query($query);
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
							$code = round($sum/$sum_length);
							if ($code == 1)
								$code = "PM";
							else if ($code == 2)
								$code = "LR";
							else if ($code == 3)
								$code = "MR";
							else if ($code == 4)
								$code = "HR";
							$query = "UPDATE segmented_pmis_aggregated".$session->user_id." SET ".$where."=".$code." WHERE SEGMENT_ID='".$segment_id;
							$database->Query($query);
						}						
					}					
				}
			}
// 			xdebug_break();
			
			break;
	}
	// get data and store in a json array
	ob_start('ob_gzhandler');
	header('Content-Type: application/json');
	echo "{\"data\":".json_encode($result). "}";	
?>