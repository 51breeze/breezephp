<?php
namespace breeze\database;

use breeze\core\Lang;
use breeze\interfaces\IPrepare;
use breeze\core\Error;
use breeze\interfaces\IAdapter;
use breeze\interfaces\IStructure;
use breeze\events\DatabaseEvent;


/**
 * Mysql 数据库驱动，实现了适配器的接口。
 * @author Administrator
 */
class Mysql extends Active implements IAdapter
{

    /**
     * @protected
     * 已连接的一个数据对象
     */
    protected $link=null;

    /**
     * @private
     * 预处理器的对象
     */
    protected $prepare;


    /**
     * @private
     * 执行sql语句后的结果集
     */
    protected $result;

    /**
     * 开启添加反单引号来解析字段或者表名
     */
    public $quoteEnable=true;


    /**
     * @see \breeze\interfaces\IAdapter::connet()
     * @return Database
     */
    public function connet()
    {
    	if( !is_resource( $this->link ) )
    	{  
    	    $host=$this->param->host;

    	    if ( !empty( $this->param->port ) &&  is_numeric( $this->param->port ) && preg_match('/:\d+$/is' , $host ) === 0 )
        	    $host.= ':'.$this->param->port;

        	if( $this->param->keep )
        	{
        	    $this->link=@mysql_pconnect($host, $this->param->user, $this->param->password,  $this->param->client);
        	    
        	}else
        	{
                $this->link=@mysql_connect($host, $this->param->user, $this->param->password, $this->param->flag, $this->param->client );
        	}
        	
        	if( !$this->link )
        	{
        	    throw new Error(  Lang::info(1002) );
        	}
        	if( !empty( $this->param->database ) )
        	{
        	   mysql_select_db( $this->param->database, $this->link );
        	}
        	
            $this->execute( "SET NAMES '".$this->param->charset."'" );
    	}
        return $this;
    }

    /**
     * @see \breeze\interfaces\IAdapter::close()
     * @return bool
     */
    public function close()
    {
        return mysql_close( $this->link );
    }
    
    /**
     * @see \breeze\interfaces\IAdapter::transaction()
     * @return Database
     */
    public function transaction()
    {
         $this->execute( 'begin' );
         return $this;
    }
    
    /**
     * @see \breeze\interfaces\IAdapter::commit()
     * @return Database
     */
    public function commit()
    {
        $this->execute( 'commit' );
        return $this;
    }
    
    /**
     * @see \breeze\interfaces\IAdapter::rollback()
     * @return Database
     */
    public function rollback()
    {
        $this->execute( 'rollback' );
        return $this;
    }
    
    /**
     * @see \breeze\interfaces\IAdapter::getErrorCode()
     * @return number
     */
    public function getErrorCode()
    {
       return mysql_errno( $this->link );
    }
    
    /**
     * @see \breeze\interfaces\IAdapter::getErrorInfo()
     * @return string
     */
    public function getErrorInfo()
    {
        return mysql_error( $this->link );
    }
    
    /**
     * @see \breeze\interfaces\IAdapter::getLastInsertId()
     * @return number
     */
    public function getLastInsertId()
    {
       return mysql_insert_id( $this->link );
    }
    
    /**
     * @see \breeze\interfaces\IAdapter::prepare()
     */
    public function prepare( $sql )
    {
        if( !( $this->prepare instanceof IPrepare ) )
            $this->prepare=new Prepare( $this );
       return  $this->prepare->prepare( $sql );
    }
    
    /**
     * @see \breeze\interfaces\IAdapter::query()
     * @return Database
     */
    public function query( $sql='' )
    {
        $this->lastQuery=$sql;
        $this->result = $this->execute( $sql );
        return $this;
    }
     
    /**
     * @see \breeze\interfaces\IAdapter::execute()
     * @return bool
     */
    public function execute( $sql='' )
    {
       $this->connet();
       if( $this->dispatchDatabaseEvent( DatabaseEvent::BEFORE, $sql ) )
       {
           $result = mysql_query( $sql , $this->link );
           $this->dispatchDatabaseEvent( DatabaseEvent::AFTER,$sql, $result );
           if( $result === false )
           {
               throw new Error('failed execute sql:'.$sql ,2000);
           }
           return $result;
       }
       return false;
    }

    /**
     * @private
     */
    private function dispatchDatabaseEvent( $type='', & $sql='' , & $resutl='' )
    {
        if( $this->hasEventListener( $type ) )
        {
            $event=new DatabaseEvent( $type );
            $event->sql=& $sql;
            $event->resutl=& $resutl;
            $this->dispatchEvent( $event );
            return !$event->prevented;
        }
        return true;
    }
    
    /**
     * @private
     */
    private $lastQuery='';
    
   /**
    * @see \breeze\interfaces\IAdapter::lastQuery()
    */
    public function lastQuery()
    {
        return $this->lastQuery;
    }
    
   /**
    * @see \breeze\interfaces\IAdapter::escape()
    */
    public function escape( $val='' )
    {
        $this->connet();
        if( is_string($val) )
        {
          return mysql_real_escape_string($val,$this->link);
          
        }else if( is_array( $val ) || is_object($val) )
        {
            foreach( $val as & $item )
            {
            	$item=$this->escape( $item );
            }
        }
        return $val;
    }

    /**
     * @see \breeze\interfaces\IAdapter::fetch()
     */
    public function fetch( $type=Database::FIELD, $single=false  )
    {
        if( is_bool($type) )
        {
            $single = $type;
            $type=Database::FIELD;
        }

        $data=null;
        if( is_resource( $this->result ) )
        {
            $fun='mysql_fetch_assoc';
            switch ( $type )
            {
            	case self::INDEX :
            	    $fun='mysql_fetch_row';
            	    break;
            	case self::OBJECT :
            	    $fun='mysql_fetch_object';
            	    break;
            }

            while ( $row=$fun( $this->result ) )
            {
                if( $single===true )
                {
                    $data=$row;
                    break;
                }
                $data[]=$row;
            }
            mysql_free_result( $this->result );
            $this->result=null;

        }else
        {
            throw new Error('没有可用的数据资源');
        }
        return $data;
    }

    /**
     * @var array
     */
    private $structure=array();

    /**
     * 获取指定表的结构
     * @param $table
     * @return IStructure|$this
     * @throws \breeze\core\Error
     */
    public function structure( $table , IStructure $struct=null )
    {
         if( !is_string($table) )
         {
             throw new Error('invalid table for '.$table);
         }

         if( $struct !== null )
         {
             $this->structure[$table] = $struct;
             return $this;
         }

         if( !isset( $this->structure[$table] ) )
         {
             $this->structure[$table] = new MysqlStructure( $this, $table );
         }
         return $this->structure[$table];
    }
}

?>