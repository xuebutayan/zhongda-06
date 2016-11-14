<?php
/**
 * 点动打赏 报名功能
 * ====================================================
 * 众达网络科技有限公司
 * 网站地址：http://diandongshenghuo.com
 * ====================================================
 * $ Id: ApplyController.class.php 2016/6/16 14:44 众达网络-CP $
 */
namespace App\Controller;
use Think\Controller;
use Api\Controller\WxController;
class ApplyController extends Controller {

    /*--------------------------------------- */
    //-- 初始化函数
    /*--------------------------------------- */
    public function _initialize() {
        $this->model = M('Order');
        $this->redirect_uri = 'http://public.diandongshenghuo.com/06/index.php?m=App&c=apply&a=wx_return_url';
    }

    /*--------------------------------------- */
    //-- 报名页面
    /*--------------------------------------- */
    public function index() {

        $openid = I('openid');
        $oid = I('oid');
        $gid = I('gid');

        $uid = R('Api/User/getUid', array($openid));
        $user_info = R('Api/User/getUserInfo',  array($uid));

        // 商品信息
        $good_info = R('Api/Good/getGoodInfo', array($gid));

        // 订单号
        $orderid = R('Api/Order/getOrderId', array($oid));

        // 判断是否已经报名成功
        $is_apply = R('Api/Order/isApply', array($oid));

        // 获得兑换地点列表
        $redeem_list = R('Api/Redeem/getList', array($gid));

        $this->assign('oid', $oid);
        $this->assign('gid', $gid);
        $this->assign('uid', $uid);
        $this->assign('openid', $openid);
        $this->assign('orderid', substr($orderid, -8));
        $this->assign('is_apply', $is_apply);
        $this->assign('good_info', $good_info);
        $this->assign('user_info', $user_info);
        $this->assign('redeem_list', $redeem_list);
        $this->display('Apply_index');
    }

    /*--------------------------------------- */
    //-- 保存报名信息
    /*--------------------------------------- */
    public function saveApplyInfo() {
        $userinfo = I("post.");

        $datas['redeem'] = $userinfo['redeem'];
        $datas['is_apply'] = 1;

        if ($userinfo['oid']) {
            $condition['id'] = $userinfo['oid'];
            $result = M('Order') -> where($condition) -> save($datas);
            $this->ajaxReturn(array('is_err'=>0));
        }


    }

    /*--------------------------------------- */
    //--
    /*--------------------------------------- */
    public function apply_reward() {

        $gid = I('gid');
        $openid = I('openid');

        $wx = new WxController();
        $token = session('token');

        if (!empty($token)) {
            // 验证是否已授权
            $status = $wx->check_access_token($token['access_token'],$token['openid']);
            if ($status['errcode'] == 0) {
                $this->assign('openid', $token['openid']);
                $this->apply($openid,$gid);
            } else {
                session('code', null);
                $this->redirect_uri .= "&gid=".$gid;
                Header("Location:".$wx->get_authorize_url(1,  $this->redirect_uri));
            }
        } else {
            // 微信授权
            $this->redirect_uri .= "&gid=".$gid;
            Header("Location:".$wx->get_authorize_url(1,  $this->redirect_uri));
        }
    }


    /*--------------------------------------- */
    //-- 回调函数
    /*--------------------------------------- */
    public function wx_return_url(){

        $gid = I('gid');

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
            $this->apply($token['openid'],$gid);
        } else {
            // 授权
            $this->apply_reward();
        }


    }

    /*--------------------------------------- */
    //-- 跳转报名页面
    /*--------------------------------------- */
    public function apply($openid, $gid) {
        // 商品信息
        $good_info = R('Api/Good/getGoodInfo', array($gid));

        // 获得商品列表
        $goods = R('Api/Good/getGoodList', array('show',1, 6));
        $this->assign('goods_list', $goods);

        $this->assign('good_info', $good_info);
        $this->assign('openid', $openid);
        $jssdk = WxController::getSignPackage();
        $this->assign('jssdk', $jssdk);

        $this->assign('gid', $gid);
        $this->display('Apply_reward');

    }

    /*--------------------------------------- */
    //-- 校验是否可以报名
    /*--------------------------------------- */
    public function checkApply() {
        $openid = I('openid');
        $gid = I('gid');
        // 商品是否限购
        // 用户是否已经到达限购次数
        // 返回值为 true 时，到达限购次数，不能报名；
        $is_limit = R('Api/Order/IsLimit', array($openid, $gid));
        // 商品库存是否充足
        // 返回值为 false 时，库存量为0，不能报名；
        $good_num = R('Api/Good/getGoodNums', array($gid));
        // TODO 商品是否已报名，是否已经打赏完成
        $order_reward = R('Api/Order/getOrderReward', array($openid, $gid));
        // TODO 商品活动时间是否结束
        $activity_date = R('Api/Good/goodActivityDate', array($gid));
        $can_apply = 1;
        $err_msg = '';
        if ($is_limit || !$good_num || !$order_reward || !$activity_date) {
            if (!$order_reward) {
                $can_apply = 2;
                $err_msg = '您还有该商品未完成打赏的订单，是否继续报名？';
            }
            if ($is_limit) {
                $can_apply = 0;
                $err_msg = '该商品已达到限购次数，已经不能报名了！';
            }
            if (!$good_num) {
                $can_apply = 0;
                $err_msg = '该商品已经没有库存了，已经不能报名了！';
            }
            if (!$activity_date) {
                $can_apply = 0;
                $err_msg = '该商品活动日期已结束，已经不能报名了！';
            }
        }
        $this->ajaxReturn(array('can_apply'=>$can_apply, 'err_msg'=>$err_msg));

    }

}