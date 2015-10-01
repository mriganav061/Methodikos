define([
	'jquery',
	'can',
	'jqx',
	'moment',	
	'util',
	'gmap',
], function ($, can, jqx, moment, util) {

	var UI = can.Control({
		
		// Initialize user interface
		init: function (element, options) {
			// attach menu
			var view = can.view(require.toUrl('views/ui/menu.ejs'));
			$('#nav').html(view);
			$("#menu").jqxMenu({ width: '100%', height: '30px', theme: 'ui-darkness', showTopLevelArrows: true,
			animationShowDuration: 0, animationHideDuration: 0, animationShowDelay: 0 });

			$('#menu').on('itemclick', function (event)
			{
			    // get the clicked LI element.
			    var element = event.args;
			});
			
			// attach splitter
			view = can.view(require.toUrl('views/ui/main.ejs'));
			$('#main').html(view);
			$('#splitter').jqxSplitter({ 
				width: '100%', 
				theme: 'classic',
				splitBarSize: '10px',
				orientation: 'horizontal', 
				height:1100, 
				panels: [{ 
					size: 450, 
					collapsible:true, 
					collapsed:false 
				}, {
					size: 650, 
					collapsible:false, 
					collapsed:false
				}] 
			});
			
			// attach tabbar
			view = can.view(require.toUrl('views/ui/tabbar.ejs'));
			$('#top-pane').html(view);
			$('#tabbar').jqxTabs({ theme: 'classic', height:'100%', width: '100%', scrollable:false, animationType: 'fade' });
			
			$('#tabbar').on('tabclick', function (event) { 
				var tabindex = event.args.item;
				if (tabindex == 2) {
					$('#detail-container').css('display', 'block');
				}
				else {
					$('#detail-container').css('display', 'none');
				}
			});

			
			
			// attach map canvas
			
			view = can.view(require.toUrl('views/ui/gmap.ejs'));
			$('#bottom-pane').html(view);
			
			// splitter event
			$('#splitter').bind('resize', function(event) {
				resizeTable(event.args.panels[0].size);
				resizeMap(event.args.panels[1].size);
			});
			
			$('#splitter').bind('expanded', function(event) {
				resizeTable(event.args.panels[0].size);
				resizeMap(event.args.panels[1].size);
			});
			
			$('#splitter').bind('collapsed', function(event) {
				resizeMap(event.args.panels[1].size);
			});
			
			function resizeTable(height) {
				$("#tabbar > div:nth-child(2) > div > div").each(function(index) {
					var gridid = $(this).attr('id');
					$('#'+gridid).jqxGrid({height : height-40});
					// $('#'+gridid).jqxGrid('updatebounddata', 'cells');
				});
			}
			
			function resizeMap(height) {
				gmap.resized(height);
			}
			
			// init common dialog
			// save as dialog
			view = can.view(require.toUrl('views/window/saveas.window.ejs'));
			$('#window').append(view);
			$("#saveas-input").jqxInput({
				height: 25, 
				width: 280, 
				minLength: 1,
				placeHolder: 'e.g. mysetting',
				theme: 'classic'
			});
			$('#saveas-window-ok').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			$('#saveas-window-cancel').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			$('#saveas-window').jqxWindow({
				theme: 'classic',
				width: '400px',
				height: '120px',
				isModal: true,
				autoOpen: false,
				okButton: $('#saveasn-window-ok'),
		        cancelButton: $('#saveas-window-cancel'),
		        resizable: false
			});
			$('#saveas-form').jqxValidator({
				rules: [
				       {input:'#saveas-input', message: 'Name is required', rule: 'required'}
				],
				theme: 'classic',
				scroll: false
			});
			$('#saveas-window-ok').on('click', function() {
				 $('#saveas-form').jqxValidator('validate');
			});
			
			
			// progress bar window
			view = can.view(require.toUrl('views/window/progress.window.ejs'));
			$('#window').append(view);			
			$('#progress-window').jqxWindow({
				theme: 'classic',
				width: '315px',
				height: 'auto', //'115px',
				isModal: true,
		        resizable: false,
		        showCloseButton: false,
		        autoOpen: false
			});
			$("#progressbar").jqxProgressBar({ 
				animationDuration:200,
				width: 303, 
				height: 30,
				max: 100,
				value: 0, 
				theme: 'fresh',
				showText: true
			});
			util.updateprogress(0, 'Initializing...');
		}
	});

	return new UI(document);
});