<?php if( !( $__RENDER__ instanceof breeze\library\Render ) ) die('Access denied!');
$__COMPILE_INFO__=array (
  'md5' => '625e3e1a8daf1d6c03125e3abd650497',
  'version' => '1.0.0',
  'tpl' => 'breezejs',
  'compile' => 'E:\\webroot\\breezephp\\application\\compile\\3151078e3fabb9c6424e18a1c9718340.php',
);
if( $__RENDER__->hasEventListener( \breeze\events\RenderEvent::UPDATED )
&& ($__RENDER__->debug===true || $__RENDER__::VERSION!==$__TPL_INFO__['version']) ){
$__EVENT__= new \breeze\events\RenderEvent( \breeze\events\RenderEvent::UPDATED );
$__EVENT__->result=& $__COMPILE_INFO__;
if( !$__RENDER__->dispatchEvent( $__EVENT__ ) )return;}
?>
<!DOCTYPE html >
<html lang="zh-cn">
<head>

    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="cache-control" content="no-cache">
    <meta http-equiv="expires" content="0">

    <meta charset="utf-8">
    <!--IE=edge-->
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE6">
    <title>breeze js</title>

   <script src="application/public/jquery-1.11.2.js" type="text/javascript" ></script>
  <!--  <script src="application/public/jquery-1.6.4.min.js" type="text/javascript" ></script>-->
  <!-- <script id="sciptjs" src="application/public/jquery-1.11.2.js" type="text/javascript"></script>-->
   <script src="application/view/sizzle.js" type="text/javascript" onabort=""></script>
   <script src="application/view/BreezeEvent.js" type="text/javascript" ></script>
   <script src="application/view/EventDispatcher.js" type="text/javascript" ></script>

    <script src="application/view/Dictionary.js" type="text/javascript" ></script>
    <script src="application/view/Bindable.js" type="text/javascript" ></script>
    <script src="application/view/Breeze.js" type="text/javascript" ></script>


    <script src="application/view/DataRender.js" type="text/javascript" ></script>
    <script src="application/view/DataGrid.js" type="text/javascript" ></script>

   <script src="application/view/HttpRequest.js" type="text/javascript" ></script>

    <script src="application/view/Timeline.js" type="text/javascript" ></script>
    <script src="application/view/Tween.js" type="text/javascript" ></script>

    <script src="application/view/Ticker.js" type="text/javascript" ></script>

   <!-- <script src="application/view/Layout.js" type="text/javascript" ></script>
    <script src="application/view/TouchEvent.js" type="text/javascript" ></script>-->

  <!--  <script src="/application/public/TweenJS/src/createjs/utils/Ticker.js" type="text/javascript" ></script>
    <script src="/application/public/TweenJS/src/createjs/events/Event.js" type="text/javascript" ></script>
    <script src="/application/public/TweenJS/src/createjs/events/EventDispatcher.js" type="text/javascript" ></script>
    <script src="/application/public/TweenJS/src/createjs/utils/extend.js" type="text/javascript" ></script>
    <script src="/application/public/TweenJS/src/createjs/utils/promote.js" type="text/javascript" ></script>
    <script src="/application/public/TweenJS/src/tweenjs/Ease.js" type="text/javascript" ></script>-->

    <script src="/application/public/TweenJS/lib/tweenjs-0.6.0.combined.js" type="text/javascript" ></script>




    <style>



        body{
            margin: 0px;
            padding: 5px;
            border: 0px;
            background: #cccccc;
        }

         .dd{
             background: #ff0000;
         }

        .ii{
            font-size: 22px;
        }

    </style>


    <script>


      Breeze.ready(function(){

            var target = Breeze('#testdiv')
            var dataGrid =  new DataGrid( target )

           dataGrid.columns({'name':'姓名','phone':'手机','edit':'编辑'})

           dataGrid.remove('edit',function(index, dataRender, event){

               dataRender.removeItem( index )

           }).edit('edit')
                   .component('name',{template:'<textarea>{name}</textarea>',bindable:true})
                   .component('phone',{template:'<textarea>{phone}</textarea>',bindable:true})

           dataGrid.dataProfile([{'name':'yejun','phone':'15302662136'}])

          //,{'name':'yejun33333','phone':'1530264564536'}

       })


    </script>



<style id="uuu">


    #testdiv
    {
    width:500px;
    height:auto;
    transition:width 1s;
    -moz-transition:width 1s; /* Firefox 4 */
    -webkit-transition:width 1s; /* Safari and Chrome */
    -o-transition:width 1s; /* Opera */
    }


    @-webkit-keyframes fadeIn
    {
        0% {
            opacity: 1; /*初始状态 透明度为0*/
        }

        50% {
            opacity: 0.1; /*初始状态 透明度为0*/
        }

        51% {
            opacity: 0.1; /*初始状态 透明度为0*/
        }

        100% {
            opacity: 1;
        }
    }


    .opacitys
    {
        -webkit-animation-name: fadeIn; /*动画名称*/
        -webkit-animation-duration: 2s; /*动画持续时间*/
        -webkit-animation-iteration-count: 1; /*动画次数*/
        -webkit-animation-delay: 0s; /*延迟时间*/
    }


</style>


    <link id="link" href="http://localhost/application/public/dist/css/bootstrap.min.css" rel="stylesheet" >
</head>
<body>


<!--
<div style="background: #61807d;" >

<layout left="50" top="50" right="50" bottom="50"  horizontal="center" vertical="middle" gap="50" ></layout>

<div style="background: #803a4e; width: 100px; height: 100px; margin-left:10px;" module="app layout 3">
</div>

<div style="background: #164f80; width: 100px; height: 150px; margin-left:20px; " module="app layout 3">
</div>

</div>
-->

<!--style="width: 300px; height: 200px; background: #94cc3c; overflow: auto;"-->
<div id="testdiv">
    dsf
    <div>
        <div>div-1</div>
    </div>
    <h1 class="uuu">h1-1</h1>
    sdfsd
</div>

<form>
<input type='hidden' name='<?php echo $CSRF_TOKEN_NAME; ?>' value='<?php echo $CSRF_VALUE; ?>'  />
   ssss: <input value="444"/>
</form>

<form>
<input type='hidden' name='<?php echo $CSRF_TOKEN_NAME; ?>' value='<?php echo $CSRF_VALUE; ?>'  />
    <div class="" id="yyy">1</div>
    <div>2<span>6666</span></div>
    <div>30000</div>
</form>

<!--<img src="http://img0.bdstatic.com/img/image/imglogo-r.png">-->



<iframe src="http://localhost/test.html" width="500" height="300">
</iframe>


<table border="1">
    <thead>
    <tr><td>columns</td><td>columns</td><td>columns</td></tr>
    </thead>
    <tr><td>values</td><td>values</td><td>values</td></tr>
    <tfoot>
    <tr><td>values</td><td>values</td><td>values</td></tr>
    </tfoot>
</table>



<!--<link id="link" href="http://localhost/application/public/dist/css/bootstrap.min.css" rel="stylesheet" >-->
<!--<img src="./bootstrap.png"  onerror="alert('img error')" />-->

</body>
</html>