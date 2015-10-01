<?php

class Segmentation {	
	private $cs_threshold;				// threshold value for condition score
	private $ds_threshold;				// threshold value for distress score
	private $user_id;
	private $esal_threshold;	
 	private $min_seg_len;				// minimum segment length
	private $max_seg_len;				// maximum segment length
	private $sections;					// sections of base year in pmis
	private $cs_based;					// condition score based segmentation result
	private $ds_based;					// distress score based segmentation result
	private $esal_based;
	private $groups = array();			// holds for group of roads divided by highway road bed, pavement type, and continuity
	private $segments = array();
	
	function Segmentation() {
		global $database;
		global $session;
		$this->user_id = $session->user_id;
		// database query for sections of based years
		$query = "SELECT CURRENT_18KIP_MEAS, NUMBER_THRU_LANES, PRIOR_TREATMENT, YEAR_OF_PRIOR_TREATMENT, FISCAL_YEAR, RATING_CYCLE_CODE, SIGNED_HIGHWAY_RDBD_ID, ADJ_DISTRESS_SCORE,
		ADJ_CONDITION_SCORE, ADJ_RIDE_SCORE, SECT_LENGTH, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP, PVMNT_TYPE_DTL_RD_LIFE_CODE,
		AADT, TRUCK_AADT_PCT, DISTRICT_NAME, ZONE_NUMBER, SPEED_LIMIT_MAX 
		FROM pmis_condition_summary_bryan WHERE FISCAL_YEAR='2011' AND RATING_CYCLE_CODE='P' AND PVMNT_TYPE_BROAD_CODE = 'A'";
		
		// store the sections
		$this->sections = $database->Query($query);
		$this->esal_threshold = $database->Query("SELECT avg(current_18kip_meas) as esal_threshold FROM pmis_condition_summary_bryan where fiscal_year=2011 and rating_cycle_code='P' and pvmnt_type_broad_code='A'");
		$this->esal_threshold = $this->esal_threshold[0]['esal_threshold'];
		
		// grouping
		$this->grouping();
		
		// segmentation
// 		$this->do_segmentation(70, 70, 2, 10);
	}
	
	private function grouping() {
		// get sections
		$sections = $this->sections;

		// if the number of sections is zero, we are done
		if (count($sections) == 0)
			return;
		
		// initialize some variables
		$highway = $sections[0]['SIGNED_HIGHWAY_RDBD_ID'];
		$cumLength = $sections[0]['SECT_LENGTH'];
		
		// first section's cumulative length
		$sections[0]['CUM_LENGTH_BEG'] = 0;
		$sections[0]['CUM_LENGTH_END'] = $sections[0]['SECT_LENGTH'];
		
		// insert first section
		$group = array();
		array_push($group, $sections[0]);
	//	$this->print_road($sections, 0);
		// loop start
		for ($i=1 ; $i<count($sections) ; $i++) {
			// determine the section belongs to the same group
			// criteria are the signed highway roadbed id, continutiy and pavement famil			
			if ($sections[$i]['SIGNED_HIGHWAY_RDBD_ID'] == $highway &&
					$sections[$i]['BEG_REF_MARKER_NBR'] == $sections[$i-1]['END_REF_MARKER_NBR'] &&

					// uncomment this line if offset is considered for continuity
 					$sections[$i]['BEG_REF_MARKER_DISP'] == $sections[$i-1]['END_REF_MARKER_DISP'] &&
					
					$this->same_pvmnt_family($sections[$i-1]['PVMNT_TYPE_DTL_RD_LIFE_CODE'], $sections[$i]['PVMNT_TYPE_DTL_RD_LIFE_CODE'])) {
				
				// if it belongs to the same group, we get the cumulative length of beginning and ending of the section
				$sections[$i]['CUM_LENGTH_BEG'] = $cumLength;
				$sections[$i]['CUM_LENGTH_END'] = $cumLength + $sections[$i]['SECT_LENGTH'];
				
				// update cumulative length variable
				$cumLength = $sections[$i]['CUM_LENGTH_BEG'] + $sections[$i]['SECT_LENGTH'];
				
				// insert this section to current group
				array_push($group, $sections[$i]);
			}
			else {
				// if it doesn't belong to the same group, we insert the group into $groups with total length of the group				
				array_push($this->groups, array("GROUP"=>$group, "TOTAL_LENGTH_END"=>$cumLength, "TOTAL_LENGTH_BEG"=>$cumLength-$sections[$i-1]['SECT_LENGTH']));
				
				// clear group variable
				unset($group); $group = array();
				
				// new group starts
				// update some variables
				$cumLength = $sections[$i]['SECT_LENGTH'];
				$highway = $sections[$i]['SIGNED_HIGHWAY_RDBD_ID'];
				
				// this group's first sections's culmultive length
				$sections[$i]['CUM_LENGTH_BEG'] = 0;
				$sections[$i]['CUM_LENGTH_END'] = $cumLength;
				
				// insert the first section to the group
				array_push($group, $sections[$i]);

		//		$this->print_road($sections, $i);				
			}
		}
		array_push($this->groups, array("GROUP"=>$group, "TOTAL_LENGTH_END"=>$cumLength, "TOTAL_LENGTH_BEG"=>$cumLength-$sections[$i-1]['SECT_LENGTH']));
		unset($group);
	}
	
