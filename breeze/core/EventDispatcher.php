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
     * 清册一个事件侦听器。
     * @param string $type 事件类型
     * @param function $listener 侦听器
     * @param int $priority 优先级，数字大的优先
     * @return EventDispatcher
     */
	final public function addEventListener($type, $listener, $priority=0)
	{
		if( !is_callable( $listener ) )
            throw new Error('Invalid listener');

		if( !isset( $this->_listener[ $type ] ) )
           $this->_listener[ $type ]=array();

        array_push( $this->_listener[ $type ], new Listeners($type, $listener, $priority ) );
        usort( $this->_listener[ $type ], function(Listeners $a,Listeners $b){
            return $b->priority-$a->priority;
        });
		return $this;
	}

    /**
     * 删除一个侦听器。
     * @param string $type 事件类型
     * @param function $listener 侦听器。如果不传递已注册过的侦听器，则删除指定事件类型的事件
     * @return bool
     */
    final public function removeEventListener($type,$listener=null)
	{
        if( isset($this->_listener[ $type ]) )
        {
            if( $listener=== null )
            {
                array_splice( $this->_listener[ $type ], 0, count($this->_listener[ $type ]) );

            }else
            {
                if( !is_callable($listener) )
                    throw new Error('invalid listener');
                foreach ( $this->_listener[ $type ] as $index=>$item )
                {
                   if( (is_array( $item->listener ) && is_array( $listener ) && $item->listener[0] === $listener[0] && $item->listener[1] === $listener[1] )
                       || $item->listener === $listener )
                   {
                       array_splice($this->_listener[ $type ],$index,1);
                       return true;
                   }
                }
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
		return ( isset( $this->_listener[ $type ] ) && !empty( $this->_listener[ $type ] ) );
	}
	
	/**
	 * 调度事件
	 * @param Event $event
	 * @throws \Exception
	 */
	final public function dispatchEvent( Event $event )
    {
        if( $this->hasEventListener( $event->type ) )
        {
            $index=count( $this->_listener[ $event->type ] );
            $event->target=$this;
            while ( $index > 0 )
            {
                --$index;
                $item=$this->_listener[ $event->type ][ $index ];
                if( $event->stopPropagation )
                    return true;
                call_user_func( $item->listener , $event );
            }
        }
        return $event->prevented === false;
	}
}

/**
 * 侦听器列表
 * @private
 */
final class Listeners
{
    /**
     * 侦听器
     * @var
     */
    private $_listener;

    /**
     * 优先级
     * @var int
     */
    private $_priority;

    /**
     * 类型
     * @var
     */
    private $_type;


    /**
     * @param $type
     * @param $listener
     * @param int $priority
     */
    public function __construct($type, $listener, $priority=0 )
	{
		$this->_listener= $listener;
	    $this->_priority=$priority;
	    $this->_type=$type;
	}

    /**
     * 访问器
     * @param $name
     * @return null
     */
    public function __get( $name )
	{
		$property="_".$name;
		return isset( $this->$property ) ? $this->$property : null;
	}
}

?>