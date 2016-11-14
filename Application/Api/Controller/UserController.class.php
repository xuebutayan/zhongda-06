<?php
/**
 * 点动打赏 用户信息接口
 * ====================================================
 * 众达网络科技有限公司
 * 网站地址：http://diandongshenghuo.com
 * ====================================================
 * $ Id: UserController.class.php 2016/6/15 15:07 Administrator $
 */
namespace Api\Controller;
use Think\Controller;
class UserController extends Controller {

    /*--------------------------------------- */
    //-- 初始化函数
    /*--------------------------------------- */
    public function _initialize() {
        $this->model = M( 'User' );
    }

    /*--------------------------------------- */
    //--
    /*--------------------------------------- */
    public function index() {
        echo 'This is User Api!';
    }

    /*--------------------------------------- */
    //-- 根据用户ID获得用户信息
    //-- @param string $id
    /*--------------------------------------- */
    public function getUserInfo($id) {
        $condition['id'] = $id;
        $user_info = M('User') -> where($condition) -> field('wx_openid, wx_nickname, wx_headimgurl') -> find();
        return $user_info;
    }

    /*--------------------------------------- */
    //-- 根据openid获得用户ID
    //-- @param array $openid
    /*--------------------------------------- */
    public function getUid($openid) {
        $condition['wx_openid'] = $openid;
        return $this->model -> where($condition) -> getField('id');
    }

    /*--------------------------------------- */
    //--保存用户信息
    //-- @param array $userinfo
    /*--------------------------------------- */
    public function saveUserInfo($userinfo) {

        $datas['wx_openid'] = $openid = $userinfo['openid'];
        $datas['wx_nickname'] = $userinfo['nickname'];
        $datas['wx_headimgurl'] = $userinfo['headimgurl'];
        if ($this->check_user_openid($openid)) {
            // 保存
            $where['wx_openid'] = $openid;
            $result = $this->model -> where($where) -> save($datas);
        } else {
            // 添加
            $result = $this->model -> add($datas);
        }
        if ($result) {
            return $result;
        }
    }

    /*--------------------------------------- */
    //-- 根据用户openid 检测用户是否已存在
    //-- @param string $openid
    /*--------------------------------------- */
    public function check_user_openid($openid) {
        $where['wx_openid'] = $openid;

        return $this->model -> where($where) -> count();
    }

    /*--------------------------------------- */
    //-- 根据用户openid 检测用户是否已存在
    //-- @param string $openid
    /*--------------------------------------- */




}