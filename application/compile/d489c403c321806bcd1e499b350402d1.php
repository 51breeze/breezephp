<?php if( !( $__RENDER__ instanceof breeze\library\Render ) ) die('Access denied!');
$__COMPILE_INFO__=array (
  'md5' => '92b54964ba2dbaced904ef0d3be7ff39',
  'version' => '1.0.0',
  'tpl' => 'frameset',
  'compile' => 'E:\\webroot\\breeze\\application\\compile\\d489c403c321806bcd1e499b350402d1.php',
);
if( $__RENDER__->hasEventListener( \breeze\events\RenderEvent::UPDATED )
&& ($__RENDER__->debug===true || $__RENDER__::VERSION!==$__TPL_INFO__['version']) ){
$__EVENT__= new \breeze\events\RenderEvent( \breeze\events\RenderEvent::UPDATED );
$__EVENT__->result=& $__COMPILE_INFO__;
if( !$__RENDER__->dispatchEvent( $__EVENT__ ) )return;}
?>
<!DOCTYPE html>
<html>
<head>
    <title>frameset</title>


    <script src="application/public/jquery-1.11.2.js" type="text/javascript" ></script>
    <!--  <script src="application/public/jquery-1.6.4.min.js" type="text/javascript" ></script>-->
    <!-- <script id="sciptjs" src="application/public/jquery-1.11.2.js" type="text/javascript"></script>-->
    <script src="application/view/sizzle.js" type="text/javascript" onabort=""></script>
    <script src="application/view/BreezeEvent.js" type="text/javascript" ></script>
    <script src="application/view/EventDispatcher.js" type="text/javascript" ></script>
    <script src="application/view/HttpRequest.js" type="text/javascript" ></script>
    <script src="application/view/Breeze.js" type="text/javascript" ></script>
    <!-- <script src="application/view/Layout.js" type="text/javascript" ></script>
     <script src="application/view/TouchEvent.js" type="text/javascript" ></script>-->


    <script>


        Breeze.ready(function(){


            Breeze('frameset').addEventListener( ElementEvent.ADDED ,function(event){

                event.stopPropagation();
                alert( event.target )

            })

            $('frameset').each(function(){


                alert( this )

            })


          // Breeze('frameset').addChildAt('<frame />',2);

        })


    </script>


</head>

<frameset rows="50%,50%">
    <frame id="frame1" src="http://localhost/test.html"/>
    <frameset cols="25%,75%" id="ppp">
        <frame  id="frame2"  src="http://localhost/test.html">
        <frame  id="frame4"   src="http://localhost/test.html">
    </frameset>
</frameset>

</html>