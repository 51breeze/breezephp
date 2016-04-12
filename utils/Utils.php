<?php

namespace breeze\utils;

use breeze\core\Application;
use breeze\core\Error;
use breeze\core\Lang;
use breeze\core\Singleton;

class Utils
{
    /**
     * @public
     * 致命的错误消息。 
     */
    const FATAL=0;
	
	/**
	 * @public
	 * 错误消息。  此信息会写在 log 文件里面并且退出程序。
	 */
	const ERROR=1;
    
	/**
	 * @public
	 * 警告消息。此信息会写在 log 文件里面，如果是 debug 模式下，此消息会输出在页面上，程序不会退出。
	 */
	const WARNING=2;
	
	/**
	 * @public
	 * 提示消息。此信息不会写在 log 文件里，如果是 debug 模式下，此消息会输出在页面上，程序不会退出。
	 */
	const NOTICE=3;
	
	/**
	 * @public
	 * 成功消息。表示某个操作成功的状态，此消息会输出在页面上。
	 */
	const SUCCESS=4;
	
	/**
	 * @public
	 * 调式消息。
	 */
	const DEBUG=5;

    /**
     * 数组中的键名为数字索引类型
     */
    const KEY_NUMBER=1;

    /**
     * 数组中的键名为字符串索引类型
     */
    const KEY_STRING=2;

    /**
     * 数组中的键名为混合索引类型
     */
    const KEY_MIXED=3;
	
	/**
	 * 输出消息
	 * @param  msg String 消息内容
	 * @param  level String  消息的输出级别
	 * @param  code int 状态码
	 * @param  path String 日志路径（如果需要打印）
	 * @param  log_name String 日志文件名
	 */
	public static function message($msg, $level=Utils::WARNING, $code=0, $log_path=null, $log_name=null )
	{
		 
		if( is_array( $msg ) || is_object( $msg ) )
		{
			ob_start();
			var_export($msg);
			$msg=ob_get_clean();
		}
		 
		$msg="[level:{$level} time:".date("Y-m-d H:i:s")."]\r\n".$msg."\r\n" ;
		 
		if( !is_null($log_path) )
		{
			if( !is_dir($log_path) )
				$log_path=self::directory( $log_path );
			
			if( is_null( $log_name ) )
				$log_name=date('Ymd')."log.txt";

			$filename=rtrim($log_path,"/").$log_name;
	 	 
			$fo=fopen( $filename , "a" );
			flock($fo,  LOCK_EX );
			fwrite($fo, $msg );
			flock($fo, LOCK_UN );
			fclose( $fo );
			 
		}
		 
		if( DEBUG===true || $level===Utils::ERROR )
		{
			echo $msg;
			exit;
		}

	}

	/**
	 * 从数组中获取值
	 * 此方法是对键名不区分大小写的
	 * @param array $array 数组对象
	 * @param mixed $key 数组的键名
     * @param boolean $recursion 是否递归查找
	 * @return mixed
	 */
	public static function fetchArray( & $array, $key ,$recursion=false )
	{
	    if( is_scalar($key) )
	    {
	       if( is_numeric( $key ) && array_key_exists($key, $array) )
	       {
	          return $array[ $key ];

	       }else
	       {
	       	   foreach ($array as $k => $val )
    	       {
    	       	  if( strcasecmp( $k,$key )===0 )
    	       	  {
    	       	  	 return $val;
    	       	  }else if( $recursion===true && is_array($val) )
                  {
                     if( $val=self::fetchArray( $val, $key ,$recursion ) )
                         return $val;
                  }
    	       }
	       }
	   }
	   return null;
	}

    /**
     *  获取目录中的文件
     *  @param  $path 要获取目录的路径
     *  @param  $type 要获取的文件类型，默认 全部的类型（all）,可用的类型 “all,dir,file”
     *  @return array $path 路径下的所有目录
     */
    public static function getFiles( $path ,$type='all')
    {
        $type=strtolower( $type );
        $result=array();
        $item=array();

        if( !is_dir($path) )
        {
            if( is_file($path) )
                $result[]=$path;
            return $result;
        }

        if( $type=='all' )
        {
            !isset( $result[ $path ] ) && $result[ $path ]=array();
            $item = & $result[ $path ];
        }else
        {
            $item= & $result;
        }

        $dir= scandir( $path );
        foreach ($dir as $k=>$v)
        {
            if( strpos($v,".")===0 )
                continue;
            $filename=$path."/".$v;
            if( is_dir($filename) )
            {
                $type=='dir' &&  array_push($item, $filename);
                $item =array_merge( $item,Utils::getFiles( $filename ,$type ) );
            }else if( ($type=='all' or $type=='file') && file_exists( $filename ) )
            {
                array_push( $item, $filename );
            }
        }
        return $result;
    }


