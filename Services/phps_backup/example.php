<?php
	/*
	 * This file is ready to run as standalone example. However, please do:
	 * 1. Add tags <html><head><body> to make a complete page
	 * 2. Change relative path in $KoolControlFolder variable to correctly point to KoolControls folder 
	 */

	$KoolControlsFolder = "KoolPHPSuite/KoolControls";//Relative path to "KoolPHPSuite/KoolControls" folder
	
	require $KoolControlsFolder."/KoolAjax/koolajax.php";
	$koolajax->scriptFolder = $KoolControlsFolder."/KoolAjax";

	require $KoolControlsFolder."/KoolGrid/koolgrid.php";
	$ds = new MySQLDataSource($db_con);//This $db_con link has been created inside KoolPHPSuite/Resources/runexample.php
	$ds->SelectCommand = "select CONTROL_SECT_JOB, DISTRICT_NUMBER, COUNTY_NUMBER, HIGHWAY_NUMBER, TYPE_OF_WORK, LAYMAN_DESCRIPTION1, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP, PROJ_DATE FROM  DCIS_PROJECT_INFORMATION_VW";

	$grid = new KoolGrid("grid");
	$grid->scriptFolder = $KoolControlsFolder."/KoolGrid";
	$grid->styleFolder="sunset";
	$grid->DataSource = $ds;
	$grid->Width = "670px";
	$grid->PageSize  = 15;
	
	$grid->RowAlternative = true;
	$grid->AllowScrolling = true;
	$grid->MasterTable->ColumnWidth = "150px";
	$grid->AjaxEnabled = true;
	$grid->AjaxLoadingImage =  $KoolControlsFolder."/KoolAjax/loading/5.gif";
	$grid->AutoGenerateColumns = true;
	$grid->AllowFiltering = true;//Enable filtering for all rows;
	$grid->AllowGrouping = true;	
	$grid->MasterTable->Pager = new GridPrevNextAndNumericPager();
	$grid->MasterTable->ShowGroupPanel = true; //Show Group Panel	
	
	
	$grid->Process();
?>

<form id="form1" method="post">
	<?php echo $koolajax->Render();?>
	<?php echo $grid->Render();?>
</form>