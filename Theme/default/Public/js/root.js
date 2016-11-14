/**
 * Created by Administrator on 2016/6/16.
 */

window.onload = function() {
    $is_wx = is_wechat_client();
    if (!$is_wx) {
        location.href="https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxe41c80e4a5e435d1&redirect_uri=http://public.diandongshenghuo.com/06/index.php?m=App&c=index&a=wx_return_url&state=1&response_type=code&scope=snsapi_userinfo&state=1&connect_redirect=1#wechat_redirect";
    }
}


function is_wechat_client(){
    var ua = navigator.userAgent.toLowerCase();
    if(ua.match(/MicroMessenger/i)=="micromessenger"){
        return true;
    }else{
        return false;
    }
}

// 设置背景图片的高度
function setBgHeight(_class) {
    // 获得手机屏幕的高度；
    _height = $(window).height();
    $(_class).height(_height);
}


