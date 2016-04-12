<?php

namespace breeze\database;

use breeze\core\Lang;
use breeze\core\Singleton;
use breeze\interfaces\IRecord;
use breeze\core\Error;
use breeze\interfaces\IAdapter;
use breeze\core\EventDispatcher;
use breeze\interfaces\IActive;
use breeze\events\DatabaseEvent;
use breeze\utils\Utils;

/**
 * Database 实现了IRecord接口。是 Active的基类。
 * 一个完整的数据库驱动必须 要实现 IAdapter,IRecord,IActive接口。
 * @author Administrator
 */
abstract class Database extends EventDispatcher implements IRecord,IAdapter,IActive
{
    /**
     * 以字段对值的数组形式返回结果
     */
    const FIELD=1;
    
    /**
     * 以索引对值的数组形式返回结果
     */
    const INDEX=2;
    
    /**
     * 以对象的形式返回结果
     */
    const OBJECT=3;

    /**
     * 是否开启添加反单引号来解析字段或者表名
     */
    public $quoteEnable=false;
    
    /**
     * @protected
     * 连接数据库的参数对象
     */
    protected $param;

    /**
     * 反单引号 <br/>
     * 针对某个数据库而言使用它能把一个字符串解析成一个变量。
     * 由子类定义
     */
    protected $quotation='`';
    
    /**
     * 保存每个表中列的详细信息
     */
    protected $columnsInfo=array();

    /**
     * 保存每个表中的列名
     */
    protected $columns;

    /**
     * 获取sql语法
     * 不同的数据库可能有自己的语法格式，为了兼容各类数据库的语法请在子类中覆盖此方法。
     */
    protected $SQLSyntax=array(
    
            //获取
            'select'  => array('select', array('distinct','columns'),'from',array('table','join','where','group','rollup','having','union','order','limit','procedure','lock')),
    
            //删除
            'delete'  => array('delete', array('delay','quick','ignore'),'from',array('table','using','join','where','order','limit')),

            //更新
            'update'  => array('update', array('delay','ignore','table','join'),'set',array('value','where','order','limit')),
    
            //插入
            'insert'  => array('insert', array('delay','ignore'),'into',array('table'),'(',array('columns'),')','values',array('value','on')),
    
            //复制
            'copy'    => array('insert', array('delay','ignore'),'into',array('table'),'(',array('columns'),')',array('value','on')),
    );

    /**
     * Constructs.
     * @param Parameter $param
     */
    public function __construct( Parameter $param )
    {
        $this->param=$param;
    }


    protected $activated=array();

    /**
     * 返回添加前缀和转义后的表名
     * @param string $table
     * @return string
     */
    public function getTable( $table )
    {
        if( is_array( $table ) || is_object( $table ) )
        {
            foreach ($table as & $item )
            {
               $item=$this->getTable( $item );
            }
        }
        if( is_string($table) )
        {
            $prefix = $this->param->prefix;
            $suffix = $this->param->suffix;
            $table = ltrim($table,  $prefix );
            $table = rtrim($table,  $suffix );
            return $this->adjust( $table ,$prefix ,$suffix );
        }
        return $table;
    }

    /**
     * @private
     */
    public function logical( $val='' , $default='AND' )
    {
        if( empty($val) || !is_scalar($val) )
            $val=$default;
    
        $val=preg_replace('/^\s*(AND|OR|NOT)\s*$/i', ' \\1 ', $val);
        return strtoupper($val) ;
    }

    /**
     * 添加反引号
     * @param $str
     * @return array|mixed|string
     */
    public function backQuote( $str )
    {
        if( $this->quoteEnable !==true || empty($this->quotation) )
            return $str;
        if( is_array($str) )
        {
            foreach( $str as & $item )
            {
                $item=$this->backQuote( $item );
            }
        }else if( !empty($str) && is_scalar($str) )
        {
            $str=str_replace($this->quotation,'',trim($str));
            if( strpos($str,'.')!==false )
            {
                $arr=explode('.', $str);
                $str=array();
                foreach ($arr as $item )
                {
                    $str[]=$this->backQuote($item);
                }
                return implode('.', $str);
            }else
            {
                return $this->quotation.$str.$this->quotation;
            }
        }
        return $str;
    }

