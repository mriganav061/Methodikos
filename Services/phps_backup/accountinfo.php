<?php include ("header.php") ?>
<tr><td valign="top" align="center"><h1>User Account Information</h1><br />
<img src="img/jacks.jpg" /></td></tr><tr><td height="40px" align="center"><br />
<?php
// If update is succeed
if(isset($_SESSION['useredit'])) {
   unset($_SESSION['useredit']);   
   echo "<h1>User Account Information Updated!</h1>";
   echo "<p><b>$session->stu_name</b>, your account has been successfully updated. </p>";
}
// Ohterwise
else {
   // Requested email error checking
   $req_user = trim($_GET['email']);
   if(!$req_user || strlen($req_user) == 0 ||
      !eregi("^[_+a-z0-9-]+(\.[_+a-z0-9-]+)*"
                    ."@[a-z0-9-]+(\.[a-z0-9-]{1,})*"
                    ."\.([a-z]{2,}){1}$", $req_user) ||
      !$database->UserEmailTaken($req_user)) {
      die("Email not registered");
   }
   // Logged in user viewing own account
   if(strcmp($session->email,$req_user) == 0) {
	   // Display requested user information
	   $req_user_info = $database->GetUserInfo($req_user);
?>
<!-- Update Form -->
<form action="process.php" method="POST" onreset="ResetUpdate();">
<table align="center" border="0" cellspacing="1" cellpadding="0" width="600px">
	<tr>
    	<td width="30%" height="30px" align="right">Email:&nbsp;&nbsp;</td>
        <td width="40%"><? echo $req_user_info['email'] ?></td>
        <td width="40%"></td>
    </tr>
	<tr>
    	<td align="right">Current Password:&nbsp;&nbsp;</td>
        <td><input type="password" name="curpass" id="curpass" maxlength="30" onFocus="DisplayMsg(this, 'Enter your password\n(alphanumeric, 4-12)', 'false')" onBlur="DisplayMsg(this, '', 'false')"></td>
        <td height="30px"><label id="curpassLabel"></label></td>
    </tr>
	<tr>
    	<td height="30px" align="right">New Password:&nbsp;&nbsp;</td>
        <td><input type="password" name="newpass" id="newpass" maxlength="30" onFocus="DisplayMsg(this, 'Enter new password\n(alphanumeric, 4-12)', 'false')" onBlur="DisplayMsg(this, '', 'false')"></td>
        <td height="30px"><label id="newpassLabel"></label></td></tr>
	<tr>
    	<td height="30px" align="right">New Password(Confirm):&nbsp;&nbsp;</td>
        <td><input type="password" name="passconfirm" id="passconfirm" maxlength="30" onFocus="DisplayMsg(this, 'Enter your password again', 'false')" onBlur="DisplayMsg(this, '', 'false')"></td>
        <td height="30px"><label id="passconfirmLabel"></label></td>
    </tr>
	<tr>
    	<td height="30px" align="right">User Name:&nbsp;&nbsp;</td>
        <td><? echo $req_user_info['firstname']." ".$req_user_info['lastname']; ?></td>
    </tr>
	<tr>
    	<td height="30px" align="right">Gender:&nbsp;&nbsp;</td>
        <td><? if ($req_user_info['gender'] == "M") echo "Male"; else echo "Female"; ?></td>
    </tr>
	<tr>
    	<td height="30px" align="right">Birth Date:&nbsp;&nbsp;</td>
        <td><? echo $req_user_info['birthdate']; ?></td>
    </tr>
	<tr><td colspan="3" align="center"><br>
		<input type="hidden" name="update" value="1">
		<input type="submit" value="Change"><input type="reset" value="Cancel">
    </td></tr>
</table>
</form>
<?php
      if ($form->Value("curpass") != "") 
?>
<script>document.getElementById('curpass').value = "<?php echo $form->Value("curpass"); ?>"</script>
<?
      $fields = array("curpass", "newpass", "passconfirm");
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
   }
   // User not logged in
   else {
      echo "<h1>You need login first</h1>";
   }
}
?>
</tr></td>
<?php include ("footer.php") ?>