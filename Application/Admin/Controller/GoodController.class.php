<?php
namespace Admin\Controller;
use Think\Image;
use Think\Upload;

class GoodController extends PublicController {
	function _initialize() {
		parent::_initialize ();
	}

	/*--------------------------------------- */
	//-- Admin 商品列表页面
	/*--------------------------------------- */
	public function index() {
		$type = I('type', 'list');
		$m = M ( "Good" );

		$count = $m->count (); // 查询满足要求的总记录数

		$Page = new \Think\Page($count,12);// 实例化分页类 传入总记录数和每页显示的记录数(25)
		$Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");

		$show = $Page->show (); // 分页显示输出
		$field = " *, old_price  as shopping_price";
		$oby = " sort DESC, id DESC ";
		$result = $m-> field($field) -> order($oby) -> limit ( $Page->firstRow . ',' . $Page->listRows )->select ();
		
		$this->assign ( "page", $show ); // 赋值分页输出
		$this->assign ( "result", $result );
		$this->assign ('type', $type);
		$this->display ();
	}

	/*--------------------------------------- */
	//-- Admin 保存商品信息
	/*--------------------------------------- */
	public function saveGood() {

		$datagood = I("post.");
		if ($datagood["goodid"]) {
			$data ["id"] = $datagood["goodid"];
			$data ["name"] = $datagood["addname"];
			$data ["old_price"] = $datagood["old_price"];
			$data ["price"] = $datagood["price"];
			$data ["reward_rate"] = $datagood["reward_rate"];
			$data ["good_num"] = $datagood["good_num"];
			$data ["price"] = $datagood["price"];
			$data ["is_new"] = $datagood["is_new"];
			$data ["sort"] = $datagood["addsort"];

			// 添加打赏金额及限购次数管理
			$data ["is_limit"] = $datagood['is_limit'];
			$data ["has_one"] = empty($datagood['has_one']) ? 0 : $datagood['has_one'];
			$data ["has_five"] = empty($datagood['has_five']) ? 0 : $datagood['has_five'];

			if ($datagood['begin_date'] != '' && $datagood['end_date'] != '') {
				if ($datagood['begin_date'] > $datagood['end_date']) {
					$this->error("活动开始时间不能大于结束时间");
				} else {
					$data ["begin_date"] = $datagood["begin_date"];
					$data ["end_date"] = $datagood["end_date"];
				}
			}

			if ($_FILES ['addimage'] ['name'] !== '') {
				$img = $this->upload ();
				$data ["image"] = $img [addimage] [savename];
				$data ["savepath"] = ltrim($img [addimage] [savepath], ".");
			}
			$data ["status"] = $datagood["addstatus"];
			$detail = I("post.editorValue",'','stripslashes');
			if ($detail) {
				$data ["detail"] = $detail;
			}
			$result = R ( "Api/Good/saveGood", array($data) );
			if ($result) {
				$this->success ( "修改商品成功！", U('Admin/Good/index') );
			} else {
				$this->error ( "数据没有任何改变！", U('Admin/Good/index') );
			}
		}else{
			$data ["good_num"] = $datagood["good_num"];
			$data ["price"] = $datagood["price"];
			$data ["name"] = $datagood["addname"];
			$data ["old_price"] = $datagood["old_price"];
			$data ["price"] = $datagood["price"];
			$data ["reward_rate"] = $datagood["reward_rate"];
			$data ["is_new"] = $datagood["is_new"];
			$data ["sort"] = $datagood["addsort"];

			// 添加打赏金额及限购次数管理
			$data ["is_limit"] = $datagood['is_limit'];
			$data ["has_one"] = empty($datagood['has_one']) ? 0 : $datagood['has_one'];
			$data ["has_five"] = empty($datagood['has_five']) ? 0 : $datagood['has_five'];

			if ($_FILES ['addimage'] ['name'] !== '') {
				$img = $this->upload ();
				$data ["image"] = $img [addimage] [savename];
				$data ["savepath"] = ltrim($img [addimage] [savepath], ".");
			} else {
				$this->error ( "未上传图片！" );
			}
			$data ["status"] = $datagood["addstatus"];
			if ($datagood['begin_date'] != '' && $datagood['end_date'] != '') {
				if ($datagood['begin_date'] > $datagood['end_date']) {
					$this->error("活动开始时间不能大于结束时间");
				} else {
					$data ["begin_date"] = $datagood["begin_date"];
					$data ["end_date"] = $datagood["end_date"];
				}
			}
			$detail = I("post.editorValue",'','stripslashes');
			if ($detail) {
				$data ["detail"] = $detail;
			}
			$result = R ( "Api/Good/saveGood", array($data) );
			if ($result) {
				$this->success ( "添加商品成功！", U('Admin/Good/index') );
			} else {
				$this->error ( "数据没有任何改变！", U('Admin/Good/index') );
			}
		}
	}

	/*--------------------------------------- */
	//-- Admin 删除商品
	/*--------------------------------------- */
	public function delgood() {
		$result = R ( "Api/Good/delGood", array (I("get.id")) );
		if ($result) {
			$this->success ( "删除商品成功！", U('Admin/Good/index') );
		}
	}

	/*--------------------------------------- */
	//-- Admin 根据ID获得商品详细信息
	/*--------------------------------------- */
	public function getGoodById() {

		$id = I("id");
		$type = I('type', 'edit');
		if ($id) {
			$result = M ( "Good" )->where (array ("id" => $id))->find ();
			$this->assign('result', $result);
			$this->assign('goodid', $id);
		}
		$this->assign('type', $type);
		$this->display('Good_index');
	}

	/*--------------------------------------- */
	//-- Admin 设置商品的兑换地点
	/*--------------------------------------- */
	public function setGoodRedeem() {

		$condition['id'] = $id = I('gid');
		// 获得商品信息
		$good_info = M('Good') -> where($condition) -> find();

		// 获得兑换地点列表
		$redeem_list = R('Api/Redeem/getListById', array($id));

		$this->assign('gid', $id);
		$this->assign('good_info', $good_info);
		$this->assign('redeem_list', $redeem_list);
		$this->display('Good_redeem');
	}

	/*--------------------------------------- */
	//-- Admin 保存商品兑换地点
	/*--------------------------------------- */
	public function saveRedeem() {
		$id = I('gid');
		$can_select = I('can_select');
		$redeem_list = I('redeem_list');
		$datas = array();
		foreach($redeem_list as $v){
			$datas['redeem_list'] .= $v.',';
		}
		$datas['can_select'] = $can_select;
		$result = M('Good') -> where('id='.$id) -> save($datas);
		if ($result) {
			$this->success('商品兑换地址分配成功', U('Admin/Good/index'));
		} else {
			$this->error('数据没有任何变化');
		}

	}

}