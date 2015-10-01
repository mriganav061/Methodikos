<?php 
include("phps/session.php");
if(!$session->logged_in) {
	header("Location: /login.php");
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta charset="UTF-8">
	<title>TxPave</title>
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Mobile Specific Metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	
	<!-- Style sheets -->
	<!-- <link rel="stylesheet" href="Content/desktop/master.css" type="text/css"  /> -->
	<link rel="shortcut icon" href="favicon.ico" >
	<link href="Content/desktop/skeleton/base.css" rel="stylesheet" type="text/css" />
	<link href="Content/desktop/skeleton/skeleton.css" rel="stylesheet" type="text/css" />
	<link href="Content/desktop/skeleton/layout.css" rel="stylesheet" type="text/css" />
	<link href="Scripts/libs/qtip/jquery.qtip.min.css" rel="stylesheet" type="text/css" />
	<link href="Scripts/libs/jqwidgets/styles/jqx.base.css" rel="stylesheet" type="text/css" />
	<link href="Scripts/libs/jqwidgets/styles/jqx.classic.css" rel="stylesheet" type="text/css" />
	<link href="Scripts/libs/jqwidgets/styles/jqx.ui-darkness.css" rel="stylesheet" type="text/css" />
	<link href="Scripts/libs/jqwidgets/styles/jqx.fresh.css" rel="stylesheet" type="text/css" />
	<link href="Content/desktop/layout.css" rel="stylesheet" type="text/css" />	
	<link href="Content/desktop/style.css" rel="stylesheet" type="text/css" />
	<link href="Content/desktop/gdropdown.css" rel="stylesheet" type="text/css" />
	
	<!-- IE Hacks -->
    <!--[if lt IE 9]>
        <script src="Scripts/libs/html5.js"></script>
    <![endif]-->
    
	<!--  Javascripts -->
	<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
	<script src="Scripts/libs/require.js" data-main="Scripts/main"></script>
	
	<script type="text/javascript"> 
		var district = "<?=$_SESSION['district']?>";
		var dist = district.replace("_", " ");
		dist = dist.replace(/(?:^|\s)\S/g, function(a) { return a.toUpperCase(); });
		// dist = dist.charAt(0).toUpperCase() + dist.slice(1);
		var baseyear = "<?=$_SESSION['baseyear']?>";
		district = district.toLowerCase();
		var userid = "<?=$_SESSION['user-id']?>";
	</script>
</head>
<body>
	<!-- basic preloader: -->
	<div id="loader"><div id="loaderInner" style="direction:ltr;white-space:nowrap;overflow:visible;">Loading ... </div></div>
	<div id='elementloader'></div>
    <div class="container">
    	<!--  Top menu goes to here -->
    	<header id="header" class="sixteen columns">
    		<div id='topmenu'>
			<ul class='topmenu'>
				<li class='topmenuitem'><a>Current District - <script>document.write(dist);</script></a></li>
				<li class='topmenuitem sub'><a href='#'>Setting</a>
					<ul>
						<li><a href='#' id='menu-data-clean-window'>Data Cleaning</a></li>
					</ul>
				</li>
				<li class='topmenuitem'><a href="process.php">Logout</a></li>
			</ul>
			</div>
			<div style="float: left;position: relative;top: -35px;left: 10px;">
			<table style="font-family: Verdana,Arial,Helvetica,sans-serif; font-size: 12px; width:1000px; margin: 0px auto;">
				<tbody>
				<tr style="height:50px;">
					<td><a href="/"><img src="Content/images/logo.png" alt="jqWidgets"></a></td>
					<td style="width: 570px;vertical-align:bottom"><nav id="nav" class="sixteen columns"></nav></td>
				</tr>
				</tbody>
			</table>
			</div>
		</header>
        
        <!-- Menu goes to here -->
        
        
        <!--  Splitter part goes to here -->
        <section id="main" class="sixteen columns">
        </section>
        
        <!-- Side Panel -->
		<!-- <div id="colleft">
			<div id="panel">
				<div id="hidePanel"><a>&laquo; Hide Panel</a></div>
			</div>
	 	</div>

		<div id="showPanel"><span>&raquo;</span></div>
 -->

        <section id="window" class="sixteen columns" style="display:none;"></section>
        
        <!-- Footer part goes to here -->
        <footer id="footer" class="sixteen columns">
           &copy; 2012 Texas A&amp;M University. All rights reserved.
        </footer>
    </div>
</body>
</html>