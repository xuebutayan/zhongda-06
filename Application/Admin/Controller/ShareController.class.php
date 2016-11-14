<?php
/**
 * 晒照管理模块
 * ====================================================
 * 众达网络科技有限公司
 * 网站地址：http://diandongshenghuo.com
 * ====================================================
 * $ Id: ShareController.class.php 2016/6/29 8:43 Administrator $
 */
namespace Admin\Controller;
use Think\Image;
use Think\Upload;
class ShareController extends PublicController {

    /*--------------------------------------- */
    //-- 初始化函数
    /*--------------------------------------- */
    public function _initialize() {
        parent::_initialize ();
    }

    /*--------------------------------------- */
    //--
    /*--------------------------------------- */
    public function index() {
        $this->getList();
    }

    /*--------------------------------------- */
    //-- 获得晒照列表
    /*--------------------------------------- */
    public function getList() {
        $m = M ( "Share" );

        $count = $m->count (); // 查询满足要求的总记录数

        $Page = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");

        $show = $Page->show (); // 分页显示输出
        $field = " * ";
        $oby = " sort DESC, id DESC ";
        $result = $m-> field($field) -> order($oby) -> limit ( $Page->firstRow . ',' . $Page->listRows )->select ();

        $this->assign ( "page", $show ); // 赋值分页输出
        $this->assign ( "result", $result );
        $this->assign ( "type" , "images_list" );
        $this->display ('Share_image');
    }

    public function add_batch() {
        $this->assign('type', 'images_batch');
        $this->display('Share_image');
    }


    /*--------------------------------------- */
    //-- 上传图片页面
    /*--------------------------------------- */
    public function add() {

        $id = I('id');

        $result = M('Share') -> where('id='.$id) -> find();

        $this->assign('result', $result);
        $this->assign('type', 'images_modify');
        $this->display('Share_image');
    }


    /*--------------------------------------- */
    //-- 保存上传图片
    /*--------------------------------------- */
    public function save() {

        $dataimg = I("post.");
        $condition['id'] = $dataimg['id'];
        $data ["title"] = $dataimg["title"];
        $data ["sort"] = $dataimg["sort"];
        if ($_FILES ['addimage'] ['name'] !== '') {
            $img = $this->upload ();
            $data ["images"] = $img [addimage] [savename];
            $data ["savepath"] = ltrim($img [addimage] [savepath], ".");
        } else {
            $this->error ( "未上传图片！" );
        }

        $result = M('Share') -> where($condition) -> save($data);
        if ($result) {
            $this->success ( "修改晒照成功！" );
        }
    }

    public function save_batch() {
        $success = 0;
        $count = count($_FILES ['fileImgs'] ['name']);
        if (!empty($_FILES ['fileImgs'] ['name'])) {
            $img = $this->upload();
            for($i=0; $i<$count; $i++) {
                $data ["images"] = $img[$i]['savename'];
                $data ["savepath"] = ltrim($img[$i]['savepath'], ".");
                $result = M('Share') -> add($data);
                if ($result) {
                    $success++;
                }
            }
        } else {
               $this->error ( "未上传图片！" );
        }

        if ($success == $count) {
            $this->success ( "添加图片成功！" );
        }
    }

    public function delImg() {
        $id = I('id');
        $result = M("Share") -> where('id='.$id) -> find();
        if ($result) {
            M('Share')->where('id=' . $id)->delete();
            $this->success('删除图片成功！');
        } else {
            $this->error('数据错误！');
        }
    }

}