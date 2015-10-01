<?php include("header.php") ?>
<tr><td valign="top" align="center"><h1> SDSU New Students Registation.</h1><br />
<img src="img/SDSULogo2.jpg" /></td></tr><tr><td height="40px" align="center">
<?php
// If user logged in
if ($session->logged_in) {
	echo "<h1>Not authorized</h1>";
	echo "<p><b>$session->stu_name</b>, you are already logged in. "."<a href=\"index.php\">Main</a>.</p>";
}
// If registration is succeed
else if (isset($_SESSION['regsuccess'])) {
	// Registration was successful
	if ($_SESSION['regsuccess']) {
		$userinfo = $_SESSION['value_array'];
		$date = $userinfo['date'];
		if ($userinfo['gender'] == "M")
			$gender = "Male";
		else
			$gender = "Female";
		// Print User's registration information
		echo "<h1>Registered!</h1>";
		echo "<p><b>" . $_SESSION['regemail'] . "</b>, information has been successfully added to the database.</p>";
		echo "<table border='1' cellpadding='0' cellspacing='0'><tr><td align='center'>Field</td><td>Your information</td></tr>";
		echo "<tr><td align='center'>Your Email</td><td>$userinfo[email]</td></tr>";	  
		echo "<tr><td align='center'>Your Name</td><td>$userinfo[firstname]  $userinfo[lastname]</td></tr>";
		echo "<tr><td align='center'>Your Gender</td><td>$gender</td></tr>";
		echo "<tr><td align='center'>Your Birthday</td><td>$date[0]/$date[1]/$date[2]</td></tr>";
		echo "</table>";
		echo "<p><b>$userinfo[firstname] $userinfo[lastname]</b>, you can login here. "."<a href=\"login.php\">Login</a>.</p>";
		
		// Send Email
		error_reporting(E_STRICT);		
		date_default_timezone_set('America/Toronto');		
		require_once('PHPMailer/class.phpmailer.php');
		$mail = new PHPMailer();
		$body = "<table border='1' cellpadding='0' cellspacing='0'><tr><td align='center'>Field</td><td>Your information</td></tr>"
		."<tr><td align='center'>Your Email</td><td>$userinfo[email]</td></tr>"
		."<tr><td align='center'>Your Password</td><td>$userinfo[pass]</td></tr>"
		."<tr><td align='center'>Your Name</td><td>$userinfo[firstname]  $userinfo[lastname]</td></tr>"
		."<tr><td align='center'>Your Gender</td><td>$gender</td></tr>"
		."<tr><td align='center'>Your Birthday</td><td>$date[0]/$date[1]/$date[2]</td></tr>"
		."</table>";
		$body = eregi_replace("[\]",'',$body);
		// telling the class to use SMTP
		$mail->IsSMTP();
		$mail->Host       = "stmp.gmail.com"; 	   // SMTP server
		
		// enables SMTP debug information (for testing)
 	    // 1 = errors and messages
        // 2 = messages only
		$mail->SMTPDebug  = 1;                     
		$mail->SMTPAuth   = true;                  // enable SMTP authentication
		$mail->SMTPSecure = "ssl";                 // sets the prefix to the servier
		$mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
		$mail->Port       = 465;                   // set the SMTP port for the GMAIL server
		$mail->Username   = "youngkwon.cha@gmail.com";  // GMAIL username
		$mail->Password   = "m329045fg";            // GMAIL password
		$mail->SetFrom('youngkwon.cha@gmail.com', 'Young Cha');
		$mail->AddReplyTo("youngkwon.cha@gmail.com","Young Cha");
		$mail->Subject    = "Thank you for Registration!";
		$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test		
		$mail->MsgHTML($body);		
		$address = $userinfo['email'];
		$mail->AddAddress($address, "Youngkwon");		
		if(!$mail->Send()) {
		  echo "Mailer Error: " . $mail->ErrorInfo;
		} else {
		  echo "Your registration information sent to your email!";
		}
	}
	// Registration failed
	else {          
		echo "<h1>Registration Failed</h1>";
		echo "<p>We're sorry, but an error has occurred and your registration for the username <b>" . $_SESSION['regemail'] . "</b>, " . "could not be completed.<br>Please try again at a later time.</p>";
	}
	unset($_SESSION['regsuccess']);
	unset($_SESSION['reguid']);
}
// If not logged in, display form
else {
?>
<!-- Register Form -->
<form name="form_register" action="process.php" method="POST" onreset="return ResetRegisterForm();">
    <table align="center" border="0" cellspacing="1" cellpadding="0" width="650px">
    <tr>
		<td id="register" width="30%" align="right">Email:&nbsp;&nbsp;</td>
		<td align="left" width="40%"><input type="text" name="email" id="email" maxlength="30" onFocus="DisplayMsg(this, 'Enter your email\n(user@domain)', 'false')" onBlur="DisplayMsg(this, '', 'false')"></td>
		<td height="30px"> <label id="emailLabel"></label></td>
    </tr>
    <tr>
		<td id="register" align="right">First Name:&nbsp;&nbsp;</td>
		<td align="left"><input type="text" name="firstname" id="firstname" maxlength="30" onFocus="DisplayMsg(this, 'Enter your first name\n(alphanumeric, 2-255)', 'false')" onBlur="DisplayMsg(this, '', 'false')"></td>
		<td height="30px"> <label id="firstnameLabel"></label></td><br />
    </tr>
    <tr>
		<td id="register" align="right">Last Name:&nbsp;&nbsp;</td>
		<td align="left"><input type="text" name="lastname" id="lastname" maxlength="30" onFocus="DisplayMsg(this, 'Enter your last name\n(alphanumeric, 2-255)', 'false')" onBlur="DisplayMsg(this, '', 'false')"></td>
        <td height="30px"><label id="lastnameLabel"></label></td><br />
    </tr>        
    <tr>
		<td id="register" align="right">Password:&nbsp;&nbsp;</td>
		<td align="left"><input type="password" name="pass" id="pass" maxlength="30" onFocus="DisplayMsg(this, 'Enter your password\n(alphanumeric, 4-12)', 'false')" onBlur="DisplayMsg(this, '', 'false')"></td>
        <td height="30px"><label id="passLabel"></label></td>
    </tr>    
    <tr>
		<td id="register" align="right">Password(Confirm):&nbsp;&nbsp;</td>
		<td align="left"><input type="password" name="passconfirm" id="passconfirm" maxlength="30" onFocus="DisplayMsg(this, 'Enter your password again', 'false')" onBlur="DisplayMsg(this, '', 'false')"></td>
        <td height="30px"><label id="passconfirmLabel"></label></td>
    </tr>
    <tr>
        <td id="register" align="right">Gender:&nbsp;&nbsp;</td>
        <td align="left"><input type="radio" name="gender" value="M" checked onFocus="DisplayMsg(this, 'Select your gender', 'false')" onBlur="DisplayMsg(this, '', 'false')">Male
            <input type="radio" name="gender" value="F" onFocus="DisplayMsg(this, 'Select your gender', 'false')" onBlur="DisplayMsg(this, '', 'false')">Female</td>
        <td><label id="genderLabel"></label></td>
    </tr>
    <tr>
        <td id="register" align="right">Birth Date:&nbsp;&nbsp;</td>
        <td align="left"><script>FillSelect(document.form_register);YearInstall(document.form_register);</script></td>
    </tr>    
    <tr>
        <td colspan="3" align="center"><input type="hidden" name="register" value="1"><input type="submit" value="Register"><input type="reset" value="Cancel"></td>
    </tr>
    </table>
</form>
<?php
  }
  if ($form->Value("email") != "") 
