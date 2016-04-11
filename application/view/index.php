
<?php

$where  = array();
if( !empty($phone)){ //用户手机号码
    $where[]=sprintf('u.phone="%s"',$phone);
}
if( !empty($openId)){ //用户手机号码
    $where[]=sprintf('u.wxOpenId="%s"',$openId);
}
if( empty($where) )
 return false;
$where=' ('.implode(' or ', $where ).') ';





?>


<!DOCTYPE html>
<html xmlns:s="sss" >
<head>
    <title>test</title>
    <script src="application/public/jquery-1.7.2.js" type="text/javascript" ></script>


    <script>

         $(function(){

           var controller=$('div[controller]');

           controller.on('change','[bindData]',function(event){
                var prop=$(this).attr('bindData');
                $(this).trigger('data:changed',[prop,$(this).val()]);
            }).on('data:changed',function(event,pro,val){

                $('[bindData='+pro+']',this).each(function(){
                   $(this).val(val);
                })
             })

             $.extend(controller,{

                data:{},
                get:function(property){
                   return this.data[property];
                },
                set :function(property,value){
                    this.data[property]=value;
                    $(this).trigger('data:changed',[property,value]);
                }

            })

            controller.set('name','0022')

            controller.set('names','uuu')

         })

    </script>


</head>
<body>

<include name="test1" />

<include name="test1" >
    <include name="test2" >
        <include name="test3" >
        </include>
    </include>
</include>

<div controller="index">

   <input bindData="name"  />
   <input bindData="name"  />
中国人民解放军
</div>
<var name="pppp" value="9999999" />

<loop name="test" key="key" value="items" >
  <li><echo name="items" /></li>
</loop>

<if condition="aasdf00000" >
    dfdsf
    <elseif condition="dfdsf" />
    dsfdsf54545
</if>


<if condition="aasdf3333" >
    dfdsf
    <elseif condition="dfdsf" />
    dsfdsf54545


</body>
</html>