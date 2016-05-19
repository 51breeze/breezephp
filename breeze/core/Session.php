<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-4-21
 * Time: 下午10:18
 */

namespace breeze\core;

use breeze\interfaces\ICache;
use breeze\interfaces\ISingle;

class Session extends EventDispatcher implements ISingle
{
    /**
     * @public
     */
    public $expire = 86500;

    /**
     * @public
     */
    public $domain = null;

    /**
     * @public
     */
    public $path   = null;

    /**
     * @public
     */
    public $name   = null;

    /**
     * @public
     */
    public $prefix = '';

    /**
     * @public
     */
    public $secure = false;

    /**
     * @public
     */
    public $cipher  = null;

    /**
     * @private
     */
    private $drive  = null;

    /**
     * @private
     */
    private $id   = '';

    /**
     * constructs.
     * @param array $options
     * @param IRecord $drive
     */
    public function __construct( array $options = array(), ICache $drive=null )
    {
        Single::register( get_called_class(), $this );

        $this->drive = $drive;
        $options = array_merge($options, Application::getInstance()->config( 'session' ,null,array() ) );
        foreach( $options as $prop => $value )
        {
            if( property_exists($this,$prop) )
            {
                $this->$prop = $value;
            }
        }
        $this->initialize();
    }

    /**
     * @see \breeze\interfaces\ISingle::getInstance()
     * @return Session
     */
    public static function getInstance()
    {
        return Single::getInstance( get_called_class() );
    }

    /**
     * @private
     */
    private function initialize()
    {
        static $initialized=null;
        if( $initialized===null )
        {
            $initialized=true;
            if( $this->drive instanceof ICache )
            {
                session_set_save_handler(
                    array($this,"open"),
                    array($this,"close"),
                    array($this,"read"),
                    array($this,"write"),
                    array($this,"destroy"),
                    array($this,"gc")
                );
            }

            !empty( $this->name ) ? session_name( $this->name ) : $this->name =  session_name();
            if( !empty( $this->path ) )
            {
                session_save_path( $this->path );
            }

            session_start();
            $this->id = session_id();
            Cookie::getInstance()->set( $this->name, $this->id, $this->expire,null,$this->domain,$this->secure );
        }
    }

    /**
     * 设置session值
     * @param $name
     * @param null $value
     * @return $this
     */
    public function set( $name, $value=null )
    {
         $name = strtolower( $name );
         if( $value === null )
         {
             unset( $_SESSION[$name] );
         }else
         {
             $_SESSION[ $name ]=$value;
         }
         return $this;
    }

    /**
     * 获取session值
     * @param $name
     * @return null
     */
    public function get( $name )
    {
        $name = strtolower( $name );
        return isset( $_SESSION[$name] ) ? $_SESSION[$name] : null;
    }

    /**
     * @private
     */
    private function open($path, $name){}

    /**
     * @private
     */
    private function close(){}

    /**
     * @private
     */
    private function read( $id )
    {
        return $this->drive->get($id);
    }

    /**
     * @private
     */
    private function write($id,$data)
    {
        $this->drive->set($id,$data);
    }

    /**
     * @private
     */
    private function destroy($id)
    {
        $this->drive->clean($id);
    }

    /**
     * @private
     */
    private function gc( $maxlifetime )
    {
    }
}
