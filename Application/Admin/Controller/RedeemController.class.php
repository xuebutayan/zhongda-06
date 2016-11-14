<?php
/**
 * 点动打赏 兑换地点
 * ====================================================
 * 众达网络科技有限公司
 * 网站地址：http://diandongshenghuo.com
 * ====================================================
 * $ Id: RedeemController.class.php 2016/6/18 14:53 众达网络-CP $
 */
namespace Admin\Controller;
class RedeemController extends PublicController {

    /*--------------------------------------- */
    //-- 初始化函数
    /*--------------------------------------- */
    public function _initialize() {
        parent::_initialize ();
    }

    /*--------------------------------------- */
    //-- 兑换地址首页
    /*--------------------------------------- */
    public function index() {
        $this->model = M('Redeem');

        $count = $this->model ->count (); // 查询满足要求的总记录数
        $Page = new \Think\Page($count, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show (); // 分页显示输出

        // 显示
        $condition = 'r.type = 2';
        $field = "r.*, CASE WHEN r.status=1 THEN '显示' ELSE '隐藏' END AS red_show, re.name as parent_name";
        $result = $this->model -> alias('r') -> where($condition)
            -> field($field)
            -> join(C('DB_PREFIX').'redeem AS re ON re.id = r.parent_id')
            -> limit ( $Page->firstRow . ',' . $Page->listRows )->order("id desc") -> select();

        $this->assign ( "result", $result );
        $this->assign ( "page", $show ); // 赋值分页输出
        $this->assign ( "type", 'index');
        $this->display ();

    }

    /*--------------------------------------- */
    //-- 获得兑换地址列表
    /*--------------------------------------- */
    public function getRedeemList() {

    }

    /*--------------------------------------- */
    //-- 获得兑换地址列表
    /*--------------------------------------- */
    public function edit() {

        $id = I('id');


        if ($id) { // 编辑页面
            $this->model = M('Redeem');
            $condition['id'] = $id;
            $result = $this->model -> where($condition) -> find();
            // 获得市列表
            $citys = R('Api/Region/getRegionInfo', array($result['city']));
            $this->assign('city_id', $citys[0]['region_id']);
            // 获得区县列表
            $district = R('Api/Region/getDistrict', array($result['district']));
            $district_list = R('Api/Region/getDistrict', array('', $citys[0]['region_id']));
            $this->assign('district_id', $district[0]['region_id']);
            $this->assign('district_list', $district_list);
        } else { // 添加页面

        }

        // 获得一级分类列表
        $parent_list = M('Redeem') -> where('type=1 AND parent_id=0') -> field('id, name') -> select();

        // 获得省
        $province = R('Api/Region/getProvince', array('山东'));
        // 获得市
        $city = R('Api/Region/getCity', array('', $province[0]['region_id']));
        // 获得区县列表

        $this->assign('province', $province);
        $this->assign('city', $city);

        $this->assign('parent_list', $parent_list);
        $this->assign('redeem_res', $result);
        $this->assign('type', 'edit');
        $this->display('Redeem_index');
    }

    /*--------------------------------------- */
    //-- 获得兑换地址列表
    /*--------------------------------------- */
    public function save() {

        $this->model = M('Redeem');

        $redeem = I('post.');
        $datas['type'] = 2;
        $datas['parent_id'] = $redeem['parent_id'];
        $datas['name'] = $redeem['name'];
        $datas['province'] = $redeem['province'];
        $datas['city'] = $redeem['city'];
        $datas['district'] = $redeem['district'];
        $datas['address'] = $redeem['address'];
        $datas['start_time'] = $redeem['start_time'];
        $datas['end_time'] = $redeem['end_time'];
        $datas['notice'] = $redeem['notice'];
        $datas['mobile'] = $redeem['mobile'];
        $datas['status'] = $redeem['status'];
        if ($_FILES ['addimage'] ['name'] !== '') {
            $img = $this->upload ();
            $datas ["image"] = $img [addimage] [savename];
            $datas ["savepath"] = ltrim($img [addimage] [savepath], ".");
        }
        if ($redeem['id']) {
            $condition['id'] = $redeem['id'];
            $result = $this->model -> where($condition) -> save($datas);
            if ($result) {
                $this->success('数据修改成功', U('Admin/Redeem/index'));
            }
        } else {
            $datas['time'] = date('Y-m-s H:i:s', time());
            $result = $this->model  -> add($datas);
            if ($result) {
                $this->success('数据添加成功', U('Admin/Redeem/index'));
            }
        }

    }


    /*--------------------------------------- */
    //-- 获得兑换记录
    /*--------------------------------------- */
    public function getLogs($rid = 0) {


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
        $this->assign('type', 'list');
        $this->display('Redeem_log');
    }

    /*--------------------------------------- */
    //-- 获得兑换数据汇总
    /*--------------------------------------- */
    public function collect() {

        $type = I('type', 'list');
        $m = M();
        // 获得每日信息汇总
        $nowdate = I('nowdate',date('Y-m-d', time()));
        $now_info =  $m
            -> table(
                '(select count(id) AS user_num from '.C('DB_PREFIX').'user where substr(time, 1, 10) = "'.$nowdate.'") AS u,'.
                '(select count(id) AS order_num from '.C('DB_PREFIX').'order where substr(time, 1, 10) = "'.$nowdate.'") AS o,'.
                '(select sum(r_money) AS reward from '.C('DB_PREFIX').'reward where substr(time, 1, 10) = "'.$nowdate.'") AS r,'.
                '(select count(id) AS redeem from '.C('DB_PREFIX').'redeem_log where substr(time, 1, 10) = "'.$nowdate.'") AS rl'
            )
            -> field('u.user_num, o.order_num, r.reward, rl.redeem')
            -> select();

        // 获得兑换总数据
        $total['total_users'] = M('User')->count(); // 总用户数
        $total['apply_nums'] = M('Order')->count(); // 总报名人数
        $total['valid_nums'] = count(M('Reward')->field('id')->group('oid')->select());
        $total['redeem_nums'] = M('Redeem_log')->count() ;  // 总兑换人数
        $total['can_redeem'] = M('Order') -> where('order_status=2') -> count();
        $total['reward_money'] = M('Reward') -> where('pay_status=1') -> sum('r_money') ;  // 总打赏金额

        $this->assign('type', $type);

        $this->assign('now_info', $now_info);
        $this->assign('nowdate', $nowdate);
        $this->assign('total', $total);
        $this->display('Collect_index');
    }

    public function goodCollect() {
        $type = I('type','good');
        // 商品信息汇总
        $good_info = M('good') -> alias('g')
            -> join('(select gid, count(id) as apply_nums from '.C('DB_PREFIX').'order GROUP BY gid) AS o ON o.gid = g.id', 'LEFT')
            -> join('(select gid, count(id) as can_redeem from '.C('DB_PREFIX').'order WHERE order_status = 2 GROUP BY gid) AS o2 ON o2.gid = g.id', 'LEFT')
            -> join('(select gid, count(id) as redeem_nums from '.C('DB_PREFIX').'redeem_log GROUP BY gid) AS rl ON rl.gid = g.id', 'LEFT')
            -> join('(select gid, count(gid) as valid_nums from (select gid from '.C('DB_PREFIX').'reward GROUP BY oid) AS re GROUP BY gid) AS r ON r.gid = g.id', 'LEFT')
            -> join('(select gid, sum(r_money) as reward_money from '.C('DB_PREFIX').'reward GROUP BY gid) AS rm ON rm.gid = g.id', 'LEFT')
            -> field('id, name, IFNULL(apply_nums, 0) AS apply_nums, IFNULL(valid_nums, 0) AS valid_nums, IFNULL(redeem_nums, 0) AS redeem_nums, IFNULL(can_redeem, 0) AS can_redeem, IFNULL(reward_money, 0) AS reward_money')
            -> order('id DESC') -> select();
        $this->assign('good', $good_info);
        $this->assign('type', $type);
        $this->display('Collect_index');
    }

    public function redeemCollect() {
        $type = I('type','redeem');
        // 兑换地点信息
        $def_red = M('Redeem')->order('id ASC')->getField('id');
        $redeem = I('redeem', $def_red);

        // 获得兑换地点信息
        $redeem_list = M('Redeem') -> field('id, name')-> order('id DESC') -> select();
        $redeems = M('Redeem') -> field('id, name') -> where('id='.$redeem) -> order('id DESC') -> select();
        $this->assign('redeem_list', $redeem_list);
        $this->assign('redeems', $redeems);


        $redeem_info = M('good') -> alias('g')
            -> join('(select redeem, gid, count(id) as can_redeem from '.C('DB_PREFIX').'order WHERE order_status = 2 AND redeem ='.$redeem.' GROUP BY gid) AS o ON o.gid = g.id', 'LEFT')
            -> join('(select rid, gid, count(id) as redeem_nums from '.C('DB_PREFIX').'redeem_log WHERE rid = '.$redeem.' GROUP BY gid) AS rl ON rl.gid = g.id', 'LEFT')
            -> field('id, name, rid, redeem, IFNULL(redeem_nums, 0) AS redeem_nums, IFNULL(can_redeem, 0) AS can_redeem')
            -> order('id DESC') -> select();
        $this->assign('type', $type);
        $this->assign('redeem', $redeem);
        $this->assign('redeem_info', $redeem_info);
        $this->display('Collect_index');
    }

    public function del() {
        $id = I('id');

        if ($id) {
            $result = M('Redeem') -> where('id='.$id) -> delete();
            if ($result) {
                $this->success('数据删除成功！');
            } else {
                $this->error('数据删除失败！');
            }
        } else {
            $this->error('数据错误！');
        }
    }


    public function ParentList() {
        $this->model = M('Redeem');

        $count = $this->model ->count (); // 查询满足要求的总记录数
        $Page = new \Think\Page($count, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('theme', "<ul class='pagination no-margin pull-right'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $show = $Page->show (); // 分页显示输出

        // 显示
        $condition['type'] = 1;
        $condition['parent_id'] = 0;
        $field = "*, CASE WHEN status=1 THEN '显示' ELSE '隐藏' END AS red_show";
        $result = $this->model -> where($condition)
            -> field($field)
            -> limit ( $Page->firstRow . ',' . $Page->listRows )->order("id desc") -> select();

        $this->assign ( "result", $result );
        $this->assign ( "page", $show ); // 赋值分页输出
        $this->assign ( "type", 'parent');
        $this->display ('Redeem_index');
    }

    public function parent_edit() {
        $id = I('id');

        if ($id) { // 编辑页面
            $this->model = M('Redeem');
            $condition['id'] = $id;
            $result = $this->model -> where($condition) -> find();

        } else { // 添加页面

        }

        $this->assign('redeem_res', $result);
        $this->assign('type', 'parent_edit');
        $this->display('Redeem_index');
    }

    public function saveParent() {
        $this->model = M('Redeem');

        $redeem = I('post.');
        $datas['type'] = 1;
        $datas['parent_id'] = 0;
        $datas['name'] = $redeem['name'];
        $datas['address'] = $redeem['address'];
        $datas['mobile'] = $redeem['mobile'];
        $datas['status'] = $redeem['status'];

        if ($redeem['id']) {
            $condition['id'] = $redeem['id'];
            $result = $this->model -> where($condition) -> save($datas);
            if ($result) {
                $this->success('数据修改成功', U('Admin/Redeem/ParentList'));
            }
        } else {
            $datas['time'] = date('Y-m-d H:i:s', time());
            $result = $this->model  -> add($datas);
            if ($result) {
                $this->success('数据添加成功');
            }
        }
    }

}