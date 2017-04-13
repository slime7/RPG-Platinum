(function (window, angular) {
  'use strict';
  angular.module('RPGPlatinumApp', [
      'ngRoute', 'ngAnimate', 'ngCookies',
      'LocalStorageModule', 'ngMaterial', 'ngFileUpload'
    ])
    .config(['$mdThemingProvider', '$mdIconProvider', '$routeProvider', '$locationProvider',
      function ($mdThemingProvider, $mdIconProvider, $routeProvider, $locationProvider) {
      $mdThemingProvider.theme('default')
        .primaryPalette('blue')
        .accentPalette('orange')
        .warnPalette('red');

        $mdIconProvider.defaultIconSet('asset/material-icons/default-set.svg', 24);

        $routeProvider
          .when('/list', {
            templateUrl: 'datalist.html',
          })
          .when('/rpg/new', {
            templateUrl: 'newdata.html',
          })
          .when('/rpg/:oid', {
            templateUrl: 'datacontent.html',
          })
          .otherwise({
            redirectTo: '/list'
          });

        $locationProvider
          .html5Mode(false)
          .hashPrefix('!');
    }]);

  if (!Array.prototype.find) {
    Array.prototype.find = function (predicate) {
      if (this == null) {
        throw new TypeError('Array.prototype.find called on null or undefined');
      }
      if (typeof predicate !== 'function') {
        throw new TypeError('predicate must be a function');
      }
      var list = Object(this);
      var length = list.length >>> 0;
      var thisArg = arguments[1];
      var value;

      for (var i = 0; i < length; i++) {
        value = list[i];
        if (predicate.call(thisArg, value, i, list)) {
          return value;
        }
      }
      return undefined;
    };
  }

  window.ObjectSize = function(obj) {
    var size = 0, key;
    for (key in obj) {
      if (obj.hasOwnProperty(key)) size++;
    }
    return size;
  };
})(window, angular)
;;
