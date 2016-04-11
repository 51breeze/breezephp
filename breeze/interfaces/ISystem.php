<?php
namespace breeze\interfaces;

interface ISystem
{
    
    /**
     * 判断当前是否使用了rewrite 重定向
     * @return boolean
     */
    public function isRewrite();
    
    /**
     * 判断配置文件中的变量是否存在
     * @param String key  配置项中的键名
     * @return boolean
     */
    public function isConfig( $key );
  
    /**
     * 获取配置文件中的变量
     * @param String key  配置项中的键名
     * @return Mixed
     */
    public function getConfig( $key  );
    
    /**
     * 设置配置文件中的变量
     * @param String key  配置项中的键名
     * @param Mixed value 指定的值
     */
    public function setConfig( $key , $value );
    
    /**
     * 给字符串添加转义符
     * @param string $str
     * @return string
     */
    public function addslashes( $str );
    
    /**
     * 获取本次请求的用户
     * @return string
     */
    public function getAgent();
    
    /**
     * 判断是否为 ajax 请求
     * @return 	boolean
     */
    public function isAjax();

    /**
     * 获取本次的请求头信息
     * @param  $key 指定获取请求头的键名,如果不指定则获取全部的请求头信息
     * @return 	mixed
     */
    public function getHeader( $key=null );

    /**
     * 检查 IP地址是否正确
     * @param	$str string 指定的ip
     * @param    $which string ip 的类型
     * @return	boolean
     */
    public function isIPAddress( $ip, $which ='');
    
    
    /**
     * 获取客户端的IP地址
     * @return	string
     */
    public function getIPAddress();
    
    /**
     * 设置 cookie
     * @param	mixed $name
     * @param	value string
     * @param	expire string
     * @param	domain string
     * @param	path string
     * @param	prefix string
     * @param	secure bool
     * @return	void
     */
    public function setCookie($name = '', $value = '', $expire = '', $domain = '', $path = '/', $prefix = '', $secure = false);
    
    /**
     * 获取 server 数组中的数据。
     * @param	$key string 指定位于数组中的键名，可以是数字或者字符串。
     * @param	$default mixed 当值为空的时候返回的值
     * @return	string
     */
    public function server( $key ,$default=null);
    
    
   /**
	* 获取 COOKIE 数组中的数据。
	* @param	$key string 指定位于数组中的键名，可以是数字或者字符串。
	* @param	$default mixed 当值为空的时候返回的值
	* @return	string
	*/
    public function cookie( $key, $default=null);

    /**
     * 获取 SESSION 数组中的数据。
     * @param	$key string 指定位于数组中的键名，可以是数字或者字符串。
     * @param	$default mixed 当值为空的时候返回的值
     * @return	string
     */
    public function session( $key, $default=null);
    
    /**
     * 获取 POST 数组中的数据。
     * @param	$key string 指定位于数组中的键名，可以是数字或者字符串。
     * @param	$default mixed 当值为空的时候返回的值
     * @return	string
     */
    public function post( $key ,$default=null);
    
    /**
     * 获取 GET 数组中的数据。
     * @param	$key string 指定位于数组中的键名，可以是数字或者字符串。
     * @param	$default mixed 当值为空的时候返回的值
     * @return	string
     */
    public function get($key,$default=null);
    
    
    /**
     * 获取当前系统使用的字符编码
     * @return  string
     */
    public function getCharset();
    
}

?>