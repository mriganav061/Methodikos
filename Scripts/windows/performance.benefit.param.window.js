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
			var view = can.view(require.toUrl('views/window/performance.benefit.param.window.ejs'));
			$('#window').append(view);	
			$("#mnr-trigger-param").jqxComboBox(
			{
				theme: 'classic',
				source:["Condition Score","Distress Score"],
				width: 140,
				selectedIndex: 0,
				height: 20,
				autoDropDownHeight: true
			});
			
			$('#mnr-trigger-value').jqxNumberInput({
		        width: '140px',
		        height: '20px',
		        decimal: 80,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic'
		    });
			this.checkseg();
		},
		checkseg: function() {
			$.ajax({
				url: '../../Services/grid/pmis.php',
				type: 'get',
				dataType: 'json',
				async: true,
				data: {
					querytype: 'seg_table'
				}
			}).done(function (response) {
				if (response.EXIST && response.SEG_TYPE==2) {
					$("#mnr-trigger-param").jqxComboBox({
						disabled:true
					});					
					$('#mnr-trigger-value').jqxNumberInput({
						disabled: true
				    });
				}
				else {
					$("#mnr-trigger-param").jqxComboBox({
						disabled:false
					});					
					$('#mnr-trigger-value').jqxNumberInput({
						disabled: false
				    });
				}
			});
		},
		initwindow: function() {
			var me = this;
			this.checkseg();
            $('#benefit-cs-threshold').jqxNumberInput({
		        width: '140px',
		        height: '20px',
		        decimal: 70,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic'
		    });

		    



		    $('#pm-viability-value').jqxNumberInput({
		        width: '140px',
		        height: '20px',
		        decimal: 50,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic'
		    });

		    $('#lr-viability-value').jqxNumberInput({
		        width: '140px',
		        height: '20px',
		        decimal: 35,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic'
		    });
			
			$('#performance-benefit-opt-window-ok').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#performance-benefit-opt-window-ok').bind('click', function() {
				me.checkseg();
			}); // end of button event
			
			$('#performance-benefit-opt-window-cancel').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });

	        $('#performance-benefit-opt-window-restore').jqxButton({
	            theme: 'classic',
	            width: '90px'
	        });

			$('#performance-benefit-opt-window').on('open', function (event) {
				me.checkseg();
			})	

			$('#performance-benefit-opt-window').jqxWindow({
				theme: 'classic',
				width: '440px',
				height: '230px',
				maxHeight: '700px',
				isModal: false,
				okButton: $('#performance-benefit-opt-window-ok'),
		        cancelButton: $('#performance-benefit-opt-window-cancel'),
		        resizable: false
			});		

			
			this.initdone = true;
		},
		initdone: false
	});
	return new window(document);
});