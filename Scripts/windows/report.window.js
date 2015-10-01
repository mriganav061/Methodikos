define([
	'jquery',
	'underscore',
	'can',
	'models/grid',
	'models/combobox',
	'util',
	'windows/report.summary.window',
	'windows/report.detail.list.window',
	'windows/report.network.condition.window',
	'jqx'
], function ($, _, can, grid, combobox, util, summarywin, impactwin, networkwin) {
	var window = can.Control({
		init: function (element, options) {
			// this.initwindow();
		},
		initwindow: function(data) {
			if (data == null) {
				// if (this.initdone) {
				// 	$('#report-window').jqxWindow('open');
				// }
				// else {
				 	console.log('input data of reportwin is empty');
					return;
				// }
			}
			var tabledata = {
				BeginYear:data.BEGIN_YEAR,
				EndYear:data.END_YEAR,
				District:data.DISTRICT,
				TotalLaneMiles:data.TOTAL_LANE_MILES,
				TotalSegments:data.TOTAL_SEGMENTS,
				TotalBudget:data.TOTAL_BUDGET
			};
			var viewdata = {
				analysis: tabledata
			};
			$('#report-window').remove();
			var view = can.view(require.toUrl('views/window/report.window.ejs'), viewdata);
			$('#window').append(view);

			// prepare the data
			var category = ['Summary of Projects', 
			                'Detailed List of Funded Projects',
			                'Network Condition']
			var windowhandler = [summarywin, impactwin, networkwin];
			var windowid = ['#report-summary-window', '#report-detail-list-window', '#report-network-condition-window'];
			this.initgrid("#report-type-grid", category, windowhandler, windowid, data);
			$("#report-type-grid").jqxGrid({showheader:false, height:100})
			$('#report-window-ok').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#report-window-ok').bind('click', function() {
				
			}); // end of button event
			
			$('#report-window').jqxWindow({
				theme: 'classic',
				width: '400px',
				height: 'auto',//'290px',
				maxHeight: '700px',
				okButton: $('#report-window-ok'),
		        resizable: false,
		        autoOpen: true
			});			
			this.initdone = true;
		},
		initdone: false,
		initgrid: function(gridid, category, handler, windowid, analysisresult) {
			var data = [];
			for (var i=0 ; i<category.length ; i++) {
				var row = {};
				row['category'] = category[i]; 
				data[i] = row;
			}
			var source =
            {
                localdata: data,
                datatype: "array",
                datafields:
                [
                    { name: 'category', type: 'string' },
                ]
            };
            var dataAdapter = new $.jqx.dataAdapter(source);       
            $(gridid).jqxGrid(
            {
                width: 380,
                source: dataAdapter,
                theme: 'classic',
                selectionmode: 'none',
                autoheight: true,
                columns: [
                  { text: 'Category', dataField: 'category', width: 230,  cellsalign: 'center', align: 'center' },
                  { text: 'View', datafield: 'View', columntype: 'button', cellsrenderer: function () {
                      return "View";
	                  }, buttonclick: function (row) {
	                      // open the popup window when the user clicks a button.
	                      if (analysisresult != null)
	                      	handler[row].initwindow(analysisresult);
	                      if (handler[row].initdone)
	                      	$(windowid[row]).jqxWindow('open');	                		  
	                	  $(windowid[row]).focus();
	                	  $(windowid[row]).jqxWindow('bringToFront');
	                  }
                  ,  cellsalign: 'center', align: 'center'}
                ]
            });
		}
	});
	return new window(document);
});