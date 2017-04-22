(function (window, angular) {
  'use strict';
  angular.module('RPGPlatinumApp')
    .controller('RPGPlatinum', [
      '$rootScope', '$scope', '$location', '$http', '$mdPanel', '$log', 'Upload', '$mdToast', '$sce', '$routeParams', '$cookies', 'localStorageService', '$mdDialog',
      function ($rootScope, $scope, $location, $http, $mdPanel, $log, Upload, $mdToast, $sce, $routeParams, $cookies, localStorageService, $mdDialog) {
        $rootScope.$on('$locationChangeStart', function (event) {
          // Check if there is a dialog active
          if (angular.element(document).find('md-dialog').length > 0) {
            event.preventDefault(); // Prevent route from changing
            $mdDialog.cancel();  // Cancel the active dialog
          }
        });
        $rootScope.$on('$routeChangeStart', function (event, next) {
          switch (next.templateUrl) {
            case 'newdata.html':
              if ($scope.user.info.islogin) {
                main.getLocalData();
              } else {
                $location.url('list');
              }
              break;
            case 'datalist.html':
              if (!!next.params.query) {
                $scope.rpg.listsQuery = next.params.query.replace('+', ' ');
              }
              main.getListData();
              break;
            case 'datacontent.html':
              main.getRpgData(next.params.oid);
              break;
            case 'userdata.html':
              main.getUserData(next.params.username);
              break;
          }
        });

        $scope.title = 'RPG Platinum';
        $scope.page = {
          now: 'datalist.html',
          last: null,
          config: angular.fromJson(document.querySelector('#page-config').value),
          loadingCircular: false
        };
        var autoSaveTimeout;
        var subTemplate = {name: '', level: 0, type: 'root', sub: []};
        var newRpgData = {
          breif: {
            title: '',
            description: '',
            coverFile: null
          },
          chapters: angular.copy(subTemplate),
          tempChapterName: ''
        };
        $scope.rpg = {
          lists: [],
          listsPager: null,
          listsQuery: '',
          listsLoadMore: function () {
            main.getListData();
          },
          detail: {},
          newRpgData: angular.copy(newRpgData),
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
            } else {
              localStorageService.set('progress', $scope.rpg.detail.progress);
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
                var json = response.data;
                if (json.msg) {
                  main.toast(json.msg);
                } else {
                  main.toast('server error.');
                }
              });
          }
        };

        $scope.changepage = function (page, p) {
          $scope.page.last = $scope.page.now;
          $scope.page.now = page;

          switch (page) {
            case 'newdata.html':
              if ($scope.user.info.islogin) {
                $location.url('rpg/new');
              } else {
                $location.url('list');
              }
              break;
            case 'datalist.html':
              if (!!p) {
                $location.url('list/' + p.replace(' ', '+'));
              } else {
                $location.url('list');
              }
              break;
            case 'datacontent.html':
              $location.url('rpg/' + p);
              break;
            case 'userdata.html':
              $location.url('user/' + p);
              break;
          }
        };

        $scope.goIndex = function (ev) {
          ev.stopPropagation();
          $scope.rpg.listsQuery = '';
          $scope.changepage('datalist.html');
        };

        $scope.search = function (ev) {
          var key = ev.keyCode;
          if (key === 13 && !!$scope.rpg.listsQuery) {
            $scope.changepage('datalist.html', $scope.rpg.listsQuery);
          }
        };

        $scope.gotop = function () {
          var m = document.querySelector('.main-container');
          var timer = setInterval(function () {
            m.scrollTop -= Math.ceil((m.scrollTop + m.scrollTop) * 0.1);
            if (m.scrollTop == 0)
              clearInterval(timer);
          }, 10);
        };

        $scope.clearNewRpgData = function () {
          $scope.rpg.newRpgData = angular.copy(newRpgData);
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
          userMenu: function ($mdMenu, ev) {
            ev.stopPropagation();
            $mdMenu.open(ev);
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
                var json = response.data;
                if (json.msg) {
                  main.toast(json.msg);
                } else {
                  main.toast('server error.');
                }
                $scope.user.info.islogin = false;
              });
          },
          logout: function (ev) {
            ev.stopPropagation();
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
            ev.stopPropagation();
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
                    $scope.user.info.logining = false;
                    $scope.user.info.msg = '';
                    mdPanelRef.close();
                  }
                }],
              controllerAs: 'LoginPanelCtrl',
              template: '' +
              '<div md-whiteframe="4" class="login-panel-frame">' +
              '  <md-input-container class="md-block hide-error-msg">' +
              '    <label>用户名</label>' +
              '    <input ng-model="LoginPanelCtrl.parent.user.info.username" type="text">' +
              '  </md-input-container>' +
              '  <md-input-container class="md-block hide-error-msg">' +
              '    <label>密码</label>' +
              '    <input ng-model="LoginPanelCtrl.parent.user.info.password" type="password">' +
              '  </md-input-container>' +
              '  <div class="login-error-message" ng-show="LoginPanelCtrl.parent.user.info.msg">' +
              '    <span md-colors="{color: \'warn\'}" ' +
              '          ng-bind="LoginPanelCtrl.parent.user.info.msg"></span>' +
              '  </div>' +
              '  <div layout="row">' +
              '    <md-button ng-click="LoginPanelCtrl.close()" ' +
              '               ng-disabled="LoginPanelCtrl.parent.user.info.logining">取消</md-button>' +
              '    <md-button class="md-raised md-primary"' +
              '               ng-click="LoginPanelCtrl.parent.user.login(false, LoginPanelCtrl.close)"' +
              '               ng-disabled="LoginPanelCtrl.parent.user.info.logining">' +
              '    登入' +
              '    </md-button>' +
              '  </div>' +
              '</div>',
              panelClass: 'login-panel',
              position: position,
              zIndex: 2,
              clickOutsideToClose: false,
              escapeToClose: true,
              focusOnOpen: false,
              hasBackdrop: false,
              disableParentScroll: true
            };
            $mdPanel.open(config);
          },
          registerPanel: function (ev) {
            $mdDialog.show({
              controller: ['$mdDialog',
                function ($mdDialog) {
                  this.parent = $scope;
                  this.registerInfo = {
                    username: '',
                    password: '',
                    password2: ''
                  };
                  this.close = function () {
                    $scope.user.info.logining = false;
                    $scope.user.info.msg = '';
                    $mdDialog.hide();
                  };
                  this.register = function () {
                    if (!/^[A-Za-z_][A-Za-z0-9_]+$/.test(this.registerInfo.username)) {
                      $scope.user.info.msg = '用户名只允许使用字母、数字、下划线且不以数字开头。';
                      return false;
                    }
                    if (this.registerInfo.password === ''
                      || this.registerInfo.password !== this.registerInfo.password2) {
                      $scope.user.info.msg = '两次密码不一样。';
                      return false;
                    }
                    var req = {
                      action: 'register',
                      username: this.registerInfo.username,
                      password: this.registerInfo.password
                    };
                    $scope.user.info.logining = true;
                    $http.post('actions/ajax.php', req)
                      .then(function (response) {
                        var json = response.data;
                        if (json.success) {
                          $scope.user.info.username = req.username;
                          $scope.user.info.password = req.password;
                          $scope.user.login();
                          $mdDialog.hide();
                        } else {
                          $scope.user.info.msg = json.msg;
                        }
                        $scope.user.info.logining = false;
                      }, function (response) {
                        var json = response.data;
                        if (json.msg) {
                          main.toast(json.msg);
                        } else {
                          main.toast('server error.');
                        }
                        $scope.user.info.logining = false;
                      });
                  };
                }],
              controllerAs: 'registerPanel',
              templateUrl: 'registerPanel.html',
              parent: angular.element(document.querySelector('.main-container')),
              targetEvent: ev,
              clickOutsideToClose: false,
              fullscreen: true
            });
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
                localStorageService.set('newRpgData', $scope.rpg.newRpgData);
              }
            } catch (e) {
              $log.log(e.message);
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
                  localStorageService.remove('newRpgData');
                  $scope.changepage('datalist.html');
                } else {
                  main.toast(json.msg);
                }
                $scope.rpg.loading = false;
              }, function (response) {
                var json = response.data;
                if (json.msg) {
                  main.toast(json.msg);
                } else {
                  main.toast('server error.');
                }
                $scope.rpg.loading = false;
              });
          }
        };

        var main = {
          getRpgData: function (oid) {
            $scope.page.loadingCircular = true;
            $http.post('actions/ajax.php', {action: 'rpgdetail', oid: oid})
              .then(function (response) {
                $scope.page.loadingCircular = false;
                var json = response.data;
                $scope.rpg.detail = json.data[0];
                main.getLocalProgress();
              });
          },
          getListData: function () {
            $scope.rpg.listsPager = null;
            $scope.page.loadingCircular = true;
            var req = {action: 'rpglist'};
            if ($scope.rpg.listsPager && $scope.rpg.listsPager.page) {
              req['page'] = $scope.rpg.listsPager.page + 1;
            }
            if (!!$scope.rpg.listsQuery) {
              req['query'] = $scope.rpg.listsQuery;
            }
            $http.post('actions/ajax.php', req)
              .then(function (response) {
                $scope.page.loadingCircular = false;
                var json = response.data;
                $scope.rpg.listsPager = json.data.pager;
                if ($scope.rpg.listsPager.page !== 1) {
                  angular.forEach(json.data.orderList, function (rpg) {
                    $scope.rpg.lists.push(rpg);
                  });
                } else {
                  $scope.rpg.lists = json.data.orderList;
                }
              }, function (response) {
                $scope.page.loadingCircular = false;
                var json = response.data;
                if (json.msg) {
                  main.toast(json.msg);
                } else {
                  main.toast('server error.');
                }
              });
          },
          getLocalData: function () {
            if (!!localStorageService.get('newRpgData')) {
              $scope.rpg.newRpgData = localStorageService.get('newRpgData');
              main.toast('发现未提交的数据并已加载。');
            }
          },
          getLocalProgress: function () {
            if (!!$scope.user.islogin && !!localStorageService.get('progress')) {
              $scope.rpg.detail.progress = localStorageService.get('progress');
            }
          },
          getUserData: function (username) {
            $scope.page.loadingCircular = true;
            $scope.userdetail = null;
            $http.post('actions/ajax.php',
              {
                action: 'userdetail',
                username: username
              })
              .then(function (response) {
                $scope.page.loadingCircular = false;
                var json = response.data;
                if (json.success) {
                  $scope.userdetail = json.data;
                } else {
                  main.toast(json.msg);
                }
              }, function (response) {
                $scope.page.loadingCircular = false;
                var json = response.data;
                if (json.msg) {
                  main.toast(json.msg);
                } else {
                  main.toast('server error.');
                }
              });
          },
          init: function () {
            $scope.user.login(true);
            $scope.pageloaded = true;
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
;
;
