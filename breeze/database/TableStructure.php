<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-5-5
 * Time: 下午1:05
 */

namespace breeze\database;
use breeze\core\Error;

class TableStructure
{

    /**
     * @private
     */
    private static $info=array();

    /**
     * @private
     */
    private static $columns=array();

    /**
     * @private
     */
    private static $primaries=array();

    /**
     * @private
     */
    private static $tables=array();

    /**
     * @private
     * @var array
     */
    public static $map=array(
        'field'=>'Field',
        'type'=>'Type',
        'collation'=>'Collation',
        'require'=>'Null',
        'index'=>'Key',
        'extra'=>'Extra',
        'label'=>'Comment',
        'privileges'=>'Privileges',
    );

    /**
     * @private
     * @var Database
     */
    private $db=null;

    /**
     * @private
     * @var null
     */
    private $table=null;

    /**
     * @construct
     * @param array $map
     */
    public function __construct( Database $db, $table )
    {
        $this->table = $table;
        $this->db=$db;
        self::$tables[ $table ] = $this;
    }

    public function add( $column , $type='varchar', $require='null', $defualt='', $increment='', $comment='' )
    {
         // 'addColumn' => array('alter',array('ignore','table'),'add','column',array('require','default','auto_increment','key','comment'),array('first','after','refcolumn') ),
    }

    /**
     * 获取指定表名的结构
     * @param string $table
     * @return TableStructure
     */
    public static function table( $name, Database $db )
    {
        if( !isset( self::$tables[ $name ] ) )
        {
            self::$tables[ $name ] = new TableStructure($db, $name);
        }
        return self::$tables[ $name ];
    }

    /**
     * 获取表的结构信息
     * @param array $info 一个数组
     * @return array
     */
    public function info( array $info = null )
    {
        $table = $this->table;
        if( !empty($info) )
        {
            self::$info[ $table ]=$info;
            return $this;
        }

        //如果没有手动配置则从数据表中获取
        if( !isset( self::$info[ $table ] ) )
        {
            $this->db->query( sprintf('SHOW FULL COLUMNS FROM %s' ,$table ) );
            self::$info[ $table ]=$this->db->fetch();
        }
        return self::$info[ $table ];
    }

    /**
     * 获取表的列名
     * @return array
     */
    public function columns(array $columns=null)
    {
        $table= $this->table;
        if( !empty($info) )
        {
            self::$columns[ $table ]=$columns;
            return $this;
        }

        if( !isset( self::$columns[ $table ] ) )
        {
            self::$columns[ $table ]=$this->info($table);
            foreach( self::$columns[ $table ] as & $item )
                $item=$item[ $this->map['field'] ];
        }
        return self::$columns[ $table ];
    }

    /**
     * 获取表的主键列名。如果不存在主键则返回唯一索引的列名。
     * @param bool $flag true 只获取主键,false 主键和唯一索引，
     * @return array|$this
     */
    public function primary($primary=null, $flag=false)
    {
        $table =  $this->table;
        if( !empty($info) )
        {
            self::$primaries[ $table ]=$primary;
            return $this;
        }

        if( !isset( self::$primaries[$table] ) )
        {
            $key=$this->map['index'];
            $type = $flag===true ? 'PRI' : 'PRI,UNI';
            self::$primaries[$table]=$this->info( $table );
            self::$primaries[$table]=array_filter(self::$primaries[$table],function($item)use($type,$key){
                return stripos($type,$item[ $key ])!==false;
            });

            if( !$type )
            {
                usort( self::$primaries[$table], function($a,$b) use( $key )
                {
                    $a=!empty($a[ $key ]) ? (stripos($a[ $key ],'PRI')===false ? 1 : 0) : 2 ;
                    $b=!empty($b[ $key ]) ? (stripos($b[ $key ],'PRI')===false ? 1 : 0) : 2 ;
                    return $a==$b ? 0 : ( $b > $a ? -1 : 1);
                });
            }

            foreach( self::$primaries[$table] as & $item )
            {
                $item = $item[ $this->map['field'] ];
            }
        }
        return (array)self::$primaries[$table];
    }

} 