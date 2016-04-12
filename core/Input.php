<?php 

namespace breeze\core;
use breeze\utils\Utils;

abstract class Input extends URI
{

	/**
	 * 客户端的IP地址
	 * @var string
	 */
	private  $ipAddress				= null;
	
	/**
	 * @private
	 * 使用浏览器的用户
	 */
	private $agent				= null;

	/**
	 * @private
	 * 请求头
	 */
	private  $headers			    = array();

	/**
	 * @protected
	 * @see \com\core\System::initialize()
	 */
	protected function initialize()
	{
        // 在POST请求下修正未获得的数据。
        if( empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strcasecmp( $_SERVER['REQUEST_METHOD'] ,'POST' )===0 )
        {
            if( !empty( $GLOBALS['HTTP_RAW_POST_DATA'] ) && is_array( $GLOBALS['HTTP_RAW_POST_DATA'] ) )
                $_POST=$GLOBALS['HTTP_RAW_POST_DATA'];
            else
                $_POST=file_get_contents('php://input','r') && $_POST=urldecode( $_POST );

            unset( $GLOBALS['HTTP_RAW_POST_DATA'] );
        }
        parent::initialize();
	}

     /**
     * @see \breeze\interfaces\ISystem::get()
     */
	public function get($key,$default=null)
	{
        $value= Utils::fetchArray( $_GET, $key );
	    return $value ? $value : $default;
	}

	 /**
     * @see \breeze\interfaces\ISystem::post()
     */
	public function post( $key = null,$default=null)
	{
        $value= Utils::fetchArray( $_POST, $key );
        return $value ? $value : $default;
	}

    /**
     * @see \breeze\interfaces\ISystem::cookie()
     */
	public function cookie( $key, $default=null)
	{
        $value= Utils::fetchArray( $_COOKIE, $key );
        return $value ? $value : $default;
	}

    /**
     * @see \breeze\interfaces\ISystem::session()
     */
    public function session( $key, $default=null)
    {
        $value= Utils::fetchArray( $_SESSION, $key );
        return $value ? $value : $default;
    }
	
	 /**
     * @see \breeze\interfaces\ISystem::server()
     */
	public function server( $key, $default=null)
	{
        $value= Utils::fetchArray( $_SERVER, $key );
        return $value ? $value : $default;
	}

    /**
     * @see \breeze\interfaces\ISystem::setCookie()
     */
	public function setCookie($name = '', $value = '', $expire = '', $domain = '', $path = '/', $prefix = '', $secure = false)
	{

		empty( $prefix ) && $this->isConfig('COOKIE_PREFIX') && $prefix=$this->getConfig('COOKIE_PREFIX');
		empty( $domain ) && $this->isConfig('COOKIE_DOMAIN') && $domain=$this->getConfig('COOKIE_DOMAIN');

		if ( $path == '/' && $this->isConfig('COOKIE_PATH') )
		{
			$path = $this->getConfig('COOKIE_PATH');
			$path=  empty( $path ) ? '/' : $path ;
		}
		
		if ( $secure == false && $this->isConfig('COOKIE_SECURE') )
		{
			$secure = $this->getConfig('COOKIE_SECURE');
			$secure=  empty( $secure ) ? false : is_bool( $secure ) ;
		}
		
		$expire = !is_numeric( $expire ) ? time() + 86500 :  time() + $expire ;
		
		( $value===null ) && $expire=time() - 86500 ;
		
		$https=$this->server('HTTPS');
		
		if( $secure && ( empty( $https ) OR strcasecmp( $https ,'off' )===0 ) )
		{
		    Utils::message('设置  Cookie 可能失败。原因是开启的 cookie secure 只有在 https 请求的方式下才能有效。 ', Utils::WARNING );
		}
		
		setcookie($prefix.$name, $value, $expire, $path, $domain, $secure);
	}