    /**
     * @private
     */
    public function isUsing( & $value='' )
    {
        if( is_string($value) && preg_match('/^\s*@\s*using\s*\(.*?\)\s*$/i',$value)>0 )
        {
            $value=preg_replace('/^\s*@\s*using\((.*?)\)\s*$/i','\\1',$value);
            return true;
        }
        return false;
    }

    //==============================================================
    // Protected Method
    //==============================================================

    /**
     * @private
     */
    protected function getColumnsInfo( $table, $full=true )
    {
        if( !isset($this->columnsInfo[ $table ]) )
        {
            $full=$full===true ? 'FULL' : '';
            $this->query( sprintf('SHOW %s COLUMNS FROM %s',$full ,$table ) );
            $this->columnsInfo[ $table ]=$this->fetch();
        }
        return $this->columnsInfo[ $table ];
    }

    /**
     * @private
     */
    protected function getColumns( $table )
    {
        if( !isset($this->columns[ $table ]) )
        {
            $this->columns[ $table ]=$this->getColumnsInfo($table);
            foreach($this->columns[ $table ] as & $item )
                $item=$item['Field'];
        }
        return $this->columns[ $table ];
    }

    /**
     * @private
     */
    private $uniqueKeys=array();

    /**
     * 从数据库中获取指定表的唯一索引的列名。
     * @param string $table
     * @param string $priority='PRI' 优先返回此指定索引类型的列
     */
    protected function getTablePrimary( $table, $priority='PRI' )
    {
       if( empty($table) || !is_string($table) )
           return '';

       if( !isset( $this->uniqueKeys[$table] ) )
       {
           $this->uniqueKeys[$table]=$this->getColumnsInfo( $table );
           $this->uniqueKeys[$table]=array_filter($this->uniqueKeys[$table],function($item){
              return stripos('PRI,UNI',$item['Key'])!==false;
           });
       }

       if( !stripos('PRI,UNI',$priority) && is_array($this->uniqueKeys[$table]) )
       {
           usort($this->uniqueKeys[$table], function($a,$b) use($priority){
                $a=!empty($a['Key']) ? (stripos($a['Key'],$priority)===false ? 1 : 0) : 2 ;
                $b=!empty($b['Key']) ? (stripos($b['Key'],$priority)===false ? 1 : 0) : 2 ;
                return $a==$b ? 0 : ( $b > $a ? -1 : 1);
           });
           return @$this->uniqueKeys[$table][0]['Field'];
       }
       return $this->uniqueKeys[$table];
    }

    /**
     * 获取多个表中同时存在主键(唯一索引)的列名
     * @param $table
     * @param string $priority
     * @return string
     */
    protected function getMultiTablePrimary($table,$priority='PRI')
    {
        if( empty($table) )
            return '';

        $tableList=is_string($table) ? explode(',',$table) : $table;
        $num=count($tableList);
        $primary=array();

        if( $num == 1 )
        {
            return $this->getTablePrimary( $tableList[0],$priority);
        }

        for( $i=0; $i < $num ; $i++ )
        {
            $tablename='table'.$i;
            $primary[]='$'.$tablename;
            $$tablename=$this->getTablePrimary( $tableList[ $i ],'' );
            $item=array();
            foreach( $$tablename as $val )
            {
                $item[ $val['Field'] ]=strtoupper($val['Field']).'_'.$val['Key'];
            }
            $$tablename=$item;
        }
        eval( sprintf( '$primary=array_intersect(%s);' ,implode(',',$primary) ) );
        if( !empty($primary) )
        {
            foreach( $primary as $field=>$item )
            {
                if( stripos( $item, $priority )!==false )
                    return $field;
            }
            $primary=@array_keys($primary);
            return $primary[0];
        }
        return '';
    }

    /**
     * @private
     */
    protected function getAlias( & $str='', $clear=true ,$default='' )
    {
        if( preg_match('/\s+(as\s+)?(\w+)$/i', $str , $match ) > 0 )
        {
            $clear===true && $str=preg_replace('/\s+(as\s+)?\w+$/is', '', $str );
            return $match[2];
        }
        return $default;
    }
    
