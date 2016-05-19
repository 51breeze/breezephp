<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-4-22
 * Time: 下午9:55
 */

namespace breeze\core;

class Cache extends Storage
{
    /**
     * 缓存到文件中
     */
    const FILE='File';

    /**
     * 缓存到数据中
     */
    const DATABASE='Database';

    /**
     * 缓存到readis 服务器中
     */
    const READIS='Readis';

    /**
     * 缓存到memcache服务器中
     */
    const MEMCACHE='Memcache';

    /**
     * @public
     */
    public $expire = 86500;

    /**
     * @public
     */
    public $path   = null;

    /**
     * @public
     */
    public $name   = null;


    public function __construct( $options=null )
    {
        parent::__construct( $options );

    }


    public function get()
    {
        return '';
    }

    public function set( $data )
    {

    }

    public function add( $data )
    {

    }

    public function remove()
    {

    }

    public function copy()
    {

    }

} 