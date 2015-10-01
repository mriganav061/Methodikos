<?php include ("header.php") ?>

<?php
// If user logged in and didn't do survey yet
if ($session->logged_in && $session->didsurvey == 0) {
	$n_interesting = $session->GetNumberOfInteresting();
	$n_waytoknow = $session->GetNumberOfWayToKnow();
?>
<tr><td align="center">
	<a href="javascript:ToggleDisplay('piechart');">Cumulative Survey Result(Show/Hide)</a>
	<div id="piechart" style="display: none; ">
		<table align="center">
        <tr>
        	<td><script type="text/javascript">DrawIntPieChart(<? echo $n_interesting[0]; ?>, <? echo $n_interesting[1]; ?>, <? echo $n_interesting[2]; ?>,
        <? echo $n_interesting[3]; ?>, <? echo $n_interesting[4]; ?>, <? echo $n_interesting[5]; ?>)</script>
        <div id="interestingPie"></div></td>
            <td><script type="text/javascript">DrawWayPieChart(<? echo $n_waytoknow[0]; ?>, <? echo $n_waytoknow[1]; ?>, <? echo $n_waytoknow[2]; ?>,
		<? echo $n_waytoknow[3]; ?>)</script>
		<div id="waytoknowPie"></div></td>
        </tr>
        </table>
	</div>
    <!-- Survey form start -->
    <!-- Define onsubmit, onreset event handler -->            
    <form method = "post" id="SurveyForm" action="process.php" method="post" onsubmit="return SurveyValidate()" onreset="return ResetForm()">
	<table>
    <tr>
    	<td><h1>Campus Visit Survey Form</h1> 		            
            <!-- checkboxes: define onfocus, onblur event handlers-->
 	        <p> 
            <em>What are you interested in about the campus?</em>&nbsp;<label id="interesting[]Label"></label><br /> 
                      
            <input type="checkbox" name="interesting[]" value="Students" checked="checked" onFocus="DisplayMsg(this, 'Select items of what you interested in the campus', 'false')" onBlur="DisplayMsg(this, '', 'false')" /> 
            <label>Students</label>&nbsp; 
            
            <input type="checkbox" name="interesting[]" value="Location" onFocus="DisplayMsg(this, 'Select items of what you interested in the campus', 'false')" onBlur="DisplayMsg(this, '', 'false')" /> 
            <label>Location</label>&nbsp; 
            
            <input type="checkbox" name="interesting[]" value="Campus" onFocus="DisplayMsg(this, 'Select items of what you interested in the campus', 'false')" onBlur="DisplayMsg(this, '', 'false')"/> 
            <label>Campus</label>&nbsp; 
            
            <input type="checkbox" name="interesting[]" value="Atmosphere" onFocus="DisplayMsg(this, 'Select items of what you interested in the campus', 'false')" onBlur="DisplayMsg(this, '', 'false')"/> 
            <label>Atmosphere</label>&nbsp; 
            
            <input type="checkbox" name="interesting[]" value="Dormrooms" onFocus="DisplayMsg(this, 'Select items of what you interested in the campus', 'false')" onBlur="DisplayMsg(this, '', 'false')"/> 
            <label>Dorm rooms</label>&nbsp; 
            
            <input type="checkbox" name="interesting[]" value="Sports" onFocus="DisplayMsg(this, 'Select items of what you interested in the campus', 'false')" onBlur="DisplayMsg(this, '', 'false')"/> 
            <label>Sports</label>&nbsp;<br />           
            </p> 
 
            <!-- radiobuttons: define onfocus, onblur event handlers--> 
            <p> 
            <em>How did you know the university?</em>&nbsp;<label id="waytoknowLabel"></label><br />  
            
            <input type="radio" name="waytoknow" value = "Friends" checked = "checked" onClick="OtherTextbox(this)" onFocus="DisplayMsg(this, 'Select item of how you did know the university', 'false'); DisplayMsg(getElementById('txtOther'), '', 'true');" onBlur="DisplayMsg(this, '', 'false')"/> 
            <label>Friends</label>&nbsp;
            
            <input type="radio" name="waytoknow" value = "Television" onClick="OtherTextbox(this)" onFocus="DisplayMsg(this, 'Select item of how you did know the university', 'false'); DisplayMsg(getElementById('txtOther'), '', 'true');" onBlur="DisplayMsg(this, '', 'false')"/> 
            <label>Television</label>&nbsp;
            
            <input type="radio" name="waytoknow" value = "Internet" onClick="OtherTextbox(this)" onFocus="DisplayMsg(this, 'Select item of how you did know the university', 'false'); DisplayMsg(getElementById('txtOther'), '', 'true');" onBlur="DisplayMsg(this, '', 'false')"/> 
            <label>Internet</label>&nbsp;
            
            <input type="radio" name="waytoknow" value = "other" onClick="OtherTextbox(this)" onFocus="DisplayMsg(this, 'Select item of how you did know the university', 'false'); DisplayMsg(getElementById('txtOther'), '', 'true');" onBlur="DisplayMsg(this, '', 'false')"/> 
            <label>Other</label>&nbsp;                      
            <input type="text" name="waytoknowother" id="txtOther" disabled="disabled" onClick="SelectAll(this)" onFocus="DisplayMsg(this, 'Fill textbox up', 'false')" onBlur="DisplayMsg(this, '', 'false')"/>&nbsp;
            <label id="txtOtherLabel"></label><br />            
            </p>
            
            <!-- <textarea> creates a multiline textbox: define onfocus, onblur event handlers--> 
            <p> 
            <label id="heading"><em>Additional comments:</em></label>&nbsp;<label id="commentsLabel"></label><br /> 
            <textarea name = "comments" rows = "4" cols = "50" onFocus="DisplayMsg(this, 'Please write additional comments', 'false')" onBlur="DisplayMsg(this, '', 'false')"></textarea>
            </p>            
            <input type="hidden" name="survey" value="1" /></td>
    </tr>
    <tr>
    	<td align="center">
    		<input type = "submit" value = "Submit" /> 
            <input type = "reset" value = "Clear" /><br /><br />
        </td>
   </tr>
   </table>
   </form>
</td></tr>
<?php
}
// Or logged user already did survey
else if ($session->logged_in && $session->didsurvey == 1) {
	$interesting = $session->GetInteresting();
	$waytoknow = $session->GetWayToKnow();
	if ($waytoknow == "Other")
		$waytoknowother = $session->survey['waytoknow_others'];
	$comments = $session->survey['comments'];
	$n_interesting = $session->GetNumberOfInteresting();
	$n_waytoknow = $session->GetNumberOfWayToKnow();
?>
<tr><td>
	<h1 align="center">Your Survey Result</h1>
	<table height="257" border="1" align="center" cellpadding="0" cellspacing="0">
  	<tr align="center">
    	<td width="300" height="34"><b>Question</b></td>
	    <td width="200"><b>Answer</b></td>
  	</tr>
  	<tr align="center">
    	<td>Student Name?</td>
	    <td><? echo $session->stu_name; ?></td>
	  </tr>
	  <tr align="center">
    	<td>Student Email?</td>
	    <td><? echo $session->email; ?></td>
	  </tr>
	<tr align="center">
    	<td>What are you interested in about the campus?</td>
    	<td><? echo $interesting; ?></td>
	</tr>
	<tr align="center">
    	<td>How did you know the university? </td>
    	<td>
		<? 
        if ($waytoknow == "other")
            echo $waytoknowother;
        else
            echo $waytoknow; 
        ?>
		</td>
	</tr>
	<tr align="center">
		<td>Additional comments?</td>
		<td><? echo $comments; ?></td>
	</tr>
	</table>
	<table align="center"><tr><td>
	<a href="javascript:ToggleDisplay('piechart');">Cumulative Survey Result(Show/Hide)</a>
	</td></tr></table>
	<div id="piechart" style="display:none; ">
	<table align="center">
    	<tr>
        	<td><script type="text/javascript">DrawIntPieChart(<? echo $n_interesting[0]; ?>, <? echo $n_interesting[1]; ?>, <? echo $n_interesting[2]; ?>, <? echo $n_interesting[3]; ?>, <? echo $n_interesting[4]; ?>, <? echo $n_interesting[5]; ?>)</script>
				<div id="interestingPie"></div></td>
        	<td><script type="text/javascript">DrawWayPieChart(<? echo $n_waytoknow[0]; ?>, <? echo $n_waytoknow[1]; ?>, <? echo $n_waytoknow[2]; ?>,<? echo $n_waytoknow[3]; ?>)</script>
				<div id="waytoknowPie"></div>
			</td>
		</tr>
	</table>
	</div>
</td></tr>
<?php
}
// Or user didn't login
else if (!$session->logged_in) {
?>
<tr>
	<td height="570px" align="center">You need to login first to access this page</td>
</tr>
<?php
}
?>
<?php include ("footer.php") ?>