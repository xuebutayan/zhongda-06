<?php
namespace Admin\Controller;

use Think\Controller;

class LoginController extends Controller {
	public function index() {
		$this->display ( "Public:login" );
	}
	public function login() {
		$result = R ( "Api/Api/login", array (I("post.username"),I("post.password")));

		if ($result) {
			session("wadmin",$result["username"]);
			session('admin_type',$result['type']);
			session('redeem',$result['redeem']);//已兑换列表菜单需要这个参数
			session('region_id',$result['region_id']);//管理员所在地区识别
			$this->success ( "登录成功", U ( "Admin/Index/index" ) );
			/*if($result['type'] == 0) {
				$this->success ( "登录成功", U ( "Admin/Index/index" ) );
			} elseif ($result['type'] == 1) {	// 审核订单验证页面
				$this->success ( "登录成功", U ( "Admin/Order/order_check" ) );
			}*/
		} else {
			$this->error ( "登录失败", U ( "Admin/Index/index" ) );
		}
	}
	public function logout() {

		session(null);

		$this->success ( '已注销登录！', U ( "Admin/Login/index" ) );
	}
}