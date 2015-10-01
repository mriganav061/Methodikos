<?php 
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

define('EOL', (PHP_SAPI=='cli') ? PHP_EOL : '<br />');

require_once 'PHPExcel/Classes/PHPExcel/IOFactory.php';

// DB CONNECTION AUTHENTICATION
define("DB_SERVER", "localhost");
define("DB_USER", "txpave");
define("DB_PASS", "3480");
define("DB_NAME", "txpave");

$dir = 'Upload';

 // create new directory with 777 permissions if it does not exist yet
 // owner will be the user/group the PHP script is run under
 if ( !file_exists($dir) ) {
  mkdir ($dir, 0777);
 }

 file_put_contents ($dir.'/output.xlsx', '');



// MAKE DB CONNECTION
$connection = mysql_connect(DB_SERVER, DB_USER, DB_PASS) or die(mysql_error());
$result = mysql_select_db(DB_NAME, $connection);

if (!$result) {
	echo "DB connection failed";
	return;
}

function query($query) {
	$result = mysql_query($query) or die("SQL Error :". mysql_error());
	$rows = array();
	while ($r = mysql_fetch_assoc($result)) {
		$rows[] = $r;
	}
	return $rows;
}

function getColumn($header, $colarray) {
	foreach ($colarray as $key=>$val) {
		if (in_array($val, $header))
			return array_search($val, $header);
	}
}

function assembleHighway($rawHighway) {
	$rawHighway = str_replace(array("-"), "", $rawHighway);
	preg_match('/(?P<name>[a-zA-z]+)(\s?)(?P<digit>\d{0,4})(?P<suffix>([a-zA-z0-9]*))/', $rawHighway, $partHighway);

	// FOUND DIGIT NULL
	if (!isset($partHighway["digit"]) || $partHighway["digit"] == "")
		return;

	// IF SUFFIX IS EMPTY
	if ($partHighway["suffix"] == "")
		$partHighway["suffix"] = " ";

	$highway = $partHighway["name"].str_pad($partHighway["digit"], 4, "0", STR_PAD_LEFT).$partHighway["suffix"];

	return array("highway"=>$highway, "part"=>$partHighway);
}

