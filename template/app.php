<!DOCTYPE html>
<html lang="zh" ng-app="RPGPlatinumApp" ng-controller="RPGPlatinum">
<head>
  <title ng-bind="title">RPG Platinum</title>
  <?php include(ROOT . 'template/meta.php'); ?>

  <base href="/">
</head>
<body class="app-body" layout="row">
<div ng-hide="pageloaded"
     style="position:fixed;top:0;bottom:0;left:0;right:0;z-index:201;background-color: #fff;">
</div>
<input type="hidden" id="page-config"
       value="<?= htmlspecialchars(json_encode($page_config, JSON_UNESCAPED_UNICODE)) ?>">
<div role="main" layout="column" tabindex="-1" flex>
  <md-toolbar md-whiteframe="1" class="navbar">
    <div class="md-toolbar-tools" ng-click="gotop()">
      <div class="navbar-logo" ng-click="goIndex($event)">
        <img src="asset/img/takara.svg">
      </div>
      <h2>
        <span>{{title}}</span>
      </h2>
      <span flex></span>
      <md-button class="md-primary login-btn"
                 ng-click="user.loginFrame($event)"
                 ng-disabled="user.info.logining"
                 ng-if="!user.info.uid">
        登入
        <md-progress-circular ng-show="user.info.logining" md-mode="indeterminate"
                              md-diameter="20"></md-progress-circular>
      </md-button>
      <md-button class="md-raised md-accent"
                 ng-if="!user.info.uid"
                 ng-click="user.registerPanel($event)">注册
      </md-button>
      <md-menu md-offset="0 32" md-position-mode="target-right target" ng-if="user.info.uid">
        <md-button class="md-icon-button" ng-click="user.userMenu($mdMenu, $event)">
          <md-icon md-menu-origin md-svg-icon="more_vert" aria-label="user menu"></md-icon>
        </md-button>
        <md-menu-content width="4">
          <md-menu-item>
            <md-button disabled="disabled">{{ user.info.username }}</md-button>
          </md-menu-item>
          <md-menu-divider></md-menu-divider>
          <md-menu-item>
            <md-button ng-href="#!/user/{{user.info.username}}">个人信息</md-button>
          </md-menu-item>
          <md-menu-item>
            <md-button ng-click="user.logout($event)">退出</md-button>
          </md-menu-item>
        </md-menu-content>
      </md-menu>
    </div>
  </md-toolbar>
  <md-content class="main-container" flex md-scroll-y layout="column">
    <!--div ng-include="page.now" flex="noshrink"></div-->
    <div class="main-container-view" ng-view flex="noshrink"></div>
    <md-button class="page-fab md-fab md-fab-bottom-right scrolling md-warn"
               ng-click="changepage('newdata.html')"
               ng-if="user.info.uid && page.now !== 'newdata.html'">
      <md-icon md-svg-icon="add" aria-label="add"></md-icon>
    </md-button>
  </md-content>
</div>
<div class="loading-circular" layout="row" layout-align="center center" ng-show="page.loadingCircular">
  <md-progress-circular
    class="md-accent"
    md-diameter="40"></md-progress-circular>
</div>

<script src="asset/lib/angularjs/1.6.0/angular.min.js"></script>
<script src="asset/lib/angularjs/1.6.0/angular-animate.min.js"></script>
<script src="asset/lib/angularjs/1.6.0/angular-aria.min.js"></script>
<script src="asset/lib/angularjs/1.6.0/angular-cookies.min.js"></script>
<script src="asset/lib/angularjs/1.6.0/angular-route.min.js"></script>
<script src="asset/lib/angularjs/1.6.0/i18n/angular-locale_zh.js"></script>
<script src="asset/lib/angular.material/1.1.4/angular-material.min.js"></script>
<script src="asset/lib/angular-local-storage/0.5.2/angular-local-storage.min.js"></script>
<script src="asset/lib/ng-file-upload/12.2.13/ng-file-upload.min.js"></script>
<link rel="stylesheet" href="asset/lib/angular.material/1.1.4/angular-material.min.css">

<?php if (usesourcecode) : ?>
  <script src="asset/js/app.js<?= '?v=' . version ?>"></script>
  <script src="asset/js/controllers/main.js<?= '?v=' . version ?>"></script>
<link rel="Stylesheet" type="text/css" href="asset/css/main.css<?= '?v=' . version ?>">
<?php else: ?>
  <script src="asset/dist/rpgplatinum.min.js<?= '?v=' . version ?>"></script>
<link rel="Stylesheet" type="text/css" href="asset/dist/rpgplatinum.min.css<?= '?v=' . version ?>">
<?php endif; ?>

