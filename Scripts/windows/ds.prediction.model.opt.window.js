define([
	'jquery',
	'underscore',
	'can',
	'models/grid',
	'models/combobox',
	'util',
	'livequery',
	'jqx'
], function ($, _, can, grid, combobox, util) {
	var window = can.Control({
		init: function (element, options) {
			var view = can.view(require.toUrl('views/window/ds.prediction.window.ejs'));
			$('#window').append(view);
		},
		initwindow: function() {
			$('#tabbar-ds-prediction-model-panel').jqxPanel({
				width: '353px',//'775px',
				height: '290px',
				theme: 'classic'
			});
			
			$('#tabbar-ds-prediction-model').jqxTabs({ theme: 'classic', width: '100%', scrollable:false });
			
			var rehaptype = ['PM', 'LR', 'MR', 'HR'];
			var cached = [];
			var promises = [];
			for (var i=0 ; i<rehaptype.length ; i++)  {
				var params = {table:'ds_modeling_parameters_'+district, rehaptype:rehaptype[i]};
				var template = _.clone(util.viewtemplate.dsparams);
				template.tabid += "ds-"+rehaptype[i];
				template.gridid += "ds-"+rehaptype[i];
				promises.push(grid.findAll({querytype:'etc', template:template, params:params}, function(response) {
					var template = response.template;
					var columns = response.columns;
					var pinned = template.pinnedcolumns;
					var unpinned = template.unpinnedcolumns;
					var width = [95,95,95];//[130, 130, 130];
					for (var i=0 ; i<pinned.length ; i++)
						_.extend(columns[pinned[i]], {editable:false, width:width[i]});	
					for (var i=0 ; i<unpinned.length ; i++)
						_.extend(columns[unpinned[i]], {columntype:'numberinput', width:'78px'});//'179px'});
					var columnCheckBox = null;
			        var updatingCheckState = false;
			        cached.push({template:response.template, dataadapter:response.dataadapter, columns:columns});
					$(template.gridid).jqxGrid({
				        width: '346px',//'768px',
				        autoHeight: true,
				        source: response.dataadapter,
				        theme: 'classic',
				        sortable: true,
				        autoshowfiltericon: true,
				        selectionmode: 'multiplecellsadvanced',
				        editable: true,
				        editmode: 'selectedcell',
				        columns: columns
					});
				}).promise());
				
			}
			$.when.apply($, promises).done(function() {
				$('#ds-prediction-window-ok').jqxButton({disabled: false});
			});
			$('#ds-prediction-window-restore').bind('click', function() {
				console.log(cached);
	        	for (var i=0 ; i<cached.length ; i++) {
	        		$(cached[i].template.gridid).jqxGrid({
	        			source:cached[i].dataadapter,
	        			columns:cached[i].columns
	        		});
	        	}
	        });		
			
			// get preset
//			$("#combo-ds-preset").jqxComboBox(
//			{
//				theme: 'classic',
//				width: 200,
//				height: 20,
//				promptText: "Select Preset...",
//				displayMember: 'Setting Name',
//				valueMember: 'Setting Name',
//				autoDropDownHeight: true
//			});
//			
//			var params = {
//					datafields: ['Setting Name'],
//					data: {querytype:'combo-preset', params:{table:'ds_modeling_parameters_'+district}},
//					url: '../../Services/grid/params.php'
//			};
//			
//			combobox.findAll({params:params}, function(response) {
//				$('#combo-ds-preset').jqxComboBox({source: response.dataadapter});
//			});
//			
//			$('#combo-ds-preset').bind('select', function (event) 
//			{
//			    var args = event.args;
//			    $('#ds-prediction-window-ok').jqxButton({disabled: true});
//				$('#ds-prediction-window-saveas').jqxButton({disabled: true});
//			    if (args) {
//			    	var item = args.item;
//			        var settingname = item.value;
//			        for (var i=0 ; i<rehaptype.length ; i++)  {
//						var params = {table:'ds_modeling_parameters_'+district, rehaptype:rehaptype[i], settingname:settingname};
//						var template = _.clone(util.viewtemplate.params);
//						template.tabid += rehaptype[i];
//						template.gridid += rehaptype[i];
//						grid.findAll({querytype:'etc', template:template, params:params}, function(response) {
//							util.initparamgrid(response);
//						});
//					}
//			    }
//			}); 
			
//			$('#ds-prediction-window-threshold').jqxNumberInput({
//		        width: '140px',
//		        height: '20px',
//		        decimal: 70,
//		        decimalDigits: 0,
//		        inputMode: 'simple',
//		        spinMode: 'simple',
//		        spinButtons: true,
//		        spinButtonsStep: 1,
//		        theme: 'classic'
//		    });
			
//			$('#ds-prediction-window-discountrate').jqxNumberInput({
//		        width: '140px',
//		        height: '20px',
//		        decimal: 0.03,
//		        inputMode: 'simple',
//		        spinMode: 'simple',
//		        spinButtons: true,
//		        spinButtonsStep: 1,
//		        theme: 'classic'
//		    });
			
//			$('#ds-prediction-window-saveas').jqxButton({
//	            theme: 'classic',
//	            width: '80px',
//	            disabled: true
//	        });
			
//			$('#ds-prediction-window-saveas').bind('click', function() {
//				$('#saveas-window').jqxWindow('open');
//				$('#saveas-input').focus();
//			});
			
			// try to save the coefficient setting to the server
//			$('#saveas-form').jqxValidator({ 
//				onSuccess: function () { 
//					var settingname = $('#saveas-input').val();
//					for (var i=0 ; i<rehaptype.length ; i++)  {
//						var template = _.clone(util.viewtemplate.params);
//						template.tabid += rehaptype[i];
//						template.gridid += rehaptype[i];
//						var data = $(template.gridid).jqxGrid('exportdata', 'json');
//						var params = {table:'ds_modeling_parameters_'+district, data:data, rehaptype:rehaptype[i]
//						, settingname:settingname};
//						
//						grid.create({querytype:'add-new-setting', template:template, params:params}).success(function (data) {
//							if (data.success && $('#saveas-window').jqxWindow('isOpen')) {
//								alert('Successfully Saved');
//								$('#saveas-window').jqxWindow('close');
//							}
//						});
//						
//					}
//				} 
//			});
			$('#ds-prediction-window-restore').jqxButton({
	            theme: 'classic',
	            width: '90px'
	        });	
			
			$('#ds-prediction-window-ok').jqxButton({
	            theme: 'classic',
	            width: '80px',
	            disabled: true
	        });
			
			$('#ds-prediction-window-ok').bind('click', function() {
				
			}); // end of button event
			
			$('#ds-prediction-window-cancel').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
	
			$('#ds-prediction-window').jqxWindow({
				theme: 'classic',
				width: '375px',
				height: '445px',
				maxHeight: '700px',
				isModal: false,
				modalOpacity: 0.1,
				okButton: $('#ds-prediction-window-ok'),
		        cancelButton: $('#ds-prediction-window-cancel'),
		        resizable: false,
			});
			this.initdone = true; 
		},
		initdone: false
	});
	return new window(document);
});