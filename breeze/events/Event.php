<?php

namespace breeze\events;


/**
 * 系统事件类
 * 该事件指示程序如何调度事件。
 */
class Event {
	
	/**
	 * 初始化控制器 
	 * 此类型表示系统已经过了"实例化控制器"后由系统自动分发。
	 */
	const INITIALIZE='initialize';
	
	/**
	 * 已经完成调度
	 * 此类型表示系统已经过了以上步骤 （   实例化控制器 -> 初始化控制器 -> 请求的动作  ） 后由系统自动分发
	 */
	const COMPLETE='complete';

    /**
     * 退出程序时
     */
    const SHUTDOWN='shutdown';

	/**
	 * @public
	 * 事件类型
	 */
	public $type;

	/**
	 * @public
	 * 停止后续事件调度
	 */
	public $stopPropagation=false;
	
	/**
	 * @public
	 * 阻止后续事务操作
	 */
	public $prevented=false;

	/**
	 * @public
	 * 事件发起目标
	 */
	public $target;

	/**
	 * @private
	 * 是否可以停止后续事件调度
	 */
	private $_stoppable;
	
	/**
	 * @private
	 * 是否可以阻止后续事务执行
	 */
	private $_cancelable=true;
	
	/**
	 * 
	 * @param string $type 事件类型
	 * @param object $stoppable 是否可以停止后续事件执行
	 * @param string $cancelable 是否可以阻止后续事务执行。
	 */
    final public function __construct( $type , $stoppable=true, $cancelable=true )
	{
		$this->type=$type;
		$this->_cancelable=$cancelable;
		$this->_stoppable=$stoppable;
	}
	
	/**
	 * 停止后续事件调度（ 如果可以 ）
	 * @return boolean
	 */
	final public function stopPropagation()
	{
	    if( $this->_stoppable === true )
	    {
		   $this->stopPropagation=true;
		   return true;
	    }
	    return false;
	}

	/**
	 * 是否已阻止后续事务执行
	 * @return boolean
	 */
	final public function preventDefault()
	{
	   if( $this->_cancelable === true )
	   {
	       $this->prevented=true;
	       return true; 
	   }
	   return false;
	}

	/**
	 * 获取属性
	 * @param string $proprety
	 * @return mixed
	 */
	public function __get( $proprety )
	{
	    return isset( $this->$proprety ) ? $this->$proprety : null;
	}

	/**
	 *@public
	 */
	public function __toString()
	{
        return get_called_class( $this ).PHP_EOL;
	}
}

?>