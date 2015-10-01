a:62:{i:0;a:3:{i:0;s:14:"document_start";i:1;a:0:{}i:2;i:0;}i:1;a:3:{i:0;s:6:"header";i:1;a:3:{i:0;s:14:"Technical note";i:1;i:1;i:2;i:1;}i:2;i:1;}i:2;a:3:{i:0;s:12:"section_open";i:1;a:1:{i:0;i:1;}i:2;i:1;}i:3;a:3:{i:0;s:13:"section_close";i:1;a:0:{}i:2;i:31;}i:4;a:3:{i:0;s:6:"header";i:1;a:3:{i:0;s:11:"Client side";i:1;i:2;i:2;i:31;}i:2;i:31;}i:5;a:3:{i:0;s:12:"section_open";i:1;a:1:{i:0;i:2;}i:2;i:31;}i:6;a:3:{i:0;s:13:"section_close";i:1;a:0:{}i:2;i:56;}i:7;a:3:{i:0;s:6:"header";i:1;a:3:{i:0;s:16:"Wizard component";i:1;i:3;i:2;i:56;}i:2;i:56;}i:8;a:3:{i:0;s:12:"section_open";i:1;a:1:{i:0;i:3;}i:2;i:56;}i:9;a:3:{i:0;s:13:"section_close";i:1;a:0:{}i:2;i:83;}i:10;a:3:{i:0;s:6:"header";i:1;a:3:{i:0;s:13:"How to use it";i:1;i:4;i:2;i:83;}i:2;i:83;}i:11;a:3:{i:0;s:12:"section_open";i:1;a:1:{i:0;i:4;}i:2;i:83;}i:12;a:3:{i:0;s:6:"p_open";i:1;a:0:{}i:2;i:83;}i:13;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:161:"I implement reusable wizard module using AngularJS directive feature and jQuery plugin. For adding wizard in the web interface is as simple as adding a html tag ";}i:2;i:105;}i:14;a:3:{i:0;s:11:"strong_open";i:1;a:0:{}i:2;i:266;}i:15;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:12:"<mth-wizard=";}i:2;i:268;}i:16;a:3:{i:0;s:18:"doublequoteopening";i:1;a:0:{}i:2;i:280;}i:17;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:9:"wizardOpt";}i:2;i:281;}i:18;a:3:{i:0;s:18:"doublequoteclosing";i:1;a:0:{}i:2;i:290;}i:19;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:14:"></mth-wizard>";}i:2;i:291;}i:20;a:3:{i:0;s:12:"strong_close";i:1;a:0:{}i:2;i:305;}i:21;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:2:". ";}i:2;i:307;}i:22;a:3:{i:0;s:18:"doublequoteopening";i:1;a:0:{}i:2;i:309;}i:23;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:9:"wizardOpt";}i:2;i:310;}i:24;a:3:{i:0;s:18:"doublequoteclosing";i:1;a:0:{}i:2;i:319;}i:25;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:92:" will contain the options for wizard that will be added. Define wizardOpt in scope as below
";}i:2;i:320;}i:26;a:3:{i:0;s:6:"plugin";i:1;a:4:{i:0;s:9:"code_code";i:1;a:4:{i:0;s:4:"code";i:1;s:17:"lang="javascript"";i:2;s:0:"";i:3;s:162:"
$scope.wizardOpt = {
  stepTitles: ["Step1", "Step2", "Step3"],
  stepForms: ["template.for.step1.html", "template.for.step2.html", "template.for.step3.html"]
}
";}i:2;i:3;i:3;s:181:" lang="javascript">
$scope.wizardOpt = {
  stepTitles: ["Step1", "Step2", "Step3"],
  stepForms: ["template.for.step1.html", "template.for.step2.html", "template.for.step3.html"]
}
";}i:2;i:417;}i:27;a:3:{i:0;s:7:"p_close";i:1;a:0:{}i:2;i:605;}i:28;a:3:{i:0;s:6:"p_open";i:1;a:0:{}i:2;i:605;}i:29;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:32:"In template.for.step1.html file
";}i:2;i:607;}i:30;a:3:{i:0;s:6:"plugin";i:1;a:4:{i:0;s:9:"code_code";i:1;a:4:{i:0;s:4:"code";i:1;s:11:"lang="HTML"";i:2;s:0:"";i:3;s:65:"
<div ng-controller="Step1Ctrl">
  <p> This is step 1</p>
</div>
";}i:2;i:3;i:3;s:78:" lang="HTML">
<div ng-controller="Step1Ctrl">
  <p> This is step 1</p>
