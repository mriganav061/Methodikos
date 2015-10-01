<?
// This will set up database if there is survey_project database is not existed
include("phps/database.php");
global $database;
$result = mysql_select_db(DB_NAME, $database->connection);
if (!$result) {
	mysql_query("CREATE DATABASE ".DB_NAME, $database->connection);
	$result = mysql_select_db(DB_NAME, $database->connection);
	
	$sqlfile = 'survey_project.sql';
	$contents = file_get_contents($sqlfile);
	
	// Remove comments
	$comment_patterns = array('/\/\*.*(\n)*.*(\*\/)?/', //C comments
	'/\s*--.*\n/', //comments start with --
	'/\s*#.*\n/', //comments start with #
	);
	$contents = preg_replace($comment_patterns, "\n", $contents);
	
	//Retrieve sql statements
	$statements = explode(";\n", $contents);
	$statements = preg_replace("/\s/", ' ', $statements);
	
	foreach ($statements as $query) {
	   if (trim($query) != '') {
		  $result = mysql_query($query);
		  if (!$result) {
			  echo 'Unable to run query ' . $query . ': ' . mysql_error();
		  }
	   }
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>SDSU Survey Installation</title>
</head>
<h1>Database Install Completed</h1>
<a href="index.php">Start from here</a>
<body>
</body>
</html>