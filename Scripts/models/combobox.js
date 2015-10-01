define([
	'require',
	'jquery',
	'can',
	'util',
	'livequery'
], function (require, $, can, util) {
	var combobox = can.Model({
		//TODO: USE FIXTURES PLUGIN TO SIMULATE AJAX
		findAll: function(params) {
			return $.ajax({
				url: params.params.url,
				type: 'get',
				dataType: 'json',
				async: true,
				data: params.params.data,
				success: function(data) {
					
					var datafields = [];
					for (var i=0 ; i<params.params.datafields.length ; i++) {
						datafields.push({name:params.params.datafields[i]});
					}
					
					// Define source for grid
					var source =
		            {
		                datatype: "json",
		                datafields: datafields,
		                localdata: data.comboboxdata
		            };
					
					// set additional info
		            data.dataadapter = new $.jqx.dataAdapter(source);
		            data.source = source;
				}
			});
		},
		findOne: 'GET ' + require.toUrl('../../Services/json/county_label.json'),
		create:  'POST ' + require.toUrl('../../Services/grid/all.json'),
		update:  'PUT ' + require.toUrl('../../Services/grid/{id}.json'),
		destroy: 'DELETE ' + require.toUrl('../../Services/grid/{id}.json')
	}, {
		
	});

	return combobox;
});