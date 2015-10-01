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
			var view = can.view(require.toUrl('views/window/unit.cost.window.ejs'));
			$('#window').append(view);				
		},
		initwindow: function() {
			var params = {table:'unit_cost_'+district};
			var cached = [];
			var promises = [];
			promises.push(grid.findAll({querytype:'etc', template:util.viewtemplate.unitcost, params:params}, function(response) {
				var template = util.viewtemplate.unitcost;
				var columns = response.columns;
				var pinned = template.pinnedcolumns;
				var unpinned = template.unpinnedcolumns;
				var filteritem = [['PM', 'LR', 'MR', 'HR'],['ACP','JCP','CRCP']];
				for (var i=0 ; i<pinned.length ; i++)
					_.extend(columns[pinned[i]], {editable:false, filtertype: 'checkedlist', filteritems:filteritem[i], width:'120px'});	
				for (var i=0 ; i<unpinned.length ; i++)
					_.extend(columns[unpinned[i]], {columntype:'numberinput', width:'120px', cellsformat:'c3',  
						createeditor: function (row, cellvalue, editor) {
	                          editor.jqxNumberInput({ digits: 10, decimalDigits: 3 });
	                    },
						cellsalign: 'right'
				    });

				cached.push({template:response.template, dataadapter:response.dataadapter, columns:columns});
			
				$(template.gridid).jqxGrid({
			        width: '360px',
			        height: '356px',
			        source: response.dataadapter,
			        theme: 'classic',
			        sortable: true,
			        autoshowfiltericon: true,
			        selectionmode: 'multiplecellsadvanced',
			        autoHeight: true,
			        editable: true,
			        columns: columns
				});
			}).promise());

			$.when.apply($, promises).done(function() {
				$('#unit-cost-window-ok').jqxButton({disabled: false});
			});
			$('#unit-cost-window-restore').bind('click', function() {
				console.log(cached);
	        	for (var i=0 ; i<cached.length ; i++) {
	        		$(cached[i].template.gridid).jqxGrid({
	        			source:cached[i].dataadapter,
	        			columns:cached[i].columns
	        		});
	        	}
	        });		
			
			$('#unit-cost-window-restore').jqxButton({
				theme: 'classic',
				width: '90px'
			})

			$('#unit-cost-window-ok').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#unit-cost-window-ok').bind('click', function() {
				
			}); // end of button event
			
			$('#unit-cost-window-cancel').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#unit-cost-window').jqxWindow({
				theme: 'classic',
				width: '380px',
				height: '425px',
				maxHeight: '700px',
				isModal: false,
				okButton: $('#unit-cost-window-ok'),
		        cancelButton: $('#unit-cost-window-cancel'),
		        resizable: false
			});			
			this.initdone = true;
		},
		initdone: false
	});
	return new window(document);
});