define([
	'jquery',
	'underscore',
	'can',
	'models/grid',
	'models/combobox',
	'util',
	'jqx'
], function ($, _, can, grid, combobox, util) {
	var window = can.Control({
		init: function (element, options) {
			
//			this.initwindow();
		},
		initwindow: function(data) {
			if (data == null)
				return;
			var me = this;
			$('#report-summary-window').remove();
			var view = can.view(require.toUrl('views/window/report.summary.window.ejs'));
			$('#window').append(view);
			var howmanyyears = data.END_YEAR - data.BEGIN_YEAR + 1;
			var beginyear = data.BEGIN_YEAR+1;
			var endyear = data.END_YEAR+1;
			var cost_values=[], mile_values=[];
			var backlog_cost=[], backlog_lanemiles=[];
			for (i=0 ; i<howmanyyears ; i++) {
				var costrow = [];				
				costrow.push(data[i].OUTPUT_STAT.FORCED_COST_TOTAL_PM+data[i].OUTPUT_STAT.LANE_COST_TOTAL_PM);
				costrow.push(data[i].OUTPUT_STAT.FORCED_COST_TOTAL_LR+data[i].OUTPUT_STAT.LANE_COST_TOTAL_LR);
				costrow.push(data[i].OUTPUT_STAT.FORCED_COST_TOTAL_MR+data[i].OUTPUT_STAT.LANE_COST_TOTAL_MR);
				costrow.push(data[i].OUTPUT_STAT.FORCED_COST_TOTAL_HR+data[i].OUTPUT_STAT.LANE_COST_TOTAL_HR);
				cost_values.push(costrow);

				var lanemilesrow = [];
				lanemilesrow.push(data[i].OUTPUT_STAT.FORCED_LANE_MILES_TOTAL_PM+data[i].OUTPUT_STAT.LANE_MILES_TOTAL_PM);
				lanemilesrow.push(data[i].OUTPUT_STAT.FORCED_LANE_MILES_TOTAL_LR+data[i].OUTPUT_STAT.LANE_MILES_TOTAL_LR);
				lanemilesrow.push(data[i].OUTPUT_STAT.FORCED_LANE_MILES_TOTAL_MR+data[i].OUTPUT_STAT.LANE_MILES_TOTAL_MR);
				lanemilesrow.push(data[i].OUTPUT_STAT.FORCED_LANE_MILES_TOTAL_HR+data[i].OUTPUT_STAT.LANE_MILES_TOTAL_HR);
				mile_values.push(lanemilesrow);		

				backlog_cost.push(data[i].OUTPUT_STAT.BACKLOG_LANE_COST_TOTAL_PM+
					data[i].OUTPUT_STAT.BACKLOG_LANE_COST_TOTAL_LR+
					data[i].OUTPUT_STAT.BACKLOG_LANE_COST_TOTAL_MR+
					data[i].OUTPUT_STAT.BACKLOG_LANE_COST_TOTAL_HR);

				backlog_lanemiles.push(data[i].OUTPUT_STAT.BACKLOG_LANE_MILES_TOTAL_PM+
					data[i].OUTPUT_STAT.BACKLOG_LANE_MILES_TOTAL_LR+
					data[i].OUTPUT_STAT.BACKLOG_LANE_MILES_TOTAL_MR+
					data[i].OUTPUT_STAT.BACKLOG_LANE_MILES_TOTAL_HR);
			}
			
			// if (me.initdone == false) {
				$('#report-summary-window-ok').jqxButton({
		            theme: 'classic',
		            width: '80px'
		        });
				
				$('#report-summary-window-ok').bind('click', function() {
					
				}); // end of button event
				
				$('#report-summary-window').jqxWindow({
					theme: 'classic',
					width: '685px',
					height: '310px',
					maxHeight: '700px',
					isModal: false,
					okButton: $('#report-summary-window-ok'),
			        cancelButton: $('#report-summary-window-cancel'),
			        resizable: false,
	//		        autoOpen: true,
			        initContent: function() {
			        	$('#tabbar-summary-panel').jqxPanel({width:'660px', height:'212px', theme:'classic'});
						$('#tabbar-summary').jqxTabs({width:'100%', theme:'classic', 
							initTabContent: function (tab) {
				                // The 'tab' parameter represents the selected tab's index.
				                if (tab == 0) {
				                	// var value = [[1110.8,168.6,307.9,275.6],[8.2,0,0,0],[6,29.8,77.1,76.5],[19,2,4,2.6]];
			 	                	var data = me.generatedata(mile_values, beginyear, endyear);
			 	        			me.initgrid("#number-lanemiles-mnr-grid", data, 'f1', backlog_lanemiles, beginyear, endyear, false);	                	
				                }
				                else if (tab == 1) {
				                	// var value = [[16359.86,2483.141,4534.751,4059.037],[623.9052,0,0,0],[470.574,2337.184,6046.875,5999.819],[2541.744,267.522,535.104,347.8176]];
			 	                	var data = me.generatedata(cost_values, beginyear, endyear);
			 	        			me.initgrid("#number-budget-grid", data, 'c2', backlog_cost, beginyear, endyear, false);
				                }
							}
			            });
			        }
				});	
			// }
			// else {
			// 	var data = me.generatedata(mile_values, beginyear, endyear);
			// 	me.initgrid("#number-lanemiles-mnr-grid", data, 'f1', backlog_lanemiles, beginyear, endyear, true);	                	
   //             	var data = me.generatedata(cost_values, beginyear, endyear);
   //     			me.initgrid("#number-budget-grid", data, 'c2', backlog_cost, beginyear, endyear, true);
			// }	
			this.initdone = true;
		},
		initdone: false,
		
		
		
		
		
		
		
		
		
		
		
		
		
		generatedata: function(value, beginyear, endyear) {
			var data = [];
			var rehaptype = ["PM", "LR", "MR", "HR"];
			for (j=0 ; j<4 ; j++) {
				var row = {};
				row['RehapType'] = rehaptype[j];
				for (k=beginyear ; k<=endyear ; k++) {
					row[k.toString()]=value[k-beginyear][j];
				}										
				var cloned = _.clone(row);
				data.push(row);
			}
			return data;
		},
		initgrid: function(gridid, data, cellsformat, backlog, beginyear, endyear, refresh) {
			var datafields = [];
			datafields.push({name:'RehapType', type: 'string'});
			for (k=beginyear ; k<=endyear ; k++)
				datafields.push({name: k.toString(), type: 'number'});

			// prepare the data
			var source =
			{
				datafields: datafields,
				localdata: data,
				datatype: "array"
			};

			var columns = [];
			columns.push({text: 'M&R Type', datafield: 'RehapType', width: 173});
			for (var k=beginyear ; k<=endyear ; k++) {
				var i = k;
				columns.push({text: k.toString(), datafield: k.toString(), width: 120, cellsformat:cellsformat,
				aggregates: [{ 'Total':
                        function (aggregatedValue, currentValue, column, record) {
                			return aggregatedValue + currentValue;
                		}
                	},{'Backlog':
                		function (aggregatedValue, currentValue, column, record) {
                			var current = parseFloat(column);
                			console.log("backlog:"+backlog);
                			console.log("i:"+i);
                			console.log("beginyear:"+beginyear);
                			console.log(backlog[i-beginyear]);
                			var l = i;
                    		return backlog[current-beginyear];
                		}
            		}],
            		aggregatesrenderer: function (aggregates) {
                        var total = aggregates['Total'];
                        var backlog = aggregates['Backlog'];
                        var renderstring = '<div style="font-size:12px;margin-top:5px;margin-left:3px;font-weight:bold;line-height:150%">' + "Total" + ': ' + total + "<br />Backlog: " + backlog + '</div>';
                        return renderstring;
                    }});			
			}
			var dataAdapter = new $.jqx.dataAdapter(source);
			if (refresh)
				$(gridid).jqxGrid(
				{
				    source: dataAdapter
				});
			else
				$(gridid).jqxGrid(
				{
				    width: '653px',
				    height: '551px',
				    source: dataAdapter,
				    altrows: true,
				    showaggregates: true,
				    showstatusbar: true,
	                statusbarheight: 50,
	                autoHeight: true,
				    theme: 'classic',
	                columns: columns
				});

		}
//		initgrid: function(gridid, detailid, data, detaildata) {
//			// prepare the data
//            var source =
//            {
//                datatype: "json",
//                datafields: [
//                    { name: 'Type', type: 'string' }
//                ],
//                id: "Type",
//                localdata: data
//            };
//            var dataAdapter = new $.jqx.dataAdapter(source);
//            var detailSource =
//            {
//                datafields: [
//                     { name: 'Type', type: 'string' },
//                     { name: 'RehapType', type: 'string' },
//                     { name: '2011', type: 'number' },
//                     { name: '2012', type: 'number' },
//                     { name: '2013', type: 'number' },
//                     { name: '2014', type: 'number' }
//                ],
//                root: "Type",
//                localdata: detaildata
//            };
//            var detailDataAdapter = new $.jqx.dataAdapter(detailSource, { autoBind: true });
//            detail = detailDataAdapter.records;
//			var initrowdetails = function (index, parentElement, gridElement, record) {
//	            var id = record.uid.toString();
//	            var grid = $($(parentElement).children()[0]);
//	            var filtergroup = new $.jqx.filter();
//	            var filter_or_operator = 1;
//	            var filtervalue = id;
//	            var filtercondition = 'equal';
//	            var filter = filtergroup.createfilter('stringfilter', filtervalue, filtercondition);
//	            // fill the detail depending on the id.
//	            var detailbyid = [];
//	            for (var m = 0; m < detail.length; m++) {
//	                var result = filter.evaluate(detail[m]["Type"]);
//	                if (result)
//	                    detailbyid.push(detail[m]);
//	            }
//	            var detailsource = { datafields: [
//	                { name: 'Type' },
//	                { name: 'RehapType' },
//	                { name: '2011' },
//	                { name: '2012' },
//	                { name: '2013' },
//	                { name: '2014' }
//	            ],
//	                id: 'Type',
//	                localdata: detailbyid
//	            }
//	            if (grid != null) {
//	                grid.jqxGrid({ 
//	                	source: detailsource, 
//	                	theme: 'classic',
//	                	width: 653, 
//	                	rowsheight: 23,
//	                	autoheight: true,
////	                	showaggregates: true,
//	                    columns: [
//	                      { text: 'M&R Type', datafield: 'RehapType', width: 173},
//	                      { text: '2011', datafield: '2011', width: 120, aggregates: ['sum'] },
//	                      { text: '2012', datafield: '2012', width: 120, aggregates: ['sum'] },
//	                      { text: '2013', datafield: '2013', width: 120, aggregates: ['sum'] },
//	                      { text: '2014', datafield: '2014', width: 120, aggregates: ['sum'] }
//	                   ]
//	                });
//	            }
//	        }
//			 
//            $(gridid).jqxGrid(
//            {
//                width: '694px',
//                height: '551px',
//                rowdetails: true,
//                initrowdetails: initrowdetails,
//                enablehover: false,
//                rowdetailstemplate: { rowdetails: "<div id='"+detailid+"' style='margin: 5px;'></div>", rowdetailsheight: 150},
//                source: dataAdapter,
//                theme: 'classic',
//                columns: [
//                    { text: 'Type', datafield: 'Type', width: '664px', cellsrenderer:function (row, column, value) {
//                        return '<span style="margin-left: 4px; margin-top: 9px; background:yellow;float: left;font-size:13px">' + value + '</span>';
//                    }}
//                ],
//                ready: function() {
//                	$(gridid).jqxGrid('showrowdetails', 0);
//                	$(gridid).jqxGrid('showrowdetails', 1);
//                	$(gridid).jqxGrid('showrowdetails', 2);
//                }
//            });
//		}
	});
	return new window(document);
});