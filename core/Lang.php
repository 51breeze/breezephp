<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-9-24
 * Time: 下午4:52
 */

namespace breeze\core;
use breeze\interfaces\ISingleton;
use breeze\utils\Utils;

/**
 * Class Lang
 * @package breeze\core
 */
class Lang implements ISingleton
{
    /**
     * @private
     */
    private $current='zh-cn';

    /**
     * @private
     */
    private $default=null;

    /**
     * @private
     */
    private $data=null;

    /**
     * Constructs.
     */
    public function __construct()
    {
        Singleton::register(get_called_class(),$this);
    }

    /**
     * @see \breeze\interfaces\ISingleton::getInstance()
     */
    public static function getInstance( array $param=array() )
    {
        return Singleton::register(get_called_class(),$param);
    }

    /**
     * @private
     */
   private function loadDefault()
   {
       if( is_null( $this->default ) )
       {
           $this->default=Utils::import('ErrorInfo');
           if( !is_array( $this->default ) )
               $this->default=array();
       }
   }

    /**
     * @private
     */
   private function language( $lang )
   {
       $this->loadDefault();
       $filepath=sprintf('%s/%s', rtrim(__LANG__,'/') ,$lang );
       $this->data=Utils::import( $filepath );
       if( empty($this->data) || !is_array($this->data) )
       {
           $this->data=& $this->default;
       }else if( is_array( $this->default ) )
       {
           foreach( $this->default as $key=>$val )
           {
               if( !isset( $this->data[$key] ) )
                 $this->data[$key]=& $val;
           }
       }
       return true;
   }

    /**
     * @private
     */
    private function get( $key='' )
    {
        if( is_null($this->data) )
        {
            $this->loadDefault();
            $this->data= & $this->default;
        }
        return empty($key) ? $this->data : ( isset( $this->data[$key] ) ? $this->data[$key] : '' ) ;
    }

    /**
     * 获取指定代码的描述
     * @param $code
     * @return string
     */
    public static function info( $code )
    {
        $lang=Singleton::getInstance('\breeze\core\Lang');
        $info=$lang->get( $code );
        if( func_num_args() > 1 && !empty($info) )
        {
            $argv=func_get_args();
            array_splice($argv,0,1,$info);
            $info=call_user_func_array('sprintf',$argv);
        }
        return $info;
    }

    /**
     * 设置指定的语言包。
     * @param string $lang 语言文件包名
     * @return boolean
     */
    public static function setLanguage( $name )
    {
        $lang=Singleton::getInstance('\breeze\core\Lang');
        return $lang->language( $name );
    }
}