<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-5-9
 * Time: 下午11:42
 */

namespace breeze\database;

use breeze\core\Error;
use breeze\database\structure\Index;
use breeze\database\structure\Structure;
use breeze\database\structure\Column;

class MysqlStructure extends Structure
{

    /**
     * 获取或者设置一个列名的结构
     * @param string|Index $name 如果是一个字符串则表示获取指定名称的索引,如果是一个索引结构表示添加一个新的索引
     * @return $this|Index
     * @throws \breeze\core\Error
     */
    public function index( $name , $remove=false )
    {
        $info = & $this->getIndexInfo();
        if( $name instanceof Index )
        {
            $info[ $name->name() ] = $name;
            $this->execute('add', $name->toString() );
            return $this;
        }
        if( !isset( $info[ $name ] ) )
        {
            throw new Error('not found index for'. $name );
        }

        if( $remove ===true )
        {
           return $this->removeIndex( $info[ $name ] );
        }
        return $info[ $name ];
    }

    /**
     * 获取或者设置一个列名的结构
     * @param string|Column $name 如果是一个字符串则表示获取指定名称的列,如果是一个列的结构类型表示添加一个新的列
     * @return $this|Column
     * @throws \breeze\core\Error
     */
    public function column( $name , $remove=false )
    {
        $info = & $this->getColumnInfo();
        if( $name instanceof Column )
        {
            $info[ $name->name() ] = $name;
            $this->execute('add', $name->toString() );
            return $this;
        }
        if( !isset( $info[ $name ] ) )
        {
            throw new Error('not found column for'. $name );
        }

        if( $remove ===true )
        {
            return $this->removeIndex( $info[ $name ] );
        }
        return $info[ $name ];
    }

    /**
     * 删除指定的索引
     * @param Index $index
     * @return $this
     */
    public function removeIndex( Index $index )
    {
        unset( $this->indexInfo[ $index->name() ] );
        $value = 'INDEX '.$index->name();
        if( $index->type() === Index::PRIMARY )
        {
            $value= Index::PRIMARY .' key';
        }
        $this->execute('drop', $value );
        return $this;
    }

    /**
     * 删除一个列的结构
     * @param Column $column
     */
    public function removeColumn( Column $column )
    {
        unset( $this->columnInfo[ $column->name() ] );
        $this->execute('drop', $column->name() );
        return $this;
    }

    /**
     * @var null
     */
    private $primary=null;

    /**
     * @param null $column
     * @return $this|null|string
     */
    public function primary( Column $column=null )
    {
        if( $this->primary === null )
        {
            $this->primary='';
            $info = $this->getIndexInfo();
            foreach( $info as $item )
            {
                if( strcasecmp($item->type(), Index::PRIMARY ) ===0 )
                {
                    $this->primary= $item;
                    break;
                }
            }
        }

        if( $column !== null )
        {
            if( $this->primary instanceof Index )
            {
                $this->removeIndex( $this->primary  );
            }
            $this->primary = $column;
            $this->index( new Index('primary',$column->name(), Index::PRIMARY) );
            return $this;
        }
        return $this->primary;
    }

    /**
     * @var Column
     */
    private $increment=null;

    /**
     * 指定列名为自增模式
     * @param $column
     * @param bool $flag
     * @return $this|Column
     */
    public function increment( Column $column=null )
    {
        if( $this->increment === null )
        {
            $this->increment='';
            $info = $this->getColumnInfo();
            foreach( $info as $item )
            {
                $extra = $item->extra();
                if( !empty( $extra ) )
                {
                    $this->increment = strcasecmp($extra,'auto_increment')===0 ? $item : '';
                }
            }
        }

        //返回自增的列
        if( $column === null )
        {
            return $this->increment;
        }

        //自增字段类型只能是一个整型娄字
        $typename = $column->type()->name();
        if( stripos( $typename, 'int' ) === false )
        {
            throw new Error('auto increment of column type must is int.');
        }

        //删除之前的自增列
        if( !empty($this->increment) )
        {
            $this->increment->extra('');
        }

        $this->increment=$column;
        $column->extra('auto_increment');
        return $this;
    }

} 