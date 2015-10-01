<?php
/**
 * Process.php
 * 
 * The Process class will do separate each job and call corresponding function
 * Hidden tag will be used
 *
 */
include("phps/session.php");

class Process
{
   // Class constructor
   function Process() {
      global $session;
      // User submitted login form
      if(isset($_POST['login'])) {
         $this->Login();
      }
      // User submitted registration form
      else if(isset($_POST['register'])) {
         $this->Register();
      }
      // User submitted edit account form
      else if(isset($_POST['update'])) {
         $this->EditAccount();
      }
	  // User submitted survey form
	  else if(isset($_POST['survey'])) {
         $this->Survey();  
	  }
	  // Remaining is Logout
      else if($session->logged_in) {
         $this->Logout();
      }
      /**
       * Should not get here, which means user is viewing this page
       * by mistake and therefore is redirected.
       */
      else {
         header("Location: /index.php");
      }
   }

   // Login
   function Login() {
      global $session, $form, $database;
      // Login attempt
      $retval = $session->Login($_POST['email'], $_POST['pass']);
      // Login successful
      if($retval) {
      	  $_SESSION['district'] = str_replace(" ", "_", strtolower($_POST['district-select']));
      	  $_SESSION['pmistable'] = "pmis_condition_summary_".$_SESSION['district']; 
           // $_SESSION['pmis'] = "pmis_condition_summary_".$_SESSION['district'];     	  
           $query = "SELECT max(fiscal_year) as baseyear FROM ".$_SESSION['pmistable'];           
           // var_dump($query);
           $baseyear = $database->Query($query);
           $_SESSION['baseyear'] = $baseyear[0]['baseyear'];
      	  $_SESSION['user-id'] = $session->user_id;
		  //echo "<script type='text/javascript'>window.alert('You successfully logged in!')</script>"; 
          echo "<script>document.location = \"$session->referrer\"</script>";
      }
      // Login failed and get error
      else {
         $_SESSION['value_array'] = $_POST;
         $_SESSION['error_array'] = $form->GetErrorArray();
         header("Location: /login.php");
      }
   }
   
   // Logout
   function Logout() {
      global $session;
      $retval = $session->Logout();
	  //echo "<script type='text/javascript'>window.alert('You successfully logged out!')</script>"; 
      echo "<script>document.location = \"index.php\"</script>";
   }
   
   // Register
   function Register() {
      global $session, $form;
	  
	  $date = $_POST['date'];

      // Convert username to all lowercase
      $_POST['email'] = strtolower($_POST['email']);	  

      // Registration attempt
      $retval = $session->Register($_POST['email'], $_POST['pass'], $_POST['passconfirm'], $_POST['firstname'], 
								$_POST['lastname'], $_POST['gender'], $date[0], $date[1], $date[2]);
      
	  $_SESSION['months'] = $date[0];
	  $_SESSION['days'] = $date[1];
	  $_SESSION['years'] = $date[2];
      
	  // Registration Successful
      if($retval == 0) {
         $_SESSION['regemail'] = $_POST['email'];
		 $_SESSION['value_array'] = $_POST;
         $_SESSION['regsuccess'] = true;
         header("Location: register.php");
      }
      // Error found with form
      else if($retval == 1) {
         $_SESSION['value_array'] = $_POST;
         $_SESSION['error_array'] = $form->GetErrorArray();
         header("Location: register.php");
      }
      // Registration attempt failed 
      else if($retval == 2) {
         $_SESSION['regemail'] = $_POST['email'];
		 $_SESSION['value_array'] = $_POST;
         $_SESSION['regsuccess'] = false;
         header("Location: register.php");
      }
   }
   
   // Edit Account    
   function EditAccount() {
      global $session, $form;
      // Account edit attempt 
      $retval = $session->EditAccount($_POST['curpass'], $_POST['newpass'], $_POST['passconfirm']);
      
	  // Account edit successful 
      if($retval) {
         $_SESSION['useredit'] = true;
         header("Location: accountinfo.php?email=".$_SESSION['email']);
      }
      // Error found with form 
      else {
         $_SESSION['value_array'] = $_POST;
         $_SESSION['error_array'] = $form->GetErrorArray();
         header("Location: accountinfo.php?email=".$_SESSION['email']);
      }
   }
  
   // Add Survey Information
   function Survey() {
      global $session;
	  
	  // Set variables from post array
	  $interesting = $_POST['interesting'];
	  $waytoknow = $_POST['waytoknow'];
	  if ($waytoknow == "other")
		  $waytoknowother = $_POST['waytoknowother'];
	  $comments = $_POST['comments'];
	  	  
	  // Registration survey attempt 
      $retval = $session->RegSurvey($_SESSION['stu_id'], $interesting, $waytoknow, $waytoknowother, $comments);	  
      
      // Registration Successful 
      if($retval == 0) {
         $_SESSION['regsurvey'] = true;
         header("Location: survey.php");
      }
      // Registration attempt failed 
      else if($retval == 1) {
         $_SESSION['regsurvey'] = false;
         header("Location: survey.php");
      }
   }
};
// Initialize process 
$process = new Process;
?>
