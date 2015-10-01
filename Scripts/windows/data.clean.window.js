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
			var view = can.view(require.toUrl('views/window/data.clean.window.ejs'));
			$('#window').append(view);				
		},
		initwindow: function() {
			$('#min-cs-threshold').jqxNumberInput({
		        width: '140px',
		        height: '20px',
		        decimal: 20,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic'
		    });
			
			$('#min-uride-threshold').jqxNumberInput({
		        width: '140px',
		        height: '20px',
		        decimal: 0.5,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic'
		    });
			
			$('#reset-ride').jqxNumberInput({
		        width: '140px',
		        height: '20px',
		        decimal: 4.8,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic'
		    });
			
			$('#data-clean-window-ok').jqxButton({
	            theme: 'classic',
	            width: '120px'
	        });
			
			$('#data-clean-window-ok').bind('click', function() {
				$.ajax({
				  type: "POST",
				  url: '../../phps/datacleaning.php',
				  data: {
					  mincs: $('#min-cs-threshold').jqxNumberInput('getDecimal'),
					  minuride: $('#min-uride-threshold').jqxNumberInput('getDecimal'),
					  resetride: $('#reset-ride').jqxNumberInput('getDecimal')
				  },				  
				  dataType: 'json'
				}).done(function( data ) {
					  // update progress bar
					$('#progress-window').jqxWindow('close');
					$('#progress-window').on('close', function (event) { 						
						setTimeout(function(){util.updateprogress(0, 'Initializing...');},500);
					}); 
				}).fail(function(data) {
					$('#progress-window').jqxWindow('close');
	            	alert("Error occured. Please try agiain.");
	            	clearTimeout(t);
				});
				util.updateprogress(0, 'Initializing...');
				$('#progress-window').jqxWindow('open');
				t = setTimeout(function() {util.updatestatus(3000);},5000);
			}); // end of button event
			
			$('#data-clean-window-cancel').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#data-clean-window').jqxWindow({
				theme: 'classic',
				width: '420px',
				height: '175px',
				maxHeight: '700px',
				isModal: false,
				modalOpacity: 0.1,
				okButton: $('#data-clean-window-ok'),
		        cancelButton: $('#data-clean-window-cancel'),
		        resizable: false
			});			
			this.initdone = true;
		},
		initdone: false
	});
	return new window(document);
});