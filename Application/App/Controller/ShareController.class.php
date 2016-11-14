<?php
/**
 * 晒图功能
 * ====================================================
 * 众达网络科技有限公司
 * 网站地址：http://diandongshenghuo.com
 * ====================================================
 * $ Id: ShareController.class.php 2016/6/30 15:44 Administrator $
 */
namespace App\Controller;
use Api\Controller\WxController;
use Think\Controller;
class ShareController extends Controller {

    /*--------------------------------------- */
    //-- 初始化函数
    /*--------------------------------------- */
    public function _initialize() {
        $this->redirect_uri = 'http://public.diandongshenghuo.com/06/index.php?m=App&c=Share&a=wx_return_url';
    }

    /*--------------------------------------- */
    //--
    /*--------------------------------------- */
    public function index() {

        $wx = new WxController();
        $token = session('token');

        if (!empty($token)) {
            // 验证是否已授权
            $status = $wx->check_access_token($token['access_token'],$token['openid']);
            if ($status['errcode'] == 0) {
                $this->assign('openid', $token['openid']);
                $this->getList();
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
            $this->getList();
        } else {
            // 授权
            $this->index();
        }

    }

    public function getList($openid) {
        $this->model = M('Share');
        $list = $this->model -> order('sort DESC, id DESC') -> select();
        $this->assign('openid', $openid);
        $this->assign('list', $list);
        $this->assign('jssdk', WxController::getSignPackage());
        $this->display('Share_image');
    }

}