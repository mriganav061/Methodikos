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
						
		},
		initwindow: function(data) {
            if (data == null)
                return;
			var me = this;

            $('#report-network-condition-window').remove();
            var view = can.view(require.toUrl('views/window/report.network.condition.window.ejs'));
            $('#window').append(view);

            // prepare chart data
            var chartdata = [data[0].CHART_DATA, data[1].CHART_DATA, data[2].CHART_DATA, data[3].CHART_DATA];
            var beginyear = data[0].CHART_DATA.YEAR;
            var rows = [];
            for (var i=0 ; i<=chartdata.length ; i++) {
                var row = {};
                row.Year = beginyear++;
                if (i==0) {
                    row.Less70=chartdata[i].INIT_BELOW_THRESHOLD;
                    row.Greater70=chartdata[i].INIT_ABOVE_THRESHOLD;
                    row.A=chartdata[i].INIT_A;
                    row.B=chartdata[i].INIT_B;
                    row.C=chartdata[i].INIT_C;
                    row.D=chartdata[i].INIT_D;
                    row.F=chartdata[i].INIT_F;
                }
                else {
                    row.Less70=chartdata[i-1].FINAL_BELOW_THRESHOLD;
                    row.Greater70=chartdata[i-1].FINAL_ABOVE_THRESHOLD;
                    row.A=chartdata[i-1].FINAL_A;
                    row.B=chartdata[i-1].FINAL_B;
                    row.C=chartdata[i-1].FINAL_C;
                    row.D=chartdata[i-1].FINAL_D;
                    row.F=chartdata[i-1].FINAL_F;  
                }
                rows.push(row);
            }
            var labelYears = [];

            var data1 = "";
            for (var i=1 ; i<rows.length ; i++)
                data1 += rows[i].Year+","+rows[i].Less70+","+rows[i].Greater70+"\n";

            var data2 = "";
            for (var i=1 ; i<rows.length ; i++) {
                data2 += rows[i].Year+","+rows[i].F+","+rows[i].D+","+rows[i].C+","+rows[i].B+","+rows[i].A+"\n";
                labelYears.push(rows[i].Year.toString());
            }

            var source1 =
            {
                datatype: "csv",
                datafields: [
                    { name: 'Year' },
                    { name: 'Less70' },
                    { name: 'Greater70' }
                ],
                localdata: data1
            };
            var dataAdapter1 = new $.jqx.dataAdapter(source1, { async: false, autoBind: true, loadError: function (xhr, status, error) { alert('Error loading "' + source.url + '" : ' + error);} });

            var source2 =
            {
                datatype: "csv",
                datafields: [
                    { name: 'Year' },
                    { name: 'VeryPoor' },
                    { name: 'Poor' },
                    { name: 'Fair' },
                    { name: 'Good' },
                    { name: 'VeryGood' }
                ],
                localdata: data2
            };
            var dataAdapter2 = new $.jqx.dataAdapter(source2, { async: false, autoBind: true, loadError: function (xhr, status, error) { alert('Error loading "' + source.url + '" : ' + error);} });
            
            var dsavgdata = [];
            var years = [];
            var dsmindata = [];
            var csavgdata = [];
            var csmindata = [];
            var threshold = [];
            var backlog_cost = [];
            beginyear = data[0].CHART_DATA.YEAR;
            for (var i=0 ; i<chartdata.length ; i++) {
                dsavgdata.push(chartdata[i].INIT_AVG_DS);
                dsavgdata.push(chartdata[i].AFTER_MNR_AVG_DS);
                dsavgdata.push(chartdata[i].FINAL_AVG_DS);
                dsmindata.push(chartdata[i].INIT_MIN_DS);
                dsmindata.push(chartdata[i].AFTER_MNR_MIN_DS);
                dsmindata.push(chartdata[i].FINAL_MIN_DS);
                csavgdata.push(chartdata[i].INIT_AVG_CS);
                csavgdata.push(chartdata[i].AFTER_MNR_AVG_CS);
                csavgdata.push(chartdata[i].FINAL_AVG_CS);
                csmindata.push(chartdata[i].INIT_MIN_CS);
                csmindata.push(chartdata[i].AFTER_MNR_MIN_CS);
                csmindata.push(chartdata[i].FINAL_MIN_CS);
                threshold.push(70);
                threshold.push(70);
                threshold.push(70);
                years.push(beginyear);
                years.push(beginyear);
                years.push(++beginyear);
                backlog_cost.push(chartdata[i].BACKLOG_LANE_COST_TOTAL_PM+
                    chartdata[i].BACKLOG_LANE_COST_TOTAL_LR+
                    chartdata[i].BACKLOG_LANE_COST_TOTAL_MR+
                    chartdata[i].BACKLOG_LANE_COST_TOTAL_HR);
            }
            
            var data3 = "";
            for (var i=0 ; i<dsavgdata.length-1 ; i++)
                data3 += "7/1/"+(years[i]+1)+","+dsavgdata[i]+","+dsmindata[i]+"\n";
            
            var source3 =
            {
                datatype: "csv",
                datafields: [
                    { name: 'Year' },
                    { name: 'AverageDS' },
                    { name: 'MinDS' }
                ],
                localdata: data3
            };
            var dataAdapter3 = new $.jqx.dataAdapter(source3, { async: false, autoBind: true, loadError: function (xhr, status, error) { alert('Error loading "' + source.url + '" : ' + error);} });

            
            var data4 = "";
            for (var i=0 ; i<csavgdata.length-1 ; i++)
                data4 += "7/1/"+(years[i]+1)+","+csavgdata[i]+","+csmindata[i]+","+threshold[i]+"\n";

            var source4 =
            {
                datatype: "csv",
                datafields: [
                    { name: 'Year' },
                    { name: 'AverageCS' },
                    { name: 'MinCS' },
                    { name: 'ThresholdCS'}
                ],
                localdata: data4
            };
            var dataAdapter4 = new $.jqx.dataAdapter(source4, { async: false, autoBind: true, loadError: function (xhr, status, error) { alert('Error loading "' + source.url + '" : ' + error);} });

            var data5 = "";
            beginyear = data[0].CHART_DATA.YEAR+1;
            for (var i=0 ; i<backlog_cost.length ; i++)
                data5 += (beginyear++)+","+backlog_cost[i]+"\n";

            // var data5="7/1/2012,32199.600754699997
            //             7/1/2013,20020.0576
            //             7/1/2014,6238.4266
            //             7/1/2015,0";

            var source5 = {
                datatype: "csv",
                datafields: [
                    {name: 'Year' },
                    {name: 'Backlog'}
                ],
                localdata: data5
            };

            var dataAdapter5 = new $.jqx.dataAdapter(source5, { async: false, autoBind: true, loadError: function (xhr, status, error) { alert('Error loading "' + source.url + '" : ' + error);} });
            
            // if (me.initdone == false) {
    			$('#report-network-condition-window-ok').jqxButton({
    	            theme: 'classic',
    	            width: '80px'
    	        });
    			
    			$('#report-network-condition-window-ok').bind('click', function() {
    				
    			}); // end of button event
    			
    			$('#report-network-condition-window-cancel').jqxButton({
    	            theme: 'classic',
    	            width: '80px'
    	        });
    			

    			$('#report-network-condition-window').jqxWindow({
    				theme: 'classic',
    				width: '718px',
    				height: '545px',
    				maxHeight: '700px',
    				okButton: $('#report-network-condition-window-ok'),
    		        cancelButton: $('#report-network-condition-window-cancel'),
    		        resizable: false,
    		        autoOpen: true,
    		        initContent: function() {
    		            // setup the chart
    		            $('#chart-overall-network-condition-good-bad').jqxChart(me.getsetting1(dataAdapter1));
    		            $('#chart-overall-network-condition-cs-classification').jqxChart(me.getsetting2(dataAdapter2));
                        $('#chart-backlog').jqxChart(me.getsetting5(dataAdapter5));
    		            $('#chart-network-prediction-ds').jqxChart(me.getsetting3(labelYears, dataAdapter3));
    		            $('#chart-network-prediction-cs').jqxChart(me.getsetting4(labelYears, dataAdapter4));
    		        }
    			});		
            // }
            // else {
            //     $('#report-network-condition-window').jqxWindow({
            //         theme: 'classic',
            //         width: '718px',
            //         height: '545px',
            //         maxHeight: '700px',
            //         okButton: $('#report-network-condition-window-ok'),
            //         cancelButton: $('#report-network-condition-window-cancel'),
            //         resizable: false,
            //         autoOpen: true,
            //         initContent: function() {
            //             // setup the chart
            //             $('#chart-overall-network-condition-good-bad').jqxChart({_renderData: new Array()});
            //             $('#chart-overall-network-condition-good-bad').jqxChart({source:dataAdapter1});
            //             $('#chart-overall-network-condition-good-bad').jqxChart('refresh');
            //             $('#chart-overall-network-condition-cs-classification').jqxChart({_renderData: new Array()});
            //             $('#chart-overall-network-condition-cs-classification').jqxChart({source:dataAdapter2});
            //             $('#chart-overall-network-condition-cs-classification').jqxChart('refresh');
            //             $('#chart-network-prediction-ds').jqxChart({_renderData: new Array()});
            //             $('#chart-network-prediction-ds').jqxChart({source:dataAdapter3});
            //             $('#chart-network-prediction-ds').jqxChart('refresh');
            //             $('#chart-network-prediction-cs').jqxChart({_renderData: new Array()});
            //             $('#chart-network-prediction-cs').jqxChart({source:dataAdapter4});
            //             $('#chart-network-prediction-cs').jqxChart('refresh');
            //         }
            //     }); 
               
            // }
			this.initdone = true;
		},
		initdone: false,
		getsetting1: function(dataAdapter) {            
            // prepare jqxChart settings
            var settings = {
                title: "Overall Network Condition",
                description: "Good vs. Bad CS",
                enableAnimations: true,
                showLegend: true,
                padding: { left: 5, top: 5, right: 5, bottom: 5 },
                titlePadding: { left: 90, top: 0, right: 0, bottom: 10 },
                source: dataAdapter,
                categoryAxis:
                    {
                        text: 'Category Axis',
                        textRotationAngle: 0,
                        dataField: 'Year',
                        showTickMarks: true,
                        tickMarksInterval: 1,
                        tickMarksColor: '#888888',
                        unitInterval: 1,
                        showGridLines: false,
                        gridLinesInterval: 1,
                        gridLinesColor: '#888888',
                        axisSize: 'auto'
                    },
                colorScheme: 'scheme01',
                seriesGroups:
                    [
                        {
                            type: 'stackedcolumn100',
                            columnsGapPercent: 100,
                            seriesGapPercent: 5,
                            valueAxis:
                            {
                                unitInterval: 10,
                                minValue: 0,
                                maxValue: 100,
                                displayValueAxis: true,
                                description: '% of Network Lane-Miles',
                                axisSize: 'auto',
                                tickMarksColor: '#888888'
                            },
                            series: [
                                     { dataField: 'Greater70', displayText: 'CS>=70' },
                                     { dataField: 'Less70', displayText: 'CS<70' }
                                ]
                        }
                    ]
            };
            return settings;
		},
        getsetting2: function(dataAdapter) {            
            // prepare jqxChart settings
            var settings = {
                title: "Overall Network Condition",
                description: "CS Classification",
                enableAnimations: true,
                showLegend: true,
                padding: { left: 5, top: 5, right: 5, bottom: 5 },
                titlePadding: { left: 90, top: 0, right: 0, bottom: 10 },
                source: dataAdapter,
                categoryAxis:
                    {
                        text: 'Category Axis',
                        textRotationAngle: 0,
                        dataField: 'Year',
                        showTickMarks: true,
                        tickMarksInterval: 1,
                        tickMarksColor: '#888888',
                        unitInterval: 1,
                        showGridLines: false,
                        gridLinesInterval: 1,
                        gridLinesColor: '#888888',
                        axisSize: 'auto'
                    },
                colorScheme: 'scheme01',
                seriesGroups:
                    [
                        {
                            type: 'stackedcolumn100',
                            columnsGapPercent: 100,
                            seriesGapPercent: 5,
                            valueAxis:
                            {
                                unitInterval: 10,
                                minValue: 0,
                                maxValue: 100,
                                displayValueAxis: true,
                                description: '% of Network Lane-Miles',
                                axisSize: 'auto',
                                tickMarksColor: '#888888'
                            },
                            series: [
                                     { dataField: 'VeryGood', displayText: 'Very Good' },
                                     { dataField: 'Good', displayText: 'Good' },
                                     { dataField: 'Fair', displayText: 'Fair' },
                                     { dataField: 'Poor', displayText: 'Poor' },
                                     { dataField: 'VeryPoor', displayText: 'Very Poor' }
                                ]
                        }
                    ]
            };
            return settings;
        },
		getsetting3: function(labelYears, dataAdapter) {
            // prepare jqxChart settings
		    var settings = {
                title: "Network Predicted Condition",
                description: "Average Distress Score",
                enableAnimations: true,
                showLegend: true,
                padding: { left: 10, top: 5, right: 10, bottom: 5 },
                titlePadding: { left: 90, top: 0, right: 0, bottom: 10 },
                source: dataAdapter,
                categoryAxis:
                    {
                        text: 'Category Axis',
                        textRotationAngle: 0,
                        dataField: 'Year',
                        formatFunction: function (value) {
                            return labelYears[value.getFullYear()%parseInt(labelYears[0])];
                        },
                        toolTipFormatFunction: function (value) {
                            return value.getDate() + '-' + labelYears[value.getFullYear()%parseInt(labelYears[0])];
                        },
                        type: 'date',
                        baseUnit: 'year',
                        showTickMarks: true,
                        valuesOnTicks: false,
                        tickMarksInterval: 1,
                        tickMarksColor: '#888888',
                        unitInterval: 1,
                        gridLinesInterval: 1,
                        gridLinesColor: '#888888'
                    },
                colorScheme: 'scheme05',
                seriesGroups:
                    [
                        {
                            type: 'line',
                            showLabels: false,
                            symbolType: 'circle',
                            valueAxis:
                            {
                                unitInterval: 10,
                                minValue: 0,
                                maxValue: 100,
                                description: 'Distress Score',
                                axisSize: 'auto',
                                tickMarksColor: '#888888'
                            },
                            series: [
                                    { dataField: 'AverageDS', displayText: 'Average Network DS'},
                                    { dataField: 'MinDS', displayText: 'Minimum Network DS'}
                                ]
                        }
                    ]
            };
            return settings;
		},
		getsetting4: function(labelYears, dataAdapter) {
            // prepare jqxChart settings
            var settings = {
                title: "Network Predicted Condition",
                description: "Average Condition Score",
                enableAnimations: true,
                showLegend: true,
                padding: { left: 10, top: 5, right: 10, bottom: 5 },
                titlePadding: { left: 90, top: 0, right: 0, bottom: 10 },
                source: dataAdapter,
                categoryAxis:
                    {
                        text: 'Category Axis',
                        textRotationAngle: 0,
                        dataField: 'Year',
                        formatFunction: function (value) {
                            return labelYears[value.getFullYear()%parseInt(labelYears[0])];
                        },
                        toolTipFormatFunction: function (value) {
                            return value.getDate() + '-' + labelYears[value.getFullYear()%parseInt(labelYears[0])];
                        },
                        type: 'date',
                        baseUnit: 'year',
                        showTickMarks: true,
                        valuesOnTicks: false,
                        tickMarksInterval: 1,
                        tickMarksColor: '#888888',
                        unitInterval: 1,
                        gridLinesInterval: 1,
                        gridLinesColor: '#888888'
                    },
                colorScheme: 'scheme05',
                seriesGroups:
                    [
                        {
                            type: 'line',
                            showLabels: false,
                            symbolType: 'circle',
                            valueAxis:
                            {
                                unitInterval: 10,
                                minValue: 0,
                                maxValue: 100,
                                description: 'Condition Score',
                                axisSize: 'auto',
                                tickMarksColor: '#888888'
                            },
                            series: [
                                    { dataField: 'AverageCS', displayText: 'Average Network CS'},
                                    { dataField: 'MinCS', displayText: 'Minimum Network CS'},
                                    { dataField: 'ThresholdCS', displayText: 'Threshold CS'}
                                ]
                        }
                    ]
            };
            return settings;
		},
        getsetting5: function(dataAdapter) {            
            // prepare jqxChart settings
            var settings = {
                title: "Backlog",
                description: "Backlog (1000$)",
                enableAnimations: true,
                showLegend: true,
                padding: { left: 5, top: 5, right: 5, bottom: 5 },
                titlePadding: { left: 90, top: 0, right: 0, bottom: 10 },
                source: dataAdapter,
                categoryAxis:
                    {
                        text: 'Category Axis',
                        textRotationAngle: 0,
                        dataField: 'Year',
                        showTickMarks: true,
                        tickMarksInterval: 1,
                        tickMarksColor: '#888888',
                        unitInterval: 1,
                        showGridLines: false,
                        gridLinesInterval: 1,
                        gridLinesColor: '#888888',
                        axisSize: 'auto'
                    },
                colorScheme: 'scheme01',
                seriesGroups:
                    [
                        {
                            type: 'column',
                            columnsGapPercent: 100,
                            // seriesGapPercent: 5,
                            // toolTipFormatSettings: { thousandsSeparator: ',' },
                            valueAxis:
                            {
                                unitInterval: 10000,
                                minValue: 0,
                                // maxValue: 1500000,
                                displayValueAxis: true,
                                description: '',
                                axisSize: 'auto',
                                tickMarksColor: '#888888',
                                // formatFunction: function (value) {
                                //     return parseInt(value / 1000000);
                                // }
                            },
                            series: [
                                     { dataField: 'Backlog', displayText: 'Backlog' },                                     
                                ]
                        }
                    ]
            };
            return settings;
        },
	});
	return new window(document);
});