function printCalibrated($rdbd, $ih, $dirc, $highway, $fromNbr, $fromDisp, $toNbr, $toDisp, &$column, &$excelRow, &$outputSheet) {
	// BASE YEAR
	$baseYear = 2011;

	$table = "pmis_condition_summary_".$_POST["district"];

	$queryFrom = "SELECT BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP FROM $table WHERE SIGNED_HIGHWAY_RDBD_ID='%s' AND FISCAL_YEAR=$baseYear ORDER BY ABS(CAST('%s' AS SIGNED)+%f-CAST(BEG_REF_MARKER_NBR AS SIGNED)-BEG_REF_MARKER_DISP) ASC LIMIT 1";

	$queryTo = "SELECT END_REF_MARKER_NBR, END_REF_MARKER_DISP FROM $table WHERE SIGNED_HIGHWAY_RDBD_ID='%s' AND FISCAL_YEAR=$baseYear ORDER BY ABS(CAST('%s' AS SIGNED)+%f-CAST(END_REF_MARKER_NBR AS SIGNED)-END_REF_MARKER_DISP) ASC LIMIT 1";

	

	$dirc = preg_replace("/[a-z]/", "", $dirc);
	$arrayDirc = str_split($dirc);
	$tempColumn = $column;
	foreach ($arrayDirc as $direction) {
		$column = $tempColumn;
		if ($ih) {
			if ($direction == "N" || $direction == "E") {
				// MAIN LANE
				if ($rdbd == "Main Lanes" || $rdbd == "ML") {
					$queryHighway = $highway["highway"]."R";
				}
				else {
					$queryHighway = $highway["highway"]."A";	
				}
										
			}
			else if ($direction == "S" || $direction == "W") {
				if ($rdbd == "Main Lanes" || $rdbd == "ML") {
					$queryHighway = $highway["highway"]."L";
				}
				else {
					$queryHighway = $highway["highway"]."X";
				}			
			}
		} else { 
			if ($highway["part"]["name"] == "FM") {
				if ($rdbd == "Main Lanes" || $rdbd == "ML")
					$queryHighway = $highway["highway"]."K";
			}
		}

		if (isset($queryHighway)) {
			$fromQuery = sprintf($queryFrom, $queryHighway, $fromNbr, $fromDisp);
			$toQuery = sprintf($queryTo, $queryHighway, $toNbr, $toDisp);
			
			$fromRows = query($fromQuery);	
			$toRows = query($toQuery);	
			

			$outputSheet->setCellValue(sprintf("%s%d", $column++, $excelRow), $queryHighway);
			
			foreach ($fromRows[0] as $key=>$val) {
				if ($fromDisp>=0) $outputSheet->setCellValue(sprintf("%s%d",$column, $excelRow), $val);
				$column++;
			}					
			foreach ($toRows[0] as $key=>$val) {
				if ($toDisp>=0) $outputSheet->setCellValue(sprintf("%s%d",$column, $excelRow), $val);
				$column++;
			}

			$querySectLength = "SELECT SUM(SECT_LENGTH) AS SL FROM $table WHERE ID>=(SELECT ID FROM $table WHERE BEG_REF_MARKER_NBR='%s' AND BEG_REF_MARKER_DISP=%f AND FISCAL_YEAR=$baseYear AND RATING_CYCLE_CODE='P' AND SIGNED_HIGHWAY_RDBD_ID='%s') AND ID<=(SELECT ID FROM $table WHERE END_REF_MARKER_NBR='%s' AND END_REF_MARKER_DISP=%f AND SIGNED_HIGHWAY_RDBD_ID='%s' AND RATING_CYCLE_CODE='P' AND FISCAL_YEAR=$baseYear) AND RATING_CYCLE_CODE='P';";
			$sectLengthQuery = sprintf($querySectLength, $fromRows[0]['BEG_REF_MARKER_NBR'], $fromRows[0]['BEG_REF_MARKER_DISP'], $queryHighway, $toRows[0]['END_REF_MARKER_NBR'], $toRows[0]['END_REF_MARKER_DISP'], $queryHighway);
			$scRows = query($sectLengthQuery);

			$queryNumLanes = "SELECT NUMBER_THRU_LANES FROM $table WHERE ID>=(SELECT ID FROM $table WHERE BEG_REF_MARKER_NBR='%s' AND BEG_REF_MARKER_DISP=%f AND FISCAL_YEAR=$baseYear AND RATING_CYCLE_CODE='P' AND SIGNED_HIGHWAY_RDBD_ID='%s') AND ID<=(SELECT ID FROM $table WHERE END_REF_MARKER_NBR='%s' AND END_REF_MARKER_DISP=%f AND SIGNED_HIGHWAY_RDBD_ID='%s' AND RATING_CYCLE_CODE='P' AND FISCAL_YEAR=$baseYear) AND RATING_CYCLE_CODE='P' GROUP BY NUMBER_THRU_LANES ORDER BY COUNT(*) DESC LIMIT 1;";

			$numLanesQuery = sprintf($queryNumLanes, $fromRows[0]['BEG_REF_MARKER_NBR'], $fromRows[0]['BEG_REF_MARKER_DISP'], $queryHighway, $toRows[0]['END_REF_MARKER_NBR'], $toRows[0]['END_REF_MARKER_DISP'], $queryHighway);
			$nlRows = query($numLanesQuery);

			$queryPvmntType = "SELECT PVMNT_TYPE_BROAD_CODE FROM $table WHERE ID>=(SELECT ID FROM $table WHERE BEG_REF_MARKER_NBR='%s' AND BEG_REF_MARKER_DISP=%f AND FISCAL_YEAR=$baseYear AND RATING_CYCLE_CODE='P' AND SIGNED_HIGHWAY_RDBD_ID='%s') AND ID<=(SELECT ID FROM $table WHERE END_REF_MARKER_NBR='%s' AND END_REF_MARKER_DISP=%f AND SIGNED_HIGHWAY_RDBD_ID='%s' AND RATING_CYCLE_CODE='P' AND FISCAL_YEAR=$baseYear) AND RATING_CYCLE_CODE='P' GROUP BY PVMNT_TYPE_BROAD_CODE ORDER BY COUNT(*) DESC LIMIT 1;";

			$pvmntTypeQuery = sprintf($queryPvmntType, $fromRows[0]['BEG_REF_MARKER_NBR'], $fromRows[0]['BEG_REF_MARKER_DISP'], $queryHighway, $toRows[0]['END_REF_MARKER_NBR'], $toRows[0]['END_REF_MARKER_DISP'], $queryHighway);
			$ptRows = query($pvmntTypeQuery);


			if ($fromDisp>=0 && $toDisp>=0) {
				$outputSheet->setCellValue(sprintf("%s%d", $column++, $excelRow), $scRows[0]["SL"]);
				if (count($nlRows)>0)
					$outputSheet->setCellValue(sprintf("%s%d", $column++, $excelRow), $nlRows[0]["NUMBER_THRU_LANES"]);
				// else
					// echo $numLanesQuery;
				if (count($ptRows)>0)
					$outputSheet->setCellValue(sprintf("%s%d", $column++, $excelRow), $ptRows[0]["PVMNT_TYPE_BROAD_CODE"]);
				// else
					// echo $pvmntTypeQuery;
			}
			$excelRow++;
			if ($queryHighway[strlen($queryHighway)-1] == "K")
				return;
		}
	}
}

