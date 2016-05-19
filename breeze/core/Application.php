<?php 

namespace breeze\core;
use breeze\interfaces\ISingle;
use breeze\utils\Utils;

/**
 * 应用程序类，所有的配置都由此类初始化
 */
class Application extends EventDispatcher implements ISingle
{

    /**
     * @public
     * 标准解析模式 inde.php?a=b&c=d
     */
    const NORMAL_MODE=1;

    /**
     * @public
     * 路径解析模式 inde.php/b/c/d
     */
    const PATH_MODE=2;

    /**
     * @public
     * 自动解析模式。以上的综合，根据传递时的格式来取决使用哪一种
     */
    const AUTO_MODE=3;

    /**
     * @public
     * rewrite解析模式 index/b/c/d
     */
    const REWRITE_MODE=4;


    /**
     * uri中是否有脚本后缀名
     * @var string
     */
    protected $uri_suffix='';

    /**
     * @private
     */
    public  $csrf_token_name		= '__HASH__';

    /**
     * @private
     */
    protected $csrf_cookie_expire	= 3600;

    /**
     * @private
     */
    protected $csrf_cookie_name	    = '__CSRF__';

    /**
     * 在指定的请求方法中验证
     * @var string
     */
    public $csrf_validate_method='post';

    /**
     * @private
     */
    public $controller='Home';

    /**
     * @private
     */
    public $method='index';

    /**
     * 构造函数
     */
    public function __construct()
    {
        Single::register( get_called_class() , $this );

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

        //解析命令行的参数
        if( IS_CLI )
        {
            $argv=$_SERVER['argv'];
            array_shift( $argv );
            if( !empty($argv) )
            {
                parse_str(implode($argv,'&'), $_GET);
            }
        }

        $mode= $this->urlMode();
        $this->controller = $this->config('controller');
        $this->method     = $this->config('method');

        //如果是标准的路由模式
        if( $mode === self::NORMAL_MODE )
        {
            $ckey =  $this->config('controller_key');
            $mkey =  $this->config('method_key');

            if( isset( $_GET[ $ckey ] ) && !empty($_GET[ $ckey ]) )
                $this->controller=$_GET[ $ckey ];

            if( isset( $_GET[ $mkey ] ) && !empty($_GET[ $mkey ]) )
                $this->method=$_GET[ $mkey ];

        }else
        {
            //获取请求的uri
            $uri=$this->uri();
            $uri = trim($uri,'/');

            //只需?前段的字符串
            $str = strstr($uri,'?', true );
            $uri = $str===false ? $uri : $str;

            //脚本的后缀名
            $suffix = &$this->uri_suffix;

            //获取后脚本的后缀名并清除掉
            $uri = preg_replace_callback('/(\.\w+)(\/|$)/i',function( $a )use( &$suffix )
            {
                $suffix=$a[1];
                return $a[2];

            }, $uri );

            //将所有的.都替换成/
            $uri = str_replace('.','/',$uri);

            //转成参数段
            $uri = explode('/', str_replace('.','/',$uri) );

            //设置控制器名
            if( !empty($uri) )
            {
                $this->controller = array_shift( $uri );
            }

            //设置方法名
            if( !empty($uri) )
            {
                $this->method = array_shift( $uri );
            }

            //把参数合并到$_GET的首个元素
            if( !empty($uri) )
            {
                array_splice($_GET,0,0,$uri);
            }
        }

        //验证 csrf
        $this->validate();

        $charset=$this->config('charset',null,'UTF-8');
        if( !in_array( strtoupper($charset), array('UTF-8','GB2312','GBK','BIG5') ) )
        {
            $charset = 'UTF-8';
            $this->config('charset',$charset);
        }

        //如果是CGI模式则设置响应头编码
        if( IS_CGI )
        {
            header( 'content-type:text/html;charset='. $charset );
        }

        //判断系统是否支持多字节处理
        define('MULTIBYTE_CHARSET',  ( preg_match('/./u', 'é' ) === 1 AND function_exists('iconv') AND ini_get('mbstring.func_overload') != 1 ) );
        if( !MULTIBYTE_CHARSET && $charset ==='UTF-8' )
        {
            trigger_error('Does not support multibyte');
        }

        //判断是否加载了mbstring库
        if( extension_loaded('mbstring') && !mb_internal_encoding( $charset ) )
        {
            trigger_error('mbstring not support the '.$charset);
        }
    }

    /**
     * @return Application
     */
    public static function getInstance()
    {
        return Single::getInstance( get_called_class() );
    }

    /**
     * 开始初始化环境
     */
    public function start()
    {
        $space= strstr( __CONTROLLER__, APP_NAME );
        $space = str_replace('/','\\',$space);
        $class =  $space.'\\'.$this->controller;
        $this->dispatcher( $class , $this->method );
    }

    /**
     * 调度指定的控制器
     * @return void
     */
    public function dispatcher( $class ,$method )
    {
        //获取控制器实例
        $instance=Single::getInstance( $class );
        call_user_func( array($instance,$method) );
    }

    /**
     * @param null $key
     * @param null $value
     * @param null $default
     * @return Mixed
     */
    public function config($key=null, $value=null, $default=null)
    {
        static $config=null;
        if( $config ===null )
        {
            $config = Config::getInstance();
        }
        return $config->config($key,$value,$default);
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
        $index =  intval( $index );
        if( $index < 0 )
        {
            $index = $index + count($segments);
            $index = max( 0, min( count($segments)-1, $index ) );
        }
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

            $headers = array_change_key_case($headers,CASE_UPPER );
            $h=array();
            foreach ($headers as $k => $val)
            {
                $k= str_replace('-', '_', $k );
                $h[ $k ] = $val;
            }
            $headers = $h;
        }

