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
			var view = can.view(require.toUrl('views/window/ride.utility.window.ejs'));
			$('#window').append(view);				
		},
		initwindow: function() {
			var params = {table:'ride_coeff_'+district};
			var cached = [];
			var promises = [];
			promises.push(grid.findAll({querytype:'etc', template:util.viewtemplate.ridecoeff, params:params}, function(response) {
				var template = util.viewtemplate.ridecoeff;
				var columns = response.columns;
				var pinned = template.pinnedcolumns;
				var unpinned = template.unpinnedcolumns;
				var filteritem = [['Low', 'Med', 'High']];
				var width = [30];//80];
				for (var i=0 ; i<pinned.length ; i++)
					_.extend(columns[pinned[i]], {editable:false, filtertype: 'checkedlist', filteritems:filteritem[i]});	
				for (var i=0 ; i<unpinned.length ; i++)
					_.extend(columns[unpinned[i]], {columntype:'numberinput', width:'100px',//'207.5px',  
						createeditor: function (row, cellvalue, editor) {
	                          editor.jqxNumberInput({ digits: 10, decimalDigits: 3 });
	                    }
				    });

				cached.push({template:response.template, dataadapter:response.dataadapter, columns:columns});
			
				$(template.gridid).jqxGrid({
			        width: '450px',//'776px',
			        height: '101px',
			        source: response.dataadapter,
			        theme: 'classic',
			        sortable: true,
			        autoshowfiltericon: true,
			        showfilterrow: true,
			        selectionmode: 'multiplecellsadvanced',
			        editable: true,
			        columns: columns
				});
			}).promise());

			$.when.apply($, promises).done(function() {
				$('#ride-utility-coeff-window-ok').jqxButton({disabled: false});
			});
			$('#ride-utility-coeff-window-restore').bind('click', function() {
	        	for (var i=0 ; i<cached.length ; i++) {
	        		$(cached[i].template.gridid).jqxGrid({
	        			source:cached[i].dataadapter,
	        			columns:cached[i].columns
	        		});
	        	}
	        });		

	        $('#ride-utility-coeff-window-restore').jqxButton({
	        	width: '90px',
	        	theme: 'classic'
	        })

			$('#ride-utility-coeff-window-ok').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#ride-utility-coeff-window-ok').bind('click', function() {
				
			}); // end of button event
			
			$('#ride-utility-coeff-window-cancel').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#ride-utility-coeff-window').jqxWindow({
				theme: 'classic',
				width: '470px',
				height: '250px',
				maxHeight: '700px',
				isModal: false,
				modalOpacity: 0.1,
				okButton: $('#ride-utility-coeff-window-ok'),
		        cancelButton: $('#ride-utility-coeff-window-cancel'),
		        resizable: false
			});			
			this.initdone = true;
		},
		initdone: false
	});
	return new window(document);
});