	public function do_segmentation($cs_threshold, $ds_threshold, $min_seg_len, $max_seg_len) {
		$base_year = 2011;
		// initialize parameters
		$this->cs_threshold = $cs_threshold;
		$this->ds_threshold = $ds_threshold;
		$this->min_seg_len = $min_seg_len;
		$this->max_seg_len = $max_seg_len;
		
		// do segmentation by both scores
		$this->esal_based = $this->segmentation_by('ESAL');
		$this->ds_based = $this->segmentation_by('Distress Score');
		$this->cs_based = $this->segmentation_by('Condition Score');
		
		// stitch the result
		$this->stitch_and_split();
		
		if ($this->user_id == '')
			return;
		
		$table_name = 'segmented_pmis_'.$this->user_id;
		$query = "DROP TABLE IF EXISTS ".$table_name;
		mysql_query($query) or die('mysql_error: '.mysql_error());
		// create table for sections with segment id
		$query = "CREATE TABLE IF NOT EXISTS `".$table_name."` (";
		$query .= "
		`FISCAL_YEAR` smallint(5) NOT NULL,
		`SIGNED_HIGHWAY_RDBD_ID` varchar(8) NOT NULL,
		`BEG_REF_MARKER_NBR` varchar(5) NOT NULL,
		`BEG_REF_MARKER_DISP` double(15,5) NOT NULL,
		`END_REF_MARKER_NBR` varchar(5) NOT NULL,
		`END_REF_MARKER_DISP` double(15,5) NOT NULL,
		`SECT_LENGTH` double(15,5) NOT NULL,
		`RATING_CYCLE_CODE` varchar(1) NOT NULL,
		`SKID_RESIST` boolean DEFAULT NULL,
		`SCI` boolean DEFAULT NULL,
		`VISUAL_ASSESSMENT` boolean DEFAULT NULL,
		`FORCED` varchar(2) DEFAULT NULL,
		`SEGMENT_ID` varchar(20) DEFAULT NULL,
		PRIMARY KEY (`FISCAL_YEAR`,`SIGNED_HIGHWAY_RDBD_ID`,`BEG_REF_MARKER_NBR`,`BEG_REF_MARKER_DISP`,`RATING_CYCLE_CODE`));";
		mysql_query($query) or die('mysql_error: '.mysql_error());
		$count = mysql_query("SELECT count(*) FROM ".$table_name);
		$f = mysql_fetch_row($count);
		if ($f[0] == 0) {
			$query = "INSERT INTO `".$table_name."` (FISCAL_YEAR, SIGNED_HIGHWAY_RDBD_ID, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP, SECT_LENGTH, RATING_CYCLE_CODE)
			SELECT FISCAL_YEAR, SIGNED_HIGHWAY_RDBD_ID, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP, SECT_LENGTH, RATING_CYCLE_CODE FROM `pmis_condition_summary_bryan`"
			." WHERE FISCAL_YEAR='2011' AND RATING_CYCLE_CODE='P' AND PVMNT_TYPE_BROAD_CODE = 'A';";
			mysql_query($query) or die('mysql_error: '.mysql_error());
		}
		
