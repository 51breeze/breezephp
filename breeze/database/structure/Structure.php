<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-5-5
 * Time: 下午1:05
 */

namespace breeze\database\structure;
use breeze\core\EventDispatcher;
use breeze\database\Database;
use breeze\events\StructureEvent;
use breeze\interfaces\IStructure;

abstract class Structure extends EventDispatcher implements IStructure
{

    const CHARSET_UTF8='utf8';
    const CHARSET_GB2312='gb2312';
    const CHARSET_GBK='gbk';
    const CHARSET_BIG5='big5';
    const CHARSET_LATIN1='latin1';
    const CHARSET_LATIN2='latin2';
    const CHARSET_LATIN5='latin5';
    const CHARSET_ASCII='ascii';
    const CHARSET_BINARY='binary';
    const CHARSET_DEC8='dec8';

    const ENGINE_INNODB='InnoDB';
    const ENGINE_MYISAM='Myisam';
    const ENGINE_BDB='BDB';
    const ENGINE_NDB='NDB';
    const ENGINE_CSV='CSV';
    const ENGINE_MEMORY='Memory';
    const ENGINE_ARCHIVE='Archive';
    const ENGINE_FEDERATED='Federated';

    protected $collation=array(
        'utf8'=>'utf8_general_ci',
        'gb2312'=>'gb2312_chinese_ci',
        'gbk'=>'gbk_chinese_ci',
        'big5'=>'big5_chinese_ci',
        'latin1'=>'latin1_swedish_ci',
        'latin2'=>'latin2_general_ci',
        'latin5'=>'latin5_turkish_ci',
        'ascii'=>'ascii_general_ci',
        'binary'=>'binary',
        'dec8'=>'dec8_swedish_ci',
    );

    /**
     * @private
     * @var array
     */
    protected $columnMap=array(
        'column'=>'Field',
        'type'=>'Type',
        'charset'=>'Collation',
        'requirement'=>'Null',
        'default'=>'Default',
        'extra'=>'Extra',
        'comment'=>'Comment',
    );

    /**
     * @private
     * @var array
     */
    protected $indexMap=array(
        'column'=>'Column_name',
        'length'=>'Sub_part',
        'format'=>'Index_type',
        'name'=>'Key_name',
        'sequence'=>'Seq_in_index',
        'index'=>'Non_unique',
    );

    /**
     * sql语法
     * @var array
     */
    protected $sql=array(
        'describe' =>'show full columns from %s',
        'index' =>'show index from %s',
        'tables' =>'show  tables',
        'alter'=>'alter table %s %s',
        'add'=>'alter table %s add %s',
        'drop'=>'alter table %s drop %s',
        'change'=>'alter table %s change %s %s',
        'create'=>"create table if not exists %s ( %s ) %s",
    );

    /**
     * @private
     * @var Database
     */
    protected  $db=null;

    /**
     * @private
     * @var null
     */
    protected  $table=null;

    /**
     * @construct
     * @param array $map
     */
    public function __construct( Database $db, $table )
    {
        $this->table = $table;
        $this->db=$db;
    }

    /**
     * 获取此结构的表名
     * @return null
     */
    public function table()
    {
        return $this->table;
    }

    /**
     * @private
     */
    protected function toValue($keyword,$value, $field )
    {
        switch($keyword)
        {
            case 'requirement' :
                return strcasecmp($value[$field],'no')===0 ? 'not null' : '';
                break;
            case 'default' :
                return strcasecmp($value[$field],'(null)')===0 ? '' : $value[$field];
                break;
            case 'index' :
                if( strcasecmp( $value[ $this->indexMap['name'] ],'primary') === 0 && strcasecmp( $value[ $this->indexMap['index'] ],'0') === 0)
                    return Index::PRIMARY;
                if( strcasecmp( $value[ $this->indexMap['format'] ],'fulltext') === 0 )return Index::FULLTEXT;
                if( strcasecmp( $value[ $this->indexMap['format'] ],'spatial') === 0 )return Index::SPATIAL;
                if( strcasecmp( $value[ $this->indexMap['index'] ],'0') === 0 )return Index::UNIQUE;
                return Structure::INDEX_INDEX;
                break;
        }
        return isset($value[$field]) ? $value[$field] : '';
    }

    /**
     * @var null
     */
    protected $indexInfo=null;

