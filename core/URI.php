<?php

namespace breeze\core;

abstract class URI extends System
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
	 * url请求字符串部分允许的定界符
	 * @var string
	 */
	protected $ALLOW_DELIMITER='+|=|&|/|.';

	/**
	 * URL请求串正则表达式
	 * @var array
	 */
	protected $URI_PATTERNS=array(
			0=>'\w+', //严谨验证,用于匹配参数名
			1=>'.*?', //不验证，用于匹配参数的值
            2=>'[\w\%\s\+\-\_]+' //标准验证,用于匹配经过 urlencode 编码后的所有字符
	);

    //脚本文件名
	private $URL_SCRIPTNAME='filter(0)delimiter.';

    //脚本文件后缀名
	private $URL_SUFFIX='.php?';

    //标准格式的请求串
	private $URL_PARAM='filter(0)=filter(1)delimiter&';

     /**
     * @see \com\core\System::initialize()
     */
    protected function initialize()
    {
        parent::initialize();
        $this->parseUri();
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
    	empty($controller) && $controller=CONTROLLER;
    	empty($method)     && $method    =METHOD;

    	$url_scriptname=$this->URL_SCRIPTNAME;
    	$url_suffix=$this->URL_SUFFIX;
    	$url_param=$this->URL_PARAM;
    	
    	$c=strtolower( $this->getConfig('CONTROLLER_KEY') );
    	$m=strtolower( $this->getConfig('METHOD_KEY') );

    	$url='';
    	
    	$dispatcher[$c]=$controller;
    	$dispatcher[$m]=$method;
    	
    	$pathinfo=pathinfo( $this->getScriptName() );
    	
    	if( !empty( $url_scriptname ) )
    	{
    		if( empty($url_param) )
    		{
    			$dispatcher=array_merge( $dispatcher,$param );
    		}
    		
    		$url=$this->toUrlStr( $url_scriptname , $dispatcher , '-' );
    		$url=sprintf('%s/%s%s',$pathinfo['dirname'],$url,$url_suffix);
    		
    	}else
    	{
    		$param=array_merge( $dispatcher,$param );
    	}

    	if( !empty($url_param) )
    	{
    		if( empty($url) )
    		{
    			$url=sprintf('%s/%s%s',$pathinfo['dirname'],$pathinfo['filename'],$url_suffix);
    		}
    		
    		$url.=$this->toUrlStr( $url_param , $param , '&' );
    	}
    	
    	return trim( trim($url,'/') ,'?');
    	
    }
    
    /**
     * @private
     */
    public function getScriptName()
    {
    	return isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : @getenv('SCRIPT_NAME');
    }
    
    
    /**
     * @private
     */
    private function toUrlStr( $pattern , array & $param=array() , $de='&' )
    {
    	
    	if( !empty( $pattern ) && !empty($param) )
    	{
    		$assignment=$this->assignment( $pattern );
    		$delimiter=$this->delimiter( $pattern );
    		empty( $delimiter ) && $delimiter=$de;
    		$limit=$this->limiter( $pattern );
    		
    		$newVal=$param;
    		
	    	if( !empty($limit) && ($limit['max']-2)>0 )
	    	{
	    		$newVal=array_splice( $param, 0, $limit['max']-2 );
	    	}
	    	
	    	if( !empty($newVal) )
	    	{
    			if( !empty($assignment) )
    			{
    				$func = create_function('&$item,$key,$assignment', ' $item=$key.$assignment.$item; ' );
    				array_walk($newVal,$func,$assignment);
    				$func=null;
    			}
    			return implode( $delimiter, $newVal );
	    	}
    	}
    	
    	return '';
    }

    /**
     * 获取请求的字符串
     * @return string
     */
    protected function getRequestStr()
    {
        $query_string=isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : @getenv('REQUEST_URI');
    	$script_name=rtrim( $this->getScriptName(),'/');

        if( preg_match('/\w+\.\w+$/i',$script_name,$filename)>0 )
    	   $script_name=preg_replace( '/\w+\.\w+$/i' ,'' , $script_name );

    	//校正反斜线
    	$script_name=trim(str_replace('\\', '/', $script_name),'/');
        $query_string=trim($query_string,'/');
    	
    	//去掉脚本路径部分
        if( !empty($script_name) )
    	   $query_string=preg_replace('/^'.preg_quote($script_name,'/').'/i' , '', $query_string );
    	return  $query_string;
    }
    
    /**
     * 获取指定匹配参数的正则表达式
     * @param mixed $index
     * @return string
     */
    protected function pattern( $index )
    {
        $index=isset($index[1]) ? $index[1] : 0;
    	if( $this->isConfig( 'URI_PATTERNS' ) )
    	{
    	   $patterns=$this->getConfig( 'URI_PATTERNS' );
    	   if( isset( $patterns[ $index ] ) )
    	   {
    	   	 return $patterns[ $index ];
    	   }
    	}

        return isset( $this->URI_PATTERNS[ $index ] ) ? $this->URI_PATTERNS[ $index ] : '\w+';
    }
    
    /**
     * 获取定界符
     * @param & $str ,
     * @param $default='&'
     * @return string
     */
    protected function delimiter( & $str )
    {
    	if( is_string($str) )
    	{
    		$de= $this->adjust( $this->ALLOW_DELIMITER ,'|');
	    	$pattern='#delimiter[\s+]?([\s+]?'.$de.'[\s+]?)#is';

	    	if( preg_match($pattern,$str,$delimiter)>0 )
	    	{
	    	   $str=preg_replace( $pattern , '', $str );
	    	   if( isset( $delimiter[1] ) )
	    	    return $delimiter[1];
	    	}
    	}
    	return null;
    }
    
    
    /**
     * 获取受限值
     * @param & $str ,
     * @param $default='&'
     * @return array
     */
    protected function limiter(  & $str )
    {
    	if( is_string($str) )
    	{
	    	$pattern='/\{[\s+]?((\d+)?[\s+]?[,]?[\s+]?(\d+)?)[\s+]?\}/';
	    	$min=0;
	    	$max=10000;
	    	 
	    	if( preg_match($pattern,$str,$limiter)>0 )
	    	{
	    		$str=preg_replace( $pattern , '', $str );
	    		
	    		( count($limiter)===3 ) && $min=$limiter[2];
	    		( count($limiter)===4 ) && list(,,$min,$max)=$limiter;
	    		
	    		$str=preg_replace($pattern, '', $str);
	    		
	    		return array('min'=>$min,'max'=>$max);
	    		
	    	}
	    	
    	}
    	return null;
    }
    
    /**
     * 转义 preg 符号
     * @param string $str
     * @return mixed
     */
    protected function adjust( $str ,$de='|' )
    {
    	if( is_string($str) )
    	{
    		$str=str_replace('\\', '', $str);
    		$str=str_replace($de, '@#####@', $str );
    		$str=preg_quote( $str );
    		$str=str_replace('@#####@',$de, $str );
    		return $str;
    	}
    	return $str;
    }
    
    /**
     * 默认的正则表达式
     * @param $str
     * @return string
     */
    protected function filterPattern( $str )
    {
    	if( !empty($str) && is_string( $str ) )
    	{
    	   $str=str_replace('@', '\w+', $str);
    	   $str=preg_replace_callback('/filter\((\w+)\)/is', array($this,'pattern' ), $str);
    	}
    	return $str;
    }
    
    /**
     *  获取需要转换成键值对应形式的赋值符号，通常是 ‘=’
     */
    protected function assignment( & $str )
    {
    	$assignment=$this->adjust( $this->ALLOW_DELIMITER , '|' );
    	$pattern='~(filter\s*\(.*?\)\s*|@)\s*('.$assignment.')\s*(filter\s*\(.*?\)\s*|@)~i';
    	if( preg_match($pattern, $str, $assignment ) )
    	{
    		return $assignment[2];
    	}
    	return null;
    }

    
    /**
     * 解析url请求格式  index.login.html?aa=cc   分解后：  （index.login） 为控制器部分 ，  （ .html?） 为请求的后缀  ，（ aa=cc） 请求的参数
     * @param string $controller 控制器部分的参数
     * @param string $suffix     请求的后缀
     * @param string $param      请求的参数
     */
    protected function parsePattern($controller,$suffix,$param)
    {
    	$pattern='';
    	$query_string=$this->getRequestStr();
    	$query_string=trim($query_string,'/');

    	//匹配文件名与参数的定界符, 如果url请求的字符串中没有就清除掉。
    	if( !empty($suffix) && preg_match('/[^a-z0-9]$/i', $suffix,$de) )
    	{
           strpos($query_string, $suffix )===false && $suffix=str_replace( $de[0], '' , $suffix );
    	}

    	//组合自定义的 url请求格式
    	!empty($controller) && $pattern.="(.*?)";                //controller
    	!empty($suffix)     && $pattern.=preg_quote( $suffix );  //suffix
        !empty( $param )    && $pattern.="(.*?)";                //param

    	//检索url格式
    	if( !empty($pattern) && !empty($query_string) )
    	{
    		$pattern=sprintf("~^%s$~is",$pattern);
	    	$match=array();

			if( !preg_match( $pattern , $query_string, $match ) )
			{
                throw new Error(Lang::info(1101));
		    		
	    	}else
	    	{
				array_shift( $match );
				if( !empty($controller) && !empty( $param ) )
					list($match_controller,$match_param)=$match;
				else if( empty($controller) && !empty( $param ) )
					list($match_param)=$match;
				else if( !empty($controller) && empty( $param ) )
					list($match_controller)=$match;

				$_GET=array();
				if( !empty($match_controller) )
                {
                    $_GET=$this->parseParam( $controller ,$match_controller , '.' );
                    $this->setControllerAndMethod( $_GET );
                }

				if( !empty($match_param) )
                {
                    $_GET=array_merge($_GET,$this->parseParam( $param ,$match_param, '&'));
                }
				$match_controller = null;
				$match_param      = null;
			}
    	}

        $this->setControllerAndMethod( $_GET );

    }

    /**
     * 设置控制器或者方法名
     * @param array $arr
     * @param boolean $type 强制将数组中的 前两个元素弹出并作为控制器和方法名
     */
    protected function setControllerAndMethod( array &$param )
    {
        if( defined('METHOD') || defined('CONTROLLER') )
            return false;

        $c = strtolower( $this->getConfig('CONTROLLER_KEY','c') );
        $m = strtolower( $this->getConfig('METHOD_KEY'    ,'m') );

        $data=array(
            $c=>$this->isConfig('CONTROLLER') ? $this->getConfig('CONTROLLER') : 'index',
            $m=>$this->isConfig('METHOD')     ? $this->getConfig('METHOD')     : 'index',
        );
        foreach( array($c,$m) as $index => $key )
        {
            $index=isset( $param[ $key ] )  ? $key : $index;
            if( !empty($param[$index]) )
            {
                $data[ $key ]=$param[$index];
                unset( $param[$index] );
            }
        }
        define('CONTROLLER', $data[$c] );
        define('METHOD'    , $data[$m] );
        unset($data);
        return true;
    }

    /**
     * 解析请求的参数
     * @param string $pattern
     * @param string $param
     * @param string $tag
     * @param string $de
     * @return array
     */
    protected function parseParam( & $pattern, & $param , $de='&')
    {
        $assignment=$this->assignment( $pattern );

    	//拼接参数的定界符
    	$delimiter=$this->delimiter( $pattern );
    	$delimiter=( $delimiter===null ) ? $de : $delimiter ;

    	//获取匹配规则
    	$pattern=$this->filterPattern( $pattern );
    	
    	//限制可以匹配的次数
    	$limiter=$this->limiter( $pattern );
    	
    	//允许出现参数的次数  aa.bb.html |　aa/bb/cc/dd
    	if( !$this->checkLimit( $limiter, $delimiter , $param ) )
    	{
            throw new Error(Lang::info(1102));
    	}

    	//检查参数是否合法
    	if( !preg_match( sprintf('~^(%s%s*)+$~i',$pattern,preg_quote($delimiter) ) , $param) )
    	{
            throw new Error( Lang::info(1103) );
    	}

    	$param=trim($param,$delimiter);
    	$arr=array();

        if( strpos($param,'%')!==false )
            $param=urldecode($param);

        //键值数组
    	if( !empty($assignment) )
    	{
            if( $assignment != '=' || $delimiter != '&' )
            {
                $param=str_replace( $assignment, $delimiter , $param );
                $this->toNormUrlFormat($param, $delimiter );
            }

    		parse_str( $param, $arr );
            return array_change_key_case($arr);
    	}
        //索引数组
        else if( !empty($param) )
    	{
    		$arr=explode($delimiter, $param);
    	}
    	return $arr;
    }
    
    /**
     * 将任意格式转换成标准的 url 请求格式  index.html?a=c&b=d
     * @param string $str 引用的str
     * @param string $delimiter 定界符 标准格式是 & 路径模式下是 /
     * @return void
     */
    private function toNormUrlFormat( &$str, $delimiter )
    {
        $str=trim($str,$delimiter);
        if( strpos($str,$delimiter)!==false )
        {
            $start=0;
            $str=preg_replace_callback('~('.preg_quote( $delimiter ).')~',function($replace) use(&$start)
            {
                $start++;
                return ( $start % 2==1 ) ? '=' : '&' ;

            }, $str );
        }
    }
    
    /**
     * @param string $str
     * @param string $delimiter
     * @return boolean
     */
    private function  checkLimit( $limiter, $delimiter ,$str )
    {
        
    	 if( $limiter ===null || empty($delimiter) )
    	 	return true;
    	 
    	 $c=substr_count( $str, $delimiter );
    	 
    	 if( ( $limiter['min'] > 0 && $limiter['min'] < $c ) ||  $c > $limiter['max'] )
    	 {
    	 	return false;
    	 }
    	 
    	 return true;
    	
    }
    
    /**
     * @private
     */
    private function getUrlMode()
    {
        $mode=$this->isConfig('URL_MODE') ? $this->getConfig('URL_MODE') : self::AUTO_MODE ;
        //自动解析模式
        if( $mode==self::AUTO_MODE  )
        {
            if( $this->isRewrite() )
                $mode=self::REWRITE_MODE;
            else if( isset($_SERVER['PATH_INFO']) )
                $mode=self::PATH_MODE;
            else
                $mode=self::NORMAL_MODE;
            $this->setConfig('URL_MODE',$mode);
        }
        return $mode;
    }

    /**
     * 解析URI
     */
    protected  function parseUri()
    {
    	$mode= $this->getUrlMode();
        switch( $mode )
        {
            case self::PATH_MODE :
            case self::REWRITE_MODE :
                  $this->URL_SCRIPTNAME = $this->isConfig('URL_SCRIPTNAME') ? $this->getConfig('URL_SCRIPTNAME') : 'filter(0)delimiter.';
                  $this->URL_SUFFIX     = $this->isConfig('URL_SUFFIX')     ? $this->getConfig('URL_SUFFIX')     : '.php?';
                  $this->URL_PARAM      = $this->isConfig('URL_PARAM')      ? $this->getConfig('URL_PARAM')      : 'filter(0)delimiter/';
                break;
            default  :
        }

        if( CLI )
        {
            $argv=$_SERVER['argv'];
            array_shift( $argv );
            if( !empty($argv) )
            {
                $de=$mode==self::NORMAL_MODE ? '&' : '/';
                $_SERVER['REQUEST_URI']=implode($argv,$de);
            }
            $this->URL_SCRIPTNAME='';
            $this->URL_SUFFIX='';
        }
    	$this->parsePattern( $this->URL_SCRIPTNAME , $this->URL_SUFFIX , $this->URL_PARAM );
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





