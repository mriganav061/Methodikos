require.config({
  waitSeconds: 0,
  //ASSIGN SHORTCUTS FOR EASY LOADING AND VERSION ABSTRACTION
  paths: {
    jquery: 'libs/jquery-1.8.3.min',
    underscore: 'libs/underscore-min',
    can: 'libs/can.jquery.min',
    jquerymobile: 'libs/jquerymobile/jquery.mobile-1.2.0-beta.1.min',
    qTip: 'libs/qtip/jquery.qtip.min',
    blockUI: 'libs/jquery.blockUI',
    ajaxForm: 'libs/jquery.form',
    moment: 'libs/moment.min',
    notifier: 'libs/notifier.mod',
    // html5placeholder: 'libs/html5placeholder.mod',
    browserselector: 'libs/css_browser_selector',
    jqx: 'libs/jqwidgets/js/jqx-all-2.6',
    // jqxold: 'libs/jqwidgets/js/jqx-all-2.6',
    livequery: 'libs/jquery.livequery.min',
    gmap: 'controllers/gmap',
    util: 'util',
    zcalc: 'libs/zcalc',
    gdropdown: 'libs/gdropdown'
  },
  //DECLARE NON-AMD COMPLIANT JS AND DEPENDENCIES
  shim: {
    underscore: {
      deps: [ 'jquery' ],
      exports: '_'
    },
    can: {
      deps: [ 'jquery' ],
      exports: 'can'
    },
    moment: {
      deps: [ 'jquery' ],
      exports: 'moment'
    },
    notifier: {
      deps: [ 'jquery' ],
      exports: 'Notifier'
    },
    qTip: ['jquery'],
    blockUI: ['jquery'],
    ajaxForm: ['jquery'],
    util: {
      deps: ['jquery'],
      exports: 'util'
    },
    // html5placeholder: ['jquery'],
    // jqx: ['jquery'],
    livequery: ['jquery']
  }
});

//INITIALIZE APP
require([
  'router',
  'browserselector'
  // 'html5placeholder'
], function () {
  //ROUTER DEPENDENCY INITIATED AND LISTENING
});