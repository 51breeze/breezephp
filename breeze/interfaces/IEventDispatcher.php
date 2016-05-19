<?php

namespace breeze\interfaces;
use breeze\events\Event;

/**
 * 事件调度接口，只有实现了此接口的类都具有事件派发和侦听的功能。
 * Interface IEventDispatcher
 * @package breeze\interfaces
 */
interface IEventDispatcher
{
    /**
     * 清册一个事件侦听器。
     * @param string $type 事件类型
     * @param function $listener 侦听器
     * @param int $priority 优先级，数字大的优先
     * @return mixed
     */
    function addEventListener($type, $listener, $priority=0);

    /**
     * 删除一个侦听器。
     * @param string $type 事件类型
     * @param function $listener 侦听器。如果不传递已注册过的侦听器，则删除指定事件类型的事件
     * @return mixed
     */
    function removeEventListener($type,$listener);

    /**
     * 判断是否有注册过此类型的侦听器。
     * @param string $type 事件类型
     * @return mixed
     */
    function hasEventListener($type);

    /**
     * 派发一个指定事件类型的事件。
     * @param Event $event
     * @return mixed
     */
    function dispatchEvent(Event $event);

}

?>