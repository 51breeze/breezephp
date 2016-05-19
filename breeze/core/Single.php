<?php

namespace breeze\core;
use breeze\interfaces\ISingle;


/**
 * 单列模式实例类。
 * 只有实现单列接口的类才能通过此类进行管理。
 * @package breeze\core
 */
abstract class Single
{
    /**
     * @private
     */
    private static $instance=array();

    /**
     * 获取指定类名的实例
     * @param string $class 完整的类名（包括命名空间）
     * @return object
     */
    final static public function getInstance( $class , $param=null )
    {
        $class=trim(trim($class),'\\');
        if( class_exists($class) )
        {
            if( !isset( Single::$instance[$class] ) )
            {
                $ref = new \ReflectionClass( $class );
                if( !$ref->implementsInterface('breeze\interfaces\ISingle') )
                    throw new Error( $class.' must implement ISingle interface');

                $ref->newInstance( $param );
                if( !( @Single::$instance[$class] instanceof ISingle) )
                    throw new Error('please in constructs use Single::register('.$class.')');
            }
            return Single::$instance[ $class ];
        }
        throw new Error('Not find class for '.$class);
    }

    /**
     * 注册一个实例对象
     * @param $class
     * @param ISingle $instance
     */
    final static public function register($class,ISingle $instance)
    {
        $class=trim(trim($class),'\\');
        if( !isset( Single::$instance[$class] ) )
            Single::$instance[$class] = $instance;
        else
            throw new Error('please use Single::getInstance('.$class.')');
    }

}
