<?php
/**
 * 点动打赏 微信首页
 * ====================================================
 * 众达网络科技有限公司
 * 网站地址：http://diandongshenghuo.com
 * ====================================================
 * $ Id: IndexController.class.php 2016/6/15 11:57 众达网络-CP $
 */
namespace App\Controller;
use Api\Controller\WxController;
use Think\Controller;
class  IndexController extends Controller {
    protected $where=array();
    /*--------------------------------------- */
    //-- 初始化函数
    /*--------------------------------------- */
    public function _initialize() {
        $this->redirect_uri = C('WX_CONFIG.WX_REDIRECT_URI');
        //添加地区自动查询
        if($location=session('location')){
            $province_id = M('Region')->where(array('region_name'=>substr($location['province'],0,-3)))->getField('region_id');
            $city_id = M('Region')->where(array('region_name'=>substr($location['city'],0,-3)))->getField('region_id');
            $district_id = M('Region')->where(array('region_name'=>$location['district']))->getField('region_id');
        }
        if(isset($_GET['auto'])&&$_GET['auto']==0){
            list($province_id,$city_id,$district_id)=explode(',',$_GET['location']);
            $this->assign('request_str',urlencode('&auto=0&location='.$_GET['location']));
        }
        if($province_id) $this->where['province']=$province_id;
        if($city_id) $this->where['city']=$city_id;
        if($district_id) $this->where['district']=$district_id;
        //省列表
        $provinces = M('Region')->field('region_id,region_name')->where('parent_id=1 ')->select();
        $new_provinces = '';
        foreach ($provinces as $v) {
            $new_provinces .= "<option value=".$v[region_id].($v['region_id']==$province_id?' selected':'')."> $v[region_name] </option>";
        }
        $this->assign('new_provinces',$new_provinces);
        //市列表
        if($province_id){
            $citys = M('Region')->field('region_id,region_name')->where(array('parent_id'=>$province_id))->select();
            $new_citys = '';
            foreach ($citys as $v) {
                $new_citys .= "<option value=".$v[region_id].($v['region_id']==$city_id?' selected':'')."> $v[region_name] </option>";
            }
            $this->assign('new_citys',$new_citys);
        }
        //区列表
        if($city_id){
            $districts = M('Region')->field('region_id,region_name')->where(array('parent_id'=>$city_id))->select();
            $new_districts = '';
            foreach ($districts as $v) {
                $new_districts .= "<option value=".$v[region_id].($v['region_id']==$district_id?' selected':'')."> $v[region_name] </option>";
            }
            $this->assign('new_districts',$new_districts);
        }
    }

    /*--------------------------------------- */
    //-- 首页
    /*--------------------------------------- */
    public function index() {

        $nowpage = I('nowpage', 1);
        $prevpage = 4;
        $type = I('type', 'show');

        $wx = new WxController();
        $token = session('token');
        if (!empty($token)) {
            // 验证是否已授权
            $status = $wx->check_access_token($token['access_token'],$token['openid']);

            if ($status['errcode'] == 0) {
                $this->assign('openid', $token['openid']);
                $this->GoodList($token['openid'], $type, $nowpage, $prevpage);
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

            $this->GoodList($token['openid'], 'show', 1, 4);
        } else {
            // 授权
            $this->index();
        }


    }

    /*--------------------------------------- */
    //-- 商品列表页面
    /*--------------------------------------- */
    public function GoodList($openid, $type, $nowpage, $prevpage) {

        // 获得用户ID
        $condition['wx_openid'] = $openid;
        $uid = M('User') -> where($condition) -> getField('id');

        // 获得首页商品列表
        $goods = R('Api/Good/getGoodList', array($type, $nowpage, $prevpage,$this->where));
        // 获得商品参与人数
        foreach ($goods as $k=>$v) {
            $goods[$k]['name'] = msubstr($v['name'],0,16);
            $goods[$k]['apply_nums'] =  M('Order') -> where('gid='.$v['id']) -> count();
        }

        // 获得商品总数
        $goods_all = ceil(intval(R('Api/Good/getGoodAll', array($type,$this->where)))/$prevpage);


        $this->assign('goods_all', $goods_all);
        $this->assign('goods', $goods);
        $this->assign('uid', $uid);
        $this->assign('nowpage', $nowpage);
        $this->assign('wx_openid', $openid);
        $this->assign('type', $type);
        $this->assign('jssdk', WxController::getSignPackage());
        $this->display('Index_index');
    }
    function setCity(){
        $location = session('location');
        if(empty($location)){
            $location = array('province'=>$_POST['province'],'city'=>$_POST['city'],'district'=>$_POST['district']);
            session('location',$location);
        }
    }

}