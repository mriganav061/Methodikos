define([
	'jquery',
	'can',
	'controllers/menu',
	'controllers/ui',
	'controllers/grid',
	'gmap'
], function ($, can) {

	var Router = can.Control({
		//HOME PAGE
		'route': function () {
			// pre-loader
			$("#loaderInner").append("  Done");
			$("#loader").fadeOut("slow");
		}
	});

	return new Router(document);
});