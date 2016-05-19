<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-5-12
 * Time: 下午6:27
 */

namespace breeze\database\structure;


use breeze\core\EventDispatcher;
use breeze\events\StructureEvent;

class Index extends EventDispatcher
{
    const INDEX='index';
    const KEY='key';
    const PRIMARY='primary';
    const UNIQUE='unique';
    const FULLTEXT='fulltext';
    const SPATIAL='spatial';

    /**
     * @var array
     */
    private $info=array();

    /**
     * 索引类型,请在具体驱动器中实现
     * @var array
     */
    protected $indexType=array(
        'primary'=>'primary key',
        'unique'=>'unique',
        'index'=>'index',
        'key'=>'key',
        'fulltext'=>'fulltext',
        'spatial'=>'spatial',
    );

    /**
     * @constructs
     */
    public function __construct( $name, $columns, $type=Index::INDEX, $format='' )
    {
        $this->info['name'] =  $name;
        $this->info['columns'] = $columns;
        $this->info['type'] =  $type;
        $this->info['format'] = $format;
    }

    /**
     * 是哪种索引格式
     * @param null $value
     * @return $this
     */
    public function format( $value=null )
    {
        return $this->info('format', $value );
    }

    /**
     * 索引名称
     * @param null $name
     * @return null
     */
    public function name( $name=null )
    {
        return $this->info('name', $name );
    }

    /**
     * 需要引用的列名
     * @param null $columns
     */
    public function columns( $columns=null, $append=false )
    {
        if( $append===true )
        {
            $columns = $this->info['columns'].','.$columns;
        }
        return $this->info('columns', $columns );
    }

    /**
     * 索引类型
     * @param null $type
     * @return $this
     */
    public function type( $type=null )
    {
        return $this->info('type', $type );
    }

    /**
     * 输出字符串
     * @return string
     */
    public function toString()
    {
        if( $this->hasEventListener(StructureEvent::TO_STRING) )
        {
            $event = new StructureEvent( StructureEvent::TO_STRING );
            if( !$this->dispatchEvent( $event ) )
                return $event->result;
        }

        $name = $this->info['name'];
        if( stripos($this->info['type'], Index::PRIMARY ) !==false )
        {
            $name = '';
        }
        $item = array( $this->indexType[ $this->info['type'] ], $name, $this->info['format'], '('.$this->info['columns'].')' ) ;
        return implode(' ',  array_filter( $item ) );
    }

    /**
     * @param $method
     * @param $value
     * @return $this
     */
    private function info( $method, $value )
    {
        if( $value===null )
        {
            return isset( $this->info[$method] ) ? $this->info[$method] : '' ;
        }

        $value = is_array($value) ? implode(',',$value ) : $value;
        if( $this->info[$method] !== $value )
        {
            $old = $this->info[$method];
            $this->info[$method] = $value;
            if( $this->hasEventListener(StructureEvent::INDEX_CHANGE) )
            {
                $event = new StructureEvent( StructureEvent::INDEX_CHANGE );
                $event->name = $method;
                $event->oldValue = $old;
                $event->newValue = & $this->info[$method];
                $this->dispatchEvent( $event );
            }
        }
        return $this;
    }
}
