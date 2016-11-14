<?php
/**
 * 点动打赏 个人中心
 * ====================================================
 * 众达网络科技有限公司
 * 网站地址：http://diandongshenghuo.com
 * ====================================================
 * $ Id: UserController.class.php 2016/6/16 15:33 众达网络-CP $
 */
namespace App\Controller;
use Api\Controller\WxController;
use Think\Controller;
class UserController extends  Controller {


    /*--------------------------------------- */
    //-- 初始化函数
    /*--------------------------------------- */
    public function _initialize() {
        $this->model = M('User');
        $this->redirect_uri = 'http://public.diandongshenghuo.com/06/index.php?m=App&c=User&a=wx_return_url';
    }

    /*--------------------------------------- */
    //-- 报名列表
    /*--------------------------------------- */
    public function index() {
        $openid = I('openid');
        $nowpage = I('nowpage', 1);
        $prepage = 4;

        $wx = new WxController();
        $token = session('token');
        if (!empty($token)) {
            // 验证是否已授权
            $status = $wx->check_access_token($token['access_token'],$token['openid']);

            if ($status['errcode'] == 0) {
                $this->assign('openid', $token['openid']);
                $this->assign('jssdk', WxController::getSignPackage());
                $this->user($token['openid'],  $nowpage, $prepage);
            } else {
                session('code', null);
                Header("Location:".$wx->get_authorize_url(1,  $this->redirect_uri));
            }
        } else {
            // 微信授权
            Header("Location:".$wx->get_authorize_url(1,  $this->redirect_uri));
        }

    }

    /*--------------------------------------- */
    //-- 回调函数
    /*--------------------------------------- */
    public function wx_return_url(){

        $wx = new WxController();
        $_code = session('code');
        if (empty($_code)) {
            session('code', $_GET['code']);
            //确认授权后会，根据返回的code获取token
            $token = $wx->get_access_token('','',$_GET['code']);
            session('token', $token);
            // 获得用户信息
            $user_info = $wx->get_user_info($token['access_token'],$token['openid']);
            // 保存用户信息
            R('Api/User/saveUserInfo', array($user_info));

            $this->assign('openid', $token['openid']);
            $this->assign('jssdk', WxController::getSignPackage());
            $this->user($token['openid'],  1, 4);
        } else {
            // 授权
            $this->index();
        }


    }

    public function user($openid, $nowpage, $prepage) {
        $uid = R('Api/User/getUid', array($openid));

        $order_list = R('Api/Order/getOrderList', array($uid, $nowpage, $prepage));

        $user_info = R('Api/User/getUserInfo',  array($uid));

        foreach($order_list as $k => $v) {
            $order_list[$k]['good_name'] =  msubstr($v['good_name'],0, 12);
        }


        $all = M('order') -> alias('o')
            -> join(C('DB_PREFIX')."good AS g ON g.id = o.gid", "LEFT")
            -> where('o.uid ='.$uid) -> count();
        $result_all = ceil(intval($all)/$prepage);

        $this->assign('pageall', $result_all);
        $this->assign('nowpage', $nowpage);
        $this->assign('user', $user_info);
        $this->assign('order_list', $order_list);
        $this->assign('openid', $openid);
        $this->assign("uid", $uid);

        $this->display('User_index');
    }

    public function duiba() {
        $uid = I('uid');
        $url =  R('Api/Points/getDrawUrl', array($uid));
        header("Location:".$url."");
        //确保重定向后，后续代码不会被执行
        exit;
    }

}