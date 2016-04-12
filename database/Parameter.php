<?php
namespace breeze\database;

class Parameter
{
    
    /**
    * @public
    * 使用压缩协议
    */
   const  COMPRESS    = MYSQL_CLIENT_COMPRESS;
   
   /**
    * @public
    * 允许函数名后的间隔
    */
   const  IGNORE      = MYSQL_CLIENT_IGNORE_SPACE; 
   
   /**
    * @public
    * 允许关闭连接之前的交互超时非活动时间
    */
   const  INTERACTIVE = MYSQL_CLIENT_INTERACTIVE;
   
   /**
    * @public
    * 使用 SSL 加密传输
    */
   const  SSL         = MYSQL_CLIENT_SSL;
    
   /**
    * @private
    * 主机地址
    */
   public  $host;
   
   /**
    * @private
    * 数据库的用户名
    */
   public  $user;
   
   /**
    * @private
    * 连接数据库的密码
    */
   public  $password;
   
   /**
    * @private
    * 数据库适配器的类型
    */
   public  $type;

   /**
    * @private
    * 数据库的端口
    */
   public  $port=3306;
   
   /**
    * @private
    * 是否总是使用新的连接
    */
   public  $flag=false;
   
   /**
    * @private
    * 连接到的数据库名
    */
   public $database=null;
   
   /**
    * @private
    * 可选的一些参数
    */
   public $options;
   
   /**
    * @private
    * 是否使用长连接
    */
   public $keep=false;
   
   /**
    * @private
    * 数据库使用的字符编码
    */
   public $charset='utf8';
   
   /**
    * @private
    * 数据表的前缀
    */
   public $prefix='';
   
   /**
    * @private
    * 数据表的后缀
    */
   public $suffix='';
   
   /**
    * @private
    * 指定一组常量
    * 是 COMPRESS IGNORE INTERACTIVE 的组合
    */
   public $client=null;
    
   /**
    * 创建一个提供数据库连接的参数
    * @param string $type 数据库配置器的类型
    * @param string $host 数据库地址
    * @param string $user 数据库的用户名
    * @param string $password  连接数据库的密码
    * @param string $port 数据库的端口
    * @param string $keep 是否使用长连接
    * @param boolean $flag=true 是否总是使用新的连接
    * @param int     $client=null 指定一组常量
    * @param array   $option=array()  可选的参数
    */
   public function __construct($type='',$host='',$user='',$password='',$database='',$port='3306',$charset='utf8',$prefix='',$suffix='',
                               $keep=false,$flag=true,$client=null,$options=array())
   {
        $this->type     = $type;
        $this->host     = $host;
        $this->user     = $user;
        $this->password = $password;
        $this->database = $database;
        $this->port     = $port;
        $this->charset  = $charset;
        $this->keep     = $keep;
        $this->flag     = $flag;
        $this->client   = $client;
        $this->options  = $options;
        $this->prefix   = $prefix;
        $this->suffix   = $suffix;
   }
   
   /**
    * 获取属性
    * @param string $property
    * @return mixed
    */
   public function __get( $property )
   {
       $property=strtolower( $property );
       return isset( $this->$property ) ? $this->$property : null;
   }
   
    /**
     * 设置属性
     * @param string $property
     * @param mixed  $value
     */
   public function __set( $property,$value )
   {
       $property=strtolower( $property );
       
       if( isset( $this->$property ) )
            $this->$property=$value;
   }

}

?>