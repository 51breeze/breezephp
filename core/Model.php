<?php

namespace breeze\core;
use breeze\database\DatabaseManager;
use breeze\interfaces\IModel;
use breeze\interfaces\ISingleton;

class Model implements IModel,ISingleton
{
    /**
     * @var \breeze\database\Database
     */
    protected $db;

	public function __construct()
	{
        $this->db=DatabaseManager::database();
        Singleton::register(get_called_class(),$this);
	}

    /**
     * @see \breeze\interfaces\ISingleton::getInstance()
     */
    public static function getInstance(array $param=array())
    {
        return Singleton::getInstance(get_called_class(),$param);
    }

    /**
     * @private
     */
    protected function config($name,$value=null)
    {
        $this->system()->config($name,$value);
    }

    /**
     * @private
     */
    protected  function isConfig($name)
    {
        return $this->system()->isConfig($name);
    }

    /**
     * 获取系统程序的实例
     * @return \breeze\interfaces\ISystem
     */
    protected function system()
    {
        return Singleton::getInstance('\breeze\core\Application');
    }

}

?>