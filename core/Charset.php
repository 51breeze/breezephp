<?php  

namespace breeze\core;
use breeze\utils\Utils;

abstract class Charset extends Security
{
    /**
     *UTF8 字符编码
     */
    const UTF8   ='UTF-8';
    
    /**
     *GB2312 字符编码
     */
    const GB2312 ='GB2312';
    
    /**
     *GBK 字符编码
     */
    const GBK    ='GBK';
    
    /**
     *BIG5 字符编码
     */
    const BIG5   ='BIG5';
    
    /**
     * @priavte
     * 当前使用的字符编码
     */
    private $currentCharset=self::UTF8;

    /**
     * @private
     */
    protected  function initialize()
    {
        parent::initialize();

        $charset=$this->getConfig('CHARSET');
        $this->inCharset( $charset ) && $this->currentCharset=$charset;

        //如果是CGI模式则设置响应头编码
        CLI || header( 'content-type:text/html;charset='. $this->currentCharset );

        //判断系统是否支持多字节处理
        define('MULTIBYTE_CHARSET',  ( preg_match('/./u', 'é' ) === 1 AND function_exists('iconv') AND ini_get('mbstring.func_overload') != 1 ) );
        !MULTIBYTE_CHARSET && trigger_error( Lang::info(1305) );

        //判断是否加载了mbstring库
        extension_loaded('mbstring') && ( mb_internal_encoding( $this->currentCharset ) || trigger_error( Lang::info(1304) ) );
    }

	/**
	 * 是否为一个可识别的字符编码
	 * @param string $str
	 * @return  string  如果是指定的字符编码则返回，否则返回 null 
	 */
	public function inCharset( $str )
	{
	    static $charsets=array( self::UTF8, self::GB2312, self::GBK , self::BIG5);
	    $str=strtoupper( $str );
		return in_array( $str, $charsets , true ) ? $str : null ;
	}
	
	/**
	 * 获取当前系统使用的字符编码
	 * @return  string
	 */
	public function getCharset()
	{
	   return $this->currentCharset;
	}

	/**
	 * 将字符串转换成指定的字符编码。<br/>
   * 如果不设置指定的编码则使用当前系统中定义的字符编码
   * @param	$to string 指定字符编码
	 * @param	$from string 当前字符的编码。如是为 null 则会自动获取当前 $str 的编码。
	 * @return	string
	 */
	public function convert( $str , $to=null, $from=null )
	{
	    
	    if( !is_string($str) || $this->isAscii( $str ) )
	       return $str; 

	    $to ===null && $to=$this->currentCharset;

		if ( function_exists('mb_convert_encoding') )
		{
			$str = @mb_convert_encoding($str, $to );
		}
		elseif ( function_exists('iconv') )
		{
		    if( $from === null )
		    {
		        if( function_exists('mb_detect_encoding') )
		            $from=mb_detect_encoding( $str , array('ASCII','GB2312','GBK','UTF-8') );
		        else
                    trigger_error(Lang::info(1303));
		    }
		    !empty( $from ) && $str = @iconv( $from , $to.'//IGNORE', $str);
		}
		else
		{
            trigger_error(Lang::info(1302));
		}
		return $str;
	}
	
	/**
	 * 判断字符串是否是 ASCII 字符集
	 * @param	string
	 * @return	boolean
	 */
	public function isAscii( $str )
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
    public function msubstr( $str, $start=0, $length=null, $charset=self::UTF8 ,$suffix='')
    {
        $charset=$this->inCharset( $charset );
        if( !$charset )
        {
            trigger_error(Lang::info(1301));
            return $str;
        }

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

}