    /**
     * 获取表结构的所有索引信息
     * @return array|mixed
     */
    public function & getIndexInfo()
    {
        if( $this->indexInfo === null )
        {
            $info = $this->db->query( sprintf( $this->sql['index'], $this->table ) )->fetch();
            $this->indexInfo=array();
            foreach( $info as $item )
            {
                $item = $this->foramt( $this->indexMap, $item );
                if( isset( $this->indexInfo[ $item['name'] ] ) )
                {
                    $this->indexInfo[ $item['name'] ].columns( $item['column'] , true );

                }else
                {
                    $index = new Index( $item['name'], $item['column'] , $item['index'], $item['format'] );
                    $index->addEventListener( StructureEvent::CHANGE , array($this,'change') );
                    $this->indexInfo[ $item['name'] ]=  $index;
                }
            }
        }
        return $this->indexInfo;
    }

    /**
     * @private
     */
    protected  $columnInfo=null;

    /**
     * 获取表的结构信息
     * @return array
     */
    public  function & getColumnInfo()
    {
        if( $this->columnInfo=== null )
        {
            $this->columnInfo=array();
            $columninfo = $this->db->query( sprintf( $this->sql['describe'], $this->table ) )->fetch();

            foreach($columninfo as $item)
            {
                $item=$this->foramt( $this->columnMap, $item );
                preg_match( '/^(\w+)(\((\d+)\)(\s+\w+)?)?$/',$item['type'], $match );
                $item['type'] = $match[1];
                $item['length'] = $match[3];
                $item['format'] = isset($match[4]) ? $match[4] : '';

                $columType =  new ColumnType( $item['type'], $item['length'], $item['format'] );
                $column = new Column( $item['column'],
                    $columType,
                    $item['requirement'],
                    $item['default'],
                    $item['comment'],
                    $item['charset'],
                    $item['extra']
                );
                $column->addEventListener( StructureEvent::CHANGE, array($this,'change') );
                $this->columnInfo[ $item[ 'column' ] ] = $column;
            }
        }
        return $this->columnInfo;
    }

    /**
     * 修改表字段信息和索引后的调度
     * @param StructureEvent $event
     */
    protected function change( StructureEvent $event )
    {
        $value = $event->target->toString();
        if( $event->target instanceof Column && $event->name === 'name' )
        {
            $value = $event->oldValue." ".$value;
        }
        $this->execute('change', $value );
    }

    /**
     * 输出表的结构
     * @return string
     */
    public function toString()
    {
        $columnInfo = $this->getColumnInfo();
        $indexInfo = $this->getIndexInfo();
        $data =array_merge( array_values($columnInfo), array_values($indexInfo) );
        foreach( $data as &$item )
        {
            $item = $item->toString();
        }

        $option=array();
        $engine = $this->engine();
        if( !empty($engine) )
            $option[] = 'engine='.$engine;

        $charset = $this->charset();
        if( !empty($charset) )
            $option[] = 'charset='.$charset;
        return sprintf( $this->sql['create'], $this->table, implode(",\r\n", $data ),  implode(' ',$option) );
    }

    /**
     * @private
     */
    private function foramt(array &$map, array $data )
    {
        $result=array();
        foreach( $map as $method=>$field )
        {
            $result[$method]= $this->toValue($method,$data,$field);
        }
        return $result;
    }

    /**
     * @var array
     */
    private $fields=null;

    /**
     * 获取表的所有字段名
     * @return array
     */
    public function fields()
    {
        if( $this->fields === null )
        {
            $this->fields=array();
            $info = $this->getColumnInfo();
            $this->fields = array_keys( $info );
        }
        return $this->fields;
    }

    /**
     * @var string
     */
    private $charset=Structure::CHARSET_UTF8;

    /**
     * 获取设置字符集
     * @param null $charset
     * @return $this|string
     */
    public function charset( $charset=null )
    {
        if( is_null($charset) )
            return $this->charset;
        $this->charset=$charset;
        return $this;
    }

    /**
     * @var string
     */
    private $engine=Structure::ENGINE_INNODB;

    /**
     * 获取设置表的引擎
     * @param null $engine
     * @return $this|string
     */
    public function engine( $engine=null )
    {
        if( is_null($engine) )
            return $this->engine;
        $this->engine=$engine;
        return $this;
    }

    /**
     * @var array
     */
    protected $execute=array();

    /**
     * @param $cmd
     * @param $sql
     * @return $this
     */
    protected function execute( $cmd, $sql )
    {
        array_push( $this->execute, $cmd.' '.$sql);
        return $this;
    }

    public function tables()
    {

    }

    /**
     * 同步到数据库
     * @return bool
     */
    public function save()
    {
        if( empty($this->execute) )
        {
            return false;
        }
        $sql =  sprintf( $this->sql['alter'] , $this->table, implode(',' ,$this->execute ) );
        $this->execute=array();
        return $this->db->execute( $sql );
    }
}