		// update segment_id
		$num_groups = count($this->groups);
		for ($i = 0 ;$i<$num_groups ; $i++) {
			$num_sections = count($this->groups[$i]['GROUP']);
			for ($j=0 ; $j<$num_sections ; $j++) {
				$segment_id = $this->groups[$i]['GROUP'][$j]['SEGMENT_ID'];
				$fiscal_year = $this->groups[$i]['GROUP'][$j]['FISCAL_YEAR'];
				$highway = $this->groups[$i]['GROUP'][$j]['SIGNED_HIGHWAY_RDBD_ID'];
				$beg_nbr = $this->groups[$i]['GROUP'][$j]['BEG_REF_MARKER_NBR'];
				$beg_disp = $this->groups[$i]['GROUP'][$j]['BEG_REF_MARKER_DISP'];
				$rating_cycle_code = $this->groups[$i]['GROUP'][$j]['RATING_CYCLE_CODE'];
				$query = "UPDATE ".$table_name." SET SEGMENT_ID = '".$segment_id."' WHERE FISCAL_YEAR = '".$fiscal_year."'
				AND SIGNED_HIGHWAY_RDBD_ID = '".$highway."' AND BEG_REF_MARKER_NBR = '".$beg_nbr."'
				AND BEG_REF_MARKER_DISP = ".$beg_disp." AND RATING_CYCLE_CODE = '".$rating_cycle_code."'";
				mysql_query($query) or die('mysql_error: '.mysql_error());
			}
		}
		
		$table_name = 'segmented_pmis_aggregated_'.$this->user_id;
		
		$query = "DROP TABLE IF EXISTS ".$table_name;
		mysql_query($query) or die('mysql_error: '.mysql_error());
		
