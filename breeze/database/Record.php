<?php

namespace breeze\database;
use breeze\core\Error;
use breeze\core\Lang;
use breeze\interfaces\IBind;
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
     * @see \breeze\interfaces\IRecord::get()
     */
    public function get($type=Database::FIELD, $single=false)
    {
        $this->isUnion=false;
        $this->query(  $this->createQuery('select')  );
        return $this->fetch($type,$single);
    }

    /**
     * @private
     * @return mixed|string
     */
    private function getPrimaryBy( array $fields )
    {
        $primary = $this->getMultiTablePrimary( $this->getSql('table') , true );
        $primary = array_intersect($primary,$fields);
        return !empty($primary) ? current( $primary ) : '';
    }

    /**
     * @see \breeze\interfaces\IRecord::set()
     */
    public function set( array $data , $primary='' )
    {
        if( empty($data) )
        {
            throw new Error('invalid data');
        }

        $this->isUnion=false;

        //创建多条更新的sql语句，需要指定一个主键。
        if( empty($primary) && Utils::isArrayIndex( $data ) )
        {
            $primary = $this->getPrimaryBy( array_keys( $data[0] ) );
        }

        $data= $this->createBatchUpdate( array($primary=>$data) );
        if( !empty($data['primary']) )
        {
            $str=new Strainer($this);
            foreach( $data['primary'] as $column=>$item )
            {
                $str->in($column,$item);
            }
            $this->where( $str );

        }else if( !empty($primary) )
        {
            throw new Error('not found primary.');
        }

        if( !$this->where()->hasItem() )
        {
            throw new Error('update need assign a strainer.');
        }

        $this->setSql('value', $data['value'] ,null,false,false);
        return $this->execute( $this->createQuery('update') );
    }

    /**
     * @see \breeze\interfaces\IRecord::save()
     */
    public function save( array $data , $primary='' )
    {
        $this->on($primary);
        return $this->add( $data );
    }

    /**
     * @see \breeze\interfaces\IRecord::on()
     */
    public function on( $primary , array $update=null )
    {
        $this->setSql('on',array($primary=>$update),null,false,false);
        return $this;
    }

    /**
     * @see \breeze\interfaces\IRecord::add()
     */
    public function add( array $data )
    {
        $this->isUnion=false;
        if( empty($data) )
        {
            throw new Error('invalid data');
        }

        $data=Utils::toArray($data);
        $this->setSql('columns', array_keys( $data[0] ) ,null,false,false);

        $on = & $this->getSql('on');
        if( !empty($on) )
        {
            list($primary, $update )= each($on);
            $update = is_null($update) ? $data : Utils::toArray($update);

            if( empty($primary) )
            {
                $primary =  $this->getPrimaryBy( array_keys($update[0]) );
            }

            $update=$this->createBatchUpdate( array($primary=>$update) );
            $on = $update['value'];
            if( empty($update['primary']) )
            {
                throw new Error('not found primary.');
            }
        }

        //解析需要插入的数据
        foreach( $data as & $item )
        {
            $item=$this->escape($item);
            $item= is_array($item) && !empty($item)  ? '("'.implode('","', $item).'")' : '';
        }
        $this->setSql('value', implode(',', $data) ,null,false,false );
        return $this->execute( $this->createQuery('insert') );
    }

    /**
     * @see \breeze\interfaces\IRecord::remove()
     */
    public function remove($alled=false)
    {
        $this->isUnion=false;
        $table=& $this->getSql('table');
        if( empty($table) && $alled===false )
        {
            throw new Error( Lang::info(2003) );
        }

        $table=(array)$table;
        $table=$this->getTable( $table );
        if( count( $table ) > 1 )
        {
            $alias=array();
            foreach($table as $item)
            {
               $alias[]=$this->getAlias( $item ,false, $item );
            }
            $this->setSql('using',implode(',',$table),null,false,false);
            $table=implode(',',$this->backQuote($alias) );
        }
        return $this->execute( $this->createQuery('delete') );
    }

    /**
     * @see \breeze\interfaces\IRecord::clean()
     */
    public function clean( $table=null )
    {
        if( !empty($table) )
        {
            $this->table( $table );
        }
        return $this->execute( $this->createQuery('clean') );
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

        $on = & $this->getSql('on');
        if( !empty($on) )
        {
            list($primary, $update )= each($on);
            if( !empty($update) )
            {
                if( empty($primary) )
                {
                    $primary =  $this->getPrimaryBy( array_keys($update[0]) );
                }

                $alias=$this->getAlias($table[0],false);
                $update=$this->createBatchUpdate( array($primary=>$update) , !empty($alias) ? $this->backQuote($alias).'.' : '' );
                $on = $update['value'];
                if( empty($update['primary']) )
                {
                    throw new Error('not found primary.');
                }
            }
        }

        $value=$this->createQuery('select');
        $toTable=$this->getTable($toTable);

        $this->setSql('value', $value ,null,false );
        $this->setSql('table', $toTable,null,false );
        $this->setSql('columns', $toColumn,null,false );
        return $this->execute( $this->createQuery('copy') );
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
                $sql[$column]=$this->parseBatchUpdateValue( $val, is_numeric($key) );
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
     * 生成一个批量更新的语句
     * @param array $update 一个数组
     *
     * @param string $prefix 是否需要加前缀
     * @private
     */
    private function createBatchUpdate(array $update=array(), $prefix='' )
    {
        $dataGroup=array();
        $primary=array();
        //$defaultPrimary='';
        $bind=$update;

        static $callback=null;
        if( $callback===null ) $callback = function($unique,$column) use( & $dataGroup )
        {
            //返回 null 时引用列名, 否则为一个完整的表达式。
            if( empty($dataGroup) || !isset($dataGroup[$column]) )
                return null;

            $val=$dataGroup[$column];
            unset( $dataGroup[$column] );
            return $val;
        };

        //只绑定了主键
       /* if( is_string($bind) )
        {
            $unique=$bind;
            $bind=null;
        }*/

       /* if( !empty($bind) )
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
        }*/

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
            $dataGroup=array_merge($dataGroup,(array)$when);
            $element=null;
            $when=null;
        }

        $sql=array();
        if( !empty($dataGroup) )
        {
            foreach( $dataGroup as $column=>$value )
            {
                if( is_string($column) && is_scalar($value) )
                    $sql[]=sprintf('%s=%s',$this->backQuote($column),$value);
            }
        }

        return array('primary'=>$primary,'value'=>implode(',',$sql) );

       /* $this->setSql('on',implode(',',$sql),null,false,false);
        $updateData=null;
        $bind=null;
        $sql=null;
        return $primary;*/
    }

} 