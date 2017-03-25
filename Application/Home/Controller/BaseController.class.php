<?php
namespace Home\Controller;

use Think\Controller;

class BaseController extends Controller {
    public function _initialize() {
        session('nickname', 'test');
        session('openid', 'asdf');
    }
}