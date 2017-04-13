<!DOCTYPE html>
<html lang="zh" ng-app="RPGPlatinumApp" ng-controller="RPGPlatinum">
<head>
  <title>{{title}}</title>
  <?php include(ROOT . 'template/meta.php'); ?>

  <script
    src="https://cdn.jsdelivr.net/g/angularjs@1.6.0(angular.min.js+angular-animate.min.js+angular-aria.min.js+angular-cookies.min.js+angular-route.min.js+i18n/angular-locale_zh.js),angular.material@1.1.3,angular-local-storage@0.5.2(angular-local-storage.min.js),angular.file-upload@12.2.13"></script>
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/g/angular.material@1.1.3(angular-material.min.css)">
  <!--link rel="stylesheet" href="asset/material-icons/material-icons.css"-->

  <?php if (usesourcecode) : ?>
    <script src="asset/js/app.js<?= '?v=' . version ?>"></script>
    <script src="asset/js/controllers/main.js<?= '?v=' . version ?>"></script>
  <link rel="Stylesheet" type="text/css" href="asset/css/main.css<?= '?v=' . version ?>">
  <?php else: ?>
    <script src="asset/dist/rpgplatinum.min.js<?= '?v=' . version ?>"></script>
  <link rel="Stylesheet" type="text/css" href="asset/dist/rpgplatinum.min.css<?= '?v=' . version ?>">
  <?php endif; ?>

  <base href="/">
</head>
<body class="app-body" layout="row">
<input type="hidden" id="page-config"
       value="<?= htmlspecialchars(json_encode($page_config, JSON_UNESCAPED_UNICODE)) ?>">
<div role="main" layout="column" tabindex="-1" flex>
  <md-toolbar md-whiteframe="1" class="navbar">
    <div class="md-toolbar-tools">
      <div class="navbar-logo" ng-click="changepage('datalist.html')">
        <img src="asset/img/takara.svg">
      </div>
      <h2>
        <span>{{title}}</span>
      </h2>
      <span flex></span>
      <span ng-if="user.info.uid">{{ user.info.username }}</span>
      <md-button class="md-primary login-btn"
                 ng-click="user.loginFrame($event)"
                 ng-disabled="user.info.logining"
                 ng-if="!user.info.uid">
        登录
        <md-progress-circular ng-show="user.info.logining" md-mode="indeterminate"
                              md-diameter="20"></md-progress-circular>
      </md-button>
      <md-button class="md-primary md-raised"
                 ng-click="user.logout()"
                 ng-if="user.info.uid">
        退出
        <md-progress-circular ng-show="user.info.logining" md-mode="indeterminate"
                              md-diameter="20"></md-progress-circular>
      </md-button>
    </div>
  </md-toolbar>
  <md-content class="main-container" flex md-scroll-y layout="column">
    <!--div ng-include="page.now" flex="noshrink"></div-->
    <div ng-view flex="noshrink"></div>
    <md-button class="page-fab md-fab md-fab-bottom-right scrolling md-warn" ng-click="changepage('newdata.html')">
      <md-icon md-svg-icon="add" aria-label="add"></md-icon>
    </md-button>
  </md-content>
</div>
<script type="text/ng-template" id="datalist.html">
  <div layout="row" layout-wrap layout-align="start start" layout-align-xs="center start" class="rpg-list">
    <div class="breif-card-wrapper" ng-repeat="list in rpg.lists" flex-xs="100">
      <div md-whiteframe="2" class="breif-card" layout-xs="row" layout-gt-xs="column">
        <div class="cover column" hide-xs show-gt-xs layout="row" layout-align="center center">
          <img class="cover-image" ng-src="{{list.cover.path || 'asset/img/takara.svg'}}">
        </div>
        <div class="cover row" hide-gt-xs show-xs layout="row" layout-align="center center">
          <img class="cover-image" ng-src="{{list.cover.path || 'asset/img/takara.svg'}}">
        </div>
        <div class="details">
          <a class="click-target" href="javascript:;"></a>
          <div class="title">
            <a href="javascript:;" title="{{list.title}}"
               ng-click="changepage('datacontent.html', list.oid)">
              {{list.title}}
            </a>
          </div>
          <div class="author-container">
            <a class="author" href="javascript:;" title="{{list.author.username}}">{{list.author.username}}</a>
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
</script>
<script type="text/ng-template" id="datacontent.html">
  <div class="rpg">
    <md-card class="rpg-breif">
      <md-card-content layout="row" layout-xs="column">
        <div class="rpg-breif-left" flex-gt-xs="50">
          <h3>{{rpg.detail.title}}</h3>
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
            <dt class="md-primary md-no-sticky">sub_level2.name</dt>
            <dd class="level3-wrap">
              <!-- type: cat -->
              <dl ng-repeat-start="sub_level3 in sub_level2.sub" ng-if="sub_level3.type == 'cat'">
            <dt class="md-primary md-no-sticky"
                ng-if="sub_level3.type == 'cat'">{{sub_level3.name}}
            </dt>
            <dd layout="row" layout-wrap ng-if="sub_level3.type == 'cat'">
              <md-checkbox ng-repeat="item in sub_level3.sub"
                           class="md-warn" flex="noshrink"
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
                       class="md-warn" flex="noshrink"
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
            <label>Title</label>
            <input ng-model="rpg.newRpgData.breif.title">
          </md-input-container>
          <md-input-container class="md-block hide-error-msg">
            <label>Description</label>
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
        <md-cart-title-text>
          <md-input-container class="hide-error-msg md-headline">
            <label>Title</label>
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
              <div>
                <md-input-container class="hide-error-msg">
                  <label>sub_level2_Title</label>
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
                    <div>
                      <md-input-container class="hide-error-msg">
                        <label>sub_level3_Title</label>
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
</body>
</html>
