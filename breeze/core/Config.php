<?php

namespace breeze\core;
use breeze\interfaces\ISingle;
use breeze\utils\Utils;

class Config implements ISingle
{
	/**
	 * @private
	 * 默认的配置项
	 */
	protected $config_items=array(

	    //开启url编码
		'URL_ENCODE_ENABLE'=>false,

		//默认控制器类
		'CONTROLLER'=>'Home',
			
		//默认控制器的方法
		'METHOD'=>'index',
			
		//当URL_MODE是标准模式时，在url参数中接收控制器的键名
		'CONTROLLER_KEY'=>'c',
			
		//当URL_MODE是标准模式时，在url参数中接收控制器方法的键名
		'METHOD_KEY'=>'m',

        //文档编码
        'CHARSET'=>'UTF-8',

        //令牌名称
        'CSRF_TOKEN_NAME'=>'__HASH__',

        //保存cookie中的名称
        'CSRF_COOKIE_NAME'=>'__TOKEN__',

        //令牌的有效期
        'CSRF_COOKIE_EXPIRE'=>3600,

        //启用那些请求方法中的令牌验证
        'CSRF_VALIDATE_METHOD'=>'post',
	);

    /**
     * Constructs.
     */
    public function __construct()
    {
        Single::register(get_called_class(), $this );
        if( file_exists(__CONFIG__) )
        {
            $files=is_dir( __CONFIG__ ) ? Utils::files( __CONFIG__,'file' ) : array( __CONFIG__ );

            foreach( $files as $filename )
            {
                $suffix = pathinfo($filename, PATHINFO_EXTENSION);
                $suffix = stripos( CONFIG_FILE_SUFFIX , $suffix )!==false ? $suffix : null;
                $suffix = strtolower($suffix);

                if( $suffix=="php" )
                {
                    function c( $filename )
                    {
                        require_once( $filename );
                        $vars = get_defined_vars();
                        unset($vars['filename']);
                        return call_user_func_array('array_merge', array_pad( $vars,2,array() ) );
                    }

                    $config = c( $filename );
                    if( empty( $config ) )
                    {
                        $msg=sprintf("加载的配置文件中无任何配置项，请检查配置文件中的变量名是否为“config”。所在文件(%s)",$filename);
                        Utils::message( $msg, Utils::WARNING );
                    }
                    $this->config_items=array_merge( $this->config_items , array_change_key_case( $config ,CASE_UPPER ) );

                }else if( $suffix=="ini" )
                {
                    $this->parseTextConfig( $filename );
                }
            }

        }else
        {
            $msg=sprintf("加载的配置文件不存在。文件名(%s)" , __CONFIG__ );
            Utils::message( $msg , Utils::WARNING );
        }
    }

    /**
     * @return ISingle|Config
     */
    public static function getInstance()
    {
        return Single::getInstance( get_called_class() );
    }

    /**
     * 获取或者设置配置项
     * @param null $key
     * @param null $value
     * @return Mixed
     */
    public function config( $key=null, $value=null, $default=null )
    {
        if( !is_string($key) )return $this->config_items;
        $key = strtoupper( $key );
        if( $value===null )
        {
            $result = Utils::propery($this->config_items, $key, $default);
            if( $result===$default && $default!==null )
            {
                 $keys = explode('.', $key);
                 $ref = & $this->config_items;
                 while( $key = array_shift($keys) )
                 {
                     if( !isset($ref[$key]) )
                     {
                         $ref[$key]= empty($keys) ? $default : array();
                     }
                     $ref = & $ref[$key];
                 }
            }
            return $result;
        }
        $this->config_items[ $key ] =  $value;
        return true;
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

            $this->config_items=array_merge( $this->config_items , array_change_key_case( $config ,CASE_UPPER ) );

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