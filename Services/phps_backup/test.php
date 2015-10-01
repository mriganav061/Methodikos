
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<title>Google Maps JavaScript API v3 Example: Polygon Simple</title>
<link href="https://developers.google.com/maps/documentation/javascript/examples/default.css" rel="stylesheet" type="text/css" />
<script type="text/javascript"
	src="http://maps.googleapis.com/maps/api/js?key=AIzaSyDyMTaO-HymWk3Y7J26cJo-386zS_4sOPI&sensor=false"></script>
<script type="text/javascript">
function drawCircle(point, radius, dir) { 
	var d2r = Math.PI / 180;   // degrees to radians 
	var r2d = 180 / Math.PI;   // radians to degrees 
	var earthsradius = 3963; // 3963 is the radius of the earth in miles

	   var points = 1000; 

	   // find the raidus in lat/lon 
	   var rlat = (radius / earthsradius) * r2d; 
	   var rlng = rlat / Math.cos(point.lat() * d2r); 

	   var triangleCoords = [
							new google.maps.LatLng(18.466465, -66.118292),				
	                         new google.maps.LatLng(25.774252, -80.190262),
	                         new google.maps.LatLng(32.321384, -64.75737),
	                         new google.maps.LatLng(18.466465, -66.118292)
	                       ];
	   var extp = new Array(); 
	   if (dir==1)  {var start=0;var end=points+1} // one extra here makes sure we connect the
	   else     {var start=points+1;var end=0}
	   //for (var i=start; (dir==1 ? i < end : i > end); i=i+dir)
	   for (var i=0 ; i<4 ; i++)  
	   { 
	      var theta = Math.PI * (i / (points/2)); 
	      ey = point.lng() + (rlng * Math.cos(theta)); // center a + radius x * cos(theta) 
	      ex = point.lat() + (rlat * Math.sin(theta)); // center b + radius y * sin(theta) 
	  extp.push(triangleCoords[i]); 
	  bounds.extend(extp[extp.length-1]);
	   } 
	   // alert(extp.length);
	   return extp;
	   }

	var map = null;
	var bounds = null;

	function initialize() {
	  var myOptions = {
	    zoom: 10,
	    center: new google.maps.LatLng(42,-80),
	    mapTypeControl: true,
	    mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
	    navigationControl: true,
	    mapTypeId: google.maps.MapTypeId.TERRAIN
	  }
	  map = new google.maps.Map(document.getElementById("map_canvas"),
	                            myOptions);

	  var everythingElse = [
	                        new google.maps.LatLng(-87, 120), 
	                        new google.maps.LatLng(-87, -87), 
	                        new google.maps.LatLng(-87, 0),
	                      ];



	                      var triangleCoords = [
	                        new google.maps.LatLng(25.774252, -80.190262),
	                         new google.maps.LatLng(32.321384, -64.75737),
		                        new google.maps.LatLng(18.466465, -66.118292),				
	                         
	                         
		                        new google.maps.LatLng(25.774252, -80.190262)
	                      ];

	                      district_layer = new google.maps.FusionTablesLayer({ 
	                      	query: { select: 'geometry',from: '3801410', where:"name='Abilene'" },
	                      	suppressInfoWindows: true
	                      }); 


	                      bermudaTriangle = new google.maps.Polygon({
	                        paths: [everythingElse, district_layer],
	                        strokeColor: "#000000",
	                        strokeOpacity: 0.8,
	                        strokeWeight: 2,
	                        fillColor: "#000000",
	                        fillOpacity: 0.5
	                      });

	                      bermudaTriangle.setMap(map);

	}
</script>
</head>
<body onload="initialize()">
  <div id="map_canvas"></div>
</body>
</html>
