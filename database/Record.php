<?php

namespace breeze\database;
use breeze\core\Error;
use breeze\core\Lang;
use breeze\interfaces\IRecord;
use breeze\utils\Utils;

/**
 * 数据集操作类
 * Class Record
 * @package breeze\database
 */
abstract class Record extends Database implements IRecord
{
    /**
     * @see \breeze\interfaces\IRecord::set()
     */
    public function set()
    {
        $this->isUnion=false;
        if( !$this->isSql('table') )
            throw new Error( Lang::info(2003) );

        //需要更新的数据
        $data=& $this->getSql('bind');
        if( empty($data) || is_scalar($data) )
            throw new Error( Lang::info(2004) );

        //创建需要更新的sql语句。
        $where= $this->createBatchUpdate();

        //把主键当做筛选的条件
        if( is_array($where) && !empty($where) )
        {
             $whereis=&$this->getSql('where');
             if( empty($whereis) )
                 $whereis=new Whereis($this);
             foreach( $where as $primary=>&$item )
             {
                 $whereis->item($primary,$item,Strainer::IN,false);
             }
             $whereis=$whereis->toString();
             $where=null;
        }

        //获取已生成好的sql 语句。
        $value=& $this->getSql('on');
        if( empty($value) )
            throw new Error( Lang::info(1005) );
        $this->setSql('value', $value ,null,false,false);
        $value=null;
        return $this->execute( $this->createQuery('update') );
    }

    /**
     * @see \breeze\interfaces\IRecord::get()
     */
    public function get( $count=1000 ,$offset=0 )
    {
        $this->isUnion=false;
        if( !$this->isSql('table') )
            throw new Error( Lang::info(2003) );
        if( !is_numeric($count) || !is_numeric($offset) )
            throw new Error( Lang::info(2006) );
        !$this->isSql('limit') && $this->limit((int)$count,(int)$offset);
        $this->query(  $this->createQuery('select')  );
        return $this;
    }

    /**
     * @see \breeze\interfaces\IRecord::add()
     */
    public function add( array $value )
    {
        $this->isUnion=false;
        if( empty( $value ) )
            throw new Error( Lang::info(2007) );
        if( !$this->isSql('table') )
            throw new Error( Lang::info(2003) );

        $value=Utils::toArray($value);

        if( $this->isSql('bind') )
        {
            $data=$this->getSql('bind');
            $data=is_array($data) && !empty($data) ?  array() :  $value;
            $this->createBatchUpdate($data );
            $this->setSql('on',$this->getSql('bind'),null,false,false);
        }

        //如果没有指定列名
        if( !$this->isSql('columns') )
        {
           if( !is_array( $value[0] ) )
               throw new Error( Lang::info(2008) );
           $this->setSql('columns', array_keys( $value[0] ) ,null,false,false);
        }

        //解析需要插入的数据
        foreach( $value as & $item )
        {
            $item=$this->escape($item);
            $item= is_array($item) && !empty($item)  ? '("'.implode('","', $item).'")' : '';
        }
        $this->setSql('value', implode(',', $value) ,null,false,false );
        return $this->execute( $this->createQuery('insert') );
    }

    /**
     * @see \breeze\interfaces\IRecord::remove()
     */
    public function remove($alled=false)
    {
        $this->isUnion=false;
        $table=& $this->getSql('table');
        if( empty($table) )
            throw new Error( Lang::info(2003) );
        $table=(array)$table;
        $table=$this->getTable( $table );
        if( count( $table ) > 1 )
        {
            $alias=array();
            foreach($table as $item)
            {
               $alias[]=$alia=$this->getAlias( $item ,false, $item );
            }
            $this->setSql('using',implode(',',$table),null,false,false);
            $table=implode(',',$this->backQuote($alias) );
        }
        return $this->execute( $this->createQuery('delete') );
    }

