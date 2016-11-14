<?php
namespace Api\Controller;
use Think\Controller;
class PointsController extends Controller {
    
    public function _initialize()
	{	
        $this->appKey = '4RpgjB9jfAPeiPUXNqJx8n43dZCD';
        $this->appSecret = '25WPeUYC266epDaFmsNxfF8FJCDm';
	}
    
    /*
     * 默认方法
     */
    public function index(){

    }

    /**************************************************
     *  以下为兑吧接口代码
     */
    
    /**
     * 用户免登录
     */
    public function getLoginUrl(){
        $uid = I('uid');
        $points = $this->getPoints($uid);
        
        if(!empty($uid)) {
            $datas['url'] = $this->buildCreditAutoLoginRequest($this->appKey,$this->appSecret,$uid,$points);
        }

    }
    
    /**
     * 登录大转盘
     */
    public function getDrawUrl($uid){
        $appKey = $this->appKey;
        $appSecret = $this->appSecret;
        
        $points = $this->getPoints($uid);
        if (!empty($uid)) {
            $url = "http://www.duiba.com.cn/autoLogin/autologin?";
            $timestamp=time()*1000 . "";
            $redirect = "http://www.duiba.com.cn/shake/index/1423843";

            $array=array("uid"=>$uid,"credits"=>$points,"appSecret"=>$appSecret,"appKey"=>$appKey,"timestamp"=>$timestamp,"redirect"=>$redirect);
            $sign=$this->sign($array);
            $url = $url . "uid=" . $uid . "&credits=" . $points . "&appKey=" . $appKey . "&sign=" . $sign . "&timestamp=" . $timestamp . "&redirect=" . urlencode($redirect);
            return $url;
        }
    }
    
    /**
     * 登录兑换记录
     */
    public function getRecordUrl(){
        $appKey = $this->appKey;
        $appSecret = $this->appSecret;
        
        $uid = I('uid');
        $points = $this->getPoints($uid);
        if(!empty($uid)) {
            $url = "http://www.duiba.com.cn/autoLogin/autologin?";
            $timestamp=time()*1000 . "";
            $redirect = "http://www.duiba.com.cn/crecord/record";

            $array=array("uid"=>$uid,"credits"=>$points,"appSecret"=>$appSecret,"appKey"=>$appKey,"timestamp"=>$timestamp,"redirect"=>$redirect);
            $sign=$this->sign($array);
            $url = $url . "uid=" . $uid . "&credits=" . $points . "&appKey=" . $appKey . "&sign=" . $sign . "&timestamp=" . $timestamp . "&redirect=" . urlencode($redirect);
            $this->redirect($url);
        }

    }
    
    /** 
     * 扣除积分操作
     */
    public function pointsConsume(){
        $reques_array=I('get.'); 
        $result = $this->parseCreditConsume($this->appKey,$this->appSecret,$reques_array);
        
        if(!empty($result)){
            switch($result){
                case '1':
                    $this->json_error('','appKey错误');
                break;
                case '2':
                    $this->json_error('','timestamp错误');
                break;
                case '3':
                    $this->json_error('','verify错误');
                break;
                default:
                    $uid = (int)I('uid');
                    $points = (int)I('credits');
                    $orderNum = I('orderNum');
                    $description = I('description');
                    
                    $user = M('user')->where("id=".I('uid')." ")->field('points')->find();
                    if($user['points'] >= $points){
                        $result = M('user')->where("id=".I('uid')." ")->setDec('points',$points);
                        if($result){
                            
                            $array['oid'] = $bizid = date('YmdHis').$uid;
                            $array['doid'] = $orderNum;
                            $array['uid']=$uid;
                            $array['points']=$points;
                            $array['description'] = $description;
                            $array['state'] = '0';    
                            $savedata = M('duiba')->create($array);
                            if($savedata){
                                $insertid = M('duiba')->add();
                                if(!empty($insertid)){
                                    $result = array(
                                        'status' => 'ok',
                                        'message' => '兑换成功',
                                        'bizId' => $bizid,
                                        'credits'=>$user['points'] - $points
                                    );
                                    echo json_encode($result);
                                }else{
                                    $this->json_error('','数据写入失败');
                                }
                            }else{
                                $this->json_error('','数据写入失败');
                            }               
                        }else{
                            $this->json_error('','积分更新失败');
                        }
                    }else{
                        $this->json_error('','用户余额不足');
                    }
                break;
            }
            
        }else{
            $this->json_error('','系统错误');
        }
    }
    
    /** 
     * 兑换结果显示
     */
    public function result(){
        $reques_array=I('get.'); 
        $result = $this->parseCreditConsume($this->appKey,$this->appSecret,$reques_array);
        
        if(!empty($result)){
            switch($result){
                case '1':
                    $this->json_error('','appKey错误');
                break;
                case '2':
                    $this->json_error('','timestamp错误');
                break;
                case '3':
                    $this->json_error('','verify错误');
                break;
                default: 
                    $orderNum = I('orderNum');
                    if($result['success']){
                        M('duiba')->where("doid='".$orderNum."' ")->setField('state',1);
                        echo 'ok';
                    }else{
                        $result = M('duiba')->where("doid='".$orderNum."' ")->find();
                        if($result){
                            $points = $result['points'];
                            $uid = $result['uid'];
                            M('user')->where("uid=".$uid."")->setInc('points',$points);
                        }
                        echo 'fail';
                    }
                break;
            }            
        }else{
            $this->json_error('','系统错误');
        }
    }
    
