define([
	'require',
	'jquery',
	'underscore'
], function (require, $, _) {
	var util = {};
	
	util.showLoader = function (template) {
		$(template.tabbar).jqxTabs({ height:'100%' });
		$(template.gridid).css('display', 'none');
		$(template.tabid).addClass('div_loader');
	};
	
	util.hideLoader = function (template) {
		$(template.tabbar).jqxTabs({ height:'auto' });
		$(template.tabid).removeClass('div_loader');
		$(template.gridid).css('display', 'block');
	};
		
	util.initgrid = function (response, template, grid, params) {
		var self = this;
		//BUILD VIEW MODEL
		var models = {
			dataadapter: response.dataadapter,
			fullcolumns: response.fullcolumns,
			fiscalyears: response.fiscalyears,
			totalrecords: response.totalrecords,
			columns: response.columns,
			defaultcolumns: response.defaultcolumns
		}
		//PASS RESULTS TO VIEW
		var view = can.view(require.toUrl(template.grid));

		//RENDER IN SPECIFIED CONTENT AREA
		$(template.tabid).html(view);
		
		$(template.gridid).bind('bindingcomplete', function(event) {
			self.hideLoader(template);
		});
		
		// define grid
		$(template.gridid).jqxGrid({
	        width: 1192,
	        height: 410,
	        source: models.dataadapter,
	        theme: 'classic',
	        filterable: true,
	        sortable: true,
	        columnsresize: true,
	        pageable: true,
	        autoshowfiltericon: true,
	        pagesizeoptions: ['1000', '5000', '10000'],
	        virtualmode: true,
	        rendergridrows: function () {
                return models.dataadapter.records;
            },
	        showtoolbar: true,
	        columns: models.columns,
	        rendertoolbar: function (toolbar) {
                var me = this;
                var toolbarhtml = can.view(require.toUrl(template.toolbar));
                toolbar.append(toolbarhtml);
                
                // Create a jqxDropDownList
                $(template.columnid).jqxDropDownList({ 
        			checkboxes: true, 
        			source: models.fullcolumns, 
        			displayMember: "Field", 
        			valueMember: "Field", 
        			width: 250, 
        			height: 25, 
        			theme: 'classic',
        			selectionRenderer: function (htmlString) {
        		        return "Choose Columns...";
        		    }
				});
                
                // bind to the checkChange event.
                $(template.columnid).bind('checkChange', function (event) {
                	if(event.args.checked)
                		$(template.gridid).jqxGrid('showcolumn', event.args.value);
                	else
                		$(template.gridid).jqxGrid('hidecolumn', event.args.value);
                });
                
                // Check defaultcolumns
                for (var i=0 ; i<models.defaultcolumns.length ; i++)
                	$(template.columnid).jqxDropDownList('checkIndex', models.defaultcolumns[i]);
                
                $(template.yearsid).jqxDropDownList({ 
        			checkboxes: true, 
        			source: models.fiscalyears, 
        			displayMember: "FISCAL_YEAR", 
        			valueMember: "FISCAL_YEAR", 
        			width: 150, 
        			height: 25, 
        			theme: 'classic',
        			selectionRenderer: function (htmlString) {
        		        return "Choose Fiscal Years...";
        		    }
				});
                
                // check default year
                $(template.yearsid).jqxDropDownList('checkIndex', 0);
                
                // Add button
                $(template.reloadid).jqxButton({ width: '100', theme: 'classic' });
                
                // Event binding
                $(template.reloadid).bind('click', function() {
                	var items = $(template.yearsid).jqxDropDownList('getCheckedItems');
        			var fiscalyears = "";
        			for (var i=0 ; i<items.length ; i++) 
        				fiscalyears +=  "'" + items[i].value + "',";
        			fiscalyears = fiscalyears.slice(0, fiscalyears.length - 1);
        			
        			// Reload grid
        			grid.findAll({querytype:'etc', fiscalyears:fiscalyears, template:template, params:params}, function(response) {
            			// reload grid
            			$(template.gridid).jqxGrid({
            				source: response.dataadapter,
        			        rendergridrows: function () {
        	                    return response.dataadapter.records;
        	                },	
        			        columns: response.columns
            			});
            			$(template.gridid).jqxGrid('showloadelement');
            			
        			});
        			
                });
                // End Of Event binding
            }
            // End Of toolbar
	    });
		// End Of grid
	}
	
	util.initmasterdetailgrid = function (responseMaster, responseDetail, template) {
		var self = this;
		
		$(template.gridid).bind('bindingcomplete', function(event) {
			self.hideLoader(template);
		});
		// master
		$(template.gridid).jqxGrid(
        {
        	width: 1192,
            height: $('#splitter').jqxSplitter('panels')[0].size - 40,
            source: responseMaster.dataadapter,
            theme: 'classic',
	        filterable: true,
	        sortable: true,
	        pageable: true,
	        autoshowfiltericon: true,
	        columnsresize: true,
	        pagesizeoptions: ['1000', '5000', '10000'],
	        virtualmode: true,
	        rendergridrows: function () {
                return responseMaster.dataadapter.records;
            },
            columns: responseMaster.columns
        });
		
		// detail
		var dataadapter = responseDetail.dataadapter;
		dataadapter.dataBind();
		
		$(template.gridid).bind('rowselect', function (event) {
            var segmentid = event.args.row.SEGMENT_ID;
            var records = new Array();
            var length = dataadapter.records.length;
            var hit = false;
            for (var i = 0; i < length; i++) {
                var record = dataadapter.records[i];
                if (record.SEGMENT_ID == segmentid) {
                    records[records.length] = record;
                    hit = true;
                }
                else if (hit == true)
                	break;
            }
            var dataSource = {
                datafields: responseDetail.datafields,
                localdata: records
            }
            var adapter = new $.jqx.dataAdapter(dataSource);
    
            // update data source.
            $("#detail-grid").jqxGrid({ source: adapter });
        });
		$("#detail-grid").jqxGrid(
        {
        	width: 1192,
            height: 200,
            columnsresize: true,
            theme: 'classic',
            columns: responseDetail.columns
        });
	}

	util.updatestatus = function(sec) {
		var me = this;
		$.getJSON('/status.json', function(data){ 
			var pbvalue = 0; 
			if (data) { 
				var total = data['total'];
				var current = data['current'];
				var msg = data['msg'];
				pbvalue = Math.floor((current / total) * 100);
				if (pbvalue > 0) {
					me.updateprogress(pbvalue, msg);
				}
			}
			if (pbvalue < 100) {
				t = setTimeout(function(){me.updatestatus(sec);}, sec);
			}
			else {
				
			}
		});  
	};
	
	util.updateprogress = function(value, msg) {
		$('#progressbar').jqxProgressBar({value:value});
		$('#progressmsg').html(msg);
		$('#progress-window').css('height', 'auto');
		$('#progress-window-content').css('height', '100%');
	}
	
	util.addtab = function(index) {
		$('#tabbar').jqxTabs('removeAt', index);
		switch(index) {
		case 2:
			$('#tabbar').jqxTabs('addAt', index, "Management Sections", "<div id='seg-pmis-grid'></div>");
			$('#tabbar > div:nth-child('+index+') > div:last-child').attr('id', 'tab-'+index+1);
			$('#tabbar').jqxTabs('select', 0);
		}
	}
	
	util.viewtemplate = {
		pmis: {
			grid: 'views/grid/pmis.grid.ejs',
			toolbar: 'views/grid/pmis.grid.toolbar.ejs',
			tabbar: '#tabbar',
			tabid: '#tab-1',
			tabnumber: 0,
			columnid: '#pmis-columns',
			gridid: '#pmis-grid',
			yearsid: '#pmis-fiscalyears',
			reloadid: '#pmis-reload',
			url: '../../Services/grid/pmis.php',
			defaultcolumns: [0, 1, 2, 3, 4, 5, 6, 7, 42, 47, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80]
				
		},
		dcis: {
			grid: 'views/grid/dcis.grid.ejs',
			toolbar: 'views/grid/dcis.grid.toolbar.ejs',
			tabbar: '#tabbar',
			tabid: '#tab-2',
			tabnumber: 1,
			columnid: '#dcis-columns',
			gridid: '#dcis-grid',
			yearsid: '#dcis-fiscalyears',
			reloadid: '#dcis-reload',
			url: '../../Services/grid/dcis.php',
			defaultcolumns: [0, 1, 2, 3, 4, 5, 6, 7, 9, 10, 11, 12, 13, 99]
		},
		csparams: {
			url: '../../Services/grid/params.php', 
			tabid:'#tab-', 
			gridid:'#grid-', 
			tabbar:'#tabbar-prediction-model',
			defaultcolumns:[2,3,4,5,6], 
			pinnedcolumns:[2,3,4], 
			unpinnedcolumns:[5,6], 
			sort:null, 
			notshowloader:true
		},
		dsparams: {
			url: '../../Services/grid/params.php', 
			tabid:'#tab-', 
			gridid:'#grid-', 
			tabbar:'#tabbar-prediction-model',
			defaultcolumns:[2,3,4,5], 
			pinnedcolumns:[2,3], 
			unpinnedcolumns:[4,5], 
			sort:null, 
			notshowloader:true
		},
		seg: {
			grid: 'views/grid/seg.grid.ejs',
			toolbar: 'views/grid/seg.grid.toolbar.ejs',
			tabbar: '#tabbar',
			tabid: '#tab-3',
			tabnumber: 2,
			columnid: '#seg-columns',
			gridid: '#seg-pmis-grid',
			yearsid: '#seg-fiscalyears',
			reloadid: '#seg-reload',
			url: '../../Services/grid/pmis.php',
			defaultcolumns: ['*']	
		},
		assessed: {
			grid: 'views/grid/assessed.grid.ejs',
			toolbar: 'views/grid/assessed.grid.toolbar.ejs',
			tabbar: '#tabbar',
			tabid: '#tab-4',
			tabnumber: 3,
			columnid: '#assessed-columns',
			gridid: '#assessed-pmis-grid',
			yearsid: '#assessed-fiscalyears',
			reloadid: '#assessed-reload',
			url: '../../Services/grid/pmis.php',
			defaultcolumns: ['*']	
		},
		unitcost: {
			url: '../../Services/grid/params.php', 
			gridid:'#unit-cost-grid',
			defaultcolumns:[0,1,2], 
			pinnedcolumns:[0,1], 
			unpinnedcolumns:[2], 
			sort:null, 
			notshowloader:true
		},
		ridecoeff: {
			url: '../../Services/grid/params.php', 
			gridid:'#ride-utility-coeff-grid',
			defaultcolumns:[1,2,3,4], 
			pinnedcolumns:[1], 
			unpinnedcolumns:[2,3,4], 
			sort:null, 
			notshowloader:true
		}
	}
	return util;
});

