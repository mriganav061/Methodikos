define([
	'jquery',
	'underscore',
	'can',
	'models/grid',
	'models/combobox',
	'util',
	'windows/seg.opt.window',
	'windows/ahp.opt.window',
	'windows/cs.prediction.model.opt.window',
	'windows/ds.prediction.model.opt.window',
	'windows/ride.utility.window',
	'windows/gain.rating.window',
	'windows/performance.benefit.param.window',
	'windows/unit.cost.window',
	'windows/other.opt.window',
	//'windows/report.window',
	'windows/pdf.windows',
	'livequery',
	'jqx'
], function ($, _, can, grid, combobox, util, segopt,
		ahpopt, cspredictionmodelopt, dspredictionmodelopt, rideutilityopt, gainrateopt, perfbenefitopt, unitcostopt, otheropt,
		pdfwin) {
	var analysisopt = can.Control({
		init: function (element, options) {
			var view = can.view(require.toUrl('views/window/analysis.window.ejs'));
			$('#window').append(view);
		},
		initwindow: function() {
			// performance parameters
			var category = ['Condition Score Prediction Model', 
			                'Distress Score Prediction Model',
			                'Ride Utility Coefficient',
			                'Gain in Ratings Due to M&R'];
			var windowhandler = [cspredictionmodelopt, dspredictionmodelopt, rideutilityopt, gainrateopt];
			var windowid = ['#cs-prediction-window', '#ds-prediction-window', '#ride-utility-coeff-window'
			                ,'#gain-rating-window'];
			this.initgrid("#performance-param-grid", category, windowhandler, windowid);
			$("#performance-param-grid").jqxGrid({showheader:false, height:100});

			// other parameters
			category = ['Performance Benefit Parameters & M&R Triggers','AHP Weights','Unit Cost','Other Parameters'];
			windowhandler = [perfbenefitopt,ahpopt,unitcostopt,otheropt];
			windowid = ['#performance-benefit-opt-window', '#ahp-window','#unit-cost-window','#other-param-window'];
			
			this.initgrid("#other-param-grid", category, windowhandler, windowid);
			$("#other-param-grid").jqxGrid({showheader:false, height:100});

			$('#analysis-window-ok').jqxButton({
	            theme: 'classic',
	            width: '100px'
	        });
			
			// start analysis
			$('#analysis-window-ok').bind('click', function() {
				/************************************************
				 * Gethering all parameter options
				 ************************************************/
				var segmnrtriggervalue=80, segmnrtriggerparam="Condition Score", segminlen=2, segmaxlen=10;
				if (segopt.initdone) {
					segmnrtriggervalue = parseFloat($('#mnr-trigger-method2').jqxNumberInput('getDecimal'));
					segmnrtriggerparam = $("#mnr-trigger-param-method2").jqxComboBox('getSelectedItem').value;
					segminlen = parseFloat($('#min-seg-len-method2').jqxNumberInput('getDecimal'));
					segmaxlen = parseFloat($('#max-seg-len-method2').jqxNumberInput('getDecimal'));
				}

				var pmrsincreaseby=0.5, pmdsresetto=100, lrrsincreaseby=1.5, lrdsresetto=100;
				var mrrsresetto=4.8, mrdsresetto=100, hrrsresetto=4.8, hrdsresetto=100;
				if (gainrateopt.initdone) {
					pmrsincreaseby = parseFloat($('#pm-rs-increaseby').jqxNumberInput('getDecimal'));
					pmdsresetto = parseFloat($('#pm-ds-resetto').jqxNumberInput('getDecimal'));
					lrrsincreaseby = parseFloat($('#lr-rs-increaseby').jqxNumberInput('getDecimal'));
					lrdsresetto = parseFloat($('#lr-ds-resetto').jqxNumberInput('getDecimal'));
					mrrsresetto = parseFloat($('#mr-rs-resetto').jqxNumberInput('getDecimal'));
					mrdsresetto = parseFloat($('#mr-ds-resetto').jqxNumberInput('getDecimal'));
					hrrsresetto = parseFloat($('#hr-rs-resetto').jqxNumberInput('getDecimal'));
					hrdsresetto = parseFloat($('#hr-ds-resetto').jqxNumberInput('getDecimal'));
				}

				var cscoeffpm, cscoefflr, cscoeffmr, cscoeffhr;
				if (cspredictionmodelopt.initdone) {
					cscoeffpm = $("#grid-cs-PM").jqxGrid('exportdata', 'json');
					cscoefflr = $("#grid-cs-LR").jqxGrid('exportdata', 'json');
					cscoeffmr = $("#grid-cs-MR").jqxGrid('exportdata', 'json');
					cscoeffhr = $("#grid-cs-HR").jqxGrid('exportdata', 'json');
				}
				var dscoeffpm, dscoefflr, dscoeffmr, dscoeffhr;
				if (dspredictionmodelopt.initdone) {
					dscoeffpm = $("#grid-ds-PM").jqxGrid('exportdata', 'json');
					dscoefflr = $("#grid-ds-LR").jqxGrid('exportdata', 'json');
					dscoeffmr = $("#grid-ds-MR").jqxGrid('exportdata', 'json');
					dscoeffhr = $("#grid-ds-HR").jqxGrid('exportdata', 'json');
				}
				var ridecoeff;
				if (rideutilityopt.initdone) {
					ridecoeff = $("#ride-utility-coeff-grid").jqxGrid('exportdata', 'json');
				}
				var mnrtriggerparam='Condition Score';
				var mnrtriggervalue=80;
				var benefitcsthreshold=70;
				var pmviabilityvalue=50;
				var lrviabilityvalue=35;
			
				if ($("#mnr-trigger-param").jqxComboBox('disabled'))
					mnrtriggerparam = segmnrtriggerparam;
				else
					mnrtriggerparam = $("#mnr-trigger-param").jqxComboBox('getSelectedItem').value;
				if ($("#mnr-trigger-value").jqxNumberInput('disabled'))
					mnrtriggervalue = segmnrtriggervalue;
				else
					mnrtriggervalue = parseFloat($('#mnr-trigger-value').jqxNumberInput('getDecimal'));

				if (perfbenefitopt.initdone) {
					benefitcsthreshold = parseFloat($('#benefit-cs-threshold').jqxNumberInput('getDecimal'));
					pmviabilityvalue = parseFloat($('#pm-viability-value').jqxNumberInput('getDecimal'));
					lrviabilityvalue = parseFloat($('#lr-viability-value').jqxNumberInput('getDecimal'));
				}
				// ahp
				var wtpcc=26, wtctv=19, wtic=22, wtltpb=19, wtlcc=14;
				if (ahpopt.initdone) {
					wtpcc = parseFloat($('#ratio-pcc').jqxNumberInput('getDecimal'));
					wtctv = parseFloat($('#ratio-ctv').jqxNumberInput('getDecimal'));
					wtic = parseFloat($('#ratio-ic').jqxNumberInput('getDecimal'));
					wtltpb = parseFloat($('#ratio-ltpb').jqxNumberInput('getDecimal'));
					wtlcc = parseFloat($('#ratio-lcc').jqxNumberInput('getDecimal'));
				}

				// pcc
				var wtcsds=21, wtride=6, wtrod=14, wtskid=16, wtsci=11, wtva=32;
				if (ahpopt.pccinitdone) {
					wtcsds = parseFloat($('#ratio-csds').jqxNumberInput('getDecimal'));
					wtride = parseFloat($('#ratio-ride').jqxNumberInput('getDecimal'));
					wtrod = parseFloat($('#ratio-rod').jqxNumberInput('getDecimal'));
					wtskid = parseFloat($('#ratio-skid').jqxNumberInput('getDecimal'));
					wtsci = parseFloat($('#ratio-sci').jqxNumberInput('getDecimal'));
					wtva = parseFloat($('#ratio-lv').jqxNumberInput('getDecimal'));
				}

				// ctv
				var wtaadt=30, wttaadt=70;
				if (ahpopt.ctvinitdone) {
					wtaadt = parseFloat($('#ratio-aadt').jqxNumberInput('getDecimal'));
					wttaadt = parseFloat($('#ratio-taadt').jqxNumberInput('getDecimal'));
				}

				// unit cost
				var unitcost;
				if (unitcostopt.initdone) {
					unitcost = $("#unit-cost-grid").jqxGrid('exportdata', 'json');
				}

				// others
				var currentyear=baseyear, year1budget=18, year2budget=18, year3budget=18, year4budget=18, discountrate=3, aadtgrowthrate=4;
				// var currentyear=baseyear, year1budget=12.5, year2budget=27.5, year3budget=26, year4budget=22.5, discountrate=3, aadtgrowthrate=4;
				if (otheropt.initdone) {
					currentyear = parseFloat($('#current-year').jqxNumberInput('getDecimal'));
					year1budget = parseFloat($('#year1-budget').jqxNumberInput('getDecimal'));
					year2budget = parseFloat($('#year2-budget').jqxNumberInput('getDecimal'));
					year3budget = parseFloat($('#year3-budget').jqxNumberInput('getDecimal'));
					year4budget = parseFloat($('#year4-budget').jqxNumberInput('getDecimal'));
					discountrate = parseFloat($('#discount-rate').jqxNumberInput('getDecimal'));
					aadtgrowthrate = parseFloat($('#aadt-growth-rate').jqxNumberInput('getDecimal'));
				}
				var totalbudget = year1budget + year2budget + year3budget + year4budget;
				var params = {
					segmnrtriggerparam: segmnrtriggerparam,
					segmnrtriggervalue: segmnrtriggervalue,
					segminlen: segminlen,
					segmaxlen: segmaxlen,
					pmrsincreaseby: pmrsincreaseby,
					pmdsresetto: pmdsresetto,
					lrrsincreaseby: lrrsincreaseby,
					lrdsresetto: lrdsresetto,
					mrrsresetto: mrrsresetto,
					mrdsresetto: mrdsresetto,
					hrrsresetto: hrrsresetto,
					hrdsresetto: hrdsresetto,
					cscoeffpm: cscoeffpm,
                	cscoefflr: cscoefflr,
                	cscoeffmr: cscoeffmr,
                	cscoeffhr: cscoeffhr,
                	dscoeffpm: dscoeffpm,
                	dscoefflr: dscoefflr,
                	dscoeffmr: dscoeffmr,
                	dscoeffhr: dscoeffhr,
                	ridecoeff: ridecoeff,
                	mnrtriggerparam: mnrtriggerparam,
                	benefitcsthreshold: benefitcsthreshold,
                	mnrtriggervalue: mnrtriggervalue,
                	pmviabilityvalue: pmviabilityvalue,
                	lrviabilityvalue: lrviabilityvalue,
                	wtpcc: wtpcc,
                	wtctv: wtctv,
                	wtic: wtic,
                	wtltpb: wtltpb,
                	wtlcc: wtlcc,
                	wtcsds: wtcsds,
                	wtride: wtride,
                	wtrod: wtrod,
                	wtskid: wtskid,
                	wtsci: wtsci,
                	wtva: wtva,
                	wtaadt: wtaadt,
                	wttaadt: wttaadt,
                	unitcost: unitcost,
                	currentyear: currentyear,
                	year1budget: year1budget,
                	year2budget: year2budget,
                	year3budget: year3budget,
                	year4budget: year4budget,
                	discountrate: discountrate,
                	aadtgrowthrate: aadtgrowthrate
				}

				// send analysis params
				$.ajax({
	                type: "POST",
	                url: "../../Services/analysis.php",
	                dataType: 'json',
	                data: { 
	                	params: params
	                }
	            }).done(function ( data ) {
                	// when it is done, load up the report window
                	// if (district.length >= 1)
                	// 	data.DISTRICT = data.DISTRICT;
                	// else
                	// 	data.DISTRICT = district[0].toUpperCase();
                	$.extend(true, data, {TOTAL_BUDGET:totalbudget});

                	//Aug_30_2015 hpPlayer
                    pdfwin.initwindow(data);
                	//reportwin.initwindow(data);


                	// update progress bar
					$('#progress-window').jqxWindow('close');
					$('#progress-window').on('close', function (event) { 						
						setTimeout(function(){util.updateprogress(0, 'Initializing...');},500);
					}); 
					$('#report-window').focus();
            	}).fail(function (data) {
	            	$('#progress-window').jqxWindow('close');
	            	alert("Error occured. Please try agiain.");
	            	clearTimeout(t);
	            });
	            util.updateprogress(0, 'Initializing...');
				$('#progress-window').jqxWindow('open');
				t = setTimeout(function() {util.updatestatus(3000);},3000);
			}); // end of button event
			
			$('#analysis-window-cancel').jqxButton({
	            theme: 'classic',
	            width: '80px'
	        });
			
			$('#analysis-window').jqxWindow({
				theme: 'classic',
				width: '470px',
				height: '325px',
				maxHeight: '700px',
				modalOpacity: 0.1,
				isModal: false,
				okButton: $('#analysis-window-ok'),
		        cancelButton: $('#analysis-window-cancel'),
		        resizable: false
			});
			
			this.initdone = true; 
		},
		initdone: false,
		initgrid: function(gridid, category, handler, windowid) {
			var data = [];
			for (var i=0 ; i<category.length ; i++) {
				var row = {};
				row['category'] = category[i]; 
				data[i] = row;
			}
			var source =
            {
                localdata: data,
                datatype: "array",
                datafields:
                [
                    { name: 'category', type: 'string' },
                ]
            };
            var dataAdapter = new $.jqx.dataAdapter(source);       
            $(gridid).jqxGrid(
            {
                width: 450,
                source: dataAdapter,
                theme: 'classic',
                selectionmode: 'none',
                columns: [
                  { text: 'Category', dataField: 'category', width: 300,  cellsalign: 'center', align: 'center' },
                  { text: 'Edit', datafield: 'Edit', columntype: 'button', cellsrenderer: function () {
                      return "Edit";
	                  }, buttonclick: function (row) {
	                      // open the popup window when the user clicks a button.
	                	  if (handler[row].initdone)
	      					$(windowid[row]).jqxWindow('open');
	                	  else
	                		  handler[row].initwindow();
	                	  $(windowid[row]).jqxWindow('bringToFront');
	                	  $(windowid[row]).focus();
	                  }
                  ,  cellsalign: 'center', align: 'center'}
                ]
            });
		}
	});
	
	return new analysisopt(document);
});