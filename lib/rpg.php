<?php

namespace slime7\rpgplatinum;

use slime7\rpgplatinum\user;
use slime7\rpgplatinum\module\comm;
use slime7\rpgplatinum\module\jsonpack;

class rpg
{
  protected $options = [];

  public function __construct($exOptions = NULL) {
    $default_options = [];
    if (!!$exOptions) {
      $this->options = array_merge($default_options, $exOptions);
    }
  }

  public function postAction() {
    $post = comm::parseBody()['data'];
    $files = comm::parseBody()['files'];
    $json = new jsonpack();

    if (!isset($post['action'])) {
      $this->errorResponse('no action.');
    }

    switch ($post['action']) {
      case 'rpglist':
        $this->listAction($json, $post);
        break;

      case 'rpgdetail':
        $this->detail($json, $post);
        break;

      case 'newrpg':
        $this->newRpg($json, $post, $files);
        break;

      case 'save':
        $this->save($json, $post);
        break;

      case 'load':
        $this->load($json, $post);
        break;

      case 'login':
        $this->login($json, $post);
        break;

      case 'logincheck':
        $this->logincheck($json, $post);
        break;

      case 'logout':
        break;

      default:
        $this->test();
        break;
    }
  }

  public function test() {
    $post_string = json_encode(comm::parseBody()['data']);
    $response = $this->errorResponse($post_string, true);
    $this->response($response);
  }

  private function login(jsonpack $json, $post) {
    global $uid;

    if (!isset($post['username']) || !isset($post['password'])) {
      $this->errorResponse('Unkonw action.', false);
    }
    $login_result = user::login($post['username'], $post['password']);
    if ($login_result) {
      $user = user::detail($uid)[$uid];
      $json->add('uid', (int)$uid);
      $json->add('username', $user['username']);
      $json->success();
    } else {
      $json->setMsg('用户名或密码不正确。');
    }
    $this->response($json);
  }

  private function logincheck(jsonpack $json) {
    global $uid;

    $logincheck_result = user::check_login();
    if ($logincheck_result) {
      $user = user::detail($uid)[$uid];
      $json->add('uid', (int)$uid);
      $json->add('username', $user['username']);
      $json->success();
    }
    $this->response($json);
  }

  private function logout() {
    //pass
  }

  private function listAction(jsonpack $json, $post) {
    $page = isset($post['page']) ? $post['page'] : 1;
    $pagesize = isset($post['pagesize']) ? $post['pagesize'] : 20;
    $order = new order();
    $orderList = $order->lists($page, $pagesize, false);

    $json->set($orderList);
    $json->success();
    $this->response($json);
  }

  private function detail(jsonpack $json, $post) {
    user::check_login();
    global $uid;

    if (!isset($post['oid'])) {
      $this->errorResponse('Need oid.', false);
    }
    $order = new order('oid:' . $post['oid']);
    if ($uid) {
      $orderDetail = $order->get(true);
    } else {
      $orderDetail = $order->get();
    }

    $json->set($orderDetail);
    $json->success();
    $this->response($json);
  }

  private function newRpg(jsonpack $json, $post, $files) {
    user::check_login();
    global $uid;

    if (!$uid) {
      $this->errorResponse('Need login.', false);
    }
    $order = new order();
    $newRpgResult = $order->newRpg($post, $files, $uid);
    if (!$newRpgResult) {
      $this->errorResponse($order->error, false);
    }

    $json->add('post', $post);
    $json->success();
    $this->response($json);
  }

  private function save(jsonpack $json, $post) {
    user::check_login();
    global $uid;

    if (!$uid) {
      $this->errorResponse('Need login.', false);
    }
    if (!isset($post['oid'])) {
      $this->errorResponse('Need oid.', false);
    }
    $order = new order('oid:' . $post['oid']);
    $result = $order->saveProgress($post, $uid);

    if ($result) {
      $json->success();
    } else {
      $json->setMsg('保存失败。');
    }
    $this->response($json);
  }

  private function load(jsonpack $json, $post) {
    user::check_login();
    global $uid;

    if (!$uid) {
      $this->errorResponse('Need login.', false);
    }
    if (!isset($post['oid'])) {
      $this->errorResponse('Need oid.', false);
    }

    $order = new order('oid:' . $post['oid']);
    $progress = $order->loadProgress($uid);

    $json->set($progress);
    $json->success();
    $this->response($json);
  }

  private function errorResponse($msg = '', $isReturn = false) {
    $json = new jsonpack();
    if (!!$msg) {
      $json->setMsg($msg);
      $json->setStatus(500, $msg);
    } else {
      $json->setStatus(500, 'Internal Server Error');
    }

    if ($isReturn) {
      return $json;
    } else {
      $this->response($json);
    }
  }

  private function response(jsonpack $json) {
    $json->header();
    exit($json);
  }
}