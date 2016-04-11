<?php

namespace breeze\core;
use breeze\database\Parameter;
use breeze\interfaces\ISingleton;
use breeze\utils\Utils;
use breeze\core\EventDispatcher;
use breeze\interfaces\ISystem;

abstract class System extends EventDispatcher implements ISystem, ISingleton
{
	/**
	 * @private
	 * 默认的配置项
	 */
	protected $CONFIG_ITEMS=array(

	    //开启url编码
		'URL_ENCODE_ENABLE'=>false,

		//默认控制器类
		'CONTROLLER'=>'INDEX',
			
		//默认控制器的方法
		'METHOD'=>'INDEX',
			
		//当URL_MODE是标准模式时，在url参数中接收控制器的键名
		'CONTROLLER_KEY'=>'C',
			
		//当URL_MODE是标准模式时，在url参数中接收控制器方法的键名
		'METHOD_KEY'=>'M',

        'CHARSET'=>'UTF-8',
	);

    /**
     * Constructs.
     */
    public function __construct()
    {
        parent::__construct();
        Singleton::register(get_called_class(),$this);
    }

    /**
     * @see \breeze\interfaces\ISingleton::getInstance()
     */
    public static function getInstance(array $param=array())
    {
        return Singleton::getInstance(get_called_class(),$param);
    }

   /**
   * 初始化应用程序
   */
   protected function initialize()
   {
       //初始化相关配置
       $this->setting( __CONFIG__ );
   }
   
   /**
    * 获取此对象上的属性
    */
   public function __get( $property )
   {
       return isset( $this->$property ) ? $this->$property : null;
   }
    
   /**
    * 设置此对象上的属性
    */
   public function __set( $property ,$value )
   {
       if( !isset( $this->$property ) )
      	 	  $this->$property=$value;
   }
   
   /**
   * @see \com\interfaces\ISystem::isConfig()
   */
   public function isConfig($key)
   {
   	  return array_key_exists( strtoupper( $key ) , $this->CONFIG_ITEMS );
   }

   /**
   * @see \com\interfaces\ISystem::getConfig()
   */
   public function & getConfig( $key )
   {
   	   $key=strtoupper( $key );
       $val=null;
       isset( $this->CONFIG_ITEMS[ $key ] ) &&  $val =& $this->CONFIG_ITEMS[ $key ];
       return $val;
   }
   
   /**
    * @see \com\interfaces\ISystem::setConfig()
    */
   public function setConfig( $key , $value )
   {
   	   $key=strtoupper( $key );
   	   if( isset( $this->CONFIG_ITEMS[ $key ] ) )
   	   {
   	   	  $this->CONFIG_ITEMS[ $key ] = $value ;
   	   	  return true;
   	   }
   	   return false;	 
   }
   
   /**
    * 获取或者设置配置项
    * @see \breeze\interfaces\ISystem::setConfig()
    */
   public function config( $key, $value=null )
   {
   	   if( $value===null ) 
   	      return $this->getConfig( $key  );
   	   else 
   	      return $this->setConfig( $key , $value);
   }

   /**
    *  获取数据库实例
    *  @return \breeze\database\Database
    */
   public function database($device=null)
   {
       $config=$this->getConfig('database');
       $device=!is_null($device) ? $device : $config['type'];
       $param=null;
       if( empty($device) )
           throw new Error(Lang::info(1008));
       if( !Singleton::isExists('\breeze\database\\'.$device) )
       {
           $param=new Parameter();
           $param->host=$config['host'];
           $param->user=$config['user'];
           $param->password=$config['password'];
           $param->database=$config['database'];
           $param->type=$config['type'];
           $param->port=isset($config['port']) ? $config['port'] : 3306;
       }
       try{
          return Singleton::getInstance('\breeze\database\\'.$device,$param);
       } catch (Error $e)
       {
          throw new Error(Lang::info(1009));
       }
   }

   //==============================================================
   // Private Method
   //==============================================================
   
   /**
    * @private
    */
   private $setted=false;
   
   /**
    *  合并整个程序的配置文件
    *  @param String $path 应用程序的绝对路径
    */
   private function setting( $path )
   {
   	   if( $this->setted )
   	     return;
   	   
   	   $this->setted=true;
   	   $_config_path= $path;
   	   $_config_files=array();

   	   if( is_dir( $_config_path ) )
   	   {
   	     $_config_files=Utils::getFiles( $_config_path,'file' );
   	   }
   	   else if( file_exists( $_config_path ) )
   	   {
   	   	 $_config_files= array( $_config_path );
   	   	 
   	   }else
   	   {
   	   	  $msg=sprintf("加载的配置文件不存在。文件名(%s)" , $_config_path );
   	   	  Utils::message( $msg , Utils::WARNING );
   	   }

   	   if( !empty( $_config_files ) )
   	   {
   	   	   foreach (  $_config_files as $filename )
   	   	   {
                $suffix=substr( $filename,-3 );
   	   	   	    $suffix= stripos( CONFIG_FILE_SUFFIX , $suffix )!==false ? $suffix : null;
   	   	   	    $suffix=strtolower($suffix);

                if( $suffix=="php" )
                {
                	$config=array();
                	
                	require_once( $filename );

                	if( empty( $config ) )
                	{
                        $msg=sprintf("加载的配置文件中无任何配置项，请检查配置文件中的变量名是否为“config”。所在文件(%s)",$filename);
	   		            Utils::message( $msg, Utils::WARNING ); 
                	}

                	$this->CONFIG_ITEMS=array_merge( $this->CONFIG_ITEMS , array_change_key_case( $config ,CASE_UPPER ) );
                		
                }else if( $suffix=="ini" )
                {
                	$this->parseTextConfig( $filename );
                }
   	   	   }
   	   }
   }

