<?php
/**
 * 点动打赏 打赏功能
 * ====================================================
 * 众达网络科技有限公司
 * 网站地址：http://diandongshenghuo.com
 * ====================================================
 * $ Id: RewardController.class.php 2016/6/16 14:41 众达网络-CP $
 */
namespace App\Controller;
use Api\Controller\WxController;
use Think\Controller;
class RewardController extends Controller {

    /*--------------------------------------- */
    //-- 初始化函数
    /*--------------------------------------- */
    public function _initialize() {
        $gid = I('gid');
        $oid = I('oid');
        R('Api/RewardUsers/canReward', array($oid, $gid));
        $this->model = M( 'Reward' );
        $this->redirect_uri = 'http://public.diandongshenghuo.com/06/index.php?m=App&c=reward&a=wx_return_url';
    }

    /*--------------------------------------- */
    //--
    /*--------------------------------------- */
    public function index() {

        $uid = I('uid');
        $gid = I('gid');
        $oid = I('oid');

        $wx = new WxController();
        $token = session('token');

        if (!empty($token)) {
            // 验证是否已授权
            $status = $wx->check_access_token($token['access_token'],$token['openid']);
            if ($status['errcode'] == 0) {
                $this->assign('openid', $token['openid']);
                $this->reward($token['openid'], $uid, $gid, $oid);
            } else {
                session('code', null);
                $this->redirect_uri .= "&uid=".$uid."&gid=".$gid."&oid=".$oid;
                Header("Location:".$wx->get_authorize_url(1,  $this->redirect_uri));
            }
        } else {
            // 微信授权
            $this->redirect_uri .= "&uid=".$uid."&gid=".$gid."&oid=".$oid;
            Header("Location:".$wx->get_authorize_url(1,  $this->redirect_uri));
        }
    }


    /*--------------------------------------- */
    //-- 回调函数
    /*--------------------------------------- */
    public function wx_return_url(){

        $uid = I('uid');
        $gid = I('gid');
        $oid = I('oid');

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
            $this->reward($token['openid'], $uid, $gid, $oid);
        } else {
            // 授权
            $this->index();
        }


    }

    /*--------------------------------------- */
    //-- 打赏页面
    /*--------------------------------------- */
    public function reward($openid, $uid = 0, $gid = 0, $oid = 0) {
        $gid = I('gid', $gid);
        $f_uid = I("uid", $uid);
        $oid = I("oid", $oid);
        $canbuy = 0;
        $canapply = 1;

        // 获得当前用户ID
        $wx_uid = M('User') -> where('wx_openid="'.$openid.'"')->getField('id');
        $uid = $f_uid == 0 ? $wx_uid : $f_uid;

        // 购买人信息
        $user_info = R('Api/User/getUserInfo', array($uid));
        $this->assign("user_info", $user_info);

        // 判断是否是非该报名用户打开
        // 页面是否显示购买按钮
        if ($f_uid != 0) {
            $u_openid = M('User') -> where('id='.$f_uid)->getField('wx_openid');
            if ($u_openid == $openid) {
                $canbuy = 1;
                $canapply = 0;
            }
        } else {
            $f_uid = $uid;
            $canbuy = 1;
            $canapply = 0;
        }
        $this->assign('f_uid', $f_uid);

        // 判断是否已到限购次数
        $is_limit = R('Api/Order/IsLimit', array($openid, $gid));
        $this->assign('is_limit', $is_limit);

        // 打赏人员列表
        if ($oid != 0) {
            $reward_list = R('Api/RewardUsers/getRewardList', array($oid));
            $this->assign('reward_list', $reward_list);
        }

        // 将报名信息存入报名表中
        if (!$oid) {
            $datas['uid'] = $wx_uid;
            $datas['gid'] = I('gid');
            $datas['orderid'] = 'BM' . date ( "ymdhis" ) . mt_rand ( 1, 9 );
            $datas['totalprice'] = R('Api/Good/getGoodPrice', array($datas['gid']));
            $datas['order_status'] = 1; // 已报名
            $datas['time'] = date('Y-m-d H:i:s', time());
            $oid =  M('Order') -> add($datas);
        }
        // 商品信息
        $good_info = R('Api/Good/getGoodInfo', array($gid));
        $this->assign('good_info', $good_info);


        $this->assign('m_uid', $wx_uid);
        $this->assign('gid', $gid);
        $this->assign('oid',$oid);

        // 获得分享链接描述语
        $desc = R('Api/RewardUsers/getPublicity');

        // 获得该订单已打赏人数，及剩余价格
        $order_info = R('Api/RewardUsers/getRewardInfo', array($oid, $gid));

        // 获得商品列表
        $goods = R('Api/Good/getGoodList', array('show',1, 6));
        $this->assign('goods_list', $goods);

        $this->assign('desc', $desc);
        $this->assign("order_info", $order_info);
        $this->assign('canbuy', $canbuy);
        $this->assign('canapply', $canapply);
        $this->assign('openid', $openid);
        $this->assign('jssdk', WxController::getSignPackage());
        $this->display('Reward_index');
    }

    /*--------------------------------------- */
    //-- 调用支付接口
    /*--------------------------------------- */
    public function WxPay() {
        $oid = I('oid');
        $gid = I('gid');
        // 根据$openid 判断用户是否可以打赏
        $canDS = R('Api/RewardUsers/canReward', array($oid, $gid));
        if ($canDS) {
            $jsApi = R('Api/Wx/wxpay');
            $this->ajaxReturn(array('is_err'=>'0','jsApi'=>$jsApi));
        } else {
            $this->ajaxReturn(array('is_err'=>'1','err_msg'=>'该订单打赏金额已满，不能打赏了！'));
        }

    }


}