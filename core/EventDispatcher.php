<?php

namespace breeze\core;

use breeze\interfaces\IEventDispatcher;
use breeze\events\Event;

abstract class EventDispatcher implements IEventDispatcher
{
	
	/**
	 * @private
	 */
	private $_listener=array();

	/**
	 * Constructs.
	 */
	public function __construct(){}

	/**
	 * 添加一个侦听器
	 * @param String $type 侦听器的类型
	 * @param Mixed $listener 侦听器。如果是一个数组则表示为一个对象成员。其格式为 Array( 'funName'=>& method );
	 * @param number $priority 优先级别
	 * @return boolean 如果添加成功为 true
	 */
	final public function addEventListener($type, $listener, $priority=0)
	{
		if( !is_callable( $listener ) )
            throw new \Exception('unknown listener');

		if( !isset( $this->_listener[ $type ] ) )
          $this->_listener[ $type ]=array();
		$startIndex=self::getIndex( $this->_listener[ $type ] , $priority );
		array_splice( $this->_listener[ $type ] , $startIndex, 0, array( new Listeners( $listener, $priority ) ) );
		return true;
	}
	
	/**
	 * @param array $listener
	 * @param number $priority
	 * @return number
	 */
	private static function getIndex( & $listener, $priority )
	{
		$index=$num=count( $listener );
		
		while ( $num > 0   )
		{
			--$num;
			if( $listener[ $num ]->priority > $priority )
				$index=min($num,$index);
		}
		return $index;
	}
	
	/**
	 * @param  String $type
	 * @param  Mixed $listener 要删除的侦听器。如果是一个数组则表示为一个对象成员。其格式为 Array( 'funName'=>& method );
	 * @return boolean 如果删除成功返回 true;
	 */
	final public function removeEventListener($type,$listener)
	{
		$fun='';
		$method=null;
		
		if( is_array( $listener ) )
		{
		   list($fun,$method)=$listener;
		    
		}else if( is_string($listener) )
		{
			$fun=$listener;
			
		}else
		{
			return false;
		}

		foreach ( $this->_listener[ $type ] as $index=>$items )
		{
		   if( $items->listener===$fun && $items->method===$method )
		   {
		      array_splice($this->_listener[ $type ],$index,1);
		  	  return true;
		   }
		}
		
		return false;
	}
	
	/**
	 * 检查是否注册过了特定的事件
	 * @param String $type
	 */
	final public function hasEventListener( $type )
    {
		return isset( $this->_listener[ $type ] );
	}
	
	/**
	 * 调度事件
	 * @param Event $event
	 * @throws \Exception
	 */
	final public function dispatchEvent( Event $event ){
		
		if( !($event instanceof Event) )
			throw new \Exception('BreezeEvent type error, must be the event object');

		$index=count( @$this->_listener[ $event->type ] );
		$event->target=$this;

		while ( $index > 0 )
		{
			--$index;
			$items=$this->_listener[ $event->type ][ $index ];
			if( !($items instanceof Listeners) )
				continue;
			if( $event->stopPropagation )
				return true;
			call_user_func( $items->listener , $event );
		}
        return $event->prevented === false;
	}
	
}

/**
 * 事件存储器
 * @private
 */
final class Listeners {
   
	private $_listener;
	private $_priority;
	
	public function __construct( $listener, $priority=0 )
	{
		$this->_listener= $listener;
	    $this->_priority=$priority;
	}

	public function __get( $name )
	{
		$property="_".$name;
		return isset( $this->$property ) ? $this->$property : null;
	}
}

?>