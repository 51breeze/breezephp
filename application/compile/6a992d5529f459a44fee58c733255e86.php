<?php if( !( $__RENDER__ instanceof breeze\library\Render ) ) die('Access denied!');
$__COMPILE_INFO__=array (
  'md5' => '504c8831f343f6af9cd63a439078dbcb',
  'version' => '1.0.0',
  'tpl' => 'index',
  'compile' => 'E:\\webroot\\breeze\\application\\compile\\6a992d5529f459a44fee58c733255e86.php',
);
if( $__RENDER__->hasEventListener( \breeze\events\RenderEvent::UPDATED )
&& ($__RENDER__->debug===true || $__RENDER__::VERSION!==$__TPL_INFO__['version']) ){
$__EVENT__= new \breeze\events\RenderEvent( \breeze\events\RenderEvent::UPDATED );
$__EVENT__->result=& $__COMPILE_INFO__;
if( !$__RENDER__->dispatchEvent( $__EVENT__ ) )return;}
if( $__RENDER__->debug===true && $__RENDER__->hasEventListener( \breeze\events\RenderEvent::DEBUGING ) ){
$__DEBUG_EVENT__= new \breeze\events\RenderEvent( \breeze\events\RenderEvent::DEBUGING );
$__DEBUG_EVENT__->result=array (
  'var' => 
  array (
    58 => 'items',
    57 => 'test',
    61 => 'hhhh',
    70 => 'head',
  ),
  'fun' => 
  array (
    63 => 'hhhh',
  ),
);
if( !empty($__DEBUG_EVENT__->result) ) $__RENDER__->dispatchEvent( $__DEBUG_EVENT__ );}?>
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


<div controller="index">

    <input bindData="name"  />
    <input bindData="name"  />
    中国人民解放军
</div>
<?php $pppp='9999999'; if( is_array( $test ) ){ foreach( $test as $key => $items){?>
    <li><?php echo $items;?></li>
<?php }}else{ trigger_error('Variables must be an array type: test',E_USER_NOTICE);} echo $hhhh; if(function_exists('hhhh')) hhhh(); else trigger_error('Undefined function: hhhh',E_USER_ERROR); ?>

{$name}
{$name}
{$name}
{$name}

<?php echo $__RENDER__->fetch('head',false,false); echo "===++++++++"; ?>
dsfdsf
</body>
</html>