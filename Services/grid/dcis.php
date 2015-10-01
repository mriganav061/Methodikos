<?php
$district = $SESSION['district'];
switch ($_GET['querytype']) {
	case 'etc':
		include_once '../../phps/database.php';
		global $database;
		if ($_GET['fiscalyears'])
			$year = "(".stripslashes($_GET['fiscalyears']).")";
		else
			$year = "(2011)";
		$SQL = "SELECT count(*) as `found_rows` FROM dcis_project_information where FISCAL_YEAR in $year";
		$foundRows = mysql_query( $SQL ) or die("Couldn t execute query.".mysql_error());
		$foundRows = mysql_fetch_assoc($foundRows);
		$total_rows = $foundRows['found_rows'];
		$SQL = "SHOW FULL COLUMNS FROM dcis_project_information";
		$result_col = mysql_query( $SQL ) or die("Couldn t execute query.".mysql_error());
		$SQL = "SELECT DISTINCT FISCAL_YEAR FROM dcis_project_information WHERE FISCAL_YEAR<=2011 ORDER BY FISCAL_YEAR DESC";
		$result_year = mysql_query( $SQL ) or die("Couldn t execute query.".mysql_error());
		$columns = array();
		$years = array();
		$count = 0;
		while($r = mysql_fetch_assoc($result_col)) {
			$columns[] = $r;
			$count++;
		}
		$count = 0;
		while($r = mysql_fetch_assoc($result_year)) {
			$years[] = $r;
			$count++;
		}
		$data = array(
				'fullcolumns' => $columns,
				'fiscalyears' => $years,
				'totalrecords' => $total_rows
		);
		break;
	case 'grid':
		include_once '../../phps/database.php';
		global $database;
		if ($_GET['fiscalyears'])
			$year = "(".stripslashes($_GET['fiscalyears']).")";
		else
			$year = "(2011)";
		$pagenum = isset($_GET['pagenum'])?$_GET['pagenum']:0;
		$pagesize = isset($_GET['pagesize'])?$_GET['pagesize']:5000;
		$start = $pagenum * $pagesize;
		$SQL = "SELECT SQL_CALC_FOUND_ROWS * FROM dcis_project_information where fiscal_year IN $year LIMIT $start, $pagesize";
		if (isset($_GET['sortdatafield']))
		{
			$sortfield = $_GET['sortdatafield'];
			$sortorder = $_GET['sortorder'];
			$result = mysql_query($SQL) or die("SQL Error 1: " . mysql_error());
			$sql = "SELECT FOUND_ROWS() AS `found_rows`;";
			$rows = mysql_query($sql);
			$rows = mysql_fetch_assoc($rows);
			$total_rows = $rows['found_rows'];

			if ($sortfield != NULL)
			{

				if ($sortorder == "desc")
				{
					$SQL = "SELECT * FROM dcis_project_information where fiscal_year IN $year ORDER BY" . " " . $sortfield . " DESC LIMIT $start, $pagesize";
				}
				else if ($sortorder == "asc")
				{
					$SQL = "SELECT * FROM dcis_project_information where fiscal_year IN $year ORDER BY" . " " . $sortfield . " ASC LIMIT $start, $pagesize";
				}
				$result = mysql_query($SQL) or die("SQL Error 1: " . mysql_error());
			}
		}
		else
		{
			$result = mysql_query($SQL) or die("SQL Error 1: " . mysql_error());
			$SQL = "SELECT FOUND_ROWS() AS `found_rows`;";
			$rows = mysql_query($SQL);
			$rows = mysql_fetch_assoc($rows);
			$total_rows = $rows['found_rows'];
		}
		// 		$result = mysql_query( $SQL ) or die("Couldn t execute query.".mysql_error());
		// 		$sql = "SELECT FOUND_ROWS() AS `found_rows`;";
		// 		$rows = mysql_query($sql);
		// 		$rows = mysql_fetch_assoc($rows);
		// 		$total_rows = $rows['found_rows'];
		$SQL = "SHOW FULL COLUMNS FROM dcis_project_information";
		$result_col = mysql_query( $SQL ) or die("Couldn t execute query.".mysql_error());
		$SQL = "SELECT DISTINCT FISCAL_YEAR FROM dcis_project_information ORDER BY FISCAL_YEAR DESC";
		$result_year = mysql_query( $SQL ) or die("Couldn t execute query.".mysql_error());
		$rows = array();
		$columns = array();
		$years = array();

		$count = 0;
		while($r = mysql_fetch_assoc($result)) {
			$rows[] = $r;
			$count++;
		}
		$count = 0;
		while($r = mysql_fetch_assoc($result_col)) {
			$columns[] = $r;
			$count++;
		}
		$count = 0;
		while($r = mysql_fetch_assoc($result_year)) {
			$years[] = $r;
			$count++;
		}
		$data = array(
				'dcisdata' => $rows,
				'fullcolumns' => $columns,
				'fiscalyears' => $years,
				'totalrecords' => $total_rows
		);
		break;
}

// get data and store in a json array
ob_start('ob_gzhandler');
header('Content-Type: application/json');
echo json_encode($data);
?>