        if( $value === null )
        {
            return $key===null ? $headers : Utils::propery( $headers, strtoupper($key), $default);
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
        $prefix = $this->config('cookie.prefix',null,'');
        if( $value===null && $expire > 0 )
        {
            return $key===null ? $_COOKIE : Utils::propery( $_COOKIE,$prefix.$key );
        }
        $cookie->set($prefix.$key,$value,$expire,$path,$domain,$secure);
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
            if( stripos('post,get,put,delete',$method)===false )
            {
                throw new Error('invalid request method');
            }
        }
        return $in=== null ? $method : stripos($in,$method)!==false;
    }

    /**
     * 根据当前传递的参数解析成 url 的地址  <br/>
     * 注意 ： 如果 $controller 或者 $method 不设置则会取当前使用的值。相当于返回当前的 url 地址
     * @param string $controller 控制器名
     * @param string $method  方法名
     * @param string $param  传递的参数
     * @return string
     */
    public function url( $controller='',$method='', array $param=array() )
    {
        $controller= empty($controller) ? $this->route[0] : $controller;
        $method    = empty($method)     ? $this->route[1] : $method;

        $mode = $this->urlMode();
        $isseg = is_numeric( $this->config('csrf_token_name',null,'__HASH__') );
        if( $mode===self::NORMAL_MODE )
        {
            $c=strtolower( $this->config('CONTROLLER_KEY') );
            $m=strtolower( $this->config('METHOD_KEY') );
            $param[$c] = $controller;
            $param[$m] = $method;

            //验证口令需要放在请求地址栏中的第几段中
            if( !$isseg )$this->setUrlToken($param, false );
            return sprintf('%s?%s', $this->scriptName(),http_build_query($param) );

        }else
        {
            $segments=array();
            $data=array();
            array_walk($param,function($item,$key)use(&$segments,&$data){
                if( is_numeric($key) )
                {
                    array_push($segments,$item);
                }else
                {
                    $data[$key]=$item;
                }
            });

            //验证口令需要放在请求地址栏中的第几段中
            $this->setUrlToken($isseg ? $segments : $data, $isseg );

            $router=array($controller,$method);
            if( $mode===self::PATH_MODE )
            {
                array_unshift( $segments,$this->scriptName() );

            }else if( strpos($method,'.')===false && !empty($this->uri_suffix) )
            {
                array_push($router,'.'.$this->uri_suffix );
            }
            $segments = array_splice($segments,0,0,$router);
            $segments= implode('/', $segments );
            $param =  empty($data) ? '' : '?'.http_build_query($data);
            return sprintf('/%s%s', $segments, $param );
        }
    }

    /**
     * 获取哈希令牌。
     * @return string
     */
    public function hashToken()
    {
        static $hash=null;
        if ( $hash===null )
        {
            $hash = md5( uniqid( mt_rand(), TRUE) );
            $token_name = $this->config('CSRF_COOKIE_NAME',null,'__TOKEN__');
            $expire     = $this->config('CSRF_COOKIE_EXPIRE',null,3600);
            $this->cookie( $token_name, $hash, $expire );
        }
        return $hash;
    }


    /**
     * 验证口令需要放在请求地址栏中的第几段中
     * @private
     * @param array $param
     * @param bool $flag
     */
    private function setUrlToken( array &$param , $flag=false )
    {
        $method = $this->config('CSRF_VALIDATE_METHOD');
        if( !empty( $method ) )
        {
            $token_name=  $this->config('CSRF_TOKEN_NAME',null,'__HASH__');
            if( $flag===true )
            {
                $index = intval( $token_name );
                $index = $index < 0 ? $index+count($param)+1 : $index;
                $index =  max( min($index, count($param) ),0);
                array_splice($param,$index,0, $this->hashToken() );

            }else if( stripos($method,'get') )
            {
                $param[ $token_name ] = $this->hashToken();
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
     * 防止跨站脚本功击
     * @return	void
     */
    private function validate()
    {
        $method = $this->config('CSRF_VALIDATE_METHOD');
        if( !empty($method) )
        {
            $cookie_name = $this->config('CSRF_TOKEN_NAME',null,'__TOKEN__');
            if( $this->requestMethod( $method ) )
            {
                $csrf_value=$this->cookie( $cookie_name );
                $key = $this->config('CSRF_TOKEN_NAME',null,'__HASH__');
                if( is_numeric($key) )
                {
                    $token = $this->segment( $key );

                }else
                {
                    $token = isset( $_POST[$key] ) ? $_POST[$key] : @$_GET[$key];
                    if( isset( $_POST[$key] ) )unset( $_POST[ $key ] );
                    if( isset( $_GET[$key] ) )unset( $_GET[ $key ] );
                }
                if( $token !== $csrf_value )
                {
                    throw new Error('invalid request');
                }
            }
        }
    }

    /**
     * @private
     */
    private function urlMode()
    {
        static $mode=null;
        if( $mode===null )
        {
            $mode=$this->config('url_mode',null,self::AUTO_MODE);

            //自动解析模式
            if( $mode==self::AUTO_MODE  )
            {
                if( $this->rewrite() )
                    $mode=self::REWRITE_MODE;
                else if( isset($_SERVER['PATH_INFO']) )
                    $mode=self::PATH_MODE;
                else
                    $mode=self::NORMAL_MODE;
                $this->config('url_mode',$mode);
            }
        }
        return $mode;
    }
}