<?php
	include_once '../../phps/session.php';
	global $session;
	global $database;
	$district = $_SESSION['district'];
	$baseyear = $_SESSION['baseyear'];
	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			switch ($_GET['querytype']) {
				case 'etc':
					$table = $_GET['params']['table'];
					$fullcolumns = $database->Query("SHOW FULL COLUMNS FROM $table");
					$exist = $database->QueryCount("show tables like '$table'");
					$rehaptype = $_GET['params']['rehaptype'];
					if (isset($rehaptype))
						$where = "where `Rehap Type`='$rehaptype'";
					$foundRows = $database->Query("SELECT count(*) as `found_rows` FROM $table $where");
					$totalrows = $foundRows[0]['found_rows'];
					$result = array(
							'exist' => $exist>=1?true:false,
							'fullcolumns' => $fullcolumns,
							'totalrecords' => $totalrows
					);
					break;
			
				case 'grid':
					$params = $_GET['params'];
					$table = $params['table'];
					$rehaptype = $_GET['params']['rehaptype'];
					$settingname = $_GET['params']['settingname'];
					if (isset($rehaptype))
						$where = "where `Rehap Type`='$rehaptype' and `User ID` in (0,$session->user_id)";
					if (isset($settingname))
						$where .= " and `Setting Name`='$settingname'";
					$fullcolumns = $database->Query("SHOW FULL COLUMNS FROM $table");
					$query = "SELECT SQL_CALC_FOUND_ROWS * FROM $table $where";
					$griddata = $database->Query($query);
					$foundRows = $database->Query("SELECT FOUND_ROWS() AS `found_rows`;");
					$totalrows = $foundRows[0]['found_rows'];
					$result = array(
							'griddata' => $griddata,
							'fullcolumns' => $fullcolumns,
							'totalrecords' => $totalrows
					);
					break;
			
				case 'combo-preset':
					$params = $_GET['params'];
					$table = $params['table'];
					$where = "where `User ID` in (0,$session->user_id)";
					$query = "SELECT DISTINCT `Setting Name` FROM $table $where";
					$presetdata = $database->Query($query);
					$result = array(
							'comboboxdata' => $presetdata
					);
					break;
			}
			break;
			
		case 'POST':
			switch ($_POST['querytype']) {
				case 'add-new-setting':
					// Check Duplicate
					if (strcasecmp($_POST['params']['settingname'],'Default')==0) {
						$result = array(
								'success' => false
						);
					}
					else if ($database->SettingTaken($_POST['params']['settingname'], $_POST['params']['table'])==true) {
						$result = array('success'=>'askupdate');
					}
					else {
						$paramdata = $_POST['params']['data'];
						$f = stripslashes($paramdata);
						$f = substr($f, 1, -2);
						$arr = explode('},',$f);  // Prepare for json_decode BUT last } missing
						$global_arr = array(); // Contains each decoded json (TABLE ROW)
						$global_keys = array(); // Contains columns for SQL
						
						for($i=0; $i<count($arr); $i++)
						{
							$decoded = json_decode($arr[$i].'}',true); // Reappend last } or it will return NULL
							$global_arr[] = $decoded;
							$decoded['User ID'] = $session->user_id;
							$decoded['Rehap Type'] = $_POST['params']['rehaptype'];
							$decoded['Setting Name'] = $_POST['params']['settingname'];
							if (is_array($decoded)) {
								foreach($decoded as $key=> $value)
								{
									$global_keys[$key] = '';
								}
							}
						}
// 						$database->InsertQuery($global_arr, $_POST['params']['table']);
						$result = array(
								'success' => true
						);
					}
					break;
			}
			break;
	}
	
	// get data and store in a json array
	ob_start('ob_gzhandler');
	header('Content-Type: application/json');
	echo json_encode($result);
?>