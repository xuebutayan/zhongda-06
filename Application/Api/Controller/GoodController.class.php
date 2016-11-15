<?php
/**
 * 点动打赏 商品接口
 * ====================================================
 * 众达网络科技有限公司
 * 网站地址：http://diandongshenghuo.com
 * ====================================================
 * $ Id: GoodController.class.php 2016/6/15 15:38 众达网络-CP $
 */
namespace Api\Controller;
use Think\Controller;
class GoodController extends Controller {


    /*--------------------------------------- */
    //-- 初始化函数
    /*--------------------------------------- */
    public function _initialize() {
        $this->model = M( 'Good' );
    }

    /*--------------------------------------- */
    //--
    /*--------------------------------------- */
    public function index() {
        echo 'This is Good Api!';
    }

    /**
     * APP 相关接口
     * ======================================
     */

    /*--------------------------------------- */
    //-- APP 获得商品列表
    /*--------------------------------------- */
    public function getGoodList($type, $nowpage, $prevpage,$location=array()) {
        // 判断商品活动时间是否已经结束
        $update_date['status'] = 0;
        M('Good') -> where('end_date IS NOT NULL AND end_date < "'.date('Y-m-d', time()).'"') -> save($update_date);
        if ($type == 'show') {
            $condition['status'] = 1;
        } elseif ($type == 'hidden') {
            $condition['status'] = 0;
        }
        $condition = array_merge($condition,$location);
        $oby = 'sort DESC, id DESC';
        $field = 'id, name, price, old_price, status, savepath, image, status, is_new, price, good_num, IFNULL(begin_date, 0) AS begin_date, IFNULL(end_date, 0) AS end_date';
        $goods = $this->model -> where($condition) -> page($nowpage, $prevpage) -> field($field) -> order($oby) -> select();
        /*foreach($goods as $k=>$v) {
            $goods[$k]['total_price'] = doubleval($v['old_price']*$v['price_rate']);
        }*/
        if ($goods) {
            return $goods;
        }

    }

    /*--------------------------------------- */
    //-- APP 获得商品价格
    /*--------------------------------------- */
    public function getGoodPrice($gid, $type='total') {
        $condition['id'] = $gid;
        if ($type == 'total') {
            $field = "price";
            $result = $this->model -> where($condition) -> field($field) -> find();
            $return_info = $result['price'];
        } else {

        }

        return $return_info;
    }

    /*--------------------------------------- */
    //-- APP 获得商品信息
    /*--------------------------------------- */
    public function getGoodInfo($gid) {
        $condition['id'] = $gid;
        $field = "*, price as totalprice, reward_rate, has_one, has_five";
        $result = $this->model -> where($condition) -> field($field) -> find();

        $result['money_pay_style'] = $result['has_five'] == 0 ? 0 : 1;

        if ($result) {
            return $result;
        }
    }

    public function getGoodAll($type,$location=array()) {
        if ($type == 'show') {
            $condition['status'] = 1;
        } elseif ($type == 'hidden') {
            $condition['status'] = 0;
        }
        $condition = array_merge($condition,$location);
        return M('Good') -> where($condition) -> count();
    }

    /*--------------------------------------- */
    //-- APP 商品库存量是否充足
    /*--------------------------------------- */
    public function getGoodNums($gid) {
        $condition['id'] = $gid;
        $good_nums = M("good") -> where($condition) -> getField('good_num');
        if ($good_nums) {
            return true;
        }
        return false;
    }

    /*--------------------------------------- */
    //-- APP 商品库存量是否充足
    /*--------------------------------------- */
    public function goodActivityDate($gid) {
        $now_date = date('Y-m-d', time());
        $end_date = M('good') -> where('id='.$gid) -> getField('end_date');
        if ($end_date != null && $end_date < $now_date) {
            return false;
        }
        return true;
    }

    /**
     * Admin 相关接口
     * ======================================
     */

    /*--------------------------------------- */
    //-- Admin 获得商品列表
    /*--------------------------------------- */
    public function getGoods() {
        $oby = 'sort DESC, id DESC';
        $field = 'id, name, price, old_price, savepath, image, status';
        $goods = $this->model -> where() -> field($field) -> order($oby) -> select();
        if ($goods) {
            return $goods;
        }
    }

    /*--------------------------------------- */
    //-- Admin 保存商品信息
    /*--------------------------------------- */
    public function saveGood($data) {
        if ($data["id"]) {
            $result = $this->model -> save($data);
        }else{
            $result = $this->model -> add($data);
        }

        if ($result) {
            return $result;
        }
    }

    /*--------------------------------------- */
    //-- Admin 删除商品信息
    /*--------------------------------------- */
    public function delGood($id) {
        $result = M ( "Good" )->where ( array (
            "id" => $id
        ) )->delete ();
        if ($result) {
            return $result;
        }
    }

}