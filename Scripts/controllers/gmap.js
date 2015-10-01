define([
	'jquery',
	'can',
	'livequery',
	'gdropdown'
], function ($, can, moment) {
	var t = 0;
	var map;
	var features, loadDone = false;
	var districts, center, counties;
	var county_labels = [];
	var district_labels = [];
	var gmap_styles = [ 
		{featureType : "road", elementType : "geometry", stylers : [ {gamma : 4.76}, {visibility : "simplified"}, {saturation : -99}, {lightness : -20} ]}, 
		{featureType : "poi", elementType : "all", stylers : [ {saturation : 0}, {visibility : "off"} ]}, 
		{featureType : "poi.park", stylers : [ {visibility : "on"}, {lightness : 8} ]}, 
		{featureType : "transit", elementType : "all", stylers : [ {visibility : "off"} ]}, 
		{featureType : "administrative.locality", elementType : "labels",stylers : [ {visibility : "off"} ]}, 
		{featureType : "road.highway", elementType : "labels", stylers : [ {visibility : "on"} ]}, 
		{featureType : "landscape", elementType : "all", stylers : [ {visibility : "off"} ]}, 
		{featureType : "water", elementType : "all", stylers : [ {lightness : 20} ]} ];

	var gmap_style_with_label = [ 
		{featureType : "road",elementType : "geometry",stylers : [ {gamma : 4.76}, {visibility : "simplified"}, {saturation : -99}, {lightness : -20} ]}, 
		{featureType : "poi",elementType : "all",stylers : [ {saturation : 0}, {visibility : "off"} ]}, 
		{featureType : "poi.park",stylers : [ {visibility : "on"}, {lightness : 8} ]}, 
		{featureType : "transit",elementType : "all",stylers : [ {visibility : "off"} ]}, 
		{featureType : "administrative.locality",elementType : "labels",stylers : [ {visibility : "on"} ]}, 
		{featureType : "road.highway",elementType : "labels",stylers : [ {visibility : "on"} ]}, 
		{featureType : "landscape",elementType : "all",stylers : [ {visibility : "off"} ]}, 
		{featureType : "water",elementType : "all",stylers : [ {lightness : 20} ]} ];

	var legend_columns = {
		'Distress Score' : [ {
			'min' : 0,
			'max' : 59,
			'color' : '#ff0000',
			'opacity' : 0.5
		}, {
			'min' : 60,
			'max' : 69,
			'color' : '#ff9900',
			'opacity' : 0.5
		}, {
			'min' : 70,
			'max' : 79,
			'color' : '#00ff00',
			'opacity' : 0.5
		}, {
			'min' : 80,
			'max' : 89,
			'color' : '#00ffff',
			'opacity' : 0.5
		}, {
			'min' : 90,
			'max' : 100,
			'color' : '#0000ff',
			'opacity' : 0.5
		} ]
	};
	var gmap = can.Control({
		init: function (element, options) {
			$('#map-canvas').waitUntilExists(function () {
				var districtname = district.charAt(0).toUpperCase() + district.slice(1);
				var query_district = 'name=\'' + districtname + '\'';
				district_layer = new google.maps.FusionTablesLayer({
					query : {
						select : 'geometry',
						from : '3801410',
						where : query_district
					},
					suppressInfoWindows : true
				});
				county_layer = new google.maps.FusionTablesLayer({
					query : {
						select : 'geometry',
						from : '3862344'
					},
					suppressInfoWindows : true
				});
				reference_markers = new google.maps.FusionTablesLayer({
					query : {
						select : 'geometry',
						from : '4068716'
					},
					suppressInfoWindows : true
				});
				bryan_distress = new google.maps.FusionTablesLayer({
					query : {
						select : 'geometry',
						from : '4164802'
					},
				});

				var texas = new google.maps.LatLng(30.505484, -98.942871);
				map = new google.maps.Map(document.getElementById('map-canvas'), {
					center : texas,
					zoom : 8,
					mapTypeId : google.maps.MapTypeId.ROADMAP,
					maxZoom : 20,
					minZoom : 7
					// mapTypeControl: true,
					// mapTypeControlOptions: {
					// style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
					// }
				});
				// bounds of the desired area
				var allowedBounds = new google.maps.LatLngBounds(
						new google.maps.LatLng(19.774769, -113.144531),
						new google.maps.LatLng(39.774769, -83.144531));

				google.maps.event.addListener(map, 'center_changed', function() {
					checkBounds(allowedBounds);
				});

				var queryurl = "https://www.googleapis.com/fusiontables/v1/query?sql=SELECT centroid_x, centroid_y FROM 1zBI-4lQEXWjxWBbrhmAGUyUt0qAP-zea1n1JsYc "
						+ "WHERE name='" + districtname + "'";
				var querytail = "&key=AIzaSyDyMTaO-HymWk3Y7J26cJo-386zS_4sOPI&callback=?";

				var jqxhr = $.get(queryurl + querytail, queryHandler, "jsonp");

				var styledMapType = new google.maps.StyledMapType(gmap_styles, {
					map : map,
					name : 'Styled Map'
				});

				map.mapTypes.set('map-style', styledMapType);
				map.setMapTypeId('map-style');

				district_layer.setMap(map);
				// reference_markers.setMap(map);
				bryan_distress.setMap(map);
				$.getJSON('county_label.json', function(data) {
					counties = data.features;
					for ( var i = 0; i < counties.length; i++) {
						center = counties[i].properties.centroid;
						var mapLabel = new MapLabel({
							text : counties[i].properties.Name,
							position : new google.maps.LatLng(
									counties[i].properties.centroid.x,
									counties[i].properties.centroid.y),
							map : null,
							fontSize : 15,
							align : 'center',
							fontColor : '#3300FF'
						});
						county_labels.push(mapLabel);
					}
				});

				$.getJSON('texas_district.json', function(data) {
					var COLORS = [ "#F1EEF6", "#D4B9DA", "#C994C7", "#DF65B0",
							"#DD1C77", "#980043" ];
					// add custom dropdown
					var dd_districts = [];
					districts = data.features;

					for ( var i = 0; i < districts.length; i++) {
						var mapLabel = new MapLabel({
							text : districts[i].properties.Name,
							position : new google.maps.LatLng(
									districts[i].properties.centroid.x,
									districts[i].properties.centroid.y),
							map : map,
							fontSize : 15,
							align : 'center',
							fontColor : '#3300FF'
						});
						district_labels.push(mapLabel);
					}

					for ( var i = 0; i < districts.length; i++) {
						center = districts[i].properties.centroid;
						var divOptions = {
							gmap : map,
							name : districts[i].properties.Name,
							title : districts[i].properties.Name,
							id : districts[i].id,
							action : function() {
								map.setZoom(8);
								map.setCenter(new google.maps.LatLng(
										districts[this.id - 1].properties.centroid.x,
										districts[this.id - 1].properties.centroid.y));
							}
						}
						dd_districts.push(new optionDiv(divOptions));
					}
					// put them all together to create the dropdown
					var ddDivOptions = {
						items : dd_districts,
						id : "myddOptsDiv"
					}

					var dropDownDiv = new dropDownOptionsDiv(ddDivOptions);
					var dropDownOptions = {
						gmap : map,
						name : 'Move to',
						id : 'ddControl',
						title : 'Select area you are interested in',
						position : google.maps.ControlPosition.TOP_RIGHT,
						dropDown : dropDownDiv
					}
					var dropDown1 = new dropDownControl(dropDownOptions);
					console.log(dropDownDiv);

					/*
					 * features = gmap.load_polygons({ map : map, data : data, data_type :
					 * "json", getColor : function(data) { return COLORS[1]; // maps
					 * [0,1) into 0 to 4. buckets of .2 }, unselected_opts : {
					 * "fillOpacity" : .5 }, highlighted_opts : { strokeWeight : 1.49,
					 * "fillOpacity" : .6, strokeColor : "#8B0000" }, selected_opts : {
					 * "fillOpacity" : .6, strokeWeight : 1.49, strokeColor : "#8B0000" },
					 * highlightCallback : function(e) {
					 * 
					 * if (timerOn = true){ clearTimeout(t); } $("#fB").show(); t =
					 * window.setTimeout(hideBox, 99000); function hideBox(){ timerOn =
					 * true; $("#fB").fadeOut("slow"); };
					 * 
					 * //add the data to the mouseover box // $("#fBwT").html("<strong>Dynamic</strong>");
					 * $("#fBdN").html("<strong>District: "+this.fields.Name+"</strong>"); //
					 * $("#fBtI1").html("<strong>Approved two month extension</strong>"); //
					 * $("#fBtI2").html("<strong>Potential<br/>annual savings</strong>");
					 * //console.log("highlighted " + this.fields.censusTract); },
					 * selectCallback : function(event) { $("#fB").fadeOut("slow");
					 * map.setZoom(9); map.setCenter(new
					 * google.maps.LatLng(this.fields.centroid.x,
					 * this.fields.centroid.y)); // Dialog
					 * $("#dialog:ui-dialog").dialog("destroy");
					 * $("#dialog-confirm").dialog({ resizable : false, height : 140,
					 * modal : true, buttons : { "Select this district" : function() {
					 * $(this).dialog("close"); features =
					 * gmap.remove_polygons(features); district_layer.setMap(map); },
					 * Cancel : function() { $(this).dialog("close"); } } });
					 * $("#dialog-confirm").html("You have selected " + this.fields.Name + "
					 * district. The related data will be loaded."); } });
					 */
					loadDone = true;
				});

				/*
				 * Attach side panel
				 */
				// var sidePanel = document.createElement('div');
				// sidePanel.appendChild(document.getElementById('colleft'));
				// sidePanel.appendChild(document.getElementById('showPanel'));
				// sidePanel.index = -500;
				// map.controls[google.maps.ControlPosition.TOP_LEFT].push(sidePanel);
				
				// function ilchide(){		
				// $("#panel").animate({marginLeft:"-175px"}, 500 );		
				// $("#colleft").animate({width:"0px", opacity:0}, 400 );		
				// $("#showPanel").show("normal").animate({width:"28px", opacity:1}, 200);		
				// $("#colright").animate({marginLeft:"50px"}, 500);}
				// function ilcshow(){		
				// $("#colright").animate({marginLeft:"200px"}, 200);		
				// $("#panel").animate({marginLeft:"0px"}, 400 );		
				// $("#colleft").animate({width:"175px", opacity:1}, 400 );		
				// }

				// function ilchide() {
				// 	$("#colleft").animate({
				// 		width : "0px",
				// 		opacity : 0
				// 	}, {
				// 		duration : 400,
				// 		step : function() {
				// 			google.maps.event.trigger(map, 'resize');
				// 		}
				// 	});
				// 	$("#panel").animate({
				// 		marginLeft : "-175px"
				// 	}, 400);
				// 	$("#showPanel").show("normal").animate({width:"28px", opacity:1}, 200);
				// 	// $("#colright").animate({marginLeft:"50px"}, 500);
				// }

				// function ilcshow() {
				// 	// $("#colright").animate({marginLeft:"200px"}, 200);		
				// 	$("#panel").animate({
				// 		marginLeft : "0px"
				// 	}, 400);

				// 	$("#colleft").show('normal').animate({width:"28px", opacity:1}, 200);
				// 	$("#colleft").animate({
				// 		width : "175px",
				// 		opacity : 1
				// 	}, {
				// 		duration : 400,
				// 		step : function() {
				// 			google.maps.event.trigger(map, 'resize');
				// 		}
				// 	});

				// 	$("#showPanel").animate({width:"0px", opacity:0}, 600).hide("normal");
				// }

				// $(document).ready(function(){	//ilchide();
				// 	$("#hidePanel").click(function(){
				// 		ilchide();
				// 	});
				// 	$("#showPanel").click(function(){				
				// 		ilcshow();
				// 	});
				// });

				/*
				 * When zoom level is changed
				 */
				google.maps.event.addListener(map, 'zoom_changed', function() {
					var zoomLevel = map.getZoom();
					if (zoomLevel > 8) {
						var styledMapType = new google.maps.StyledMapType(
								gmap_style_with_label, {
									map : map,
									name : 'Styled Map'
								});

						map.mapTypes.set('map-style', styledMapType);
						map.setMapTypeId('map-style');

						/*
						 * County labels
						 */
						for ( var i = 0; i < county_labels.length; i++) {
							county_labels[i].setMap(map);
						}

						for ( var i = 0; i < district_labels.length; i++) {
							district_labels[i].setMap(null);
						}
						county_layer.setMap(map);
					} else {
						var styledMapType = new google.maps.StyledMapType(gmap_styles, {
							map : map,
							name : 'Styled Map'
						});
						map.mapTypes.set('map-style', styledMapType);
						map.setMapTypeId('map-style');
						for ( var i = 0; i < district_labels.length; i++) {
							district_labels[i].setMap(map);
						}
						for ( var i = 0; i < county_labels.length; i++) {
							county_labels[i].setMap(null);
						}
						county_layer.setMap(null);
					}
				});
				addLegend(getKey());

				function queryHandler(data) {
					// display the first row of retrieved data
					console.log(data);
					map.setCenter(new google.maps.LatLng(data.rows[0][0],
							data.rows[0][1]));
					map.setZoom(9);
				}

				// limit map area
				function checkBounds(allowedBounds) {
					if (!allowedBounds.contains(map.getCenter())) {
						var C = map.getCenter();
						var X = C.lng();
						var Y = C.lat();
						var AmaxX = allowedBounds.getNorthEast().lng();
						var AmaxY = allowedBounds.getNorthEast().lat();
						var AminX = allowedBounds.getSouthWest().lng();
						var AminY = allowedBounds.getSouthWest().lat();
						if (X < AminX) {
							X = AminX;
						}
						if (X > AmaxX) {
							X = AmaxX;
						}
						if (Y < AminY) {
							Y = AminY;
						}
						if (Y > AmaxY) {
							Y = AmaxY;
						}
						map.setCenter(new google.maps.LatLng(Y, X));
					}
				};

				var legend_width = '100px';
				function getKey() {
					for (key in legend_columns) {
						return key;
					}
				}

				// Create the legend with the corresponding colors
				function updateLegend(column) {
					var legendDiv = document.createElement('div');
					var legend = new Legend(legendDiv, column);
					legendDiv.index = 1;
					map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].pop();
					map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(legendDiv);
				}

				// Apply the style to the layer
				function addLegend(column) {
					var defined_styles = legend_columns[column];
					updateLegend(column);
				}

				// Generate the content for the legend
				function Legend(controlDiv, column) {
					controlDiv.style.padding = '10px';
					var controlUI = document.createElement('div');
					controlUI.style.backgroundColor = 'white';
					controlUI.style.borderStyle = 'solid';
					controlUI.style.borderWidth = '1px';
					controlUI.style.width = legend_width;
					controlUI.title = 'Legend';
					controlDiv.appendChild(controlUI);
					var controlText = document.createElement('div');
					controlText.style.fontFamily = 'Arial,sans-serif';
					controlText.style.fontSize = '12px';
					controlText.style.paddingLeft = '4px';
					controlText.style.paddingRight = '4px';

					controlText.innerHTML = legendContent(column);
					controlUI.appendChild(controlText);
				}

				function legendContent(column) {
					var defined_styles = legend_columns[column];

					// Generate the content for the legend using colors from object
					var controlTextList = new Array();
					controlTextList.push('<p><b>');
					controlTextList.push(column);
					controlTextList.push('</b></p>');
					for (defined_style in defined_styles) {
						var style = defined_styles[defined_style];
						controlTextList.push('<div style="background-color: ');
						controlTextList.push(style.color);
						controlTextList
								.push('; height: 1px; width: 20px; margin: 7px; float: left;"></div>');
						controlTextList.push(style.min);
						controlTextList.push(' - ');
						controlTextList.push(style.max);
						controlTextList.push('<br style="clear: both;"/>');
					}

					// controlTextList.push('<br />');
					return controlTextList.join('');
				}
				


				/**
				* hoverIntent r5 // 2007.03.27 // jQuery 1.1.2+
				* <http://cherne.net/brian/resources/jquery.hoverIntent.html>
				* 
				* @param  f  onMouseOver function || An object with configuration options
				* @param  g  onMouseOut function  || Nothing (use configuration options object)
				* @author    Brian Cherne <brian@cherne.net>
				*/
				(function($){$.fn.hoverIntent=function(f,g){var cfg={sensitivity:7,interval:100,timeout:0};cfg=$.extend(cfg,g?{over:f,out:g}:f);var cX,cY,pX,pY;var track=function(ev){cX=ev.pageX;cY=ev.pageY;};var compare=function(ev,ob){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);if((Math.abs(pX-cX)+Math.abs(pY-cY))<cfg.sensitivity){$(ob).unbind("mousemove",track);ob.hoverIntent_s=1;return cfg.over.apply(ob,[ev]);}else{pX=cX;pY=cY;ob.hoverIntent_t=setTimeout(function(){compare(ev,ob);},cfg.interval);}};var delay=function(ev,ob){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);ob.hoverIntent_s=0;return cfg.out.apply(ob,[ev]);};var handleHover=function(e){var p=(e.type=="mouseover"?e.fromElement:e.toElement)||e.relatedTarget;while(p&&p!=this){try{p=p.parentNode;}catch(e){p=this;}}if(p==this){return false;}var ev=jQuery.extend({},e);var ob=this;if(ob.hoverIntent_t){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);}if(e.type=="mouseover"){pX=ev.pageX;pY=ev.pageY;$(ob).bind("mousemove",track);if(ob.hoverIntent_s!=1){ob.hoverIntent_t=setTimeout(function(){compare(ev,ob);},cfg.interval);}}else{$(ob).unbind("mousemove",track);if(ob.hoverIntent_s==1){ob.hoverIntent_t=setTimeout(function(){delay(ev,ob);},cfg.timeout);}}};return this.mouseover(handleHover).mouseout(handleHover);};})(jQuery);


				/**
				* Copyright 2011 Google Inc.
				* Licensed under the Apache License, Version 2.0 (the "License");
				* you may not use this file except in compliance with the License.
				* You may obtain a copy of the License at
				* http://www.apache.org/licenses/LICENSE-2.0

				* Unless required by applicable law or agreed to in writing, software
				* distributed under the License is distributed on an "AS IS" BASIS,
				* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
				* See the License for the specific language governing permissions and
				* limitations under the License.
				*/
				(function(){
					var d="prototype";function e(a){this.set("fontFamily","sans-serif");this.set("fontSize",12);this.set("fontColor","#000000");this.set("strokeWeight",4);this.set("strokeColor","#ffffff");this.set("align","center");this.set("zIndex",1E3);this.setValues(a)}e.prototype=new google.maps.OverlayView;window.MapLabel=e;e[d].changed=function(a){switch(a){case "fontFamily":case "fontSize":case "fontColor":case "strokeWeight":case "strokeColor":case "align":case "text":return h(this);case "maxZoom":case "minZoom":case "position":return this.draw()}};
					function h(a){var b=a.a;if(b){var f=b.style;f.zIndex=a.get("zIndex");var c=b.getContext("2d");c.clearRect(0,0,b.width,b.height);c.strokeStyle=a.get("strokeColor");c.fillStyle=a.get("fontColor");c.font=a.get("fontSize")+"px "+a.get("fontFamily");var b=Number(a.get("strokeWeight")),g=a.get("text");if(g){if(b)c.lineWidth=b,c.strokeText(g,b,b);c.fillText(g,b,b);a:{c=c.measureText(g).width+b;switch(a.get("align")){case "left":a=0;break a;case "right":a=-c;break a}a=c/-2}f.marginLeft=a+"px";f.marginTop=
					"-0.4em"}}}e[d].onAdd=function(){var a=this.a=document.createElement("canvas");a.style.position="absolute";var b=a.getContext("2d");b.lineJoin="round";b.textBaseline="top";h(this);(b=this.getPanes())&&b.mapPane.appendChild(a)};e[d].onAdd=e[d].onAdd;
					e[d].draw=function(){var a=this.getProjection();if(a&&this.a){var b=this.get("position");if(b){b=a.fromLatLngToDivPixel(b);a=this.a.style;a.top=b.y+"px";a.left=b.x+"px";var b=this.get("minZoom"),f=this.get("maxZoom");if(b===void 0&&f===void 0)b="";else{var c=this.getMap();c?(c=c.getZoom(),b=c<b||c>f?"hidden":""):b=""}a.visibility=b}}};e[d].draw=e[d].draw;e[d].onRemove=function(){var a=this.a;a&&a.parentNode&&a.parentNode.removeChild(a)};e[d].onRemove=e[d].onRemove;})()


			});		
		},
		resized: function (data) {
			$("#map-canvas").css("height", data);
			google.maps.event.trigger(map, "resize");
		}
	});

	return new gmap(document);
});