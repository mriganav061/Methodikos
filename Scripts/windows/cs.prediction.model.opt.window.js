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
			var view = can.view(require.toUrl('views/window/cs.prediction.window.ejs'));
			$('#window').append(view);
		},
		initwindow: function() {
			var me = this;
			$('#tabbar-cs-prediction-model-panel').jqxPanel({
				width: '450px',//'775px',
				height: '534px',
				theme: 'classic'
			});
			
			$('#tabbar-cs-prediction-model').jqxTabs({ theme: 'classic', width: '100%', scrollable:false });
			
			// load default param
			var rehaptype = ['PM', 'LR', 'MR', 'HR'];
			var cached = [];
			var promises = [];
			for (var i=0 ; i<rehaptype.length ; i++)  {
				var params = {table:'cs_modeling_parameters_'+district, rehaptype:rehaptype[i]};
				var template = _.clone(util.viewtemplate.csparams);
				template.tabid += "cs-"+rehaptype[i];
				template.gridid += "cs-"+rehaptype[i];
				promises.push(grid.findAll({querytype:'etc', template:template, params:params}, function(response) {
					var template = response.template;
					var columns = response.columns;
					var pinned = template.pinnedcolumns;
					var unpinned = template.unpinnedcolumns;
					var filteritem = [['A', 'B', 'C'],['High', 'Med', 'Low'],['High', 'Med', 'Low']];
					var width = [95,95,95];//[130, 130, 130];
					for (var j=0 ; j<pinned.length ; j++)
						_.extend(columns[pinned[j]], {editable:false, filtertype: 'checkedlist', filteritems:filteritem[i], width:width[j]});	
					for (var j=0 ; j<unpinned.length ; j++)
						_.extend(columns[unpinned[j]], {columntype:'numberinput', width:'70px'});//'179px'});
					var columnCheckBox = null;
			        var updatingCheckState = false;
			        
			        /* uncomment this to add 'Set to Default' Buttons to each row
					columns.push({
						text: 'Set to Default', 
						datafield: 'Set to Default', 
						columntype: 'button', 
						cellsrenderer: function () { return "Set to Default";}, 
						buttonclick: function (rowindex) {
							var originaldata = response.dataadapter.originaldata;
				            $(template.gridid).jqxGrid('setcellvalue', rowindex, "Rho", originaldata[rowindex].Rho);
				            $(template.gridid).jqxGrid('setcellvalue', rowindex, "Beta", originaldata[rowindex].Beta);
						},
						renderer: function () {
							return '<div style="margin-left: 2px; margin-top: 1px;margin-right: 1px;padding-bottom: 4px;"></div>';
						},
						rendered: function (element) {
							$(element).html('Set to Default');
							$(element).jqxButton({ theme: 'classic' });
							var rowscount = $(template.gridid).jqxGrid('getdatainformation').rowscount;
							$(element).bind('click', function (event) {
								var originaldata = response.dataadapter.originaldata;
								for (var i = 0; i < rowscount; i++) {
									$(template.gridid).jqxGrid('setcellvalue', i, "Rho", originaldata[i].Rho);
					                $(template.gridid).jqxGrid('setcellvalue', i, "Beta", originaldata[i].Beta);
								}
							});
						},
						sortable: false,
						filterable: false,
						menu: false
			        });
			        */
			        cached.push({template:response.template, dataadapter:response.dataadapter, columns:columns});
					$(template.gridid).jqxGrid({
				        width: '444px',//'768px',
				        height: '495px',
				        source: response.dataadapter,
				        theme: 'classic',
				        filterable: true,
				        sortable: true,
				        autoshowfiltericon: true,
				        showfilterrow: true,
				        selectionmode: 'multiplecellsadvanced',
				        editable: true,
				        editmode: 'selectedcell',
				        columns: columns
					});
				}).promise());
			}
			$.when.apply($, promises).done(function() {
				$('#cs-prediction-window-ok').jqxButton({disabled: false});		
			});
			$('#cs-prediction-window-restore').bind('click', function() {
	        	for (var i=0 ; i<cached.length ; i++) {
	        		$(cached[i].template.gridid).jqxGrid({
	        			source:cached[i].dataadapter,
	        			columns:cached[i].columns
	        		});
	        	}
	        });		
			// get preset
			// $("#combo-cs-preset").jqxComboBox(
			// {
			// 	theme: 'classic',
			// 	width: 200,
			// 	height: 20,
			// 	promptText: "Select Preset...",
			// 	displayMember: 'Setting Name',
			// 	valueMember: 'Setting Name',
			// 	autoDropDownHeight: true
			// });
			
			// var params = {
			// 		datafields: ['Setting Name'],
			// 		data: {querytype:'combo-preset', params:{table:'cs_modeling_parameters_'+district}},
			// 		url: '../../Services/grid/params.php'
			// };
			
			// combobox.findAll({params:params}, function(response) {
			// 	$('#combo-cs-preset').jqxComboBox({source: response.dataadapter});
			// });
			
			// $('#combo-cs-preset').bind('select', function (event) 
			// {
			//     var args = event.args;
			//     $('#cs-prediction-window-ok').jqxButton({disabled: true});
			// 	$('#cs-prediction-window-saveas').jqxButton({disabled: true});
			//     if (args) {
			//     	var item = args.item;
			//         var settingname = item.value;
			//         for (var i=0 ; i<rehaptype.length ; i++)  {
			// 			var params = {table:'cs_modeling_parameters_'+district, rehaptype:rehaptype[i], settingname:settingname};
			// 			var template = _.clone(util.viewtemplate.params);
			// 			template.tabid += rehaptype[i];
			// 			template.gridid += rehaptype[i];
			// 			grid.findAll({querytype:'etc', template:template, params:params}, function(response) {
			// 				util.initparamgrid(response);
			// 			});
			// 		}
			//     }
			// }); 
			
			// $('#cs-prediction-window-threshold').jqxNumberInput({
		 //        width: '140px',
		 //        height: '20px',
		 //        decimal: 70,
		 //        decimalDigits: 0,
		 //        inputMode: 'simple',
		 //        spinMode: 'simple',
		 //        spinButtons: true,
		 //        spinButtonsStep: 1,
		 //        theme: 'classic'
		 //    });
			
			// $('#cs-prediction-window-discountrate').jqxNumberInput({
		 //        width: '140px',
		 //        height: '20px',
		 //        decimal: 0.03,
		 //        inputMode: 'simple',
		 //        spinMode: 'simple',
		 //        spinButtons: true,
		 //        spinButtonsStep: 1,
		 //        theme: 'classic'
		 //    });
			
			// $('#cs-prediction-window-saveas').jqxButton({
	  //           theme: 'classic',
	  //           width: '80px',
	  //           disabled: true
	  //       });
			
			// $('#cs-prediction-window-saveas').bind('click', function() {
			// 	$('#saveas-window').jqxWindow('open');
			// 	$('#saveas-input').focus();
			// });
			
			// try to save the coefficient setting to the server
			// $('#saveas-form').jqxValidator({ 
			// 	onSuccess: function () { 
			// 		var settingname = $('#saveas-input').val();
			// 		for (var i=0 ; i<rehaptype.length ; i++)  {
			// 			var template = _.clone(util.viewtemplate.params);
			// 			template.tabid += rehaptype[i];
			// 			template.gridid += rehaptype[i];
			// 			var data = $(template.gridid).jqxGrid('exportdata', 'json');
			// 			var params = {table:'cs_modeling_parameters_'+district, data:data, rehaptype:rehaptype[i]
			// 			, settingname:settingname};
						
			// 			grid.create({querytype:'add-new-setting', template:template, params:params}).success(function (data) {
			// 				if (data.success && $('#saveas-window').jqxWindow('isOpen')) {
			// 					alert('Successfully Saved');
			// 					$('#saveas-window').jqxWindow('close');
			// 				}
			// 			});
						
			// 		}
			// 	} 
			// });

			$('#cs-prediction-window-restore').jqxButton({
	            theme: 'classic',
	            width: '90px'
	        });	        
			
			$('#cs-prediction-window-ok').jqxButton({
	            theme: 'classic',
	            width: '80px',
	            disabled: true
	        });
			
			$('#cs-prediction-window-ok').bind('click', function() {
				
			}); // end of button event
			
			$('#cs-prediction-window-cancel').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
	
			$('#cs-prediction-window').jqxWindow({
				theme: 'classic',
				width: '470px',
				height: '690px',
				maxHeight: '700px',
				isModal: false,
				modalOpacity: 0.1,
				okButton: $('#cs-prediction-window-ok'),
		        cancelButton: $('#cs-prediction-window-cancel'),
		        resizable: false,
			});
			this.initdone = true; 
		},
		initdone: false,

		loadparam: function() {
			
		}
	});
	return new window(document);
});