<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-10-18
 * Time: 下午12:55
 */

namespace breeze\core;

use breeze\interfaces\ISingleton;
use breeze\library\Render;
use breeze\utils\Utils;

class View implements ISingleton
{
    /**
     * @private
     */
    private $template_path='';

    /**
     * @private
     */
    private $template_suffix='.php';

    /**
     * @private
     */
    private $cache_path='';

    /**
     * @private
     */
    private $cache_suffix='.html';

    /**
     * @private
     */
    protected $cache_handle=null;

    /**
     * @private
     */
    private $cache_expire=3600;

    /**
     * @private
     */
    private $data=array();

    /**
     * 是否将模板写入缓存
     */
    private $cache=false;

    /**
     * 模板渲染器
     * @var \breeze\library\Render
     */
    private $render=null;

    /**
     * @return View
     * @see \breeze\interfaces\ISingleton::getInstance()
     */
    public static function getInstance(array $param=array())
    {
        return Singleton::getInstance(get_called_class(),$param);
    }

    public function __construct()
    {
       $this->template_path= __VIEW__;
       $this->cache_path=Utils::directory('cache'.DIRECTORY_SEPARATOR.'view', APP_PATH );

       if( $this->isConfig('render') )
       {
            $render=$this->config('render');
            if(is_callable( $render ) )
            {
               $this->render=call_user_func( $render );
            }
       }else
       {
           $this->render=Render::getInstance();
           $this->render->template_path=$this->template_path;
           $this->render->compile_path=APP_PATH.DIRECTORY_SEPARATOR.'compile';
           $this->render->cache_path=$this->cache_path;
           $this->render->debug=DEBUG;
           $this->render->cacheEnable=true;
       }

        Singleton::register(get_called_class(),$this);
    }

    /**
     * @return \breeze\library\Render
     */
    public function render()
    {
         return $this->render;
    }

    /**
     * @param $name
     * @param null $value
     * @return bool|Mixed|null
     */
    protected function config($name,$value=null)
    {
       return $this->system()->config($name,$value);
    }

    /**
     * @param $name
     * @return bool
     */
    protected function isConfig($name)
    {
        return $this->system()->isConfig($name);
    }

    /**
     * @return \breeze\core\Application
     */
    protected function system()
    {
        return Application::getInstance();
    }
}