<script type="text/ng-template" id="datalist.html">
  <div layout="row" layout-align="center center">
    <md-input-container class="search-bar hide-error-msg md-block">
      <label>搜索</label>
      <input ng-model="rpg.listsQuery" ng-keyup="search($event)">
    </md-input-container>
  </div>
  <div layout="row" layout-wrap layout-align="start start" layout-align-xs="center start" class="rpg-list">
    <div class="md-display-1" ng-if="!page.loadingCircular && !rpg.lists.length" flex="100">
      真遗憾，什么都没有。
    </div>
    <div class="breif-card-wrapper" ng-repeat="list in rpg.lists" flex-xs="100">
      <div md-whiteframe="2" class="breif-card" layout-xs="row" layout-gt-xs="column">
        <div class="cover column"
             hide-xs show-gt-xs layout="row"
             layout-align="center center"
             ng-click="changepage('datacontent.html', list.oid)">
          <img class="cover-image" ng-src="{{list.cover.path || 'asset/img/takara.svg'}}">
        </div>
        <div class="cover row"
             hide-gt-xs show-xs layout="row"
             layout-align="center center"
             ng-click="changepage('datacontent.html', list.oid)">
          <img class="cover-image" ng-src="{{list.cover.path || 'asset/img/takara.svg'}}">
        </div>
        <div class="details">
          <a class="click-target" href="javascript:;"></a>
          <div class="title w140" hide-xs show-gt-xs>
            <a href="javascript:;" title="{{list.title}}"
               ng-click="changepage('datacontent.html', list.oid)">
              {{list.title}}
            </a>
          </div>
          <div class="title" hide-gt-xs show-xs>
            <a href="javascript:;" title="{{list.title}}"
               ng-click="changepage('datacontent.html', list.oid)">
              {{list.title}}
            </a>
          </div>
          <div class="author-container">
            <a class="author"
               ng-href="#!/user/{{list.author.username}}"
               title="{{list.author.username}}">{{list.author.username}}</a>
          </div>
          <div class="description w160" hide-xs show-gt-xs>
            <md-tooltip>{{list.description}}</md-tooltip>
            {{list.description}}
          </div>
          <div class="description" hide-gt-xs show-xs>
            <md-tooltip>{{list.description}}</md-tooltip>
            {{list.description}}
          </div>
        </div>
      </div>
    </div>
  </div>
  <div layout="row" layout-align="center center">
    <md-button class="md-raised md-primary"
               ng-click="rpg.listsLoadMore()"
               ng-disabled="page.loadingCircular"
               ng-if="rpg.listsPager.page * rpg.listsPager.pagesize < rpg.listsPager.count">更多
    </md-button>
  </div>
</script>
<script type="text/ng-template" id="datacontent.html">
  <div class="rpg">
    <md-card class="rpg-breif" ng-if="rpg.detail.oid">
      <md-card-content layout="row" layout-xs="column">
        <div class="rpg-breif-left" flex-gt-xs="50">
          <h3>{{rpg.detail.title}}</h3>
          <p>作者:<span>{{rpg.detail.author.username}}</span></p>
          <p>日期:<span>{{rpg.detail.create_time | date:'longDate'}}</span></p>
          <p>{{rpg.detail.description}}</p>
        </div>
      </md-card-content>
    </md-card>
    <md-card class="rpg-body"
             ng-repeat="chapter in rpg.detail.chapters.sub">
      <md-toolbar class="thin">
        <div class="md-toolbar-tools">
          <span class="md-headline">{{chapter.name}}</span>
        </div>
      </md-toolbar>
      <md-card-content>
        <div class="rpg-listblock">
          <dl ng-repeat="sub_level2 in chapter.sub">
            <dt class="md-primary md-no-sticky">{{sub_level2.name}}</dt>
            <dd class="level3-wrap">
              <!-- type: cat -->
              <dl ng-repeat-start="sub_level3 in sub_level2.sub" ng-if="sub_level3.type == 'cat'">
            <dt class="md-primary md-no-sticky"
                ng-if="sub_level3.type == 'cat'">{{sub_level3.name}}
            </dt>
            <dd ng-if="sub_level3.type == 'cat'">
              <md-checkbox ng-repeat="item in sub_level3.sub"
                           class="md-warn"
                           ng-model="rpg.detail.progress[item.oiid]"
                           ng-change="progress.delaySave()">
                {{item.name}}
              </md-checkbox>
            </dd>
            <md-divider ng-if="sub_level2.sub.length > $index + 1"></md-divider>
          </dl>
          <!-- end type: cat -->
          <!-- type: item -->
          <md-checkbox ng-repeat-end ng-if="sub_level3.type == 'item'"
                       class="md-warn"
                       ng-model="rpg.detail.progress[sub_level3.oiid]"
                       ng-change="progress.delaySave()">
            {{sub_level3.name}}
          </md-checkbox>
          <!-- end type: item -->
          </dd>
          </dl>
        </div>
      </md-card-content>
    </md-card>
  </div>
