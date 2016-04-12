<?php

namespace breeze\events;

class RouteEvent extends Event
{
    //开始路由
    const BEFORE='before';

    //结束路由
    const AFTER='after';

    //要路由到的控制器
    public $controller='';

    //要路由到的方法
    public $method='';

} 