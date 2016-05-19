<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-4-22
 * Time: 下午9:55
 */

namespace breeze\core;
use breeze\interfaces\ICache;

class Redis extends EventDispatcher implements ICache
{
    /**
     * @public
     * @var string
     */
    public $host='127.0.0.1';

    /**
     * @public
     * @var int
     */
    public $port = 6379;

    /**
     * @public
     * @var int
     */
    public $timeout=0;

    /**
     * @private
     * @var null
     */
    private $redis=null;

    /**
     * constructs.
     * @param string $host
     * @param int $port
     * @param int $timeout
     */
    public function __construct($host='127.0.0.1',$port=6379,$timeout=0)
    {
        $this->timeout= $timeout;
        $this->port=$port;
        $this->timeout = $timeout;
        if( !extension_loaded('redis') )
        {
            throw new Error('Not exists redis extension');
        }
    }

    /**
     * @private
     */
    private function ping()
    {
        if( $this->redis===null )
        {
            $this->redis = new \Redis();
            if( !$this->redis->connect($this->host,$this->port,$this->timeout) )
            {
                throw new Error('redis connect failed');
            }
        }
    }

    /**
     * @see \breeze\interfaces\ICache::get()
     */
    public function get( $key )
    {
       $this->ping();
       $this->redis->get($key);
    }

    /**
     * @see \breeze\interfaces\ICache::set()
     */
    public function set($key, $data , $expire=3600 )
    {
        $this->ping();
        $this->redis->setex($key,$expire,$data);
    }

    /**
     * @see \breeze\interfaces\ICache::expire()
     */
    function expire($key, $expire=3600 )
    {
        $this->ping();
        $this->redis->expire($key,$expire);
    }

    /**
     * @see \breeze\interfaces\ICache::clean()
     */
    function clean()
    {
        $this->ping();
        return call_user_func_array( array($this->redis,'del'), func_get_args() );
    }

    /**
     * @see \breeze\interfaces\ICache::flush()
     */
    function flush()
    {
        $this->ping();
        $this->redis->flushAll();
    }
} 