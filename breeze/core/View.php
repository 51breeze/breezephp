<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-10-18
 * Time: 下午12:55
 */

namespace breeze\core;

use breeze\events\RenderEvent;
use breeze\utils\Utils;
use breeze\interfaces\IRender;

class View extends EventDispatcher
{
    /**
     * 默认模板渲染器
     * @var \breeze\library\Render
     */
    protected $render=null;

    /**
     * @var Application
     */
    protected $app = null;

    /**
     * constructs.
     */
    public function __construct()
    {
       $this->app = Application::getInstance();
    }

    /**
     * @return IRender
     */
    final public function render()
    {
        static $render=null;
        if( $render ===null )
        {
            $options=array(
                'template_path'=>__VIEW__,
                'compile_path' =>APP_PATH.DS.'compile',
                'cache_path'   =>Utils::mkdir('cache'.DS.'view', APP_PATH ),
                'debug'        =>DEBUG,
                'cacheEnable'  =>DEBUG,
                'template_suffix'  =>'.html',
                'renderClass'       =>'\breeze\library\Render',
            );
            $options =  array_merge($options, $this->config('view',null,array() ) );

            if( $this->render === null )
            {
              $this->render=$options['renderClass'];
            }
            unset( $options['render'] );
            $ref = new \ReflectionClass( $this->render );
            if( !$ref->implementsInterface('\breeze\interfaces\IRender') )
            {
                throw new Error('invalid view render');
            }
            $render = $ref->newInstance( $options );
            $csrf_method = $this->config('CSRF_VALIDATE_METHOD');
            if( $render instanceof EventDispatcher && !empty($csrf_method) && stripos($csrf_method,'post')!==false )
            {
                $token = $this->app->hashToken();
                $this->assign('CSRF_VALUE',$token);
                $this->assign('CSRF_TOKEN_NAME', $this->config('CSRF_TOKEN_NAME') );
                $render->addEventListener(RenderEvent::COMPILE_DONE,function( RenderEvent $event )
                {
                    $input = "<input type='hidden' name='<?php echo \$CSRF_TOKEN_NAME; ?>' value='<?php echo \$CSRF_VALUE; ?>'  />";
                    $event->content = preg_replace('/(<form[^>]*>)/i',"\\1\r\n".$input, $event->content);
                });
            }
        }
        return $render;
    }

    /**
     * @see \breeze\interfaces\IRender::assign
     * @return $this
     */
    final protected function assign( $name='', $value=null )
    {
        $this->render()->assign($name,$value);
        return $this;
    }

    /**
     * @see \breeze\interfaces\IRender::dispaly
     * @return string
     */
    final protected function dispaly( $name='' )
    {
        echo $this->fetch( $name );
    }

    /**
     * @param string $name
     * @return string
     */
    final protected function fetch($name='')
    {
        $name = empty($name) ? $this->app->method : $name;
        return $this->render()->dispaly($name,DEBUG,DEBUG);
    }

    /**
     * @see \breeze\core\Application::config()
     */
    final protected function config($key=null, $value=null, $default=null)
    {
        return $this->app->config($key,$value,$default);
    }
}