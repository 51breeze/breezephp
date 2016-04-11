<?php

namespace breeze\database;

use breeze\core\Error;
use breeze\core\Lang;
use breeze\core\Singleton;
use breeze\utils\Utils;

abstract class DatabaseManager
{


  private static $databases=array();

    /**
     * @param $device 设备名称
     * @return \breeze\database\Database
     * @throws Error
     */
  final static public function database( Parameter $param=null )
  {
        if( $param===null )
        {
            $config=& DatabaseManager::config();
            $param=new Parameter();
            $param->host=$config['host'];
            $param->user=$config['user'];
            $param->password=$config['password'];
            $param->database=$config['database'];
            $param->type=$config['type'];
            $param->port=isset($config['port']) ? $config['port'] : 3306;
        }

        if( empty( $param->type ) )
          throw new Error(Lang::info(1008));

        $param->type=ucfirst($param->type);
        $activated=$param->host.':'.$param->port.':'.$param->type;

       if( !isset(self::$databases[$activated]) )
       {
           $drive='\breeze\database\\'.$param->type;
            try{
                self::$databases[$activated]=new $drive( $param );
            } catch (Error $e)
            {
                throw new Error(Lang::info(1009));
            }
       }
       return self::$databases[$activated];
    }

    /**
     * 获取配置文件
     * @return mixed
     */
   static private function & config()
   {
        static $config=null;
        $config===null && $config=& Singleton::getInstance('\breeze\core\Application')->getConfig('database');
        return $config;
   }

} 