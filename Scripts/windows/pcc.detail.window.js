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
			var view = can.view(require.toUrl('views/window/pcc.detail.window.ejs'));
			$('#window').append(view);				
		},
		initwindow: function() {
			var me = this;
			$('#ratio-csds').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimal: 21,
		        decimalDigits: 0,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic',
		        symbol: '%',
		        symbolPosition: 'right'
		    });
			$('#ratio-csds').bind('valuechanged', function (event) {
				me.updateTotal();
            }); 
			
			$('#ratio-ride').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimal: 6,
		        decimalDigits: 0,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic',
		        symbol: '%',
		        symbolPosition: 'right'
		    });
			
			$('#ratio-ride').bind('valuechanged', function (event) {
				me.updateTotal();
            }); 
			
			$('#ratio-rod').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimal: 14,
		        decimalDigits: 0,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic',
		        symbol: '%',
		        symbolPosition: 'right'
		    });
			
			$('#ratio-rod').bind('valuechanged', function (event) {
				me.updateTotal();
            }); 
			
			$('#ratio-skid').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimal: 16,
		        decimalDigits: 0,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic',
		        symbol: '%',
		        symbolPosition: 'right'
		    });
			
			$('#ratio-skid').bind('valuechanged', function (event) {
				me.updateTotal();
            }); 
			
			$('#ratio-sci').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimal: 11,
		        decimalDigits: 0,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic',
		        symbol: '%',
		        symbolPosition: 'right'
		    });
			
			$('#ratio-sci').bind('valuechanged', function (event) {
				me.updateTotal();
            }); 
			
			$('#ratio-va').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimal: 32,
		        decimalDigits: 0,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic',
		        symbol: '%',
		        symbolPosition: 'right'
		    });
			
			$('#ratio-va').bind('valuechanged', function (event) {
				me.updateTotal();
            }); 
			
			$('#ratio-pcc-total').jqxInput({
				width: '80px',
		        height: '20px'
		    });
			
			$('#ratio-pcc-total').val('100%');
			
			$('#pcc-detail-window-ok').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });

	        $('#pcc-detail-window-restore').jqxButton({
	            theme: 'classic',
	            width: '90px'
	        });
			
			$('#pcc-detail-window-ok').bind('click', function() {
				
			}); // end of button event
			
			$('#pcc-detail-window-cancel').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#pcc-detail-window').jqxWindow({
				theme: 'classic',
				width: '320px',
				height: '285px',
				maxHeight: '700px',
				isModal: false,
				modalOpacity: 0.1,
				okButton: $('#pcc-detail-window-ok'),
		        cancelButton: $('#pcc-detail-window-cancel'),
		        resizable: false
			});			
			
			$('#pcc-form').jqxValidator({
		         rules: [
		                    { 
		                    	input: '#ratio-pcc-total', 
		                    	message: 'Total should be 100%', 
		                    	action: 'valuechanged',
		                    	rule: function() {
		                    		var value = $('#ratio-pcc-total').val();
		                    		value = value.substring(0, value.length-1);
			                    	var total = parseInt(value);
			         			    var result = (total == 100);
			         			    return result;
			                    } 
		                    }
		                ],
		         theme: 'classic',
		         scroll: false
			 });
			
			this.initdone = true;
		},
		initdone: false,
		updateTotal: function() {
			var sum = parseInt($('#ratio-csds').jqxNumberInput('val')) + parseInt($('#ratio-ride').jqxNumberInput('val'))
			+parseInt($('#ratio-rod').jqxNumberInput('val'))+parseInt($('#ratio-skid').jqxNumberInput('val'))+
			parseInt($('#ratio-sci').jqxNumberInput('val'))+parseInt($('#ratio-va').jqxNumberInput('val'));
			$('#ratio-pcc-total').val(sum+'%');
			$('#pcc-form').jqxValidator('validate');
		}
	});
	return new window(document);
});