    /**
     * @private
     */
    protected function adjust( $str, $prefix='', $suffix='')
    {
        if(strpos($str,'"')!==false || strpos($str,"'")!==false )
            return $str;

        $str=trim( $str );
        $alias=$this->getAlias( $str );
        !empty($alias) && $alias=' AS '.$alias;

        // database.table or table.column
        if( strpos($str,'.')!==false )
        {
           list($owner,$property)=explode('.', $str,2);
           $owner=$this->backQuote($owner);
           $property=$this->backQuote($prefix.$property.$suffix);
           return sprintf('%s.%s%s',$owner,$property,$alias);
        }
        $str=$this->backQuote($prefix.$str.$suffix);
        return $str.$alias;
    }

    /**
     * @private
     */
    protected $sql=array();
    
    /**
     * @private
    */
    protected $index=0;
    
    /**
     * @private
     */
    protected $isUnion=false;

    /**
     * @private
     * 在内部调用 IActive 接口时，代替每个接口在操作setSql方法中的append的参数。
     * 在操作sql时 $sqlClear = true 时将清空对应$key的所有sql语句。
     * 在每一次sql操作之后都会将$sqlClear = false,这意味着不再代替 append 的功能除非在每次操作sql时都将$sqlClear = true。
     */
    protected $sqlClear=false;
    
    /**
     * 设置sql语句
     * @param string $key
     * @param string $value
     * @param mixed $separate
     * @param boolean $append
     */
    protected function setSql($key='',$value='', $separate=null, $append=true ,$dispatch=true)
    { 
        $index=( $this->isUnion===true ) ?  $this->index : 0 ;
        
        if( !isset( $this->sql[ $index ] ) )
           $this->sql[ $index ]=array();
        
        $data='';

        //引用变量在内存中的地址
        if( empty($key) )
        {
            $data = & $this->sql[ $index ];
        }else
        {
            if( !isset( $this->sql[ $index ][$key] ) )
              $this->sql[ $index ][$key]='';
            $data = & $this->sql[ $index ][ $key ];
        }

        //是否为追加模式。
        $append &= $this->sqlClear===false;

        //是否需要分发事件
        if( $dispatch===true && $this->hasEventListener( DatabaseEvent::SQL_CHANGE ) )
        {
            $event=new DatabaseEvent( DatabaseEvent::SQL_CHANGE );
            $event->name=$key;
            $event->data=& $data;
            $event->append= & $append;
            $event->value= & $value;
            $event->separate= & $separate;
            $this->dispatchEvent( $event );
            if( $event->prevented===true )
                return;
        }

        //是决定使用追加还是使用履盖
        if( $append && !empty($data) )
        {
            /**
             * 在已设定的变量中使用分隔符。
             * 把分隔符添加到已设定变量（字符串或者数组）的末尾。
             */
            if( is_scalar($value) && is_scalar($data) )
            {
                !is_null($separate) && $data.=$separate;
                $data.= $value;
            }else
            {
                $data=(array)$data;
                !is_null( $separate ) && $data=array_push($data,$separate);
                is_array($value) ? ( $data=$key=='bind' ? $this->merge($data,$value) : array_merge($data,$value) ): array_push( $data , $value );
            }

        }else
        {
            $data=$value;
        }
        $this->sqlClear=false;
    }

    /**
     * @private
     */
    protected  function merge( & $data=array(),$value )
    {
       if( !is_array($data) )
           $data=array();

       if( !is_scalar($value) && !empty($value) )
       {
           foreach( $value as $key=>& $item )
           {
               if( is_array($item) && isset($data[ $key ]) )
               {
                   //$this->merge( $data[ $key ] ,$item);
                   if( !isset($data[ $key ][0]) )
                       $data[ $key ]=array(  $data[ $key ] );
                   $data[ $key ][]=$item;

               }else
               {
                   if( is_numeric($key) && isset($data[ $key ]) )
                   {
                       if( !is_array($data[ $key ]) )
                         $data[ $key ]=(array)$data[ $key ];
                       array_push($data[ $key ],$item);
                   }else
                   {
                      //$data[$key]=(array)$data[$key];
                      $data[$key]=$item;
                   }
               }
           }
       }else
       {
           $data[] =$value;
       }
       return $data;
    }

    /**
     * 获取已经设置的sql语句
     * @param string $key
     * @param string $default 如果为空时默认认返回值。
     * @return mixed 返回数据的引用
     */
    protected function & getSql( $key , $default='' )
    {
        $index= $this->isUnion===true  ? $this->index : 0 ;
        $data=$default;
        if( isset( $this->sql[$index] ) )
          $data=&$this->sql[$index];
        if( is_null($key) )
            return $data;
        if( isset( $data[$key] ) )
            $data= & $data[$key];
        else
            return $default;
        return $data;
    }
    
