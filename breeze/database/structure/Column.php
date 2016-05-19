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

class Column extends EventDispatcher
{
    /**
     * @var array
     */
    private $info=array();

    /**
     * @var array
     */
    protected $requirementMap=array(
        'yes'=>'not null',
        'no'=>'',
    );


    /**
     * @constructs
     */
    public function __construct( $name , ColumnType $type, $requirement='', $default='',$comment='',$charset='',$extra='')
    {
        $this->info['name'] = $name;
        $this->info['type'] = $type;
        $this->info['requirement'] = $requirement;
        $this->info['default'] = $default;
        $this->info['charset'] = $charset;
        $this->info['extra'] = $extra;
        $this->info['comment'] = $comment;
        $self = $this;
        $type->addEventListener(StructureEvent::CHANGE,function( StructureEvent $event )use($self){
            $self->dispatchEvent( $event );
        });

    }

    /**
     * 列名
     * @param null $name
     * @return $this|null
     */
    public function name( $name=null )
    {
        return $this->info('name', $name );
    }

    /**
     * 设置字段的类型
     * @param ColumnType $type
     * @return ColumnType|$this
     */
    public function type( ColumnType $type=null )
    {
        return $this->info('type',$type);
    }

    /**
     * 是否可以为空
     * @param bool $value
     * @return $this
     */
    public function requirement( $value=null )
    {
        return $this->info('requirement',$value);
    }

    /**
     * 获取设置字符集
     * @param null $charset
     * @return $this|string
     */
    public function charset( $charset=null )
    {
        return $this->info('charset', $charset);
    }

    /**
     * 为当前的列指定一些扩展功能
     * @param null $charset
     * @return $this|string
     */
    public function extra( $extra=null )
    {
        return $this->info('extra', $extra);
    }

    /**
     * 获取设置备注
     * @param null $charset
     * @return $this|string
     */
    public function comment( $comment=null )
    {
        return $this->info('comment', $comment);
    }

    /**
     * 默认值
     * @param null $charset
     * @return $this|string
     */
    public function defaultValue( $value=null )
    {
        return $this->info('default', $value);
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

        $type = $this->type()->toString();
        $item = array(
            $this->info['name'],
            $type,
            $this->info['requirement'] ? $this->requirementMap['yes'] : $this->requirementMap['no'],
        );
        $item=array_filter($item);
        if( !empty($this->info['default']) )
            $item[] = sprintf('default "%s"', $this->info['default'] );
        if( !empty($this->info['comment']) )
            $item[] = sprintf('comment "%s"', $this->info['comment'] );
        return implode(' ', $item );
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
            $old =  $this->info[$method];
            $this->info[$method] = $value;

            if( $value instanceof ColumnType )
            {
                $self = $this;
                $value->addEventListener(StructureEvent::CHANGE,function( StructureEvent $event )use($self){
                    $self->dispatchEvent( $event );
                });
            }

            if( $this->hasEventListener(StructureEvent::CHANGE) )
            {
                $event = new StructureEvent( StructureEvent::CHANGE );
                $event->name = $method;
                $event->oldValue=$old;
                $event->newValue = & $this->info[$method];
                $this->dispatchEvent( $event );
            }
        }
        return $this;
    }
}
