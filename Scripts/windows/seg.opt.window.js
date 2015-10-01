define([
	'jquery',
	'underscore',
	'can',
	'models/grid',
	'models/combobox',
	'util',
	'zcalc',
	'livequery',
	'jqx'
], function ($, _, can, grid, combobox, util) {
	var segoptwindow = can.Control({
		init: function (element, options) {
			var view = can.view(require.toUrl('views/window/segmentation.window.ejs'));
			$('#window').append(view);
			// this.initwindow();
		},
		selected_tab: 0,
		initwindow: function() {
			var me = this;
			me.selected_tab = 0;
			$('#tabbar-segmentation-option-panel').jqxPanel({width:'380px', height:'200px', theme:'classic'});
			$('#tabbar-segmentation-option').jqxTabs({width:'100%', theme:'classic' });	   

			//****************** Add CS OR DS			
			
			$('#cs-threshold-method1').jqxNumberInput({
		        width: '140px',
		        height: '20px',
		        decimal: 70,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic'
		    });
			
			$('#ds-threshold-method1').jqxNumberInput({
		        width: '140px',
		        height: '20px',
		        decimal: 80,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic'
		    });
			
			$('#min-seg-len-method1').jqxNumberInput({
		        width: '140px',
		        height: '20px',
		        decimal: 2,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic'
		    });
			
			$('#max-seg-len-method1').jqxNumberInput({
		        width: '140px',
		        height: '20px',
		        decimal: 10,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic'
		    });
			
			$('#reliability-level-method1').jqxNumberInput({
		        width: '140px',
		        height: '20px',
		        decimal: 50,
		        decimalDigits: 0,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic',
		        symbol: '%',
		        symbolPosition: 'right'
		    });

		    $('#mnr-trigger-method2').jqxNumberInput({
		        width: '140px',
		        height: '20px',
		        decimal: 80,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic'
		    });

		    $("#mnr-trigger-param-method2").jqxComboBox(
			{
				theme: 'classic',
				source:["Condition Score","Distress Score"],
				width: 140,
				selectedIndex: 0,
				height: 20,
				autoDropDownHeight: true
			});

		    $('#min-seg-len-method2').jqxNumberInput({
		        width: '140px',
		        height: '20px',
		        decimal: 2,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic'
		    });

		    $('#max-seg-len-method2').jqxNumberInput({
		        width: '140px',
		        height: '20px',
		        decimal: 10,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic'
		    });
			
			$('#segmentation-window-ok').jqxButton({
	            theme: 'classic',
	            width: '120px'
	        });

	        $('#tabbar-segmentation-option').on('tabclick', function (event) {
                me.selected_tab = event.args.item;
            });
			
			$('#segmentation-window-ok').bind('click', function() {
				var params, method;
				// method 1
				if (me.selected_tab == 0) {
					var csthreshold = parseFloat($('#cs-threshold-method1').jqxNumberInput('getDecimal'));
					var dsthreshold = parseFloat($('#ds-threshold-method1').jqxNumberInput('getDecimal'));
					var minseglen = parseFloat($('#min-seg-len-method1').jqxNumberInput('getDecimal'));
					var maxseglen = parseFloat($('#max-seg-len-method1').jqxNumberInput('getDecimal'));
					var reliabilitylevel = parseFloat($('#reliability-level-method1').jqxNumberInput('getDecimal'));
					var zvalue = trimfloat(Math.abs(critz(reliabilitylevel/100)), ROUND_FLOAT);
					// console.log(zvalue);
					params = {
						csthreshold:csthreshold, 
						dsthreshold:dsthreshold, 
						minseglen:minseglen, 
						maxseglen:maxseglen,
						zvalue:zvalue,
						table:'1_segmented_pmis_aggregated_'+userid,
						doseg:true,
						method:1
					};	
					method = 1;
				}
				// method 2	
				else {
					var mnrtriggervalue = parseFloat($('#mnr-trigger-method2').jqxNumberInput('getDecimal'));
					var mnrtriggerparam = $("#mnr-trigger-param-method2").jqxComboBox('getSelectedItem').value;
					var minseglen = parseFloat($('#min-seg-len-method2').jqxNumberInput('getDecimal'));
					var maxseglen = parseFloat($('#max-seg-len-method2').jqxNumberInput('getDecimal'));
					params = {
						mnrtriggervalue:mnrtriggervalue,
						mnrtriggerparam:mnrtriggerparam,
						minseglen:minseglen,
						maxseglen:maxseglen,
						table:'2_segmented_pmis_aggregated_'+userid,
						doseg:true,
						method:2
					};
					method = 2;
				}
				util.addtab(2);
				
				// grid with detail
				$.when(
					//Sep.9 2015
					//grid.findAll({querytype:'etc', fiscalyears:'2011', template:util.viewtemplate.seg, params:params}),
					//grid.findAll({querytype:'etc', fiscalyears:'2011', template:util.viewtemplate.seg, params:{table:method+'_segmented_pmis_'+userid}})
					grid.findAll({querytype:'etc', fiscalyears:'2015', template:util.viewtemplate.seg, params:params}),
					grid.findAll({querytype:'etc', fiscalyears:'2015', template:util.viewtemplate.seg, params:{table:method+'_segmented_pmis_'+userid}})
				).then(function(responseMaster, responseDetail) {
					util.initmasterdetailgrid(responseMaster, responseDetail, util.viewtemplate.seg);
					$('#tabbar').jqxTabs('select', 2);
					$('#detail-container').css('display', 'block');		
					// update progress bar
					$('#progress-window').jqxWindow('close');
					$('#progress-window').on('close', function (event) { 

						setTimeout(function(){util.updateprogress(0, 'Initializing...');},500);
					}); 								
				}, function() {
					$('#progress-window').jqxWindow('close');
					alert("Error occured. Please try agiain.");
					clearTimeout(t);
				});
				
				util.updateprogress(0, 'Initializing...');
				$('#progress-window').jqxWindow('open');
				t = setTimeout(function() {util.updatestatus(3000);},3000);
			}); // end of button event
			
			$('#segmentation-window-cancel').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#segmentation-window').jqxWindow({
				theme: 'classic',
				width: '400px',
				isModal: false,
				modalOpacity: 0.1,
				okButton: $('#segmentation-window-ok'),
		        cancelButton: $('#segmentation-window-cancel'),
		        resizable: false,
		        initContent: function() {
		        	
				}
			});
			this.initdone = true;
		},
		initdone: false
	});
	return new segoptwindow(document);
});