    /**
     *  创建指定路径下的目录，如果目录已经存在则会格式化此路径。
     *  @param  String path 创建目录的路径
     *  @param  String dir 指定所在的目录
     *  @param  int mode=0777 创建目录的权限,只在 linux 下有效。
     *  @return String path 返回已创建的路径
     */
    public static function directory( $path,$dir='', $mode=0755 )
    {
        if( !is_string($path) )
        {
           trigger_error( Lang::info(1007),E_USER_WARNING );
           return $path;
        }
        $path=str_replace('\\', '/', $path );
        !empty($dir) && $path=ltrim($dir,'/').'/'.$path;

        if( !is_dir( $path ) )
        {
            $_path=explode('/',trim( $path ,'/'));
            $path='';
            foreach( $_path as $newPath )
            {
                $path.=$newPath."/";
                !is_dir( $path ) && mkdir( $path,$mode );
            }
        }
        return str_replace(array('\\','/'), DIRECTORY_SEPARATOR, realpath( $path ) );
    }

    /**
     * 加载指定的文件
     * @param  file String 要加载的文件名
     * @param  dir String  指定加载的路径 ，默认 ''
     * @param  suffix String 加载文件的后缀 , 默认 '.php'
     * @return 如果加载成功返回 true 否则返回false.
     */
    public static  function import($file,$dir='',$suffix='.php')
    {
        static $_file_list=array();
        $class=$file;
        if( strpos($file,$suffix)===false )
        {
            if( !empty($dir) )
            {
                $dir=str_replace(".", "/", $dir );
                $dir=preg_replace( "/\\+/s",'/',trim( $dir,"/" ) );
                $file=rtrim($dir,'/')."/".$file;
            }else if( strpos($file, '.')!==false )
            {
                $file=str_replace(".", "/", trim($file,'.') );
            }
            $file=$file.$suffix;
        }

        $filename=realpath( $file );
        if( $filename===false && isset( $GLOBALS['include_path'] ) )
        {
            $num=count( $GLOBALS['include_path'] );
            while( $filename===false && $num > 0 )
            {
                --$num;
                $item=$GLOBALS['include_path'][ $num ];
                if( $item!='.' && !empty($item) )
                {
                    $filename=realpath( rtrim( $item ,'/').'/'.$file );
                }
            }
        }

        if( $filename!==false )
        {
            //echo $filename,"\n";
            if( !isset( $_file_list[ $filename ] ) )
            {
                $data= require( $filename );
                $_file_list[ $filename ]= & $data;
            }
            return $_file_list[ $filename ];

        }else
        {
            if( class_exists( self::namespaceByClass( $class ),false) )
            {
                $_file_list[ $filename ]=true;
                return true;
            }
            throw new Error( sprintf('%s:[%s]',Lang::info(1006),$file) );
        }
        return false;
    }

	/**
	 * 判断php版本是否符合指定条件的版本
	 * @param String key  配置项中的键名
	 * @param String value 要设置的值
	 * @param boolean lower 是否将值转为小写后返回
	 */
	public static  function isVersion( $version , $condition='>=')
	{
		return version_compare( PHP_VERSION ,$version, $condition );
	}
	
	/**
	 * 根据指定的状态码返回对应的英文名
	 * @param int $code
	 * @param string
	 */
	public static function headerInfoByCode( $code )
	{
	    static $map = array(
            200	=> 'OK',
            201	=> 'Created',
            202	=> 'Accepted',
            203	=> 'Non-Authoritative Information',
            204	=> 'No Content',
            205	=> 'Reset Content',
            206	=> 'Partial Content',
    
            300	=> 'Multiple Choices',
            301	=> 'Moved Permanently',
            302	=> 'Found',
            304	=> 'Not Modified',
            305	=> 'Use Proxy',
            307	=> 'Temporary Redirect',
    
            400	=> 'Bad Request',
            401	=> 'Unauthorized',
            403	=> 'Forbidden',
            404	=> 'Not Found',
            405	=> 'Method Not Allowed',
            406	=> 'Not Acceptable',
            407	=> 'Proxy Authentication Required',
            408	=> 'Request Timeout',
            409	=> 'Conflict',
            410	=> 'Gone',
            411	=> 'Length Required',
            412	=> 'Precondition Failed',
            413	=> 'Request Entity Too Large',
            414	=> 'Request-URI Too Long',
            415	=> 'Unsupported Media Type',
            416	=> 'Requested Range Not Satisfiable',
            417	=> 'Expectation Failed',
    
            500	=> 'Internal Server Error',
            501	=> 'Not Implemented',
            502	=> 'Bad Gateway',
            503	=> 'Service Unavailable',
            504	=> 'Gateway Timeout',
            505	=> 'HTTP Version Not Supported'
	    );
	    
	   return ( is_numeric($code) && isset( $map[ $code ] ) ) ? $map[ $code ] : null;
		
	}
	
