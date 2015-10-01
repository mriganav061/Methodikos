<?
/**
 * Session.php
 */
include("database.php");
include("form.php");

class Session
{
   var $email;				// current logged-in student email
   var $user_name;			// current logged-in student name
   var $userinfo = array(); //The array holding all user info
   var $user_id;				// Student id
   var $logged_in;    		// True if user is logged in, false otherwise
   var $url;          		// The page url current being viewed
   var $referrer;     		// Last recorded site page viewed
   
   // Class constructor
   function Session() {
      $this->StartSession();
   }

   // Start Session
   function StartSession() {
      global $database; 	// The database connection
      session_start();		// Tell PHP to start the session

      // Determine if user is logged in
      $this->logged_in = $this->CheckLogin();

      // Set referrer page
      if(isset($_SESSION['url'])) {
         $this->referrer = $_SESSION['url'];
      }
	  else {
         $this->referrer = "/";
      }

      // Set current url
      $this->url = $_SESSION['url'] = "/index.php";
   }

   // Check user is logged in already
   function CheckLogin() {
      global $database;		// The database connection

      // If session email is set
      if(isset($_SESSION['email'])) {
         // Check that is valid
         if($database->ConfirmEmail($_SESSION['email']) != 0) {
            // If not valid, then user is not logged in
            unset($_SESSION['email']);
            return false;
         }

         // User is logged in, set class variables
         $this->userinfo  = $database->GetUserInfo($_SESSION['email']);
		 $this->user_id = $_SESSION['stu_id'] = $this->userinfo['id'];
		 $this->email = $_SESSION['email'];
         $this->user_name  = $this->userinfo['firstname']." ".$this->userinfo['lastname'];
         return true;
      }
      // User not logged in
      else {	
         return false;
      }
   }

   // Login with email and password
   function Login($email, $pass) {
      global $database, $form;	// The database and form object

      // Email error checking 
      $field = "email";			// Use field name for email
      if(!$email || strlen($email = trim($email)) == 0) {
         $form->SetError($field, "Email not entered");
      }
      else {
         // First check email is proper format
         if(!eregi("^[_+a-z0-9-]+(\.[_+a-z0-9-]+)*"
                 ."@[a-z0-9-]+(\.[a-z0-9-]{1,})*"
                 ."\.([a-z]{2,}){1}$", $email)) {
            $form->SetError($field, "Email is not proper");
         }		 
      }

      // Password error checking
      $field = "pass";			// Use field name for password
      if(!$pass) {
         $form->SetError($field, "Password not entered");
      }
      
      // Return if form errors exist
      if($form->num_errors > 0) {
         return false;
      }

      // Checks that email is in database and password is correct */
      $email = stripslashes($email);
      $result = $database->ConfirmUserPass($email, md5($pass));

      // Check error codes
      if($result == 1) {
         $field = "email";
         $form->SetError($field, "Email not found");
      }
      else if($result == 2) {
         $field = "pass";
         $form->SetError($field, "Invalid password");
      }
      
      // Return if form errors exist
      if($form->num_errors > 0) {
         return false;
      }

      // email and password correct, register session variables
      $this->userinfo  = $database->GetUserInfo($email);
      $this->email  = $_SESSION['email'] = $this->userinfo['email'];
      
      // Login completed successfully
      return true;
   }

   // Logout
   function Logout() {
      global $database;		// The database connection

      // Unset PHP session variables
      unset($_SESSION['email']);

      // Reflect fact that user has logged out
      $this->logged_in = false;	  
   }