// if ($_FILES["file"]["error"] > 0) {
// 	echo "Error: " . $_FILES["file"]["error"] . "<br>";
// } else {
// 	echo "Upload: " . $_FILES["file"]["name"] . "<br>";
// 	echo "Type: " . $_FILES["file"]["type"] . "<br>";
// 	echo "Size: " . ($_FILES["file"]["size"]/1024) . " kB<br>";
// 	echo "Stored in: " . $_FILES["file"]["tmp_name"] . " <br>";
// }
move_uploaded_file($_FILES["file"]["tmp_name"], "Upload/" . $_FILES["file"]["name"]);

// echo date('H:i:s') , " Load from Excel2007 file" , EOL;

$inputFileName = $_FILES['file']['name'];
$inputFileName = "Upload/$inputFileName";
$inputFileType = PHPExcel_IOFactory::identify($inputFileName);
$objReader = PHPExcel_IOFactory::createReader($inputFileType);
$objPHPExcel = $objReader->load("$inputFileName");

// echo date('H:i:s') , " Processing..." , EOL;

// $objPHPExcel = PHPExcel_IOFactory::load("Upload/$inputFileName");

// $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
// var_dump($sheetData);

// Define Column Alias
$keyColumns = array(
	array("yearColumn", "YEAR"),
 	array("districtColumn", "DISTRICT"),
 	array("highwayColumn", "HWY", "Highway Number"),
	array("fromColumn", "TRM From"),
	array("fromDispColumn", "TRM From Displ"),
	array("toColumn", "TRM To"),
	array("toDispColumn", "TRM To Displ"),
	array("rdbdColumn", "Roadbed"),
	array("dirColumn", "Direction")
);
// LOOP THROUGH DATA
$sheetCount = $objPHPExcel->getSheetCount();

// PREPARE EXCEL FILE
// CREATE NEW PHPEXCEL OBJECT
$objPHPOutputExcel = new PHPExcel();