	/**
	 * 设置响应头状态
	 * @param int $code 服务器响应的代码
	 * @return boolean
	 */
	public static function setHeaderStatus( $code = 200 )
	{
	    $text=self::headerInfoByCode( $code );

		if ( !empty( $text ) )
		{
    		$server_protocol = isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : false;
    
    		if ( substr( php_sapi_name(), 0, 3 ) == 'cgi' )
    		{
    			header("Status: {$code} {$text}", TRUE);
    		}
    		elseif ( $server_protocol == 'HTTP/1.1' OR  $server_protocol == 'HTTP/1.0')
    		{
    			header( $server_protocol." {$code} {$text}", TRUE, $code );
    		}
    		else
    		{
    			header("HTTP/1.1 {$code} {$text}", TRUE, $code);
    		}
    		
    		return true;
		}
		
		return false;
	}
	
	/**
	 * 获取设置配置项中的值
	 * @param String key  配置项中的键名
	 * @param String value 要设置的值
	 * @param boolean lower 是否将值转为小写后返回
	 */
	public static function config( $key, $value=null, $lower=false )
	{
       return Singleton::getInstance('\breeze\core\Application')->config($key,$value,$lower);
	}
	
	/**
	 * 判断数组键名的类型
	 * @param array $data
	 * @param string $type=ARRAY_KEY_NUMBER  ARRAY_KEY_NUMBER|ARRAY_KEY_STRING|ARRAY_KEY_MIXED
	 * @return boolean
	 */
	public static function isArrayIndex( & $data=array(), $type=Utils::KEY_NUMBER )
	{
        if( is_array($data) )
        {
            $data=array_slice( $data,0 );
            $count=count( $data );
            $in=array_intersect_key($data,range(0,$count-1));
            if( $type===Utils::KEY_NUMBER && count($in)==$count )
                return true;
            else if( $type===Utils::KEY_STRING && empty($in) )
                return true;
            else if( $type==Utils::KEY_MIXED )
                return true;
        }
	    return false;
	}
	
	/**
	 * 把一个数组转换为指定索引类型的键名,数组的键名将会重置，只对一维数组进行转换。<br/>
	 * 如果转换成"字符串索引"类型，且数组中存在相同键名的值则后面的履盖前面的值。数字索引的元素则不会返回。
     * 如果转换成指定"数字索引"类型，如果数组中有字符串索引的元素则不会返回。
	 * @param array arr=array()
	 * @param string type=KEY_NUMBER | KEY_STRING  索引类型
	 * @return array
	 */
	public static function toArray( & $data=array(), $type=Utils::KEY_NUMBER )
	{
        $data=(array) $data;
	    if( empty($data) )
	      return $data;
        $data=array_splice( $data,0 );
	    $count=count($data);
	    $temp=range(0,$count-1);

	    //交集的都属于数字索引
	    $in=array_intersect_key($data,$temp);

	    //差集的都属于字符串索引
	    $temp=array_diff_key($data,$temp);
	    
	    if( $type===Utils::KEY_NUMBER && count($in)!== $count )
	    {
	       $in[]=$temp;

	    }else if( $type==Utils::KEY_STRING )
	    {
    	   foreach ( $in as $item )
    	     $temp=array_merge($temp,(array)$item);
    	   $in=$temp;
	    }
	    return $in;
	}
	
	 /**
     * 获取有效的控制器名。如果传递的控制器名不存在则会在命名空间中去查找。
     * @param $name 类名
     * @return string
     */
    public static  function namespaceByClass( $name )
    {
        if( !class_exists( $name,false ) )
        {
            //在命名空间中找此控制器
            $declared_classes=get_declared_classes();
            $num=count( $declared_classes );
            while ( $num > 0  )
            {
                --$num;
                if( stripos( $declared_classes[ $num ] , $name )!==false )
                {
                    $name=$declared_classes[ $num ];
                    break;
                }
            }
        }
        return $name;
    }

}

?>