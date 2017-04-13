(function (window, angular) {
  'use strict';
  angular.module('RPGPlatinumApp')
    .controller('RPGPlatinum', [
      '$scope', '$location', '$http', '$mdPanel', '$log', 'Upload', '$mdToast', '$sce', '$routeParams', '$cookies',
      function ($scope, $location, $http, $mdPanel, $log, Upload, $mdToast, $sce, $routeParams, $cookies) {
        $scope.title = 'RPG Platinum';
        $scope.page = {
          now: 'datalist.html',
          last: null,
          config: angular.fromJson(document.querySelector('#page-config').value)
        };
        var autoSaveTimeout;
        var subTemplate = {name: '', level: 0, type: 'root', sub: []};
        $scope.rpg = {
          lists: [],
          detail: {},
          newRpgData: {
            breif: {
              title: '',
              description: '',
              coverFile: null
            },
            chapters: angular.copy(subTemplate),
            tempChapterName: ''
          },
          loading: false
        };
        $scope.progress = {
          save: function () {
            $http.post('actions/ajax.php', {
              action: 'save',
              progress: $scope.rpg.detail.progress,
              oid: $scope.rpg.detail.oid
            });
          },
          delaySave: function () {
            if ($scope.user.info.islogin) {
              clearTimeout(autoSaveTimeout);
              autoSaveTimeout = setTimeout(function () {
                $scope.progress.save();
              }, 10000);
            }
          },
          load: function () {
            $http.post('actions/ajax.php', {
                action: 'load',
                oid: $scope.rpg.detail.oid
              })
              .then(function (response) {
                var json = response.data;
                if (json.success) {
                  $scope.rpg.detail.progress = json.data.progress;
                } else {
                  main.toast(json.msg);
                }
              }, function (response) {
                main.toast('server error.');
              });
          }
        };

        $scope.changepage = function (page, p) {
          $scope.page.last = $scope.page.now;
          $scope.page.now = page;

          switch (page) {
            case 'newdata.html':
              $location.url('rpg/new');
              break;
            case 'datalist.html':
              $location.url('list');
              main.getListData();
              break;
            case 'datacontent.html':
              $location.url('rpg/' + p);
              main.getRpgData(p);
              break;
          }
        };

        $scope.user = {
          info: {
            username: '',
            password: '',
            uid: null,
            islogin: false,
            logining: false,
            msg: ''
          },
          login: function (check, close) {
            check = check || false;
            if (!check) {
              var req = {
                action: 'login',
                username: $scope.user.info.username,
                password: $scope.user.info.password
              };
            } else {
              var req = {
                action: 'logincheck'
              };
            }
            $scope.user.info.logining = true;
            $scope.user.info.msg = '';
            $http.post('actions/ajax.php', req)
              .then(function (response) {
                var json = response.data;
                if (json.success) {
                  $scope.user.info.uid = json.data.uid;
                  $scope.user.info.username = json.data.username;
                  $scope.user.info.islogin = true;
                  main.toast('登录成功。');
                  if (!check && /^\/rpg\/\d+$/i.test($location.path())) {
                    $log.log('Load progress.');
                    $scope.progress.load();
                  }
                  if (angular.isFunction(close)) {
                    close();
                  }
                } else {
                  $scope.user.info.msg = json.msg;
                }
                $scope.user.info.password = '';
                $scope.user.info.logining = false;
              }, function (response) {
                $scope.user.info.msg = 'server error.';
                $scope.user.info.islogin = false;
              });
          },
          logout: function () {
            $log.log('Logout.');
            $cookies.remove($scope.page.config.cookieSuffix + 'token');
            $scope.user.info = {
              username: '',
              password: '',
              uid: null,
              islogin: false,
              logining: false,
              msg: ''
            };
          },
          loginFrame: function (ev) {
            var position = $mdPanel.newPanelPosition()
              .relativeTo(ev.toElement)
              .addPanelPosition($mdPanel.xPosition.ALIGN_END, $mdPanel.yPosition.BELOW);
            var animate = $mdPanel.newPanelAnimation()
              .openFrom(ev.toElement)
              .withAnimation($mdPanel.animation.SCALE);
            var config = {
              animation: animate,
              attachTo: angular.element(document.body),
              controller: ['mdPanelRef',
                function (mdPanelRef) {
                  this.parent = $scope;
                  this.close = function () {
                    mdPanelRef.close();
                  }
                }],
              controllerAs: 'LoginPanelCtrl',
              template: '' +
              '<div md-whiteframe="4" class="login-panel-frame">' +
              '  <md-input-container class="md-block hide-error-msg">' +
              '    <label>Username</label>' +
              '    <input ng-model="LoginPanelCtrl.parent.user.info.username" type="text">' +
              '  </md-input-container>' +
              '  <md-input-container class="md-block hide-error-msg">' +
              '    <label>Password</label>' +
              '    <input ng-model="LoginPanelCtrl.parent.user.info.password" type="password">' +
              '  </md-input-container>' +
              '  <div class="login-error-message" ng-show="LoginPanelCtrl.parent.user.info.msg">' +
              '    <span md-colors="{color: \'warn\'}" ' +
              '          ng-bind="LoginPanelCtrl.parent.user.info.msg"></span>' +
              '  </div>' +
              '  <div layout="row">' +
              '    <md-button ng-click="LoginPanelCtrl.close()" ' +
              '               ng-disabled="LoginPanelCtrl.parent.user.info.logining">cancel</md-button>' +
              '    <md-button class="md-raised md-primary"' +
              '               ng-click="LoginPanelCtrl.parent.user.login(false, LoginPanelCtrl.close)"' +
              '               ng-disabled="LoginPanelCtrl.parent.user.info.logining">' +
              '    login' +
              '    </md-button>' +
              '  </div>' +
              '</div>',
              panelClass: 'login-panel',
              position: position,
              zIndex: 2,
              clickOutsideToClose: false,
              escapeToClose: true,
              focusOnOpen: false,
              hasBackdrop: true,
              disableParentScroll: true
            };
            $mdPanel.open(config);
          }
        };

        $scope.newrpg = {
          addchapter: function () {
            if ($scope.rpg.newRpgData.chapters[$scope.rpg.newRpgData.chapters.length - 1].name !== '') {
              $scope.rpg.newRpgData.chapters.push(angular.copy(subTemplate));
            }
          },
          addsub: function (parent) {
            try {
              if (parent.level < 3) {
                var sub = angular.copy(subTemplate);
                sub.level = parent.level + 1;
                parent.type = 'cat';
                parent.sub.push(sub);
              }
            } catch (e) {
              console.log(e.message);
              console.log(parent);
            }
          },
          removesub: function (parent, self) {
            if (parent.type === 'item') {
              parent.sub = [];
            } else {
              var index = parent.sub.indexOf(self);
              parent.sub.splice(index, 1);
            }
            if (!parent.sub.length) {
              parent.type = 'root';
            }
          },
          checksub: function (parent, self) {
            if (self.name === '') {
              this.removesub(parent, self);
            }
          },
          additem: function (parent) {
            parent.type = 'item';
          },
          submitrpg: function () {
            var req = {
              url: 'actions/ajax.php',
              method: 'POST',
              data: {
                action: 'newrpg',
                rpgdata: {
                  breif: {
                    title: $scope.rpg.newRpgData.breif.title,
                    description: $scope.rpg.newRpgData.breif.description
                  },
                  chapters: $scope.rpg.newRpgData.chapters
                },
                cover: $scope.rpg.newRpgData.breif.coverFile
              }
            };
            $scope.rpg.loading = true;
            Upload.upload(req)
              .then(function (response) {
                var json = response.data;
                if (json.success) {
                  $location.url('list');
                } else {
                  main.toast(json.msg);
                }
                $scope.rpg.loading = false;
              }, function (response) {
                main.toast('server error.');
                $scope.rpg.loading = false;
              });
          }
        };

        var main = {
          getRpgData: function (oid) {
            $http.post('actions/ajax.php', {action: 'rpgdetail', oid: oid})
              .then(function (response) {
                var json = response.data;
                $scope.rpg.detail = json.data[0];
              });
          },
          getListData: function () {
            $http.post('actions/ajax.php', {action: 'rpglist'})
              .then(function (response) {
                var json = response.data;
                $scope.rpg.lists = json.data.orderList;
              });
          },
          init: function () {
            $scope.user.login(true);
            if (/^\/list$/i.test($location.path()) || $location.path() == '') {
              main.getListData();
            }
            if (/^\/rpg\/\d+$/i.test($location.path())) {
              main.getRpgData($location.path().substr(5));
            }
          },
          toast: function (msg, delay) {
            delay = delay || '3000';
            $mdToast.show(
              $mdToast.simple()
                .textContent($sce.trustAsHtml(msg))
                .position('bottom left')
                .hideDelay(delay)
            );
          }
        };

        main.init();
      }]);
})(window, angular)
;;
