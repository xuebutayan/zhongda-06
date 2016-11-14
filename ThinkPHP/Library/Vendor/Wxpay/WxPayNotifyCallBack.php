<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/4/21
 * Time: 14:26
 */
require_once "WxPayPubHelper.php";
class WxPayNotifyCallBack extends WxPayNotify {

    public function Queryorder($transaction_id)
    {
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = WxPayApi::orderQuery($input);
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;
    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg)
    {
        $notfiyOutput = array();

        if(!array_key_exists("transaction_id", $data)){
            $msg = "输入参数不正确";
            return false;
        }
        //查询订单，判断订单真实性
        if(!$this->Queryorder($data["transaction_id"])){
            $msg = "订单查询失败";
            return false;
        }

        // 支付成功，修改支付状态
        $order = M('Reward');
        $reward_info = explode("|", $data['out_trade_no']);
        $datas['trade_no'] = $reward_info[0];
        $datas['uid'] = $reward_info[1];
        $datas['gid'] = $reward_info[2];
        $datas['oid'] = $reward_info[3];
        $datas['transaction_id'] = $where['transaction_id'] = $data["transaction_id"];
        $datas['r_money'] = doubleval($reward_info[4])/100;

        $ids = M('Publicity') -> field('id') -> where('type=1') -> select();
        $ids_array = array();
        foreach ($ids as $k=>$v) {
            array_push($ids_array, $v['id']);
        }
        $datas['publicity'] = $ids_array[array_rand($ids_array, 1)];

        // 添加积分操作
        $points = M('User') -> where('id='.$datas['uid']) -> getField('points');
        M('User')-> where('id='.$datas['uid']) -> setField('points', intval($points)+2);

        $datas['pay_status'] = 1;
        if (!$order->where($where)->count()) {
            $order->add($datas);
        }


        return true;
    }

}