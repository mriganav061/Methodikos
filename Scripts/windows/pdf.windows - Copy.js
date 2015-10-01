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
			var view = can.view(require.toUrl('views/window/pdf.window.ejs'));
			$('#window').append(view);
		},
		initwindow: function(data) {
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
			$('#report-window').remove();
			var view = can.view(require.toUrl('views/window/report.window.ejs'), viewdata);
			$('#window').append(view);

			// prepare the data
			var category = ['Summary of Projects', 
			                'Detailed List of Funded Projects',
			                'Network Condition']
			var windowhandler = [summarywin, impactwin, networkwin];
			var windowid = ['#report-summary-window', '#report-detail-list-window', '#report-network-condition-window'];
			this.initgrid("#report-type-grid", category, windowhandler, windowid, data);
			//hpPlayer----end

			console.error("In pdf" + " " + data.BEGIN_YEAR);


			$('#grid1').jqxPanel({width:'380px', height:'200px', theme:'classic'});
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
			 

			    pdf.fontSetSize(12);
			    pdf.textAdd(50, 90, 'Report Date', 0);

			    pdf.fontSetSize(11);
			    pdf.fontSetStyle(false, false, false);
			    pdf.textAdd(50, 120, 'Jurisdiction', 0);
			    pdf.textAdd(50, 140, 'Comments', 0);
			    pdf.textAdd(50, 160, 'Plan period', 0);

			    pdf.textAdd(400, 120, 'Total lane-Miles', 0);

				pdf.textSetBoxPadding(3, 2, 2, 3);
			    
			    // draw table header
			    pdf.graphicsDrawRectangle(50, 200, 520, 220);
			    pdf.graphicsDrawLine(50, 220, 570, 220);
			    pdf.textSetAlign(BytescoutPDF.CENTER);

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
                  { text: 'View', datafield: 'View', columntype: 'button', cellsrenderer: function () {
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