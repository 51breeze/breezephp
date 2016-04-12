<?php

namespace breeze\database;

use breeze\core\Error;
use breeze\core\Lang;
use breeze\interfaces\IActive;


/**
 * Active 是一个抽像类，是适配器的基类,Database 的子类。
 * 所有的数据库驱动必须继承此类。
 * @author Administrator
 */
abstract class Active extends Record implements IActive
{
    /**
     * @see \breeze\interfaces\IActive::table()
     */
    public function table( $table )
    {
        if( !empty($table) )
        {
            if( is_string( $table ) )
            {
                $table=strpos($table,',')!==false ? explode(',',$table) : (array)$table;
            }
            $this->setSql('table', $table );
        }
        return $this;
    }
 
   /**
    * @see \breeze\interfaces\IActive::columns()
    */
    public function columns( $column )
    { 
        if( !empty($column) )
        {
           if( is_string( $column ) )
           {
               $column=strpos($column,',')!==false ? explode(',',$column) : (array)$column;
           }
           $this->setSql('columns', $column );
        }
        return $this;
    }

    /**
     * @see \breeze\interfaces\IActive::procedure()
     */
    public function procedure($name,$param)
    {
        $name= $name.'('.$param.')';
        $this->setSql('procedure',$name,null,false);
        return $this;
    }
    
    /**
     * @see \breeze\interfaces\IActive::lock()
     */
    public function lock()
    {
        $this->transaction();
        $this->setSql('lock','for update',null,false);
        return $this;
    }

    /**
     * @see \breeze\interfaces\IActive::union()
     */
    public function union( $union=null )
    {
        if( empty($union) )
        {
            $this->index++;
            $this->isUnion=true;
        }else if( is_string($union) )
        {
            $this->isUnion=false;
            $this->setSql('union',array($union) );
        }
        return $this;
    }
    
    /**
     * @see \breeze\interfaces\IActive::endUnion()
     */
    public function endUnion()
    {
        $this->isUnion=false;
        return $this;
    }
    
    /**
     * @see \com\interfaces\IActive::distinct()
     */
    public function distinct()
    {
        $this->setSql('distinct','distinct',null,false);
        return $this;
    }
    
    /**
     * @see \breeze\interfaces\IActive::limit()
     */
    public function limit($count=1000,$offset=0)
    { 
       $this->setSql('limit','limit '.$offset.','.$count,null,false);
       return $this;
    }

    /**
     * @see \breeze\interfaces\IActive::where()
     */
    public function where( $column ,$value=null , $strainer=Strainer::EQUAL , $logical='AND' )
    {
        if( is_bool($column) || empty($column) )
            throw new Error(Lang::info(2011));
        $whereis=& $this->getSql('where');
        if( empty($whereis) )
        {
            $whereis= new Whereis( $this );
            $this->setSql('where',$whereis,null,false,false );
        }
        $whereis->item($column,$value,$strainer,$logical);
        return $whereis;
    }

    /**
     * @see \breeze\interfaces\IActive::order()
     */
    public function order( $column, $type='ASC' )
    {
        if( !empty($column) )
        {
            if( is_array($column) )
            {
                foreach ( $column as $key => $val )
                    $this->order( is_numeric($key) ? $val : $key , $type );

            }else if( is_scalar($column) )
            {
                $column=$this->orderOrGroup($column,$type);
                $this->setSql('order', $column,',');
            }
        }
        return $this;
    }

    /**
     * @see \breeze\interfaces\IActive::group()
     */
    public function group( $column, $order='ASC', $rollup=false )
    {
        if( !empty($column) )
        {
            if( is_array($column) )
            {
               foreach ( $column as $key => $val )
               {
              	  $this->group( is_numeric($key) ? trim($val) : $key , $order ,$rollup );
               }
               
            }else if( is_scalar($column) )
            {
                $column=$this->orderOrGroup($column,$order);
                $this->setSql('group', $column,',');
                if( $rollup===true )
                    $this->setSql('rollup','WITH ROLLUP',null,false);
            }
        }
        return $this;
    }
    
    /**
     * @see \breeze\interfaces\IActive::having()
     */
    public function having( $condition , $expre='AND' )
    {
        if( !empty( $condition ) )
        {
            $this->setSql('having',$condition,$this->logical( $expre ));
        }
        return $this;
    }
    
    /**
     * @see \breeze\interfaces\IActive::join()
     */
    public function join( $table, $correlation , $type='left' )
    {
        if( empty($table) || empty($correlation) || !is_string($table) || !is_string( $correlation ) )
            throw new Error( Lang::info(3001) );

        if( !empty($this->quotation) )
        {
          $correlation=str_replace($this->quotation,'',$correlation);
          $correlation=preg_replace('/(\w+)\.(\w+)\s*=\s*(\w+)\.(\w+)/i','`\\1`.`\\2`=`\\3`.`\\4`',$correlation);
        }
        $table=$this->getTable($table);
        $join=$type.' join '.$table.' on '.$correlation;
        $this->setSql('join', $join ,' ');
        return $this;
    }

    /**
     * @see \breeze\interfaces\IActive::cache()
     */
    public function cache( $enable=true )
    {
        $this->setSql('cache', $enable===true ? 'SQL_CACHE' : 'SQL_NO_CACHE',null,false);
        return $this;
    }
    
    /**
     * @see \breeze\interfaces\IActive::quick()
     */
    public function quick()
    {
        $this->setSql('quick', 'quick',null,false);
        return $this;
    }
    
    /**
     * @see \breeze\interfaces\IActive::delay()
     * LOW_PRIORITY | DELAYED | HIGH_PRIORITY
     */
    public function delay( $type='HIGH_PRIORITY' )
    {
        $this->setSql('delay', $type,null,false );
        return $this;
    }
    
    /**
     * @see \breeze\interfaces\IActive::ignore()
     */
    public function ignore()
    {
        $this->setSql('ignore', 'ignore',null,false);
        return $this;
    }
    
   /**
    * @see \breeze\interfaces\IActive::using()
    */
    public function using( $table )
    {
        if( is_string($table) && strpos($table,',')!==false )
            $table=explode(',',$table);

        $table= $this->getTable( $table );
        $this->setSql('using', (array)$table );
        return $this;
    }

    /**
     * @private
     */
    protected  function getOrderType( $str='' )
    {
        return !empty($str) && strcasecmp($str,'DESC')===0  ? 'DESC' : 'ASC';
    }

    /**
     * @private
     */
    private function orderOrGroup( $column, $order='ASC' )
    {
        $order=$this->getOrderType( $order );
        $column=trim($column);
        if( strpos( $column,' ')!==false )
        {
            $column=preg_replace('/^(.*?)\s+(.*?)$/e', '$this->quotation(\\1) \\2', $column);

        }else if( !empty($order) )
        {
            $column=$this->backQuote( $column );
            $column.=' '.$order;
        }
        return $column;
    }
}

?>