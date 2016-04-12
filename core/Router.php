<?php

namespace breeze\core;
use breeze\events\RouteEvent;
use breeze\utils\Utils;
use breeze\events\Event;

abstract class Router extends Charset
{

    /**
     * @see \breeze\core\System::initialize()
     */
    protected function initialize()
    {
        parent::initialize();

    }

	/**
	 * 调度指定的控制器
	 * @return void
	 */
	public function dispatcher( $controller ,$method )
	{
       $path= __CONTROLLER__ ;

       if( $this->hasEventListener( RouteEvent::BEFORE ) )
       {
           $event=new RouteEvent( RouteEvent::BEFORE );
           $event->controller= & $controller;
           $event->method= & $method;
           if( !$this->dispatchEvent( $event ) )
               return;
       }

	    if( !is_dir( $path ) )
            throw new Error( Lang::info(1201) .sprintf(' [%s]',$path) );

        //加载指定的控制器
        if( !Utils::import( $controller, $path ) )
            throw new Error( Lang::info(1202) .sprintf(' [%s%s]',$path,$controller) );

        //获取控制器实例
        $instance=Singleton::getInstance( Utils::namespaceByClass( $controller ) );

        if( $instance instanceof Controller  )
        {
            if( !method_exists( $instance , $method ) )
                throw new Error( Lang::info(1203).sprintf(' [%s->%s]',$controller,$method) );

            $prevented=false;

            //初始化此控制器
            if( $instance->hasEventListener( Event::INITIALIZE ) )
                $prevented=!$instance->dispatchEvent( new Event( Event::INITIALIZE ) );

            if( !$prevented )
            {
                call_user_func( array($instance,$method) );

                //调度完成
                if( $instance->hasEventListener( Event::COMPLETE ) )
                    $instance->dispatchEvent( new Event( Event::COMPLETE ) );
            }
        }

        if( $this->hasEventListener( RouteEvent::AFTER ) )
        {
            $event=new RouteEvent( RouteEvent::AFTER );
            $event->controller= & $controller;
            $event->method= & $method;
            $this->dispatchEvent( $event );
        }
	}

    public function addRoute($name,$value)
    {

    }

}

?>