		// create table for groups (aggregated)
		$query = "CREATE TABLE IF NOT EXISTS `".$table_name."` (";
		$query .= "
		`FISCAL_YEAR` smallint(5) NOT NULL,
		`SIGNED_HIGHWAY_RDBD_ID` varchar(8) NOT NULL,
		`BEG_REF_MARKER_NBR` varchar(5) NOT NULL,
		`BEG_REF_MARKER_DISP` double(15,5) NOT NULL,
		`END_REF_MARKER_NBR` varchar(5) NOT NULL,
		`END_REF_MARKER_DISP` double(15,5) NOT NULL,
		`RATING_CYCLE_CODE` varchar(1) NOT NULL,
		`GP_CONDITION_SCORE` double(15,1) NOT NULL,
		`GP_STD_CONDITION_SCORE` double(15,1) NOT NULL,
		`GP_DISTRESS_SCORE` double(15,1) NOT NULL,
		`GP_STD_DISTRESS_SCORE` double(15,1) NOT NULL,
		`GP_RIDE_SCORE` double(15,2) NOT NULL,
		`GP_STD_RIDE_SCORE` double(15,2) NOT NULL,
		`GP_ESAL` double(15,0) NOT NULL,
		`GP_STD_ESAL` double(15,0) NOT NULL,
		`GP_SKID_RESIST` boolean DEFAULT NULL,
		`GP_SCI` boolean DEFAULT NULL,
		`GP_VISUAL_ASSESSMENT` boolean DEFAULT NULL,
		`GP_FORCED` varchar(2) DEFAULT NULL,
		`GP_LENGTH` double(15,2) NOT NULL,
		`GP_NUMBER_THRU_LANES` smallint(5) NOT NULL,
		`GP_AADT` double(15,0) NOT NULL,
		`GP_PVMNT_TYPE_BROAD_CODE` varchar(1) NOT NULL,
		`GP_PVMNT_TYPE_DTL_RD_LIFE_CODE` varchar(2) NOT NULL,
		`GP_PVMT_FAMILY` varchar(1) NOT NULL,
		`GP_TRUCK_AADT_PCT` double(15,1) NOT NULL,
		`GP_DISTRICT_NAME` varchar(48) NOT NULL,
		`GP_ZONE_NUMBER` varchar(2) NOT NULL,
		`GP_SPEED_LIMIT_MAX` smallint(5) NOT NULL,
		`GP_PRIOR_TREATMENT` varchar(3) DEFAULT NULL,
		`GP_YEAR_OF_PRIOR_TREATMENT` varchar(4) NOT NULL,
		`SEGMENT_ID` varchar(20) DEFAULT NULL,
		PRIMARY KEY (`FISCAL_YEAR`,`SIGNED_HIGHWAY_RDBD_ID`,`BEG_REF_MARKER_NBR`,`BEG_REF_MARKER_DISP`,`RATING_CYCLE_CODE`));";
		mysql_query($query) or die('mysql_error: '.mysql_error());

		// update table
		$num_segments = count($this->segments);
		for ($i = 0 ;$i<$num_segments ; $i++) {
			$num_sections = count($this->segments[$i]);
			$sum_sec_length = 0;
			$sum_wcs = 0.0;
			$sum_wride = 0.0;
			$sum_wesal = 0.0;
			$sum_wds = 0.0;
			for ($j=0 ; $j<$num_sections ; $j++) {
				$sum_sec_length += $this->segments[$i][$j]['SECT_LENGTH'];
				$sum_wcs += $this->segments[$i][$j]['SECT_LENGTH']*$this->segments[$i][$j]['ADJ_CONDITION_SCORE'];
				$sum_wds += $this->segments[$i][$j]['SECT_LENGTH']*$this->segments[$i][$j]['ADJ_DISTRESS_SCORE'];
				$sum_wride += $this->segments[$i][$j]['SECT_LENGTH']*$this->segments[$i][$j]['ADJ_RIDE_SCORE'];
				$sum_wesal += $this->segments[$i][$j]['SECT_LENGTH']*$this->segments[$i][$j]['CURRENT_18KIP_MEAS'];
			}	
			$gp_condition_score= $sum_wcs/$sum_sec_length;
			$gp_distress_score= $sum_wds/$sum_sec_length;
			$gp_ride_score= $sum_wride/$sum_sec_length;
			$gp_esal= $sum_wesal/$sum_sec_length;
			
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
			
			for ($j=0 ; $j<$num_sections ; $j++) {
				$sum_num_thru_lanes += $this->segments[$i][$j]['NUMBER_THRU_LANES']*$this->segments[$i][$j]['SECT_LENGTH'];
				$sum_year += $this->segments[$i][$j]['YEAR_OF_PRIOR_TREATMENT']*$this->segments[$i][$j]['SECT_LENGTH']; 
				$sum_speedlimit += $this->segments[$i][$j]['SPEED_LIMIT_MAX']*$this->segments[$i][$j]['SECT_LENGTH'];
				$sum_taadt += $this->segments[$i][$j]['TRUCK_AADT_PCT']*$this->segments[$i][$j]['SECT_LENGTH'];
				$sum_aadt += $this->segments[$i][$j]['AADT']*$this->segments[$i][$j]['SECT_LENGTH'];
				$sum_secs += pow($gp_condition_score-$this->segments[$i][$j]['ADJ_CONDITION_SCORE'], 2)*$this->segments[$i][$j]['SECT_LENGTH'];
				$sum_seds += pow($gp_distress_score-$this->segments[$i][$j]['ADJ_DISTRESS_SCORE'], 2)*$this->segments[$i][$j]['SECT_LENGTH'];
				$sum_seride += pow($gp_ride_score-$this->segments[$i][$j]['ADJ_RIDE_SCORE'], 2)*$this->segments[$i][$j]['SECT_LENGTH'];
				$sum_seesal += pow($gp_esal-$this->segments[$i][$j]['CURRENT_18KIP_MEAS'], 2)*$this->segments[$i][$j]['SECT_LENGTH'];
				if ($this->segments[$i][$j]['PRIOR_TREATMENT'] == "PM")
					$sum_treatment += 1*$this->segments[$i][$j]['SECT_LENGTH'];
				else if ($this->segments[$i][$j]['PRIOR_TREATMENT'] == "LR")
					$sum_treatment += 2*$this->segments[$i][$j]['SECT_LENGTH'];
				else if ($this->segments[$i][$j]['PRIOR_TREATMENT'] == "MR")
					$sum_treatment += 3*$this->segments[$i][$j]['SECT_LENGTH'];
				else if ($this->segments[$i][$j]['PRIOR_TREATMENT'] == "HR" || $this->segments[$i][$j]['PRIOR_TREATMENT'] == "ORG")
					$sum_treatment += 4*$this->segments[$i][$j]['SECT_LENGTH'];
			}
			
			if ($num_sections > 1) {
				$gp_std_condition_score = sqrt($sum_secs/((($num_sections-1)*$sum_sec_length)/$num_sections));
				$gp_std_distress_score = sqrt($sum_seds/((($num_sections-1)*$sum_sec_length)/$num_sections));
				$gp_std_ride_score = sqrt($sum_seride/((($num_sections-1)*$sum_sec_length)/$num_sections));
				$gp_std_esal = sqrt($sum_seesal/((($num_sections-1)*$sum_sec_length)/$num_sections));
			}
			else {
				$gp_std_condition_score = 0;
				$gp_std_distress_score = 0;
				$gp_std_ride_score = 0;
				$gp_std_esal = 0;
			}		
				
			$fiscal_year = $base_year;
			$signed_highway_rdbd_id = $this->segments[$i][0]['SIGNED_HIGHWAY_RDBD_ID'];
			$beg_ref_marker_nbr = $this->segments[$i][0]['BEG_REF_MARKER_NBR'];
			$beg_ref_marker_disp = $this->segments[$i][0]['BEG_REF_MARKER_DISP'];
			$end_ref_marker_nbr = $this->segments[$i][$num_sections-1]['END_REF_MARKER_NBR'];
			$end_ref_marker_disp = $this->segments[$i][$num_sections-1]['END_REF_MARKER_DISP'];
			$rating_cycle_code = 'P';
			$gp_length = $sum_sec_length;
			$gp_number_thru_lanes = round($sum_num_thru_lanes/$sum_sec_length);
			$gp_aadt = $sum_aadt/$sum_sec_length;
			$gp_pvmnt_type_broad_code ='A';
			$gp_pvmnt_type_dtl_rd_life_code = $this->segments[$i][0]['PVMNT_TYPE_DTL_RD_LIFE_CODE'];
			$gp_pvmt_family = $this->get_pvmnt_family($gp_pvmnt_type_dtl_rd_life_code);
			$gp_truck_aadt_pct = $sum_taadt/$sum_sec_length;
			$gp_district_name = $this->segments[$i][0]['DISTRICT_NAME'];
			$gp_zone_number = $this->segments[$i][0]['ZONE_NUMBER'];
			$gp_speed_limit_max = $this->round_nearest($sum_speedlimit/$sum_sec_length, 5);
			if (round($sum_treatment/$sum_sec_length) == 1)
 				$gp_prior_treatment = "PM";
			else if (round($sum_treatment/$sum_sec_length) == 2)
				$gp_prior_treatment = "LR";
			else if (round($sum_treatment/$sum_sec_length) == 3)
				$gp_prior_treatment = "MR";
			else if (round($sum_treatment/$sum_sec_length) == 4)
				$gp_prior_treatment = "HR";
			$gp_year_of_prior_treatment = round($sum_year/$sum_sec_length);
			$segment_id = $this->segments[$i][0]['SEGMENT_ID'];
			$query = "INSERT INTO `".$table_name."` 
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
			."'".$segment_id
			."')";
			mysql_query($query) or die('mysql_error: '.mysql_error());
		}
	}	
	
	private function segmentation_by($by) {
		// retain original
		$groups = $this->groups;
		$num_groups = count($groups);

		// the variable groups contains the grouped sections based on the criteria we mentioned
		// determine POIs
		for ($i=0 ; $i<$num_groups ; $i++) {
			$prev_slope_sign = 0;
			$groups[$i]['GROUP'][0]['POI'] = true;
			$group_length = count($groups[$i]['GROUP']);
			for ($j=1 ; $j<$group_length ; $j++) {
				if (strcmp($by,'Condition Score')==0)
					$diff = $groups[$i]['GROUP'][$j]['ADJ_CONDITION_SCORE'] - $this->cs_threshold;
				else if (strcmp($by, 'Distress Score')==0)
					$diff = $groups[$i]['GROUP'][$j]['ADJ_DISTRESS_SCORE'] - $this->ds_threshold;
				else
					$diff = $groups[$i]['GROUP'][$j]['CURRENT_18KIP_MEAS'] - $this->esal_threshold;
				if ($diff >= 0) $sign_slope = 1; else $sign_slope = -1;
				
				// this section is point of interest
				if ($sign_slope * $prev_slope_sign < 0) {
					$groups[$i]['GROUP'][$j]['POI'] = true;
				}
				$prev_slope_sign = $sign_slope;
			}
		}
		
		// print poi
// 		for ($i=0 ; $i<$num_groups ; $i++) {
// 			$group_highway = $groups[$i]['GROUP'];
// 			$group_length = count($groups[$i]['GROUP']);
// 			for ($j=0 ; $j<$group_length ; $j++) {
// 				if ($groups[$i]['GROUP'][$j]['POI'] == true)
// 					$this->print_road($group_highway, $j);				
// 			}
// 		}
// 		xdebug_break();
		
		// first part of OR
		// we determine those sections no POIs in rear and no new in front within minimum segment length
		for ($i=0 ; $i<$num_groups ; $i++) {
			// starting section is boundary
			$groups[$i]['GROUP'][0]['BOUNDARY'] = true;
			$cum_length = $groups[$i]['GROUP'][0]['CUM_LENGTH_BEG'];
			$rear_poi_loc = $cum_length;
			$group_length = count($groups[$i]['GROUP']);
			for ($j = 1; $j < $group_length; $j++) {
				if ($groups[$i]['GROUP'][$j]['POI']) {
// 					xdebug_break();
				 	if (// no POIs in rear
				 		(float)(string)($groups[$i]['GROUP'][$j]['CUM_LENGTH_BEG']-$rear_poi_loc) >= $this->min_seg_len &&
				 		
				 		// no new in front
						(float)(string)($groups[$i]['TOTAL_LENGTH_END']-$groups[$i]['GROUP'][$j]['CUM_LENGTH_BEG']) >= $this->min_seg_len)

				 		// then this is boundary
				 		$groups[$i]['GROUP'][$j]['BOUNDARY'] = true;
				 	
				 	// update rear poi location
					$rear_poi_loc = $groups[$i]['GROUP'][$j]['CUM_LENGTH_BEG'];
				}	
			}
		}
// 		xdebug_break();
		// second part of OR
		for ($i=0 ; $i<$num_groups ; $i++) {
			// starting section is boundary
			$groups[$i]['GROUP'][0]['BOUNDARY'] = true;
			$cum_length = $groups[$i]['GROUP'][0]['CUM_LENGTH_BEG'];
			$group_length = count($groups[$i]['GROUP']);
			$rear_boundary_loc = $cum_length;
			for ($j = 1; $j < $group_length; $j++) {
				if ($groups[$i]['GROUP'][$j]['POI']) {
					$next_poi_loc = $this->find_next_poi_loc($groups[$i]['GROUP'], $j);
					if (// no boundary in rear
						(float)(string)($groups[$i]['GROUP'][$j]['CUM_LENGTH_BEG']-$rear_boundary_loc) >= $this->min_seg_len &&
						// no poi in front
						(float)(string)($next_poi_loc-$groups[$i]['GROUP'][$j]['CUM_LENGTH_BEG']) >= $this->min_seg_len)
						
						// then this is boundary
						$groups[$i]['GROUP'][$j]['BOUNDARY'] = true;
				}
				
				// update rear boundary location
				if ($groups[$i]['GROUP'][$j]['BOUNDARY'])
					$rear_boundary_loc = $groups[$i]['GROUP'][$j]['CUM_LENGTH_BEG'];
			}
		}
		
		// now we have segmentated sections by $by
		// we organize the result by separating boundaries and groups
		$boundaries = array();
		$result = array();
		for ($i = 0; $i<count($groups); $i++) {
			$group_highway = $groups[$i]['GROUP'];
			for ($j = 0; $j < count($groups[$i]['GROUP']); $j++) {
				if ($groups[$i]['GROUP'][$j]['BOUNDARY'] == true) {
					array_push($boundaries, $group_highway[$j]);
//  					$this->print_road($group_highway, $j);
				}
			}
			array_push($result, array("boundaries"=>$boundaries, "section"=>$group_highway));			
			unset($boundaries);
			$boundaries = array();
		}
// 		xdebug_break();
		// $result is array of "boundaries" and "section" for each group
		return $result;		
	}
	
	// when stitching $boundary_set2 has priority over boundary_set1
	private function union($boundary_set1, $boundary_set2) {
// 		xdebug_break();
		$num_groups = count($this->groups);
		
		// stitching
		$stitched_boundaries = array();
		$boundaries = array();
		$union = array();
		
		// for each group
		for ($i = 0; $i < $num_groups; $i++) {
			$boundary1 = $boundary_set1[$i]['boundaries'];
			$boundary2 = $boundary_set2[$i]['boundaries'];
		
			$index1 = 0;
			$index2 = 0;
			$group_length = count($boundary1) >= count($boundary2) ? count($boundary1) : count($boundary2);
			// union
			while (true) {
				if ($boundary1[$index1]['CUM_LENGTH_BEG'] == $boundary2[$index2]['CUM_LENGTH_BEG']) {
					array_push($union, array("info"=>$boundary2[$index2], "src"=>"both"));
					$index1++;
					$index2++;
				} else if ($boundary1[$index1]['CUM_LENGTH_BEG'] > $boundary2[$index2]['CUM_LENGTH_BEG']) {
					array_push($union, array("info"=>$boundary2[$index2++], "src"=>"2"));
				} else {
					array_push($union, array("info"=>$boundary1[$index1++], "src"=>"1"));
				}
				if ($index1 == count($boundary1) || $index2 == count($boundary2))
					break;
			}
			if ($index1 == count($boundary1)) {
				while ($index2 < count($boundary2))
					array_push($union, array("info"=>$boundary2[$index2++], "src"=>"2"));
			} else if ($index2 == count($boundary2)) {
				while ($index1 < count($boundary1))
					array_push($union, array("info"=>$boundary1[$index1++], "src"=>"1"));
			}
		
			// cut unnecessary cs
			for ($j = 0; $j < count($union); $j++) {
				// ds is preffered
				if ($union[$j]['src'] == "both" || $union[$j]['src'] == "2") {
					array_push($boundaries, $union[$j]['info']);
				} else {
					if (($j - 1 >= 0 && (float)(string)($union[$j]['info']['CUM_LENGTH_BEG'] - $union[$j - 1]['info']['CUM_LENGTH_BEG']) >= $this->min_seg_len) &&
							(($j + 1 < count($union) && (float)(string)($union[$j + 1]['info']['CUM_LENGTH_BEG'] - $union[$j]['info']['CUM_LENGTH_BEG']) >= $this->min_seg_len) ||
									$j == count($union) - 1)) {
						array_push($boundaries, $union[$j]['info']);
					} else {
						array_splice($union, $j, 1);
						$j--;
					}
				}
			}
			unset($union);
			$union = array();
			array_push($stitched_boundaries, array("boundaries"=>$boundaries));
			unset($boundaries);
			$boundaries = array();
		}
		return $stitched_boundaries;
	}
	
	// stiching and split the long segment whose length is longer than maximum segment length
	private function stitch_and_split() {
		$stitched_boundaries = $this->union($this->cs_based, $this->esal_based);
		$stitched_boundaries = $this->union($stitched_boundaries, $this->ds_based);
		
		$num_groups = count($this->groups);
		
		// now we update
		for ($i = 0 ;$i<$num_groups ; $i++) {
			$num_sections = count($this->groups[$i]['GROUP']);
			$boundaries = $stitched_boundaries[$i]['boundaries'];
			$k=0;
			for ($j=0 ; $j<$num_sections ; $j++) {
				if ($boundaries[$k]['CUM_LENGTH_BEG'] == $this->groups[$i]['GROUP'][$j]['CUM_LENGTH_BEG']) {
					$this->groups[$i]['GROUP'][$j]['BOUNDARY'] = true;
// 					$this->print_road($this->groups[$i]['GROUP'], $j);
					$k++;
				}
			}
		}
		
		// split long segments
		// for each group
// 		xdebug_break();
		for ($i = 0; $i < $num_groups; $i++) {
			// get the boundary of this group
			$boundaries = $stitched_boundaries[$i]['boundaries'];
			// get the group of sections
			$group = $this->groups[$i]['GROUP'];
			$num_sections = count($group);
			$num_boundaries = count($boundaries);

			$last = $this->groups[$i]['TOTAL_LENGTH_END'];
			for ($j=0 ; $j<$num_boundaries ; $j++) {
				$first = $boundaries[$j]['CUM_LENGTH_BEG'];
				if ($j+1 < $num_boundaries)
					$second = $boundaries[$j+1]['CUM_LENGTH_END'];
				else
					$second = $last;
				
				if ($second - $first > $this->max_seg_len) {
					$this->split($first, $second, $i);
				}
			}			
		}
		
// 		xdebug_break();
// 		for ($i = 0; $i<$num_groups; $i++) {
// 			$group_highway = $this->groups[$i]['GROUP'];
// 			for ($j = 0; $j < count($this->groups[$i]['GROUP']); $j++) {
// 				if ($this->groups[$i]['GROUP'][$j]['BOUNDARY'] == true) {
//   					$this->print_road($group_highway, $j);
// 				}
// 			}
// 		}
		
// 		xdebug_break();

		for ($i = 0; $i<$num_groups; $i++) {
			$group_highway = $this->groups[$i]['GROUP'];
			$highway = $this->groups[$i]['GROUP'][0]['SIGNED_HIGHWAY_RDBD_ID'];
			if ($highway != $prev_highway)
				$segment_id = 0;
			for ($j = 0; $j < count($this->groups[$i]['GROUP']); $j++) {
				if ($this->groups[$i]['GROUP'][$j]['BOUNDARY'] == true) {
  					$segment_id++;
				}
				$this->groups[$i]['GROUP'][$j]['SEGMENT_ID'] = $this->groups[$i]['GROUP'][$j]['SIGNED_HIGHWAY_RDBD_ID'].' '.$segment_id;				
			}
			$prev_highway = $this->groups[$i]['GROUP'][0]['SIGNED_HIGHWAY_RDBD_ID'];
		}
		
		$group = array();
		for ($i = 0; $i<$num_groups; $i++) {
			array_push($group, $this->groups[$i]['GROUP'][0]);
			for ($j = 1; $j < count($this->groups[$i]['GROUP']); $j++) {
				if ($this->groups[$i]['GROUP'][$j]['BOUNDARY'] == true) {
					array_push($this->segments, $group);
					unset($group);
					$group = array();
				}
				array_push($group, $this->groups[$i]['GROUP'][$j]);
			}
			array_push($this->segments, $group);
			unset($group);
			$group = array();
		}
		
		// done!
	}
	
	private function split($start, $end, $k) {
// 		xdebug_break();
		$howmany = ceil(($end-$start) / $this->max_seg_len);
		
		// if too close to the maximum segment length
		if (ceil(($end-$start) / $howmany) >= $this->max_seg_len - 0.5)
			$howmany++;
		$howlong = ceil(($end-$start) / $howmany);
		$cum = 0;
		$num_sections = count($this->groups[$k]['GROUP']);
		for ($i=0 ; $i<$num_sections ; $i++) {
			if ($this->groups[$k]['GROUP'][$i]['CUM_LENGTH_BEG'] >= $start &&
					$this->groups[$k]['GROUP'][$i]['CUM_LENGTH_END'] <= $end) {
				$cum += $this->groups[$k]['GROUP'][$i]['SECT_LENGTH'];
				if ($cum >= $howlong) {
					$this->groups[$k]['GROUP'][$i]['BOUNDARY'] = true;
					$cum = 0;
				}				
			}
		}
	}
		
	// find closest rear boundary from section $j as searching backward
	private function find_boundary($group, $j) {
		for ($k=$j-1; $k>=0; $k--) {
			if ($group_highway[$k]['BOUNDARY'] == true) 
				return $group[$k];
		}
		return $group[0];
	}	
	
	// find closest front poi location as searching forward from $j
	private function find_next_poi_loc($group, $j) {
		for ($k=$j+1; $k<count($group); $k++) {
			if ($group[$k]['POI'] == true)
				return $group[$k]['CUM_LENGTH_BEG'];
		}
		return $group[count($group)-1]['CUM_LENGTH_END'];
	}
	
	private function round_nearest($no,$near) 
	{ 	
		return round($no/$near)*$near; 
	} 	
	
	// determine whether two pvmnt codes belong to the same pvmnt family
	private	function same_pvmnt_family($pvmnt_code1, $pvmnt_code2) {
		// if they are the same pvmnt family
		if ($this->get_pvmnt_family($pvmnt_code1) == $this->get_pvmnt_family($pvmnt_code2))
			return true;
		// if not
		else
			return false;
	}
	
	/* get pvmnt family based on pvmnt code
	 * 4,5,9 => A
	 * 7,8 => B
	 * 6,10 => C
	 */
	private function get_pvmnt_family($pvmnt_code) {
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
	
	
	// for debugging purpose	
	function print_road($group, $k) {
		printf("%s, %s, %s, %s, %s\n", $group[$k]['SIGNED_HIGHWAY_RDBD_ID'], $group[$k]['BEG_REF_MARKER_NBR'],
								$group[$k]['BEG_REF_MARKER_DISP'], $group[$k]['END_REF_MARKER_NBR'], $group[$k]['END_REF_MARKER_DISP']);
	}
	
	function print_road2($group) {
		printf("%s, %s, %s, %s, %s\n", $group['SIGNED_HIGHWAY_RDBD_ID'], $group['BEG_REF_MARKER_NBR'],
				$group['BEG_REF_MARKER_DISP'], $group['END_REF_MARKER_NBR'], $group['END_REF_MARKER_DISP']);
	}
};

$segmentation = new Segmentation;
?>