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
			var view = can.view(require.toUrl('views/window/gain.rating.window.ejs'));
			$('#window').append(view);
			// this.initwindow();
		},
		initwindow: function() {
			$('#pm-rs-increaseby').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimalDigits: 1,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        theme: 'classic',
		        decimal: 0.5
		    });

		    $('#pm-ds-resetto').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimalDigits: 1,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        theme: 'classic',
		        decimal: 100
		    });

		    $('#lr-rs-increaseby').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimalDigits: 1,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        theme: 'classic',
		        decimal: 1.5
		    });

		    $('#lr-ds-resetto').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimalDigits: 1,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        theme: 'classic',
		        decimal: 100
		    });

		    $('#mr-rs-resetto').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimalDigits: 1,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        theme: 'classic',
		        decimal: 4.8
		    });

		    $('#mr-ds-resetto').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimalDigits: 1,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        theme: 'classic',
		        decimal: 100
		    });

		    $('#hr-rs-resetto').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimalDigits: 1,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        theme: 'classic',
		        decimal: 4.8
		    });

		    $('#hr-ds-resetto').jqxNumberInput({
		        width: '80px',
		        height: '20px',
		        decimalDigits: 1,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        theme: 'classic',
		        decimal: 100
		    });
			/*
			var rehap = ['pm', 'lr', 'mr', 'hr'];
			var score = ['ds', 'rs'];
			var rsdefaults = [0.5, 1.5, 4.8, 4.8];
			var dsdefaults = [0, 0, 0, 0];
			for (var i=0 ; i<rehap.length ; i++) {
				for (var j=0 ; j<score.length ; j++) {
					$('#'+rehap[i]+'-'+score[j]+'-increaseby').jqxNumberInput({
				        width: '80px',
				        height: '20px',
				        decimalDigits: 1,
				        inputMode: 'simple',
				        spinMode: 'simple',
				        spinButtons: true,
				        theme: 'classic'
				    });
					if (score[j] == 'rs' && rehap[i] == 'pm') {
						$('#'+rehap[i]+'-'+score[j]+'-increaseby').jqxNumberInput('setDecimal', 0.5);
						$('#'+rehap[i]+'-'+score[j]+'-increaseby').jqxNumberInput({
							spinMode: 'none',
							readOnly: true,
							spinButtons: false
						});
					}
					else if (score[j] == 'rs' && rehap[i] == 'lr') {
						$('#'+rehap[i]+'-'+score[j]+'-increaseby').jqxNumberInput('setDecimal', 1.5);
						$('#'+rehap[i]+'-'+score[j]+'-increaseby').jqxNumberInput({
							spinMode: 'none',
							readOnly: true,
							spinButtons: false
						});
					}
					$('#'+rehap[i]+'-'+score[j]+'-resetto').jqxInput({
						width: '80px',
				        height: '20px'
				    });
					if (score[j] == 'ds')
						$('#'+rehap[i]+'-'+score[j]+'-resetto').val(100);
					else if (score[j] == 'rs' && (rehap[i] == 'mr' || rehap[i] == 'hr'))
						$('#'+rehap[i]+'-'+score[j]+'-resetto').val(4.8);
				}
			}
			*/

			$('#gain-rating-window-restore').jqxButton({
	            theme: 'classic',
	            width: '90px'
	        });
			
			$('#gain-rating-window-ok').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#gain-rating-window-ok').bind('click', function() {
				
			}); // end of button event
			
			$('#gain-rating-window-cancel').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#gain-rating-window').jqxWindow({
				theme: 'classic',
				width: '430px',
				height: '455px',
				maxHeight: '700px',
				isModal: false,
//				autoOpen: true,
				okButton: $('#gain-rating-window-ok'),
		        cancelButton: $('#gain-rating-window-cancel'),
		        resizable: false
			});			
			this.initdone = true;
		},
		initdone: false
	});
	return new window(document);
});