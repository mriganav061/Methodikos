define([
	'jquery',
	'can',
	'moment',
	'models/grid',
	'util',
	'jqx'
], function ($, can, moment, grid, util) {
	var PMIS = can.Control({
		init: function (element, options) {
			var params = {table:'pmis_condition_summary_'+district};
			grid.findAll({querytype:'etc', fiscalyears:baseyear, template:util.viewtemplate.pmis, params:params}, function(response) {
				util.initgrid(response, util.viewtemplate.pmis, grid, params);
			});
		}
	});
	
	var DCIS = can.Control({
		init: function (element, options) {
			var params = {table:'dcis_project_information'};
			grid.findAll({querytype:'etc', fiscalyears:baseyear, template:util.viewtemplate.dcis, params:params}, function(response) {
				util.initgrid(response, util.viewtemplate.dcis, grid, params);
			});
			
		}
	});
	
	var segmented = can.Control({
		init: function (element, options) {			
			// grid with detail
			$.when(
				grid.findAll({querytype:'etc', fiscalyears:baseyear, template:util.viewtemplate.seg, params:{table:'segmented_pmis_aggregated_'+userid, loadseg:true}}),
				grid.findAll({querytype:'etc', fiscalyears:baseyear, template:util.viewtemplate.seg, params:{table:'segmented_pmis_'+userid, loadseg:true}})
			).then(function(responseMaster, responseDetail) {
				if (responseMaster.exist && responseDetail.exist) {
					// 33, 34 cell format change
					_.extend(responseMaster.dataadapter._source.datafields[33], {type: 'float'});
					_.extend(responseMaster.dataadapter._source.datafields[34], {type: 'float'});
					_.extend(responseMaster.columns[33], {cellsformat:'f1'});
					_.extend(responseMaster.columns[34], {cellsformat:'f1'});
					util.addtab(2);
					util.initmasterdetailgrid(responseMaster, responseDetail, util.viewtemplate.seg);
				}
			});
		}
	});

	new PMIS(document);
	new segmented(document);
	new DCIS(document);
});