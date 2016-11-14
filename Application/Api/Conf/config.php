<?php
return array(
	//'配置项'=>'配置值'
    //========= 微信 基本信息设置 =============
    'WX_CONFIG' => array(
        // - 绑定支付的APPID
        'WX_APPID' => 'wxe41c80e4a5e435d1',
        // - 公众帐号secert
        'WX_APPSECRET' => '53e1449fdb7463d1a1f2df008cc42f83',
        // - 微信 回调接口地址
        'WX_REDIRECT_URI' => "http://public.diandongshenghuo.com/06/index.php?m=App&c=index&a=wx_return_url",
    ),
    //========= 微信 支付回调接口设置 =============
    'WxPayConf_pub'=>array(

        'NOTIFY_URL' =>  'http://public.diandongshenghuo.com/06/index.php/App/Notice/notice/ ',
    ),

);