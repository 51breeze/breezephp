<?php

namespace breeze\utils;

use breeze\core\Error;
use breeze\core\Lang;
use breeze\core\Single;

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
				$log_path=self::mkdir( $log_path );
			
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
     *  获取目录中的文件
     *  @param  $path 要获取目录的路径
     *  @param  $type 要获取的文件类型，默认 全部的类型（all）,可用的类型 “all,dir,file”
     *  @return array $path 路径下的所有目录
     */
    public static function files( $path ,$type='all')
    {
        $type=strtolower( $type );
        $result=array();

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
        foreach ($dir as $v)
        {
            if( strpos($v,".")===0 )
                continue;
            $filename=$path."/".$v;
            if( is_dir($filename) )
            {
                $type=='dir' &&  array_push($item, $filename);
                $item =array_merge( $item,Utils::files( $filename ,$type ) );

            }else if( $type=='all' or $type=='file' )
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
    public static function mkdir( $path,$dir='', $mode=0755 )
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
     * 将字符串转换成指定的字符编码。<br/>
     * 如果不设置指定的编码则使用当前系统中定义的字符编码
     * @param	$to string 指定字符编码
     * @param	$from string 当前字符的编码。如是为 null 则会自动获取当前 $str 的编码。
     * @return	string
     */
    public static function convert( $str , $to='UTF-8', $from=null )
    {
        if( !is_string($str) || Utils::isAscii( $str ) )
            return $str;

        if ( function_exists('mb_convert_encoding') )
        {
            $str = @mb_convert_encoding($str, $to );

        }elseif ( function_exists('iconv') )
        {
            if( $from === null )
            {
                if( function_exists('mb_detect_encoding') )
                    $from=mb_detect_encoding( $str , array('ASCII','GB2312','GBK','UTF-8','BIG5') );
                else
                    trigger_error('Does not support mb_detect_encoding,need assign encoding for from param');
            }
            if( !empty( $from ) )
                $str = @iconv( $from , $to.'//IGNORE', $str);
        }
        else
        {
            trigger_error('convert failed');
        }
        return $str;
    }


    /**
     * 判断字符串是否是 ASCII 字符集
     * @param	string
     * @return	boolean
     */
    public static function isAscii( $str )
    {
        return ( preg_match('/[^\x00-\x7F]/S', $str ) == 0 );
    }


    /**
     * 字符串截取，支持中文和其他编码
     * @param string $str 需要转换的字符串
     * @param string $start 开始位置
     * @param string $length 截取长度
     * @param string $charset 编码格式
     * @param string $suffix 截断显示字符
     * @return string
     */
    public static function msubstr( $str, $start=0, $length=null, $charset=self::UTF8 ,$suffix='')
    {
        if( function_exists("mb_substr") )
        {
            $slice = mb_substr($str, $start, $length, $charset);

        }elseif(function_exists('iconv_substr'))
        {
            $slice = iconv_substr($str,$start,$length,$charset);

        }else
        {
            $re[self::UTF8  ]   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re[self::GB2312]   = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re[self::GBK   ]   = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re[self::BIG5  ]   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("",array_slice($match[0], $start, $length));
        }
        return $slice.$suffix;
    }


    /**
     * 给字符串添加转义符
     * @param string $str
     * @return string
     */
    public static function addslashes( $str )
    {
        if( !is_string($str) || empty($str) )
            return $str;

        /**
         * 防止重复转义
         * magic_quotes_gpc = on 时系统会自动添加转义符
         */
        if( get_magic_quotes_gpc() )
            $str=stripslashes( $str );
        return addslashes( $str );
    }

    /**
     * 解析通过 htmlentities 编码后的字符
     * @param	string
     * @param	string
     * @return	string
     */
    public function unhtmlentities($str, $charset='UTF-8')
    {
        if ( strpos($str, '&') === false )
        {
            return $str;
        }
        $str = html_entity_decode($str, ENT_COMPAT, $charset);
        $str = preg_replace_callback('~&#x(0*[0-9a-f]{2,5})~i',  function($param){return chr(hexdec($param[1]));}, $str);
        return preg_replace_callback('~&#([0-9]{2,4})~', function($param){return chr($param[1]);}, $str);
    }

    /**
     * 获取一个对象或者数组中的值。对键名不区分大小写
     * @param $object
     * @param $key
     * @return mixed
     */
    public static function fetchCaseValue( &$object, $key ,$default=null)
    {
        if( is_array($object) )
        {
            if( isset( $object[$key] ) )
              return $object[$key];

        }else if( is_object($object)  )
        {
            if( property_exists($object,$key) )
                return $object->$key;
        }
        foreach($object as $prop=>$value )if( strcasecmp($prop,$key)===0 )
        {
           return $value;
        }
        return $default;
    }

    /**
     * 获取一个对象中的属性值
     * @param $object 指定的对象
     * @param $name 属性名, 可以使用'.'的形式获取多层级的属性值
     * @return null
     */
    public static function propery( &$object, $name ,$default=null )
    {
         $name = is_string($name) && strpos($name,'.') !== false ? explode('.', $name ) : $name;
         $key  = is_array($name) ? array_shift( $name ) : $name;
         $result = Utils::fetchCaseValue($object,$key,$default);
         if( $result !==$default && !is_scalar($result) &&  is_array( $name ) && !empty($name) )
         {
             $result = Utils::propery( $result, array_shift( $name ) , $default );
         }
         return $result;
    }

    /**
     * 加载指定的文件
     * @param  $class String 要加载的文件名
     * @param  $path String  指定加载的路径 ，默认 ''
     * @param  suffix String 加载文件的后缀 , 默认 '.php'
     * @return 如果加载成功返回 true 否则返回false.
     */
    public static  function import($class,$path='',$suffix='.php')
    {
        static $_loaded=array();
        $filename=$class;
        if( strpos($class,$suffix)===false )
        {
            if( !empty($path) )
            {
                $path=str_replace(".", "/", $path );
                $path=preg_replace( "/\\+/s",'/',trim( $path,"/" ) );
                $class=rtrim($path,'/')."/".$class;
            }

            if( strpos($class, '.')!==false )
            {
                $class=str_replace(".", "/", trim($class,'.') );
            }
            $filename=$class.$suffix;
        }
        if( !isset( $_loaded[ $filename ] ) )
        {
            if( !file_exists( $filename ) )
            {
                throw new Error(' No found file. for '.$filename );
            }else{
                $_loaded[ $filename ]= require( $filename );
            }
        }
        return $_loaded[ $filename ];
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
     * 验证ip地址
     * @param string $ip ip地址
     * @param string $type ip类型, ipv4|ipv6
     * @return bool
     */
    public static function isip( $ip, $type ='')
    {
        $type = empty($type) ? ( strpos($ip, '.') !== FALSE ? 'ipv4' : 'ipv6' ) : strtolower( $type );
        return function_exists('filter_var') ? (bool)filter_var($ip, FILTER_VALIDATE_IP, $type=='ipv4' ? FILTER_FLAG_IPV4 : FILTER_FLAG_IPV6 ) : false;
    }

    /**
     * 设置请求头
     * @param $type
     * @param string $value
     */
    public static function setHeader( $type, $value='' )
    {
         $header=array(
            'html'  => 'Content-type:text/html; charset=%s',
            'xml'   => 'Content-type:text/xml; charset=%s',
            'plain' => 'Content-type:text/plain; charset=%s',
            'css'   => 'Content-type:text/css',
            'javascript'=>'Content-type:text/javascript',
            'rss' => 'Content-type:application/rss+xml; charset=ISO-8859-1',
            'atom'=> 'Content-type:application/atom+xml; charset=ISO-8859-1',
            'pdf' => 'Content-type:application/pdf',
            'json'=> 'Content-type:application/json; charset=%s',
            'zip' => 'Content-type:application/zip',
            'stream'=> 'Content-type:application/octet-stream',
            'flash' => 'Content-type:application/x-shockwave-flash',
            'attachment'=>'Content-Disposition:attachment;filename=%s',
            'forceDownload'=>'Content-type:application/force-download',
            'encoding'=>'Content-Transfer-Encoding:%s',
            'jpeg'=>'Content-type:image/jpeg',
            'audio'=>'Content-type:audio/mpeg',
            'language'=>'Content-Language:charset=%s',
            'ranges'=>'Accept-Ranges:%s',
            'length'=>'Content-Length:%d',
            'lastModified'=>function($value){ return 'Last-Modified:'.gmdate("D, d M Y H:i:s", $value).' GMT';},
            'notModified'=>'HTTP/1.1 304 Not Modified',
            'noCache'=>'Cache-Control: no-cache, no-store, max-age=0, must-revalidate|Pragma:no-cache|Expires:-1',
            'location'=>'Location:%s',
            'refresh'=>'Refresh:%d;url=%s',
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

        $content = isset( $header[$type] ) ?  $header[$type] : null;
        $code=null;
        $replace= null;
        if( is_numeric($type) )
        {
            $content = !empty( $content ) ? Utils::propery($_SERVER, 'SERVER_PROTOCOL','Status').": {$type} {$content}" : null;
            $code    = $type;
            $replace=true;

        }else
        {
            $value = $value==='' ? 'UTF-8' : $value;
            if( is_callable( $content ) )
            {
                $content = call_user_func($content,$value);

            }else{
                $value =  explode(',',$value);
                array_unshift($value,$content);
                $content = call_user_func_array('sprintf', $value );
            }
        }
        header( $content, $replace, $code );
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
     * 对数据进行加解密
     * @param string $data 需要加解密的字符
     * @param string $key 密钥
     * @param bool $flag true加密 false 解密
     * @return string
     */
    public static function cipher($data, $key, $flag=true)
    {
        $key = md5($key);
        $data=$flag===false ? base64_decode($data) : $data;
        $len = strlen($data);
        $str='';
        for ($i = 0; $i < $len; $i++)
        {
            $v = ord( substr($data,$i,1) );
            $k = ord( substr($key, $i % 32 ,1) );
            $str .= chr( $flag===true ? $v + $k % 256 : abs( $v - $k + 256 ) );
        }
        return $flag===true ? base64_encode($str) : $str;
    }

}

?>