    /**
     * @private
     */
    protected function isSql( $key='' )
    {
        $index= $this->isUnion===true  ? $this->index : 0 ;
        $obj=isset( $this->sql[$index] ) ? $this->sql[$index] : '' ;
        return empty($obj) ? false : isset( $obj[$key] );
    }
    
    /**
     * @private
     * @param string $type
     * @return string
     */
    protected function createQuery( $type='')
    {
        $syntax=$this->SQLSyntax[ $type ];
        ksort( $this->sql );
        $union=array_splice( $this->sql ,1);

        //union joint
        foreach( $union as & $item  )
        {
            if( !empty($item) )
            {
                $item=$this->jointSyntax( $syntax, $item );
            }
        }

        list($sql)=$this->sql;

        /**
         * @private
         * 判断是否需要创建临时表来辅助完成工作
         * msyql 出现 union 语句时需要对 order 语句进行例外的处理
         * order 时引用的列名不能出现表名(table.id)，只能用别名来实现或者直接引用列名。
         */
        if( !empty($union) )
        {
            $sql['union']=$union;
            if( isset($sql['order']) )
            {
                $sql['order']=str_replace( $this->quotation,'',$sql['order'] );
                foreach(  $sql['columns'] as $column )
                {
                    if( stripos($sql['order'],$column)!==false )
                    {
                        $alias=sprintf('%s%s%s',$this->quotation,$this->getAlias($column,true,$column),$this->quotation);
                        $sql['order']=str_replace( $column,$alias,$sql['order'] );
                    }
                }
            }
            if( isset($sql['group']) || isset($sql['having']) )
            {
                 trigger_error( Lang::info(2002), E_USER_NOTICE );
            }
        }
        return $this->jointSyntax( $syntax ,$sql );
    }


    /**
     * @private
     */
    protected function itemToString( $key, $value )
    {

        if( empty($value) )
            return '';
        $key=strtoupper( trim($key) );
        switch ( $key )
        {
        	case 'ORDER':
            {
        	    $key='ORDER BY';
        	    break;
            }
        	case 'GROUP':
            {
                $key='GROUP BY';
        	    break;
            }
    	    case 'ON':
            {
	            $key='ON DUPLICATE KEY UPDATE';
	            break;
            }
    	    case 'USING' :
            {
                $value=is_array($value) ? implode(',',$value) : $value;
    	        break;
            }
        	case 'WHERE' :
        	{
                if( $value instanceof Whereis )
                  $value=$value->toString();
        	    break;
        	}
            case 'TABLE' :
            {
                if( is_array($value) )
                {
                    $value=$this->getTable( $value );
                    $value=implode(',',$value);
                }
                $key=null;
                break;
            }
            case 'COLUMNS' :
            {
                if( is_array($value) )
                {
                     foreach( $value as &$item )
                        $item=$this->adjust($item);
                    $value=implode(',',$value);
                }
                $key=null;
                break;
            }
            case 'HAVING':
            {
                $value=sprintf('HAVING( %s )', is_array( $value ) ? implode(' ',$value ) : $value );
                $key=null;
                break;
            }
            case 'UNION' :
            {
                $value=sprintf('UNION( %s )', is_array( $value ) ? implode( ') UNION (' , $value ) : $value );
            }
        	default:
        	    $key=null;
        }
        $value=is_array( $value ) ? @implode(' ',$value) : $value;

    	return empty($key) ? $value : $key.' '.$value;
    }

    //==============================================================
    // Private Method
    //==============================================================

    /**
     * @private
     */
    private function jointSyntax( &$syntax=array(),  $item=array() )
    {
        !isset( $item['columns'] ) && $item['columns']='*';
        $joint=array();
        foreach ( $syntax as $type )
        {
            if( is_array($type) )
            {
                //可选值
                foreach ( $type as $keys)
                {
                    if( isset( $item[$keys] ) )
                    {
                        array_push($joint, $this->itemToString( $keys,$item[$keys] ) );
                    }
                }
            }else
            {
                //必须出现的语句
                array_push( $joint, trim($type) );
            }
        }
        return implode(' ', $joint);
    } 

}

?>