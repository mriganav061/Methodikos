<?php include("phps/session.php"); ?>
<?php
if($session->logged_in) {
	header ("Location: /index.php");
}
else {
}
?>

<!doctype html>

<head>

<!-- Basics -->

<title>Methodikos</title>

<!-- CSS -->
<link rel="shortcut icon" href="favicon.ico" >
<link rel="stylesheet" href="Content/desktop/reset.css">
<link rel="stylesheet" href="Content/desktop/animate.css">
<link rel="stylesheet" href="Content/desktop/login-box.css">
<link rel="stylesheet" href="Content/desktop/font-awesome-4.2.0/css/font-awesome.min.css">
<script type="text/javascript" src="Scripts/libs/validation.js"></script>
<script type="text/javascript" src="Scripts/libs/misc.js"></script>
<script type="text/javascript">
window.onload = function() {
var select = document.getElementById('district-select');
var districts = ["Abilene", "Amarillo", "Atlanta", "Austin", "Beaumont", "Brownwood", "Bryan", "Childress", "Corpus Christi", "Dallas", "El Paso", "Fort Worth", "Houston", "Laredo", "Lubbock", "Lufkin", "Odessa", "Paris", "Pharr", "San Angelo", "San Antonio", "Tyler", "Waco", "Wichita Falls", "Yoakum"];
for (var i=0; i<districts.length; i++)
{
	var opt = document.createElement("option");
    
    // Add an Option object to Drop Down/List Box
    document.getElementById('district-select').options.add(opt);
    // Assign text and value to Option object
    opt.text = districts[i];
    opt.value = districts[i];
	if (districts[i] == "Fort Worth" || districts[i] == "Lubbock" || districts[i] == "Tyler" || districts[i] == "Bryan" || districts[i] == "San Antonio")
	{
		if (districts[i] == "Bryan")	
			opt.selected = true;
		opt.disabled = false;
	}
	else
	{
		opt.disabled = true;
	}
}
};


</script>
</head>

<!-- Main HTML -->

<body>
	<!-- Login Form -->
	<!-- Begin Page Content -->
<div id="outercontainer">
	<div id="headercontainer">
		<p id="Header"> Methodikos<p>
	</div>
	<div id="leftcontainer">
	<!--	<a href="/" class="logo"></a>-->
		<p id="Motto"> Maintain your roadway assets methodically.<p><br><br>
        <i class="fa fa-database fa-3x"> </i> <i id="headline1"> &nbsp; Access your roadway data anytime, anywhere, and from any device.</i><br><br><br>
        <i class="fa fa-signal fa-3x"> </i> <i id="headline1"> &nbsp; Forecast the condition of your roadway assets.</i><br><br><br>
        <i class="fa fa-bullseye fa-3x"> </i> <i id="headline1"> &nbsp; Form and prioritize your maintenance and rehabilitation projects.</i><br><br><br>
        <i class="fa fa-file-text-o fa-3x"> </i> <i id="headline1"> &nbsp; Generate asset management reports.</i><br>
        </div>
		<div id="container">
		<!-- <a href="/" class="logo"></a> -->
		<form action="process.php" method="POST" onreset="ResetLogin();">
			<label for="username">Email:</label> <input type="text"
				id="email" name="email"> <label for="password">Password:</label>

			<p><a href="#">Forgot your password?</a></p> <input type="password"
				name="pass" id="pass">
			
			<label for="username">District:</label>
			<div class="styled-select">
				<select id="district-select" name="district-select">
				</select>
			</div>
			<div id="lower">

				<input type="checkbox"><label class="check" for="checkbox">Keep me
					logged in</label> <input type="submit" value="Login">
			</div>
			<input type="hidden" name="login" value="1">
			<!--/ lower-->
			<label id="emailLabel"><br /></label><label id="passLabel"></label>
		</form>

	</div>
	<!--/ lowercontainer for about, language, etc.-->
	<!--<div id="lowercontainer">about</div>-->
	<!-- End Content Part -->
</div>






	<?php
	if ($form->Value("email") != "")
		?>
	<script>document.getElementById('email').value = "<?php echo $form->Value("email"); ?>"</script>
	<?
	if ($form->Value("pass") != "")
		?>
	<script>document.getElementById('pass').value = "<?php echo $form->Value("pass"); ?>"</script>
	<?
	$fields = array("email", "pass");
	for ($i=0 ; $i<count($fields) ; $i++) {
		if ($form->Error($fields[$i]) != "") {
			?>
	<script>DisplayMsg(document.getElementById("<?php echo $fields[$i]; ?>"), "<?php echo $form->Error($fields[$i]); ?>", false);</script>
	<?php
		}
		else {
			?>
	<script>DisplayMsg(document.getElementById("<?php echo $fields[$i]; ?>"), "", true);</script>
	<?php
		}
	}

	?>

<footer id="main">
   <a>&copy; 2014 Methodikos. All rights reserved.</a>
</footer>
</body>
</html>
