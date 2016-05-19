<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-4-21
 * Time: 下午10:18
 */

namespace breeze\core;
use breeze\events\Event;
use breeze\interfaces\ISingle;

class Cookie extends EventDispatcher implements ISingle
{
    /**
     * @public
     */
    public $expire = 86400;

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
    public $secure = false;

    /**
     * 加密函数
     * @public
     */
    public $cipher  = array('\breeze\utils\Utils','cipher');

    /**
     * @public
     */
    public $key     = 'cipherkey';

    /**
     * 设置cookie的最大有效时间
     * @public
     */
    public $maxExpire =0;

    /**
     * @private
     * @var array
     */
    private $cookie=array();

    /**
     * constructs.
     * @param array $options
     * @param IRecord $drive
     */
    public function __construct( $options = null )
    {
        Single::register( get_called_class(), $this );
        $options = is_array($options) ? $options : array();
        $options = array_merge($options, Application::getInstance()->config( 'cookie' ,null,array() ) );
        foreach( $options as $prop => $value )
        {
            if( property_exists($this,$prop) )
            {
                $this->$prop = $value;
            }
        }

        if( !is_callable($this->cipher) )$this->cipher=null;
        if( $this->cipher )
        {
            $cookie =  Application::getInstance()->header('Cookie');
            $_COOKIE=array();
            if( !empty($cookie) )
            {
                $cookies =  explode(';', $cookie );
                foreach($cookies as $cookie )
                {
                    $cookie =  call_user_func_array($this->cipher,array($cookie,$this->key,false) );
                    $cookie = unserialize( $cookie );
                    $ctime = time();
                    if( is_array($cookie) ) foreach($cookie as $item)
                    {
                       if( $ctime < $item['expire'] )
                       {
                           $this->maxExpire = max($this->maxExpire, $item['expire'] );
                           $_COOKIE[ $item['name'] ] = $item['value'];
                       }
                    }
                }
            }

            $self = $this;
            Application::getInstance()->addEventListener(Event::SHUTDOWN,function(Event $event)use( $self )
            {
                $cookie  = $self->cookie;
                if( !empty($cookie) )
                {
                    $cipher = $self->cipher;
                    $key    = $self->key;
                    $cookie = call_user_func_array($cipher,array(serialize( $cookie ),$key,true) );
                    header( 'Set-Cookie:'.$cookie.';expires=Sat,'.gmdate("d-M-Y H:i:s", $self->maxExpire ).' GMT');
                }
            });
        }
    }

    /**
     * @see \breeze\interfaces\ISingle::getInstance()
     * @return Cookie
     */
    public static function getInstance()
    {
        return Single::getInstance( get_called_class() );
    }

    /**
     * 设置cookie值
     * @param $name
     * @param null $value
     * @param null $expire
     * @param null $path
     * @param null $domain
     * @param bool $secure
     */
    public function set($name,$value=null, $expire=null, $path=null, $domain=null, $secure=false )
    {
        if( $expire === null )$expire = $this->expire;
        if( $path === null )$path = $this->path;
        if( $domain === null )$domain = $this->domain;
        if( $secure === null )$secure = $this->secure;

        $expire = time()+$expire;
        $name = strtolower($name);
        if( $this->cipher )
        {
            if( !isset( $_COOKIE[ $name ] ) )
            {
                $this->maxExpire =max( $expire, $this->maxExpire );
                $item = array('name'=>$name,'value'=>$value,'expire'=>$expire , 'secure'=>$secure ) ;
                if( !empty($domain) )$item['domain']=$domain;
                if( !empty($secure) )$item['secure']=$secure;
                array_push( $this->cookie, $item);
            }

        }else
        {
            setcookie( $name, $value, $expire , $path, $domain,$secure );
        }
        $_COOKIE[ $name ] = $value;
    }

    /**
     * 获取cookie值
     * @param $name
     * @return null
     */
    public function get( $name )
    {
        $name = strtolower( $name );
        return isset( $_COOKIE[ $name ] ) ? $_COOKIE[ $name ] : null;
    }
}
