<?php

class Module3 {	
	private $user_id;
	private $segments;
	private $district;
	private $init_cost;
	private $mnrtriggervalue;
	private $ltpb;
	private $lcc;
	
	function Module3() {
		ini_set('max_execution_time', 0);
		global $session;
		global $database;	
		$this->district = $_SESSION['district'];
		$this->user_id = $session->user_id;
	}
	
	public function do_analysis($in_segments, $cscoeffpm, $cscoefflr, $cscoeffmr, $cscoeffhr, $ridecoeff, $benefitcsthreshold, $unitcost, $discountrate,
		$mnrtriggerparam, $mnrtriggervalue, $pmviabilityvalue, $lrviabilityvalue, $wtpcc, $wtctv, $wtic, $wtltpb, $wtlcc, $wtcsds, $wtride, $wtrod, $wtskid, $wtsci, $wtva, $wtaadt, $wttaadt,
		$pmrsincreaseby, $pmdsresetto, $lrrsincreaseby, $lrdsresetto, $mrrsresetto, $mrdsresetto, $hrrsresetto, $hrdsresetto, $seg_table_name, $first_year) {
		// xdebug_break();
		global $session;
   		global $database;

   		$viability_pm_min = $pmviabilityvalue;
   		$viability_lr_min = $lrviabilityvalue;

		// initialize parameters and variables
		$analysis_period = 20;
   		$rehap_type = array("PM", "LR", "MR", "HR");
		$cs_coeffs = array("PM"=>$cscoeffpm, "LR"=>$cscoefflr, "MR"=>$cscoeffmr, "HR"=>$cscoeffhr);

		$this->mnrtriggervalue = $mnrtriggervalue;

   		if ($ridecoeff == null) {
   			$query = "SELECT * FROM ride_coeff_$this->district WHERE `Setting Name`='Default'";
   			$ridecoeff = $database->Query($query);
   		}

   		if ($unitcost == null) {
    		// need to query default param
    		$query = "SELECT * FROM unit_cost_$this->district";
    		$unitcost = $database->Query($query);
    	}

		for ($i=0 ; $i<count($rehap_type) ; $i++) {
			$this->init_cost[$rehap_type[$i]] = array();
			$this->ltpb[$rehap_type[$i]] = array();
			$this->lcc[$rehap_type[$i]] = array();
			if ($cs_coeffs[$rehap_type[$i]] == null) {
				$query = "SELECT * FROM cs_modeling_parameters_$this->district WHERE `Setting Name`='Default' AND `Rehap Type`='$rehap_type[$i]'";
				$cs_coeffs[$rehap_type[$i]] = $database->Query($query);
			}
		}

		// iterate each segment
		$this->segments = $in_segments;
		$num_segments = count($this->segments);
		$segments = $this->segments;

		for ($i=0 ; $i<$num_segments ; $i++) {
			// if ($i==32)
			//	xdebug_break();
			// identify ESAL CLASS
			if ($segments[$i]['GP_ESAL'] < 1000)
				$esal_class = "Low";
			else if ($segments[$i]['GP_ESAL'] < 10000)
				$esal_class	= "Med";
			else
				$esal_class = "High";
			$segments[$i]['GP_ESAL_CLASS'] = $esal_class;

			// identify AADT * Speed
			$traffic_speed = $segments[$i]['GP_AADT'] * $segments[$i]['GP_SPEED_LIMIT_MAX'];
			if ($traffic_speed <= 27500)
				$traffic_speed_class = "Low";
			else if ($traffic_speed <= 165000)
				$traffic_speed_class = "Med";
			else
				$traffic_speed_class = "High";
			$segments[$i]['GP_TRAFFIC_SPEED_CLASS'] = $traffic_speed_class;

			// print $traffic_speed_class."\n";
			/**
			 * April 1, 2013
			 * Check disqualifibility
			 */

			if ($mnrtriggerparam == "Condition Score")
				$viability_score = $segments[$i]['GP_MIN_CS'];
			else
				$viability_score = $segments[$i]['GP_MIN_DS'];			
			
			$bc_ratios = array();
			$cs_start;

			// if ($segments[$i]['SIGNED_HIGHWAY_RDBD_ID'] == "FM0027 K" &&
			// 	$segments[$i]['BEG_REF_MARKER_NBR'] == "0622")
			// 	xdebug_break();
			// if ($i == 101)
				// xdebug_break();

			// for each rehap type
			for ($j=0 ; $j<count($rehap_type) ; $j++) {
				/*
				 * calculate benefit
				 */
				$params = $this->get_cs_start($segments[$i]['GP_RIDE_RELIAB'], $rehap_type[$j], $traffic_speed_class, $ridecoeff
					,$pmrsincreaseby, $pmdsresetto, $lrrsincreaseby, $lrdsresetto, $mrrsresetto, $mrdsresetto, $hrrsresetto, $hrdsresetto);
				$cs_coeff = $this->cs_filter_by_value($cs_coeffs[$rehap_type[$j]], $rehap_type[$j], $segments[$i]['GP_PVMT_FAMILY'], $esal_class, $traffic_speed_class);
				
				// store the result for module4
				// echo $rehap_type[$j]."\n";
				$segments[$i]['GP_START_VALUE_'.$rehap_type[$j]] = $params;
				$cs_start = $params['CS_START'];
				
				if ($cs_start != 0 && $cs_coeff['Beta'] != 0 && $benefitcsthreshold/$cs_start != 1)
					$denominator = pow(-log(1-$benefitcsthreshold/$cs_start),1/$cs_coeff['Beta']);
				else
					$denominator = 0;
				
				if ($denominator != 0)
					$area[$rehap_type[$j]] = $cs_coeff['Rho']/$denominator;
				else
					$area[$rehap_type[$j]] = 0;	

				/**
				 * April 1, 2013
				 * Check disqualifibility
				 */
				// xdebug_break();
				$segments[$i]['VIABILITY_'.$rehap_type[$j]] = true;
				if (($viability_score!=null) && (($rehap_type[$j]=="PM" && $viability_score < $viability_pm_min) ||
					($rehap_type[$j]=="LR" && $viability_score < $viability_lr_min))) {
					// LR disqualified
					$segments[$i]['VIABILITY_'.$rehap_type[$j]] = false;
					$denominator = 0;
				}	

						
				
				if ($denominator != 0)
					$effect_years = floor($cs_coeff['Rho']/$denominator)-1;
				else
					$effect_years = -1;
				
				// get cycles
				$full_cycles = $effect_years+1;
				$n_full_cycles = $full_cycles!=0?floor($analysis_period/$full_cycles):0;
				$partial_cycles = $full_cycles!=0?$analysis_period-$n_full_cycles*$full_cycles:0;

				if ($full_cycles != 0) {
					// using composite simpson's rule for integration from 0 to $full_cycles
					$benefit = $n_full_cycles * $this->simpsonsrule(0, $full_cycles, 30, $cs_coeff, $cs_start, $benefitcsthreshold);
					
					// using composite simpson's rule for integration from 0 to $partial_cycles
					$partial_benefit = $this->simpsonsrule(0, $partial_cycles, 30, $cs_coeff, $cs_start, $benefitcsthreshold);
				}
				// if benefit is not valid, then set to zero
				else {
					$benefit = 0;
					$partial_benefit = 0;
				}

				// compute aupc (long term performance benefit)
				$total_aupc = $benefit + $partial_benefit;
				// print $benefit.",".$partial_benefit."\n";
				$total_aupc_multiplied = $segments[$i]['GP_AADT'] * $total_aupc * $segments[$i]['GP_NUMBER_THRU_LANES'] * $segments[$i]['GP_LENGTH'] * 365;
				$total_aupc_each_year = $total_aupc_multiplied / $analysis_period;

				/**
				 * calculate cost
				 */
				// PM disqualified, it is LR but use PM unit cost
				// xdebug_break();
				$unit_cost = array();
				if ($rehap_type[$j] == "LR" && !$segments[$i]['VIABILITY_PM'] && $area["PM"]!=0) {
					$pm_unit_cost = $this->unit_cost_filter_by_value($unitcost, "PM");
					$lr_unit_cost = $this->unit_cost_filter_by_value($unitcost, "LR");
					$unit_cost['Unit Cost'] = $pm_unit_cost['Unit Cost'] *(1-$segments[$i]['GP_RATIO_BELOW_PM_VIABILITY']) +
											$lr_unit_cost['Unit Cost'] *$segments[$i]['GP_RATIO_BELOW_PM_VIABILITY'];
				}
				// LR disqualified
				else if ($rehap_type[$j] == "MR" && !$segments[$i]['VIABILITY_LR']) {
					if ($area["PM"]!=0) {
						$pm_unit_cost = $this->unit_cost_filter_by_value($unitcost, "PM");
						$mr_unit_cost = $this->unit_cost_filter_by_value($unitcost, "MR");
						$unit_cost['Unit Cost'] = $pm_unit_cost['Unit Cost']*(1-$segments[$i]['GP_RATIO_BELOW_LR_VIABILITY'])+
								$mr_unit_cost['Unit Cost']*$segments[$i]['GP_RATIO_BELOW_LR_VIABILITY'];
					}
					else if ($area["LR"]!=0) {
						$lr_unit_cost = $this->unit_cost_filter_by_value($unitcost, "LR");
						$mr_unit_cost = $this->unit_cost_filter_by_value($unitcost, "MR");
						$unit_cost['Unit Cost'] = $lr_unit_cost['Unit Cost']*(1-$segments[$i]['GP_RATIO_BELOW_LR_VIABILITY'])+
								$mr_unit_cost['Unit Cost']*$segments[$i]['GP_RATIO_BELOW_LR_VIABILITY'];
					}
					else
						$unit_cost = $this->unit_cost_filter_by_value($unitcost, $rehap_type[$j]);
				}
				else
					$unit_cost = $this->unit_cost_filter_by_value($unitcost, $rehap_type[$j]);
				$init_cost = $unit_cost['Unit Cost'] * $segments[$i]['GP_NUMBER_THRU_LANES'] * $segments[$i]['GP_LENGTH'];
				
				// store init cost for later use (AHP score calculation)
				$segments[$i]['INIT_COST_'.$rehap_type[$j]] = $init_cost;
				array_push($this->init_cost[$rehap_type[$j]], $init_cost);

				// compute present value
				$investment = array();
				for ($k=1 ; $k<=$analysis_period ; $k++) {
					if ($full_cycles!=0 && $k%$full_cycles === 0) {
						array_push($investment, $init_cost);
					}
					else
						array_push($investment, 0);
				}
				$pv = $this->npv($discountrate, $investment)+$init_cost;

				// compute salvage
				if ($partial_cycles == 0)
					$unused_years = 0;
				else
					$unused_years = $full_cycles - $partial_cycles;
				$salvage = $full_cycles!=0?$unused_years/$full_cycles*$init_cost:0;
				$sal_pv =$salvage*(1/pow(1+$discountrate, $analysis_period));

				// final net present value
				$npv = $pv - $sal_pv;

				// compute lcc
				$term = pow(1+$discountrate, $analysis_period);
				$euac = $npv * (($discountrate*$term)/($term-1));

				// if ($i == 1)
				// 	xdebug_break();

				if ($total_aupc_each_year!=0) {
					$ltpb = $total_aupc_each_year;					
					$lcc = $euac;

					// store these for later use (AHP score calculation)
					$segments[$i]['LTPB_'.$rehap_type[$j]] = $ltpb;
					array_push($this->ltpb[$rehap_type[$j]], $ltpb);
					$segments[$i]['LCC_'.$rehap_type[$j]] = $lcc;
					// if ($rehap_type[$j] == "LR")
					// 	print $lcc."\n";
					array_push($this->lcc[$rehap_type[$j]], $lcc);

					// compute benefit cost ratio
					if ($lcc != 0)
						$bc_ratio = $ltpb/$lcc;
					else
						$bc_ratio = 0;
					$bc_ratios[$rehap_type[$j]]=$bc_ratio;
				}
				// else
					// print "no area\n";
				// $this->print_bc($segments, $i, $rehap_type[$j], $init_cost, $ltpb, $lcc, $bc_ratio);
			}
			
			if (count($bc_ratios) != 0) {
				$maxs = array_keys($bc_ratios, max($bc_ratios));
				$segments[$i]['THEORETICAL_TREATMENT'] = $maxs[0];
			}
			else {
				// xdebug_break();
				$segments[$i]['THEORETICAL_TREATMENT'] = "NA";
			}

			
			// xdebug_break();
			// pm disqualified
			if (($viability_score!=null) && $viability_score >= $viability_lr_min && $viability_score < $viability_pm_min) {
				if ($area["PM"]!=0 && $area["LR"]!=0)
					$segments[$i]['CORRECTION_TYPE'] = "PM/LR";
				else
					$segments[$i]['CORRECTION_TYPE'] = "No Correction";
			}
			// lr disqualified
			else if (($viability_score!=null) && $viability_score < $viability_lr_min) {
				if ($area["LR"]!=0 && $area["PM"]==0)
					$segments[$i]['CORRECTION_TYPE'] = "LR/MR";
				else if ($area["PM"]!=0 && $area["LR"]!=0)
					$segments[$i]['CORRECTION_TYPE'] = "PM/MR";
				else
					$segments[$i]['CORRECTION_TYPE'] = "No Correction";
			}
			// no correction
			else {
				$segments[$i]['CORRECTION_TYPE'] = "No Correction";
			}

			$segments[$i]['GP_LANE_MILES'] = $segments[$i]['GP_LENGTH'] * $segments[$i]['GP_NUMBER_THRU_LANES'];
		}
		// update
		$this->segments = $segments;

		// do ahp calculation
		$this->ahp_calc($wtpcc, $wtctv, $wtic, $wtltpb, $wtlcc, $wtcsds, $wtride, $wtrod, $wtskid, $wtsci, $wtva, $wtaadt, $wttaadt, $seg_table_name, $first_year);
		$segments = $this->segments;
		// var_dump($segments[0]);
		// xdebug_break();
		
		return $segments;
	}

