<?
/**
 * Database.php
 *
 * Contains database related functions
 *
 */

include("constants.php");

class MySQLDB
{
	var $connection;
	 
	// Constructor
	function MySQLDB() {
		// Make connection
		$this->connection = mysql_connect(DB_SERVER, DB_USER, DB_PASS) or die(mysql_error());
		$result = mysql_select_db(DB_NAME, $this->connection);
		 
		// If there is no database, then create in install.php page
		if (!$result) {
			header("Location: /install.php");
		}
	}

	// Check user's password is valid
	function ConfirmUserPass($email, $password) {
		// For query
		if(!get_magic_quotes_gpc()) {
			$email = addslashes($email);
		}
		 
		// Verify that user is in database
		$query = "SELECT password FROM ".TBL_STU." WHERE email = '$email'";
		$result = mysql_query($query, $this->connection);
		if(!$result || (mysql_numrows($result) < 1)) {
			return 1; // Indicates there is no such a email
		}

		// Retrieve password from result, strip slashes
		$userarray = mysql_fetch_array($result);
		$userarray['password'] = stripslashes($userarray['password']);
		$password = stripslashes($password);
		 
		// Validate that password is correct
		if($password == $userarray['password']) {
			return 0; // Success! email and password confirmed
		}
		else {
			return 2; // Indicates password is not correct
		}
	}
	 
	// Check email is exist
	function ConfirmEmail($email) {
		// For query
		if(!get_magic_quotes_gpc()) {
			$email = addslashes($email);
		}
		 
		// Verify that email is in database
		$query = "SELECT email FROM ".TBL_STU." WHERE email = '$email'";
		$result = mysql_query($query, $this->connection);
		if(!$result || (mysql_numrows($result) < 1)) {
			return 1; // Indicates there is no such a email
		}

		// Retrieve email from result, strip slashes
		$userarray = mysql_fetch_array($result);
		$userarray['email'] = stripslashes($userarray['email']);
		$email = stripslashes($email);

		// Validate that email is correct
		if($email == $userarray['email']) {
			return 0; // Success! email confirmed
		}
		else {
			return 1; // Indicates email invalid
		}
	}
	 
	// Check user email is being used
	function UserEmailTaken($email) {
		if(!get_magic_quotes_gpc()) {
			$email = addslashes($email);
		}
		$query = "SELECT email FROM ".TBL_STU." WHERE email = '$email'";
		$result = mysql_query($query, $this->connection);
		return (mysql_numrows($result) > 0);
	}
	
	function SettingTaken($settingname, $table) {
		global $session;
		$query = "SELECT `Setting Name` FROM $table WHERE `User ID`=$session->user_id AND `Setting Name`='$settingname'";
		$result = mysql_query($query, $this->connection) or die("SQL Error 1: " . mysql_error());
		return (mysql_numrows($result) > 0);
	}
	 
	// Add new user
	function AddNewUser($email, $password, $firstname, $lastname, $gender, $birth) {
		$query = "INSERT INTO ".TBL_STU." VALUES ('', '$email', '$password', '$firstname', '$lastname', '$gender', '$birth', 0)";
		return mysql_query($query, $this->connection);
	}
	 
	// Add new survey
	function AddNewSurvey($email, $stu_id, $int_stu, $int_loc, $int_cam, $int_atm, $int_dorm,
			$int_spo, $waytoknow, $waytoknow_other, $comments) {
		$query = "INSERT INTO ".TBL_SUR." VALUES ('', '$stu_id', '$int_stu', '$int_loc', '$int_cam', '$int_atm',
		'$int_dorm', '$int_spo', '$waytoknow', '$waytoknow_other', '$comments')";
		$result = mysql_query($query, $this->connection);
		if (!$result)
			return 2;	// Indicate insert survey information failure

