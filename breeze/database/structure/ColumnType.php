<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-5-12
 * Time: 下午6:26
 */

namespace breeze\database\structure;

use breeze\core\EventDispatcher;
use breeze\events\StructureEvent;

class ColumnType extends EventDispatcher
{
    const TINYINT="tinyint";
    const SMALLINT="smallint";
    const MEDIUMINT="mediumint";
    const INT="int";
    const INTEGER="integer";
    const BIGINT="bigint";
    const REAL="real";
    const DOUBLE="double";
    const FLOAT="float";
    const DECIMAL="decimal";
    const NUMERIC="numeric";
    const DATE="date";
    const TIME="time";
    const TIMESTAMP="timestamp";
    const CHAR="char";
    const VARCHAR="varchar";
    const TINYBLOB="tinyblob";
    const BLOB="blob";
    const MEDIUMBLOB="mediumblob";
    const TINYTEXT="tinytext";
    const MEDIUMTEXT="mediumtext";
    const TEXT="text";
    const LONGTEXT="longtext";
    const ENUM="enum";
    const SET="set";

    const FORMAT_UNSIGNED= 'unsigned';
    const FORMAT_ZEROFILL= 'zerofill';
    const FORMAT_ASCII= 'ascii';
    const FORMAT_UNICODE= 'unicode';
    const FORMAT_BINARY= 'binary';

    /**
     * 字段类型
     * @var array
     */
    protected $fieldType=array(
        'tinyint'=>'tinyint(%s) %s',
        'smallint'=>'smallint(%s) %s',
        'mediumint'=>'mediumint(%s) %s',
        'int'=>'int(%s) %s',
        'integer'=>'integer(%s) %s',
        'bigint'=>'bigint(%s) %s',
        'integer'=>'integer(%s) %s',
        'real'=>'real(%s) %s',
        'double'=>'double(%s) %s',
        'float'=>'float(%s) %s',
        'decimal'=>'decimal(%s) %s',
        'numeric'=>'numeric(%s) %s',
        'date'=>'date',
        'time'=>'time',
        'timestamp'=>'timestamp',
        'char'=>'char(%s) %s',
        'varchar'=>'varchar(%s)',
        'blob'=>'blob',
        'mediumblob'=>'mediumblob',
        'tinyblob'=>'tinyblob',
        'tinytext'=>'tinytext %s',
        'text'=>'text %s',
        'longtext'=>'longtext %s',
        'enum'=>'enum(%s)',
        'set'=>'set(%s)',
    );

    /**
     * 存储的格式类型,请在具体驱动器中实现
     * @var array
     */
    protected $formatType=array(
        'unsigned'=>'unsigned',
        'zerofill'=>'zerofill',
        'ascii'   =>'ascii',
        'unicode' =>'unicode',
        'binary'  =>'binary',
    );

    /**
     * @var array
     */
    private $info=array();

    /**
     * @param $name
     */
    public function __construct($name=ColumnType::VARCHAR, $length='',$decimal='', $format='' )
    {
        $this->info['name']   =  $name;
        $this->info['length'] =  $length;
        $this->info['decimal'] = $decimal;
        $this->info['format'] =  $format;
    }

    /**
     * 类型名
     * @param null $name
     * @return $this
     */
    public function name( $name=null )
    {
        return $this->info('name', $name );
    }

    /**
     * 字符长度
     * @param null $lenght
     * @return $this
     */
    public function length( $lenght=null )
    {
        return $this->info('length', $lenght );
    }

    /**
     * 字符长度
     * @param null $lenght
     * @return $this
     */
    public function decimal( $decimal=null )
    {
        return $this->info('decimal', $decimal );
    }

    /**
     * 字符格式
     * @param null $type
     * @return $this
     */
    public function format( $format=null )
    {
        return $this->info('format', $format );
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

        $value = array($this->info['length'], $this->info['decimal']);
        $value = array_filter($value);
        if( empty($value) )array_push($value, $this->info['format'] );
        $format = @$this->formatType[ $this->info['format'] ];
        return sprintf( $this->fieldType[ $this->info['name'] ] , implode(',', $value) , $format );
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
            if( $this->hasEventListener(StructureEvent::CHANGE) )
            {
                $event = new StructureEvent( StructureEvent::CHANGE );
                $event->name = $method;
                $event->oldValue = $old;
                $event->newValue = & $this->info[$method];
                $this->dispatchEvent( $event );
            }
        }
        return $this;
    }
}
