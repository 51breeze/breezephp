<?php /*%%SmartyHeaderCode:548546178be720371-70412008%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'fee6dff1d3937c232d2243983c2e47b93e3b08e4' => 
    array (
      0 => 'D:\\wamp\\www\\breeze\\application\\view\\index.php',
      1 => 1417087004,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '548546178be720371-70412008',
  'cache_lifetime' => 3600,
  'version' => 'Smarty-3.1.18',
  'unifunc' => 'content_5477081e9252e5_34924945',
  'has_nocache_code' => false,
),true); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5477081e9252e5_34924945')) {function content_5477081e9252e5_34924945($_smarty_tpl) {?><!DOCTYPE html>
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

<include file="common" />

<include file="test" >
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

</body>
</html><?php }} ?>
