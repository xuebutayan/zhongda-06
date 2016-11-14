<?php
/**
 *
 * ====================================================
 * 众达网络科技有限公司
 * 网站地址：http://diandongshenghuo.com
 * ====================================================
 * $ Id: RedeemController.class.php 2016/6/21 15:04 Administrator $
 */
namespace Api\Controller;
use Think\Controller;
class RedeemController extends Controller {

    /*--------------------------------------- */
    //-- 初始化函数
    /*--------------------------------------- */
    public function _initialize() {
    }

    /*--------------------------------------- */
    //--
    /*--------------------------------------- */
    public function index() {
        echo 'This is redeem Api!';
    }

    /**
     * APP 相关接口
     * ======================================
     */

    /*--------------------------------------- */
    //-- APP 获得兑换地点列表
    /*--------------------------------------- */
    public function getList($gid) {
        $redeem_list = M('Good')->where('id='.$gid)->getField('redeem_list');
        if ($redeem_list) {
            $redeem_list = substr($redeem_list,0,strlen($redeem_list)-1);
            $condition = 'status = 1 AND id in ('.$redeem_list.')';
        } else {
            $condition['status'] = 1;
            $condition['type'] = 2;
        }
        $list = M('Redeem') -> where($condition) -> select();
        foreach ($list as $k=>$v) {
            $list[$k]['province'] = RegionController::getRegionName($v['province']);
            $list[$k]['city'] = RegionController::getRegionName($v['city']);
            $list[$k]['district'] = RegionController::getRegionName($v['district']);
        }
        if ($list) {
            return $list;
        }
    }


    public function getRedeemInfo($rid) {
        $condition['id'] = $rid;
        $condition['status'] = 1;
        $redeem_info = M('Redeem') -> where($condition) -> find();
        $redeem_info['province'] = RegionController::getRegionName($redeem_info['province']);
        $redeem_info['city'] = RegionController::getRegionName($redeem_info['city']);
        $redeem_info['district'] = RegionController::getRegionName($redeem_info['district']);
        return $redeem_info;
    }

    /*--------------------------------------- */
    //-- APP 获得打赏地址详细地址
    /*--------------------------------------- */
    public function getDetailAdress() {
        $rid = I('rid');
        if ($rid) {
            $this->model = M('Redeem');
            $condition['id'] = $rid;

            $result = $this->model -> where($condition) -> field('name, district, address, mobile') -> find();
            $result['district'] = RegionController::getRegionName($result['district']);
            if ($result) {
                $this->ajaxReturn(array('is_err'=> 0, 'res'=>$result));
            }
        } else {
            $this->ajaxReturn(array('is_err'=> 1));
        }

    }

    /*--------------------------------------- */
    //-- APP 获得兑换地点列表
    /*--------------------------------------- */
    public function getListById($gid) {

        $condition['id'] = $gid;
        $redeem_list = M('good') -> where($condition) -> getField('redeem_list');
        $parent_id = M('Redeem') -> where(array('type'=>1, 'status'=>1)) -> field('id') -> select();
        $redeems = array();
        foreach ($parent_id as $k=>$v) {
            $children_list = M('Redeem') ->where('parent_id='.$v['id'].' AND status=1') -> field('id, name') -> select();
            $checkbox = $this->checkbox($children_list, $redeem_list);
            $redeems[$k]['list'] = $checkbox;
        }
        return $redeems;
    }

    /*--------------------------------------- */
    //-- 复选框控件
    /*--------------------------------------- */
    public static function checkbox($array = array(), $id = '') {
        $string = '';
        $id = trim($id);
        if($id && $id != 'all') {
            $all = false;
            $id = strpos($id, ',') ? explode(',', $id) : array($id);
        } else if ($id && $id == 'all') {
            $all = true;
        }
        foreach($array as $key=>$value) {
            $key = trim($value['id']);
            $checked = ($id && in_array($key, $id)) || $all ? 'checked' : '';
            $string .= '<li><div class="checkbox-group"><label>'.htmlspecialchars($value['name']).'</label>';
            $string .= '<input type="checkbox" name="redeem_list[]"'.$checked.' value="'.htmlspecialchars($value['id']);
            $string .= '"> ';
            $string .= ' </div></li>';
        }
        return $string;
    }
}