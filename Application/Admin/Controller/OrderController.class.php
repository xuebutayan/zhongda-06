<?php
namespace Admin\Controller;

class OrderController extends PublicController {

	/*--------------------------------------- */
	//-- Admin 初始化函数
	/*--------------------------------------- */
	function _initialize() {
		parent::_initialize ();
		$this->model = M ("order");
	}

	/*--------------------------------------- */
	//-- Admin 获得订单列表
	/*--------------------------------------- */
	public function index() {
		$m = D ( "Order" );


		// 筛选
		$orderid = I('orderid');
		$redeem = I('redeem');
		$gid = I('gid', 0);
		$order_status = I('order_status');
		$begindate = I('begindate');
		$enddate = I('enddate');

		$where = ' 1 ';
		if ($orderid) {
			$where .= ' AND o.orderid like "%'.$orderid.'%"';
		}
		if ($redeem) {
			$where .= ' AND o.redeem = '.$redeem;
		}
		if ($order_status) {
			$where .= ' AND o.order_status = '.$order_status;
		}
		if ($gid) {
			$where .= ' AND o.gid ='.$gid;
		}
		if ($begindate && $enddate) {
			if ($begindate <= $enddate) {
				$where .= ' AND o.time >="'.$begindate.'" AND o.time <="'.$enddate.'"';
			} else {
				$this->error('结束时间应大于开始时间！');
			}
		}


		$count = $this->model -> alias('o') -> where($where) ->count (); // 查询满足要求的总记录数
		$Page = new \Think\Page($count, 10);// 实例化分页类 传入总记录数和每页显示的记录数
		$Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
		$Page->parameter['redeem'] = $redeem;
		$Page->parameter['order_status'] = $order_status;
		$Page->parameter['gid'] = $gid;
		$Page->parameter['begindate'] = $begindate;
		$Page->parameter['enddate'] = $enddate;
		$show = $Page->show (); // 分页显示输出

		$field = " o.id, o.orderid, o.totalprice, o.uid, o.username, o.order_status, o.mobile, o.time, u.wx_nickname, g.name as good_name, ".
			     " CASE WHEN o.redeem = 0 THEN '未兑换' ELSE r.name END AS red_name, ".
			     " IFNULL(w.r_money, 0) as has_r_money,round(((g.old_price * g.price_rate - g.price) / g.reward_rate), 2) as total_r_money, ".
			     " CASE WHEN order_status = 1 THEN '已报名' WHEN order_status = 2 THEN '可兑换' WHEN order_status = 3 THEN '已完成' END AS status";
		$result = $this->model -> alias('o')
			-> join(C('DB_PREFIX').'redeem AS r ON r.id = o.redeem', 'LEFT')
			-> join(C('DB_PREFIX').'user AS u ON u.id = o.uid', 'LEFT')
			-> join(C('DB_PREFIX').'good AS g ON g.id = o.gid', 'LEFT')
			-> join('(SELECT oid, IFNULL(SUM(r_money),0) AS r_money FROM '.C('DB_PREFIX').'reward GROUP BY oid) AS w ON w.oid = o.id', 'LEFT')
			-> field($field)
			-> where($where)
			-> limit ( $Page->firstRow . ',' . $Page->listRows )->order("o.id desc") ->select ();


		// 获得兑换地点列表
		$redeems = M('Redeem') -> field('id, name') -> order('id DESC') -> select();
		// 获得商品列表
		$goods_list = M('Good') -> field('id, name') -> order('id DESC') -> select();

		$this->assign('goods_list', $goods_list);
		$this->assign('redeems', $redeems);
		$this->assign ( "result", $result );
		$this->assign ( "page", $show ); // 赋值分页输出
		$this->assign ( "type", 'order_list');
		$this->display ();
	}

	/*--------------------------------------- */
	//-- Admin 根据ID获得订单详情
	/*--------------------------------------- */
	public function getOrderInfo() {
		$id = I("id");
		$field = " o.id, o.orderid, o.totalprice, o.uid, o.username, o.mobile, o.gid, g.name, g.old_price * g.price_rate as price, o.time, r.name as rname, u.wx_nickname, ".
			"CASE WHEN order_status = 1 THEN '已报名' WHEN order_status = 2 THEN '可兑换' WHEN order_status = 3 THEN '已完成' END AS status";
		$result = $this->model -> alias('o')
			    -> join(C('DB_PREFIX')."redeem AS r ON r.id = o.redeem", 'LEFT')
				-> join(C('DB_PREFIX')."good AS g ON g.id = o.gid", 'LEFT')
			    -> join(C('DB_PREFIX')."user AS u ON u.id = o.uid", 'LEFT')
				-> field($field)
				-> where ('o.id='.$id)->find ();

		// 获得打赏人员列表
		$reward_info = R('Api/RewardUsers/getRewardUsers', array($id));
		$result['reward_info'] = $reward_info;
		$this->assign ( "id", $id );
		$this->assign ( "order_info", $result );
		$this->assign ( "type", 'order_info');
		$this->display ('Order_index');
	}

