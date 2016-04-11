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
  'cache_lifetime' => 3600,
  'version' => 'Smarty-3.1.18',
  'unifunc' => 'content_547966ddd7c8f0_85724870',
  'variables' => 
  array (
    'name' => 0,
  ),
  'has_nocache_code' => false,
),true); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_547966ddd7c8f0_85724870')) {function content_547966ddd7c8f0_85724870($_smarty_tpl) {?><!DOCTYPE html>
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


Notice: Undefined index: name in E:\webroot\breeze\application\libs\smarty\sysplugins\smarty_internal_templatebase.php(151) : eval()'d code on line 109

Call Stack:
    0.0000     121704   1. {main}() E:\webroot\breeze\index.php:0
    0.0010     122936   2. require_once('E:\webroot\breeze\breeze\Breeze.php') E:\webroot\breeze\index.php:4
    0.0050     226624   3. breeze\core\start() E:\webroot\breeze\breeze\Breeze.php:15
    0.0580     728328   4. breeze\core\Application->start() E:\webroot\breeze\breeze\core\Core.php:182
    0.0600     736440   5. breeze\core\Router->dispatcher() E:\webroot\breeze\breeze\core\Application.php:34
    0.0630     785528   6. call_user_func() E:\webroot\breeze\breeze\core\Router.php:60
    0.0630     785528   7. application\controller\Index->index() E:\webroot\breeze\breeze\core\Router.php:60
    0.0770    1431480   8. Smarty_Internal_TemplateBase->display() E:\webroot\breeze\application\controller\Index.php:32
    0.0770    1431624   9. Smarty_Internal_TemplateBase->fetch() E:\webroot\breeze\application\libs\smarty\sysplugins\smarty_internal_templatebase.php:377
    0.1430    3227000  10. content_5479667ef22183_66770006() E:\webroot\breeze\application\libs\smarty\sysplugins\smarty_internal_templatebase.php:182


Notice: Trying to get property of non-object in E:\webroot\breeze\application\libs\smarty\sysplugins\smarty_internal_templatebase.php(151) : eval()'d code on line 109

Call Stack:
    0.0000     121704   1. {main}() E:\webroot\breeze\index.php:0
    0.0010     122936   2. require_once('E:\webroot\breeze\breeze\Breeze.php') E:\webroot\breeze\index.php:4
    0.0050     226624   3. breeze\core\start() E:\webroot\breeze\breeze\Breeze.php:15
    0.0580     728328   4. breeze\core\Application->start() E:\webroot\breeze\breeze\core\Core.php:182
    0.0600     736440   5. breeze\core\Router->dispatcher() E:\webroot\breeze\breeze\core\Application.php:34
    0.0630     785528   6. call_user_func() E:\webroot\breeze\breeze\core\Router.php:60
    0.0630     785528   7. application\controller\Index->index() E:\webroot\breeze\breeze\core\Router.php:60
    0.0770    1431480   8. Smarty_Internal_TemplateBase->display() E:\webroot\breeze\application\controller\Index.php:32
    0.0770    1431624   9. Smarty_Internal_TemplateBase->fetch() E:\webroot\breeze\application\libs\smarty\sysplugins\smarty_internal_templatebase.php:377
    0.1430    3227000  10. content_5479667ef22183_66770006() E:\webroot\breeze\application\libs\smarty\sysplugins\smarty_internal_templatebase.php:182



</body>
</html><?php }} ?>
