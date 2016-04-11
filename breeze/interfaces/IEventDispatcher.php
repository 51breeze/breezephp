<?php

namespace breeze\interfaces;
use breeze\events\Event;

interface IEventDispatcher {

	function addEventListener($type, $listener, $priority=0);
	
	function removeEventListener($type,$listener);
	
	function hasEventListener($type);
	
	function dispatchEvent(Event $event);

}

?>