<?php /* Smarty version Smarty-3.1.18, created on 2014-11-29 06:25:33
         compiled from "E:\webroot\breeze\application\view\index.html" */ ?>
<?php /*%%SmartyHeaderCode:283805479667eeb4b69-68940200%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '64dabac74a01763062b901f1e75e262b7e97dfa9' => 
    array (
      0 => 'E:\\webroot\\breeze\\application\\view\\index.html',
      1 => 1417242331,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '283805479667eeb4b69-68940200',
  'function' => 
  array (
  ),
  'cache_lifetime' => 3600,
  'version' => 'Smarty-3.1.18',
  'unifunc' => 'content_5479667ef22183_66770006',
  'variables' => 
  array (
    'name' => 0,
  ),
  'has_nocache_code' => false,
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5479667ef22183_66770006')) {function content_5479667ef22183_66770006($_smarty_tpl) {?><!DOCTYPE html>
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


<if condition="aasdf3333 and uuuuss" >
    dfdsf
    <elseif condition="dfdsf" />
    dsfdsf54545
</if>

<?php echo $_smarty_tpl->tpl_vars['name']->value;?>


</body>
</html><?php }} ?>