	private function ahp_calc($_wtpcc, $_wtctv, $_wtic, $_wtltpb, $_wtlcc, $_wtcsds, $_wtride, $_wtrod, $_wtskid, $_wtsci, $_wtva, $_wtaadt, $_wttaadt, $seg_table_name, $first_year) {
		global $session;
		global $database;
		// xdebug_break();
		// get min and max value for rate of deterioration, AADT, and TAADT	
		$query = "SELECT max(GP_RATE_OF_DETERIORATION) as ROD_MAX, min(GP_RATE_OF_DETERIORATION) as ROD_MIN 
			,max(GP_AADT) as AADT_MAX, min(GP_AADT) as AADT_MIN 
			,max(GP_TAADT) as TAADT_MAX, min(GP_TAADT) as TAADT_MIN FROM $seg_table_name";
		$min_max = $database->Query($query);
		if ($first_year != 0) {
			$rod_max = 0;
			$rod_min = 0;
		} else {
			$rod_max = $min_max[0]['ROD_MAX'];
			$rod_min = $min_max[0]['ROD_MIN'];	
		}
		
		$aadt_max = $min_max[0]['AADT_MAX'];
		$aadt_min = $min_max[0]['AADT_MIN'];
		$taadt_max = $min_max[0]['TAADT_MAX'];
		$taadt_min = $min_max[0]['TAADT_MIN'];
		// xdebug_break();

		// get min and max value of init cost, ltpb, and lcc for each rehap type
		$rehap_type = array("PM", "LR", "MR", "HR");
		for ($j=0 ; $j<count($rehap_type) ; $j++) {
			// if (count($this->init_cost[$rehap_type[$j]]) == 0 ||
			// 	count($this->ltpb[$rehap_type[$j]]) == 0 ||
			// 	count($this->lcc[$rehap_type[$j]]) == 0)
				// xdebug_break();
			$max_init_cost[$rehap_type[$j]] = max($this->init_cost[$rehap_type[$j]]);
			$min_init_cost[$rehap_type[$j]] = min($this->init_cost[$rehap_type[$j]]);
			$max_ltpb[$rehap_type[$j]] = max($this->ltpb[$rehap_type[$j]]);
			$min_ltpb[$rehap_type[$j]] = min($this->ltpb[$rehap_type[$j]]);
			$max_lcc[$rehap_type[$j]] = max($this->lcc[$rehap_type[$j]]);
			$min_lcc[$rehap_type[$j]] = min($this->lcc[$rehap_type[$j]]);
		}

		$total_max_init_cost = max($max_init_cost['PM'], $max_init_cost['LR'], $max_init_cost['MR'], $max_init_cost['HR']);
		$total_min_init_cost = min($min_init_cost['PM'], $min_init_cost['LR'], $min_init_cost['MR'], $min_init_cost['HR']);
		$total_max_ltpb = max($max_ltpb['PM'], $max_ltpb['LR'], $max_ltpb['MR'], $max_ltpb['HR']);
		$total_min_ltpb = min($min_ltpb['PM'], $min_ltpb['LR'], $min_ltpb['MR'], $min_ltpb['HR']);
		$total_max_lcc = max($max_lcc['PM'], $max_lcc['LR'], $max_lcc['MR'], $max_lcc['HR']);
		$total_min_lcc = min($min_lcc['PM'], $min_lcc['LR'], $min_lcc['MR'], $min_lcc['HR']);

		// var_dump($max_init_cost);
		// var_dump($min_init_cost);
		// var_dump($max_ltpb);
		// var_dump($min_ltpb);
		// var_dump($max_lcc);
		// var_dump($min_lcc);
		// xdebug_break();

		// iterate each segment
		$num_segments = count($this->segments);
		$segments = $this->segments;
		for ($i=0 ; $i<$num_segments ; $i++) {

			// get default weights
			$wt_pcc = $_wtpcc/100;
			$wt_ctv = $_wtctv/100;
			$wt_ic = $_wtic/100;
			$wt_ltpb = $_wtltpb/100;
			$wt_lcc = $_wtlcc/100;
			$wt_csds = $_wtcsds/100;
			$wt_ride = $_wtride/100;
			$wt_rod = $_wtrod/100;
			$wt_skid = $_wtskid/100; 
			$wt_sci = $_wtsci/100;
			$wt_va = $_wtva/100;
			$wt_aadt = $_wtaadt/100;
			$wt_taadt = $_wttaadt/100;
			
			
			// utility
			$gp_ds_reliab_utility = 1-$segments[$i]['GP_DS_RELIAB']/100;
			$gp_cs_reliab_utility = 1-$segments[$i]['GP_CS_RELIAB']/100;
			$gp_ride_utility = (5-$segments[$i]['GP_RIDE_RELIAB'])/4.9;			
			$gp_rod_utility = ($rod_max-$rod_min!=0)?($segments[$i]['GP_RATE_OF_DETERIORATION']-$rod_min)/($rod_max-$rod_min):null;			
			$gp_aadt_utility = ($segments[$i]['GP_AADT']-$aadt_min)/($aadt_max-$aadt_min);			
			$gp_taadt_utility = ($segments[$i]['GP_TAADT']-$taadt_min)/($taadt_max-$taadt_min);

			// pcc
			if ($segments[$i]['GP_SKID_RESIST'] == null)
				$wt_skid = 0;
			if ($segments[$i]['GP_SCI'] == null)
				$wt_sci = 0;
			if ($segments[$i]['GP_VISUAL_ASSESSMENT'] == null)
				$wt_va = 0;
			if ($segments[$i]['GP_RATE_OF_DETERIORATION'] === null || $gp_rod_utility === null)
				$wt_rod = 0;
			
			// reallocation
			$pcc_total = $wt_csds + $wt_ride + $wt_skid + $wt_sci + $wt_va + $wt_rod;
			$wt_csds = $wt_csds / $pcc_total * $wt_pcc;
			$wt_ride = $wt_ride / $pcc_total * $wt_pcc;
			$wt_skid = $wt_skid / $pcc_total * $wt_pcc;
			$wt_sci = $wt_sci / $pcc_total * $wt_pcc;
			$wt_va = $wt_va / $pcc_total * $wt_pcc;
			$wt_rod = $wt_rod / $pcc_total * $wt_pcc;

			// ctv
			$wt_aadt = $wt_aadt * $wt_ctv;
			$wt_taadt = $wt_taadt * $wt_ctv;
			
			// array for using in module4
			$module4_data = array();

			// if ($i == 622)
			// 	xdebug_break();
			
			// if ($i == 1)
			// 	xdebug_break();
			for ($j=0 ; $j<count($rehap_type) ; $j++) {
				// values, min, max for this rehap type
				// xdebug_break();
				$ltpb = $segments[$i]['LTPB_'.$rehap_type[$j]];
				$min_pb = $total_min_ltpb; //$min_ltpb[$rehap_type[$j]];
				$max_pb = $total_max_ltpb; //$max_ltpb[$rehap_type[$j]];
				$lcc = $segments[$i]['LCC_'.$rehap_type[$j]];
				$max_cc = $total_max_lcc; //$max_lcc[$rehap_type[$j]];
				$min_cc = $total_min_lcc; //$min_lcc[$rehap_type[$j]];
				$ic = $segments[$i]['INIT_COST_'.$rehap_type[$j]];
				$max_ic = $total_max_init_cost; //$max_init_cost[$rehap_type[$j]];
				$min_ic = $total_min_init_cost; //$min_init_cost[$rehap_type[$j]];
				
				// only if they are valid
				if ($ltpb != null && $lcc != null) {
					// compute utility			
					// if ($max_pb-$min_pb == 0)
					// 	xdebug_break();
					$init_cost_utility = ($max_ic - $ic) / ($max_ic - $min_ic);
					$ltpb_utility = ($ltpb - $min_pb) / ($max_pb - $min_pb);
					$lcc_utility = ($max_cc - $lcc) / ($max_cc - $min_cc);

					// compute ahp score for this rehap type
					// xdebug_break();
					$ahp_score = $gp_cs_reliab_utility * $wt_csds + $gp_ride_utility * $wt_ride +
					$segments[$i]['GP_SKID_RESIST'] * $wt_skid + $segments[$i]['GP_SCI'] * $wt_sci + $segments[$i]['GP_VISUAL_ASSESSMENT'] * $wt_va +
					$gp_rod_utility * $wt_rod + $gp_taadt_utility * $wt_taadt + $gp_aadt_utility * $wt_aadt +
					$init_cost_utility * $wt_ic + $ltpb_utility * $wt_ltpb + $lcc_utility * $wt_lcc;

					// store for later use in module4
					$module4_data[$j]['BENEFIT'] = $ahp_score;
					$module4_data[$j]['INIT_COST'] = $ic;
					$module4_data[$j]['TREATMENT_TYPE'] = $rehap_type[$j];
				}
				$segments[$i]['BENEFIT_'.$rehap_type[$j]] = $ahp_score;
			}
			// store $module4_data in $segment
			$segments[$i]['MODULE4_DATA'] = $module4_data;			
		}
		// update
		$this->segments = $segments;
	}

