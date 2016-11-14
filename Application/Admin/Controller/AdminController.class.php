<?php
/**
 * 我要平台：管理员管理页面
 * ====================================================
 * 众达网络科技有限公司
 * 网站地址：http://diandongshenghuo.com
 * ====================================================
 * $ Id: AdminController.class.php 2016/7/9 9:43 Administrator $
 */
namespace Admin\Controller;
use Think\Controller;
class AdminController extends PublicController {

    public function _initialize() {
        parent::_initialize ();
    }

    public function index() {
        $m = M ( "Admin" );
        $type = I('type', 'list');
        $count = $m->count (); // 查询满足要求的总记录数

        $Page = new \Think\Page($count,12);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");

        $show = $Page->show (); // 分页显示输出
        $field = "a.id, a.username, r.name as r_name, a.time, CASE WHEN a.type = 0 THEN '超级管理员' ELSE '商家' END AS type_name, a.type";
        $oby = "a.type ASC, a.id DESC ";
        $result = $m -> alias('a')
            -> join(C('DB_PREFIX').'redeem as r ON r.id=a.redeem', 'LEFT')
            -> field($field) -> order($oby) -> limit ( $Page->firstRow . ',' . $Page->listRows )->select ();

        $this->assign ( "page", $show ); // 赋值分页输出
        $this->assign ( "result", $result );
        $this->assign ('type', $type);
        $this->display ();
    }


    public function edit() {
        $id = I('id');
        $type = I('type', 'edit');

        if ($id) {
            $result = M('Admin') -> where('id='.$id) -> find();
            $this->assign('result', $result);
        }

        // 获得兑换地点
        $redeem_list = $list = M('Redeem') -> field('id, name') -> where('status=1 AND type=2') -> select();

        $this->assign('redeem_list', $redeem_list);
        $this->assign('type', $type);
        $this->display('Admin_index');
    }

    public function save() {
        $post = I('post.');
        if ($post['id']) {
            $datas['username'] = $post['username'];
            if ($post['password']) {
                $datas['password'] = md5($post['password']);
            }
            $datas['type'] = $post['type'];
            $datas['redeem'] = $post['redeem'];
            $result = M('Admin') -> where("id=".$post['id']) -> save($datas);
            if ($result) {
                $this->success('管理员修改成功！', U('Admin/Admin/index'));
            } else {
                $this->error('数据没有任何改变！');
            }
        } else {
            $datas['username'] = $post['username'];
                $datas['password'] = md5($post['password']);
            $datas['type'] = $post['type'];
            $datas['redeem'] = $post['redeem'];
            $datas['time'] = date('Y-m-d H:i:s', time());
            $result = M('Admin') -> add($datas);
            if ($result) {
                $this->success('管理员添加成功！', U('Admin/Admin/index'));
            } else {
                $this->error('管理员添加失败！');
            }
        }
    }

    public function del() {
        $id = I('id');
        if ($id) {
            $type = M('Admin') -> where('id='.$id) -> getField('type');
            if ($type == 0) {
                $this->error('您没有删除超级管理员的权限！', U('Admin/Admin/index'));
            } else {
                $result = M('Admin') -> where('id='.$id) -> delete();
                if ($result) {
                    $this->success('管理员删除成功！', U('Admin/Admin/index'));
                }
            }
        }
    }

}