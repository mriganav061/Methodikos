define([
	'jquery',
	'underscore',
	'can',
	'models/grid',
	'models/combobox',
	'windows/bytescoutpdf1.15.150',
	'windows/report.summary.window',
	'windows/report.detail.list.window',
	'windows/report.network.condition.window',
	'util',
	'jqx'
], function ($, _, can, grid, combobox, util, summarywin, impactwin, networkwin) {
	//var pdfwindow = can.Control({
	var window = can.Control({
		init: function (element, options) {
			//var view = can.view(require.toUrl('views/window/pdf.window.ejs'));
			//$('#window').append(view);
		},
		initwindow: function(data, params) {
			if (data == null) {
				 	console.log("input data of pdfwin is empty");
					return;
			}
			//hpPlayer Aug_30_2015
			var tabledata = {
				BeginYear:data.BEGIN_YEAR,
				EndYear:data.END_YEAR,
				District:data.DISTRICT,
				TotalLaneMiles:data.TOTAL_LANE_MILES,
				TotalSegments:data.TOTAL_SEGMENTS,
				TotalBudget:data.TOTAL_BUDGET
			};

			var viewdata = {
				analysis: tabledata
			};
			$('#pdf-window').remove();
			var view = can.view(require.toUrl('views/window/pdf.window.ejs'), viewdata);
			$('#window').append(view);

			// prepare the data
			var category = ['Summary of Projects', 
			                'Detailed List of Funded Projects',
			                'Network Condition']
			var windowhandler = [summarywin, impactwin, networkwin];
			var windowid = ['#report-summary-window', '#report-detail-list-window', '#report-network-condition-window'];
			this.initgrid("#report-type-grid", category, windowhandler, windowid, data);
			$("#report-type-grid").jqxGrid({showheader:false, height:100})				
			$('#report-window').jqxWindow({
				theme: 'classic',
				width: '400px',
				height: 'auto',//'290px',
				maxHeight: '700px',
				okButton: $('#report-window-ok'),
		        resizable: false,
		        autoOpen: true
			});	
			//console.error("In pdf" + " " + data.unitcost);
			//hpPlayer----end

			


			$('#grid1').jqxPanel({width:'500px', height:'300px', theme:'classic'});
			$('#pdf').jqxNumberInput({
		        width: '140px',
		        height: '20px',
		        decimal: 1,
		        inputMode: 'simple',
		        spinMode: 'simple',
		        spinButtons: true,
		        spinButtonsStep: 1,
		        theme: 'classic'
		    });
			$('#pdf-ok').jqxButton({
	            theme: 'classic',
	            width: '120px'
	        });
			$('#pdf-cancel').jqxButton({
	            theme: 'classic',
	            width: '120px'
	        });	        
			$('#pdf-ok').bind('click', function () {
				//var BytescoutPDF = require('E:/xampp_old/htdocs/Scripts/windows/bytescoutpdf1.15.150.js');
			    // create BytescoutPDF object instance
			    var pdf = new BytescoutPDF();


			    // set document properties: Title, subject, keywords, author name and creator name
			    pdf.propertiesSet('Sample Invoice', 'Invoice #1234', 'invoice, company, customer', 'Document Author', 'Document Creator');

			    // set page size
			    pdf.pageSetSize(BytescoutPDF.Letter);

			    // set portrait page orientation
			    pdf.pageSetOrientation(BytescoutPDF.PORTRAIT);

			    // add new page
			    pdf.pageAdd();

			    // set font name
			    pdf.fontSetName('Times-Roman');

			    // add requisites

			    pdf.textSetBox(30, 30, 520, 220);
			    pdf.textSetAlign(BytescoutPDF.CENTER);
			    pdf.fontSetSize(24);
			    pdf.fontSetStyle(true, false, false);
			    pdf.textAddToBox('Pavement Management Plan (PMP)');
			 

			    var today = new Date();
				var dd = today.getDate();
				var mm = today.getMonth()+1; //January is 0!
				var yyyy = today.getFullYear();

				if(dd<10) {
				    dd='0'+dd
				} 

				if(mm<10) {
				    mm='0'+mm
				} 

				today = mm+'/'+dd+'/'+yyyy;

			    //pdf.fontSetSize(12);
			    //pdf.textAdd(50, 90, 'Report Date: ' + today, 0);
			    //textAdd(x-Axis, y-Axis)
			    pdf.fontSetSize(11);
			    pdf.fontSetStyle(false, false, false);
			    pdf.textAdd(50, 120, 'Jurisdiction: ', 0);

			    pdf.fontSetColor(0, 0, 255);//set to blue
			    pdf.textAdd(150, 120, data.DISTRICT, 0);

				pdf.fontSetColor(0, 0, 0);//set to black
			    pdf.textAdd(50, 140, 'Plan period: ', 0);

			    pdf.fontSetColor(0, 0, 255);//set to blue
			    pdf.textAdd(150, 140, data.BEGIN_YEAR + ' - ' + data.END_YEAR, 0);

				pdf.fontSetColor(0, 0, 0);//set to black
			    pdf.textAdd(400, 120, 'Report Date: ', 0);

			    pdf.fontSetColor(0, 0, 255);//set to blue
			    pdf.textAdd(500, 120, today, 0);

				pdf.fontSetColor(0, 0, 0);//set to black
			    pdf.textAdd(400, 140, 'Total lane-Miles: ', 0);

			    pdf.fontSetColor(0, 0, 255);//set to blue
			    pdf.textAdd(500, 140, data.TOTAL_LANE_MILES, 0);

				pdf.fontSetColor(0, 0, 0);

			    pdf.textAdd(50, 160, 'Comments:', 0);
			    pdf.textAdd(50, 175, '____________________________________________________________________________________________', 0);
			    pdf.textAdd(50, 190, '____________________________________________________________________________________________', 0);
			    pdf.textAdd(50, 205, '____________________________________________________________________________________________', 0);


			    pdf.fontSetColor(0, 0, 0);//set to black

				pdf.textSetBoxPadding(3, 2, 2, 3);
			    

			    pdf.fontSetSize(14);
			    pdf.fontSetStyle(true, false, false);
				pdf.textAdd(50, 230, 'Available Budget', 0);

				pdf.fontSetSize(11);
				pdf.fontSetStyle(false, false, false);
				pdf.textAdd(50, 250, ' Year 1 Budget ($ million)', 0);

			    pdf.fontSetColor(0, 0, 255);//set to blue
			    pdf.textAdd(300, 250, ' ' + params.year1budget, 0);
				pdf.fontSetColor(0, 0, 0);//set to black

				pdf.textAdd(50, 270, ' Year 2 Budget ($ million):', 0);

			    pdf.fontSetColor(0, 0, 255);//set to blue
			    pdf.textAdd(300, 270, ' ' + params.year2budget, 0);
				pdf.fontSetColor(0, 0, 0);//set to black

				pdf.textAdd(50, 290, ' Year 3 Budget ($ million):', 0);

			    pdf.fontSetColor(0, 0, 255);//set to blue
			    pdf.textAdd(300, 290, ' ' + params.year3budget, 0);
				pdf.fontSetColor(0, 0, 0);//set to black

				pdf.textAdd(50, 310, ' Year 4 Budget ($ million):', 0);

			    pdf.fontSetColor(0, 0, 255);//set to blue
			    pdf.textAdd(300, 310, ' ' + params.year4budget, 0);
				pdf.fontSetColor(0, 0, 0);//set to black


				pdf.textAdd(50, 330, ' Total Budget for Planning Period ($ million):', 0);
				

			    pdf.fontSetColor(0, 0, 255);//set to blue
			    pdf.textAdd(300, 330, ' ' + data.TOTAL_BUDGET, 0);
				pdf.fontSetColor(0, 0, 0);//set to black

			    pdf.fontSetSize(14);
			    pdf.fontSetStyle(true, false, false);
				pdf.textAdd(50, 350, 'Analysis Parameters', 0);

				pdf.fontSetSize(12);
				pdf.textAdd(50, 370, 'Economic Parameters', 0);

				pdf.fontSetSize(11);
				pdf.fontSetStyle(false, false, false);
				pdf.textAdd(50, 390, ' Discount Rate (%): ', 0);

			    pdf.fontSetColor(0, 0, 255);//set to blue
			    pdf.textAdd(300, 390, ' ' + params.discountrate, 0);
				pdf.fontSetColor(0, 0, 0);//set to black

				pdf.fontSetSize(12);
				pdf.fontSetStyle(true, false, false);
				pdf.textAdd(50, 410, 'Traffic Growth', 0);

				pdf.fontSetSize(11);
				pdf.fontSetStyle(false, false, false);
				pdf.textAdd(50, 430, ' AADT Compound Growth Rate (%): ', 0);

			    pdf.fontSetColor(0, 0, 255);//set to blue
			    pdf.textAdd(300, 430, ' ' + params.aadtgrowthrate, 0);
				pdf.fontSetColor(0, 0, 0);//set to black


				pdf.fontSetSize(12);
				pdf.fontSetStyle(true, false, false);
				pdf.textAdd(50, 450, 'Treatment Unit Cost ($1000 per Lane-mile)', 0);
				pdf.fontSetStyle(false, false, false);
				// draw table header
			    //graphicsDrawRectangle(left x-Axis, top y-Axis, right x-Axis, height)
			    pdf.graphicsDrawRectangle(50, 455, 520, 100);//50, 200, 520, 220
			    //rows
			    //pdf.graphicsDrawLine(left x-Axis, right y-Axis, length???, left y-Axis)
			    pdf.graphicsDrawLine(50, 480, 570, 480);
			    pdf.graphicsDrawLine(50, 505, 570, 505);
			    pdf.graphicsDrawLine(50, 530, 570, 530);
			    pdf.textSetAlign(BytescoutPDF.CENTER);

			    //columns
			    //textSetBox(left x-Axis, y-Axis, length, height????)
			    pdf.fontSetStyle(true, false, false);
			    pdf.fontSetSize(10);
			    pdf.textSetBox(50, 460, 80, 100);
			    pdf.textAddToBox('Pavement Type');
			    //pdf.graphicsDrawLine(left x-Axis, left y axis. right x-Axis, right y-Axis)
				pdf.graphicsDrawLine(155, 455, 155, 555);

			    pdf.textSetBox(150, 460, 120, 100);
			    pdf.textAddToBox('Preventive Maintenance');
				pdf.graphicsDrawLine(265, 455, 265, 555);

				pdf.textSetBox(290, 460, 80, 100);
			    pdf.textAddToBox('Light Rehab');
				pdf.graphicsDrawLine(385, 455, 385, 555);

				pdf.textSetBox(390, 460, 80, 100);
			    pdf.textAddToBox('Medium Rehab');
				pdf.graphicsDrawLine(485, 455, 485, 555);

				pdf.textSetBox(490, 460, 80, 100);
			    pdf.textAddToBox('Heavy Rehab');		    			    			    

			    pdf.fontSetStyle(false, false, false);

				pdf.textSetBox(50, 485, 100, 100);
			    pdf.textAddToBox('ACP or HMA-Overlaid');
			    pdf.textSetBox(50, 510, 80, 100);
			    pdf.textAddToBox('CRCP');
			    pdf.textSetBox(50, 535, 80, 100);
			    pdf.textAddToBox('JCP');



			    pdf.fontSetSize(12);
				pdf.fontSetStyle(true, false, false);
				pdf.textAdd(50, 585, 'Treatment\'s Immediate Effect on Pavement Condition', 0);
				pdf.fontSetStyle(false, false, false);
				pdf.graphicsDrawRectangle(50, 590, 520, 100);//50, 200, 520, 220
				pdf.fontSetSize(10);
				pdf.textAdd(50, 700, '*If Ride Score > 4.8, set Ride Score = 4.8', 0);
				
			    pdf.graphicsDrawLine(50, 625, 570, 625);
			    pdf.graphicsDrawLine(50, 655, 570, 655);
			    pdf.textSetAlign(BytescoutPDF.CENTER);

	    		pdf.fontSetStyle(true, false, false);    
			    pdf.textSetBox(50, 595, 80, 100);
			    pdf.textAddToBox('Effect');
			    //pdf.graphicsDrawLine(left x-Axis, left y axis. right x-Axis, right y-Axis)
				pdf.graphicsDrawLine(155, 590, 155, 690);

				pdf.textSetBox(150, 595, 120, 100);
			    pdf.textAddToBox('Preventive Maintenance');
				pdf.graphicsDrawLine(265, 590, 265, 690);

				pdf.textSetBox(290, 595, 80, 100);
			    pdf.textAddToBox('Light Rehab');
				pdf.graphicsDrawLine(385, 590, 385, 690);

				pdf.textSetBox(390, 595, 80, 100);
			    pdf.textAddToBox('Medium Rehab');
				pdf.graphicsDrawLine(485, 590, 485, 690);

				pdf.textSetBox(490, 595, 80, 100);
			    pdf.textAddToBox('Heavy Rehab');		    			    			    

			    pdf.fontSetStyle(false, false, false);

			    pdf.fontSetSize(9);
			    pdf.textSetBox(50, 625, 100, 100);
			    pdf.textAddToBox('*Increase Ride Score by:');

			    pdf.fontSetColor(0, 0, 255);//set to blue			    

			    pdf.textSetBox(155, 625, 100, 100);
			    pdf.textAddToBox('' + params.pmrsincreaseby);

			    pdf.textSetBox(280, 625, 100, 100);
			    pdf.textAddToBox('' + params.lrrsincreaseby);

			    pdf.textSetBox(380, 625, 100, 100);
			    pdf.textAddToBox('' + params.mrrsresetto);

			    pdf.textSetBox(480, 625, 100, 100);
			    pdf.textAddToBox('' + params.hrrsresetto);

			    pdf.fontSetColor(0, 0, 0);//set to blue

			    pdf.textSetBox(50, 655, 100, 100);
			    pdf.textAddToBox('Set Distress Score to:');

			    pdf.fontSetColor(0, 0, 255);//set to blue
			    pdf.textSetBox(155, 655, 100, 100);
			    pdf.textAddToBox('' + params.pmdsresetto);

			    pdf.textSetBox(280, 655, 100, 100);
			    pdf.textAddToBox('' + params.lrdsresetto);

			    pdf.textSetBox(380, 655, 100, 100);
			    pdf.textAddToBox('' + params.mrdsresetto);

			    pdf.textSetBox(480, 655, 100, 100);
			    pdf.textAddToBox('' + params.hrdsresetto);
			    pdf.fontSetColor(0, 0, 0);//set to blue

			    pdf.pageAdd();
			    pdf.fontSetSize(24);
			    pdf.textAdd(200, 100, 'Output Data', 0);
				pdf.textAdd(200, 200, 'First Year\'s cost: ' + (data[0].OUTPUT_STAT.FORCED_COST_TOTAL_PM + data[0].OUTPUT_STAT.LANE_COST_TOTAL_PM), 0);
			    /*
			    // add 'Description' column
			    pdf.textSetBox(50, 200, 80, 20);
			    pdf.textAddToBox('Pavement Type');
			    pdf.graphicsDrawLine(270, 200, 270, 420);
				// add 'Quantity' column
			    pdf.textSetBox(270, 200, 80, 20);
			    pdf.textAddToBox('Light Rehab');
			    pdf.graphicsDrawLine(350, 200, 350, 420);
			    // add 'Price' column
			    pdf.textSetBox(350, 200, 100, 20);
			    pdf.textAddToBox('medium Rehab');
			    pdf.graphicsDrawLine(450, 200, 450, 420);
			    // add 'Amount' column
			    pdf.textSetBox(450, 200, 120, 20);
			    pdf.textAddToBox('Heavy Rehab');
			    pdf.textSetAlign(BytescoutPDF.LEFT);
				        
			    // fill table content
			    for (var row=0; row < 10; row++) {
			        pdf.textSetBox(50, 220 + row * 20, 220, 20);
			        pdf.textAddToBox('Data ' + row);
			        pdf.graphicsDrawLine(50, 240 + row * 20, 570, 240 + row * 20);
			    }

			    // add signature
			    pdf.textAdd(390, 470, 'Signature', 0);
			    pdf.graphicsDrawLine(450, 470, 570, 470);
				*/

            	// get generated PDF file in a form of encoded string
            	var PDFContentBase64 = pdf.getBase64Text();
				var pdfdiv = document.getElementById("getpdf");
			    // added on May 5, 2015:   
				// add a link to download PDF as attachment (via <a href..></a> link with download="filename.pdf" parameter)                
				pdfdiv.innerHTML = pdfdiv.innerHTML + '<h3><a title=\"title\" download=\"Sample.PDF\" href=\"data:application/pdf;base64,' + PDFContentBase64 + '\"><\/a></h3>';
				// create the button code
				var buttonCode = '<button onclick=\"' + 'location.href = \'data:application\/pdf;base64,' + PDFContentBase64 + '\'";' + 
				'id=\"showPDFButton\" class=\"buttonClass\">Report</button>';
                
	            // add the button code to the pdfdiv element existing code
                pdfdiv.innerHTML += buttonCode;


			});
			$('#pdf-windows').jqxWindow({
					theme: 'classic',
					width: '400px',
					height: '400px',
					isModal: false,
					modalOpacity: 0.1,
			        resizable: false,
			        //okButton: $('#pdf-ok'),
		       		cancelButton: $('#pdf-cancel'),
			        initContent: function() {	
					}
			});
		this.initdone = true;
		//console.error("here");
		},
		initdone: false,
		initgrid: function(gridid, category, handler, windowid, analysisresult) {
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
                width: 380,
                source: dataAdapter,
                theme: 'classic',
                selectionmode: 'none',
                autoheight: true,
                columns: [
                  { text: 'Category', dataField: 'category', width: 230,  cellsalign: 'center', align: 'center' },
                  { text: 'PDF', datafield: '#pdf-ok', columntype: 'button', cellsrenderer: function () {
                      return "View";
	                  }, buttonclick: function (row) {
	                      // open the popup window when the user clicks a button.
	                      if (analysisresult != null)
	                      	handler[row].initwindow(analysisresult);
	                      if (handler[row].initdone)
	                      	$(windowid[row]).jqxWindow('open');	                		  
	                	  $(windowid[row]).focus();
	                	  $(windowid[row]).jqxWindow('bringToFront');
	                  }
                  ,  cellsalign: 'center', align: 'center'}
                ]
            });
		}
	});
	//return new pdfwindow(document);
	return new window(document);
});