<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-4-22
 * Time: 下午9:55
 */

namespace breeze\core;
use breeze\interfaces\ICache;
use breeze\utils\Utils;

class File extends EventDispatcher implements ICache
{
    /**
     * @public
     */
    public $path   = null;

    /**
     * @var array
     */
    static private $data=array();

    /**
     * constructs.
     * @param string $path
     */
    public function __construct( $path=null )
    {
        $this->path = $path;
        if( empty($this->path) )
        {
            $this->path = rtrim(APP_PATH,'/').'/cache';
        }
        $this->path = Utils::mkdir( $this->path );
    }

    /**
     * @private
     * @param $key
     * @return string
     */
    private function filename( $key )
    {
        return $this->path.'/'.$key;
    }

    /**
     * @see \breeze\interfaces\ICache::get()
     */
    public function get( $key )
    {
       $filename = $this->filename($key);
       if( !isset( self::$data[ $filename ] ) )
       {
          if( !file_exists($key) )return null;
          $data =  file_get_contents( $filename ,false );
          list($expire,$data)= explode(':',$data,2);
          self::$data[ $key ] = array('d'=>unserialize($data),'e'=>$expire );
       }
       $data = self::$data[ $key ];
       if( time() > $data['e'] && $data['e'] > 0 )
       {
           unset( self::$data[ $key ] );
           @unlink( $filename );
           return null;
       }
       return self::$data[ $key ]['d'];
    }

    /**
     * @see \breeze\interfaces\ICache::set()
     */
    public function set($key, $data ,$expire=7200 )
    {
        if( isset( self::$data[ $key ] ) )
        {
            if( self::$data[ $key ]['d'] === $data && self::$data[ $key ]['e']===$expire ){
                return false;
            }
        }
        self::$data[ $key ]= array('d'=>$data,'e'=>time()+$expire);
        file_put_contents( $this->filename($key), $expire.':'.serialize( self::$data[ $key ]['d'] ) , LOCK_EX );
        return true;
    }

    /**
     * @see \breeze\interfaces\ICache::expire()
     */
    public function expire($key, $expire=7200 )
    {
        if( $expire < 0 )
        {
            $this->clean( $key );

        }else
        {
            if( !$this->get($key) )throw new Error('Not exists key');
            $this->set($key, self::$data[ $key ]['d'] , $expire );
        }
        return true;
    }

    /**
     * @see \breeze\interfaces\ICache::clean()
     */
    function clean()
    {
        $keys = func_get_args();
        if( !empty($keys) )
        {
            foreach( $keys as $key )
            {
                if( isset(self::$data[ $key ]) )
                {
                    unset( self::$data[ $key ] );
                }
                return @unlink( $this->filename($key) );
            }
            return true;
        }
        return false;
    }

    /**
     * @see \breeze\interfaces\ICache::flush()
     */
    function flush()
    {
        $files= Utils::files( $this->path, 'file');
        while( $file= array_pop($files) )
        {
            @unlink( $file );
        }
        self::$data=array();
        return true;
    }
}