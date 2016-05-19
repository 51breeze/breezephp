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

class Whereis
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
     * Contructs.
     */
    public function __construct( Database $database )
    {
    	$this->database=$database;
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
    public function toString()
    {
    	$data='';
    	foreach( $this->data as $item )
    	{
              $issub=false;
              list($column,$value,$strainer,$logical) = $item;
              if( $column instanceof Whereis )
              {
                  $val = $item->toString();
                  $issub=true;

              }else
              {
                  $val=$this->combine( $column,$value,$strainer,$logical);
              }

    		  $logical=$this->database->logical( $logical ,'AND');
    		  if( !empty($val) )
              {
                  $val = $issub===true ? sprintf('(%s)', $val ) : $val;
                  $data.= empty($data)?  $val : $logical.$val ;
              }
    	}
    	return $data;
    }

    /**
     * 组合查询条件
     * @param string|array $column 列名
     * @param mixed $value 需要过滤的值
     * @param string $strainer 过滤器
     * @param string $logical 逻辑符
     * @return array|mixed|string
     * @throws \breeze\core\Error
     */
    private function combine($column,$value=null,$strainer='EQUAL',$logical='AND')
    {
        if( is_array($column) )
        {
            $logical=$this->database->logical($logical);
            foreach( $column as $field=>&$val )
            {
                $val= $this->combine($field,$val,$strainer);
            }
            return implode($logical, $column );
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

}

?>