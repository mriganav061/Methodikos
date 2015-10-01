<?php 

?>
<html>

<body>

	<form action="download.php" method="post" enctype="multipart/form-data">
		
		<label for="file"><h1>Choose the excel file that contains pavement management projects:</h1></label>
		
		<select name="district">
			<option value="bryan">Bryan</option>
			<option value="lubbock">Lubbock</option>
			<option value="fort_worth">Fort Worth</option>
		</select>
		<input type="file" name="file" id="file"><br>
		<input type="submit" name="submit" value="Submit"><br>
		<hr>
		<ul>
			<li>All worksheets in excel will be processed</li>
			<li>The following column header should exist at the first row of each worksheet</li>
			<ul>
				<li>'HWY' or 'Highway Number'</li>
				<li>'TRM From'</li>
				<li>'TRM From Displ'</li>
				<li>'TRM To'</li>
				<li>'TRM To Displ'</li>
				<li>'Roadbed'</li>
				<li>'Direction'</li>
			</ul>			
		</ul>
		<hr>
		<ul>
		<?php
			if ($_FILES["file"]["error"] > 0) {
				echo "<li>Error: " . $_FILES["file"]["error"] . "</li>";
			} else {
				echo "<li>Upload: " . $_FILES["file"]["name"] . "</li>";
				echo "<li>Type: " . $_FILES["file"]["type"] . "</li>";
				echo "<li>Size: " . ($_FILES["file"]["size"]/1024) . " kB</li>";
				echo "<li>Stored in: " . $_FILES["file"]["tmp_name"] . " </li>";
			}
			move_uploaded_file($_FILES["file"]["tmp_name"], "Upload/" . $_FILES["file"]["name"]);

			echo "<li>", date('H:i:s') , " Load from Excel2007 file </li>";
		?>
		</ul>
	</form>
</body>

</html>