    /**
     * @see \breeze\interfaces\IRecord::copy()
     */
    public function copy( $toTable  )
    {
        $table=& $this->getSql('table');
        if( empty($table) || empty($toTable) )
            throw new Error( Lang::info(2003) );

        if( !is_string($toTable) )
           throw new Error( Lang::info(1006) );

        $table=(array)$table;
        $table=$this->getTable( $table );
        $columns=& $this->getSql('columns');
        $toColumn=array();

        if( DEBUG===true && count($table) > 1 )
            trigger_error(Lang::info(2014));

        // 如果没有指定列名则从数据库获取
        if( empty($columns) || $columns=='*'  )
        {
            $columns = $this->getColumns( $table[0] );
            foreach( $columns as &$item )$item=$item[0];
            $toColumn=$columns;
        }else foreach($columns as $item)
        {
            $toColumn[]=$this->getAlias($item,false,$item);
        }

        //如果使用了数据绑定
        if( $this->isSql('bind') )
        {
            $alias=$this->getAlias($table[0],false);
            $this->createBatchUpdate(array(), !empty($alias) ? $this->backQuote($alias).'.' : ''  );
        }

        $value=$this->createQuery('select');
        $toTable=$this->getTable($toTable);

        $this->setSql('value', $value ,null,false );
        $this->setSql('table', $toTable,null,false );
        $this->setSql('columns', $toColumn,null,false );
        return $this->execute( $this->createQuery('copy') );
    }

    /**
     * @see \breeze\interfaces\IRecord::bind()
     */
    public function bind( $column,$value=null, array $updata=null )
    {
        $data=$updata;
        $reference=false;

        //给定一个主键列名，由程序内部实现
        if(  is_string($column) && is_null($value) && is_null($updata) )
        {
            $this->setSql('bind', $column,null,false );

        }else if( !empty($column) )
        {
            //默认要赋值的列名
            if( is_array($column) && is_null($value) )
            {
                if( isset($column[0]) || array_sum( array_keys( $column ) )>0 )
                    throw new Error(Lang::info(2015));
                $value=$column;
                $column='';
                $reference=true;
            }

            //合并主键和要更新的值
            if( is_string($column) && !is_null($value) )
            {
                //如果键值都是一个字符串则合并成一个数组(非数字索引的数组)
                $data=is_array($value) ? $value : ( Utils::isArrayIndex($updata) ? array() : array($column=>$value) );

                //合并需要更新的数据
                is_array($updata) && !empty($updata) && $data=array_merge($updata,$data);

                //如果不是引用，更新的数据中必须出现主键
                if( !( isset( $data[$column] ) || isset( $data[0][$column]) ) && $reference===false )
                {
                    throw new Error(Lang::info(2005));
                }
            }
            $this->setSql('bind', array($column=>$data) );
        }
        return $this;
    }

    //==============================================================
    // Protected Method
    //==============================================================

    /**
     * @private
     */
    protected function whenToCase( & $data=array() , $unique='', $default=null,$prefix='',& $sql=array() )
    {
        if( is_scalar($data) || is_null($data) )
            return $data;
        foreach ( $data as $key=>$val )
        {
            if( !empty($unique) && is_array($val) && !empty($val) && strcasecmp($key,$unique)!==0 )
            {
                $column=$this->backQuote($key);
                $defaultValue=$column;
                if( is_callable($default) )
                {
                    //从指定的函数中获取子级值。
                    $defaultValue=call_user_func($default,$unique,$key);
                    $defaultValue=is_null($defaultValue) ? $column : $defaultValue;

                }else if( !empty($default) && is_scalar($default) )
                {
                    $defaultValue=$this->parseBatchUpdateValue($default);
                }
                $sql[$key]=sprintf('CASE %s%s %s ELSE %s END',$prefix,$this->backQuote($unique),implode('', $val ),$defaultValue );

            }else
            {
                empty($unique) ? $sql[$key]=$val :  $sql[$unique]=$val;
            }
        }
        return $sql;
    }

