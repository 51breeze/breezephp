<?php

namespace breeze\library;

use breeze\interfaces\ICache;
use breeze\interfaces\ISingleton;

class Cache implements ISingleton,ICache
{

    /**
     * @see \breeze\interfaces\ISingleton::getInstance()
     */
    public static function getInstance(array $param=array())
    {
        return Singleton::register(get_called_class(),$param);
    }

    /**
     * Constructs.
     */
    public function __construct()
    {
        Singleton::register(get_called_class(),$this);
    }

    public function get($key)
    {

    }

    public function set($key,$data,$expire=3600)
    {

    }

    public function replace($key,$data,$expire=3600)
    {

    }

    public function del( $key )
    {

    }

    public function clean($key=null)
    {

    }

} 