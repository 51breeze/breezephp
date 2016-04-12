<?php

namespace breeze\interfaces;

interface ISingleton
{

    /**
     * 当类实例化时自动构造函数
     */
    public function __construct();

    /**
     * 获取指定类的实例对象
     * @param array $param=null
     * @return ISingleton
     */
    public static function getInstance( array $param=array() );

}