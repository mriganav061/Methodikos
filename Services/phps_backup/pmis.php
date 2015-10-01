<?php
/*
 * Simple PHP page that checks if all HTTP request methods are supported by your apache installation
 * and directly writes the parameters back as json
 */
$page = $_GET['page']; // get the requested page
$limit = $_GET['rows']; // get how many rows we want to have into the grid
$sidx = $_GET['sidx']; // get index row - i.e. user click to sort
$sord = $_GET['sord']; // get the direction
if(!$sidx) $sidx =1;
// connect to the database
$db = mysql_connect("txpave.com", "txpave5_root", "34802818")
or die("Connection Error: " . mysql_error());

mysql_select_db("txpave5_txpave") or die("Error conecting to db.");
$result = mysql_query("SELECT COUNT(*) AS count FROM pmis_condition_summary_bryan");
$row = mysql_fetch_array($result,MYSQL_ASSOC);
$count = $row['count'];

if( $count >0 ) {
	$total_pages = ceil($count/$limit);
} else {
	$total_pages = 0;
}
if ($page > $total_pages) $page=$total_pages;
$start = $limit*$page - $limit; // do not put $limit*($page - 1)
$SQL = "SELECT SIGNED_HIGHWAY_RDBD_ID FROM pmis_condition_summary_bryan ORDER BY $sidx $sord";
$result = mysql_query( $SQL ) or die("Couldn t execute query.".mysql_error());

$responce->page = $page;
$responce->total = $total_pages;
$responce->records = $count;
$i=0;
while($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
    $responce->rows[$i]['id']=$row[SIGNED_HIGHWAY_RDBD_ID];
    $responce->rows[$i]['cell']=array($row[SIGNED_HIGHWAY_RDBD_ID]);
    $i++;
}        
echo json_encode($responce);
 ?>