?>
<script>document.getElementById('email').value = "<?php echo $form->Value("email"); ?>"</script>
<?
  if ($form->Value("firstname") != "") 
?>
<script>document.getElementById('firstname').value = "<?php echo $form->Value("firstname"); ?>"</script> 	
<?
  if ($form->Value("lastname") != "") 
?>
<script>document.getElementById('lastname').value = "<?php echo $form->Value("lastname"); ?>"</script> 	
<?
  $fields = array("email", "firstname", "lastname", "pass", "passconfirm", "months", "days", "years");
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
		  if ($fields[$i] == "months")
?>
<script>
var selMonth = document.getElementById("months");
selMonth.options[<?php echo $_SESSION['months']; unset($_SESSION['months']); ?>].selected = true;
</script>
<?php
		  if ($fields[$i] == "days")
?>
<script>
var selDays = document.getElementById("days");
selDays.options[<?php echo $_SESSION['days']; unset($_SESSION['days']);?>].selected = true;
</script>
<?php
		  if ($fields[$i] == "years")
?>
<script>
var selYears = document.getElementById("years");
selYears.options[<?php echo ($_SESSION['years']-1969); unset($_SESSION['years']);?>].selected = true;
</script>
<?php
  	  }
  }
?>
</td></tr>
<?php
  include("footer.php")
?>