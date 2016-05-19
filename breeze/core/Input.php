<?php 

namespace breeze\core;
use breeze\utils\Utils;

abstract class Input extends Config
{

	/**
	 * @construct
	 */
    public function __construct()
    {
        parent::__construct();

        // 在POST请求下修正未获得的数据。
        if( empty($_POST) && $this->requestMethod('post,put') )
        {
            $data = file_get_contents('php://input','r');
            $_POST=array();
            if( !empty($data) )
            {
                $data=urldecode( $data );
                parse_str($data,$_POST);
            }
        }
	}

    /**
     * 获取客户端的ip地址
     * @return string
     */
    private function addr()
    {
        static $ip = null;
        if ( $ip === null )
        {
            $ip = '0.0.0.0';
            foreach ( array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP','REMOTE_ADDR') as $header)
            {
                $s = isset( $_SERVER[ $header ] ) ? $_SERVER[ $header ] : @getenv($header) ;
                if ( $s !== null && Utils::isip( $s ) )
                {
                    $ip = $s;
                    break;
                }
            }
        }
        return $ip;
    }

    /**
     * 获取指定分段的查询参数。
     * @param $index
     * @return null
     */
    public function segment( $index )
    {
        static $segments=null;
        if( $segments===null )$segments= array_values( $_GET );
        return isset( $segments[$index] ) ? $segments[$index] : null;
    }

    /**
     * 获取地址栏中的参数
     * @param $key
     * @return null
     */
    public function get( $key=null , $default=null )
	{
	    return $key===null ? $_GET : Utils::propery( $_GET, $key , $default);
	}

    /**
     * 获取表单中的参数
     * @param null $key
     * @return null
     */
    public function post( $key = null , $default=null)
	{
        return $key===null ? $_POST : Utils::propery( $_POST, $key , $default );
	}

    /**
     * 获取表单中的参数
     * @param null $key
     * @return null
     */
    public function request( $key = null , $default=null)
    {
        static $request = null;
        if( $request=== null ) $request = array_merge($_GET, $_POST);
        return $key===null ? $request : Utils::propery( $request, $key , $default );
    }

    /**
     * 获取表单中上传文件的参数
     * @param null $key
     * @return null
     */
    public function files( $key = null , $default=null)
    {
        return $key===null ? $_FILES : Utils::propery( $_FILES, $key , $default );
    }


    /**
     * 获取服务器的参数
     * @param null $key
     * @return null
     */
    public function server( $key=null, $default=null )
    {
        $key = is_string( $key ) ? strtoupper( $key ) : $key;
        if( $key==='REMOTE_ADDR' )
        {
            $_SERVER['REMOTE_ADDR'] = $this->addr();
            return $_SERVER['REMOTE_ADDR'];
        }
        return $key===null ? $_SERVER : Utils::propery( $_SERVER, $key, $default );
    }

    /**
     * 获取/设置请求头信息
     * @param null $key
     * @param null $value
     * @return array|null
     */
    public function header( $key=null , $value=null, $default=null )
    {
        static $headers=array();
        if ( empty( $headers ) )
        {
            if ( function_exists('apache_request_headers') )
            {
                $headers=call_user_func('apache_request_headers');
            }
            else
            {
                $headers['Content-Type'] = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : @getenv('CONTENT_TYPE') ;
                foreach ($_SERVER as $key => $val) if ( strncmp($key, 'HTTP_', 5 ) === 0 )
                {
                   $headers[ substr($key, 5) ] = $this->server( $key );
                }
            }
            foreach ($headers as $key => $val)
            {
                $key = str_replace('_', ' ', strtolower($key) );
                $key = str_replace(' ', '-', ucwords($key) );
                $headers[ $key ] = $val;
            }
        }

        if( $value === null )
        {
            return $key===null ? $headers : Utils::propery( $headers, $key, $default);
        }
        Utils::setHeader($key,$value);
        return $this;
    }

    /**
     * 获取/设置 COOKIE 数组中的数据。
     * @param string $key 指定位于数组中的键名，可以是数字或者字符串。
     * @param string $value
     * @param int $expire
     * @param string $domain
     * @param string $path
     * @param string $secure
     * @return mixed|$this
     */
    public function cookie( $key=null, $value = null, $expire = 86500, $path = null,$domain = null, $secure = false)
	{
        $cookie = Cookie::getInstance();
        if( $value===null && $expire > 0 )
        {
            return $key===null ? $_COOKIE : Utils::propery( $_COOKIE,$cookie->prefix.$key );
        }
        $cookie->set($key,$value,$expire,$path,$domain,$secure);
        return $this;
	}

    /**
     * 获取/设置会话参数
     * @param null $key
     * @param null $value
     * @param null $default
     * @return $this|null
     */
    public function session( $key=null , $value=null, $default=null )
    {
        $session = Session::getInstance();
        if( $value==null )
        {
            return $key===null ? $_SESSION : Utils::propery( $_SESSION, $key, $default );
        }
        $session->set($key,$value);
        return $this;
    }

    /**
     * 判断当前是否ajax请求
     * @return bool
     */
    public function ajax()
	{
	    return strcasecmp($this->server('HTTP_X_REQUESTED_WITH') , 'XMLHttpRequest')===0;
	}

    /**
     * @private
     */
    public function scriptName()
    {
        return isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : @getenv('SCRIPT_NAME');
    }

    /**
     * 请求的uri的字符串
     * @return string
     */
    public function uri()
    {
        return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : @getenv('REQUEST_URI');
    }

    /**
     * 判断当前是否使用了rewrite 重定向
     * @return boolean
     */
    public function rewrite()
    {
        return ( isset( $_SERVER['HTTP_X_REWRITE_URL'] ) || isset( $_SERVER['REDIRECT_URL'] ) );
    }

    /**
     * 获取当前请求的方法
     * @param null $in 一个字符串，用来判断当前请求是否在指定的方法中。如果不指定则返回当前请求的方法。
     * @return bool|string
     */
    public function requestMethod( $in = null )
    {
        static $method=null;
        if( $method===null )
        {
            $method = $this->server('REQUEST_METHOD','');
        }
        return $in=== null ? $method : stripos($in,$method)!==false;
    }

}
