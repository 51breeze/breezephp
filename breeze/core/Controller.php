<?php

namespace breeze\core;
use breeze\events\Event;
use breeze\interfaces\ISingle;

abstract class Controller extends View implements ISingle
{
    /**
     * @var Application
     */
     protected $app = null;

    /**
     * constructs. 
     */
	final public function __construct()
	{
        parent::__construct();
        Single::register( get_called_class() , $this );
        $this->app = Application::getInstance();
        $this->initialize();
	}

    /**
     * @see \breeze\interfaces\ISingle::getInstance()
     */
    final public static function getInstance()
    {
        return Single::getInstance( get_called_class() );
    }

	/**
	 * 初始化此控制器辅助功能<br/>
	 * 通常可以在子类中覆盖此方法来实现一些辅助功能。
	 * @param Event $event
	 */
	protected function initialize(){}
}

?>