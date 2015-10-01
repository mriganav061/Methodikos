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
			var view = can.view(require.toUrl('views/window/ctv.detail.window.ejs'));
			$('#window').append(view);				
		},
		initwindow: function() {
			var me = this;
			$('#ratio-aadt').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimal: 30,
		        decimalDigits: 0,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic',
		        symbol: '%',
		        symbolPosition: 'right'
		    });
			
			$('#ratio-taadt').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimal: 70,
		        decimalDigits: 0,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic',
		        symbol: '%',
		        symbolPosition: 'right'
		    });
			
			$('#ratio-ctv-total').jqxInput({
				width: '80px',
		        height: '20px'
		    });
			
			$('#ratio-ctv-total').val('100%');
			
			$('#ratio-aadt').bind('valuechanged', function (event) {
				me.updateTotal();
            }); 
			
			$('#ratio-taadt').bind('valuechanged', function (event) {
				me.updateTotal();
            });
			
			$('#ctv-detail-window-ok').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });

	        $('#ctv-detail-window-restore').jqxButton({
	            theme: 'classic',
	            width: '90px'
	        });
			
			$('#ctv-detail-window-ok').bind('click', function() {
				
			}); // end of button event
			
			$('#ctv-detail-window-cancel').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#ctv-detail-window').jqxWindow({
				theme: 'classic',
				width: '235px',
				height: '175px',
				maxHeight: '700px',
				isModal: false,
				modalOpacity: 0.1,
				okButton: $('#ctv-detail-window-ok'),
		        cancelButton: $('#ctv-detail-window-cancel'),
		        resizable: false
			});		

			$('#ctv-form').jqxValidator({
		         rules: [
		                    { 
		                    	input: '#ratio-ctv-total', 
		                    	message: 'Total should be 100%', 
		                    	action: 'valuechanged',
		                    	rule: function() {
		                    		var value = $('#ratio-ctv-total').val();
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
			var ratioaadt = parseInt($('#ratio-aadt').jqxNumberInput('val'));
			var ratiotaadt = parseInt($('#ratio-taadt').jqxNumberInput('val'));
			var sum = ratioaadt + ratiotaadt;
			$('#ratio-ctv-total').val(sum+'%');
			$('#ctv-form').jqxValidator('validate');
		}
	});
	return new window(document);
});