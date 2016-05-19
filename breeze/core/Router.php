<?php

namespace breeze\core;
use breeze\events\RouteEvent;
use breeze\utils\Utils;

abstract class Router extends Input
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
    protected $suffix='';

    /**
     * 当前控制器名
     * @var string
     */
    protected $controller='';

    /**
     * 当前需要调度到的方法
     * @var string
     */
    protected $method='';

    /**
     * @construct
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 构造函数
     */
    protected function initialize()
    {
        parent::initialize();
        $this->controller = $this->config('controller');
        $this->method     = $this->config('method');

        $mode=$this->config('url_mode');
        $mode= $mode!==null ? $mode : self::AUTO_MODE ;

        //获取当前的路由模式
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

        //如果是标准的路由模式
        if( $mode === self::NORMAL_MODE )
        {
            $ckey =  $this->config('controller_key') ;
            $mkey =  $this->config('method_key') ;

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
            $suffix = &$this->suffix;

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
	 * 调度指定的控制器
	 * @return void
	 */
	public function dispatcher( $controller ,$method )
	{
        $space= strstr( __CONTROLLER__, APP_NAME );
        $space = str_replace('/','\\',$space);
        $class =  $space.'\\'.$controller;

        if( $this->hasEventListener( RouteEvent::BEFORE ) )
        {
            $event=new RouteEvent( RouteEvent::BEFORE );
            $event->controller= & $controller;
            $event->method= & $method;
            if( !$this->dispatchEvent( $event ) )
                return;
        }

        //获取控制器实例
        $instance=Single::getInstance( $class );

        call_user_func( array($instance,$method) );

        if( $this->hasEventListener( RouteEvent::AFTER ) )
        {
            $event=new RouteEvent( RouteEvent::AFTER );
            $event->controller= & $controller;
            $event->method= & $method;
            $this->dispatchEvent( $event );
        }
	}

}

?>