    /**
     * @see \breeze\interfaces\ISystem::getIPAddress()
     */
	public function getIPAddress()
	{
		if ( $this->ipAddress !== null )
		{
			return $this->ipAddress;
		}

		$proxy_ips = $this->getConfig('PROXY_IPS');
		
		if ( !empty($proxy_ips) )
		{
			$proxy_ips = explode(',', str_replace(' ', '', $proxy_ips) );
			
			foreach (array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP') as $header)
			{
			    
			    $spoof = $this->server( $header );
			    
				if ( $spoof !== null )
				{
					if (strpos($spoof, ',') !== FALSE)
					{
						$spoof = explode(',', $spoof, 2);
						$spoof = $spoof[0];
					}

					if ( !$this->isIPAddress( $spoof ) )
					{
						$spoof = false;
					}
					else
					{
						break;
					}
				}
			}

			$this->ipAddress = ( $spoof !== false && in_array( $_SERVER['REMOTE_ADDR'] , $proxy_ips, TRUE ) ) ? $spoof : $_SERVER['REMOTE_ADDR'] ;
		}
		else
		{
			$this->ipAddress = $_SERVER['REMOTE_ADDR'];
		}

		!$this->isIPAddress( $this->ipAddress ) && $this->ipAddress = '0.0.0.0';
		return $this->ipAddress;
	}

    /**
     * @see \breeze\interfaces\ISystem::isIPAddress()
     */
	public function isIPAddress( $ip, $which ='')
	{
		if( empty($which) )
		{
			if (strpos($ip, ':') !== FALSE)
			{
				$which='ipv6';
			}
			elseif (strpos($ip, '.') !== FALSE)
			{
				$which='ipv4';
			}
			
		}else
		{
		    $which = strtolower($which);
		}

		if ( is_callable('filter_var') )
		{
			$flag='';
			($which=='ipv4') && $flag=FILTER_FLAG_IPV4;
			($which=='ipv6') && $flag=FILTER_FLAG_IPV6;

			return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flag);
			
		}else if ($which == 'ipv6')
		{
			return $this->checkIpv6($ip);
			
		}else if ( $which !== 'ipv4')
		{
		    return $this->checkIpv4($ip);
		}
		
	    return false;
	}
	
    /**
     * @see \breeze\interfaces\ISystem::getHeader()
     */
	public function getHeader( $key=null )
	{
	    if ( empty( $this->headers ) )
	    {
	        if ( function_exists('apache_request_headers') )
	        {
	            $headers=call_user_func('apache_request_headers');
	        }
	        else
	        {
	            $headers['Content-Type'] = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : @getenv('CONTENT_TYPE') ;
	
	            foreach ($_SERVER as $key => $val)
	            {
	                if ( strncmp($key, 'HTTP_', 5 ) === 0 )
	                {
	                    $headers[ substr($key, 5) ] = $this->server( $key );
	                }
	            }
	        }
	
	        foreach ($headers as $key => $val)
	        {
	            $key = str_replace('_', ' ', strtolower($key) );
	            $key = str_replace(' ', '-', ucwords($key) );
	
	            $this->headers[$key] = $val;
	        }
	    }
	
	    if( $key===null )
	        return $this->headers;
	
	    if ( !isset( $this->headers[ $key ] ) )
	    {
	        return null;
	    }
	
	    return $this->headers[ $key ];
	}
	
    /**
     * @see \breeze\interfaces\ISystem::isAjaxRequest()
     */
	public function isAjax()
	{
	    return strcasecmp($this->server('HTTP_X_REQUESTED_WITH') , 'XMLHttpRequest')===0;
	}
	
	/**
     * @see \breeze\interfaces\ISystem::getUserAgent()
     */
	public function getAgent()
	{
	    if ($this->agent !== null )
	        return $this->agent;
	    $this->agent = !isset( $_SERVER['HTTP_USER_AGENT'] ) ? null : $_SERVER['HTTP_USER_AGENT'];
	    return $this->agent;
	}
	
	/**
	* 检查  IPv4  的地址是否正确 
	* @param	$str string 指定的ip
	* @return	boolean
	*/
	private function checkIpv4( $ip )
	{
		$ip_segments = explode('.', $ip);

		if ( empty($ip_segments) || count($ip_segments) !== 4 || $ip_segments[0][0] == '0')
		{
			return FALSE;
		}
		foreach ($ip_segments as $segment)
		{
			if ($segment == '' OR preg_match("/[^0-9]/", $segment) OR $segment > 255 OR strlen($segment) > 3)
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	* 检查  IPv6  的地址是否正确 
	* @param	$str string 指定的ip
	* @return	boolean
	*/
	private function checkIpv6( $str )
	{

		$groups = 8;
		$collapsed = false;

		$chunks = array_filter( preg_split('/(:{1,2})/', $str, NULL, PREG_SPLIT_DELIM_CAPTURE) );

		if (current($chunks) == ':' OR end($chunks) == ':')
		{
			return false;
		}

		// PHP supports IPv4-mapped IPv6 addresses, so we'll expect those as well
		if (strpos(end($chunks), '.') !== false)
		{
			$ipv4 = array_pop($chunks);

			if ( ! $this->checkIpv4($ipv4) )
			{
				return false;
			}

			$groups--;
		}

		while ( !empty($chunks) )
		{
			$seg = array_pop($chunks);
			
			if ($seg[0] == ':')
			{
				if (--$groups == 0)
				{
					return false;	// too many groups
				}

				if (strlen($seg) > 2)
				{
					return false;	// long separator
				}

				if ($seg == '::')
				{
					if ($collapsed)
					{
						return false;	// multiple collapsed
					}

					$collapsed = true;
				}
			}
			elseif (preg_match("/[^0-9a-f]/i", $seg) OR strlen($seg) > 4)
			{
				return false; // invalid segment
			}
		}

		return $collapsed OR $groups == 1;
	}

}