    /**
     * @private
     */
    protected function arrayToWhen( & $data=array() , $unique='', & $sql=array() )
    {
       if( is_scalar($data) || is_null($data) )
           return $sql;

        foreach ( $data as $key=>$val )
        {
            if( is_array($val) )
            {
                $this->arrayToWhen($val,$unique,$sql);

            }else if( !empty($unique) && isset( $data[$unique] ) )
            {
                if( is_callable($val) )
                {
                    //从定义的函数中返回需要更新的值
                    $val=call_user_func($val,$unique,$data[$unique],$key);
                }
                if( is_scalar($val) )
                {
                    $sql[$key][]= strcasecmp($key,$unique)===0 ?
                        $this->parseBatchUpdateValue($val,is_numeric($key),'') :
                        sprintf(" WHEN '%s' THEN %s ", $data[$unique], $this->parseBatchUpdateValue( $val,is_numeric($key) ) );
                }
            }else if( empty($unique) )
            {
                //直接指定需要更新的值
                $column=( is_numeric($key) && is_string($val) ) ? $val : $key;
                $sql[$column]=$this->parseBatchUpdateValue( $val, is_numeric($key));
            }
        }
        return $sql;
    }

    //==============================================================
    // Private Method
    //==============================================================

    /**
     * @private
     */
    private function parseBatchUpdateValue( $value='',$reference=false ,$quote="'")
    {
        if( !empty($value) )
        {
            //从指定的表中引用数据
            if( $this->isUsing($value) || $reference===true )
            {
                $value=$this->backQuote($value);
            }else
            {
                $value=$quote.$this->escape($value).$quote;
            }
        }
        return $value;
    }

    /**
     * @private
     */
    private function createBatchUpdate(array $update=array(), $prefix='' )
    {
        $updateData=array();
        $primary=array();
        $defaultPrimary='';
        $bind=& $this->getSql('bind');
        static $callback=null;
        $callback===null && $callback = function($unique,$column) use( & $updateData )
        {
            //返回 null 时引用列名, 否则为一个完整的表达式。
            if( empty($updateData) || !isset($updateData[$column]) )
                return null;
            $val=$updateData[$column];
            unset( $updateData[$column] );
            return $val;
        };

        //只绑定了主键
        if( is_string($bind) )
        {
            $unique=$bind;
            $bind=null;
        }

        if( !empty($bind) )
        {
            //指定更新列的默认值,当前待更新列值没有设置时使用默认值。确保默认值的数据放到最后。
            if( isset($bind[$defaultPrimary]) )
            {
                $temp=$bind[$defaultPrimary];
                unset($bind[$defaultPrimary]);
                $bind[$defaultPrimary]=isset($temp[0]) ? array_splice($temp,-1,1) : $temp;
                unset($temp);
            }

            if( !empty($update) )
            {
                if( empty($unique) )
                {
                    $bind[$defaultPrimary]=isset( $bind[$defaultPrimary] ) ? array_merge( $update,$bind[$defaultPrimary] ) : $update;
                }else
                {
                    $bind=$this->merge($bind,array($unique=>$update));
                }
            }
        }
        //只生成批量更新的语句
        else if( !empty($unique) && !empty($update) )
        {
            $bind=array($unique=>$update);
        }

        /*
        * 从数组的结尾往前流。
        * 当 caseByWhen 调用时会检索 $this->onUpdateData 中的数据
        * 如果此时有相同的列名则认为之前的 case 数据为当前 case 的子级
        * 则会把前一次的case数据添加到此case 'else' 部分，并从$this->onUpdateData中删除。
        * 最终会形成每个列名只有一条数据。
        */
        while( !empty($bind) )
        {
            $element=array_splice($bind,-1,1);
            @list($unique,$element)=each($element);
            $when= $this->arrayToWhen($element,$unique );
            $when= $this->whenToCase( $when , $unique , $callback , $prefix );

            if( !empty($unique) && isset($when[$unique]) )
            {
                $primary=array_merge($primary,array($unique=>$when[$unique]));
                unset( $when[$unique] );
            }
            //合并已设置需要更新的列。
            $updateData=array_merge($updateData,(array)$when);
            $element=null;
            $when=null;
        }
        $sql=array();
        if( !empty($updateData) )
        {
            foreach( $updateData as $column=>$value )
            {
                if( is_string($column) && is_scalar($value) )
                    $sql[]=sprintf('%s=%s',$this->backQuote($column),$value);
            }
        }
        $this->setSql('on',implode(',',$sql),null,false,false);
        $updateData=null;
        $bind=null;
        $sql=null;
        return $primary;
    }

} 