	/*--------------------------------------- */
	//-- Admin 删除订单
	/*--------------------------------------- */
	public function del(){
		$result = R ( "Api/Api/delorder", array (I("get.id")) );
		$this->success ( "操作成功" );
	}

	/*--------------------------------------- */
	//-- Admin 删除商品
	/*--------------------------------------- */
	public function publish(){
		$result = R ( "Api/Api/publish", array (I("get.id")) );
		$this->success ( "操作成功" );
	}

	/*--------------------------------------- */
	//-- Admin 删除商品
	/*--------------------------------------- */
	public function payComplete(){
		$result = R ( "Api/Api/payComplete", array (I("get.id")) );
		$this->success ( "操作成功" );
	}

	/*--------------------------------------- */
	//-- Admin 交易验证码完成交易页面
	/*--------------------------------------- */
	public function order_check() {

		// 获得当前管理员相关信息
		$redeem_id = M('Admin')->where('username="'.$_SESSION['wadmin'].'"')->getField('redeem');

		// 获得兑换地点信息
		$redeem_info = R('Api/Redeem/getRedeemInfo', array($redeem_id));

		$this->assign('redeem_info', $redeem_info);
		$this->display('Order_Check');
	}

	public function getOrderDetial() {
		// 获得当前管理员相关信息
		$redeem_id = M('Admin')->where('username="'.$_SESSION['wadmin'].'"')->getField('redeem');

		// 获得兑换地点信息
		$redeem_info = R('Api/Redeem/getRedeemInfo', array($redeem_id));


		$trade_no = I('trade_no');
		$O = M('Order');
		$filed = "o.id, o.gid, g.savepath, g.image,o.totalprice, r.name AS rname, o.order_status, g.old_price*g.price_rate as now_price, g.name, o.username, o.mobile";
		$where['orderid'] = array('like','%'.$trade_no);
		$result = $O->alias('o')
			->join(C('DB_PREFIX').'good AS g ON g.id = o.gid', 'LEFT')
			->join(C('DB_PREFIX').'redeem AS r ON r.id = o.redeem', 'LEFT')
			->field($filed)
			->where($where)->find();

		if ($result['order_status'] == 1) {
			// 获得该订单已打赏人数，及剩余价格
			$order_info = R('Api/RewardUsers/getRewardInfo', array($result['id'], $result['gid']));
			$this->assign("order_info", $order_info);
		} elseif ($result['order_status'] == 3) {
			$redeem_name = M('Redeem_log') -> alias('rl')
				->join(C('DB_PREFIX').'redeem AS r ON r.id = rl.rid', 'LEFT')
				-> where('orderid="'.$trade_no.'"')
				-> getField('r.name AS rname');
			$this->assign('redeem_name', $redeem_name);
		}

		$this->assign('redeem_info', $redeem_info);
		$this->assign('trade_no', $trade_no);
		$this->assign('order', $result);
		$this->display('Order_Check');
	}
	public function checkTradeNo() {
		$trade_no = I('trade_no');
		$O = M('Order');
		$where['orderid'] = array('like','%'.$trade_no);
		$result = $O->where($where)->count();
		if ($result) {
			$this->ajaxReturn(array('is_err'=>'0'));
		} else {
			$this->ajaxReturn(array('is_err'=>'1', 'msg'=>"未找到订单信息！"));
		}
	}

	public function setOrderStatus() {
		$trade_no = I('trade_no');
		$rid = I('rid');
		$where['orderid'] = array('like','%'.$trade_no.'%');
		$O = M('Order');
		$result = $O-> where($where) -> setField(array('order_status'=>3, 'redeem'=>$rid));
		if ($result) {
			$datas['orderid'] = $trade_no;
			$datas['rid'] = $rid;
			$datas['admin'] = session('wadmin');
			$datas['gid'] = I('gid');
			$datas['time'] = date('Y-m-d H:i:s', time());
			M('Redeem_log') -> add($datas);
			$this->ajaxReturn(array('is_err'=>0));
		} else {
			$this->ajaxReturn(array('is_err'=>1, 'err_msg'=>'数据修改失败！'));
		}
	}

	public function getRewardLog() {
		$rid = I('rid');
		$count = M('Redeem_log') ->count (); // 查询满足要求的总记录数
		$Page = new \Think\Page($count, 10);// 实例化分页类 传入总记录数和每页显示的记录数
		$Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
		$show = $Page->show (); // 分页显示输出

		if ($rid) {
			$condition['rid'] = $rid;
		}
		$result = M('Redeem_log') ->alias('rl')
			-> join(C('DB_PREFIX').'good AS g ON g.id = rl.gid', 'LEFT')
			-> join(C('DB_PREFIX').'redeem AS r ON r.id = rl.rid', 'LEFT')
			-> field('rl.orderid, rl.id, g.name as gname, r.name as rname, rl.admin, rl.time')
			-> limit ( $Page->firstRow . ',' . $Page->listRows )
			-> where($condition) -> select();

		$this->assign ( "page", $show ); // 赋值分页输出

		$this->assign('list', $result);
		$this->display('Order_checklist');
	}

}