    /**
     *  md5签名，$array中务必包含 appSecret
     */
    function sign($array){
        ksort($array);
        $string="";
        while (list($key, $val) = each($array)){
          $string = $string . $val ;
        }
        return md5($string);
    }
    /**
     *  签名验证,通过签名验证的才能认为是合法的请求
     */
    function signVerify($appSecret,$array){
        $newarray=array();
        $newarray["appSecret"]=$appSecret;
        reset($array);
        while(list($key,$val) = each($array)){
            if($key != "sign" ){
              $newarray[$key]=$val;
            }
            
        }
        $sign=$this->sign($newarray);
        if($sign == $array["sign"]){
        	return true;
        }
        return false;
    }
    
    /**
     *  生成自动登录地址
     *  通过此方法生成的地址，可以让用户免登录，进入积分兑换商城
     */
    function buildCreditAutoLoginRequest($appKey,$appSecret,$uid,$credits){
        $url = "http://www.duiba.com.cn/autoLogin/autologin?";
        $timestamp=time()*1000 . "";
        $array=array("uid"=>$uid,"credits"=>$credits,"appSecret"=>$appSecret,"appKey"=>$appKey,"timestamp"=>$timestamp);
        $sign=$this->sign($array);
        $url = $url . "uid=" . $uid . "&credits=" . $credits . "&appKey=" . $appKey . "&sign=" . $sign . "&timestamp=" . $timestamp;
        return $url;
    }
    
    /**
     *  生成订单查询请求地址
     *  orderNum 和 bizId 二选一，不填的项目请使用空字符串
     */
    function buildCreditOrderStatusRequest($appKey,$appSecret,$orderNum,$bizId){
        $url="http://www.duiba.com.cn/status/orderStatus?";
        $timestamp=time()*1000 . "";
        $array=array("orderNum"=>$orderNum,"bizId"=>$bizId,"appKey"=>$appKey,"appSecret"=>$appSecret,"timestamp"=>$timestamp);
        $sign=$this->sign($array);
        $url=$url . "orderNum=" . $orderNum . "&bizId=" . $bizId . "&appKey=" . $appKey . "&timestamp=" . $timestamp . "&sign=" . $sign ;
        return $url;
    }
    
    /**
     *  兑换订单审核请求
     *  有些兑换请求可能需要进行审核，开发者可以通过此API接口来进行批量审核，也可以通过兑吧后台界面来进行审核处理
     */
    function buildCreditAuditRequest($appKey,$appSecret,$passOrderNums,$rejectOrderNums){
        $url="http://www.duiba.com.cn/audit/apiAudit?";
        $timestamp=time()*1000 . "";
        $array=array("appKey"=>$appKey,"appSecret"=>$appSecret,"timestamp"=>$timestamp);
        if($passOrderNums !=null && !empty($passOrderNums)){
            $string=null;
            while(list($key,$val)=each($passOrderNums)){
                if($string == null){
                    $string=$val;
                }else{
                    $string= $string . "," . $val;
                }
            }
            $array["passOrderNums"]=$string;
        }
        if($rejectOrderNums !=null && !empty($rejectOrderNums)){ 
            $string=null;
            while(list($key,$val)=each($rejectOrderNums)){
                if($string == null){
                    $string=$val;
                }else{
                    $string= $string . "," . $val;
                }
            }
            $array["rejectOrderNums"]=$string;
         }
        $sign = sign($array);
        $url=$url . "appKey=".$appKey."&passOrderNums=".$array["passOrderNums"]."&rejectOrderNums=".$array["rejectOrderNums"]."&sign=".$sign."&timestamp=".$timestamp;  
        return $url; 
    }
    /**
     *  积分消耗请求的解析方法
     *  当用户进行兑换时，兑吧会发起积分扣除请求，开发者收到请求后，可以通过此方法进行签名验证与解析，然后返回相应的格式
     *  返回格式为：
     *  {"status":"ok","message":"查询成功","data":{"bizId":"9381"}} 或者
     *  {"status":"fail","message":"","errorMessage":"余额不足"}
     */
    function parseCreditConsume($appKey,$appSecret,$request_array){
        if($request_array["appKey"] != $appKey){
            return '1';
        }
        if($request_array["timestamp"] == null ){
            return '2';
        }
        $verify=$this->signVerify($appSecret,$request_array);
        if(!$verify){
            return '3';
        }
        $ret=array("appKey"=>$request_array["appKey"],"credits"=>$request_array["credits"],"timestamp"=>$request_array["timestamp"],"description"=>$request_array["description"],"orderNum"=>$request_array["orderNum"]);
        return $ret;
    }
    /**
     *  兑换订单的结果通知请求的解析方法
     *  当兑换订单成功时，兑吧会发送请求通知开发者，兑换订单的结果为成功或者失败，如果为失败，开发者需要将积分返还给用户
     */
    function parseCreditNotify($appKey,$appSecret,$request_array){ 
        if($request_array["appKey"] != $appKey){
        	return '1';
        }
        if($request_array["timestamp"] == null ){
        	return '2';
        }
        $verify=signVerify($appSecret,$request_array);
        if(!$verify){
        	return '3';
        }
        $ret=array("success"=>$request_array["success"],"errorMessage"=>$request_array["errorMessage"],"bizId"=>$request_array["bizId"]); 
        return $ret;
    }
    
    
    public function json_success($message = '', $data = array()) {
		
        $result = array(
			'status' => 'ok',
			'message' => $message,
			'data' => $data
		);

		echo json_encode($result);
		exit;
	}
    
    public function json_error($message = '', $error = '') {
		
        $result = array(
			'status' => 'fail',
			'message' => $message,
			'errorMessage' => $error
		);

		echo json_encode($result);
		exit;
	}

    public function getPoints($uid) {
        return M('User')->where('id='.$uid)->getField('points');
    }
    
}
?>