</div>
";}i:2;i:644;}i:31;a:3:{i:0;s:7:"p_close";i:1;a:0:{}i:2;i:729;}i:32;a:3:{i:0;s:6:"p_open";i:1;a:0:{}i:2;i:729;}i:33;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:30:"and so on for other templates.";}i:2;i:731;}i:34;a:3:{i:0;s:7:"p_close";i:1;a:0:{}i:2;i:761;}i:35;a:3:{i:0;s:6:"p_open";i:1;a:0:{}i:2;i:761;}i:36;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:218:"For providing validation logic for each step in wizard. Just define controller that is associated with the template view and listen on $wizardChange event which will pass changeEvent, changeInfo arguments. For example,";}i:2;i:763;}i:37;a:3:{i:0;s:7:"p_close";i:1;a:0:{}i:2;i:981;}i:38;a:3:{i:0;s:6:"p_open";i:1;a:0:{}i:2;i:981;}i:39;a:3:{i:0;s:6:"plugin";i:1;a:4:{i:0;s:9:"code_code";i:1;a:4:{i:0;s:4:"code";i:1;s:17:"lang="javascript"";i:2;s:0:"";i:3;s:394:"
angular.controller('Step1Ctrl', ['$scope', function($scope) {
  $scope.$on("$wizardChange", function(e, changeEvent, changeInfo) {
    if (changeInfo.step === 1 && changeInfo.direction === "next") {
      // import validation logic here!!
      if (## not form is not valid ##)
        changeEvent.preventDefault();    // This will prevent users from going next step;        
    }
  });
}]);
";}i:2;i:3;i:3;s:413:" lang="javascript">
angular.controller('Step1Ctrl', ['$scope', function($scope) {
  $scope.$on("$wizardChange", function(e, changeEvent, changeInfo) {
    if (changeInfo.step === 1 && changeInfo.direction === "next") {
      // import validation logic here!!
      if (## not form is not valid ##)
        changeEvent.preventDefault();    // This will prevent users from going next step;        
    }
  });
}]);
";}i:2;i:988;}i:40;a:3:{i:0;s:7:"p_close";i:1;a:0:{}i:2;i:1408;}i:41;a:3:{i:0;s:6:"p_open";i:1;a:0:{}i:2;i:1408;}i:42;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:55:"It is also possible to add wizard in modal. Simply add ";}i:2;i:1410;}i:43;a:3:{i:0;s:7:"acronym";i:1;a:1:{i:0;s:4:"HTML";}i:2;i:1465;}i:44;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:74:" button tag with mth-modal-wizard and modal-opts attributes. For example, ";}i:2;i:1469;}i:45;a:3:{i:0;s:11:"strong_open";i:1;a:0:{}i:2;i:1543;}i:46;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:25:"<button mth-modal-wizard=";}i:2;i:1545;}i:47;a:3:{i:0;s:18:"doublequoteopening";i:1;a:0:{}i:2;i:1570;}i:48;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:10:"wizardOpts";}i:2;i:1571;}i:49;a:3:{i:0;s:18:"doublequoteclosing";i:1;a:0:{}i:2;i:1581;}i:50;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:12:" modal-opts=";}i:2;i:1582;}i:51;a:3:{i:0;s:18:"doublequoteopening";i:1;a:0:{}i:2;i:1594;}i:52;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:9:"modalOpts";}i:2;i:1595;}i:53;a:3:{i:0;s:18:"doublequoteclosing";i:1;a:0:{}i:2;i:1604;}i:54;a:3:{i:0;s:5:"cdata";i:1;a:1:{i:0;s:23:"> Open Wizard </button>";}i:2;i:1605;}i:55;a:3:{i:0;s:12:"strong_close";i:1;a:0:{}i:2;i:1628;}i:56;a:3:{i:0;s:7:"p_close";i:1;a:0:{}i:2;i:1630;}i:57;a:3:{i:0;s:13:"section_close";i:1;a:0:{}i:2;i:1632;}i:58;a:3:{i:0;s:6:"header";i:1;a:3:{i:0;s:12:"Logic behind";i:1;i:4;i:2;i:1632;}i:2;i:1632;}i:59;a:3:{i:0;s:12:"section_open";i:1;a:1:{i:0;i:4;}i:2;i:1632;}i:60;a:3:{i:0;s:13:"section_close";i:1;a:0:{}i:2;i:1654;}i:61;a:3:{i:0;s:12:"document_end";i:1;a:0:{}i:2;i:1654;}}