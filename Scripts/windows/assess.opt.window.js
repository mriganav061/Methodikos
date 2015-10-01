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
	var assessoptwindow = can.Control({
		init: function (element, options) {
			var view = can.view(require.toUrl('views/window/assessment.window.ejs'));
			$('#window').append(view);
		},
		initwindow: function() {
			// combobox init
			$("#combo-highway").jqxComboBox(
			{
				theme: 'classic',
				width: 200,
				height: 20,
				promptText: "Select Highway...",
				displayMember: 'SIGNED_HIGHWAY_RDBD_ID',
				valueMember: 'SIGNED_HIGHWAY_RDBD_ID'
			});
			
			$("#combo-brm").jqxComboBox(
			{
				theme: 'classic',
				width: 200,
				height: 20,
				disabled: true,
				promptText: "Select BRM...",
				displayMember: 'BEG_REF_MARKER',
				valueMember: 'BEG_REF_MARKER_VAL'
			});
			
			$("#combo-erm").jqxComboBox(
			{
				theme: 'classic',
				width: 200,
				height: 20,
				disabled: true,
				promptText: "Select ERM...",
				displayMember: 'END_REF_MARKER',
				valueMember: 'END_REF_MARKER_VAL'
			});
			
			$("#combo-assess-type").jqxComboBox(
			{
				theme: 'classic',
				source:["Visual","Skid", "Structural", "Forced Projects"],
				width: 200,
				height: 20,
				autoDropDownHeight: true,
				promptText: "Select Assessment Type..."
			});
			
			$("#combo-assess-val").jqxComboBox(
			{
				theme: 'classic',
				width: 200,
				height: 20,
				disabled: true,
				autoDropDownHeight: true,
				promptText: "Select Assessment Value..."
			});
			
			var params = {
				datafields: ['SIGNED_HIGHWAY_RDBD_ID'],
				data: {querytype:'combo-highway'},
				url: '../../Services/grid/pmis.php'
			};
			combobox.findAll({params:params}, function(response) {
				$("#combo-highway").jqxComboBox({source: response.dataadapter});
			});
			
			var highway;
			
			$("#combo-highway").bind('select', function(event)
			{
				if (event.args)
				{
					$("#combo-brm").jqxComboBox({ disabled: false });	
					$("#combo-brm").jqxComboBox('selectIndex', -1); 
					highway = event.args.item.value;
					var params = {
							datafields: ['BEG_REF_MARKER','BEG_REF_MARKER_VAL'],
							data: {querytype:'combo-brm', SIGNED_HIGHWAY_RDBD_ID: highway},
							url: '../../Services/grid/pmis.php'
					}; 
					combobox.findAll({params:params}, function(response) {
						$("#combo-brm").jqxComboBox({source: response.dataadapter});
					});
				}
			});   
			
			$("#combo-brm").bind('select', function(event)
			{
				if (event.args)
				{
					$("#combo-erm").jqxComboBox({ disabled: false });	
					$("#combo-erm").jqxComboBox('selectIndex', -1); 
					if (event.args.item) {
						var value = event.args.item.value;
						var params = {
								datafields: ['END_REF_MARKER','END_REF_MARKER_VAL'],
								data: {querytype:'combo-erm', SIGNED_HIGHWAY_RDBD_ID: highway,
									brm: value},
								url: '../../Services/grid/pmis.php'
						}; 
						combobox.findAll({params:params}, function(response) {
							$("#combo-erm").jqxComboBox({source: response.dataadapter});
						});
					}
					else
						$("#combo-erm").jqxComboBox({source: null});
				}
			}); 
			
			$("#combo-assess-type").bind('select', function(event)
			{
				if (event.args)
				{
					$("#combo-assess-val").jqxComboBox({ disabled: false });	
					$("#combo-assess-val").jqxComboBox('selectIndex', -1); 
					if (event.args.item) {
						var value = event.args.item.value;
						if (value == "Forced Projects")
							$("#combo-assess-val").jqxComboBox({source: ["PM", "LR", "MR", "HR"]});
						else
							$("#combo-assess-val").jqxComboBox({source: ["Adequate", "Inadequate"]});
					}
					else
						$("#combo-assess-val").jqxComboBox({source: null});
				}
			});
			
			var generaterow = function () {
                var row = {};
                row["highway"] = $("#combo-highway").jqxComboBox('getSelectedItem').value;
                row["brm"] = $("#combo-brm").jqxComboBox('getSelectedItem').value;
                row["erm"] = $("#combo-erm").jqxComboBox('getSelectedItem').value;
                row["type"] = $("#combo-assess-type").jqxComboBox('getSelectedItem').value;
                row["value"] = $("#combo-assess-val").jqxComboBox('getSelectedItem').value;
                return row;
            }
	        var data=[];
            var source =
            {
                localdata: data,
                datatype: "local",
                addrow: function (rowid, rowdata, position, commit) {
                    commit(true);
                },
                deleterow: function (rowid, commit) {
                    commit(true);
                },
                updaterow: function (rowid, newdata, commit) {
                    commit(true);
                }
            };
            var dataAdapter = new $.jqx.dataAdapter(source);
            // initialize jqxGrid
            
	        $('#assess-grid').jqxGrid(
            {
                width: 525,
                height: 280,
                source: dataAdapter,
                theme: 'classic',
                columns: [
                  { text: 'Highway ID', datafield: 'highway', width: 105 },
                  { text: 'BRM', datafield: 'brm', width: 105 },
                  { text: 'ERM', datafield: 'erm', width: 105 },
                  { text: 'Type', datafield: 'type', width: 105},
                  { text: 'Value', datafield: 'value', width: 105}
                ]
            });		        
			
			$('#assessment-window-restore').jqxButton({
	            theme: 'classic',
	            width: '120px'
	        });

			$('#assessment-window-ok').jqxButton({
	            theme: 'classic',
	            width: '120px'
	        });
			
			$('#assessment-window-ok').bind('click', function() {
				$.ajax({
	                type: "POST",
	                url: "../../Services/grid/pmis.php",
	                dataType: 'json',
	                data: { 
	                	assessment_rows: $("#assess-grid").jqxGrid('exportdata', 'json'),
	                	assessment_threshold: $('#assess-threshold').jqxNumberInput('getDecimal'),
	                	querytype:'assessment' 
	                },
	                success: function(data) {
	                	// reload segmented
	                	util.addtab(2);
//	    				$('#tabbar').jqxTabs('removeAt', 3);
//	    				$('#tabbar').jqxTabs('addAt', 3, "Assessed PMIS", "<div id='assessed-pmis-grid'></div>");
//	    				$('#tabbar > div:nth-child(3) > div:last-child').attr('id', 'tab-4');
	    				$('#tabbar').jqxTabs('select', 2);
	    				// grid with detail
	    				$.when(
	    					grid.findAll({querytype:'etc', fiscalyears:baseyear, template:util.viewtemplate.seg, params:{table:'segmented_pmis_aggregated_'+userid, loadseg:true}}),
	    					grid.findAll({querytype:'etc', fiscalyears:baseyear, template:util.viewtemplate.seg, params:{table:'segmented_pmis_'+userid, loadseg:true}})
	    				).then(function(responseMaster, responseDetail) {
	    					util.initmasterdetailgrid(responseMaster, responseDetail, util.viewtemplate.seg);
	    					$('#detail-container').css('display', 'block');
	    				});
	    				// reload assessed
//	    				grid.findAll({querytype:'etc', template:util.viewtemplate.assessed, params:{table:'segmented_pmis_'+userid,asseessedonly:true}}, function(response) {
//	    					util.initgrid(response, util.viewtemplate.assessed, grid, params);
//	    				});
	                }
	            });				
			}); // end of button event
			
			$('#assessment-window-cancel').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#assessment-window-add').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#assessment-window-add').bind('click', function() {
				var highway = $("#combo-highway").jqxComboBox('getSelectedItem');
                var brm = $("#combo-brm").jqxComboBox('getSelectedItem');
                var erm = $("#combo-erm").jqxComboBox('getSelectedItem');
                var type = $("#combo-assess-type").jqxComboBox('getSelectedItem');
                var value = $("#combo-assess-val").jqxComboBox('getSelectedItem');
                if (highway==null||brm==null||erm==null||type==null||value==null) {
                	alert('One of the values is empty');
                	return;
                }		                	
				var datarow = generaterow();
                var commit = $('#assess-grid').jqxGrid('addrow', null, datarow);
			});
			
			$('#assessment-window-remove').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#assessment-window-remove').bind('click', function() {
				var selectedrowindex = $('#assess-grid').jqxGrid('getselectedrowindex');
                var rowscount = $('#assess-grid').jqxGrid('getdatainformation').rowscount;
                if (selectedrowindex >= 0 && selectedrowindex < rowscount) {
                    var id = $('#assess-grid').jqxGrid('getrowid', selectedrowindex);
                    var commit = $('#assess-grid').jqxGrid('deleterow', id);
                }
			});
			
			$('#assess-threshold').jqxNumberInput({
		        width: 35,
		        height: 20,
		        decimal: 10,
		        inputMode: 'simple',
		        spinButtons: false,
		        theme: 'classic'
		    });
			
			$('#assessment-window').jqxWindow({
				theme: 'classic',
				width: '550px',
				height: '605px',
				maxHeight: '610px',
				isModal: false,
				modalOpacity: 0.1,
				okButton: $('#assessment-window-ok'),
		        cancelButton: $('#assessment-window-cancel'),
		        resizable: false
			});
			this.initdone = true;
		},
		initdone: false
	});
	return new assessoptwindow(document);
});