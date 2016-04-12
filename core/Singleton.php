<?php

namespace breeze\core;
use breeze\interfaces\ISingleton;


/**
 * 单列模式实例类。
 * 只有实现单列接口的类才能通过此类进行管理。
 * @package breeze\core
 */
abstract class Singleton
{
    /**
     * @private
     */
    private static $instance=null;

    /**
     * 获取指定类名的实例
     * @param string $class 完整的类名（包括命名空间）
     * @return object
     */
    final static public function getInstance( $class ,array $param=array() )
    {
        $class=trim($class,'\\');
        if( is_null(Singleton::$instance) )
            Singleton::$instance=new \stdClass();

        if( !isset( Singleton::$instance->$class ) )
        {
           $ref=new \ReflectionClass($class);
           if( !$ref->implementsInterface('\breeze\interfaces\ISingleton') )
               throw new Error('Must implement ISingleton interface');

           $ref->newInstanceArgs( $param );
           if( !self::isExists( $class ) )
              throw new Error('In the implementation of the interface must use Singleton::register() to register an instance object :'.$class );
        }
        return Singleton::$instance->$class;
    }

    /**
     * 注册一个实例对象
     * @param $class
     * @param ISingleton $instance
     */
    final static public function register($class,ISingleton $instance)
    {
        if( !isset( Singleton::$instance->$class ) )
            Singleton::$instance->$class = $instance;
        else
            throw new Error('please use Singleton::getInstance():'.$class);
    }

    /**
     * 判断指定类名的实例是否存在
     * @param string $class 完整的类名（包括命名空间）
     * @return boolean
     */
    final static public function isExists( $class )
    {
       $class=trim($class,'\\');
       return isset( Singleton::$instance->$class );
    }

}
