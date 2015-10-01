<? 
/**
 * Form.php
 *
 * This will check any errors from a form. And help to validate it
 *
 */
 
class Form
{
   var $values = array();  	// Holds submitted form field values
   var $errors = array();  	// Holds submitted form error messages
   var $num_errors;   		// The number of errors in submitted form

   // Constructor
   function Form() {
      // Set arributes with SESSION values
      if(isset($_SESSION['value_array']) && isset($_SESSION['error_array'])) {
         $this->values = $_SESSION['value_array'];
         $this->errors = $_SESSION['error_array'];
         $this->num_errors = count($this->errors);

         unset($_SESSION['value_array']);
         unset($_SESSION['error_array']);
      }
	  // If there is no form submitted, then set num_errors to 0
      else {
         $this->num_errors = 0;
      }
   }

   // Set Value
   function SetValue($field, $value) {
      $this->values[$field] = $value;
   }

   // Set Error
   function SetError($field, $errmsg) {
      $this->errors[$field] = $errmsg;
      $this->num_errors = count($this->errors);
   }

   // Get Value
   function Value($field) {
      if(array_key_exists($field,$this->values)) {
         return htmlspecialchars(stripslashes($this->values[$field]));
      }
	  else {
         return "";
      }
   }

   // Get Error
   function Error($field) {
      if(array_key_exists($field,$this->errors)) {
         return $this->errors[$field];
      }
	  else {
         return "";
      }
   }

   // Get Error Array
   function GetErrorArray() {
      return $this->errors;
   }
}; 
?>