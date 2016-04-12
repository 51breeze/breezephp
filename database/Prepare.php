<?php

namespace breeze\database;

use breeze\interfaces\IAdapter;
use breeze\interfaces\IPrepare;

class Prepare implements IPrepare
{
    /**
     * @private
     * 操作数据库的适配器对象
     */
    private $adapter;
    
    /**
     * @private
     * 代表此预处理对象的名称
     */
    private $name;
    
    /**
     * @private
     * sql 语名队列表
     */
    private $sqls;
    
    /*
     * @private
     */
    private $stmt;
    
    /**
     * 预处理计数器
     */
    static $count=0;
    
    /**
     * Constructs.
     */
    public function __construct( IAdapter $adapter )
    {
        $this->adapter=$adapter;
        self::$count++;
        $this->name='stmt'.self::$count;
    }
    
    /*
    * 执行一条预处理语句
    * @param string $sql 要执行的 sql 语句
    * @retrun Prepare 
    */
    public function prepare( $sql='' )
    {
       if( $this->adapter && !empty($sql) )
       {
           $this->sqls='PREPARE '. $this->name .'FROM '.$sql;
           $this->adapter->execute( $sql );
       }
       
       return $this;
    }

    /**
     * @private
     */
    private $params=array();
	
    /*
    * 绑定给预处理的参数
    * @param $key 参数名
    * @param $value 需要绑定的值
    */
    public function bindParam( $key ,$value )
    {
         $this->params[ '@'.$key ]=$value;
         $sql=sprintf("set @%s='%s'",$key,$value);
         $this->adapter->execute( $sql );
    }

    /**
     * @private
     */
    private $variableResult=array();

    /*
    * 绑定预处理的结果到 php 变量。
    * @param $key 参数名
    * @param $value 需要绑定的值
    */
    public function bindResult()
    {
       // extract( func_get_args(),EXTR_OVERWRITE );
    }

    /**
     * 执行一条已经准备好的 sql 语句
     * @param string $sql
     * @return resource
     */
    public function execute()
    {
       if( !empty($this->params) )
       {
          $sql=sprintf("EXECUTE %s USING %s",$this->name,implode(',', array_keys($this->params)));
          return $this->adapter->query($sql);
       }
       return false;
    }
    
}
?>