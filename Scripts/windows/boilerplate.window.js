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
			var view = can.view(require.toUrl('views/window/.ejs'));
			$('#window').append(view);				
		},
		initwindow: function() {
			
			$('#ok-button').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#ok-button').bind('click', function() {
				
			}); // end of button event
			
			$('#cancel-button').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#windowid').jqxWindow({
				theme: 'classic',
				width: '470px',
				height: '460px',
				maxHeight: '700px',
				isModal: false,
				okButton: $('#ok-button'),
		        cancelButton: $('#cancel-button'),
		        resizable: false
			});			
			this.initdone = true;
		},
		initdone: false
	});
	return new window(document);
});