</script>
<script type="text/ng-template" id="newdata.html">
  <form class="rpg new-rpg">
    <md-card class="rpg-breif">
      <md-card-content layout="row" layout-xs="column">
        <div class="rpg-breif-left" flex-gt-xs="50">
          <md-input-container class="md-block hide-error-msg">
            <label>列表标题</label>
            <input ng-model="rpg.newRpgData.breif.title">
          </md-input-container>
          <md-input-container class="md-block hide-error-msg">
            <label>列表描述</label>
            <textarea ng-model="rpg.newRpgData.breif.description" md-maxlength="150" rows="3"></textarea>
          </md-input-container>
        </div>
        <div class="rpg-breif-right" flex-gt-xs="50">
          <img class="cover-preview" ngf-src="rpg.newRpgData.breif.coverFile">
          <div class="set-cover-btns">
            <md-button class="md-fab md-mini md-raised md-primary"
                       ng-if="!rpg.newRpgData.breif.coverFile"
                       ngf-select
                       accept="image/*"
                       ng-model="rpg.newRpgData.breif.coverFile">
              <md-icon md-svg-icon="file_upload" aria-label="upload"></md-icon>
            </md-button>
            <md-button class="md-fab md-mini md-raised"
                       ng-if="!rpg.newRpgData.breif.coverFile"
                       ng-click="">
              <md-icon md-svg-icon="cloud" aria-label="select"></md-icon>
            </md-button>
            <md-button class="md-fab md-mini md-raised"
                       ng-if="rpg.newRpgData.breif.coverFile"
                       ng-click="(rpg.newRpgData.breif.coverFile = null)">
              <md-icon md-svg-icon="close" aria-label="close"></md-icon>
            </md-button>
          </div>
        </div>
      </md-card-content>
    </md-card>
    <md-card class="rpg-body"
             ng-repeat="chapter in rpg.newRpgData.chapters.sub">
      <md-card-title>
        <md-cart-title-text class="name-line">
          <md-input-container class="hide-error-msg md-headline">
            <label>章名</label>
            <input ng-model="chapter.name">
          </md-input-container>
          <md-button class="md-icon-button"
                     ng-click="newrpg.removesub(rpg.newRpgData.chapters, chapter)">
            <md-icon md-svg-icon="delete" aria-label="remove"></md-icon>
          </md-button>
        </md-cart-title-text>
      </md-card-title>
      <md-card-content>
        <div class="rpg-listblock">
          <div ng-repeat="sub_level2 in chapter.sub">
            <div ng-if="chapter.type === 'cat'">
              <div class="name-line">
                <md-input-container class="hide-error-msg">
                  <label>子标题</label>
                  <input ng-model="sub_level2.name">
                </md-input-container>
                <md-button class="md-icon-button"
                           ng-click="newrpg.removesub(chapter, sub_level2)">
                  <md-icon md-svg-icon="delete" aria-label="remove"></md-icon>
                </md-button>
              </div>
              <div class="rpg-listblock">
                <div ng-repeat="sub_level3 in sub_level2.sub">
                  <div ng-if="sub_level2.type === 'cat'">
                    <div class="name-line">
                      <md-input-container class="hide-error-msg">
                        <label>子标题</label>
                        <input ng-model="sub_level3.name">
                      </md-input-container>
                      <md-button class="md-icon-button"
                                 ng-click="newrpg.removesub(sub_level2, sub_level3)">
                        <md-icon md-svg-icon="delete" aria-label="remove"></md-icon>
                      </md-button>
                    </div>
                    <div class="rpg-listblock">
                      <div ng-if="sub_level3.type === 'item'">
                        <div layout="row">
                          <md-chips ng-model="sub_level3.sub" md-removable="true"
                                    md-separator-keys="[13,187,188]"
                                    placeholder="值1,值2..."
                                    md-enable-chip-edit="true"
                                    flex></md-chips>
                          <md-button class="md-icon-button"
                                     ng-click="newrpg.removesub(sub_level3)">
                            <md-icon md-svg-icon="delete" aria-label="remove"></md-icon>
                          </md-button>
                        </div>
                      </div>
                    </div>
                    <div class="rpg-body-actions" layout="row">
                      <md-button class="md-raised md-accent"
                                 ng-click="newrpg.additem(sub_level3)"
                                 ng-if="sub_level3.type === 'root'">
                        添加子项
                      </md-button>
                    </div>
                  </div>
                </div>
                <div ng-if="sub_level2.type === 'item'">
                  <div layout="row">
                    <md-chips ng-model="sub_level2.sub" md-removable="true"
                              md-separator-keys="[13,187,188]"
                              placeholder="值1,值2..."
                              md-enable-chip-edit="true"
                              flex></md-chips>
                    <md-button class="md-icon-button"
                               ng-click="newrpg.removesub(sub_level2)">
                      <md-icon md-svg-icon="delete" aria-label="remove"></md-icon>
                    </md-button>
                  </div>
                </div>
              </div>
              <div class="rpg-body-actions" layout="row">
                <md-button class="md-raised md-accent"
                           ng-click="newrpg.addsub(sub_level2)"
                           ng-if="sub_level2.type !== 'item'">
                  添加分类
                </md-button>
                <md-button class="md-raised md-accent"
                           ng-click="newrpg.additem(sub_level2)"
                           ng-if="sub_level2.type === 'root'">
                  添加子项
                </md-button>
              </div>
            </div>
          </div>
        </div>
        <div class="rpg-body-actions" layout="row">
          <md-button class="md-raised md-accent"
                     ng-click="newrpg.addsub(chapter)"
                     ng-if="chapter.type !== 'item'">
            添加分类
          </md-button>
        </div>
      </md-card-content>
    </md-card>
    <md-card class="rpg-body">
      <md-card-title>
        <md-card-title-text>
          <span class="md-headline">添加一章</span>
          <span class="md-subhead">另起一块或者保存当前内容。</span>
        </md-card-title-text>
        <md-card-actions layout="row" layout-align="end center">
          <md-button class="md-primary" ng-click="newrpg.addsub(rpg.newRpgData.chapters)">添加一章</md-button>
          <md-button class="md-primary md-raised" ng-click="newrpg.submitrpg()">保存</md-button>
        </md-card-actions>
    </md-card>
    <i>&nbsp;</i>
  </form>