   // Register with user's typed information
   function Register($email, $pass, $passconfirm, $firstname, $lastname, $gender, $month, $day, $year) {
      global $database, $form;		// The database, form object
      
      // Email error checking
      $field = "email";  	// Use field name for email
      if(!$email || strlen($email = trim($email)) == 0) {
         $form->SetError($field, "Email not entered");
      }
      else {
         $email = stripslashes($email);
         // Check email is proper format
         if(!eregi("^[_+a-z0-9-]+(\.[_+a-z0-9-]+)*"
                 ."@[a-z0-9-]+(\.[a-z0-9-]{1,})*"
                 ."\.([a-z]{2,}){1}$", $email)) {
            $form->SetError($field, "Email is not proper");
         }
         // Check if email is already in use
         else if($database->UserEmailTaken($email)) {
            $form->SetError($field, "Email already in use");
         }
      }

      // Firstname error checking
      $field = "firstname";		// Use field name for firstname
      if(!$firstname || strlen($firstname = trim($firstname)) == 0) {
         $form->SetError($field, "First name not entered");
      }
      else {
		 // Firstname length check
         $firstname = stripslashes($firstname);
         if(strlen($firstname) < 2) {
            $form->SetError($field, "First name below 1 character");
         }
         else if(strlen($firstname) > 256) {
            $form->SetError($field, "First name above 256 characters");
         }
         // Check if firstname is not alphanumeric
         else if(!eregi("^([0-9a-z])+$", $firstname)) {
            $form->SetError($field, "First name not alphanumeric");
         }
      }
	  
	  // Lastname error checking
      $field = "lastname";		// Use field name for username
      if(!$firstname || strlen($lastname = trim($lastname)) == 0) {
         $form->SetError($field, "Last name not entered");
      }
      else {
         // Lastname length check
         $lastname = stripslashes($lastname);
         if(strlen($lastname) < 2) {
            $form->SetError($field, "Last name below 1 characters");
         }
         else if(strlen($lastname) > 256) {
            $form->SetError($field, "Last name above 256 characters");
         }
         // Check if lastname is not alphanumeric
         else if(!eregi("^([0-9a-z])+$", $lastname)) {
            $form->SetError($field, "Last name not alphanumeric");
         }
      }
	  
      // Password error checking
      $field = "pass";		// Use field name for password
      if(!$pass) {
         $form->SetError($field, "Password not entered");
      }
      else {
         // Password length check
         $pass = stripslashes($pass);
         if(strlen($pass) < 4) {
            $form->SetError($field, "Password too short");
         }
		 else if(strlen($pass) > 13) {
            $form->SetError($field, "Password above 13 characters");
         }
         /* Check if password is not alphanumeric */
         else if(!eregi("^([0-9a-z])+$", ($pass = trim($pass)))) {
            $form->SetError($field, "Password not alphanumeric");
         }
      }
	  
	  // Password confirm error checking
	  $field = "passconfirm";
	  if ($pass != $passconfirm)
		  $form->SetError($field, "Confirm password is not matched");

	  // Month error checking
      $field = "months";
      if(!$month) {
         $form->SetError($field, "Month is not selected");
      }

	  // Days error checking
      $field = "days";
      if(!$day) {
         $form->SetError($field, "Day is not selected");
      }

	  // Years error checking
      $field = "years";
      if(!$year) {
         $form->SetError($field, "Year is not selected");
      }
      
      // Errors exist, have user correct them
      if($form->num_errors > 0) {
         return 1;		// Errors with form
      }
      // No errors, add the new account to the database
      else {
		 $birth = date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
         if($database->AddNewUser($email, md5($pass), $firstname, $lastname, $gender, $birth))
			return 0;		// New user added succesfully
         else
		 	return 2;		// Registration attempt failed
      }
   }
			
   // Update account information		
   function EditAccount($curpass, $newpass, $passconfirm) {
      global $database, $form;		// The database and form object
      // New password entered
      if($newpass) {	  
         // Current Password error checking
         $field = "curpass";		// Use field name for current password
         if(!$curpass) {
            $form->SetError($field, "Current Password not entered");
         }
         else {
            // Check if password too short or is not alphanumeric */
            $curpass = stripslashes($curpass);
            if(strlen($curpass) < 4 ||
               !eregi("^([0-9a-z])+$", ($curpass = trim($curpass)))) {
               $form->SetError($field, "Current Password incorrect");
            }
            // Password entered is incorrect */
            if($database->ConfirmUserPass($this->email, md5($curpass)) != 0) {
               $form->SetError($field, "Current Password incorrect");
            }
         }
         
         // New Password error checking
         $field = "newpass";		// Use field name for new password
         // Check if new password too short or is not alphanumeric */
         $newpass = stripslashes($newpass);
         if(strlen($newpass) < 4) {
            $form->SetError($field, "New Password too short");
         }
         else if(!eregi("^([0-9a-z])+$", ($newpass = trim($newpass)))) {
            $form->SetError($field, "New Password not alphanumeric");
         }
		 
		 // New Password confirm error checking
		 $field = "passconfirm";
		 $passconfirm = stripslashes($passconfirm);
		 if ($newpass != $passconfirm)
		 	$form->SetError($field, "Confirm password is not matched");
      }
      // New password is not entered
      else if($curpass) {
         // New Password error reporting
         $field = "newpass";		// Use field name for new password
         $form->SetError($field, "New Password not entered");
      }
	  // New and current password is not entered
	  else {
	  	 // Current Password error checking
         $field = "curpass";  // Use field name for current password
         $form->SetError($field, "Current Password not entered");
		 
		 // New Password error reporting 
         $field = "newpass";  // Use field name for new password
         $form->SetError($field, "New Password not entered");
	  }
      
      // Errors exist, have user correct them
      if($form->num_errors > 0) {
         return false;  // Errors with form
      }
      
      // Update password since there were no errors
      if($curpass && $newpass) {
		  // Check curpass and new pass are same
		  if ($curpass == $newpass) {
			 $field = "curpass";  // Use field name for current password
			 $form->SetError($field, "Current and new password are same");
			 $field = "newpass";  // Use field name for new password
			 $form->SetError($field, "Current and new password are same");
			 return false;
		  }
         $database->UpdateUserField($this->email,"Password",md5($newpass));      
	  }
      // Success!
      return true;
   }
};
// Instantiate
$session = new Session;
$form = new Form;
?>