for ($sheetNum=0 ; $sheetNum<$sheetCount ; $sheetNum++) {
	unset($sheetData);
	$sheetData = $objPHPExcel->getSheet($sheetNum)->toArray(null,true,true,true);
	$header = $sheetData[1];

	// var_dump($sheetData);
	foreach ($keyColumns as $key=>$val) {
		// echo $val[0];
		$$val[0] = getColumn($header, array_splice($val, 1));
		// var_dump($$val[0]);
	}

	if ($sheetNum!=0) $objPHPOutputExcel->createSheet();
	$objPHPOutputExcel->setActiveSheetIndex($sheetNum);
	$outputSheet = $objPHPOutputExcel->getActiveSheet();


	// echo "Total found worksheets in Excel: $sheetCount <br>";

	$count = count($sheetData);
	$excelRow = 1;

	// echo "<table border=0>";
	for ($i=2 ; $i<=$count ; $i++) {
		$highway = assembleHighway($sheetData[$i][$highwayColumn]);
		$rdbd = $sheetData[$i][$rdbdColumn];
		$dirc = $sheetData[$i][$dirColumn];
		// var_dump($highway);
		$column = 'A';
		// FIRST WRITE ORIGINAL
		foreach ($sheetData[$i] as $key=>$val) {
			$outputSheet->setCellValue(sprintf("%s%d",$column,$excelRow), $val);
			$column++;
		}

		// NEED TO PRINT EMPTY DATA
		if (!$highway) {
			$excelRow++;
			continue;
		}

		
		$column++;
		unset($rows);	

		// DETERMINE ROADBED
		// INTERSTATE
		if ($highway["part"]["name"] == "IH") {
			// $highway["highway"];
			printCalibrated($rdbd, true, $dirc, $highway, $sheetData[$i][$fromColumn], $sheetData[$i][$fromDispColumn], $sheetData[$i][$toColumn], $sheetData[$i][$toDispColumn], &$column, &$excelRow, &$outputSheet);		
		}
		// NON INTERSTATE
		else {
			// $highway["highway"];
			if ($highway["part"]["name"] == "FM" && ($rdbd == "Main Lanes" || $rdbd == "ML"))
			printCalibrated($rdbd, false, $dirc, $highway, $sheetData[$i][$fromColumn], $sheetData[$i][$fromDispColumn], $sheetData[$i][$toColumn], $sheetData[$i][$toDispColumn], &$column, &$excelRow, &$outputSheet);
			else
				$excelRow++;
		}

		// echo "<tr>";
		// echo "<td>" . $rows[0]['SIGNED_HIGHWAY_RDBD_ID'] . "</td><td>" . $rows[0]['BEG_REF_MARKER_NBR'] . "</td><td>" . $rows[0]['BEG_REF_MARKER_DISP'] . "</td><td>" . $sheetData[$i][$highwayColumn] . "</td><td>" . $sheetData[$i][$fromColumn] . "</td><td>" . $sheetData[$i][$fromDispColumn] . "</td>";
		// echo "</tr>";

		// echo $highwayColumn;
		// echo "<tr>";

		// echo "<td>" . $highway["highway"]  . "</td>";

		// echo "<tr>";
		// $excelRow++;
	}
	// echo "</table>";
}

// Redirect output to a client’s web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="output.xlsx"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPOutputExcel, 'Excel2007');
$objWriter->save('php://output');
exit;

// 

// for ($i=0 ; $i<$sheetCount ; $i++) {
// 	$sheetData = $objPHPExcel->getSheet($i)->toArray(null,true,true,true);
// 	var_dump($sheetData);
// }

// $data =  array();
// $worksheet = $objPHPExcel->getActiveSheet();
// foreach ($worksheet->getRowIterator() as $row) {
// 	$cellIterator = $row->getCellIterator();
// 	$cellIterator->setIterateOnlyExistingCells(false);
// 	foreach ($cellIterator as $cell) {
// 		$data[$cell->getRow()][$cell->getColumn()] = $cell->getValue();
// 	}
// }
// var_dump($sheetData);

?>
