<?php

namespace breeze\core;
use breeze\utils\Utils;
use breeze\events\Event;

abstract class Route extends Input
{

	/**
	 * 调度指定的控制器
	 * @return void
	 */
	public function dispatcher( $controller ,$method )
	{
	   	$path=!is_dir(__CONTROLLER__) ? trim(APP_PATH,'/').'/'.__CONTROLLER__ : __CONTROLLER__;

	    if( !is_dir( $path ) )
            throw new Error( Lang::info(1201) .sprintf(' [%s]',$path) );

        //加载指定的控制器
        if( !Utils::import( $controller, $path ) )
            throw new Error( Lang::info(1202) .sprintf(' [%s%s]',$path,$controller) );

        //获取带有命名空间的控制器名
        $controller=Utils::namespaceByClass( $controller );
        $instance=Single::getInstance($controller);

        if( $instance instanceof Controller  )
        {
            if( !method_exists( $controller , $method ) )
                throw new Error( Lang::info(1203).sprintf(' [%s->%s]',$controller,$method) );

            $prevented=false;

            //初始化此控制器
            if( $instance->hasEventListener( Event::INITIALIZING ) )
                $prevented=!$instance->dispatchEvent( new Event( Event::INITIALIZING ) );

            if( !$prevented )
            {
                call_user_func( array($instance,$method) );

                //调度结束
                if( $instance->hasEventListener( Event::FINISH ) )
                    $instance->dispatchEvent( new Event( Event::FINISH ) );
            }
        }
	}

}

?>