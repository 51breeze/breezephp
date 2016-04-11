<?php

namespace breeze\database;
use breeze\core\Lang;
use breeze\events\DatabaseEvent;
use breeze\interfaces\IActive;
use breeze\interfaces\IRecord;
use breeze\core\Error;

/**
 * 查找命令
 * 此类主要配合 IActive::where() 使用，使用此类可创建更为复杂的查找命令。
 * 此类允许单独使用，
 */

class Whereis implements IRecord,IActive
{
    
    /**
     * @private
     */
    private $database;
    
    /**
     * @private
     */
    private $data=array();
    
    /**
     * @private
    */
    private $issub=false;
    
    /**
     * Contructs.
     */
    public function __construct( Database $database )
    {
    	$this->database=$database;
    }

    /**
     * 开始一个新的筛选器
     * @return \breeze\database\Whereis
     */
    public function begin($issub=true)
    {
    	$class=get_called_class();
    	$class=new $class( $this->database );
    	$class->issub=$issub;
    	array_push($this->data,$class);
    	return $class;
    }

    /**
     * 结束一个或者整个筛选器
     * @param boolean $topped=true 是结束当前节点还是整个筛选器。
     * @return \breeze\database\Whereis
     */
    public function end( $topped=true )
    {
        return $topped===true ? $this->database : $this ;
    }
    
    /**
     * 添加一个需要筛选的数据项
     * @param string $column
     * @param string $value
     * @param string $strainer
     * @param string $logical
     * @return \breeze\database\Whereis
     */
    public function item($column,$value=null,$strainer=Strainer::EQUAL,$logical='AND')
    {
        //是否需要分发事件
        if( $this->database->hasEventListener( DatabaseEvent::SQL_CHANGE ) )
        {
            $event=new DatabaseEvent( DatabaseEvent::SQL_CHANGE );
            $event->name='where';
            $event->column=& $column;
            $event->value= & $value;
            $event->strainer= & $strainer;
            $event->logical= & $logical;
            $this->database->dispatchEvent( $event );
            if( $event->prevented===true )
                return $this;
        }

        if( is_bool($column) || empty($column) )
            throw new Error(Lang::info(2012));
        array_push( $this->data, array($column,$value,$strainer,$logical) );
        return $this;
    }

    /**
     * 获取已经定义的列名
     * @param int $maxDepth 获取列名的深度
     */
    public function definedColumns( $maxDepth=1 , &$columns=array() )
    {
       if( empty($this->data) )
           return $columns;
       $val=array();
       $sub=array();
       foreach( $this->data as $item )
       {
           if( is_array($item) )
           {
               $val[]=$item[0];

           }else if( $maxDepth > 1 && $item instanceof Whereis )
           {
               $sub=$item->definedColumns( --$maxDepth );
           }
       }
       $columns=array_merge($columns,$val,$sub);
       return $columns;
    }
    
    /**
     * 将筛选器转换中字符串的表达形式
     * @param  boolean $logical=false 是否在返回字符串前面加上首个筛选条件的逻辑符。‘and’ 或者 ‘or’
     * @return string;
     */
    public function toString( $logical=false )
    {
    	$data='';
    	$first_logical='';
    	if( $logical === true )
    	{
    	   $first_logical=@$this->data[0][3];
    	   $first_logical=$this->database->logical( $first_logical ,'AND');
    	}
    	foreach( $this->data as $item )
    	{
    	     $val='';
    	     $logical='AND';
    		 if( $item instanceof Whereis )
    		 {
    		     $logical=@$item->data[0][3];
    		     $val=$item->toString();
    		 }
             else if( is_array($item) )
             {
                  list($column,$value,$strainer,$logical) = $item;
                  $val=$this->combine( $column,$value,$strainer,false );
             }
    		 $logical=$this->database->logical( $logical ,'AND');
    		 if( !empty($val) )
    		   $data.= empty($data)?  $val : $logical.$val ;
    	}
    	if( !empty($data) )
    	{
    	   return $this->issub===true ? sprintf( '%s(%s)', $first_logical,$data ) : $first_logical.$data ;
    	}
    	return '';
    }


    /**
     * 联合查询条件
     * 多个条件的格式为:
     * column=array(
     *      'column3=value3',
     *      array(
     *        'column1','value1','equal','or'
     *      ),
     *      array(
     *        'column2','value2','not_equal','and'
     *      )
     *   )
     * 结果为： (column3='value3' or column1='value1' and column2!='value2')
     * @return string
     */
    private function combine($column,$value=null,$strainer='EQUAL',$subEnable=true )
    {
        if( is_array($column) )
        {
            $sub=0;
            $data='';
            foreach ( $column as $field=>$val )
            {
                $s=$strainer;
                $l='AND';
                $temp='';
                if( !empty( $val ) )
                {
                    if( is_numeric($field) )
                    {
                        is_array($val) ? @list($field,$val,$s,$l)=$val : $temp=$val;
                    }else
                    {
                        @list($val,$s,$l)=(array)$val;
                    }
                }

                if( empty($temp) && !empty($field) )
                    $temp=$this->combine($field,$val,empty($s) ? Strainer::EQUAL : $s );

                if( !empty($temp) )
                {
                    $sub++;
                    $data.=empty($data) ? $temp :  $this->database->logical($l,'AND').$temp;
                }
            }
            return ($sub>1 && $subEnable===true) ? sprintf('(%s)',$data) : $data;
        }

        //确保 $column 已定义并且是一个标量
        if( empty($column) || !is_string($column) )
        {
            throw new Error( Lang::info(2010) );
        }

        //如果值为 null 则认为不需要再解析
        if( is_null($value) )
            return $column;

        $strainer= Strainer::get($strainer);
        $column=$this->database->backQuote($column);
        $value= $this->parseValue( $this->database->escape($value) , $strainer);

        //替换列名
        $strainer=( stripos($strainer,'field') !== false )  ? str_replace('field', $column, $strainer ) : $column.$strainer;
        //替换值
        $strainer=( stripos($strainer,'value') !== false )  ? str_replace('value', $value , $strainer ) : $strainer.$value;
        return $strainer;
    }

