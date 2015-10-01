define([
	'jquery',
	'underscore',
	'can',
	'models/grid',
	'models/combobox',
	'util',
	'windows/seg.opt.window',
	'windows/assess.opt.window',
	'windows/analysis.window',
	'windows/data.clean.window',
	'windows/report.window',
    'windows/pdf.windows',
	'controllers/ui',
	'livequery',
	'jqx'
], function ($, _, can, grid, combobox, util, segopt, assessopt, analysisopt, datacleanopt, reportwin, pdfwin) {
	var menu = can.Control({
		init: function (element, options) {
			var me = this;
			me.bindClick('#menu-seg-opt-window', '#segmentation-window', segopt);
			me.bindClick('#menu-assess-opt-window', '#assessment-window', assessopt);
			me.bindClick('#menu-analysis-window', '#analysis-window', analysisopt);
			me.bindClick('#menu-data-clean-window', '#data-clean-window', datacleanopt);
			me.bindClick('#menu-report-window', '#report-window', reportwin);
			me.bindClick('#menu-generate-pdf', '#pdf-windows', pdfwin);
		},
		
		bindClick: function(menuid, windowid, handler) {
			$(menuid).click(function() {
				if (handler.initdone)
					//window.alert("hello");
					$(windowid).jqxWindow('open');
				else
					//window.alert("hello");
					handler.initwindow();
				$(windowid).focus();
			});
		}
	});

	new menu(document);
});