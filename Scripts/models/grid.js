define([
	'require',
	'underscore',
	'jquery',
	'can',
	'util',
	'livequery'
], function (require, _, $, can, util) {
	var grid = can.Model({
		//TODO: USE FIXTURES PLUGIN TO SIMULATE AJAX
		findAll: function(params) {
			var template = params.template;
			return $.ajax({
				url: template.url,
				type: 'get',
				dataType: 'json',
				async: true,
				data: {
					querytype: params.querytype,
					fiscalyears: params.fiscalyears,
					params: params.params
				},
				beforeSend: function() {
					if (template.notshowloader == false || typeof template.notshowloader == "undefined") {
						if ($(template.tabid).length==0) {
							$(template.tabid).waitUntilExists(function() {
								util.showLoader(template);
						    });						
						}
						util.showLoader(template);
					}
				},
				complete: function() {
//					util.hideLoader(template);
				},
				success: function(data) {
					// we have column and fiscal years, and total number of records
					var models = {
						fullcolumns: data.fullcolumns || null,
						fiscalyears: data.fiscalyears,
						totalrecords: data.totalrecords
					};
					var datafields = [];
					var columns = [];
					
					// BUILD default column
					var defaultcolumns = template.defaultcolumns;
					var pinnedcolumns = template.pinnedcolumns;
					var k=0, l=0, hidden, pinned;
					
					for (var i=0 ; i<models.fullcolumns.length ; i++) {
						datafields.push({name:models.fullcolumns[i].Field});
						var type = models.fullcolumns[i].Type;
						if (type.indexOf('decimal')==0)
							_.extend(datafields[i], {type:'number'});
						else if (type.indexOf('TINYINT(1)')==0)
							_.extend(datafields[i], {type:'bool'});
						
						if (defaultcolumns == undefined || defaultcolumns[0] == '*' || i == defaultcolumns[k]) {
							hidden = false
							k++;
						}
						else
							hidden = true;
						if (pinnedcolumns == undefined) {
							pinned = false;
						}
						else if(i == pinnedcolumns[l]) {
							pinned = true;
							l++;
						}
						else {
							pinned = false;
						}
						columns.push({
							datafield:models.fullcolumns[i].Field, 
							text:models.fullcolumns[i].Field, 
							hidden:hidden, 
							pinned:pinned,
							width:150
						});
					}
					// Define source for grid
					var source =
		            {
		                datatype: "json",
		                datafields: datafields,
		                url: template.url,
		                data: {
		                	querytype: 'grid',
		                	params: params.params,
		                	fiscalyears: params.fiscalyears,
		                },	    
		                totalrecords: models.totalrecords,
		                pagesize: 1000
		            };
					if (typeof template.sort== "undefined") {
						source.sort = function() {
		                	$(template.gridid).jqxGrid('updatebounddata');
		                };
					}
					// set additional info
		            data.dataadapter = new $.jqx.dataAdapter(source);
		            data.columns = columns;
		            data.datafields = datafields;
		            data.defaultcolumns = defaultcolumns;
		            data.source = source;
		            data.template = template;
				}
			});
		},
		findOne: 'GET ' + require.toUrl('../../Services/json/county_label.json'),
		create:  function(params) {
			var template = params.template;
			return $.ajax({
				url: template.url,
				type: 'post',
				dataType: 'json',
				data: {
					querytype: params.querytype,
					params: params.params
				}
			});
		},
		update:  'PUT ' + require.toUrl('../../Services/grid/{id}.json'),
		destroy: 'DELETE ' + require.toUrl('../../Services/grid/{id}.json')
	}, {
		
	});

	return grid;
});