    /**
     * @private
     */
    private function parseValue( $value, $filtrate )
    {
        if( $this->database->isUsing($value) )
        {
            $value= $this->database->backQuote( $value );
            return $value;
        }
        if( is_array($value) )
        {
            $value=stripos($filtrate,'in')!==false ? implode("','", $value ) : implode('', $value ) ;
        }
        return sprintf('\'%s\'',$value);
    }

    //============================================================================
    // Record Interface
    //============================================================================
    
    /**
     * @see \breeze\interfaces\IRecord::get()
     */
    public function get($count=1000 ,$offset=0)
    {
        return $this->database->get($count ,$offset);
    }
    
    /**
    * @see \breeze\interfaces\IRecord::set()
    */
    public function set()
    {
        return $this->database->set();
    }
    
   /**
    * @see \breeze\interfaces\IRecord::append()
    */
    public function add( array $value )
    {
        return $this->database->add( $value );
    }

    /**
     * @see \breeze\interfaces\IRecord::remove()
     */
    public function remove()
    {
        return $this->database->remove();
    }
    
    /**
     * @see \breeze\interfaces\IRecord::copy()
     */
    public function copy($toTable='',$fromTable='')
    {
        return $this->database->copy( $toTable,$fromTable );
    }

    /**
     * @see \breeze\database\Database::getTable()
     */
    public function getTable( $table='' )
    {
        return $this->database->getTable( $table);
    }

    /**
     * @see \breeze\interfaces\IRecord::bind()
     */
    public function bind($column,$value='',array $update=null)
    {
        return $this->database->bind($column,$value,$update);
    }


    //============================================================================
    // Active Interface
    //============================================================================

    /**
     * @see \breeze\interfaces\IActive::table()
     */
    public function table( $table )
    {
       return $this->database->table($table);
    }

    /**
     * @see \breeze\interfaces\IActive::columns()
     */
    public function columns( $columns )
    {
       return $this->database->columns($columns);
    }

    /**
     * @see \breeze\interfaces\IActive::where()
     */
    public function where( $column ,$value=null ,$strainer='EQUAL', $type='AND' )
    {
        return $this->database->where($column,$value,$strainer,$type);
    }

    /**
     * @see \breeze\interfaces\IActive::order()
     */
    public function order( $column, $type='ASC' )
    {
        return $this->database->order($column, $type);
    }

    /**
     * @see \breeze\interfaces\IActive::order()
     */
    public function group( $column, $order='ASC', $rollup=false )
    {
        return $this->database->group($column, $order,$rollup);
    }

    /**
     * @see \breeze\interfaces\IActive::order()
     */
    public function having( $condition, $expre='AND' )
    {
        return $this->database->having($condition, $expre);
    }

    /**
     * @see \breeze\interfaces\IActive::join()
     */
    public function join( $table, $correlation , $type='left' )
    {
        return $this->database->join($table, $correlation,$type);
    }

    /**
     * @see \breeze\interfaces\IActive::union()
     */
    public function union( $union=null )
    {
        return $this->database->union( $union );
    }

    /**
     * @see \breeze\interfaces\IActive::endUnion()
     */
    public function endUnion()
    {
        return $this->database->endUnion();
    }

    /**
     * @see \breeze\interfaces\IActive::distinct()
     */
    public function distinct()
    {
        return $this->database->distinct();
    }

    /**
     * @see \breeze\interfaces\IActive::delay()
     */
    public function delay()
    {
        return $this->database->delay();
    }

    /**
     * @see \breeze\interfaces\IActive::ignore()
     */
    public function ignore()
    {
        return $this->database->ignore();
    }

    /**
     * @see \breeze\interfaces\IActive::quick()
     */
    public function quick()
    {
        return $this->database->quick();
    }

    /**
     * @see \breeze\interfaces\IActive::cache()
     */
    public function cache( $enable=true )
    {
        return $this->database->cache( $enable );
    }

    /**
     * @see \breeze\interfaces\IActive::procedure()
     */
    public function procedure($name,$param)
    {
        return $this->database->procedure( $name, $param );
    }

    /**
     * @see \breeze\interfaces\IActive::lock()
     */
    public function lock()
    {
        return $this->database->lock();
    }

    /**
     * @see \breeze\interfaces\IActive::limit()
     */
    public function limit($count=1000,$offset=0)
    {
        return $this->database->limit($count,$offset);
    }

    /**
     * @see \breeze\interfaces\IActive::using()
     */
    public function using( $table )
    {
        return $this->database->using($table);
    }

}

?>