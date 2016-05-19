<?php

namespace breeze\core;
use breeze\database\DBManager;
use breeze\interfaces\IStructure;

class Model
{
    /**
     * @var \breeze\database\Database
     */
    protected $db;

    /**
     * @var Application
     */
    protected $app=null;

    /**
     * @var string
     */
    protected $table='';

    /**
     * constructs.
     * @param string $group
     */
    final public function __construct( $group='default' )
	{
        $this->app = Application::getInstance();
        $this->db=DBManager::database( $group );

        if( empty($this->table) && preg_match('/\w+$/', get_called_class(), $match ) )
        {
            $this->table = strtolower($match[0]);
        }

        if( !empty($this->table) )
        {
            $this->db->table( $this->table );
        }
        $this->initialize();
	}

    /**
     * 获取设置此表的结构
     * @param string table
     * @param IStructure $struct=null
     * @return IStructure
     */
    protected function structure( IStructure $struct=null )
    {
       return $this->db->structure( $this->table , $struct );
    }

    /**
     * 获取程序的配置信息
     * @param null $key
     * @param null $value
     * @param null $default
     * @return Mixed
     */
    final protected function config($key=null, $value=null, $default=null)
    {
       return $this->app->config($key,$value,$default);
    }

    /**
     * 初始化此控制器辅助功能<br/>
     * 通常可以在子类中覆盖此方法来实现一些辅助功能。
     * @param Event $event
     */
     protected function initialize(){}
}

?>