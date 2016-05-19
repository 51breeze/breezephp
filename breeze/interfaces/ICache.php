<?php

namespace breeze\interfaces;

interface ICache
{
    /**
     * 获取内容
     * @param $key
     * @return mixed
     */
    function get( $key );

    /**
     * 设置指定键的生存时间
     * @param $key
     * @param int $expire
     * @return bool
     */
    function expire($key, $expire=3600 );

    /**
     * 设置内容
     * @param string $key
     * @param mixed $data
     * @param int $expire 如果为0为永久
     * @return bool
     */
    function set( $key , $data, $expire=3600 );

    /**
     * 清除指定键的缓存
     * @param $key1,$key2,$key3,...
     * @return bool
     */
    function clean();

    /**
     * 清除所有缓存
     * @return bool
     */
    function flush();

} 