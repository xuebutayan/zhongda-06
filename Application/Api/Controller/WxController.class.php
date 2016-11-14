<?php
/**
 * 点动打赏 微信功能接口
 * ====================================================
 * 众达网络科技有限公司
 * 网站地址：http://diandongshenghuo.com
 * ====================================================
 * $ Id: WxController.class.php 2016/6/15 10:55 众达网络-CP $
 */
namespace Api\Controller;
use Think\Controller;
Vendor('Wechat.JSSDK');
Vendor('Wxpay.WxPayPubHelper');

class WxController extends Controller {

    /*--------------------------------------- */
    //-- 初始化函数
    /*--------------------------------------- */
    public function _initialize() {

        $this->app_id = C('WX_CONFIG.WX_APPID');
        $this->app_secret = C('WX_CONFIG.WX_APPSECRET');

    }

    /*--------------------------------------- */
    //-- 首页
    /*--------------------------------------- */
    public function index() {
        echo 'This is wx app!';
    }

    /*--------------------------------------- */
    //-- 获取微信授权链接
    //-- @param mixed $state 参数
    /*--------------------------------------- */
    public function get_authorize_url($state = '', $redirect_uri, $scope = 'snsapi_userinfo')
    {
        $redirect_uri = urlencode($redirect_uri);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->app_id}&redirect_uri={$redirect_uri}&response_type=code&scope={$scope}&state={$state}#wechat_redirect";
    }

    /*--------------------------------------- */
    //-- 获取授权token
    //-- @param string $code 通过get_authorize_url获取到的code
    /*--------------------------------------- */
    public function get_access_token($app_id = '', $app_secret = '', $code = '')
    {
        $token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->app_id}&secret={$this->app_secret}&code={$code}&grant_type=authorization_code";
        $token_data = $this->http($token_url);

        if($token_data[0] == 200)
        {
            return json_decode($token_data[1], TRUE);
        }

        return FALSE;
    }

    /*--------------------------------------- */
    //-- 获取授权后的微信用户信息
    //-- @param string $access_token
    //-- @param string $open_id
    /*--------------------------------------- */
    public function get_user_info($access_token = '', $open_id = '')
    {
        if($access_token && $open_id)
        {
            $info_url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$open_id}&lang=zh_CN";
            $info_data = $this->http($info_url);

            if($info_data[0] == 200)
            {
                return json_decode($info_data[1], TRUE);
            }
        }

        return FALSE;
    }

    /*--------------------------------------- */
    //-- 验证授权
    //-- @param string $access_token
    //-- @param string $open_id
    /*--------------------------------------- */
    public function check_access_token($access_token = '', $open_id = '')
    {
        if($access_token && $open_id)
        {
            $info_url = "https://api.weixin.qq.com/sns/auth?access_token={$access_token}&openid={$open_id}&lang=zh_CN";
            $info_data = $this->http($info_url);

            if($info_data[0] == 200)
            {
                return json_decode($info_data[1], TRUE);
            }
        }

        return FALSE;
    }

    /*--------------------------------------- */
    //-- 获得微信JSSDK 配置信息
    /*--------------------------------------- */
    public function getSignPackage() {
        $jssdk = new \JSSDK($this->app_id, $this->app_secret);
        return $jssdk->getSignPackage();
    }

    /*--------------------------------------- */
    //-- 微信支付接口
    /*--------------------------------------- */
    public function wxPay() {
        //①、获取用户openid
        //打赏人员信息
        $pay_info = I('post.');
        $trade_no = 'DS' . date ( "ymdhis" ) . mt_rand ( 1, 9 ) .'|'.$pay_info['uid'].'|'.$pay_info['gid'].'|'.$pay_info['oid'].'|'.doubleval($pay_info['pay_price'])*100;
        // 回调链接
        $WxPayConf = C("WxPayConf_pub");
        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody('点动打赏');
        $input->SetAttach('点动打赏');
        $input->SetOut_trade_no($trade_no);
        $input->SetTotal_fee(doubleval($pay_info['pay_price'])*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetNotify_url($WxPayConf['NOTIFY_URL']);
        $input->SetTrade_type("JSAPI");
        $tools = new \JsApiPay();
        $input->SetOpenid($pay_info['openid']);
        $order = \WxPayApi::unifiedOrder($input);

        $jsApiParameters = $tools->GetJsApiParameters($order);
        return $jsApiParameters;
    }


    /*--------------------------------------- */
    //-- 跳转链接
    /*--------------------------------------- */
    public function http($url, $method, $postfields = null, $headers = array(), $debug = false)
    {
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);

        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty($postfields)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                    $this->postdata = $postfields;
                }
                break;
        }
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);

        $response = curl_exec($ci);
        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);

        if ($debug) {
            echo "=====post data======\r\n";
            var_dump($postfields);

            echo '=====info=====' . "\r\n";
            print_r(curl_getinfo($ci));

            echo '=====$response=====' . "\r\n";
            print_r($response);
        }
        curl_close($ci);
        return array($http_code, $response);
    }


}