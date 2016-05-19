<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-4-24
 * Time: 下午5:02
 */

namespace breeze\interfaces;

interface IRender
{
    /**
     * 指一组变量到程序模板
     * @param $name
     * @param $value
     * @return mixed
     */
    public function assign( $name, $value=null );

    /**
     * 显示指定的模板
     * @param $name
     * @param bool $cache
     * @param bool $debug
     * @return mixed
     */
    public function dispaly( $name, $cache=true, $debug=true );

} 