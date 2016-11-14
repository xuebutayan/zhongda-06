<?php
/**
 * 点动打赏 打赏人员相关接口
 * ====================================================
 * 众达网络科技有限公司
 * 网站地址：http://diandongshenghuo.com
 * ====================================================
 * $ Id: RewardUsersController.class.php 2016/6/16 12:00 Administrator $
 */
namespace Api\Controller;
use Think\Controller;
class RewardUsersController extends Controller {

    /*--------------------------------------- */
    //-- 初始化函数
    /*--------------------------------------- */
    public function _initialize() {
        $this->model = M( 'reward' );
    }

    /*--------------------------------------- */
    //--
    /*--------------------------------------- */
    public function index() {
        echo 'This is Reward Users Api!';
    }

    /**
     * APP 相关接口
     * ========================================
     */

    /*--------------------------------------- */
    //-- 获得打赏人员列表
    /*--------------------------------------- */
    public function getRewardList($oid) {
        $condition['oid'] = $oid;
        $condition['pay_status'] = 1;
        $result = M( 'reward' ) -> alias('r')
            -> field('r.r_money, u.wx_nickname, u.wx_headimgurl, p.value as publicity')
            -> join(C('DB_PREFIX')."user AS u ON u.id = r.uid", "LEFT")
            -> join(C('DB_PREFIX')."publicity AS p ON p.id = r.publicity", "LEFT")
            -> where($condition) -> select();
        if ($result) {
            return $result;
        }
    }

    /*--------------------------------------- */
    //-- 保存打赏人员信息
    /*--------------------------------------- */
    public function saveRewardInfo($datas) {
        $datas['time'] = date('Y-m-d H:i:s',time());
        $this->model -> add($datas);
    }

    /*--------------------------------------- */
    //-- 判断用户是否已经打赏过
    /*--------------------------------------- */
    public function CanDs($openid, $oid) {
        // 获得用户ID
        $uid = M('User')->where('wx_openid="'.$openid.'"')->getField('id');
        $condition['uid'] = $uid;
        $condition['oid'] = $oid;
        $condition['pay_status'] = 1;

        $result = $this->model
            -> where($condition) -> count();
        return $result;
    }

    /*--------------------------------------- */
    //-- 获得订单打赏人数，及剩余金额
    /*--------------------------------------- */
    public function getRewardInfo($oid, $gid) {
        $condition['oid'] = $oid;
        $reward = M('Reward') -> where($condition) -> select();

        // 商品信息
        $good_info = M('good') -> where('id='.$gid)->find();
        // 商品总价
        $price_all = $surplus_price = $good_info['old_price'] - $good_info['price'];

        foreach($reward as $k => $v) {
            $surplus_price = $surplus_price - ($v['r_money']*$good_info['reward_rate']);
        }
        if ($surplus_price <= 0) {
            $surplus_price = 0;
        }
        $info['save_money'] = round($price_all - $surplus_price, 2);
        $info['surplus_price'] = $surplus_price;
        $info['has_people'] = count($reward);
        return $info;
    }

    /*--------------------------------------- */
    //-- 判断订单是否可被打赏
    /*--------------------------------------- */
    public function canReward($oid, $gid) {
        $info = $this->getRewardInfo($oid, $gid);
        if ($info['surplus_price'] <= 0) {
            $data['order_status'] = 2;
            M('Order') -> where('id='.$oid.' AND order_status = 1') -> save($data);
            return false;
        }
        return true;
    }

    /*--------------------------------------- */
    //-- 获得宣传语
    /*--------------------------------------- */
    public function getPublicity() {
        $info = M('Publicity') -> field('id, value') -> select();
        $ids_array = array();
        foreach ($info as $k=>$v) {
            array_push($ids_array, $v['id']);
        }
        $condition['id'] = array_rand($ids_array, 1);

        return M('Publicity') -> where($condition) -> getField('value');
    }


    /**
     * Admin 相关接口
     * ========================================
     */

    /*--------------------------------------- */
    //-- Admin 根据订单ID获得订单相关打赏人员信息
    /*--------------------------------------- */
    public function getRewardUsers($oid) {

        $condition['oid'] = $oid;
        $result = M('Reward') -> alias('r')
            -> join(C('DB_PREFIX')."user AS u ON u.id = r.uid", "LEFT")
            -> field('r.r_money, u.wx_nickname, r.time as time')
            -> where($condition) -> select();
        if ($result) {
            return $result;
        }

    }



}