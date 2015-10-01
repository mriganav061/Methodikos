a:6:{i:0;a:3:{i:0;s:14:"document_start";i:1;a:0:{}i:2;i:0;}i:1;a:3:{i:0;s:6:"p_open";i:1;a:0:{}i:2;i:0;}i:2;a:3:{i:0;s:6:"plugin";i:1;a:4:{i:0;s:9:"code_code";i:1;a:4:{i:0;s:4:"code";i:1;s:3:"php";i:2;s:0:"";i:3;s:2730:"
        /**
	 * PRELIMINARY GROUPING
	 */
	private function grouping() {
		// GET SECTIONS
		$sections = $this->sections;

		// IF THE NUMBER OF SECTIONS IS ZERO, WE ARE DONE
		if (count($sections) == 0)
			return;
		
		// INITIALIZE SOME VARIABLES
		$highway = $sections[0]['signed_highway_rdbd_id'];
		$cumLength = $sections[0]['sect_length'];
		
		// FIRST SECTION'S CUMULATIVE LENGTH
		$sections[0]['cum_length_beg'] = 0;
		$sections[0]['cum_length_end'] = $sections[0]['sect_length'];
		
		// INSERT FIRST SECTION
		$this->groups = array();
		$group = array();
		array_push($group, $sections[0]);
		// $this->print_road($sections, 0);
		
		// LOOP START
		for ($i=1 ; $i<count($sections) ; $i++) {
			// DETERMINE THE SECTION BELONGS TO THE SAME GROUP
			// CRITERIA ARE THE SIGNED HIGHWAY ROADBED ID, CONTINUTIY AND PAVEMENT FAMILY
			if ($sections[$i]['signed_highway_rdbd_id'] == $highway &&
					$sections[$i]['beg_ref_marker_nbr'] == $sections[$i-1]['end_ref_marker_nbr'] &&

					// UNCOMMENT THIS LINE IF OFFSET IS CONSIDERED FOR CONTINUITY
 					$sections[$i]['beg_ref_marker_disp'] == $sections[$i-1]['end_ref_marker_disp'] &&
					
					$this->same_pvmnt_family($sections[$i-1]['pvmnt_type_dtl_rd_life_code'], $sections[$i]['pvmnt_type_dtl_rd_life_code'])) {
				
				// IF IT BELONGS TO THE SAME GROUP, WE GET THE CUMULATIVE LENGTH OF BEGINNING AND ENDING OF THE SECTION
				$sections[$i]['cum_length_beg'] = $cumLength;
				$sections[$i]['cum_length_end'] = $cumLength + $sections[$i]['sect_length'];
				
				// UPDATE CUMULATIVE LENGTH VARIABLE
				$cumLength = $sections[$i]['cum_length_beg'] + $sections[$i]['sect_length'];
				
				// INSERT THIS SECTION TO CURRENT GROUP
				array_push($group, $sections[$i]);
			}
			else {
				// IF IT DOESN'T BELONG TO THE SAME GROUP, WE INSERT THE GROUP INTO $GROUPS WITH TOTAL LENGTH OF THE GROUP				
				array_push($this->groups, array("group"=>$group, "total_length_end"=>$cumLength, "total_length_beg"=>$cumLength-$sections[$i-1]['sect_length']));
				
				// CLEAR GROUP VARIABLE
				unset($group); $group = array();
				
				// NEW GROUP STARTS
				// UPDATE SOME VARIABLES
				$cumLength = $sections[$i]['sect_length'];
				$highway = $sections[$i]['signed_highway_rdbd_id'];
				
				// THIS GROUP'S FIRST SECTIONS'S CULMULTIVE LENGTH
				$sections[$i]['cum_length_beg'] = 0;
				$sections[$i]['cum_length_end'] = $cumLength;
				
				// INSERT THE FIRST SECTION TO THE GROUP
				array_push($group, $sections[$i]);

		//		$this->print_road($sections, $i);				
			}
		}
		array_push($this->groups, array("group"=>$group, "total_length_end"=>$cumLength, "total_length_beg"=>$cumLength-$sections[$i-1]['sect_length']));
		
		unset($group);
		unset($segments);
	}
";}i:2;i:3;i:3;s:2735:" php>
        /**
	 * PRELIMINARY GROUPING
	 */
	private function grouping() {
		// GET SECTIONS
		$sections = $this->sections;

		// IF THE NUMBER OF SECTIONS IS ZERO, WE ARE DONE
		if (count($sections) == 0)
			return;
		
		// INITIALIZE SOME VARIABLES
		$highway = $sections[0]['signed_highway_rdbd_id'];
		$cumLength = $sections[0]['sect_length'];
		
		// FIRST SECTION'S CUMULATIVE LENGTH
		$sections[0]['cum_length_beg'] = 0;
		$sections[0]['cum_length_end'] = $sections[0]['sect_length'];
		
		// INSERT FIRST SECTION
		$this->groups = array();
		$group = array();
		array_push($group, $sections[0]);
		// $this->print_road($sections, 0);
		
		// LOOP START
		for ($i=1 ; $i<count($sections) ; $i++) {
			// DETERMINE THE SECTION BELONGS TO THE SAME GROUP
			// CRITERIA ARE THE SIGNED HIGHWAY ROADBED ID, CONTINUTIY AND PAVEMENT FAMILY
			if ($sections[$i]['signed_highway_rdbd_id'] == $highway &&
					$sections[$i]['beg_ref_marker_nbr'] == $sections[$i-1]['end_ref_marker_nbr'] &&

					// UNCOMMENT THIS LINE IF OFFSET IS CONSIDERED FOR CONTINUITY
 					$sections[$i]['beg_ref_marker_disp'] == $sections[$i-1]['end_ref_marker_disp'] &&
					
					$this->same_pvmnt_family($sections[$i-1]['pvmnt_type_dtl_rd_life_code'], $sections[$i]['pvmnt_type_dtl_rd_life_code'])) {
				
				// IF IT BELONGS TO THE SAME GROUP, WE GET THE CUMULATIVE LENGTH OF BEGINNING AND ENDING OF THE SECTION
				$sections[$i]['cum_length_beg'] = $cumLength;
				$sections[$i]['cum_length_end'] = $cumLength + $sections[$i]['sect_length'];
				
				// UPDATE CUMULATIVE LENGTH VARIABLE
				$cumLength = $sections[$i]['cum_length_beg'] + $sections[$i]['sect_length'];
				
				// INSERT THIS SECTION TO CURRENT GROUP
				array_push($group, $sections[$i]);
			}
			else {
				// IF IT DOESN'T BELONG TO THE SAME GROUP, WE INSERT THE GROUP INTO $GROUPS WITH TOTAL LENGTH OF THE GROUP				
				array_push($this->groups, array("group"=>$group, "total_length_end"=>$cumLength, "total_length_beg"=>$cumLength-$sections[$i-1]['sect_length']));
				
				// CLEAR GROUP VARIABLE
				unset($group); $group = array();
				
				// NEW GROUP STARTS
				// UPDATE SOME VARIABLES
				$cumLength = $sections[$i]['sect_length'];
				$highway = $sections[$i]['signed_highway_rdbd_id'];
				
				// THIS GROUP'S FIRST SECTIONS'S CULMULTIVE LENGTH
				$sections[$i]['cum_length_beg'] = 0;
				$sections[$i]['cum_length_end'] = $cumLength;
				
				// INSERT THE FIRST SECTION TO THE GROUP
				array_push($group, $sections[$i]);

		//		$this->print_road($sections, $i);				
			}
		}
		array_push($this->groups, array("group"=>$group, "total_length_end"=>$cumLength, "total_length_beg"=>$cumLength-$sections[$i-1]['sect_length']));
		
		unset($group);
		unset($segments);
	}
";}i:2;i:6;}i:3;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:0:"";}i:2;i:2748;}i:4;a:3:{i:0;s:7:"p_close";i:1;a:0:{}i:2;i:2748;}i:5;a:3:{i:0;s:12:"document_end";i:1;a:0:{}i:2;i:2748;}}