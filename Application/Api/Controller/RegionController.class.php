<?php
/**
 * 点动打赏 省市区
 * ====================================================
 * 众达网络科技有限公司
 * 网站地址：http://diandongshenghuo.com
 * ====================================================
 * $ Id: RegionController.class.php 2016/6/18 15:44 Administrator $
 */
namespace Api\Controller;
use Think\Controller;
class RegionController extends Controller {

    /*--------------------------------------- */
    //-- 初始化函数
    /*--------------------------------------- */
    public function _initialize() {

    }

    /*--------------------------------------- */
    //--
    /*--------------------------------------- */
    public function index() {
        echo 'This is region api!';
    }

    /*--------------------------------------- */
    //-- 获得省市区信息
    /*--------------------------------------- */
    public function getRegion() {

        $type = I('type');
        $parent_id = I('pid');
        $target = I('target');

        $this->model = M('Region');
        $condition['region_type'] = $type;
        $condition['parent_id'] = $parent_id;

        $result = $this->model -> where($condition) -> select();
        if ($result) {
            $this->ajaxReturn(array('regions'=>$result,'type'=>$type,'target'=>$target));
        }else{
            $this->ajaxReturn(array('regions'=>array(),'type'=>$type,'target'=>$target));
        }
    }

    /*--------------------------------------- */
    //-- 获得省列表
    /*--------------------------------------- */
    public function getProvince($region_name = '') {

        if ($region_name != '') {
            $condition['region_name'] = $region_name;
        }

        $this->model = M('Region');
        $condition['region_type'] = 1;
        $condition['parent_id'] = 1;

        $result = $this->model -> where($condition) -> select();
        if ($result) {
            return $result;
        }
    }

    /*--------------------------------------- */
    //-- 获得市列表
    /*--------------------------------------- */
    public function getCity($parent_id = 0,$region_name = '') {

        if ($region_name != '') {
            $condition['region_name'] = $region_name;
        }
        if ($parent_id != 0) {
            $condition['parent_id'] = $parent_id;
        }

        $this->model = M('Region');
        $condition['region_type'] = 2;

        $result = $this->model -> where($condition) -> select();
        if ($result) {
            if(IS_AJAX){
                return $this->ajaxReturn($result);
            }else{
                return $result;
            }
        }
    }

    /*--------------------------------------- */
    //-- 获得区列表
    /*--------------------------------------- */
    public function getDistrict($region_id = '', $parent_id = 0) {

        if ($region_id != '') {
            $condition['region_id'] = $region_id;
        }
        if ($parent_id != 0) {
            $condition['parent_id'] = $parent_id;
        }

        $this->model = M('Region');
        $condition['region_type'] = 3;

        $result = $this->model -> where($condition) -> select();
        if ($result) {
            if(IS_AJAX){
                return $this->ajaxReturn($result);
            }else{
                return $result;
            }
        }
    }

    /*--------------------------------------- */
    //-- 获得市列表
    /*--------------------------------------- */
    public function getRegionInfo($region_id) {

        if ($region_id != 0) {
            $condition['region_id'] = $region_id;
        }

        $this->model = M('Region');
        $condition['region_type'] = 2;

        $result = $this->model -> where($condition) -> select();
        if ($result) {
            return $result;
        }
    }

    /*--------------------------------------- */
    //-- 获得地区名称
    /*--------------------------------------- */
    public function getRegionName($id) {
        $condition['region_id'] = $id;
        return M('Region') -> where($condition) -> getField('region_name');
    }

}