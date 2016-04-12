<?php

namespace breeze\core;

use breeze\events\Event;
use breeze\interfaces\ISingleton;

abstract class Controller extends EventDispatcher implements ISingleton
{

    /**
     * constructs. 
     */
	public function __construct()
	{ 
		parent::__construct();

        Singleton::register(get_called_class(),$this);

		/**
		 * 注册一个初始化事件，这个事件由系统自动分发
		 */
		$this->addEventListener( Event::INITIALIZE , array( $this , 'initialize' ) );
	}

    /**
     * @see \breeze\interfaces\ISingleton::getInstance()
     */
    public static function getInstance(array $param=array())
    {
        return Singleton::register(get_called_class(),$param);
    }

	/**
	 * 初始化此控制器辅助功能<br/>
	 * 通常可以在子类中覆盖此方法来实现一些辅助功能。
	 * @param Event $event
	 */
	protected function initialize( Event $event )
	{
	}

    /**
     * @return \breeze\interfaces\ISystem
     */
    protected function system()
    {
        return Singleton::getInstance('\breeze\core\Application');
    }
	
	/**
	 * @private
	 */
	public function __get( $property )
	{
	    if( strtolower($property)=='app' )
	       return  Singleton::getInstance('\breeze\core\Application');
	    return isset( $this->$property ) ? $this->$property : null ;
	}
	
	/**
	 * @private
	 */
	public function __set( $property ,$value )
	{
	    if( !isset( $this->$property ) )
	        $this->$property=$value;
	}
	
	/**
	 * 如果调用不存在的方法时尝试调用系统接口中的方法
	 * @param $method
	 * @param $args
	 * @see com\interfaces\ISystem
	 */
	public function __call($method, $args)
	{
	    if( method_exists( $this->app, $method ) )
	    {
	    	return call_user_func_array( array($this->app,$method) ,$args );
	    }
	}
	
}

?>