    /**
    * 解析一个 json 格式的配置项。不能解析 嵌套的 json
    * @param String $str 配置文件
    */
   private function parseJson( $str )
   {
       $result=null;

       if( preg_match('/\{(.*)\}/s',$str,$json )>0  )
	   {
             if( empty($json[1]) )
               	return $result;

             $result=array();
             $json = trim( $json[1] ,',');
             $json=explode(',', $json);

             foreach ( $json as $key => $value ) 
             {

                preg_match('/[\'|\"]?(\w+)[\'|\"]?\:(.*)?/',$value, $config_item ) ;

                $key=trim($config_item[1]);
                $value=trim($config_item[2]);
                $value=trim($value,"'");
                $value=trim($value,'"');

             	if( strpos( $value , '{' )!==false )
             	{
                    $temp=self::parseJson( $value );
                    !is_null($temp) && $value = $temp ;
             	}

             	$result[ strtoupper($key) ] = $value ;
             }
	   }
	   return $result;
   }
   
   /**
    * 解析以文本方式的配置文件
    * @param String $filename 配置文件
    */
   private function parseTextConfig( $filename )
   {
   	
   	    if( !file_exists( $filename ) )
   	    	return;

	   	$handle=fopen( $filename, "r" );
	   	flock($handle, LOCK_SH );
	   	$contents=fread( $handle, filesize($filename) );
	   	
	   	$patterns = array(
	   			'/\/\*.*?\*\//s',
	   			'/\/\/.*?\r\n?/',
	   			'/\#.*?\r\n?/',
	   			'/[\\r|\\n|\\t]+/s',
	   	);
	   	 
	   	$contents=preg_replace($patterns, '',$contents);
	   	
	   	preg_match_all('/[\s+]?(\w+[\s+]?=[\s+]?.*?);/'   , $contents, $config_items );
	   	
	   	$error_rows="";
	   	$warning_rows=array();
	   	 
	   	if( !empty($config_items[1]) )
	   	{
	   		$config=array();
	   		 
	   		foreach ( $config_items[1] as $item )
	   		{
	   			$config_item=explode("=", $item);
	   			 
	   			if( count($config_item)>2 )
	   			{
	   				$error_rows.=$config_item[0];
	   				continue;
	   			}
	   			 
	   			isset($config[ $config_item[0] ] ) && array_push( $warning_rows,$config_item[0] );
	   			
	   			$json=self::parseJson( $config_item[1] );

	   			!is_null( $json ) && $config_item[1]=$json;
	   	
	   			$config[ $config_item[0] ]=$config_item[1];
	   	
	   			$config_item=null;
	   		}
	   	
	   		$this->CONFIG_ITEMS=array_merge( $this->CONFIG_ITEMS , array_change_key_case( $config ,CASE_UPPER ) );
	   	
	   		$config=null;
	   	}
	   	 
	   	//警告信息
	   	if( count( $warning_rows ) > 0 )
	   	{
	   		$warning_rows=implode(",", $warning_rows );
	   		$warning_rows=sprintf("配置文件中变量名有冲突。所在文件(%s)，冲突的变量名：'%s'",$filename,$warning_rows);
	   		Utils::message( $warning_rows, Utils::WARNING );
	   		$warning_rows=null;
	   	}
	   	 
	   	//找出配置文件中存在问题的行号
	   	if( !empty( $error_rows ) )
	   	{
	   		$line=0;
	   		fseek($handle,0);
	   		while ( !feof($handle) )
	   		{
	   			$line++;
	   			$row=fgets( $handle ,1024 );
	   			 
	   			if( preg_match('/[\s+]?(\w+)[\s+]?=/', $row ,$row)>0 )
	   			{
	   				$row=$row[1];
	   	
	   				if( !empty( $row ) )
	   				{
	   					if( stripos($error_rows,$row) !== false )
	   					{
	   						$msg=sprintf("配置文件(%s)中第%d行有错误。",$filename, $line );
	   						Utils::message( $msg , Utils::ERROR );
	   						break;
	   					}
	   				}
	   			}
	   	
	   		} // end while
	   	
	   		$error_rows=null;
	   	}
	   	 
	   	$contents=null;
	   	flock($handle, LOCK_UN );
	   	fclose($handle);

   }
   
}

?>