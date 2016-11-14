/**
 * Created by Administrator on 2016/6/18.
 */


function loadRegion(sel,type_id,selName,url){
    jQuery("#"+selName+" option").each(function(){
        jQuery(this).remove();
    });
    jQuery("<option value=0>请选择</option>").appendTo(jQuery("#"+selName));
    if(jQuery("#"+sel).val()==0){
        return;
    }
    jQuery.getJSON(url,{pid:jQuery("#"+sel).val(),type:type_id},
        function(data){
            if(data.region){
                jQuery.each(data.region,function(idx,item){
                    jQuery("<option value="+item.region_id+">"+item.region_name+"</option>").appendTo(jQuery("#"+selName));
                });
            }else{
                jQuery("<option value='0'>请选择</option>").appendTo(jQuery("#"+selName));
            }
        }
    );
}