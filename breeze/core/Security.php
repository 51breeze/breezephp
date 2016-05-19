<?php

namespace breeze\core;

abstract class Security extends Config
{
	/**
	 * @private
	 */
	protected $csrf_expire			= 3600;
	
	/**
	 * @private
	 */
	protected $token_name		= '__HASH__';
	
	/**
	 * @private
	 */
	protected $cookie_name	    = '__CSRF__';

	/**
	 * @private
	 */
	protected $hash			   = null;

    /**
     * @private
     * 是否启用跨脚本安全
     */
    protected $xss_enable		= true;

    /**
     * @private
     * 是否启用跨站请求伪造安全
     */
    protected $csrf_enable		= true;

	/**
	 * @private
	 */
	protected $neverAllowed = array(
		'document.cookie'	=> '[removed]',
		'document.write'	=> '[removed]',
		'.parentNode'		=> '[removed]',
		'.innerHTML'		=> '[removed]',
		'window.location'	=> '[removed]',
		'-moz-binding'		=> '[removed]',
		'<!--'				=> '&lt;!--',
		'-->'				=> '--&gt;',
		'<![CDATA['			=> '&lt;![CDATA[',
		'<comment>'			=> '&lt;comment&gt;'
	);

	/**
	 * @private
	 */
	protected $neverAllowedRegex = array(
		'javascript\s*:',
		'expression\s*(\(|&\#40;)', // CSS and IE
		'vbscript\s*:', // IE, surprise!
		'Redirect\s+302',
		'(["\'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?'
	);

    public function __construct()
    {
        parent::__construct();

        if( $this->config('xss_enable') !==null )
        {
            $this->xss_enable  = !!$this->config('xss_enable');
        }

        if( $this->config('csrf_enable') !==null )
        {
            $this->csrf_enable  = !!$this->config('csrf_enable');
        }

        if ( $this->csrf_enable !== false )
        {
            foreach ( array('csrf_expire', 'token_name', 'cookie_name' ) as $key )
            {
                $val = $this->config($key);
                if( $val !== null )
                {
                    $this->$key = $val;
                }
            }
            $this->verification();
        }

        //销毁 cookie 中的这些键的值
        if ( is_array($_COOKIE) )
        {
            unset( $_COOKIE['$Version'] );
            unset( $_COOKIE['$Path']    );
            unset( $_COOKIE['$Domain']  );
        }

        /*
        * 清除所有未知的全局变量
        */
        $clean=function($name)
        {
            static $protected = array('_SERVER', '_GET', '_POST', '_FILES','_SESSION', '_ENV', 'GLOBALS', 'HTTP_RAW_POST_DATA');
            if ( is_string($name) && !in_array($name, $protected) )
            {
                global $$name;
                unset($$name);
            }
        };

        foreach ( array($_GET, $_POST, $_COOKIE, $_FILES)  as $var )
        {
            if( is_array( $var ) )
            {
                foreach ($var as $key => $val)
                {
                    $clean($key);
                }
                $this->filter( $var );
            }
        }
        $_SERVER['PHP_SELF'] = strip_tags( $_SERVER['PHP_SELF'] );

    }

    /**
     * 获取哈希字符串。
     * @return string
     */
    public function getHash()
    {
        if ( $this->hash===null )
        {
            $this->hash = md5( uniqid(rand(), TRUE) );
        }
        return $this->hash;
    }

    /**
     * 给字符串添加转义符
     * @param string $str
     * @return string
     */
    public function addslashes( $str )
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
     * @private
     * 过滤外部输入的数据
     * @param  mixed	$data
     * @return mixed
     */
    public  function filter(  &$data )
    {
        if ( is_array( $data ) || is_object($data) )
        {
            foreach ( $data as $key => & $val )
            {
                $data[ $this->checkKey( $key ) ]=$this->filter( $val );
            }

        }else
        {
            $data = $this->xss_enable === true ? $this->clean( $data ) : $data;
            $data = $this->addslashes( $data );

            // 使用标准换行
            if( is_string( $data ) )
            {
                strpos( $data, "\r") !== false &&
                $data = str_replace(array("\r\n", "\r", "\r\n\n"), PHP_EOL, $data );
            }
        }
        return $data;
    }

    /**
     * 获取令牌名
     * @return 	string
     */
    public function tokenName()
    {
        return $this->token_name;
    }