	// compute net present value
	private function npv($rate, $values) { 
		$npv = 0;
	    for ($i=0;$i<=count($values);$i+=1) { 
	        $npv += $values[$i] / pow((1 + $rate), $i+1); 
	    } 
    	return $npv; 
	}

	// prediction model function
	private function model($cs_coeff, $cs_start, $x, $benefitcsthreshold) {
		// returns f(x) for integral approximation with composite Simpson's rule
		if ($x == 0)
			return $cs_start-$benefitcsthreshold;
		else
			return ($cs_start * (1-exp(-(pow($cs_coeff['Rho']/$x, $cs_coeff['Beta']))))-$benefitcsthreshold);
	}

	// use numerial method (composite simpson's rule) integration
	// approximates integral_a_b f(x) dx with composite Simpson's rule with $n intervals
	// $n has to be an even number
	// f(x) is defined in "function model($x)"
	private function simpsonsrule($a, $b, $n, $cs_coeff, $cs_start, $benefitcsthreshold) {
	   if($n%2 == 0) {
	      $h = ($b-$a)/$n;
	      $S = $this->model($cs_coeff, $cs_start, $a, $benefitcsthreshold) + $this->model($cs_coeff, $cs_start, $b, $benefitcsthreshold);
	      $i = 1;
	      while($i<=($n - 1)) {
	         $xi = $a + $h * $i;
	         if ($i%2 == 0){
	            $S = $S + 2 * $this->model($cs_coeff, $cs_start, $xi, $benefitcsthreshold);
	         }
	         else {
	            $S = $S + 4 * $this->model($cs_coeff, $cs_start, $xi, $benefitcsthreshold);
	         }
	         $i++;
	      }
	      return($h/3*$S);
	      }
	   else {
	      return('$n has to be an even number');
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
		$params['DS_START'] = $ds_start;
		//************************************************************
		// 5 is fixed???????????
		//************************************************************
		$params['CS_START'] = $ds_start*$uride<$this->mnrtriggervalue+5?0:$ds_start*$uride;
		return $params;
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
	function print_bc($segments, $i, $rehap_type, $init_cost, $ltpb, $lcc, $bc_ratio) {
		printf("%s, %s, %s, %s, %s, %s, %f, %f, %f, %f\n", $segments[$i]['SIGNED_HIGHWAY_RDBD_ID'], $segments[$i]['BEG_REF_MARKER_NBR'],
		$segments[$i]['BEG_REF_MARKER_DISP'], $segments[$i]['END_REF_MARKER_NBR'], $segments[$i]['END_REF_MARKER_DISP'], $rehap_type,
		$init_cost, $ltpb, $lcc, $bc_ratio);
	}
};

$module3 = new Module3;
?>