<?php
ini_set('memory_limit', '9000M');
class Segmentation {	
	private $cs_threshold;				// threshold value for condition score
	private $ds_threshold;				// threshold value for distress score
	private $mnr_trigger;
	private $mnr_trigger_param;
	private $user_id;
	private $esal_threshold;	
 	private $min_seg_len;				// minimum segment length
	private $max_seg_len;				// maximum segment length
	private $zvalue;
	private $sections;					// sections of base year in pmis
	private $cs_based;					// condition score based segmentation result
	private $ds_based;					// distress score based segmentation result
	private $esal_based;
	private $groups = array();			// holds for group of roads divided by highway road bed, pavement type, and continuity
	private $segments = array();
	private $group;
	
	function Segmentation() {
		global $database;
		global $session;
		$base_year = $_SESSION['baseyear'];
		$table = "pmis_condition_summary_".$_SESSION['district'];
		$this->user_id = $session->user_id;
		// database query for sections of based years
		$query = "SELECT ID, CURRENT_18KIP_MEAS, NUMBER_THRU_LANES, PRIOR_TREATMENT, YEAR_OF_PRIOR_TREATMENT, FISCAL_YEAR, RATING_CYCLE_CODE, SIGNED_HIGHWAY_RDBD_ID, ADJ_DISTRESS_SCORE,
		ADJ_CONDITION_SCORE, ADJ_RIDE_SCORE, SECT_LENGTH, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP, PVMNT_TYPE_DTL_RD_LIFE_CODE,
		AADT, TRUCK_AADT_PCT, DISTRICT_NAME, ZONE_NUMBER, SPEED_LIMIT_MAX, RATE_OF_DETERIORATION  
		FROM $table WHERE FISCAL_YEAR=$base_year AND RATING_CYCLE_CODE='P' AND PVMNT_TYPE_BROAD_CODE = 'A'";
		
		// store the sections
		$this->sections = $database->Query($query);
		$this->esal_threshold = $database->Query("SELECT avg(current_18kip_meas) as esal_threshold FROM $table where fiscal_year=$base_year and rating_cycle_code='P' and pvmnt_type_broad_code='A'");
		$this->esal_threshold = $this->esal_threshold[0]['esal_threshold'];
		
		$session->updateprogress(0, "Initialize temporal grouping...");
		
		// grouping
		$this->grouping();
		
		// segmentation
		// $this->do_segmentation(70, 70, 2, 10);
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
		$this->groups = array();
		$group = array();
		array_push($group, $sections[0]);
		// $this->print_road($sections, 0);
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

	public function do_segmentation_method2($sections, $mnr_trigger, $mnr_trigger_param, $min_seg_len, $max_seg_len) {
		global $session;
		global $database;
		// xdebug_break();
		$base_year = $_SESSION['baseyear'];
		// initialize parameters
		$this->mnr_trigger = $mnr_trigger;
		$this->mnr_trigger_param = $mnr_trigger_param;	
		// xdebug_break();	

		$this->min_seg_len = $min_seg_len;
		if (isset($sections)) {
			$this->sections = $sections;
			$table = $_SESSION['pmistable'];
			$this->esal_threshold = $database->Query("SELECT avg(current_18kip_meas) as esal_threshold FROM $table where fiscal_year=$base_year and rating_cycle_code='P' and pvmnt_type_broad_code='A'");
			$this->esal_threshold = $this->esal_threshold[0]['esal_threshold'];
			$this->grouping();
		}

		$groups = $this->groups;
		$segment_id = 1;
		$segments = array();
		for ($i=0 ; $i<count($groups) ; $i++) {
			// if ($i==0 || $i==579)
			// 	xdebug_break();
			$group = $groups[$i]['GROUP'];			
			$segment = array();
			$boundaries = array();
			// xdebug_break();
			for ($j=0 ; $j<count($group) ; $j++) {
				// if ($group[$j]['SIGNED_HIGHWAY_RDBD_ID'] == 'FM2620 K' && $group[$j]['BEG_REF_MARKER_NBR'] == 404
				// 	&& $group[$j]['BEG_REF_MARKER_DISP'] == 1.5)
				// 	xdebug_break();
				if (($mnr_trigger_param == "Condition Score" && $group[$j]['ADJ_CONDITION_SCORE']<$mnr_trigger) ||
					($mnr_trigger_param == "Distress Score" && $group[$j]['ADJ_DISTRESS_SCORE']<$mnr_trigger)) {
					$group[$j]['NEED_MNR'] = true;
					if (empty($segment)) {
						$group[$j]['IS_BEG'] = true;						
						array_push($boundaries, array('INFO'=>$group[$j], 'INDEX'=>$j));
					}
					array_push($segment, $j);
				}
				else {
					$group[$j]['NEED_MNR'] = false;
					if (!empty($segment)) {
						$last = $segment[count($segment)-1];
						$group[$last]['IS_END'] = true;
						if ($group[$last]['IS_BEG'])
							array_pop($boundaries);
						array_push($boundaries, array('INFO'=>$group[$last], 'INDEX'=>$last));
						unset($segment);
						$segment = array();
					}
				}

			}
			if (!empty($segment)) {
				$last = $segment[count($segment)-1];
				$group[$last]['IS_END'] = true;
				if ($group[$last]['IS_BEG'])
					array_pop($boundaries);
				array_push($boundaries, array('INFO'=>$group[$last], 'INDEX'=>$last));
				unset($segment);
				$segment = array();
			}

			/*
			 * extent 1
			 */
			$result_boundaries = $boundaries;
			$extent1 = $min_seg_len;// + 0.6;	5/1 Change		
			for ($j=0 ; $j<count($boundaries) ; $j++) {
				// if ($group[$j]['SIGNED_HIGHWAY_RDBD_ID'] == 'FM2620 K' && $group[$j]['BEG_REF_MARKER_NBR'] == 404
				// 	&& $group[$j]['BEG_REF_MARKER_DISP'] == 1.5)
				// 	xdebug_break();
				$index = $boundaries[$j]['INDEX'];
				$boundary = $boundaries[$j]['INFO'];

				if ($boundary['IS_BEG']) {					
					if ($j-1>=0) {
						$prev_boundary = $boundaries[$j-1]['INFO'];
						if ($boundary['CUM_LENGTH_BEG']-$prev_boundary['CUM_LENGTH_END'] >= $extent1)
						// if ($boundary['CUM_LENGTH_BEG']-$prev_boundary['CUM_LENGTH_BEG']-$extent1 >= 0.0000001)
							;
						else {
							if ($prev_boundary['IS_END'])
								// there is End rear then make this beg cancel
								unset($result_boundaries[$j]['INFO']['IS_BEG']);
						}
					}

				} 
				if ($boundary['IS_END']) {
					if ($j+1<=count($boundaries)-1) {
						$next_boundary = $boundaries[$j+1]['INFO'];												
						if ($next_boundary['CUM_LENGTH_BEG']-$boundary['CUM_LENGTH_END'] >= $extent1) {
						// if ($next_boundary['CUM_LENGTH_END']-$boundary['CUM_LENGTH_END']-$extent1 >= 0.0000001) {
							;
						}
						else {
							if ($next_boundary['IS_BEG'])
								// there is Beg front then make this end cancel
								unset($result_boundaries[$j]['INFO']['IS_END']);
						}
					}
				}

				if ($result_boundaries[$j]['INFO']['IS_BEG'] && $result_boundaries[$j]['INFO']['IS_END'])
					$result_boundaries[$j]['INFO']['MARK'] = "B&E";
				else if ($result_boundaries[$j]['INFO']['IS_BEG'])
					$result_boundaries[$j]['INFO']['MARK'] = "B";
				else if ($result_boundaries[$j]['INFO']['IS_END'])
					$result_boundaries[$j]['INFO']['MARK'] = "E";

			}
			$boundaries = $result_boundaries;
			unset($result_boundaries);

			// if (isset($sections) && $boundaries[0]['INFO']['SIGNED_HIGHWAY_RDBD_ID'] == "FM1452 K") {
			// for ($j=0 ; $j<count($boundaries) ; $j++) {
			// 	if (isset($boundaries[$j]['INFO']['MARK'])) {
			// 		print $boundaries[$j]['INFO']['MARK'].",";
			// 		$this->print_road($group, $boundaries[$j]['INDEX']);
			// 	}
			// }
			// xdebug_break();
			// }
			

		   /*
			* extent2
			*/
			// xdebug_break();
			$result_boundaries = $boundaries;
			$extent2a = $extent1 + $min_seg_len/2;
			$extent2b = $extent1 + $min_seg_len;
			for ($j=0 ; $j<count($boundaries) ; $j++) {
				// if ($group[$j]['SIGNED_HIGHWAY_RDBD_ID'] == 'FM2620 K' && $group[$j]['BEG_REF_MARKER_NBR'] == 404
				// 	&& $group[$j]['BEG_REF_MARKER_DISP'] == 1.5)
				// 	xdebug_break();
				// if ($boundaries[$j]['INFO']['SIGNED_HIGHWAY_RDBD_ID'] == 'FM1696 K' && $boundaries[$j]['INFO']['BEG_REF_MARKER_NBR'] == 654
				// 		&& $boundaries[$j]['INFO']['BEG_REF_MARKER_DISP'] == 1.5)
				// 		xdebug_break();

				if ($boundaries[$j]['INFO']['MARK'] == "B") {
					// B&E rear
					if ($j-1>=0) {
						// if ($boundaries[$j]['INFO']['CUM_LENGTH_BEG']-$boundaries[$j-1]['INFO']['CUM_LENGTH_BEG']-$extent2a >= 0.0000001)
						if ($boundaries[$j]['INFO']['CUM_LENGTH_BEG']-$boundaries[$j-1]['INFO']['CUM_LENGTH_END'] >= $extent2a)
							;
						else {
							if ($boundaries[$j-1]['INFO']['MARK'] == "B&E") {
								// $result_boundaries[$j-1]['INFO']['MARK'] = "B";
								unset($result_boundaries[$j]['INFO']['MARK']);
							}
						}
					}

				} 
				else if ($boundaries[$j]['INFO']['MARK'] == "E") {
					// B&E front					
					if ($j+1<=count($boundaries)-1) {
						// if ($boundaries[$j+1]['INFO']['CUM_LENGTH_END']-$boundaries[$j]['INFO']['CUM_LENGTH_END']-$extent2a >= 0.0000001)
						if ($boundaries[$j+1]['INFO']['CUM_LENGTH_BEG']-$boundaries[$j]['INFO']['CUM_LENGTH_END'] >= $extent2a)
							;
						else {
							if ($boundaries[$j+1]['INFO']['MARK'] == "B&E") {
								// $result_boundaries[$j+1]['INFO']['MARK'] = "E";
								unset($result_boundaries[$j]['INFO']['MARK']);
							}
						}
						
					}
				}
				else if ($boundaries[$j]['INFO']['MARK'] == "B&E") {
					// if ($boundaries[$j]['INFO']['SIGNED_HIGHWAY_RDBD_ID'] == 'FM2620 K' && $boundaries[$j]['INFO']['BEG_REF_MARKER_NBR'] == 0)
						// xdebug_break();
					$no_e_in_rear = true;
					$no_be_in_rear = true;
					$no_b_in_front = true;
					$no_be_in_front = true;
					if ($j-1>=0) {
						// if ($boundaries[$j]['INFO']['CUM_LENGTH_BEG']-$boundaries[$j-1]['INFO']['CUM_LENGTH_BEG']-$extent2b >= 0.0000001)
						if ($boundaries[$j]['INFO']['CUM_LENGTH_BEG']-$boundaries[$j-1]['INFO']['CUM_LENGTH_END'] >= $extent2b)
							;
						else {			
							// count B&E and E in rear				
							if ($boundaries[$j-1]['INFO']['MARK'] == "B&E") {
									// $result_boundaries[$j-1]['INFO']['MARK'] = "B";
									// $result_boundaries[$j]['INFO']['MARK'] = "E";
								$no_be_in_rear = false;
							}
							// if ($boundaries[$j]['INFO']['CUM_LENGTH_BEG']-$boundaries[$j-1]['INFO']['CUM_LENGTH_BEG']-$extent2a >= 0.0000001) {
							if ($boundaries[$j]['INFO']['CUM_LENGTH_BEG']-$boundaries[$j-1]['INFO']['CUM_LENGTH_END'] >= $extent2a) {
								;								
							}
							else if ($boundaries[$j-1]['INFO']['MARK'] == "E") {
								// unset($result_boundaries[$j-1]['INFO']['MARK']);
								// $result_boundaries[$j]['INFO']['MARK'] = "E";
								$no_e_in_rear = false;
							}
						}
						
					}
					if ($j+1<=count($boundaries)-1) {
						// if ($boundaries[$j+1]['INFO']['CUM_LENGTH_END']-$boundaries[$j]['INFO']['CUM_LENGTH_END']-$extent2b >= 0.0000001)
						if ($boundaries[$j+1]['INFO']['CUM_LENGTH_BEG']-$boundaries[$j]['INFO']['CUM_LENGTH_END'] >= $extent2b)
							;
						else {
							// count B&E and B in front
							if ($boundaries[$j+1]['INFO']['MARK'] == "B&E") {
								// $result_boundaries[$j+1]['INFO']['MARK'] = "E";
								// $result_boundaries[$j]['INFO']['MARK'] = "B";
								$no_be_in_front = false;
							}
							// if ($boundaries[$j+1]['INFO']['CUM_LENGTH_END']-$boundaries[$j]['INFO']['CUM_LENGTH_END']-$extent2a >= 0.0000001) {
							if ($boundaries[$j+1]['INFO']['CUM_LENGTH_BEG']-$boundaries[$j]['INFO']['CUM_LENGTH_END'] >= $extent2a) {
								;								
							}
							else if ($boundaries[$j+1]['INFO']['MARK'] == "B") {
								// unset($result_boundaries[$j+1]['INFO']['MARK']);
								// $result_boundaries[$j]['INFO']['MARK'] = "B";
								$no_b_in_front = false;
							}
						}
					}
					$result1 = "";
					$result2 = "";
					if ($no_e_in_rear) //&& $no_be_in_rear)
						$result1 = "B";
					if ($no_b_in_front) //&& $no_be_in_front)
						$result2 = "E";
					if ($result1 != "" && $result2 != "")
						$result_boundaries[$j]['INFO']['MARK'] = "B&E";
					else if ($result1 != "")
						$result_boundaries[$j]['INFO']['MARK'] = "B";
					else if ($result2 != "")
						$result_boundaries[$j]['INFO']['MARK'] = "E";
					else
						unset($result_boundaries[$j]['INFO']['MARK']);
				}

				// update boundary info
				$group[$result_boundaries[$j]['INDEX']]['MARK'] = $result_boundaries[$j]['INFO']['MARK'];

			}
			$boundaries = $result_boundaries;
			// xdebug_break();
			/**
			 * extent 2
			 */
			
			// for ($j=0 ; $j<count($boundaries) ; $j++) {
			// 	if (isset($boundaries[$j]['INFO']['MARK'])) {
			// 		print $boundaries[$j]['INFO']['MARK'].",";
			// 		$this->print_road($group, $boundaries[$j]['INDEX']);
			// 	}
			// }





			
			/*
			 * extent 3
			 */
			$extent = $min_seg_len / 2 + 0.1;
			unset($this->group);
			$this->group = $group;
			// xdebug_break();
			for ($j=0 ; $j<count($boundaries) ; $j++) {
				// if ($group[$j]['SIGNED_HIGHWAY_RDBD_ID'] == 'FM2620 K' && $group[$j]['BEG_REF_MARKER_NBR'] == 404
				// 	&& $group[$j]['BEG_REF_MARKER_DISP'] == 1.5)
				// 	xdebug_break();
				if ($boundaries[$j]['INFO']['MARK'] == "B")
					$this->extend_to_front("B", $boundaries[$j]['INDEX'], $extent, false);
				else if ($boundaries[$j]['INFO']['MARK'] == "E")
					$this->extend_to_rear("E", $boundaries[$j]['INDEX'], $extent, false);
				else if ($boundaries[$j]['INFO']['MARK'] == "B&E") {
					$this->extend_to_rear("B&E",$boundaries[$j]['INDEX'], $extent, false);
					$this->extend_to_front("B&E",$boundaries[$j]['INDEX'], $extent, false);
				}
			}

			$group = $this->group;

			$group = $this->partial_split($group, $max_seg_len);

			// for ($j=0 ; $j<count($group) ; $j++) {
			// 	if (isset($group[$j]['MARK'])) {
			// 		print $group[$j]['MARK'].",";
			// 		$this->print_road($group, $j);
			// 	}
			// }
			// xdebug_break();

			// sections to segments
			$highway = $group[0]['SIGNED_HIGHWAY_RDBD_ID'];
			$segment = array();
			if ($highway != $prev_highway || !isset($prev_highway))
				$segment_id = 0;
			for ($j=0 ; $j<count($group) ; $j++) {
				if ($group[$j]['MARK'] == "B&E" || $group[$j]['MARK'] == "B") {
					$segment_id++;
					$group[$j]['SEGMENT_ID'] = $highway.' '.$segment_id;
					// if (count($segments)==245)
						// xdebug_break();
					array_push($segment, $group[$j]);
					if ($group[$j]['MARK'] == "B") {
						$j++;
						while ($j<=count($group)-1 && $group[$j]['MARK'] != "E") {
							$group[$j]['SEGMENT_ID'] = $highway.' '.$segment_id;
							array_push($segment, $group[$j]);
							$j++;
						}
						$group[$j]['SEGMENT_ID'] = $highway.' '.$segment_id;
						array_push($segment, $group[$j]);
					}
					array_push($segments, $segment);
					unset($segment);
					$segment = array();
				}
			}
			$prev_highway = $highway;
			$this->groups[$i]['GROUP'] = $group;
		}
		unset($this->segments);
		// xdebug_break();
		$this->segments = $segments;

		// split long segments

		// xdebug_break();
		
		if (!isset($sections))
			$this->create_group_and_table(2, true);
		else
			$this->create_group_and_table(2, false);
		$segments = $this->segments;
		// xdebug_break();
		return $segments;
	}

	private function extend_to_rear($mark, $start, $extent, $force) {
		$group = $this->group;
		$found = false;
		$group_length = 0;
		// calculate group length
		if ($mark == "B&E") {
			$group_length = $group[$start]['SECT_LENGTH'];
			$beg = $start;
			$found = true;
		}
		else if ($mark == "E") {
			$group_length = $group[$start]['SECT_LENGTH'];
			$i= $start;
			
			// find matched B
			while (--$i>=0) {
				$group_length += $group[$i]['SECT_LENGTH'];
				if ($group[$i]['MARK'] == "B") {
					$beg = $i;
					$found = true;
					break;
				}
				else if (isset($group[$i]['MARK']))
					break;
				if ($group[$i]['NEED_MNR'])
					$first = $i;
			}
			if (!$found) {
				// if ($first == null) {
				// 	$group_length = $group[$start]['SECT_LENGTH'];
				// 	$beg = $start;
				// 	$found = true;
				// }
				// else {
					$beg = $first;
					$group_length = 0;
				// }
				// xdebug_break();
			}
		}

		// mismatch found
		if (!$found) {
			print "Error\n";
			// temp B
			// xdebug_break();
			// $this->extend_to_front("B", $beg, $extent, true);
			// $group = $this->group;
		}

		// Need Merge
		if ($group_length < $this->min_seg_len || $force) {
			$i = $beg-1;
			// extend B if possible

			// go back
			while ($i>=0) {
				// look ahead
				$j = $i+1;
				$cum_length = 0;
				$stop = false;
				$discovered = false;
				do {
					// $cum_length += $group[$j]['SECT_LENGTH'];
					// we found NM
					// if ($group[0]['SIGNED_HIGHWAY_RDBD_ID'] == "BS0021HK")
					//	xdebug_break();
					if ($group[$j]['NEED_MNR'] && $cum_length < $extent) {
						// move end
						if ($group[$beg]['MARK'] == "B&E")
							$group[$beg]['MARK'] = "E";
						else
							unset($group[$beg]['MARK']);
						$beg = $i;
						$group[$i]['MARK'] = "B";
						$discovered = true;
						break;
					}
					$cum_length += $group[$j]['SECT_LENGTH'];	// FROM LINE 516 TO 529
					if ($stop)
						break;
					if (isset($group[++	$j]['MARK']))
						$stop = true;
				} while (true);
				if (!$discovered)
					break;
				$i--;
			}
		}
		$this->group = $group;
	}

	private function extend_to_front($mark, $start, $extent, $force) {
		$group = $this->group;
		// if ($group[0]['SIGNED_HIGHWAY_RDBD_ID'] == "FM1618 K")
		// 	xdebug_break();
		$found = false;
		$group_length = 0;
		// calculate group length
		if ($mark == "B&E") {
			$group_length = $group[$start]['SECT_LENGTH'];
			$end = $start;
			$found = true;
		}
		else if ($mark == "B") {
			$group_length = $group[$start]['SECT_LENGTH'];
			$i= $start;
			
			// find matched E
			while (++$i<=count($group)-1) {
				$group_length += $group[$i]['SECT_LENGTH'];
				if ($group[$i]['MARK'] == "E") {
					$end = $i;
					$found = true;
					break;
				}
				else if (isset($group[$i]['MARK']))
					break;
				if ($group[$i]['NEED_MNR'])
					$last = $i;
			}
			if (!$found) {
				// if ($last==null) {
				// 	$group_length = $group[$start]['SECT_LENGTH'];
				// 	$end = $start;
				// 	$found = true;
				// }
				// else {
					$end = $last;
					$group_length = 0;
				// }
				// xdebug_break();
			}
		}

		// mismatch found
		if (!$found) {
			print "Error\n";
			// temp B
			// xdebug_break();
			// $this->extend_to_rear("E", $end, $extent, true);
			// $group = $this->group;
		}

		// Need Merge
		if ($group_length < $this->min_seg_len || $force) {
			$i = $end + 1;
			// extend E if possible

			// go front
			while ($i<=count($group)-1) {
				// look back
				$j = $i-1;
				$cum_length = 0;
				$stop = false;
				$discovered = false;
				do {
					// $cum_length += $group[$j]['SECT_LENGTH'];
					// we found NM
					if ($group[$j]['NEED_MNR'] && $cum_length < $extent) {
						// move end
						if ($group[$end]['MARK'] == "B&E") {
							$group[$end]['MARK'] = "B";							
						}
						else
							unset($group[$end]['MARK']);
						$end = $i;
						$group[$i]['MARK'] = "E";
						$discovered = true;
						break;
					}
					$cum_length += $group[$j]['SECT_LENGTH']; // FROM LINE 608 -> TO 622
					if ($stop)
						break;
					if (isset($group[--$j]['MARK']))
						$stop = true;
				} while (true);
				if (!$discovered)
					break;
				$i++;
			}
		}
		
		$this->group = $group;
	}

	private function get_last_key($array) {
		end($array);
		return key($array);
	}
	
	public function do_segmentation_method1($cs_threshold, $ds_threshold, $min_seg_len, $max_seg_len, $zvalue) {
		global $session;
		// xdebug_break();
		$base_year = $_SESSION['baseyear'];
		// initialize parameters
		$this->cs_threshold = $cs_threshold;
		$this->ds_threshold = $ds_threshold;
		$this->min_seg_len = $min_seg_len;
		$this->max_seg_len = $max_seg_len;
		$this->zvalue = $zvalue;
		
		// do segmentation by both scores
		$session->updateprogress(15, "Segmentation by ESAL...");
		$this->esal_based = $this->segmentation_by('ESAL');
		$session->updateprogress(35, "Segmentation by Distress Score...");
		$this->ds_based = $this->segmentation_by('Distress Score');
		$session->updateprogress(55, "Segmentation by Condition Score...");
		$this->cs_based = $this->segmentation_by('Condition Score');
		
		// stitch the result
		$session->updateprogress(65, "Stitching the result...");
		$this->stitch_and_split();
		$this->create_group_and_table(1, true);
		
	}	

	private function create_group_and_table($method, $create_table) {
		global $session;
		$base_year = $_SESSION['baseyear'];
		if ($this->user_id == '')
			return;		
		if ($create_table) {
			$session->updateprogress(70, "Creating segmentation table...");
			$table_name = '_segmented_pmis_'.$this->user_id;
			$query = "DROP TABLE IF EXISTS 1".$table_name;
			mysql_query($query) or die('mysql_error: '.mysql_error());
			$query = "DROP TABLE IF EXISTS 2".$table_name;
			mysql_query($query) or die('mysql_error: '.mysql_error());
			$table_name = $method.$table_name;
			// create table for sections with segment id
			$query = "CREATE TABLE IF NOT EXISTS `".$table_name."` (";
			$query .= "
			`ID` int(11) NOT NULL,
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
			$pmis_table = "pmis_condition_summary_".$_SESSION['district'];
			if ($f[0] == 0) {
				$query = "INSERT INTO `".$table_name."` (ID, FISCAL_YEAR, SIGNED_HIGHWAY_RDBD_ID, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP, SECT_LENGTH, RATING_CYCLE_CODE)
				SELECT ID, FISCAL_YEAR, SIGNED_HIGHWAY_RDBD_ID, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP, SECT_LENGTH, RATING_CYCLE_CODE FROM `$pmis_table`"
				." WHERE FISCAL_YEAR=$base_year AND RATING_CYCLE_CODE='P' AND PVMNT_TYPE_BROAD_CODE = 'A';";
				mysql_query($query) or die('mysql_error: '.mysql_error());
			}		
		
			// xdebug_break();
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
				// xdebug_break();
			}
			// xdebug_break();
			// $query = "DELETE FROM `$table_name` WHERE SEGMENT_ID='' OR ISNULL(SEGMENT_ID)";
			// mysql_query($query) or die('mysql_error: '.mysql_error());

			// xdebug_break();		
			$table_name = '_segmented_pmis_aggregated_'.$this->user_id;
			
			$query = "DROP TABLE IF EXISTS 1".$table_name;
			mysql_query($query) or die('mysql_error: '.mysql_error());
			$query = "DROP TABLE IF EXISTS 2".$table_name;
			mysql_query($query) or die('mysql_error: '.mysql_error());
			$table_name = $method.$table_name;
			
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
			`GP_RATE_OF_DETERIORATION` double(15,5) NOT NULL,
			`GP_STD_RATE_OF_DETERIORATION` double(15,1) NOT NULL,
			`GP_DS_RELIAB` double(15,2) NOT NULL,
			`GP_CS_RELIAB` double(15,2) NOT NULL,
			`GP_RIDE_RELIAB` double(15,1) NOT NULL,
			`GP_RATE_OF_DETERIORATION_RELIAB` double(15,1) NOT NULL,
			`GP_TAADT` double(15,0) NOT NULL,
			`GP_MIN_CS` double(15,1) NOT NULL,
			`GP_MIN_DS` double(15,1) NOT NULL,
			`GP_MIN_RIDE` double(15,1) NOT NULL,
			`GP_RATIO_BELOW_PM_VIABILITY` double(15,4) NOT NULL,
			`GP_RATIO_BELOW_LR_VIABILITY` double(15,4) NOT NULL,
			`GP_BEG_ID` int(11) NOT NULL,
			`GP_END_ID` int(11) NOT NULL,
			`SEGMENT_ID` varchar(20) DEFAULT NULL,
			PRIMARY KEY (`FISCAL_YEAR`,`SIGNED_HIGHWAY_RDBD_ID`,`BEG_REF_MARKER_NBR`,`BEG_REF_MARKER_DISP`,`RATING_CYCLE_CODE`));";
			mysql_query($query) or die('mysql_error: '.mysql_error());
		}