	/**
	 * 防止跨站脚本功击
	 * @return	void
	 */
	protected function verification()
	{
		if ( isset($_SERVER['REQUEST_METHOD']) && strcasecmp( $_SERVER['REQUEST_METHOD'] ,'POST' ) ===0 )
		{
		    $csrf_value=$this->cookie( $this->cookie_name );
			if ( !isset( $_POST[ $this->token_name ] ) || empty($csrf_value) ||
			     $_POST[ $this->token_name ] != $csrf_value )
			{
                throw new Error('invalid request');
			}
			unset( $_POST[ $this->token_name ] );
            unset( $_REQUEST[ $this->token_name ] );
			$this->setCookie($this->cookie_name,null);
		}
        $this->setCookie( $this->cookie_name, $this->getHash() , $this->csrf_expire );
	}

	/**
	 * 清除一些不符合要求的数据
	 * @return	string
	 */
	protected function clean( & $str )
	{
	    if( !is_string($str) )
	        return $str;

		$str = $this->removeInvisibleCharacters($str);
		$str = $this->validateEntities($str);
		strpos($str,'%')!==false && $str = rawurldecode( $str );

		$str = preg_replace_callback('/\w+=([\'\"]).*?\\1/si', array($this, 'convertAttribute'), $str);
		$str = preg_replace_callback('/<\w+.*?(?=>|<|$)/si', array($this, 'decodeEntity'), $str);
		strpos($str, '\t') !== false && $str = str_replace('\t', ' ', $str);
		$str = $this->disallow($str);
		$str = str_replace(array('<?', '?>'),  array('&lt;?', '?&gt;'), $str);

		/*
		 * 合并以下分开的语句
		 */
		$words = array(
			'javascript', 'expression', 'vbscript', 'script', 'base64','applet', 'alert', 'document', 'write', 'cookie', 'window'
		);

		foreach ($words as $word)
		{
			$temp = '';
			for ($i = 0, $wordlen = strlen($word); $i < $wordlen; $i++)
			{
				$temp .= substr($word, $i, 1).'\s*';
			}
			$str = preg_replace_callback('#('.substr($temp, 0, -3).')(\W)#is', array($this, 'compactExplodedWords'), $str);
		}

		/*
		 * 清除不合法的标签
		 */
		do
		{
			$original = $str;

			if (preg_match('/<a/i', $str))
			{
				$str = preg_replace_callback('#<a\s+([^>]*?)(>|$)#si', array($this, 'jslinkRemoval'), $str);
			}

			if (preg_match('/<img/i', $str))
			{
				$str = preg_replace_callback('#<img\s+([^>]*?)(\s?/?>|$)#si', array($this, 'jsimgRemoval'), $str);
			}

			if (preg_match('/script/i', $str) OR preg_match('/xss/i', $str))
			{
				$str = preg_replace('#<(/*)(script|xss)(.*?)\>#si', '[removed]', $str);
			}
		}
		while($original != $str);
		unset($original);

		//清除不符合要求的属性
		$str = $this->removeEvilAttributes($str, false);

		/*
		 * 修正不符合要求的html标签
		 */
		$naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
		$str = preg_replace_callback('#<(/*\s*)('.$naughty.')([^><]*)([><]*)#is', array($this, 'sanitizeNaughtyHtml'), $str);

		/*
		 * 修正不符合要求的 script 语法
		 */
		$str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', '\\1\\2&#40;\\3&#41;', $str);
		$str = $this->disallow($str);

		return $str;
	}
	
	
	/**
	 * 移除隐藏的字符
	 * @param string $str
	 * @param string $urlEncoded
	 * @return string
	 */
	private function removeInvisibleCharacters($str, $urlEncoded = TRUE )
	{
		$displayables = array();
	
		if ( $urlEncoded )
		{
			$displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
			$displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
		}
	
		$displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127
	
		do
		{
			$str = preg_replace($displayables, '', $str, -1, $count);
			
		} while ( $count );
	
		return $str;
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
	 * 合拼被分开的语句
	 * @param	type
	 * @return	type
	 */
	protected function compactExplodedWords($matches)
	{
		return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
	}


	/*
	 * 清除不合法的属性
	 */
	protected function removeEvilAttributes($str, $is_image)
	{
		// All javascript event handlers (e.g. onload, onclick, onmouseover), style, and xmlns
		$evil_attributes = array('on\w*', 'style', 'xmlns', 'formaction');

		if ($is_image === TRUE)
		{
			/*
			 * Adobe Photoshop puts XML metadata into JFIF images, 
			 * including namespacing, so we have to allow this for images.
			 */
			unset($evil_attributes[array_search('xmlns', $evil_attributes)]);
		}

		do {
			$count = 0;
			$attribs = array();


            //匹配所有属性。如果属性值中含有与第2匹配符相等的引号("|')则后面的值不再匹配。
			preg_match_all('/('.implode('|', $evil_attributes).')\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is', $str, $matches, PREG_SET_ORDER);

			foreach ($matches as $attr)
			{
				$attribs[] = preg_quote($attr[0], '/');
			}

			// find occurrences of illegal attribute strings without quotes
			preg_match_all('/('.implode('|', $evil_attributes).')\s*=\s*([^\s>]*)/is', $str, $matches, PREG_SET_ORDER);

			foreach ($matches as $attr)
			{
				$attribs[] = preg_quote($attr[0], '/');
			}

			// replace illegal attribute strings that are inside an html tag
			if (count($attribs) > 0)
			{
				$str = preg_replace('/(<?)(\/?[^><]+?)([^A-Za-z<>\-])(.*?)('.implode('|', $attribs).')(.*?)([\s><]?)([><]*)/i', '$1$2 $4$6$7$8', $str, -1, $count);
			}

		} while ($count);

		return $str;
	}

	/**
	 * 修正不符合要求的html标签
	 * @param	array
	 * @return	string
	 */
	protected function sanitizeNaughtyHtml($matches)
	{
		$str = '&lt;'.$matches[1].$matches[2].$matches[3];
		$str .= str_replace(array('>', '<'), array('&gt;', '&lt;'),$matches[4]);
		return $str;
	}

	/**
	 * 清除引用 css 资源时出现的非法关键词
	 * @param	array
	 * @return	string
	 */
	protected function jslinkRemoval($match)
	{
		return str_replace(
			$match[1],
			preg_replace(
				'#href=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|data\s*:)#si',
				'',
				$this->filterAttributes(str_replace(array('<', '>'), '', $match[1]))
			),
			$match[0]
		);
	}

	/**
	 * 清除引用 js img 资源时出现的非法关键词
	 * @param	array
	 * @return	string
	 */
	protected function jsimgRemoval($match)
	{
		return str_replace(
			$match[1],
			preg_replace(
				'#src=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si',
				'',
				$this->filterAttributes(str_replace(array('<', '>'), '', $match[1]))
			),
			$match[0]
		);
	}

	/**
     * 转换属性中中的尖括号和反斜线
	 * @param	array
	 * @return	string
	 */
	protected function convertAttribute($match)
	{
		return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
	}

	/**
	 * 过滤 html 属性
	 */
	protected function filterAttributes($str)
	{
		$attr = '';
		if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches))
		{
			foreach ($matches[0] as $match)
			{
				$attr.= preg_replace('#/\*.*?\*/#s', '', $match);
			}
		}
		return $attr;
	}

	/**
	 * 解码html实体标签
	 * @param	array
	 * @return	string
	 */
	private function decodeEntity($match)
	{
		return $this->unhtmlentities( $match[0] ,  $this->getCharset() );
	}

	/**
     * @private
	 * @param 	string
	 * @return 	string
	 */
	private function validateEntities($str)
	{
        static $hash=null;
        $hash===null && $hash = md5( time() + mt_rand(0, 1999999999) );

		$str = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-]+)|i', $hash."\\1=\\2", $str);
		$str = preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', "\\1;\\2", $str);
		$str = preg_replace('#(&\#x?)([0-9A-F]+);?#i',"\\1\\2;",$str);
		$str = str_replace($hash, '&', $str);
		return $str;
	}


	/**
	 * 去掉或者替换不允许出现的字符
	 * @param 	string
	 * @return 	string
	 */
	private function disallow( $str )
	{
		$str = str_replace( array_keys($this->neverAllowed), $this->neverAllowed, $str);
		foreach( $this->neverAllowedRegex as $regex)
		{
			$str = preg_replace('#'.$regex.'#is', '[removed]', $str);
		}
		return $str;
	}

    /**
     * @private
     * 检查键名是符合法
     * @param	$key string
     * @return	boolean
     */
    private function checkKey( $key )
    {
        if( !preg_match('/^[a-z0-9:_\/-]+$/i', trim($key) ) )
            throw new Error('Invalid request');
        return $key;
    }

}
