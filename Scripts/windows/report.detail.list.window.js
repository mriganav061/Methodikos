define([
	'jquery',
	'underscore',
	'can',
	'models/grid',
	'models/combobox',
	'util',
	'jqx'
], function ($, _, can, grid, combobox, util) {
	var win = can.Control({
		init: function (element, options) {			
			// this.initwindow();
		},
		initwindow: function(data) {
			var me = this;
			if (data == null)
				return;
			var tabdata = {
				data: {Year:data.BEGIN_YEAR}
			};
			$('#report-detail-list-window').remove();
			var view = can.view(require.toUrl('views/window/report.detail.list.window.ejs'), tabdata);
			$('#window').append(view);
			var fields = ["SEGMENT_ID", "SIGNED_HIGHWAY_RDBD_ID","BEG_REF_MARKER_NBR",
								"BEG_REF_MARKER_DISP","END_REF_MARKER_NBR","END_REF_MARKER_DISP",
								"GP_FINAL_TREATMENT_FOR_OUTPUT","GP_FINAL_TREATMENT_COST_FOR_OUTPUT"];
			var columns=[];
			var datafields = [];
			for (var i=0 ; i<=fields.length ; i++) {
				datafields.push({name: fields[i]});
				columns.push({text: fields[i], datafield:fields[i], width:'150px'});
			}

			$('#report-detail-list-window-export').jqxButton({
				theme: 'classic',
				width: '80px'
			})

			$('#report-detail-list-window-export').bind('click', function() {
				// $("#detailed-list-projects-grid-"+data[0].YEAR).jqxGrid('exportdata', 'csv');
				var csv = "";
				var org = "";
				org += me.Json2CSV(data.ORG_LIST, parseInt(baseyear))+"\n\n";
				var downloadLink = document.createElement("a");
				downloadLink.href = "data:text/csv;charset=utf-8," + escape(org);
				downloadLink.download = "org.csv";
				document.body.appendChild(downloadLink);
				downloadLink.click();
				document.body.removeChild(downloadLink);
				for (var i=0 ; i<4 ; i++) {
					csv = "";
					var year = parseInt(baseyear)+(i+1);
					csv += me.Json2CSV(data[i].PROJECT_LIST, parseInt(baseyear)+(i+1))+"\n\n";				
					downloadLink = document.createElement("a");
					downloadLink.href = "data:text/csv;charset=utf-8," + escape(csv);
					downloadLink.download = "projectlist"+year+".csv";
					document.body.appendChild(downloadLink);
					downloadLink.click();
					document.body.removeChild(downloadLink);
				}
				
				
			})

			// if (me.initdone == false) {
			$('#report-detail-list-window-ok').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#report-detail-list-window-ok').bind('click', function() {
				
			}); // end of button event
			
			$('#report-detail-list-window-cancel').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			//detailed-list-projects-grid-2011
			$('#report-detail-list-window').jqxWindow({
				theme: 'classic',
				width: '1192px',
				height: '675px',
				maxWidth: '1600px',
				maxHeight: '700px',
				// autoOpen: true,
				okButton: $('#report-detail-list-window-ok'),
		        resizable: false,
		        initContent: function() {
		        	$('#tabbar-detail-project-list-panel').jqxPanel({width:'1162px', height:'580px', theme:'classic'});
					$('#tabbar-detail-project-list').jqxTabs({width:'100%', theme:'classic', 
						initTabContent: function (tab) {
			                // The 'tab' parameter represents the selected tab's index.
			                if (tab == 0) {
		 	                	me.initgrid(datafields, data[0].PROJECT_LIST, columns, "#detailed-list-projects-grid-"+(data[0].YEAR+1), false);
			                }
			                else if (tab == 1) {
		 	                	me.initgrid(datafields, data[1].PROJECT_LIST, columns, "#detailed-list-projects-grid-"+(data[1].YEAR+1), false);	
			                }
			                else if (tab == 2) {
			                	me.initgrid(datafields, data[2].PROJECT_LIST, columns, "#detailed-list-projects-grid-"+(data[2].YEAR+1), false);
			                }
			                else {
			                	me.initgrid(datafields, data[3].PROJECT_LIST, columns, "#detailed-list-projects-grid-"+(data[3].YEAR+1), false);
			                }
						}
		            });	   
				}
	        });
			this.initdone = true;
		},
		initdone: false,
		initgrid: function(datafields, data, columns, gridid, refresh) {
			// prepare the data
			var source =
			{
				datafields: datafields,
				localdata: data,
				datatype: "array",
				pagesize: 1000
			};			
			var dataAdapter = new $.jqx.dataAdapter(source);
			if (refresh)
				$(gridid).jqxGrid({
			        source: dataAdapter			    
			    });
			else
				$(gridid).jqxGrid({
			        width: 1155,
			        height: 548,
			        source: dataAdapter,
			        theme: 'classic',
			        filterable: true,
			        sortable: true,
			        pageable: true,
			        autoshowfiltericon: true,
			        pagesizeoptions: ['1000', '5000', '10000'],
			        columns: columns
			    });
		},
		Json2CSV: function (objArray, analysisyear) {
		    var array = typeof objArray != 'object' ? JSON.parse(objArray) : objArray;

		    var str = '';
		    var line = '';

		    var stop = false;
		    // for (var i = 0; i < array.length; i++) {
		        // var head = array[0];
		        
	            for (var index in array[0]) {
	            	// if (index == "DONT_PRINT")
	            		// break;
	            	if (index == "FISCAL_YEAR")
	            		index = "ANALYSIS_YEAR";
	            	if (index == "GP_FINAL_TREATMENT_COST_FOR_OUTPUT")
	            		continue;
	                line += index + ',';
	                stop = true;
	            }

	        	// if (!stop)
	            	// continue;
		        line = line.slice(0, -1);
		        str += line + '\r\n';
		        // if (stop)
	            	// break;
		    // }

		    stop = false;
		    for (var i = 0; i < array.length; i++) {
		        var line = '';

		            for (var index in array[i]) {
		            	// if (index == "DONT_PRINT") {
		            		// stop = true;
		            		// break;
		            	// }
		            	if (index == "FISCAL_YEAR")
		            		line += analysisyear + ',';
		            	else if (index == "GP_FINAL_TREATMENT_COST_FOR_OUTPUT")
	            			continue;
		            	else
		                	line += array[i][index] + ',';
		            }

		        // if (stop) {
		        	// stop = false;
		        	// continue;
		        // }
		        	
		        line = line.slice(0, -1);
		        str += line + '\r\n';
		    }
		    return str;
		    
		}
	});
	return new win(document);
});