		// update table
		$segments = array();
		$num_segments = count($this->segments);
		$pm_viability_threshold = 50;
		$lr_viability_threshold = 35;
		$segmentation_param = "Condition Score";

		for ($i = 0 ;$i<$num_segments ; $i++) {
			$num_sections = count($this->segments[$i]);
			$sum_sec_length = 0;
			$sum_wcs = 0.0;
			$sum_wride = 0.0;
			$sum_wesal = 0.0;
			$sum_wds = 0.0;
			$sum_wrd = 0.0;
			$gp_min_cs = 100.0;
			$gp_min_ds = 100.0;
			$gp_min_ride = 4.8;
			$cum_lane_mile_below_pm_viability = 0.0;
			$cum_lane_mile_below_lr_viability = 0.0;

			// xdebug_break();
			for ($j=0 ; $j<$num_sections ; $j++) {
				if ($this->segments[$i][$j]['ADJ_CONDITION_SCORE'] <= $gp_min_cs)
					$gp_min_cs = $this->segments[$i][$j]['ADJ_CONDITION_SCORE'];
				if ($this->segments[$i][$j]['ADJ_DISTRESS_SCORE'] <= $gp_min_ds)
					$gp_min_ds = $this->segments[$i][$j]['ADJ_DISTRESS_SCORE'];
				if ($this->segments[$i][$j]['ADJ_RIDE_SCORE'] <= $gp_min_ride)
					$gp_min_ride = $this->segments[$i][$j]['ADJ_RIDE_SCORE'];

				if (($segmentation_param == "Condition Score" && $this->segments[$i][$j]['ADJ_CONDITION_SCORE'] < $pm_viability_threshold) ||
					($segmentation_param == "Distress Score" && $this->segments[$i][$j]['ADJ_DISTRESS_SCORE'] < $pm_viability_threshold))
					$cum_lane_mile_below_pm_viability += $this->segments[$i][$j]['NUMBER_THRU_LANES']*$this->segments[$i][$j]['SECT_LENGTH'];

				if (($segmentation_param == "Condition Score" && $this->segments[$i][$j]['ADJ_CONDITION_SCORE'] < $lr_viability_threshold) ||
					($segmentation_param == "Distress Score" && $this->segments[$i][$j]['ADJ_DISTRESS_SCORE'] < $lr_viability_threshold))
					$cum_lane_mile_below_lr_viability += $this->segments[$i][$j]['NUMBER_THRU_LANES']*$this->segments[$i][$j]['SECT_LENGTH'];

				$sum_sec_length += $this->segments[$i][$j]['SECT_LENGTH'];
				$sum_wcs += $this->segments[$i][$j]['SECT_LENGTH']*$this->segments[$i][$j]['ADJ_CONDITION_SCORE'];
				$sum_wds += $this->segments[$i][$j]['SECT_LENGTH']*$this->segments[$i][$j]['ADJ_DISTRESS_SCORE'];
				$sum_wride += $this->segments[$i][$j]['SECT_LENGTH']*$this->segments[$i][$j]['ADJ_RIDE_SCORE'];
				$sum_wesal += $this->segments[$i][$j]['SECT_LENGTH']*$this->segments[$i][$j]['CURRENT_18KIP_MEAS'];
				$sum_wrd += $this->segments[$i][$j]['SECT_LENGTH']*$this->segments[$i][$j]['RATE_OF_DETERIORATION'];
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
				$sum_num_thru_lanes += $this->segments[$i][$j]['NUMBER_THRU_LANES']*$this->segments[$i][$j]['SECT_LENGTH'];
				$sum_year += $this->segments[$i][$j]['YEAR_OF_PRIOR_TREATMENT']*$this->segments[$i][$j]['SECT_LENGTH']; 
				$sum_speedlimit += $this->segments[$i][$j]['SPEED_LIMIT_MAX']*$this->segments[$i][$j]['SECT_LENGTH'];
				$sum_taadt += $this->segments[$i][$j]['TRUCK_AADT_PCT']*$this->segments[$i][$j]['SECT_LENGTH'];
				$sum_aadt += $this->segments[$i][$j]['AADT']*$this->segments[$i][$j]['SECT_LENGTH'];
				$sum_secs += pow($gp_condition_score-$this->segments[$i][$j]['ADJ_CONDITION_SCORE'], 2)*$this->segments[$i][$j]['SECT_LENGTH'];
				$sum_seds += pow($gp_distress_score-$this->segments[$i][$j]['ADJ_DISTRESS_SCORE'], 2)*$this->segments[$i][$j]['SECT_LENGTH'];
				$sum_seride += pow($gp_ride_score-$this->segments[$i][$j]['ADJ_RIDE_SCORE'], 2)*$this->segments[$i][$j]['SECT_LENGTH'];
				$sum_seesal += pow($gp_esal-$this->segments[$i][$j]['CURRENT_18KIP_MEAS'], 2)*$this->segments[$i][$j]['SECT_LENGTH'];
				$sum_serd += pow($gp_rate_of_deterioration-$this->segments[$i][$j]['RATE_OF_DETERIORATION'], 2)*$this->segments[$i][$j]['SECT_LENGTH'];
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
				$gp_std_rate_of_deterioration = sqrt($sum_serd/((($num_sections-1)*$sum_sec_length)/$num_sections));
			}
			else {
				$gp_std_condition_score = 0;
				$gp_std_distress_score = 0;
				$gp_std_ride_score = 0;
				$gp_std_esal = 0;
				$gp_std_rate_of_deterioration = 0;
			}		
				
			$fiscal_year = $base_year;
			// xdebug_break();
			$beg_id = $this->segments[$i][0]['ID'];
			$end_id = $this->segments[$i][$num_sections-1]['ID'];
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
			$gp_ds_reliab = $gp_distress_score-($gp_std_distress_score*$this->zvalue);
			$gp_ds_reliab = $gp_ds_reliab<0?0:$gp_ds_reliab;
			$gp_cs_reliab = $gp_condition_score-($gp_std_condition_score*$this->zvalue);
			$gp_cs_reliab = $gp_cs_reliab<0?0:$gp_cs_reliab;
			$gp_ride_reliab = $gp_ride_score-($gp_std_ride_score*$this->zvalue);
			$gp_ride_reliab = $gp_ride_reliab<0?0:$gp_ride_reliab;
			$gp_rate_of_deterioration_reliab = $gp_rate_of_deterioration-($gp_std_rate_of_deterioration*$this->zvalue);
			$gp_taadt = round(($gp_aadt/100)*$gp_truck_aadt_pct);
			$gp_ratio_below_pm_viability = min(1,$cum_lane_mile_below_pm_viability/($gp_number_thru_lanes*$gp_length));
			$gp_ratio_above_pm_viability = 1-$gp_ratio_below_pm_viability;
			$gp_ratio_below_lr_viability = min(1,$cum_lane_mile_below_lr_viability/($gp_number_thru_lanes*$gp_length));
			$gp_ratio_above_lr_viability = 1-$gp_ratio_below_lr_viability;
			$segment_id = $this->segments[$i][0]['SEGMENT_ID'];

			$segments[$i]['FISCAL_YEAR'] = $fiscal_year;
			$segments[$i]['SIGNED_HIGHWAY_RDBD_ID'] = $signed_highway_rdbd_id;
			$segments[$i]['BEG_REF_MARKER_NBR'] = $beg_ref_marker_nbr;
			$segments[$i]['BEG_REF_MARKER_DISP'] = $beg_ref_marker_disp;
			$segments[$i]['END_REF_MARKER_NBR'] = $end_ref_marker_nbr;
			$segments[$i]['END_REF_MARKER_DISP'] = $end_ref_marker_disp;
			$segments[$i]['RATING_CYCLE_CODE'] = $rating_cycle_code;
			$segments[$i]['GP_CONDITION_SCORE'] = $gp_condition_score;
			$segments[$i]['GP_STD_CONDITION_SCORE'] = $gp_std_condition_score;
			$segments[$i]['GP_DISTRESS_SCORE'] = $gp_distress_score;
			$segments[$i]['GP_STD_DISTRESS_SCORE'] = $gp_std_ride_score;
			$segments[$i]['GP_RIDE_SCORE'] = $gp_ride_score;
			$segments[$i]['GP_STD_RIDE_SCORE'] = $gp_std_ride_score;
			$segments[$i]['GP_ESAL'] = $gp_esal;
			$segments[$i]['GP_STD_ESAL'] = $gp_std_esal;
			$segments[$i]['GP_LENGTH'] = $gp_length;
			$segments[$i]['GP_NUMBER_THRU_LANES'] = $gp_number_thru_lanes;
			$segments[$i]['GP_AADT'] = $gp_aadt;
			$segments[$i]['GP_PVMNT_TYPE_BROAD_CODE'] = $gp_pvmnt_type_broad_code;
			$segments[$i]['GP_PVMT_FAMILY'] = $gp_pvmt_family;
			$segments[$i]['GP_TRUCK_AADT_PCT'] = $gp_truck_aadt_pct;
			$segments[$i]['GP_DISTRICT_NAME'] = $gp_district_name;
			$segments[$i]['GP_ZONE_NUMBER'] = $gp_zone_number;
			$segments[$i]['GP_SPEED_LIMIT_MAX'] = $gp_speed_limit_max;
			$segments[$i]['GP_PRIOR_TREATMENT'] = $gp_prior_treatment;
			$segments[$i]['GP_YEAR_OF_PRIOR_TREATMENT'] = $gp_year_of_prior_treatment;
			$segments[$i]['GP_RATE_OF_DETERIORATION'] = $gp_rate_of_deterioration;
			$segments[$i]['GP_STD_RATE_OF_DETERIORATION'] = $gp_std_rate_of_deterioration;
			$segments[$i]['GP_DS_RELIAB'] = $gp_ds_reliab;
			$segments[$i]['GP_CS_RELIAB'] = $gp_cs_reliab;
			$segments[$i]['GP_RIDE_RELIAB'] = $gp_ride_reliab;
			$segments[$i]['GP_RATE_OF_DETERIORATION_RELIAB'] = $gp_rate_of_deterioration_reliab;
			$segments[$i]['GP_TAADT'] = $gp_taadt;
			$segments[$i]['GP_MIN_CS'] = $gp_min_cs;
			$segments[$i]['GP_MIN_DS'] = $gp_min_ds;
			$segments[$i]['GP_MIN_RIDE'] = $gp_min_ride;
			$segments[$i]['GP_RATIO_BELOW_PM_VIABILITY'] = $gp_ratio_below_pm_viability;
			$segments[$i]['GP_RATIO_BELOW_LR_VIABILITY'] = $gp_ratio_below_lr_viability;
			$segments[$i]['SEGMENT_ID'] = $segment_id;
			$segments[$i]['GP_BEG_ID'] = $beg_id;
			$segments[$i]['GP_END_ID'] = $end_id;

			if ($create_table) {
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
				."'".$segment_id
				."')";
				mysql_query($query) or die('mysql_error: '.mysql_error());
			}
		}
		$this->segments = $segments;
		if ($create_table)
			$session->updateprogress(100, "Finishing...");
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
		// for ($i=0 ; $i<$num_groups ; $i++) {
		// 	$group_highway = $groups[$i]['GROUP'];
		// 	$group_length = count($groups[$i]['GROUP']);
		// 	for ($j=0 ; $j<$group_length ; $j++) {
		// 		if ($groups[$i]['GROUP'][$j]['POI'] == true)
		// 			$this->print_road($group_highway, $j);				
		// 	}
		// }
		// xdebug_break();
		
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
					// xdebug_break();
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
		// xdebug_break();
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
 					// $this->print_road($group_highway, $j);
				}
			}
			array_push($result, array("boundaries"=>$boundaries, "section"=>$group_highway));			
			unset($boundaries);
			$boundaries = array();
		}
		// xdebug_break();
		// $result is array of "boundaries" and "section" for each group
		return $result;		
	}
	
	// when stitching $boundary_set2 has priority over boundary_set1
	private function union($boundary_set1, $boundary_set2) {
		// xdebug_break();
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
			// if ($union[0]['info']['SIGNED_HIGHWAY_RDBD_ID'] == "SH0007 K")
			// 	xdebug_break();

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
		// xdebug_break();
		$stitched_boundaries = $this->union($this->esal_based, $this->ds_based);
		// xdebug_break();
		$stitched_boundaries = $this->union($stitched_boundaries, $this->cs_based);
		
		$num_groups = count($this->groups);
		
		// now we update
		for ($i = 0 ;$i<$num_groups ; $i++) {
			$num_sections = count($this->groups[$i]['GROUP']);
			$boundaries = $stitched_boundaries[$i]['boundaries'];
			$k=0;
			for ($j=0 ; $j<$num_sections ; $j++) {
				if ($boundaries[$k]['CUM_LENGTH_BEG'] == $this->groups[$i]['GROUP'][$j]['CUM_LENGTH_BEG']) {
					$this->groups[$i]['GROUP'][$j]['BOUNDARY'] = true;
					// $this->print_road($this->groups[$i]['GROUP'], $j);
					$k++;
				}
			}
		}
		
		// split long segments
		// for each group
		// xdebug_break();
		for ($i = 0; $i < $num_groups; $i++) {
			// get the boundary of this group
			$boundaries = $stitched_boundaries[$i]['boundaries'];
			// get the group of sections
			$group = $this->groups[$i]['GROUP'];
			// if ($this->groups[$i]['GROUP'][0]['SIGNED_HIGHWAY_RDBD_ID'] == "FM0039 K")
			// 	xdebug_break();
			$num_sections = count($group);
			$num_boundaries = count($boundaries);

			$last = $this->groups[$i]['TOTAL_LENGTH_END'];
			for ($j=0 ; $j<$num_boundaries ; $j++) {
				$first = $boundaries[$j]['CUM_LENGTH_BEG'];
				if ($j+1 < $num_boundaries)
					$second = $boundaries[$j+1]['CUM_LENGTH_BEG'];
				else
					$second = $last;
				
				// LONG SEGMENT
				if ($second - $first > $this->max_seg_len) {
					// xdebug_break();
					$this->split($first, $second, $i);
					
				}
			}			
		}
		
		// xdebug_break();
		// for ($i = 0; $i<$num_groups; $i++) {
		// 	$group_highway = $this->groups[$i]['GROUP'];
		// 	for ($j = 0; $j < count($this->groups[$i]['GROUP']); $j++) {
		// 		if ($this->groups[$i]['GROUP'][$j]['BOUNDARY'] == true) {
  					// $this->print_road($group_highway, $j);
		// 		}
		// 	}
		// }
		
		// xdebug_break();

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

	private function partial_split($group ,$max_seg_len) {
		for ($j=0 ; $j<count($group) ; $j++) {
			$cum_length = 0;
			$beg_index = $j;
			if ($group[$j]['MARK'] == "B&E" || $group[$j]['MARK'] == "B") {
				$cum_length += $group[$j]['SECT_LENGTH'];
				if ($group[$j]['MARK'] == "B") {
					$j++;
					$cum_length += $group[$j]['SECT_LENGTH'];
					while ($j<=count($group)-1 && $group[$j]['MARK'] != "E") {
						$j++;
						$cum_length += $group[$j]['SECT_LENGTH'];
					}
				}
				$end_index = $j;

				// SPLIT
				if ($cum_length > $max_seg_len) {
					// xdebug_break();
					$start = $group[$beg_index]['CUM_LENGTH_BEG'];
					$end = $group[$end_index]['CUM_LENGTH_BEG'];
					$howmany = ceil(($end-$start)/$max_seg_len);
					$count = $end_index - $beg_index + 1;
					$num_subsections = ceil($count/$howmany);
					$id = 1;
					$count = 0;
					for ($i=$beg_index ; $i<=$end_index ; $i++) {
						if ($group[$i]['CUM_LENGTH_END'] > $start &&
								$group[$i]['CUM_LENGTH_END'] <= $end) {
							if ($id%$num_subsections == 0) {
								$group[$i]['MARK'] = "E";
								$group[$i+1]['MARK'] = "B";
								$count++;
							}
							if ($count == $howmany-1)
								break;
							$id++;				
						}
					}
				}
			}
		}
		return $group;
	}
	
	private function split($start, $end, $k) {
		// xdebug_break();
		// $howmany = ceil(($end-$start) / $this->max_seg_len);
		
		// if too close to the maximum segment length
		// if (ceil(($end-$start) / $howmany) >= $this->max_seg_len - 0.5)
		// 	$howmany++;
		// $howlong = ceil(($end-$start) / $howmany);
		// $cum = 0;
		// $num_sections = count($this->groups[$k]['GROUP']);
		// for ($i=0 ; $i<$num_sections ; $i++) {
		// 	if ($this->groups[$k]['GROUP'][$i]['CUM_LENGTH_BEG'] >= $start &&
		// 			$this->groups[$k]['GROUP'][$i]['CUM_LENGTH_END'] <= $end) {
		// 		$cum += $this->groups[$k]['GROUP'][$i]['SECT_LENGTH'];
		// 		if ($cum >= $howlong) {
		// 			$this->groups[$k]['GROUP'][$i]['BOUNDARY'] = true;
		// 			$cum = 0;
		// 		}				
		// 	}
		// }
		// if ($this->groups[$k]['GROUP'][0]['SIGNED_HIGHWAY_RDBD_ID'] == "FM0486 K")
		// 	xdebug_break();
		if ($this->max_seg_len == 0)
			$howmany = 0;
		else
			$howmany = ceil(($end-$start)/$this->max_seg_len);
		$num_sections = count($this->groups[$k]['GROUP']);
		$count = 0;
		for ($i=0 ; $i<$num_sections ; $i++) {
			if ($this->groups[$k]['GROUP'][$i]['CUM_LENGTH_END'] > $start &&
					$this->groups[$k]['GROUP'][$i]['CUM_LENGTH_END'] <= $end) {
				$count++;
				// $this->print_road($this->groups[$k]['GROUP'], $i);
			}
		}
		if ($howmany != 0)
			$num_subsections = ceil($count/$howmany);
		else
			$num_subsections = 1;
		$id = 1;
		$count = 0;
		for ($i=0 ; $i<$num_sections ; $i++) {
			if ($this->groups[$k]['GROUP'][$i]['CUM_LENGTH_END'] > $start &&
					$this->groups[$k]['GROUP'][$i]['CUM_LENGTH_END'] <= $end) {
				if ($id%$num_subsections == 0) {
					$this->groups[$k]['GROUP'][$i]['BOUNDARY'] = true;
					// $this->print_road($this->groups[$k]['GROUP'], $i);
					$count++;
				}
				if ($count == $howmany-1)
					break;
				$id++;				
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