		// Also update user's survey field so that this user can't do survey again
		$result = $this->UpdateUserField($email, "survey", 1);
		if (!$result) {
			return 1;	// Success
		}
		else {
			return 0;	// Indicate update user's field failure
		}
	}
	 
	// Update user's field
	function UpdateUserField($email, $field, $value) {
		$q = "UPDATE ".TBL_STU." SET ".$field." = '$value' WHERE email = '$email'";
		return mysql_query($q, $this->connection);
	}
	 
	// Get user's information
	function GetUserInfo($email) {
		$query = "SELECT * FROM ".TBL_STU." WHERE email = '$email'";
		$result = mysql_query($query, $this->connection);

		// Error occurred
		if(!$result || (mysql_numrows($result) < 1)) {
			return NULL;
		}

		// Return result
		$userarray = mysql_fetch_array($result);
		return $userarray;
	}
	 
	// Get number of survey field with specified value
	function GetNumber($field, $seek) {
		$query = "SELECT * FROM ".TBL_SUR." WHERE $field = '$seek'";
		$result = mysql_query($query, $this->connection);
		if (!$result) {
			return -1;
		}
		return mysql_numrows($result);
	}
	 
	// Simple query
	function Query($query) {
		$result = mysql_query($query, $this->connection) or die("SQL Error 1: " . mysql_error());
		$rows = array();
		$count = 0;
		while($r = mysql_fetch_assoc($result)) {
			$rows[] = $r;
			$count++;
		}
		return $rows;
	}
	 
	function InsertQuery($global_arr, $table) {
		
		for($i=0; $i<count($global_arr); $i++) // this is faster than foreach
		{
			// NOW use what ardav suggested
			if (is_array($global_arr[$i])) {
				foreach($global_arr[$i] as $key => $value){
					$sql[] = (is_numeric($value)) ? "`$key` = $value" : "`$key` = '" . mysql_real_escape_string($value) . "'";
				}
			}
			$sqlclause = implode(",",$sql);
			unset($sql);
			//$rs = mysql_query("INSERT INTO $table SET ".$sqlclause) or die(mysql_error());
		} // for i
	}
	 
	function QueryCount($query) {
		$result = mysql_query($query, $this->connection) or die("SQL Error 1: " . mysql_error());
		return mysql_numrows($result);
	}
	 
	 
	function QueryPMIS($select, $district, $fiscal_year, $highway, $beg_rm, $beg_disp, $rating_cycle_code, $orderby) {
		$query = "SELECT ".$select." FROM pmis_condition_summary_".$district;
		$where_clause = array();
		if ($fiscal_year != "") {
			if ($fiscal_year[0] == "(")
				array_push($where_clause, $fiscal_year!=""?"FISCAL_YEAR%IN%".$fiscal_year:"");
			else
				array_push($where_clause, $fiscal_year!=""?"FISCAL_YEAR=".$fiscal_year:"");
		}

		array_push($where_clause, $highway!=""?"SIGNED_HIGHWAY_RDBD_ID='".$highway."'":"");
		array_push($where_clause, $beg_rm!=""?"BEG_REF_MARKER_NBR='".$beg_rm."'":"");
		array_push($where_clause, $beg_disp!=""?"BEG_REF_MARKER_DISP=".$beg_disp:"");
		array_push($where_clause, $rating_cycle_code!=""?"RATING_CYCLE_CODE='".$rating_cycle_code."'":"");
		array_push($where_clause, "PVMNT_TYPE_BROAD_CODE='A'");
		$where = "";
		for ($i=0 ; $i<count($where_clause) ; $i++) {
			if ($where_clause[$i] != "")
				$where .= $where_clause[$i]." ";
		}
		$where = $where[strlen($where)-1]==' '?substr($where, 0, -1):$where;
		$where = str_replace(" ", " AND ", $where);
		$where = str_replace("%", " ", $where);
		$query .= $where!=""?" WHERE ".$where:"";
		$query .= $orderby;
		//print $query;
		return $this->Query($query);
	}
	 
	function GetColumns($district) {
		$query = "SHOW FULL COLUMNS FROM pmis_condition_summary_".$district;
		return $this->Query($query);
	}
	 
};

// Create database instance
$database = new MySQLDB;
?>