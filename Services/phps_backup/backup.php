<div id="tabs-1">
					<?php
						//Connect database here
						$db_con = mysql_connect($dbhost,$dbuser,$dbpass);
						if(mysql_select_db($dbname))
						{
							$KoolControlsFolder = "KoolPHPSuite/KoolControls";//Relative path to "KoolPHPSuite/KoolControls" folder
							require $KoolControlsFolder."/KoolAjax/koolajax.php";
							$koolajax->scriptFolder = $KoolControlsFolder."/KoolAjax";
							require $KoolControlsFolder."/KoolGrid/koolgrid.php";
							$ds = new MySQLDataSource($db_con);//This $db_con link has been created inside KoolPHPSuite/Resources/runexample.php
							$ds->SelectCommand = "select FISCAL_YEAR,RESPONSIBLE_DISTRICT,SIGNED_HIGHWAY_RDBD_ID,BEG_REF_MARKER_NBR,BEG_REF_MARKER_DISP,END_REF_MARKER_NBR,END_REF_MARKER_DISP,DISTRESS_SCORE,CONDITION_SCORE,RIDE_SCORE FROM pmis_condition_summary_bryan_2011";
							//$ds->SelectCommand = "select CONTROL_SECT_JOB, DISTRICT_NUMBER, COUNTY_NUMBER, HIGHWAY_NUMBER, TYPE_OF_WORK, LAYMAN_DESCRIPTION1, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP, PROJ_DATE FROM  DCIS_PROJECT_INFORMATION_VW";

							$grid = new KoolGrid("grid1");
							$grid->scriptFolder = $KoolControlsFolder."/KoolGrid";
							$grid->styleFolder="sunset";
							$grid->DataSource = $ds;
							$grid->Width = "670px";
							$grid->PageSize  = 15;

							$grid->RowAlternative = true;
							$grid->AllowScrolling = true;
							//$grid->MasterTable->VirtualScrolling = true;
							$grid->MasterTable->ColumnWidth = "150px";
							$grid->MasterTable->Height = "500px";
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
						<?php
						mysql_close($db_con);
						}
						else
						{
							?>
						Could not connect to database!
						<?php
						}
						?>
					</div>
					<div id="tabs-2">
						<?php
						//Connect database here
						$db_con = mysql_connect($dbhost,$dbuser,$dbpass);
						if(mysql_select_db($dbname))
						{
							$KoolControlsFolder = "KoolPHPSuite/KoolControls";//Relative path to "KoolPHPSuite/KoolControls" folder
							//require $KoolControlsFolder."/KoolAjax/koolajax.php";
							//require $KoolControlsFolder."/KoolGrid/koolgrid.php";
							$ds = new MySQLDataSource($db_con);//This $db_con link has been created inside KoolPHPSuite/Resources/runexample.php
							//$ds->SelectCommand = "select FISCAL_YEAR,SIGNED_HIGHWAY_RDBD_ID,BEG_REF_MARKER_NBR,BEG_REF_MARKER_DISP,END_REF_MARKER_NBR,END_REF_MARKER_DISP,DISTRESS_SCORE,CONDITION_SCORE,RIDE_SCORE FROM PMIS_CONDITION_SUMMARY";
							$selected_district = $_SESSION['district-select'];
							$selected_district = trim($selected_district, " ");
							$selected_district = strtolower($selected_district);
							$ds->SelectCommand = "select CONTROL_SECT_JOB, DISTRICT_NUMBER, COUNTY_NUMBER, HIGHWAY_NUMBER, TYPE_OF_WORK, ".
							"LAYMAN_DESCRIPTION1, BEG_REF_MARKER_NBR, BEG_REF_MARKER_DISP, END_REF_MARKER_NBR, END_REF_MARKER_DISP, PROJ_DATE FROM cleaned_dcis_project_information_vw WHERE DISTRICT_NUMBER=17";
							//.$selected_district
							$grid = new KoolGrid("grid2");
							$grid->scriptFolder = $KoolControlsFolder."/KoolGrid";
							$grid->styleFolder="sunset";
							$grid->DataSource = $ds;
							$grid->Width = "670px";
							$grid->PageSize  = 15;

							$grid->RowAlternative = true;
							$grid->AllowScrolling = true;
							//$grid->MasterTable->VirtualScrolling = true;
							$grid->MasterTable->ColumnWidth = "150px";
							$grid->MasterTable->Height = "500px";
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
						<?php
						mysql_close($db_con);
						}
						else
						{
							?>
						Could not connect to database!
						<?php
						}
						?>
					</div>