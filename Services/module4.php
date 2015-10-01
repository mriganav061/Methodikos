<?php

class Module4 {	
	private $user_id;
	private $segments;
	private $district;
	private $init_cost;
	private $sections;
	private $ltpb;
	private $lcc;
	
	function Module4() {
		global $session;
		global $database;
		$this->district = $_SESSION['district'];
		$this->user_id = $session->user_id;
	}
	
	public function do_analysis($in_sections, $in_segments, $cscoeffpm, $cscoefflr, $cscoeffmr, $cscoeffhr, $dscoeffpm, $dscoefflr, $dscoeffmr, $dscoeffhr, $ridecoeff, $benefitcsthreshold, $unitcost, $discountrate,
		$mnrtriggerparam, $mnrtriggervalue, $budget, $currentyear, $aadtgrowthrate, $total_lane_miles, $seg_method,
		$pmrsincreaseby, $pmdsresetto, $lrrsincreaseby, $lrdsresetto, $mrrsresetto, $mrdsresetto, $hrrsresetto, $hrdsresetto, $iter) {
		
		global $session;
   		global $database;

   		// initialize parameters
   		if ($ridecoeff == null) {
   			$query = "SELECT * FROM ride_coeff_$this->district WHERE `Setting Name`='Default'";
   			$ridecoeff = $database->Query($query);
   		}
   		if ($unitcost == null) {
    		$query = "SELECT * FROM unit_cost_$this->district";
    		$unitcost = $database->Query($query);
    	}
		$rehap_type = array("PM", "LR", "MR", "HR");
		$cs_coeffs = array("PM"=>$cscoeffpm, "LR"=>$cscoefflr, "MR"=>$cscoeffmr, "HR"=>$cscoeffhr);
		$ds_coeffs = array("PM"=>$dscoeffpm, "LR"=>$dscoefflr, "MR"=>$dscoeffmr, "HR"=>$dscoeffhr);
		for ($i=0 ; $i<count($rehap_type) ; $i++) {
			if ($cs_coeffs[$rehap_type[$i]] == null) {
				$query = "SELECT * FROM cs_modeling_parameters_$this->district WHERE `Setting Name`='Default' AND `Rehap Type`='$rehap_type[$i]'";
				$cs_coeffs[$rehap_type[$i]] = $database->Query($query);
			}
			if ($ds_coeffs[$rehap_type[$i]] == null) {
				$query = "SELECT * FROM ds_modeling_parameters_$this->district WHERE `Setting Name`='Default' AND `Rehap Type`='$rehap_type[$i]'";
				$ds_coeffs[$rehap_type[$i]] = $database->Query($query);
			}
		}


		// if ($database->QueryCount("show tables like 'forced_".$this->district."'")>=1 && $this->user_id == 1) {
		// 	$forced_list_exists = true;
		// 	$query = "SELECT YEAR".$iter." FROM `forced_".$this->district."` WHERE SIGNED_HIGHWAY_RDBD_ID='$highway_id' AND BEG_REF_MARKER_NBR='$beg_ref_marker_nbr' AND BEG_REF_MARKER_DISP=$beg_ref_marker_disp";
		// 	$query = "SELECT YEAR".$iter." FROM `forced_".$this->district."`";
		// 	$forced_list = $database->Query($query);
		// }
		// else {
		// 	$forced_list_exists = false;
		// }

		/* CHANGE ON 5/2/2013 */
		// if ($in_sections) {
		// 	for ($i=0 ; $i<count($in_sections) ; $i++) {
		// 		if ($in_sections[$i]['GP_FORCED'] != null) {
		// 			// compute the cost for forced projects and update
		// 			$forced_type = $in_sections[$i]['GP_FORCED'];
		// 			$forced_cost += $in_sections[$i]['INIT_COST_'.$forced_type];
		// 			$in_sections[$i]['GP_FINAL_TREATMENT'] = $forced_type;
		// 			$in_sections[$i]['GP_FINAL_TREATMENT_COST'] = $in_sections[$i]['INIT_COST_'.$forced_type];
		// 			$unit_cost = $this->unit_cost_filter_by_value($unitcost, $rehap_type[$j]);
		// 		}
		// 	}	
		// }

		// iterate each segment
		$this->segments = $in_segments;
		$num_segments = count($this->segments);
		$segments = $this->segments;

		$set_of_module4_data = array();

		// total cost for forced projects
		// xdebug_break();
		$forced_cost = 0;
		for ($i=0 ; $i<$num_segments ; $i++) {
			// if ($i == 1)
				// xdebug_break();
			// check forced projects
			// if ($forced_list_exists) {
			// 	$year_column = 'YEAR'.$iter;
			// 	if ($forced_list[$i][$year_column] != '0')
			// 		$segments[$i]['GP_FORCED'] = $forced_list[$i][$year_column];
			// }
			if ($segments[$i]['GP_FORCED'] == null) {
				if ($seg_method == 2) {
					$segments[$i]['GP_FINAL_TREATMENT'] = 'M&R NOT FUNDED';
				}
				else if ($mnrtriggerparam == "Condition Score") {
					if ($segments[$i]['GP_CS_RELIAB']>$mnrtriggervalue)
						$segments[$i]['GP_FINAL_TREATMENT'] = 'DN';
					else
						$segments[$i]['GP_FINAL_TREATMENT'] = 'M&R NOT FUNDED';
				}
				else {
					if ($segments[$i]['GP_DS_RELIAB']>$mnrtriggervalue)
						$segments[$i]['GP_FINAL_TREATMENT'] = 'DN';
					else
						$segments[$i]['GP_FINAL_TREATMENT'] = 'M&R NOT FUNDED';
				}
				$segments[$i]['GP_FINAL_TREATMENT_COST'] = 0;
			}
			else {
				// compute the cost for forced projects and update
				$forced_type = $segments[$i]['GP_FORCED'];
				$forced_cost += $segments[$i]['INIT_COST_'.$forced_type];
				$segments[$i]['GP_FINAL_TREATMENT'] = $forced_type;
				$segments[$i]['GP_FINAL_TREATMENT_COST'] = $segments[$i]['INIT_COST_'.$forced_type];
				// var_dump($segments[$i]);
				// xdebug_break();
			}

			// if "do nothing" or forced, we skip
			if ($segments[$i]['GP_FINAL_TREATMENT'] == 'DN' ||
				$segments[$i]['GP_FORCED'] != null)
				continue;

			// otherwise, get module4_data which is stored in module3
			$module4_data = $segments[$i]['MODULE4_DATA'];

			// sort the data with init cost ascending
			$init_cost = array();
			foreach ($module4_data as $key => $row) {
			    $init_cost[$key] = $row['INIT_COST'];
			}

			//*******************************************************************************************************
			$theo = array();
			for ($ii=0 ; $ii<4 ; $ii++) {
				if (isset($module4_data[$ii]['BENEFIT']) && $module4_data[$ii]['INIT_COST']!=0)
					$theo[$rehap_type[$ii]] = $module4_data[$ii]['BENEFIT']/$module4_data[$ii]['INIT_COST'];
			}
			if (count($theo) != 0) {
				$maxs = array_keys($theo, max($theo));
				$segments[$i]['THEORETICAL_TREATMENT'] = $maxs[0];
			}
			else 
				$segments[$i]['THEORETICAL_TREATMENT'] = "NA";

			/*
			// store for later use in module4
					$module4_data[$j]['BENEFIT'] = $ahp_score;
					$module4_data[$j]['INIT_COST'] = $ic;
					$module4_data[$j]['TREATMENT_TYPE'] = $rehap_type[$j];
			*/

			// $segments[$i]['THEORETICAL_TREATMENT'] = 

			// add $data as the last parameter, to sort by the common key
			array_multisort($init_cost, SORT_ASC, $module4_data);	

			// xdebug_break();
			// now $module4_data is sorted in init cost ascending
			// make sure benefit is also increasing order otherwise we splice it from array
			for ($j=0 ; $j<count($module4_data) ; $j++) {
				for ($k=$j+1 ; $k<count($module4_data) ; $k++) {
					if ($module4_data[$k]['BENEFIT']-$module4_data[$j]['BENEFIT'] <= 0.0001) {
						array_splice($module4_data, $k, 1);
						$k=$j;
					}
				}		
			}	
			
			// xdebug_break();	
			
			/*
			 * compute incremental initial cost and benefit
			 */
			// xdebug_break();
			for ($j=0 ; $j<count($module4_data) ; $j++) {
				$module4_data[$j]['INCREMENTAL_INIT_COST'] = $module4_data[$j]['INIT_COST'];
				$module4_data[$j]['INCREMENTAL_BENEFIT'] = $module4_data[$j]['BENEFIT'];
				if ($j>0) {
					$module4_data[$j]['INCREMENTAL_INIT_COST'] -= $module4_data[$j-1]['INIT_COST'];
					$module4_data[$j]['INCREMENTAL_BENEFIT'] -= $module4_data[$j-1]['BENEFIT'];
				}
				// compute IBC ratio
				$ibc_ratio = array();
				for ($k=$j ; $k>=0 ; $k--) {					
					if ($k == $j)
						array_push($ibc_ratio, $module4_data[$j]['BENEFIT']/$module4_data[$j]['INIT_COST']);
					else {
						// if ($module4_data[$j]['INIT_COST']-$module4_data[$k]['INIT_COST'] == 0)
							// xdebug_break();
						array_push($ibc_ratio, ($module4_data[$j]['BENEFIT']-$module4_data[$k]['BENEFIT'])/($module4_data[$j]['INIT_COST']-$module4_data[$k]['INIT_COST']));
					}
				}
				// if ($i==527 || $i==735)
				// 	xdebug_break();
				$module4_data[$j]['IBC_RATIO'] = min($ibc_ratio);
			}

			/*
			 * compute local rank
			 */
			$ibc_ratio = array();
			foreach ($module4_data as $key => $row) {
			    $ibc_ratio[$key] = $row['IBC_RATIO'];
			}
			array_multisort($ibc_ratio, SORT_DESC, $module4_data);
			$local_rank = 1;

			// store some addition info for convenience
			for ($j=0 ; $j<count($module4_data) ; $j++) {
				// group index is the segment index where this $module4_data belongs to
				$module4_data[$j]['GROUP_INDEX'] = $i;
				// local index
				$module4_data[$j]['LOCAL_INDEX'] = $j;
				// local rank
				$module4_data[$j]['LOCAL_RANK'] = $local_rank++;
				// index in $set of module4_data
				$module4_data[$j]['INDEX'] = count($set_of_module4_data);
				array_push($set_of_module4_data, $module4_data[$j]);
			}
		}
		// xdebug_break();

		/*
		 * compute global rank
		 */
		$ibc_ratio = array();
		foreach ($set_of_module4_data as $key => $row) {
		    $ibc_ratio[$key] = $row['IBC_RATIO'];
		}
		array_multisort($ibc_ratio, SORT_DESC, $set_of_module4_data);
		$global_rank = 1;
		// xdebug_break();
		for ($i=0 ; $i<count($set_of_module4_data) ; $i++) {
			$group_index = $set_of_module4_data[$i]['GROUP_INDEX'];
			$local_index = $set_of_module4_data[$i]['LOCAL_INDEX'];
			$local_rank = $set_of_module4_data[$i]['LOCAL_RANK'];
			$set_of_module4_data[$i]['GLOBAL_RANK'] = $global_rank;

			// update
			$segments[$group_index]['MODULE4_DATA'][$local_index]['GLOBAL_RANK'] = $global_rank;
			$segments[$group_index]['MODULE4_DATA'][$local_index]['LOCAL_RANK'] = $local_rank;
			$global_rank++;
		}
		// xdebug_break();

		/*
		 * actual incremental benefit algorithm start
		 * we iterate two times - in second iteration, we remove last listed funded project and switch it with next one
		 * and see we have more benefit
		 */
		// xdebug_break();
		for ($j=0 ; $j<2 ; $j++) {
			$total_budget = $budget * 1000;	// $million to $thousand
			$available_budget = $total_budget - $forced_cost;
			
			$cum_cost = 0;				// cumulutive cost
			$cum_benefit = 0;			// cumulutive benefit
			$project_list = array();	// funded project list
			
			// from the top
			// note that $set_of_module4_data is sorted in global rank (ibc_ratio)
			for ($i=0 ; $i<count($set_of_module4_data) ; $i++) {
				$group_index = $set_of_module4_data[$i]['GROUP_INDEX'];
				// print $group_index.",".$set_of_module4_data[$i]['TREATMENT_TYPE']."\n";
				// if ($group_index==1)
				// 	xdebug_break();
				// last project? we ignore it and continue
				if (isset($last_project_index) && $i == $last_project_index) {
					// xdebug_break();
					continue;
				}
				// check the same group is already in project list
				else if (isset($project_list[$group_index])) {
					// check the old cost and benefit
					$old_cost = $project_list[$group_index]['INIT_COST'];
					$old_benefit = $project_list[$group_index]['BENEFIT'];
					$new_cost = $set_of_module4_data[$i]['INIT_COST'];
					$new_benefit = $set_of_module4_data[$i]['BENEFIT'];

					// should we replace it?
					if ($new_cost<=$available_budget+$old_cost || $new_cost<=$available_budget) {
						// we have more benefit from replacement, so we replace
						if ($new_benefit > $old_benefit) {
							// update variables from this change
							$project_list[$group_index] = $set_of_module4_data[$i];
							$available_budget = $available_budget + $old_cost - $new_cost;
							$cum_benefit +=  -$old_benefit + $new_benefit;
							$cum_cost += -$old_cost + $new_cost;
							$last_project = $i;
						}
					}
				}
				// otherwise, check we have enough budget to fund this project
				else if ($set_of_module4_data[$i]['INIT_COST']<=$available_budget) {
					// if so, add it to project_list
					$cum_benefit += $set_of_module4_data[$i]['BENEFIT'];
					$cum_cost += $set_of_module4_data[$i]['INIT_COST'];
					$project_list[$group_index] = $set_of_module4_data[$i];
					$available_budget = $available_budget - $set_of_module4_data[$i]['INIT_COST'];
					$last_project = $i;
				}			
			}
			// before switch
			if (!isset($last_project_index)) {
				// switch the last
				// xdebug_break();
				$before_switch_project_list = $project_list;
				$last_project_index = $last_project;			
				$before_switch_cum_benefit = $cum_benefit;
				$before_switch_cum_cost = $cum_cost;
			}
			// after switch
			else {
				$after_switch_project_list = $project_list;			
				$after_switch_cum_benefit = $cum_benefit;
				$after_switch_cum_cost = $cum_cost;
			}
		}

		// xdebug_break();
		$cum_cost = 0;
		$cum_benefit = 0;
		$project_list = array();

		// xdebug_break();
		// choose one that has more cumulutive benefit
		if ($before_switch_cum_benefit >= $after_switch_cum_benefit) {
			$cum_cost = $before_switch_cum_cost;
			$cum_benefit = $before_switch_cum_benefit;
			$project_list = $before_switch_project_list;
		}
		else {
			$cum_cost = $after_switch_cum_cost;
			$cum_benefit = $after_switch_cum_benefit;
			$project_list = $after_switch_project_list;
		}

		// xdebug_break();
		// update segment using project list
		foreach ($project_list as $key=>$row) {
			$global_index = $key;
			$segments[$global_index]['GP_FINAL_TREATMENT'] = $row['TREATMENT_TYPE'];
			$segments[$global_index]['GP_FINAL_TREATMENT_COST'] = $row['INIT_COST'];
		}

		unset($project_list);
		$project_list = array();
		for ($j=0 ; $j<count($rehap_type) ; $j++) {
			$output['BACKLOG_LANE_MILES_TOTAL_'.$rehap_type[$j]] = 0;
			$output['BACKLOG_LANE_COST_TOTAL_'.$rehap_type[$j]] = 0;
			$output['FORCED_LANE_MILES_TOTAL_'.$rehap_type[$j]] = 0;
			$output['FORCED_COST_TOTAL_'.$rehap_type[$j]] = 0;
			$output['LANE_MILES_TOTAL_'.$rehap_type[$j]] = 0;
			$output['LANE_COST_TOTAL_'.$rehap_type[$j]] = 0;
		}
		
		/*
		 * calculate predicted score based on model and treatments we decided on and ready data for next analysis in module3
		 * up to this point, 'GP_FINAL_TREATMENT' has one of "DN", "M&R NOT FUNDED", "PM", "LR", "MR", "HR"
		 * 
		 */

		// depending on segmentation method, we need to determine whether we go from group to sections
		
		// xdebug_break();		
		$init_A = $init_B = $init_C = $init_D = $init_F = 0;
		$final_A = $final_B = $final_C = $ifinal_D = $final_F = 0;
		$init_above_threshold = $init_below_threshold = 0;
		$final_above_threshold = $final_below_threshold = 0;
		$init_avg_cs = $init_avg_ds = $init_avg_ride = 0;
		$after_mnr_avg_cs = $after_mnr_avg_ds = $after_mnr_avg_ride = 0;
		$final_avg_cs = $final_avg_ds = $final_avg_ride = 0;
		$init_min_ds = $init_min_cs = $init_min_ride = 100;
		$after_mnr_min_ds = $after_mnr_min_cs = $after_mnr_min_ride = 100;
		$final_min_ds = $final_min_cs = $final_min_ride = 100;
		$project_list = array();
		
		if ($seg_method == 1) {
			// $this->segments = $this->segment_based_prediction($segments);
			$num_segments = count($segments);
			for ($i=0 ; $i<$num_segments ; $i++) {
				$esal_class = $segments[$i]['GP_ESAL_CLASS'];
				$traffic_speed_class = $segments[$i]['GP_TRAFFIC_SPEED_CLASS'];

				$init_cs = $segments[$i]['GP_CS_RELIAB'];
				$init_ds = $segments[$i]['GP_DS_RELIAB'];
				$init_ride = $segments[$i]['GP_RIDE_RELIAB'];

				// compute ds,ride,cs start
				if ($segments[$i]['GP_FINAL_TREATMENT'] == "DN" || $segments[$i]['GP_FINAL_TREATMENT'] == "M&R NOT FUNDED") {
					$ds_start = $segments[$i]['GP_DS_RELIAB'];
					$ride_start = $segments[$i]['GP_RIDE_RELIAB'];
					$cs_start = $segments[$i]['GP_CS_RELIAB'];
				}
				else {
					// xdebug_break();
					$params = $segments[$i]['GP_START_VALUE_'.$segments[$i]['GP_FINAL_TREATMENT']];
					$ds_start = $params["DS_START"];
					$ride_start = $params['RIDE_START'];
					$cs_start = $params['URIDE'] * $ds_start;
				}

				$after_mnr_cs = $cs_start;
				$after_mnr_ds = $ds_start;
				$after_mnr_ride = $ride_start;

				// for funded projects, we use GP_FINAL_TREATMENT
				if ($segments[$i]['GP_FINAL_TREATMENT'] != "DN" && $segments[$i]['GP_FINAL_TREATMENT'] != "M&R NOT FUNDED") {
					// get the last treatment
					$last_treatment = $segments[$i]['GP_FINAL_TREATMENT'];

					// get cs and ds coefficient for model
					$cs_coeff = $this->cs_filter_by_value($cs_coeffs[$last_treatment], $last_treatment, $segments[$i]['GP_PVMT_FAMILY'], $esal_class, $traffic_speed_class);
					$ds_coeff = $this->ds_filter_by_value($ds_coeffs[$last_treatment], $last_treatment, $segments[$i]['GP_PVMT_FAMILY'], $esal_class);

					$year_of_treatment = $currentyear;
					$ds_year_treatment = $currentyear;
					$cs_year_treatment = $currentyear;
				}
				// for not funded projects, we use GP_PRIOR_TREATMENT
				else {

					$last_treatment = $segments[$i]['GP_PRIOR_TREATMENT'];
					$year_of_treatment = $segments[$i]['GP_YEAR_OF_PRIOR_TREATMENT'];
					$cs_coeff = $this->cs_filter_by_value($cs_coeffs[$last_treatment], $last_treatment, $segments[$i]['GP_PVMT_FAMILY'], $esal_class, $traffic_speed_class);
					$ds_coeff = $this->ds_filter_by_value($ds_coeffs[$last_treatment], $last_treatment, $segments[$i]['GP_PVMT_FAMILY'], $esal_class);	
					
					if ($cs_coeff['Beta'] != 0)
						$denominator = pow(-log(1-min(99.9999999,$cs_start)/100),1/$cs_coeff['Beta']);
					else
						$denominator = 0;
					
					if ($denominator != 0)
						$cs_year_treatment = $currentyear - round($cs_coeff['Rho']/$denominator);
					else {
						// print "error";
						// xdebug_break();
					}
						
					if ($ds_coeff['Beta'] != 0)
						$denominator = pow(-log(1-min(99.9999999,$ds_start)/100),1/$ds_coeff['Beta']);
					else
						$denominator = 0;
					
					if ($denominator != 0)
						$ds_year_treatment = $currentyear - round($ds_coeff['Rho']/$denominator);
					else {
						// print "error";
						// xdebug_break();
					}
				}
			
				/*
				 * for next year, predict score
				 */
				$next_year = $currentyear + 1;
				$ds_based_age = $next_year - $ds_year_treatment;
				$cs_based_age = $next_year - $cs_year_treatment;

				// cs, ds, uride prediction based on model
				$predicted_ds = 100 * (1-exp(-(pow($ds_coeff['Rho']/$ds_based_age, $ds_coeff['Beta']))));
				// xdebug_break();
				// for funded project
				if ($segments[$i]['GP_FINAL_TREATMENT'] != "DN" && $segments[$i]['GP_FINAL_TREATMENT'] != "M&R NOT FUNDED") {
					$predicted_cs = $cs_start * (1-exp(-(pow($cs_coeff['Rho']/$cs_based_age, $cs_coeff['Beta']))));
				}
				else
					$predicted_cs = min($predicted_ds, $cs_start);
				
				$predicted_uride = min($predicted_cs/$predicted_ds, 1);
				
				// li prediction
				if ($predicted_uride == 1)
					$predicted_li = 1;
				else {
					$ride_coeff = $this->ride_filter_by_value($ridecoeff, $traffic_speed_class);
					if ($ride_coeff['Beta'] != 0 && $ride_coeff['Alpha'] != 0)
						$denominator = pow(-log((1-$predicted_uride)/$ride_coeff['Alpha']), 1/$ride_coeff['Beta']);
					else
						$denominator = 0;
					
					if ($denominator == 0) {
						// print "error at module4 line 356";
						// xdebug_break();
						// var_dump($segments[$i]);
					}
					else
						$predicted_li = $ride_coeff['Rho']/($denominator);
				}

				// if ($segments[$i]['SIGNED_HIGHWAY_RDBD_ID'] == 'FM0158 R')
				// 	xdebug_break();

				// ride prediction
				if ($traffic_speed_class == "Low")
					$predicted_ride = 2.5-(0.025*$predicted_li);
				else if ($traffic_speed_class == "Med")
					$predicted_ride = 3-(0.03*$predicted_li);
				else
					$predicted_ride = 3.5-(0.035*$predicted_li);

				// Check
				// if ($predicted_ride<=0)
				// 	xdebug_break();			

				/*
				 * output
				 */
				$length = $segments[$i]['GP_LENGTH'] * $segments[$i]['GP_NUMBER_THRU_LANES'];
				$cost = $segments[$i]['GP_FINAL_TREATMENT_COST'];
				switch ($segments[$i]['GP_FORCED']) {
					// forced project outputs
					case 'PM':
						$output['FORCED_LANE_MILES_TOTAL_PM'] += $length;
						$output['LANE_COST_TOTAL_PM'] += $cost;
						break;
					case 'LR':
						$output['FORCED_LANE_MILES_TOTAL_LR'] += $length;
						$output['LANE_COST_TOTAL_LR'] += $cost;
						break;
					case 'MR':
						$output['FORCED_LANE_MILES_TOTAL_MR'] += $length;
						$output['LANE_COST_TOTAL_MR'] += $cost;
						break;
					case 'HR':
						$output['FORCED_LANE_MILES_TOTAL_HR'] += $length;
						$output['LANE_COST_TOTAL_HR'] += $cost;
						break;					
					default:
						break;
				}
				// non forced project outputs
				switch ($segments[$i]['GP_FINAL_TREATMENT']) {
					case 'PM':
						if ($segments[$i]['GP_FORCED'] == null) {
							$output['LANE_MILES_TOTAL_PM'] += $length;
							$output['LANE_COST_TOTAL_PM'] += $cost;
						}
						break;
					case 'LR':
						if ($segments[$i]['GP_FORCED'] == null) {
							$output['LANE_MILES_TOTAL_LR'] += $length;
							$output['LANE_COST_TOTAL_LR'] += $cost;
						}
						break;
					case 'MR':
						if ($segments[$i]['GP_FORCED'] == null) {
							$output['LANE_MILES_TOTAL_MR'] += $length;
							$output['LANE_COST_TOTAL_MR'] += $cost;
						}
						break;
					case 'HR':
						if ($segments[$i]['GP_FORCED'] == null) {
							$output['LANE_MILES_TOTAL_HR'] += $length;
							$output['LANE_COST_TOTAL_HR'] += $cost;
						}
						break;			
					case 'M&R NOT FUNDED':	
						// xdebug_break();
						$cost = $segments[$i]['INIT_COST_'.$segments[$i]['THEORETICAL_TREATMENT']];

						switch ($segments[$i]['THEORETICAL_TREATMENT']) {
							case 'PM':
								$output['BACKLOG_LANE_MILES_TOTAL_PM'] += $length;
								$output['BACKLOG_LANE_COST_TOTAL_PM'] += $cost;
								break;
							case 'LR':
								$output['BACKLOG_LANE_MILES_TOTAL_LR'] += $length;
								$output['BACKLOG_LANE_COST_TOTAL_LR'] += $cost;
								break;
							case 'MR':
								$output['BACKLOG_LANE_MILES_TOTAL_MR'] += $length;
								$output['BACKLOG_LANE_COST_TOTAL_MR'] += $cost;
								break;
							case 'HR':
								$output['BACKLOG_LANE_MILES_TOTAL_HR'] += $length;
								$output['BACKLOG_LANE_COST_TOTAL_HR'] += $cost;
								break;										
							default:
								# code...
								break;
						}
						break;				
					default:
						# code...
						break;	
					
				}

				// ready data for next iteration
				$segments[$i]['GP_CONDITION_SCORE'] = $predicted_cs;
				$segments[$i]['GP_CS_RELIAB'] = $predicted_cs;
				$segments[$i]['GP_DISTRESS_SCORE'] = $predicted_ds;
				$segments[$i]['GP_DS_RELIAB'] = $predicted_ds;
				$segments[$i]['GP_RIDE_SCORE'] = $predicted_ride;
				$segments[$i]['GP_RIDE_RELIAB'] = $predicted_ride;
				unset($segments[$i]['GP_MIN_CS']);
				unset($segments[$i]['GP_MIN_DS']);
				unset($segments[$i]['GP_MIN_RIDE']);
				$segments[$i]['GP_AADT'] = round($segments[$i]['GP_AADT']*($aadtgrowthrate/100+1));
				$segments[$i]['GP_PRIOR_TREATMENT'] = $last_treatment;
				$segments[$i]['GP_YEAR_OF_PRIOR_TREATMENT'] = $year_of_treatment;
				$segments[$i]['GP_RATE_OF_DETERIORATION'] = null;			
				$segments[$i]['GP_SKID_RESIST'] = null;
				$segments[$i]['GP_SCI'] = null;
				$segments[$i]['GP_VISUAL_ASSESSMENT'] = null;

				
				// clear things up which don't need further
				
				unset($segments[$i]['MODULE4_DATA']);

				$final_cs = $segments[$i]['GP_CS_RELIAB'];
				$final_ds = $segments[$i]['GP_DS_RELIAB'];
				$final_ride = $segments[$i]['GP_RIDE_RELIAB'];

				/* 
				 * for chart data
				 */
				$length = $segments[$i]['GP_LENGTH'] * $segments[$i]['GP_NUMBER_THRU_LANES'];
				$init_avg_cs += $length * $init_cs;
				$init_avg_ds += $length * $init_ds;
				$init_avg_ride += $length * $init_ride;
				$after_mnr_avg_cs += $length * $after_mnr_cs;
				$after_mnr_avg_ds += $length * $after_mnr_ds;
				$after_mnr_avg_ride += $length * $after_mnr_ride;
				$final_avg_cs += $length * $final_cs;
				$final_avg_ds += $length * $final_ds;
				$final_avg_ride += $length * $final_ride;
				
				if ($init_cs <= $init_min_cs)
					$init_min_cs = $init_cs;

				if ($init_ds <= $init_min_ds)
					$init_min_ds = $init_ds;
				
				if ($init_ride <= $init_min_ride)
					$init_min_ride = $init_ride;
				
				if ($after_mnr_cs <= $after_mnr_min_cs)
					$after_mnr_min_cs = $after_mnr_cs;
				
				if ($after_mnr_ds <= $after_mnr_min_ds)
					$after_mnr_min_ds = $after_mnr_ds;
				
				if ($after_mnr_ride <= $after_mnr_min_ride)
					$after_mnr_min_ride = $after_mnr_ride;
				
				if ($final_cs <= $final_min_cs)
					$final_min_cs = $final_cs;
				
				if ($final_ds <= $final_min_ds)
					$final_min_ds = $final_ds;
				
				if ($final_ride <= $final_min_ride)
					$final_min_ride = $final_ride;

				if ($init_cs >= 70)
					$init_above_threshold += $length;
				else
					$init_below_threshold += $length;

				if ($final_cs >= 70)
					$final_above_threshold += $length;
				else
					$final_below_threshold += $length;

				if ($init_cs >= 90)
					$init_A += $length;
				else if ($init_cs >= 70)
					$init_B += $length;
				else if ($init_cs >= 50)
					$init_C += $length;
				else if ($init_cs >= 35)
					$init_D += $length;
				else
					$init_F += $length;

				if ($final_cs >= 90)
					$final_A += $length;
				else if ($final_cs >= 70)
					$final_B += $length;
				else if ($final_cs >= 50)
					$final_C += $length;
				else if ($final_cs >= 35)
					$final_D += $length;
				else
					$final_F += $length;

				for ($j=0 ; $j<count($rehap_type) ; $j++) {
					unset($segments[$i]['LTPB_'.$rehap_type[$j]]);
					unset($segments[$i]['LCC_'.$rehap_type[$j]]);
					// unset($segments[$i]['INIT_COST_'.$rehap_type[$j]]);
					// unset($segments[$i]['AHP_SCORE_'.$rehap_type[$j]]);
					// unset($segments[$i]['BENEFIT_'.$rehap_type[$j]]);
					unset($segments[$i]['GP_START_VALUE_'.$rehap_type[$j]]);
				}

				/*
				 * project list
				 */
				if ($segments[$i]['GP_FINAL_TREATMENT'] != "DN" && $segments[$i]['GP_FINAL_TREATMENT'] != "M&R NOT FUNDED") {
					if ($segments[$i]['GP_FINAL_TREATMENT'] == "LR" && $segments[$i]['CORRECTION_TYPE'] == "PM/LR" && $segments[$i]['GP_FORCED'] == null)
						$segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT'] = "PM/LR";	
					else if ($segments[$i]['GP_FINAL_TREATMENT'] == "MR" && $segments[$i]['CORRECTION_TYPE'] == "LR/MR" && $segments[$i]['GP_FORCED'] == null)
						$segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT'] = "LR/MR";	
					else if ($segments[$i]['GP_FINAL_TREATMENT'] == "MR" && $segments[$i]['CORRECTION_TYPE'] == "PM/MR" && $segments[$i]['GP_FORCED'] == null)
						$segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT'] = "PM/MR";	
					else
						$segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT'] = $segments[$i]['GP_FINAL_TREATMENT'];
					if ($segments[$i]['GP_FORCED'] != null)
						$segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT'] .= " (F)";
					$segments[$i]['GP_FINAL_TREATMENT_COST_FOR_OUTPUT'] = $segments[$i]['GP_FINAL_TREATMENT_COST'];
					array_push($project_list, $segments[$i]);
					// xdebug_break();
					// debug purpose
					// $this->print_segment($segments, $i);
				}
				else {
					$segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT'] = "Do Nothing";
					$segments[$i]['GP_FINAL_TREATMENT_COST_FOR_OUTPUT'] = 0;
					array_push($project_list, $segments[$i]);
				}

				for ($j=0 ; $j<count($rehap_type) ; $j++) {
					unset($segments[$i]['INIT_COST_'.$rehap_type[$j]]);
					unset($segments[$i]['AHP_SCORE_'.$rehap_type[$j]]);
					unset($segments[$i]['BENEFIT_'.$rehap_type[$j]]);
					
				}
				
				unset($segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT']);
				unset($segments[$i]['GP_FINAL_TREATMENT_COST_FOR_OUTPUT']);
				$segments[$i]['GP_FORCED'] = null;
				
			}			
			// printf("Input\n");
			// xdebug_break();
			// for ($i=0 ; $i<$num_segments ; $i++)
			// 	$this->print_segment($segments, $i);
			// xdebug_break();
			$chart_data = array(
				"INIT_AVG_DS"=>$init_avg_ds/$total_lane_miles,
				"INIT_AVG_CS"=>$init_avg_cs/$total_lane_miles,
				"INIT_AVG_RIDE"=>$init_avg_ride/$total_lane_miles,
				"AFTER_MNR_AVG_DS"=>$after_mnr_avg_ds/$total_lane_miles,
				"AFTER_MNR_AVG_CS"=>$after_mnr_avg_cs/$total_lane_miles,
				"AFTER_MNR_AVG_RIDE"=>$after_mnr_avg_ride/$total_lane_miles,
				"FINAL_AVG_DS"=>$final_avg_ds/$total_lane_miles,
				"FINAL_AVG_CS"=>$final_avg_cs/$total_lane_miles,
				"FINAL_AVG_RIDE"=>$final_avg_ride/$total_lane_miles,
				"INIT_MIN_DS"=>$init_min_ds,
				"INIT_MIN_CS"=>$init_min_cs,
				"INIT_MIN_RIDE"=>$init_min_ride,
				"AFTER_MNR_MIN_DS"=>$after_mnr_min_ds,
				"AFTER_MNR_MIN_CS"=>$after_mnr_min_cs,
				"AFTER_MNR_MIN_RIDE"=>$after_mnr_min_ride,
				"FINAL_MIN_DS"=>$final_min_ds,
				"FINAL_MIN_CS"=>$final_min_cs,
				"FINAL_MIN_RIDE"=>$final_min_ride,
				"INIT_ABOVE_THRESHOLD"=>$init_above_threshold/$total_lane_miles*100,
				"INIT_BELOW_THRESHOLD"=>$init_below_threshold/$total_lane_miles*100,
				"FINAL_ABOVE_THRESHOLD"=>$final_above_threshold/$total_lane_miles*100,
				"FINAL_BELOW_THRESHOLD"=>$final_below_threshold/$total_lane_miles*100,
				"INIT_A"=>$init_A/$total_lane_miles*100,
				"INIT_B"=>$init_B/$total_lane_miles*100,
				"INIT_C"=>$init_C/$total_lane_miles*100,
				"INIT_D"=>$init_D/$total_lane_miles*100,
				"INIT_F"=>$init_F/$total_lane_miles*100,
				"FINAL_A"=>$final_A/$total_lane_miles*100,
				"FINAL_B"=>$final_B/$total_lane_miles*100,
				"FINAL_C"=>$final_C/$total_lane_miles*100,
				"FINAL_D"=>$final_D/$total_lane_miles*100,
				"FINAL_F"=>$final_F/$total_lane_miles*100,
				"BACKLOG_LANE_COST_TOTAL_PM"=>round($output['BACKLOG_LANE_COST_TOTAL_PM'],2),
				"BACKLOG_LANE_COST_TOTAL_LR"=>round($output['BACKLOG_LANE_COST_TOTAL_LR'],2),
				"BACKLOG_LANE_COST_TOTAL_MR"=>round($output['BACKLOG_LANE_COST_TOTAL_MR'],2),
				"BACKLOG_LANE_COST_TOTAL_HR"=>round($output['BACKLOG_LANE_COST_TOTAL_HR'],2),
				"YEAR"=>$currentyear
			);

			// update
			$segments['CHART_DATA'] = $chart_data;
			$segments['OUTPUT_STAT'] = $output;
			$segments['PROJECT_LIST'] = $project_list;
			$this->segments = $segments;
			// return $segments;
			return $this->segments;
		}
		else {

			/**
			 * segmentation method 2
			 */
			// xdebug_break();
			

			$sections = $in_sections;
			$num_sections = count($sections);
			$num_segments = count($segments);
			$k=0;
			$num_segments = count($segments);
			for ($i=0 ; $i<$num_segments ; $i++) {
				unset($segments[$i]['GP_MIN_CS']);
				unset($segments[$i]['GP_MIN_DS']);
				unset($segments[$i]['GP_MIN_RIDE']);			
				unset($segments[$i]['MODULE4_DATA']);
				/*
				 * output
				 */
				$length = $segments[$i]['GP_LENGTH'] * $segments[$i]['GP_NUMBER_THRU_LANES'];
				$cost = $segments[$i]['GP_FINAL_TREATMENT_COST'];
				switch ($segments[$i]['GP_FORCED']) {
					// forced project outputs
					case 'PM':
						$output['FORCED_LANE_MILES_TOTAL_PM'] += $length;
						$output['LANE_COST_TOTAL_PM'] += $cost;
						break;
					case 'LR':
						$output['FORCED_LANE_MILES_TOTAL_LR'] += $length;
						$output['LANE_COST_TOTAL_LR'] += $cost;
						break;
					case 'MR':
						$output['FORCED_LANE_MILES_TOTAL_MR'] += $length;
						$output['LANE_COST_TOTAL_MR'] += $cost;
						break;
					case 'HR':
						$output['FORCED_LANE_MILES_TOTAL_HR'] += $length;
						$output['LANE_COST_TOTAL_HR'] += $cost;
						break;					
					default:
						break;
				}
				// non forced project outputs
				switch ($segments[$i]['GP_FINAL_TREATMENT']) {
					case 'PM':
						if ($segments[$i]['GP_FORCED'] == null) {
							$output['LANE_MILES_TOTAL_PM'] += $length;
							$output['LANE_COST_TOTAL_PM'] += $cost;
						}
						break;
					case 'LR':
						if ($segments[$i]['GP_FORCED'] == null) {
							$output['LANE_MILES_TOTAL_LR'] += $length;
							$output['LANE_COST_TOTAL_LR'] += $cost;
						}
						break;
					case 'MR':
						if ($segments[$i]['GP_FORCED'] == null) {
							$output['LANE_MILES_TOTAL_MR'] += $length;
							$output['LANE_COST_TOTAL_MR'] += $cost;
						}
						break;
					case 'HR':
						if ($segments[$i]['GP_FORCED'] == null) {
							$output['LANE_MILES_TOTAL_HR'] += $length;
							$output['LANE_COST_TOTAL_HR'] += $cost;
						}
						break;			
					case 'M&R NOT FUNDED':	
						// xdebug_break();
						$cost = $segments[$i]['INIT_COST_'.$segments[$i]['THEORETICAL_TREATMENT']];
						switch ($segments[$i]['THEORETICAL_TREATMENT']) {
							case 'PM':
								$output['BACKLOG_LANE_MILES_TOTAL_PM'] += $length;
								$output['BACKLOG_LANE_COST_TOTAL_PM'] += $cost;
								break;
							case 'LR':
								$output['BACKLOG_LANE_MILES_TOTAL_LR'] += $length;
								$output['BACKLOG_LANE_COST_TOTAL_LR'] += $cost;
								break;
							case 'MR':
								$output['BACKLOG_LANE_MILES_TOTAL_MR'] += $length;
								$output['BACKLOG_LANE_COST_TOTAL_MR'] += $cost;
								break;
							case 'HR':
								$output['BACKLOG_LANE_MILES_TOTAL_HR'] += $length;
								$output['BACKLOG_LANE_COST_TOTAL_HR'] += $cost;
								break;										
							default:
								# code...
								break;
						}
						break;				
					default:
						# code...
						break;	
					
				}

				for ($j=0 ; $j<count($rehap_type) ; $j++) {
					unset($segments[$i]['LTPB_'.$rehap_type[$j]]);
					unset($segments[$i]['LCC_'.$rehap_type[$j]]);
					// unset($segments[$i]['INIT_COST_'.$rehap_type[$j]]);
					// unset($segments[$i]['AHP_SCORE_'.$rehap_type[$j]]);
					// unset($segments[$i]['BENEFIT_'.$rehap_type[$j]]);
					unset($segments[$i]['GP_START_VALUE_'.$rehap_type[$j]]);
				}

				/*
				 * project list
				 */
				if ($segments[$i]['GP_FINAL_TREATMENT'] != "DN" && $segments[$i]['GP_FINAL_TREATMENT'] != "M&R NOT FUNDED") {
					if ($segments[$i]['GP_FINAL_TREATMENT'] == "LR" && $segments[$i]['CORRECTION_TYPE'] == "PM/LR" && $segments[$i]['GP_FORCED'] == null)
						$segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT'] = "PM/LR";	
					else if ($segments[$i]['GP_FINAL_TREATMENT'] == "MR" && $segments[$i]['CORRECTION_TYPE'] == "LR/MR" && $segments[$i]['GP_FORCED'] == null)
						$segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT'] = "LR/MR";	
					else if ($segments[$i]['GP_FINAL_TREATMENT'] == "MR" && $segments[$i]['CORRECTION_TYPE'] == "PM/MR" && $segments[$i]['GP_FORCED'] == null)
						$segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT'] = "PM/MR";	
					else
						$segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT'] = $segments[$i]['GP_FINAL_TREATMENT'];
					if ($segments[$i]['GP_FORCED'] != null)
						$segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT'] .= " (F)";
					$segments[$i]['GP_FINAL_TREATMENT_COST_FOR_OUTPUT'] = $segments[$i]['GP_FINAL_TREATMENT_COST'];
					// array_push($project_list, $segments[$i]);
					// xdebug_break();
					// debug purpose
					// $this->print_segment($segments, $i);
				}
				else {
					$segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT'] = "Do Nothing";
					$segments[$i]['GP_FINAL_TREATMENT_COST_FOR_OUTPUT'] = 0;
					// array_push($project_list, $segments[$i]);
				}

				// for ($j=0 ; $j<count($rehap_type) ; $j++) {
				// 	unset($segments[$i]['INIT_COST_'.$rehap_type[$j]]);
				// 	unset($segments[$i]['AHP_SCORE_'.$rehap_type[$j]]);
				// 	unset($segments[$i]['BENEFIT_'.$rehap_type[$j]]);
					
				// }
				
				// unset($segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT']);
				// unset($segments[$i]['GP_FINAL_TREATMENT_COST_FOR_OUTPUT']);
				// $segments[$i]['GP_FORCED'] = null;
			}

			// $project_list = array();
			// segments to sections
			for ($i=0 ; $i<$num_segments ; $i++) {
				// if ($segments[$i]['SIGNED_HIGHWAY_RDBD_ID'] == "FM0003 K")
				// 	xdebug_break();

				// if ($sections[$k]['SIGNED_HIGHWAY_RDBD_ID'] == "FM0003 K")
				// 	xdebug_break();		
				
				$highway = $segments[$i]['SIGNED_HIGHWAY_RDBD_ID'];
				$beg_id = $segments[$i]['GP_BEG_ID'];
				$end_id = $segments[$i]['GP_END_ID'];
				// $sec_beg = floatval($sections[$k]['BEG_REF_MARKER_NBR']) + floatval($sections[$k]['BEG_REF_MARKER_DISP']);
				// $sec_end = floatval($sections[$k]['END_REF_MARKER_NBR']) + floatval($sections[$k]['END_REF_MARKER_DISP']);
				// $seg_beg = floatval($segments[$i]['BEG_REF_MARKER_NBR']) + floatval($segments[$i]['BEG_REF_MARKER_DISP']);
				// $seg_end = floatval($segments[$i]['END_REF_MARKER_NBR']) + floatval($segments[$i]['END_REF_MARKER_DISP']);
				$stop = false;
				while ($k<$num_sections) {
					
					// while ($sections[$k]['SIGNED_HIGHWAY_RDBD_ID'] == $highway && ($sec_beg >= $seg_beg || $stop)
					// 	&& ($sec_beg < $seg_end || (($sec_end==$seg_end) && ($sec_beg>=$seg_beg)) ||
					// 		(($sec_end==$seg_end) && ($sec_beg >= $seg_beg)))) {
					// xdebug_break();
					while ($sections[$k]['ID'] >= $beg_id && $sections[$k]['ID'] <= $end_id) {
						// if ($k == 2754)
						// 	xdebug_break();
						$sections[$k]['FINAL_TREATMENT'] = $segments[$i]['GP_FINAL_TREATMENT'];
						$sections[$k]['FINAL_TREATMENT_COST'] = $segments[$i]['GP_FINAL_TREATMENT_COST'];
						$sections[$k]['FORCED'] = $segments[$i]['GP_FORCED'];
						$sections[$k]['THEORETICAL_TREATMENT'] = $segments[$i]['THEORETICAL_TREATMENT'];
						$k++;
						// $sec_beg = floatval($sections[$k]['BEG_REF_MARKER_NBR']) + floatval($sections[$k]['BEG_REF_MARKER_DISP']);
						// $sec_end = floatval($sections[$k]['END_REF_MARKER_NBR']) + floatval($sections[$k]['END_REF_MARKER_DISP']);
						$stop = true;
					}
					if ($stop || $sections[$k]['SIGNED_HIGHWAY_RDBD_ID'] > $highway || $k==$num_sections) {
						
						break;
					}
					$sections[$k]['GP_FINAL_TREATMENT_FOR_OUTPUT']  = "Need Nothing";
					$sections[$k]['GP_FINAL_TREATMENT_COST_FOR_OUTPUT'] = 0;
					$sections[$k]['FINAL_TREATMENT'] = "DN";
					$sections[$k]['FINAL_TREATMENT_COST'] = 0;
					$sections[$k]['FORCED'] = $segments[$i]['GP_FORCED'];
					$sections[$k]['THEORETICAL_TREATMENT'] = $segments[$i]['THEORETICAL_TREATMENT'];
					$k++;
					// $sec_beg = floatval($sections[$k]['BEG_REF_MARKER_NBR']) + floatval($sections[$k]['BEG_REF_MARKER_DISP']);
					// $sec_end = floatval($sections[$k]['END_REF_MARKER_NBR']) + floatval($sections[$k]['END_REF_MARKER_DISP']);
				}
			}

			// xdebug_break();
			// update sections
			for ($i=0 ; $i<$num_sections ; $i++) {		
				// if ($sections[$i]['SIGNED_HIGHWAY_RDBD_ID'] == "FM0003 K" &&
					// $sections[$i]['BEG_REF_MARKER_NBR'] == "0374")
				// if ($sections[$i]['SIGNED_HIGHWAY_RDBD_ID'] == "FM0244 K" &&
				// 	$sections[$i]['BEG_REF_MARKER_NBR'] == "0410")
				// 	xdebug_break();		
					// xdebug_break();		
				// identify ESAL CLASS
				if ($sections[$i]['CURRENT_18KIP_MEAS'] < 1000)
					$esal_class = "Low";
				else if ($sections[$i]['CURRENT_18KIP_MEAS'] < 10000)
					$esal_class	= "Med";
				else
					$esal_class = "High";

				$traffic_speed = $sections[$i]['AADT'] * $sections[$i]['SPEED_LIMIT_MAX'];
				if ($traffic_speed <= 27500)
					$traffic_speed_class = "Low";
				else if ($traffic_speed <= 165000)
					$traffic_speed_class = "Med";
				else
					$traffic_speed_class = "High";

				$init_cs = $sections[$i]['ADJ_CONDITION_SCORE'];
				$init_ds = $sections[$i]['ADJ_DISTRESS_SCORE'];
				$init_ride = $sections[$i]['ADJ_RIDE_SCORE'];

				// compute ds,ride,cs start
				if ($sections[$i]['FINAL_TREATMENT'] == "DN" || $sections[$i]['FINAL_TREATMENT'] == "M&R NOT FUNDED") {
					$ds_start = $sections[$i]['ADJ_DISTRESS_SCORE'];
					$ride_start = $sections[$i]['ADJ_RIDE_SCORE'];
					$cs_start = $sections[$i]['ADJ_CONDITION_SCORE'];
					// print $cs_start."\n";
					// if ($sections[$i]['SIGNED_HIGHWAY_RDBD_ID'] == "FM0166 K" &&
					// $sections[$i]['BEG_REF_MARKER_DISP'] == 0 &&
					// $sections[$i]['BEG_REF_MARKER_NBR'] == 600
					// )
					// xdebug_break();
				}
				else {
					$params = $this->get_cs_start($sections[$i]['ADJ_RIDE_SCORE'], $sections[$i]['FINAL_TREATMENT'], $traffic_speed_class, $ridecoeff,
						$pmrsincreaseby, $pmdsresetto, $lrrsincreaseby, $lrdsresetto, $mrrsresetto, $mrdsresetto, $hrrsresetto, $hrdsresetto);
					// print $sections[$i]['FINAL_TREATMENT']."\n";
					$ride_start = $params['RIDE_START'];
					$ds_start = $params['DS_START'];
					$cs_start = $params['URIDE'] * $ds_start;
					// if ($sections[$i]['SIGNED_HIGHWAY_RDBD_ID'] == "FM0166 K" &&
					// $sections[$i]['BEG_REF_MARKER_DISP'] == 0 &&
					// $sections[$i]['BEG_REF_MARKER_NBR'] == 600
					// )
					// xdebug_break();
				}

				$after_mnr_cs = $cs_start;
				$after_mnr_ds = $ds_start;
				$after_mnr_ride = $ride_start;

				// for funded projects, we use GP_FINAL_TREATMENT
				if ($sections[$i]['FINAL_TREATMENT'] != "DN" && $sections[$i]['FINAL_TREATMENT'] != "M&R NOT FUNDED") {
					// get the last treatment
					$last_treatment = $sections[$i]['FINAL_TREATMENT'];

					$pvmt_family = $this->get_pvmnt_family($sections[$i]['PVMNT_TYPE_DTL_RD_LIFE_CODE']);

					// get cs and ds coefficient for model
					$cs_coeff = $this->cs_filter_by_value($cs_coeffs[$last_treatment], $last_treatment, $pvmt_family, $esal_class, $traffic_speed_class);
					$ds_coeff = $this->ds_filter_by_value($ds_coeffs[$last_treatment], $last_treatment, $pvmt_family, $esal_class);

					$year_of_treatment = $currentyear;
					$ds_year_treatment = $currentyear;
					$cs_year_treatment = $currentyear;
				}
				// for not funded projects, we use GP_PRIOR_TREATMENT
				else {
					$last_treatment = $sections[$i]['PRIOR_TREATMENT'];
					$year_of_treatment = $sections[$i]['YEAR_OF_PRIOR_TREATMENT'];				

					$pvmt_family = $this->get_pvmnt_family($sections[$i]['PVMNT_TYPE_DTL_RD_LIFE_CODE']);

					// print $last_treatment.",".$year_of_treatment.",".$pvmt_family."\n";

					$cs_coeff = $this->cs_filter_by_value($cs_coeffs[$last_treatment], $last_treatment, $pvmt_family, $esal_class, $traffic_speed_class);
					$ds_coeff = $this->ds_filter_by_value($ds_coeffs[$last_treatment], $last_treatment, $pvmt_family, $esal_class);	
					
					if ($cs_coeff['Beta'] != 0)
						$denominator = pow(-log(1-min(99.9999999,$cs_start)/100),1/$cs_coeff['Beta']);
					else
						$denominator = 0;
					
					if ($denominator != 0)
						$cs_year_treatment = $currentyear - round($cs_coeff['Rho']/$denominator);
					else {
						// print "error";
						// xdebug_break();
					}
						
					if ($ds_coeff['Beta'] != 0)
						$denominator = pow(-log(1-min(99.9999999,$ds_start)/100),1/$ds_coeff['Beta']);
					else
						$denominator = 0;
					
					if ($denominator != 0)
						$ds_year_treatment = $currentyear - round($ds_coeff['Rho']/$denominator);
					else {
						// print "error";
						// xdebug_break();
					}
				}
			
				/*
				 * for next year, predict score
				 */
				$next_year = $currentyear + 1;
				$ds_based_age = $next_year - $ds_year_treatment;
				$cs_based_age = $next_year - $cs_year_treatment;

				// cs, ds, uride prediction based on model
				$predicted_ds = 100 * (1-exp(-(pow($ds_coeff['Rho']/$ds_based_age, $ds_coeff['Beta']))));
				// xdebug_break();
				// for funded project
				if ($sections[$i]['FINAL_TREATMENT'] != "DN" && $sections[$i]['FINAL_TREATMENT'] != "M&R NOT FUNDED") {
					$predicted_cs = $cs_start * (1-exp(-(pow($cs_coeff['Rho']/$cs_based_age, $cs_coeff['Beta']))));
				}
				else
					$predicted_cs = min($predicted_ds, $cs_start);
				
				$predicted_uride = min($predicted_cs/$predicted_ds, 1);
				
				// li prediction
				if ($predicted_uride == 1)
					$predicted_li = 1;
				else {
					$ride_coeff = $this->ride_filter_by_value($ridecoeff, $traffic_speed_class);
					if ($ride_coeff['Beta'] != 0 && $ride_coeff['Alpha'] != 0)
						$denominator = pow(-log((1-$predicted_uride)/$ride_coeff['Alpha']), 1/$ride_coeff['Beta']);
					else
						$denominator = 0;
					
					if ($denominator == 0) {
						// print "error at module4 line 356";
						// xdebug_break();
						// var_dump($segments[$i]);
					}
					else
						$predicted_li = $ride_coeff['Rho']/($denominator);
				}

				// if ($segments[$i]['SIGNED_HIGHWAY_RDBD_ID'] == 'FM0158 R')
				// 	xdebug_break();

				// ride prediction
				if ($traffic_speed_class == "Low")
					$predicted_ride = 2.5-(0.025*$predicted_li);
				else if ($traffic_speed_class == "Med")
					$predicted_ride = 3-(0.03*$predicted_li);
				else
					$predicted_ride = 3.5-(0.035*$predicted_li);

				// Check
				// if ($predicted_ride<=0)
				// 	xdebug_break();			

				// if ($sections[$i]['SIGNED_HIGHWAY_RDBD_ID'] == "FM1452 K" &&
				// 	$sections[$i]['BEG_REF_MARKER_NBR'] == "0628")
				// 	xdebug_break();		

				// ready data for next iteration
				$sections[$i]['ADJ_CONDITION_SCORE'] = $predicted_cs;
				$sections[$i]['ADJ_DISTRESS_SCORE'] = $predicted_ds;
				$sections[$i]['ADJ_RIDE_SCORE'] = $predicted_ride;
				$sections[$i]['AADT'] = round($sections[$i]['AADT']*($aadtgrowthrate/100+1));
				$sections[$i]['PRIOR_TREATMENT'] = $last_treatment;
				$sections[$i]['YEAR_OF_PRIOR_TREATMENT'] = $year_of_treatment;
				$sections[$i]['RATE_OF_DETERIORATION'] = 0;			
				$sections[$i]['SKID_RESIST'] = null;
				$sections[$i]['SCI'] = null;
				$sections[$i]['VISUAL_ASSESSMENT'] = null;

				$final_cs = $sections[$i]['ADJ_CONDITION_SCORE'];
				$final_ds = $sections[$i]['ADJ_DISTRESS_SCORE'];
				$final_ride = $sections[$i]['ADJ_RIDE_SCORE'];

				/* 
				 * for chart data
				 */
				$length = $sections[$i]['SECT_LENGTH'] * $sections[$i]['NUMBER_THRU_LANES'];
				$init_avg_cs += $length * $init_cs;
				$init_avg_ds += $length * $init_ds;
				$init_avg_ride += $length * $init_ride;
				$after_mnr_avg_cs += $length * $after_mnr_cs;
				$after_mnr_avg_ds += $length * $after_mnr_ds;
				$after_mnr_avg_ride += $length * $after_mnr_ride;
				$final_avg_cs += $length * $final_cs;
				$final_avg_ds += $length * $final_ds;
				$final_avg_ride += $length * $final_ride;
				
				if ($init_cs <= $init_min_cs)
					$init_min_cs = $init_cs;

				if ($init_ds <= $init_min_ds)
					$init_min_ds = $init_ds;
				
				if ($init_ride <= $init_min_ride)
					$init_min_ride = $init_ride;
				
				if ($after_mnr_cs <= $after_mnr_min_cs)
					$after_mnr_min_cs = $after_mnr_cs;
				
				if ($after_mnr_ds <= $after_mnr_min_ds)
					$after_mnr_min_ds = $after_mnr_ds;
				
				if ($after_mnr_ride <= $after_mnr_min_ride)
					$after_mnr_min_ride = $after_mnr_ride;
				
				if ($final_cs <= $final_min_cs)
					$final_min_cs = $final_cs;
				
				if ($final_ds <= $final_min_ds)
					$final_min_ds = $final_ds;
				
				if ($final_ride <= $final_min_ride)
					$final_min_ride = $final_ride;

				if ($init_cs >= 70)
					$init_above_threshold += $length;
				else
					$init_below_threshold += $length;

				if ($final_cs >= 70)
					$final_above_threshold += $length;
				else
					$final_below_threshold += $length;

				if ($init_cs >= 90)
					$init_A += $length;
				else if ($init_cs >= 70)
					$init_B += $length;
				else if ($init_cs >= 50)
					$init_C += $length;
				else if ($init_cs >= 35)
					$init_D += $length;
				else
					$init_F += $length;

				if ($final_cs >= 90)
					$final_A += $length;
				else if ($final_cs >= 70)
					$final_B += $length;
				else if ($final_cs >= 50)
					$final_C += $length;
				else if ($final_cs >= 35)
					$final_D += $length;
				else
					$final_F += $length;

				
				$sections[$i]['FORCED'] = null;
				
			}

			$k = 0;
			for ($i=0 ; $i<$num_segments && $k<$num_sections ; $i++) {				
				$highway = $segments[$i]['SIGNED_HIGHWAY_RDBD_ID'];
				$beg_id = $segments[$i]['GP_BEG_ID'];
				$end_id = $segments[$i]['GP_END_ID'];
				$stop = false;
				while ($k<$num_sections) {
					
					while ($sections[$k]['ID'] >= $beg_id && $sections[$k]['ID'] <= $end_id) {
						$k++;
						// $sec_beg = floatval($sections[$k]['BEG_REF_MARKER_NBR']) + floatval($sections[$k]['BEG_REF_MARKER_DISP']);
						// $sec_end = floatval($sections[$k]['END_REF_MARKER_NBR']) + floatval($sections[$k]['END_REF_MARKER_DISP']);
						$stop = true;
					}
					if ($stop || $sections[$k]['SIGNED_HIGHWAY_RDBD_ID'] > $highway || $k==$num_sections) {
						if ($stop) {
							if ($segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT'] == "Do Nothing") {
								$segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT'] = "Not Funded";
							}
							$segment = array(
								"ANALYSIS_YEAR"=>$currentyear+1,
								"SIGNED_HIGHWAY_RDBD_ID"=>$segments[$i]['SIGNED_HIGHWAY_RDBD_ID'],
								"BEG_REF_MARKER_NBR"=>$segments[$i]['BEG_REF_MARKER_NBR'],
								"BEG_REF_MARKER_DISP"=>$segments[$i]['BEG_REF_MARKER_DISP'],
								"END_REF_MARKER_NBR"=>$segments[$i]['END_REF_MARKER_NBR'],
								"END_REF_MARKER_DISP"=>$segments[$i]['END_REF_MARKER_DISP'],
								"ADJ_CONDITION_SCORE"=>$segments[$i]['GP_CONDITION_SCORE'],
								"ADJ_DISTRESS_SCORE"=>$segments[$i]['GP_DISTRESS_SCORE'],
								// "BENEFIT_PM"=>$segments[$i]["BENEFIT_PM"],
								// "BENEFIT_LR"=>$segments[$i]["BENEFIT_LR"],
								// "BENEFIT_MR"=>$segments[$i]["BENEFIT_MR"],
								// "BENEFIT_HR"=>$segments[$i]["BENEFIT_HR"],
								// "INIT_COST_PM"=>$segments[$i]["INIT_COST_PM"],
								// "INIT_COST_LR"=>$segments[$i]["INIT_COST_LR"],
								// "INIT_COST_MR"=>$segments[$i]["INIT_COST_MR"],
								// "INIT_COST_HR"=>$segments[$i]["INIT_COST_HR"],
								"ADJ_RIDE_SCORE"=>$segments[$i]['GP_RIDE_SCORE'],
								// "GP_TRAFFIC_SPEED_CLASS"=>$segments[$i]['GP_TRAFFIC_SPEED_CLASS'],
								// "GP_AADT"=>$segments[$i]['GP_AADT'],
								"SECT_LENGTH"=>$segments[$i]['GP_LENGTH'],
								"NUMBER_THRU_LANES"=>$segments[$i]['GP_NUMBER_THRU_LANES'],								
								"GP_FINAL_TREATMENT_FOR_OUTPUT"=>$segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT'],
								"GP_FINAL_TREATMENT_COST_FOR_OUTPUT"=>$segments[$i]['GP_FINAL_TREATMENT_COST_FOR_OUTPUT'],
								"SEGMENT_ID"=>$segments[$i]['SEGMENT_ID'],
							);
							// array_push($project_list, $segments[$i]);
							array_push($project_list, $segment);
							unset($segments[$i]['GP_FINAL_TREATMENT_FOR_OUTPUT']);
							unset($segments[$i]['GP_FINAL_TREATMENT_COST_FOR_OUTPUT']);
							$segments[$i]['GP_FORCED'] = null;
						}
						break;
					}
					$sections[$k]['GP_FINAL_TREATMENT_FOR_OUTPUT']  = "Need Nothing";
					$sections[$k]['GP_FINAL_TREATMENT_COST_FOR_OUTPUT'] = 0;
					$section = array(
						"ANALYSIS_YEAR"=>$currentyear+1,
						"SIGNED_HIGHWAY_RDBD_ID"=>$sections[$k]['SIGNED_HIGHWAY_RDBD_ID'],
						"BEG_REF_MARKER_NBR"=>$sections[$k]['BEG_REF_MARKER_NBR'],
						"BEG_REF_MARKER_DISP"=>$sections[$k]['BEG_REF_MARKER_DISP'],
						"END_REF_MARKER_NBR"=>$sections[$k]['END_REF_MARKER_NBR'],
						"END_REF_MARKER_DISP"=>$sections[$k]['END_REF_MARKER_DISP'],
						"ADJ_CONDITION_SCORE"=>$sections[$k]['ADJ_CONDITION_SCORE'],
						"ADJ_DISTRESS_SCORE"=>$sections[$k]['ADJ_DISTRESS_SCORE'],
						"ADJ_RIDE_SCORE"=>$sections[$k]['ADJ_RIDE_SCORE'],
						// "AADT"=>$sections[$i]['AADT'],
						"SECT_LENGTH"=>$sections[$k]['SECT_LENGTH'],
						"NUMBER_THRU_LANES"=>$sections[$k]['NUMBER_THRU_LANES'],						
						"GP_FINAL_TREATMENT_FOR_OUTPUT"=>"Need Nothing",
						"GP_FINAL_TREATMENT_COST_FOR_OUTPUT"=>0);
					array_push($project_list, $section);
					$k++;
				}
			}
			// xdebug_break();		
			// printf("Input\n");
			// xdebug_break();
			// for ($i=0 ; $i<$num_segments ; $i++)
			// 	$this->print_segment($segments, $i);
			// xdebug_break();
			$chart_data = array(
				"INIT_AVG_DS"=>$init_avg_ds/$total_lane_miles,
				"INIT_AVG_CS"=>$init_avg_cs/$total_lane_miles,
				"INIT_AVG_RIDE"=>$init_avg_ride/$total_lane_miles,
				"AFTER_MNR_AVG_DS"=>$after_mnr_avg_ds/$total_lane_miles,
				"AFTER_MNR_AVG_CS"=>$after_mnr_avg_cs/$total_lane_miles,
				"AFTER_MNR_AVG_RIDE"=>$after_mnr_avg_ride/$total_lane_miles,
				"FINAL_AVG_DS"=>$final_avg_ds/$total_lane_miles,
				"FINAL_AVG_CS"=>$final_avg_cs/$total_lane_miles,
				"FINAL_AVG_RIDE"=>$final_avg_ride/$total_lane_miles,
				"INIT_MIN_DS"=>$init_min_ds,
				"INIT_MIN_CS"=>$init_min_cs,
				"INIT_MIN_RIDE"=>$init_min_ride,
				"AFTER_MNR_MIN_DS"=>$after_mnr_min_ds,
				"AFTER_MNR_MIN_CS"=>$after_mnr_min_cs,
				"AFTER_MNR_MIN_RIDE"=>$after_mnr_min_ride,
				"FINAL_MIN_DS"=>$final_min_ds,
				"FINAL_MIN_CS"=>$final_min_cs,
				"FINAL_MIN_RIDE"=>$final_min_ride,
				"INIT_ABOVE_THRESHOLD"=>$init_above_threshold/$total_lane_miles*100,
				"INIT_BELOW_THRESHOLD"=>$init_below_threshold/$total_lane_miles*100,
				"FINAL_ABOVE_THRESHOLD"=>$final_above_threshold/$total_lane_miles*100,
				"FINAL_BELOW_THRESHOLD"=>$final_below_threshold/$total_lane_miles*100,
				"INIT_A"=>$init_A/$total_lane_miles*100,
				"INIT_B"=>$init_B/$total_lane_miles*100,
				"INIT_C"=>$init_C/$total_lane_miles*100,
				"INIT_D"=>$init_D/$total_lane_miles*100,
				"INIT_F"=>$init_F/$total_lane_miles*100,
				"FINAL_A"=>$final_A/$total_lane_miles*100,
				"FINAL_B"=>$final_B/$total_lane_miles*100,
				"FINAL_C"=>$final_C/$total_lane_miles*100,
				"FINAL_D"=>$final_D/$total_lane_miles*100,
				"FINAL_F"=>$final_F/$total_lane_miles*100,
				"BACKLOG_LANE_COST_TOTAL_PM"=>round($output['BACKLOG_LANE_COST_TOTAL_PM'],2),
				"BACKLOG_LANE_COST_TOTAL_LR"=>round($output['BACKLOG_LANE_COST_TOTAL_LR'],2),
				"BACKLOG_LANE_COST_TOTAL_MR"=>round($output['BACKLOG_LANE_COST_TOTAL_MR'],2),
				"BACKLOG_LANE_COST_TOTAL_HR"=>round($output['BACKLOG_LANE_COST_TOTAL_HR'],2),
				"YEAR"=>$currentyear
			);	
			// xdebug_break();

			// update
			$sections['CHART_DATA'] = $chart_data;
			$sections['OUTPUT_STAT'] = $output;
			$sections['PROJECT_LIST'] = $project_list;
			$this->sections = $sections;
			return $sections;
		}
	}	

	// get condition score start
	private function get_cs_start($ride_score, $rehap_type, $traffic_speed_class, $ridecoeff,
		$pmrsincreaseby, $pmdsresetto, $lrrsincreaseby, $lrdsresetto, $mrrsresetto, $mrdsresetto, $hrrsresetto, $hrdsresetto) {
		$params = array();
		switch ($rehap_type) {
			case "PM":
				$factor = $pmrsincreaseby;
				$ds_start = $pmdsresetto;
				break;
			case "LR":
				$factor = $lrrsincreaseby;
				$ds_start = $lrdsresetto;
				break;
			case "MR":
				$factor = $mrrsresetto;
				$ds_start = $mrdsresetto;
				break;
			default:
			case "HR":
				$factor = $hrrsresetto;
				$ds_start = $hrdsresetto;
				break;
		};

		$params['DS_START'] = $ds_start;

		$ride_start = min(4.8,$ride_score + $factor);
		$params['RIDE_START'] = $ride_start;
		
		if ($traffic_speed_class == "Low") 
			$li = 100 * ((2.5-$ride_start)/2.5);
		else if ($traffic_speed_class == "Med")
			$li = 100 * ((3-$ride_start)/3);
		else
			$li = 100 * ((3.5-$ride_start)/3.5);
		$li = $li<=0?0:$li;
		$params['LI'] = $li;
		$coeff = $this->ride_filter_by_value($ridecoeff, $traffic_speed_class);		
		$uride = $li==0?1:(1-($coeff['Alpha']*exp(-(pow($coeff['Rho']/$li, $coeff['Beta'])))));
		$params['URIDE'] = $uride;

		//************************************************************
		// 5 is fixed???????????
		//************************************************************
		$params['CS_START'] = $ds_start*$uride<$this->mnrtriggervalue+5?0:$ds_start*$uride;
		return $params;
	}

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


	private	function cs_filter_by_value ($array, $rehap_type, $pvttype, $esalclass, $trafficspeed) { 
        if (is_array($array) && count($array)>0)  
        { 
            foreach(array_keys($array) as $key){ 
                $temp[$key] = $array[$key]; 
                 
                if ($temp[$key]['Pavement Type'] == $pvttype &&
                	$temp[$key]['ESAL Class'] == $esalclass &&
                	$temp[$key]['Traffic Speed'] == $trafficspeed) { 
                    $newarray = $array[$key]; 
                } 
            } 
        } 
      	return $newarray; 
    }

    private	function ds_filter_by_value ($array, $rehap_type, $pvttype, $esalclass) { 
        if (is_array($array) && count($array)>0)  
        { 
            foreach(array_keys($array) as $key){ 
                $temp[$key] = $array[$key]; 
                 
                if ($temp[$key]['Pavement Type'] == $pvttype &&
                	$temp[$key]['ESAL Class'] == $esalclass) { 
                    $newarray = $array[$key]; 
                } 
            } 
        } 
      	return $newarray; 
    }

    private function unit_cost_filter_by_value ($array, $rehap_type) {
   		if (is_array($array) && count($array)>0)  
        { 
            foreach(array_keys($array) as $key){ 
                $temp[$key] = $array[$key]; 
                 
                if ($temp[$key]['M&R Type'] == $rehap_type) { 
                    $newarray = $array[$key]; 
                } 
            } 
        } 
      	return $newarray; 
    }

    private	function ride_filter_by_value ($array, $trafficspeed) {    	
        if (is_array($array) && count($array)>0)  
        { 
            foreach(array_keys($array) as $key){ 
                $temp[$key] = $array[$key]; 
                 
                if ($temp[$key]['AADT x Speed'] == $trafficspeed) { 
                    $newarray = $array[$key]; 
                } 
            } 
        } 
      	return $newarray; 
    } 
	
	
	// for debugging purpose	
	function print_bc($segments, $i, $rehap_type, $init_cost, $ltpb, $lcc, $bc_rate) {
		printf("%s, %s, %s, %s, %s, %s, %f, %f, %f, %f\n", $segments[$i]['SIGNED_HIGHWAY_RDBD_ID'], $segments[$i]['BEG_REF_MARKER_NBR'],
		$segments[$i]['BEG_REF_MARKER_DISP'], $segments[$i]['END_REF_MARKER_NBR'], $segments[$i]['END_REF_MARKER_DISP'], $rehap_type,
		$init_cost, $ltpb, $lcc, $bc_rate);
	}

	function print_segment($segments, $i) {
		printf("%s,%s,%s,%s,%s,%s,%s,%f,%f,%f,$f\n", $segments[$i]['SIGNED_HIGHWAY_RDBD_ID'], $segments[$i]['BEG_REF_MARKER_NBR'],
		$segments[$i]['BEG_REF_MARKER_DISP'], $segments[$i]['END_REF_MARKER_NBR'], $segments[$i]['END_REF_MARKER_DISP'],
		$segments[$i]['GP_FINAL_TREATMENT'], $segments[$i]['GP_CS_RELIAB'], $segments[$i]['GP_DS_RELIAB'], $segments[$i]['GP_RIDE_RELIAB'],
		$segments[$i]['GP_FINAL_TREATMENT_COST']);
	}
};

$module4 = new Module4;
?>