</script>
<script type="text/ng-template" id="userdata.html">
  <div class="rpg">
    <div md-whiteframe="2" layout-padding ng-if="userdetail">
      <strong class="md-headline">{{userdetail.username}}</strong>
      <p><span ng-bind="userdetail.create_time * 1000 | date:'y-M-d'"></span> 加入</p>
    </div>
    <section md-whiteframe="2" class="user-created" ng-if="userdetail.order_count">
      <md-list flex>
        <md-list-item>
          我的列表(<span ng-bind="userdetail.order_count"></span>)
        </md-list-item>
        <md-divider ng-repeat-start="item in userdetail.created"></md-divider>
        <md-list-item class="md-2-line" ng-repeat-end>
          <img ng-src="{{item.cover.path || 'asset/img/takara.svg'}}" class="md-avatar">
          <div class="md-list-item-text">
            <h3>{{item.title}}</h3>
            <h4>{{item.create_time | date:'longDate'}}</h4>
          </div>
          <md-button class="md-secondary md-raised md-primary" ng-href="#!/rpg/{{item.oid}}">查看</md-button>
          <md-icon class="md-secondary"
                   ng-click="user.deleteRpg(item)"
                   aria-label="delete"
                   md-svg-icon="close"></md-icon>
        </md-list-item>
      </md-list>
    </section>
  </div>
</script>
<script type="text/ng-template" id="registerPanel.html">
  <md-dialog class="register-panel" aria-label="register">
    <md-toolbar>
      <div class="md-toolbar-tools">
        <h2>用户注册</h2>
        <span flex></span>
        <md-button class="md-icon-button" ng-click="registerPanel.close()">
          <md-icon md-svg-icon="close" aria-label="close"></md-icon>
        </md-button>
      </div>
    </md-toolbar>
    <md-dialog-content>
      <div class="md-dialog-content">
        <md-input-container class="hide-error-msg md-block">
          <label>用户名</label>
          <input placeholder="字母、数字、下划线且不以数字开头"
                 maxlength="16"
                 ng-model="registerPanel.registerInfo.username">
        </md-input-container>
        <md-input-container class="hide-error-msg md-block">
          <label>密码</label>
          <input maxlength="64"
                 type="password"
                 ng-model="registerPanel.registerInfo.password">
        </md-input-container>
        <md-input-container class="hide-error-msg md-block">
          <label>确认密码</label>
          <input maxlength="64"
                 type="password"
                 ng-model="registerPanel.registerInfo.password2">
        </md-input-container>
        <div ng-if="!!registerPanel.parent.user.info.msg" md-colors="{color: 'default-red'}">
          {{registerPanel.parent.user.info.msg}}
        </div>
      </div>
    </md-dialog-content>
    <md-dialog-actions>
      <span flex></span>
      <md-button class="md-primary" ng-click="registerPanel.close()">放弃</md-button>
      <md-button class="md-raised md-primary"
                 ng-disabled="registerPanel.parent.user.info.logining"
                 ng-click="registerPanel.register()">注册
      </md-button>
    </md-dialog-actions>
  </md-dialog>
</script>
</body>
</html>
