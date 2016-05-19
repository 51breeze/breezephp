<?php

namespace breeze\core;

abstract class URI extends Security
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
     * uri 中是否有脚本后缀名
     * @var string
     */
    protected $url_suffix='';


    /**
     * constructs
     */
    public function __construct()
    {
        parent::__construct();
        $mode= $this->urlMode();

        if( IS_CLI )
        {
            $argv=$_SERVER['argv'];
            array_shift( $argv );
            if( !empty($argv) )
            {
                parse_str(implode($argv,'&'), $_GET);
            }

        }else if( $mode !== self::NORMAL_MODE )
        {
            $uri=$this->requestUri();
            $uri = trim($uri,'/');
            $str = strstr($uri,'?', true );
            $uri = $str===false ? $uri : $str;
            $url_suffix = &$this->url_suffix;

            $uri = preg_replace_callback('/(\.\w+)(\/|$)/i',function( $a )use( &$url_suffix )
            {
                $url_suffix=$a[1];
                return $a[2];

            }, $uri );

            $uri = str_replace('.','/',$uri);
            if( !empty($uri) )
            {
                array_splice($_GET,0,0,explode('/', str_replace('.','/',$uri) ) );
            }
        }
    }

    private $__mode__=false;

    /**
     * @private
     */
    private function urlMode()
    {
        $mode=$this->config('url_mode');
        if( !$this->__mode__ )
        {
            $this->__mode__=true;
            $mode= $mode!==null ? $mode : self::AUTO_MODE ;

            //自动解析模式
            if( $mode==self::AUTO_MODE  )
            {
                if( $this->isRewrite() )
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


    /**
     * 根据当前传递的参数解析成 url 的地址  <br/>
     * 注意 ： 如果 $controller 或者 $method 不设置则会取当前使用的值。相当于返回当前的 url 地址
     * @param string $controller 控制器名
     * @param string $method  控制器中的方法名
     * @param string $param  传递的参数
     * @return string
     */
    public function url( $controller='',$method='', array $param=array() )
    {
        $controller= empty($controller) ? $this->route[0] : $controller;
        $method    = empty($method)     ? $this->route[1] : $method;

        $mode = $this->urlMode();
        if( $mode===self::NORMAL_MODE )
        {
            $c=strtolower( $this->config('CONTROLLER_KEY') );
            $m=strtolower( $this->config('METHOD_KEY') );
            $param[$c] = $controller;
            $param[$m] = $method;
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

            $param =  empty($data) ? '' : '?'.http_build_query($data);
            $segments = array_splice($segments,0,0,array($controller,$method) );
            if( $mode===self::PATH_MODE )
            {
                array_unshift( $segments,$this->scriptName() );
            }
            $segments= implode('/', $segments );
            return sprintf('/%s%s', $segments, $param );
        }
    }

    /**
     * @private
     */
    public function scriptName()
    {
        return isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : @getenv('SCRIPT_NAME');
    }

    /**
     * 获取指定分段的查询参数。
     * @param $index
     * @return null
     */
    public function segment( $index )
    {
        static $segments=null;
        if( $segments===null )
        {
            $segments= array_values( $_GET );
        }
        return isset( $segments[$index] ) ? $segments[$index] : null;
    }

    /**
     * 请求的uri的字符串
     * @return string
     */
    public function requestUri()
    {
        return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : @getenv('REQUEST_URI');
    }

    /**
     * 判断当前是否使用了rewrite 重定向
     * @return boolean
     */
    public function isRewrite()
    {
        return ( isset( $_SERVER['HTTP_X_REWRITE_URL'] ) || isset( $_SERVER['REDIRECT_URL'] ) );
    }

}





