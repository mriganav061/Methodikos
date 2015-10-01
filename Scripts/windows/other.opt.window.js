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
			var view = can.view(require.toUrl('views/window/other.opt.window.ejs'));
			$('#window').append(view);				
		},
		initwindow: function() {
			$('#other-param-window').jqxWindow({
				theme: 'classic',
				width: '320px',
				height: '305px',
				maxHeight: '700px',
				isModal: false,
				modalOpacity: 0.1,
				initContent: function() {
					$('#current-year').jqxNumberInput({
				        width: '120px',
				        height: '20px',
				        decimal: baseyear,
				        decimalDigits: 0,
				        disabled: true,
				        min: 1960,
				        max: 2015,
				        validationMessage: 'Please enter a valid value.',
				        inputMode: 'simple',
				        spinMode: 'simple',
				        spinButtons: true,
				        spinButtonsStep: 1,
				        theme: 'classic'
				    });
					
					$('#year1-budget').jqxNumberInput({
				        width: '120px',
				        height: '20px',
				        decimal: 18,
				        decimalDigits: 1,
				        inputMode: 'simple',
				        spinMode: 'simple',
				        spinButtons: true,
				        spinButtonsStep: 1,
				        symbol: '$',
				        symbolPosition: 'left',
				        theme: 'classic'
				    });
					
					$('#year2-budget').jqxNumberInput({
				        width: '120px',
				        height: '20px',
				        decimal: 18,
				        decimalDigits: 1,
				        inputMode: 'simple',
				        spinMode: 'simple',
				        spinButtons: true,
				        spinButtonsStep: 1,
				        symbol: '$',
				        symbolPosition: 'left',
				        theme: 'classic'
				    });
					
					$('#year3-budget').jqxNumberInput({
				        width: '120px',
				        height: '20px',
				        decimal: 18,
				        decimalDigits: 1,
				        inputMode: 'simple',
				        spinMode: 'simple',
				        spinButtons: true,
				        spinButtonsStep: 1,
				        symbol: '$',
				        symbolPosition: 'left',
				        theme: 'classic'
				    });
					
					$('#year4-budget').jqxNumberInput({
				        width: '120px',
				        height: '20px',
				        decimal: 18,
				        decimalDigits: 1,
				        inputMode: 'simple',
				        spinMode: 'simple',
				        spinButtons: true,
				        spinButtonsStep: 1,
				        symbol: '$',
				        symbolPosition: 'left',
				        theme: 'classic'
				    });
					
					$('#discount-rate').jqxNumberInput({
				        width: '120px',
				        height: '20px',
				        decimal: 3,
				        inputMode: 'simple',
				        spinMode: 'simple',
				        spinButtons: true,
				        spinButtonsStep: 1,
				        symbol: '%',
				        symbolPosition: 'right',
				        theme: 'classic'
				    });
					
					$('#aadt-growth-rate').jqxNumberInput({
				        width: '120px',
				        height: '20px',
				        decimal: 4,
				        inputMode: 'simple',
				        spinMode: 'simple',
				        spinButtons: true,
				        spinButtonsStep: 1,
				        symbol: '%',
				        symbolPosition: 'right',
				        theme: 'classic'
				    });
					
					$('#other-param-window-ok').jqxButton({
			            theme: 'classic',
			            width: '80px'
			        });

			        $('#other-param-window-restore').jqxButton({
			            theme: 'classic',
			            width: '90px'
			        });
					
					// $('#other-param-window-ok').bind('click', function() {
						
					// }); // end of button event
					
					$('#other-param-window-cancel').jqxButton({
			            theme: 'classic',
			            width: '80px'
			        });
				},
				okButton: $('#other-param-window-ok'),
		        cancelButton: $('#other-param-window-cancel'),
		        resizable: false
			});			
			this.initdone = true;
		},
		initdone: false
	});
	return new window(document);
});