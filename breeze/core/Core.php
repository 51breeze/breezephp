<?php

namespace breeze\core;
use breeze\utils\Utils;

/**
 * @private
 * 开启一个新的缓冲池，防止意外输出导致 set header 出错。
 */
ob_start();

/**
 * @private
 */
require_once('\breeze\utils\Utils.php');

//=============================================================================
//     环境参数定义
//=============================================================================

/**
 * @private
 * 程序运行的开始时间
 */
define( "START_TIME" , microtime(true) );

/**
 * @public
 */
define('VERSION', '1.0.0');

/**
 * @public
 * 当前脚本运行的模式
 * 命令行为 true 否则 false
 */
defined('CLI') || define('CLI', strcasecmp( php_sapi_name(), 'cli' )===0 );

/**
 * @public
 * 当前工作的路径
 */
!defined( "BASE_PATH" ) && define( "BASE_PATH" , str_replace('\\','/', getcwd() ) );

/**
 * @public
 * 系统根路径
 */
!defined( "SYSTEM_PATH" ) && define( "SYSTEM_PATH" , str_replace('\\','/', dirname( dirname(__FILE__) ) ) );

/**
 * @public
 * php 配置文件中的变量名
 */
!defined('PHP_CONFIG_NAME') && define( "PHP_CONFIG_NAME" ,'CONFIG' );

/**
 * @public
 * 配置文件的类型 可用的值 php,ini
 */
!defined('CONFIG_FILE_SUFFIX') && define( "CONFIG_FILE_SUFFIX" ,'php,ini' );

/**
 * @public
 * 是否为调试模式
 */
!defined( "DEBUG" ) && define( "DEBUG" , false );

/**
 * @public
 * 项目名
 */
!defined( "APP_NAME" ) && define( "APP_NAME" , 'application' );

/**
 * @public
 * 项目的最基本组织结构
 */
!defined( "APP_PATH" ) && define( "APP_PATH" , Utils::directory( APP_NAME ) );

/**
 * @public
 * 项目的最基本组织结构
 */
!defined( "__CONTROLLER__" ) && define( "__CONTROLLER__" , Utils::directory( 'controller', APP_PATH ) );
!defined( "__MODEL__"      ) && define( "__MODEL__"      , Utils::directory( 'model'     , APP_PATH ) );
!defined( "__CONFIG__"     ) && define( "__CONFIG__"     , Utils::directory( 'config'    , APP_PATH ) );
!defined( "__VIEW__"       ) && define( "__VIEW__"       , Utils::directory( 'view'      , APP_PATH ) );
!defined( "__LANG__"       ) && define( "__LANG__"       , Utils::directory( 'lang'      , APP_PATH ) );
!defined( "__LIBS__"       ) && define( "__LIBS__"       , Utils::directory( 'libs'      , APP_PATH ) );


//=============================================================================
//     辅助函数定义
//=============================================================================

/**
 * @private
 * 注册自动加载函数
 */
spl_autoload_register('\breeze\utils\Utils::import');

/**
 * @private
 * 注册程序中止或者退出时的要执行的函数
 */
register_shutdown_function('\breeze\core\shutdown');

/**
 * @private
 * 在非调试模式下的错误控制。
 */
DEBUG===false && set_exception_handler('\breeze\core\exceptionHandler');

/**
 * 设置程序的加载路径。当程序在加载文件时则会尝试从这些路径中查找文件。
 * @param  path 准备设置的路径，可以是一个数组。
 * @return void
 */
function setIncludePath( $path )
{
    $separator=PATH_SEPARATOR;
    $separator=empty( $separator ) ? ";" : $separator;
    $_path=explode($separator, trim( get_include_path() ) );
    $path=array_merge( $_path ,(array) $path );
    $path=array_unique( $path );
    $GLOBALS['include_path']=array_splice($path,0);
    set_include_path( implode($separator, $path) );
}

/**
 * @public
 * 程序被中止或者退出时回调
 */
function shutdown()
{
    $end=microtime(true);

    /*gc_enable();
    gc_collect_cycles();
    gc_disable();*/
    //echo number_format($end-START_TIME,6);
}

/**
 * @public
 * 捕获异常错误
 */
function exceptionHandler( \Exception $exception )
{
    echo 'exceptionHandler::'.$exception->getMessage();
}

/**
 * 获取系统实例
 * @return \breeze\core\Application
 */
function system()
{
    return Singleton::getInstance('\breeze\core\Application');
}

/**
 * 构建项目并运行程序
 * @return boolean
 */
function start()
{
    if( DEBUG===true )
    {
        //设置自动加载的路径。
        $sys_path=Utils::getFiles( SYSTEM_PATH ,'dir' );
       $app_path=Utils::getFiles( APP_PATH ,'dir' );
       $files=array_merge( (array)$sys_path , $app_path );
       setIncludePath( $files );
    }

    //如果程序没有启动
    if( !system()->initialized() )
    {
        //启动程序
        system()->start();
        return true;
    }
    return false;
}