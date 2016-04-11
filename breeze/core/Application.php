<?php 

namespace breeze\core;

/**
 * 应用程序类，所有的配置都由此类初始化
 */
class Application extends Router
{
   /**
    * @private
    * 一个标志是否已经初始化。
    */
   protected $initialized=false;

   /**
    * @see \breeze\core\System::initialize()
    */
   protected  function initialize()
   {
   	  parent::initialize();
   	  $this->initialized=true;
   }

   /**
   * 开始运行程序
   * @return void
   */
   public function start()
   {
       if( $this->initialized===false )
          $this->initialize();

       $this->dispatcher(CONTROLLER,METHOD);
   }

    /**
     * 应用程序是否已经被初始化。
     * @return boolean
     */
   public function initialized()
   {
      return $this->initialized;
   }

}