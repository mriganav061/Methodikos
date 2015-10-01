/**
 * validation.js
 *
 * This file used for form validation
 *
 */
 
// Set 'other' textbox disabled attribute depending upon radio button value
function OtherTextbox(rad) {
	if (rad.value == "other")
		  document.getElementById("txtOther").disabled = false;
	else		  
		  document.getElementById("txtOther").disabled = true;
}

// Check form validation
function SurveyValidate() {
	var result = true;
	
	// Check checkbox field
	if (!CheckedBox())
		result = false;
  
	// Check 'other' text box is filled
	if (!FilledOther(document.getElementById("txtOther")))
		result = false;
	
	// If result is true, show 'submit success message'
	if (result == true)
		alert("Successfully submitted");

	// Return result
	return result;
}

// Display error messages or help messages
function DisplayMsg(obj, msg, resu) {
	// Use id or name
	var idstr = obj.id + "Label";
	var namestr = obj.name + "Label";
	
	// Make object color to red
	if (resu) 
		obj.className = "";           
	else
		obj.className = "error";
  
	// Use id if it is not empty
	if (obj.id != null && obj.id != "") {
		// Display message and set style
		document.getElementById(idstr).innerHTML = msg;
		document.getElementById(idstr).style.color = "red";
		document.getElementById(idstr).style.fontSize = "12px";
	}
	// Use name if id is empty
	else {		
		// Display message and set style
		document.getElementById(namestr).innerHTML = msg;
		document.getElementById(namestr).style.color = "red";
		document.getElementById(namestr).style.fontSize = "12px";
	}
}

// Check at least one checkbox is checked
function CheckedBox() {
	// Get checkbox objects
	var chkInteresting = document.getElementsByName("interesting[]");
	
	// Check at least one checkbox is checked
	for (var i=0 ; i<chkInteresting.length ; i++) {
		if (chkInteresting[i].checked) {
			// If so, return true
			document.getElementById("interesting[]Label").innerHTML = "";
			return true;
		}
	}
	// if none of checkboxes is not checked, then return false with error message
	document.getElementById("interesting[]Label").innerHTML = "Please check at least one of the list";
	document.getElementById("interesting[]Label").style.color = "red";
	document.getElementById("interesting[]Label").style.fontSize = "12px";
	return false;
}

// Check 'other' textbox is filled
function FilledOther(txtOther) {
	// If 'other' textbox is enabled and it is empty or null
	if (txtOther.disabled == false &&  (txtOther.value == null || txtOther.value == "")) {
		// Display error message
		DisplayMsg(txtOther, "Please fill textbox out", false);
		return false;
	}
	// Otherwise display nothing
	DisplayMsg(txtOther, "", true);
	return true;
}

// Reset register form
function ResetRegisterForm() {
	// Show confirm box, and if user clicks ok button
	if (confirm('Are you sure?') == true) {
		// Clear all the error messages
		document.getElementById("email").value = "";
		document.getElementById("firstname").value = "";
		document.getElementById("lastname").value = "";
		DisplayMsg(document.getElementById("email"), "", true);
		DisplayMsg(document.getElementById("firstname"), "", true);
		DisplayMsg(document.getElementById("lastname"), "", true);
		DisplayMsg(document.getElementById("pass"), "", true);
		DisplayMsg(document.getElementById("passconfirm"), "", true);
		DisplayMsg(document.getElementById("months"), "", true);
		DisplayMsg(document.getElementById("days"), "", true);
		DisplayMsg(document.getElementById("years"), "", true);
		// And return true
		return true;
	}
	// otherwise return false
	return false;
}

// Reset login form
function ResetLogin() {
	// Clear all the error messages
	  document.getElementById("email").value = "";
	  document.getElementById("pass").value = "";
	  DisplayMsg(document.getElementById("email"), "", true);
	  DisplayMsg(document.getElementById("pass"), "", true);
	  // And return true
	  return true;
}

// Reset form
function ResetUpdate() {
	// Clear all the error messages
	  document.getElementById("curpass").value = "";
	  document.getElementById("newpass").value = "";
	  document.getElementById("passconfirm").value = "";
	  DisplayMsg(document.getElementById("curpass"), "", true);
	  DisplayMsg(document.getElementById("newpass"), "", true);
	  DisplayMsg(document.getElementById("passconfirm"), "", true);
	  // And return true
	  return true;
}

// Reset form
function ResetForm() {
	// Show confirm box, and if user clicks ok button
	if (confirm('Are you sure?') == true) {
		// Clear all the error messages
		DisplayMsg(document.getElementById("txtOther"), "", true);
		document.getElementById("interestingLabel").innerHTML = "";
		// And return true
		return true;
	}
	// otherwise return false
	return false;
}