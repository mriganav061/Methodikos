define([
	'jquery',
	'underscore',
	'can',
	'models/grid',
	'models/combobox',
	'util',
	'windows/pcc.detail.window',
	'windows/ctv.detail.window',
	'jqx'
], function ($, _, can, grid, combobox, util, pccdetail, ctvdetail) {
	var ahpopt = can.Control({
		init: function (element, options) {
			var view = can.view(require.toUrl('views/window/ahp.window.ejs'));
			$('#window').append(view);
		},
		initwindow: function() {
			var me = this;
			$('#btn-pcc-detail-window').click(function() {
				if (pccdetail.initdone)
					$('#pcc-detail-window').jqxWindow('open');
				else {
					pccdetail.initwindow();
					me.pccinitdone = true;
				}
				$('#pcc-detail-window').focus();
				$('#pcc-detail-window').jqxWindow('bringToFront');
			});

			$('#btn-ctv-detail-window').click(function() {
				if (ctvdetail.initdone)
					$('#ctv-detail-window').jqxWindow('open');
				else {
					ctvdetail.initwindow();
					me.ctvinitdone = true;
				}
				$('#ctv-detail-window').focus();
				$('#ctv-detail-window').jqxWindow('bringToFront');
			});
			
			$('#ratio-pcc').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimal: 26,
		        decimalDigits: 0,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic',
		        symbol: '%',
		        symbolPosition: 'right'
		    });
			
			$('#ratio-ctv').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimal: 19,
		        decimalDigits: 0,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic',
		        symbol: '%',
		        symbolPosition: 'right'
		    });
			
			$('#ratio-ic').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimal: 22,
		        decimalDigits: 0,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic',
		        symbol: '%',
		        symbolPosition: 'right'
		    });
			
			$('#ratio-ltpb').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimal: 19,
		        decimalDigits: 0,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic',
		        symbol: '%',
		        symbolPosition: 'right'
		    });
			
			$('#ratio-lcc').jqxNumberInput({
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
			
			$('#ratio-total').jqxInput({
				width: '80px',
		        height: '20px'
		    });
			
			$('#ratio-total').val('100%');
			
			$('#ratio-pcc').bind('valuechanged', function (event) {
				me.updateTotal();
            }); 
			
			$('#ratio-ctv').bind('valuechanged', function (event) {
				me.updateTotal();
            }); 
			
			$('#ratio-ic').bind('valuechanged', function (event) {
				me.updateTotal();
            }); 
			
			$('#ratio-ltpb').bind('valuechanged', function (event) {
				me.updateTotal();
            }); 
			
			$('#ratio-lcc').bind('valuechanged', function (event) {
				me.updateTotal();
            }); 
			
			$('#ahp-window-ok').bind('click', function() {
				
			}); // end of button event
			
			$('#ahp-window-cancel').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });

	        $('#ahp-window-restore').jqxButton({
	            theme: 'classic',
	            width: '90px'
	        });
			
			$('#ahp-window').jqxWindow({
				theme: 'classic',
				width: '320px',
				height: '260px',
				maxHeight: '700px',
				isModal: false,
				okButton: $('#ahp-window-ok'),
		        cancelButton: $('#ahp-window-cancel'),
		        resizable: false
			});
			
			$('#ahp-window-ok').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#ahp-form').jqxValidator({
		         rules: [
		                    { 
		                    	input: '#ratio-total', 
		                    	message: 'Total should be 100%', 
		                    	action: 'valuechanged',
		                    	rule: function() {
		                    		var value = $('#ratio-total').val();
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
		pccinitdone: false,
		ctvinitdone: false,
		updateTotal: function() {
			var ratiopcc = parseInt($('#ratio-pcc').jqxNumberInput('val'));
			var ratioctv = parseInt($('#ratio-ctv').jqxNumberInput('val'));
			var ratioic = parseInt($('#ratio-ic').jqxNumberInput('val'));
			var ratioltpb = parseInt($('#ratio-ltpb').jqxNumberInput('val'));
			var ratiolcc = parseInt($('#ratio-lcc').jqxNumberInput('val'));
			var sum = ratiopcc + ratioctv + ratioic + ratioltpb + ratiolcc;
			$('#ratio-total').val(sum+'%');
			$('#ahp-form').jqxValidator('validate');
		}
	});
	return new ahpopt(document);
});