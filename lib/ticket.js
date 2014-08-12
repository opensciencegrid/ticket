//var app = angular.module('app', ['ui.bootstrap']);
var app = angular.module('app', []);

app.directive('ngConfirmClick', [
    function(){
        return {
            priority: 1, //make sure this directive gets processed first
            terminal: true, //don't let angular run ng-click
            link: function (scope, element, attr) {
                var msg = attr.ngConfirmClick || "Are you sure?";
                var clickAction = attr.ngClick;
                element.bind('click',function (event) {
                    $(".dropdown").removeClass("open");
                    if ( window.confirm(msg) ) {
                        scope.$eval(clickAction)
                        scope.$apply(); //scope application doesn't happen for some reason
                    }
                });
            }
        };
}])

$(function() {
    //translate zend validation error message to bootstrap
    $(".errors").addClass("alert").addClass("alert-error");
});

