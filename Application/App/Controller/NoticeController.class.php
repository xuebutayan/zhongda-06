<?php
/**
 * 点动打赏 微信支付回调
 * ====================================================
 * 众达网络科技有限公司
 * 网站地址：http://diandongshenghuo.com
 * ====================================================
 * $ Id: NoticeController.class.php 2016/6/16 14:44 众达网络-CP $
 */
namespace APP\Controller;
use Think\Controller;
Vendor('Wxpay.WxPayNotifyCallBack');
class NoticeController extends Controller{
    public function notice() {

        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $this->logger($postStr);

        $notify = new \WxPayNotifyCallBack();
        $notify->Handle(false);

        if (isset($_GET)){
             echo "success";
         }

    }
    //日志记录
    function logger($log_content) {
        $max_size = 100000;
        $log_filename = "log.xml";
        if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
        file_put_contents($log_filename, date('H:i:s')." ".$log_content."\r\n", FILE_APPEND);
    }

}