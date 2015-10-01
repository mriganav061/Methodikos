<?php
	include_once '../phps/session.php';
	global $session;
	global $database;

	ini_set("memory_limit", "2000M");

	
	$baseyear = $_SESSION['baseyear'];
	$district = $_SESSION['district'];
	$pmistable = $_SESSION['pmistable'];
	// 4 years analysis
	$howmanyyears = 4; 
	// organize parameters
	$segmnrtriggervalue = $_POST['params']['segmnrtriggervalue'];
	$segmnrtriggerparam = $_POST['params']['segmnrtriggerparam'];
	$segminlen = $_POST['params']['segminlen'];
	$segmaxlen = $_POST['params']['segmaxlen'];
	$pmrsincreaseby = $_POST['params']['pmrsincreaseby'];
	$pmdsresetto = $_POST['params']['pmdsresetto'];
	$lrrsincreaseby = $_POST['params']['lrrsincreaseby'];
	$lrdsresetto = $_POST['params']['lrdsresetto'];
	$mrrsresetto = $_POST['params']['mrrsresetto'];
	$mrdsresetto = $_POST['params']['mrdsresetto'];
	$hrrsresetto = $_POST['params']['hrrsresetto'];
	$hrdsresetto = $_POST['params']['hrdsresetto'];
	$cscoeffpm = isset($_POST['params']['cscoeffpm'])?json_decode(stripslashes($_POST['params']['cscoeffpm']),true):null;
	$cscoefflr = isset($_POST['params']['cscoefflr'])?json_decode(stripslashes($_POST['params']['cscoefflr']),true):null;
	$cscoeffmr = isset($_POST['params']['cscoeffmr'])?json_decode(stripslashes($_POST['params']['cscoeffmr']),true):null;
	$cscoeffhr = isset($_POST['params']['cscoeffhr'])?json_decode(stripslashes($_POST['params']['cscoeffhr']),true):null;
	$dscoeffpm = isset($_POST['params']['dscoeffpm'])?json_decode(stripslashes($_POST['params']['dscoeffpm']),true):null;
	$dscoefflr = isset($_POST['params']['dscoefflr'])?json_decode(stripslashes($_POST['params']['dscoefflr']),true):null;
	$dscoeffmr = isset($_POST['params']['dscoeffmr'])?json_decode(stripslashes($_POST['params']['dscoeffmr']),true):null;
	$dscoeffhr = isset($_POST['params']['dscoeffhr'])?json_decode(stripslashes($_POST['params']['dscoeffhr']),true):null;
	$ridecoeff = isset($_POST['params']['ridecoeff'])?json_decode(stripslashes($_POST['params']['ridecoeff']),true):null;
	$unitcost = isset($_POST['params']['unitcost'])?json_decode(stripslashes($_POST['params']['unitcost']),true):null;
	$benefitcsthreshold = $_POST['params']['benefitcsthreshold'];
	$mnrtriggervalue = $_POST['params']['mnrtriggervalue'];
	$mnrtriggerparam =  $_POST['params']['mnrtriggerparam'];
	$pmviabilityvalue = $_POST['params']['pmviabilityvalue'];
	$lrviabilityvalue = $_POST['params']['lrviabilityvalue'];
	$discountrate = $_POST['params']['discountrate']/100;
	$wtpcc = $_POST['params']['wtpcc'];
	$wtctv = $_POST['params']['wtctv'];
	$wtic = $_POST['params']['wtic'];
	$wtltpb = $_POST['params']['wtltpb'];
	$wtlcc = $_POST['params']['wtlcc'];
	$wtcsds = $_POST['params']['wtcsds'];
	$wtride = $_POST['params']['wtride'];
	$wtrod = $_POST['params']['wtrod'];
	$wtskid = $_POST['params']['wtskid'];
	$wtsci = $_POST['params']['wtsci'];
	$wtva = $_POST['params']['wtva'];
	$wtaadt = $_POST['params']['wtaadt'];
	$wttaadt = $_POST['params']['wttaadt'];
	$currentyear = intval($_POST['params']['currentyear']);
	$aadtgrowthrate = $_POST['params']['aadtgrowthrate'];

	// xdebug_break();
	$seg_agg_table_name = "segmented_pmis_aggregated_".$session->user_id;
	$seg_table_name = "segmented_pmis_".$session->user_id;
	$seg_method;
	if ($database->QueryCount("show tables like '1_$seg_agg_table_name'")>=1) {
		$seg_agg_table_name = '1_'.$seg_agg_table_name;
		$seg_method = 1;
	}		
	else if ($database->QueryCount("show tables like '2_$seg_agg_table_name'")>=1) {
		$seg_agg_table_name = '2_'.$seg_agg_table_name;
		$query = "DELETE FROM $seg_agg_table_name WHERE SEGMENT_ID=''";
		mysql_query($query) or die(mysql_error());
		// $database->Query($query);
		$seg_method = 2;

		// xdebug_break();
		// add forced section to segment
		if ($database->QueryCount("show tables like '2_$seg_table_name'")>=1) {
			$seg_table_name = '2_'.$seg_table_name;
			$nn = $database->Query("SELECT * FROM $seg_table_name WHERE SEGMENT_ID='' AND FORCED IS NOT NULL");
			$number_nn = count($nn);
			if ($number_nn>0) {
				$id_track = $nn[0]['ID'];
				$forced_track = $nn[0]['FORCED'];
				$highway_track = $nn[0]['SIGNED_HIGHWAY_RDBD_ID'];
				for ($i=0 ; $i<$number_nn ; $i++) {
					if ($i == $number_nn-1 || $id_track != $nn[$i]['ID'] || $forced_track != $nn[$i]['FORCED']
						|| $highway_track != $nn[$i]['SIGNED_HIGHWAY_RDBD_ID']) {
						if (isset($beg_id)) {
							if ($i == $number_nn-1) {
								// xdebug_break();
								if ($id_track == $nn[$i]['ID']) {
									if ($forced_track != $nn[$i]['FORCED'] ||
										$highway_track != $nn[$i]['SIGNED_HIGHWAY_RDBD_ID']) {
										$end_id = $id_track-1;
										group_aggregate($beg_id, $end_id, $forced_track);
										group_aggregate($nn[$i]['ID'], $nn[$i]['ID'], $nn[$i]['FORCED']);
									}
									else {
										$end_id = $id_track;
										group_aggregate($beg_id, $end_id, $forced_track);
									}
								}
								else
									group_aggregate($nn[$i]['ID'], $nn[$i]['ID'], $nn[$i]['FORCED']);
							}
							else {
								$end_id = $id_track-1;
								group_aggregate($beg_id, $end_id, $forced_track);
							}
							$beg_id = $nn[$i]['ID'];
							$forced_track = $nn[$i]['FORCED'];
							$id_track = $nn[$i]['ID'];
							$highway_track = $nn[$i]['SIGNED_HIGHWAY_RDBD_ID'];
							// if ($i == $number_nn-1)
								// group_aggregate($beg_id, $beg_id, $forced_track);
							// unset($beg_id);
							unset($end_id);
						}
					}
					else if ($id_track == $nn[$i]['ID']) {
						if (!isset($beg_id))
							$beg_id = $id_track;
					}
					
					$id_track++;
				}
			}
		}
		// group to forced segment
	}
	else
		return;		// no segmentation table founded need to inform this error to the user
	
	// get database ready for the analysis
	// database query for segmented sections of based years
	$query = "SELECT * FROM $seg_agg_table_name WHERE FISCAL_YEAR=$baseyear ORDER BY GP_BEG_ID ASC";
	
	// store the segments
	$segments = $database->Query($query);
	$org = $segments;

	// store the sections
	$query = "SELECT ID, CURRENT_18KIP_MEAS, NUMBER_THRU_LANES, PRIOR_TREATMENT, YEAR_OF_PRIOR_TREATMENT, FISCAL_YEAR, RATING_CYCLE_CODE, SIGNED_HIGHWAY_RDBD_ID, ADJ_DISTRESS_SCORE,
		ADJ_CONDITION_SCORE, ADJ_RIDE_SCORE, SECT_LENGTH, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP, PVMNT_TYPE_DTL_RD_LIFE_CODE,
		AADT, TRUCK_AADT_PCT, DISTRICT_NAME, ZONE_NUMBER, SPEED_LIMIT_MAX, RATE_OF_DETERIORATION  
		FROM $pmistable WHERE FISCAL_YEAR=$baseyear AND RATING_CYCLE_CODE='P' AND PVMNT_TYPE_BROAD_CODE = 'A'";
		
	// store the sections
	$sections = $database->Query($query);

	$query = "SELECT sum(SECT_LENGTH*NUMBER_THRU_LANES) AS TOTAL_LANE_MILES FROM $pmistable WHERE FISCAL_YEAR=$baseyear AND RATING_CYCLE_CODE='P' AND PVMNT_TYPE_BROAD_CODE = 'A'";

	$total_lane_miles = $database->Query($query);
	$total_lane_miles = $total_lane_miles[0]['TOTAL_LANE_MILES'];
	
	$output = array();
	$begin_year = $currentyear;

	// xdebug_break();

	// analysis loop
	include_once 'grid/segmentation.php';
	include_once 'module3.php';
	include_once 'module4.php';
	for ($i=0 ; $i<$howmanyyears ; $i++) {
		$session->updateprogress(25*($i+1), "Analyzing for Year ".$currentyear."...");
		// xdebug_break();
		if ($seg_method == 2 && $i != 0)
			$segments = $segmentation->do_segmentation_method2($sections, $segmnrtriggervalue, $segmnrtriggerparam, $segminlen, $segmaxlen);
		
		$segments = $module3->do_analysis($segments, $cscoeffpm, $cscoefflr, $cscoeffmr, $cscoeffhr, $ridecoeff, $benefitcsthreshold, $unitcost, $discountrate,	$mnrtriggerparam, $mnrtriggervalue, $pmviabilityvalue, $lrviabilityvalue, $wtpcc, $wtctv, $wtic, $wtltpb, $wtlcc, $wtcsds, $wtride, $wtrod, $wtskid, $wtsci, $wtva, $wtaadt, $wttaadt, $pmrsincreaseby, $pmdsresetto, $lrrsincreaseby, $lrdsresetto, $mrrsresetto, $mrdsresetto, $hrrsresetto, $hrdsresetto, $seg_agg_table_name, $i);
		
		$budget = $_POST['params']['year'.($i+1).'budget'];
		
		if ($seg_method == 1) {
			$segments = $module4->do_analysis(null, $segments, $cscoeffpm, $cscoefflr, $cscoeffmr, $cscoeffhr, $dscoeffpm, $dscoefflr, $dscoeffmr, $dscoeffhr, $ridecoeff, $benefitcsthreshold, $unitcost, $discountrate,
				$mnrtriggerparam, $mnrtriggervalue, $budget, $currentyear, $aadtgrowthrate, $total_lane_miles, $seg_method,
				$pmrsincreaseby, $pmdsresetto, $lrrsincreaseby, $lrdsresetto, $mrrsresetto, $mrdsresetto, $hrrsresetto, $hrdsresetto, $i+1);
			$output[$i]['OUTPUT_STAT'] = $segments['OUTPUT_STAT'];
			$output[$i]['PROJECT_LIST'] = $segments['PROJECT_LIST'];
			$output[$i]['CHART_DATA'] = $segments['CHART_DATA'];
			unset($segments['CHART_DATA']);
			unset($segments['OUTPUT_STAT']);
			unset($segments['PROJECT_LIST']);
		
		}
		else {
			// xdebug_break();
			$sections = $module4->do_analysis($sections, $segments, $cscoeffpm, $cscoefflr, $cscoeffmr, $cscoeffhr, $dscoeffpm, $dscoefflr, $dscoeffmr, $dscoeffhr, $ridecoeff, $benefitcsthreshold, $unitcost, $discountrate,
				$mnrtriggerparam, $mnrtriggervalue, $budget, $currentyear, $aadtgrowthrate, $total_lane_miles, $seg_method,
				$pmrsincreaseby, $pmdsresetto, $lrrsincreaseby, $lrdsresetto, $mrrsresetto, $mrdsresetto, $hrrsresetto, $hrdsresetto, $i+1);
			$output[$i]['OUTPUT_STAT'] = $sections['OUTPUT_STAT'];
			if ($session->user_id != 1)
			$output[$i]['PROJECT_LIST'] = $sections['PROJECT_LIST'];
			// var_dump($output[$i]['PROJECT_LIST']);
			$output[$i]['CHART_DATA'] = $sections['CHART_DATA'];
			unset($sections['CHART_DATA']);
			unset($sections['OUTPUT_STAT']);
			unset($sections['PROJECT_LIST']);
		}

		$output[$i]['YEAR'] = $currentyear;
		
		$currentyear++;
	}	
	$end_year = $currentyear - 1;

	// add pieces
	$output['ORG_LIST'] = $org;
	$output['TOTAL_LANE_MILES'] = $total_lane_miles;
	$output['TOTAL_SEGMENTS'] = count($segments);
	$output['BEGIN_YEAR'] = $begin_year;
	$output['END_YEAR'] = $end_year;
	$output['DISTRICT'] = ucfirst($district);
	// $return = array_map(‘utf8_encode’, $output);
	// xdebug_break();

	// get data and store in a json array
	ob_start('ob_gzhandler');
	header('Content-Type: application/json');
	echo json_encode($output);
	// echo json_last_error();


	function group_aggregate($beg_id, $end_id, $forced) {
		global $pmistable, $baseyear, $seg_agg_table_name, $database;
		$sections = $database->Query("SELECT * FROM $pmistable WHERE ID>=$beg_id AND ID<=$end_id");
		$num_sections = count($sections);
		$sum_sec_length = 0;
		$sum_wcs = 0.0;
		$sum_wride = 0.0;
		$sum_wesal = 0.0;
		$sum_wds = 0.0;
		$sum_wrd = 0.0;
		$gp_min_cs = 100.0;
		$gp_min_ds = 100.0;
		$gp_min_ride = 4.8;

		// xdebug_break();
		for ($j=0 ; $j<$num_sections ; $j++) {
			if ($sections[$j]['ADJ_CONDITION_SCORE'] <= $gp_min_cs)
				$gp_min_cs = $sections[$j]['ADJ_CONDITION_SCORE'];
			if ($sections[$j]['ADJ_DISTRESS_SCORE'] <= $gp_min_ds)
				$gp_min_ds = $sections[$j]['ADJ_DISTRESS_SCORE'];
			if ($sections[$j]['ADJ_RIDE_SCORE'] <= $gp_min_ride)
				$gp_min_ride = $sections[$j]['ADJ_RIDE_SCORE'];

			$sum_sec_length += $sections[$j]['SECT_LENGTH'];
			$sum_wcs += $sections[$j]['SECT_LENGTH']*$sections[$j]['ADJ_CONDITION_SCORE'];
			$sum_wds += $sections[$j]['SECT_LENGTH']*$sections[$j]['ADJ_DISTRESS_SCORE'];
			$sum_wride += $sections[$j]['SECT_LENGTH']*$sections[$j]['ADJ_RIDE_SCORE'];
			$sum_wesal += $sections[$j]['SECT_LENGTH']*$sections[$j]['CURRENT_18KIP_MEAS'];
			$sum_wrd += $sections[$j]['SECT_LENGTH']*$sections[$j]['RATE_OF_DETERIORATION'];
		}	
		// if ($sum_sec_length == 0)
		// 	xdebug_break();
		$gp_condition_score= $sum_wcs/$sum_sec_length;
		$gp_distress_score= $sum_wds/$sum_sec_length;
		$gp_ride_score= $sum_wride/$sum_sec_length;
		$gp_esal= $sum_wesal/$sum_sec_length;
		$gp_rate_of_deterioration = $sum_wrd/$sum_sec_length;
		
		$sum_secs = 0.0;
		$sum_seds = 0.0;
		$sum_seride = 0.0;
		$sum_seesal = 0.0;
		$sum_aadt = 0.0;
		$sum_taadt = 0.0;
		$sum_speedlimit = 0.0;
		$sum_year = 0.0;
		$sum_num_thru_lanes = 0.0;
		$sum_treatment = 0.0;
		$sum_serd = 0.0;
		
		for ($j=0 ; $j<$num_sections ; $j++) {
			$sum_num_thru_lanes += $sections[$j]['NUMBER_THRU_LANES']*$sections[$j]['SECT_LENGTH'];
			$sum_year += $sections[$j]['YEAR_OF_PRIOR_TREATMENT']*$sections[$j]['SECT_LENGTH']; 
			$sum_speedlimit += $sections[$j]['SPEED_LIMIT_MAX']*$sections[$j]['SECT_LENGTH'];
			$sum_taadt += $sections[$j]['TRUCK_AADT_PCT']*$sections[$j]['SECT_LENGTH'];
			$sum_aadt += $sections[$j]['AADT']*$sections[$j]['SECT_LENGTH'];
			$sum_secs += pow($gp_condition_score-$sections[$j]['ADJ_CONDITION_SCORE'], 2)*$sections[$j]['SECT_LENGTH'];
			$sum_seds += pow($gp_distress_score-$sections[$j]['ADJ_DISTRESS_SCORE'], 2)*$sections[$j]['SECT_LENGTH'];
			$sum_seride += pow($gp_ride_score-$sections[$j]['ADJ_RIDE_SCORE'], 2)*$sections[$j]['SECT_LENGTH'];
			$sum_seesal += pow($gp_esal-$sections[$j]['CURRENT_18KIP_MEAS'], 2)*$sections[$j]['SECT_LENGTH'];
			$sum_serd += pow($gp_rate_of_deterioration-$sections[$j]['RATE_OF_DETERIORATION'], 2)*$sections[$j]['SECT_LENGTH'];
			if ($sections[$j]['PRIOR_TREATMENT'] == "PM")
				$sum_treatment += 1*$sections[$j]['SECT_LENGTH'];
			else if ($sections[$j]['PRIOR_TREATMENT'] == "LR")
				$sum_treatment += 2*$sections[$j]['SECT_LENGTH'];
			else if ($sections[$j]['PRIOR_TREATMENT'] == "MR")
				$sum_treatment += 3*$sections[$j]['SECT_LENGTH'];
			else if ($sections[$j]['PRIOR_TREATMENT'] == "HR" || $sections[$j]['PRIOR_TREATMENT'] == "ORG")
				$sum_treatment += 4*$sections[$j]['SECT_LENGTH'];
		}
		
		if ($num_sections > 1) {
			$gp_std_condition_score = sqrt($sum_secs/((($num_sections-1)*$sum_sec_length)/$num_sections));
			$gp_std_distress_score = sqrt($sum_seds/((($num_sections-1)*$sum_sec_length)/$num_sections));
			$gp_std_ride_score = sqrt($sum_seride/((($num_sections-1)*$sum_sec_length)/$num_sections));
			$gp_std_esal = sqrt($sum_seesal/((($num_sections-1)*$sum_sec_length)/$num_sections));
			$gp_std_rate_of_deterioration = sqrt($sum_serd/((($num_sections-1)*$sum_sec_length)/$num_sections));
		}
		else {
			$gp_std_condition_score = 0;
			$gp_std_distress_score = 0;
			$gp_std_ride_score = 0;
			$gp_std_esal = 0;
			$gp_std_rate_of_deterioration = 0;
		}		
			
		$fiscal_year = $baseyear;
		// xdebug_break();
		$beg_id = $sections[0]['ID'];
		$end_id = $sections[$num_sections-1]['ID'];
		$signed_highway_rdbd_id = $sections[0]['SIGNED_HIGHWAY_RDBD_ID'];
		$beg_ref_marker_nbr = $sections[0]['BEG_REF_MARKER_NBR'];
		$beg_ref_marker_disp = $sections[0]['BEG_REF_MARKER_DISP'];
		$end_ref_marker_nbr = $sections[$num_sections-1]['END_REF_MARKER_NBR'];
		$end_ref_marker_disp = $sections[$num_sections-1]['END_REF_MARKER_DISP'];
		$rating_cycle_code = 'P';
		$gp_length = $sum_sec_length;
		$gp_number_thru_lanes = round($sum_num_thru_lanes/$sum_sec_length);
		$gp_aadt = $sum_aadt/$sum_sec_length;
		$gp_pvmnt_type_broad_code ='A';
		$gp_pvmnt_type_dtl_rd_life_code = $sections[0]['PVMNT_TYPE_DTL_RD_LIFE_CODE'];
		$gp_pvmt_family = get_pvmnt_family($gp_pvmnt_type_dtl_rd_life_code);
		$gp_truck_aadt_pct = $sum_taadt/$sum_sec_length;
		$gp_district_name = $sections[0]['DISTRICT_NAME'];
		$gp_zone_number = $sections[0]['ZONE_NUMBER'];
		$gp_speed_limit_max = round_nearest($sum_speedlimit/$sum_sec_length, 5);
		if (round($sum_treatment/$sum_sec_length) == 1)
				$gp_prior_treatment = "PM";
		else if (round($sum_treatment/$sum_sec_length) == 2)
			$gp_prior_treatment = "LR";
		else if (round($sum_treatment/$sum_sec_length) == 3)
			$gp_prior_treatment = "MR";
		else if (round($sum_treatment/$sum_sec_length) == 4)
			$gp_prior_treatment = "HR";
		$gp_year_of_prior_treatment = round($sum_year/$sum_sec_length);
		$gp_ds_reliab = $gp_distress_score;
		$gp_ds_reliab = $gp_ds_reliab<0?0:$gp_ds_reliab;
		$gp_cs_reliab = $gp_condition_score;
		$gp_cs_reliab = $gp_cs_reliab<0?0:$gp_cs_reliab;
		$gp_ride_reliab = $gp_ride_score;
		$gp_ride_reliab = $gp_ride_reliab<0?0:$gp_ride_reliab;
		$gp_rate_of_deterioration_reliab = $gp_rate_of_deterioration;
		$gp_taadt = round(($gp_aadt/100)*$gp_truck_aadt_pct);
		$gp_ratio_below_pm_viability = 0;
		$gp_ratio_above_pm_viability = 0;
		$gp_ratio_below_lr_viability = 0;
		$gp_ratio_above_lr_viability = 0;
		$segment_id = $sections[0]['SEGMENT_ID'];

		$query = "INSERT INTO `".$seg_agg_table_name."` 
			(
			FISCAL_YEAR,
			SIGNED_HIGHWAY_RDBD_ID,
			BEG_REF_MARKER_NBR,
			BEG_REF_MARKER_DISP,
			END_REF_MARKER_NBR,
			END_REF_MARKER_DISP,
			RATING_CYCLE_CODE,
			GP_CONDITION_SCORE,
			GP_STD_CONDITION_SCORE,
			GP_DISTRESS_SCORE,
			GP_STD_DISTRESS_SCORE,
			GP_RIDE_SCORE,
			GP_STD_RIDE_SCORE,
			GP_ESAL,
			GP_STD_ESAL,
			GP_LENGTH,
			GP_NUMBER_THRU_LANES,
			GP_AADT,
			GP_PVMNT_TYPE_BROAD_CODE,
			GP_PVMNT_TYPE_DTL_RD_LIFE_CODE,
			GP_PVMT_FAMILY,
			GP_TRUCK_AADT_PCT,
			GP_DISTRICT_NAME,
			GP_ZONE_NUMBER,
			GP_SPEED_LIMIT_MAX,
			GP_PRIOR_TREATMENT,
			GP_YEAR_OF_PRIOR_TREATMENT,
			GP_RATE_OF_DETERIORATION,
			GP_STD_RATE_OF_DETERIORATION,
			GP_DS_RELIAB,
			GP_CS_RELIAB,
			GP_RIDE_RELIAB,
			GP_RATE_OF_DETERIORATION_RELIAB,
			GP_TAADT,
			GP_MIN_CS,
			GP_MIN_DS,
			GP_MIN_RIDE,
			GP_RATIO_BELOW_PM_VIABILITY,
			GP_RATIO_BELOW_LR_VIABILITY,
			GP_BEG_ID,
			GP_END_ID,
			GP_FORCED,
			SEGMENT_ID
			) VALUES
			("
			.$fiscal_year.","
			."'".$signed_highway_rdbd_id."',"
			."'".$beg_ref_marker_nbr."',"
			.$beg_ref_marker_disp.","
			."'".$end_ref_marker_nbr."',"
			.$end_ref_marker_disp.","
			."'".$rating_cycle_code."',"
			.$gp_condition_score.","
			.$gp_std_condition_score.","
			.$gp_distress_score.","
			.$gp_std_distress_score.","
			.$gp_ride_score.","
			.$gp_std_ride_score.","
			.$gp_esal.","
			.$gp_std_esal.","
			.$gp_length.","
			.$gp_number_thru_lanes.","
			.$gp_aadt.","
			."'".$gp_pvmnt_type_broad_code."',"
			."'".$gp_pvmnt_type_dtl_rd_life_code."',"
			."'".$gp_pvmt_family."',"
			.$gp_truck_aadt_pct.","
			."'".$gp_district_name."',"
			."'".$gp_zone_number."',"
			.$gp_speed_limit_max.","
			."'".$gp_prior_treatment."',"
			.$gp_year_of_prior_treatment.","
			.$gp_rate_of_deterioration.","
			.$gp_std_rate_of_deterioration.","
			.$gp_ds_reliab.","
			.$gp_cs_reliab.","
			.$gp_ride_reliab.","
			.$gp_rate_of_deterioration_reliab.","
			.$gp_taadt.","
			.$gp_min_cs.","
			.$gp_min_ds.","
			.$gp_min_ride.","
			.$gp_ratio_below_pm_viability.","
			.$gp_ratio_below_lr_viability.","
			.$beg_id.","
			.$end_id.","
			."'".$forced."',"
			."'".$segment_id
			."')";
			mysql_query($query) or die('mysql_error: '.mysql_error());
	}

	function get_pvmnt_family($pvmnt_code) {
		// manual override for pvmnt code 01
		if ($pvmnt_code == '01')
			$pvmnt_code = '05';
		
		if ($pvmnt_code == '04' || $pvmnt_code == '05' || $pvmnt_code == '09')
			return 'A';
		else if($pvmnt_code == '07' || $pvmnt_code == '08')
			return 'B';
		else
			return 'C';
	}

    function round_nearest($no,$near) 
	{ 	
		return round($no/$near)*$near; 
	} 	
?>