<?php
/**
 *
 * ====================================================
 * 众达网络科技有限公司
 * 网站地址：http://diandongshenghuo.com
 * ====================================================
 * $ Id: OrderController.class.php 2016/6/16 15:44 Administrator $
 */
namespace Api\Controller;
use Think\Controller;
class OrderController extends Controller {

    /*--------------------------------------- */
    //-- 初始化函数
    /*--------------------------------------- */
    public function _initialize() {
        $this->model = M( 'Order' );
    }

    /*--------------------------------------- */
    //-- 根据用户ID 获得用户报名列表
    //-- @param string $uid
    /*--------------------------------------- */
    public function getOrderList($uid, $nowpage, $prepage) {

        $field = "o.id, o.orderid, o.totalprice, o.order_status, o.uid, o.gid, g.name as good_name, g.savepath, g.image,".
                "CASE WHEN order_status = 1 THEN '已报名' WHEN order_status = 2 THEN '可兑换' WHEN order_status = 3 THEN '已完成' END AS status";

        $result = $this->model -> alias('o')
            -> join(C('DB_PREFIX')."good AS g ON g.id = o.gid", "LEFT")
            -> field($field)
            -> page($nowpage, $prepage)
            -> where('o.uid ='.$uid) -> order('id DESC') -> select();
        if ($result) {
            return $result;
        }
    }

    /*--------------------------------------- */
    //-- 根据商品ID 获得商品已参与人数
    //-- @param string $gid
    /*--------------------------------------- */
    public function getApplyNums($gid) {
        $condition['gid'] = $gid;
        return  M('Order') -> where($condition) -> count();
    }

    /*--------------------------------------- */
    //-- 根据报名ID 获得报名订单好
    //-- @param string $uid
    /*--------------------------------------- */
    public function getOrderId($oid) {
        $condition['id'] = $oid;
        return $this->model -> where($condition) -> getField('orderid');
    }

    /*--------------------------------------- */
    //-- 保存订单信息
    //-- @param string $uid
    /*--------------------------------------- */
    public function saveOrder($datas) {
        $datas['orderid'] = 'DS' . date ( "ymdhis" ) . mt_rand ( 1, 9 );
        $datas['totalprice'] = R('Api/Good/getGoodPrice', array($datas['gid']));
        $datas['order_status'] = 1; // 已报名
        $datas['time'] = date('Y-m-d H:i:s', time());

        $result =  $this->model -> add($datas);
        if ($result) {
            $this->success('信息保存成功！');
        } else {
            $this->error('信息输入错误！');
        }
    }

    /*--------------------------------------- */
    //-- 根据报名ID 获得该订单是否已报名
    //-- @param string $uid
    /*--------------------------------------- */
    public function isApply($oid) {
        $order_info = M('Order') -> alias('o')
            -> join(C('DB_PREFIX').'redeem AS r ON o.redeem = r.id', 'LEFT')
            -> field('o.order_status, o.username, o.mobile, r.name AS rname, r.district, r.address, o.is_apply, r.city, r.mobile AS rmobile')
            -> where('o.id='.$oid) -> find();
        $order_info['city'] = RegionController::getRegionName($order_info['city']);
        $order_info['district'] = RegionController::getRegionName($order_info['district']);
        return $order_info;
    }


    public function IsLimit($openid, $gid) {

        // 获得Uid
        $uid = M('User')->where('wx_openid="'.$openid.'"')->getField('id');

        // 商品是否有限购次数
        $is_limit = M('Good')->where('id='.$gid)->getField('is_limit');

        if ($is_limit != 0) {
            $count = M('Order') -> where('uid='.$uid.' AND gid='.$gid) -> count();
            if ($is_limit <= $count) {
                return true;
            }
        }
        return false;
    }

    /*--------------------------------------- */
    //-- 判断用户，该商品是否有未打赏完成的订单
    /*--------------------------------------- */
    public function getOrderReward($openid, $gid) {
        // 获得Uid
        $uid = M('User')->where('wx_openid="'.$openid.'"')->getField('id');

        // 判断是否已经购买过该商品
        $has_order = M('order') -> where('uid='.$uid.' AND gid='.$gid) -> field('order_status') -> select();

        if ($has_order) {
            foreach ($has_order as $k=>$v)  {
                if ($v['order_status'] == 1) {
                    return false;
                }
            }
        }
        return true;
    }

}