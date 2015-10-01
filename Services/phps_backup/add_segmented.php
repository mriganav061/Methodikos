<?php
include('config.php');

//connection String
$connect = mysql_connect($dbhost, $dbuser, $dbpass) or die('Could not connect: ' . mysql_error());

//select database
mysql_select_db($dbname, $connect);

//Select The database
$bool = mysql_select_db($dbname, $connect);
if ($bool === False){
	print "can't find $dbname";
}

$res = json_decode(stripslashes($_POST['json']), true);
$res[php_message] = "I am PHP";
$f = stripslashes($_POST['json']);
$f = substr($f, 1, -2);
$arr = explode('},',$f);  // Prepare for json_decode BUT last } missing
$global_arr = array(); // Contains each decoded json (TABLE ROW)
$global_keys = array(); // Contains columns for SQL
if(!function_exists('json_decode')) die('Your host does not support json');
for($i=0; $i<count($arr); $i++)
{
	$decoded = json_decode($arr[$i].'}',true); // Reappend last } or it will return NULL
	//if ($i==0 || $i+1 == count($arr))
	//	var_dump($arr[$i]);
	$global_arr[] = $decoded;
	if (is_array($decoded)) {
		foreach($decoded as $key=> $value)
		{
			$global_keys[$key] = '';
		}
	}
}
$query = "DROP TABLE IF EXISTS `segmented_pmis`";
mysql_query($query) or die('mysql_error: '.mysql_error());

// CREATE SQL TABLE
$query = "CREATE TABLE IF NOT EXISTS `segmented_pmis` (";
$query .= "
`FISCAL_YEAR` smallint(5) NOT NULL,
`SIGNED_HIGHWAY_RDBD_ID` varchar(8) NOT NULL,
`BEG_REF_MARKER_NBR` varchar(5) NOT NULL,
`BEG_REF_MARKER_DISP` double(15,5) NOT NULL,
`RATING_CYCLE_CODE` varchar(1) NOT NULL,
`SEGMENT_ID` varchar(20) DEFAULT NULL,
PRIMARY KEY (`FISCAL_YEAR`,`SIGNED_HIGHWAY_RDBD_ID`,`BEG_REF_MARKER_NBR`,`BEG_REF_MARKER_DISP`,`RATING_CYCLE_CODE`))";

mysql_query($query) or die('mysql_error: '.mysql_error());
// iterate $global_arr
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
	//if ($i == 0 || $i+1 == count($global_arr))
	//var_dump($sqlclause);
	$rs = mysql_query("INSERT INTO `segmented_pmis` SET ".$sqlclause) or die(mysql_error());
} // for i
//

?>