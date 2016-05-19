<?php

namespace breeze\database;
use breeze\core\Application;
use breeze\core\Error;
use breeze\core\Lang;

abstract class DBManager
{

  private static $databases=array();

    /**
     * @param string $group 连接分组名
     * @return \breeze\database\Database
     * @throws Error
     */
  final static public function database($group='default', Parameter $param=null )
  {
      $group=  strtolower( $group );
      if( $param ===null )
      {
          $config= Application::getInstance()->config( 'database' );
          if( !isset($config[$group]) && $group !== 'default' )
          {
              throw new Error('invalid group',2000);
          }

          $config = isset($config[$group]) ? $config[$group] : $config;
          if( empty($config) || !isset($config['type']) )
          {
              throw new Error('invalid type for '.$config['type'] ,2000);
          }

          $param=new Parameter();
          $param->host=$config['host'];
          $param->user=$config['user'];
          $param->password=$config['password'];
          $param->database=$config['database'];
          $param->port=isset($config['port']) ? $config['port'] : 3306;
          $param->type=ucfirst( $config['type'] );
      }

       $activated=$group.':'.$param->host.':'.$param->port.':'.$param->type;
       if( !isset(self::$databases[$activated]) )
       {
            $drive='\breeze\database\\'.$param->type;
            try{
                self::$databases[ $activated ]=new $drive( $param );
            } catch (\Exception $e)
            {
                throw new Error('failed new database instance',2000);
            }
       }
       return self::$databases[ $activated ];
  }

} 