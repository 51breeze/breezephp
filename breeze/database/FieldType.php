<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-5-9
 * Time: 下午8:46
 */

namespace breeze\database;

use breeze\core\Error;

class FieldType
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

   const TYPE_UNSIGNED= 'unsigned';
   const TYPE_ZEROFILL= 'zerofill';
   const TYPE_ASCII= 'ascii';
   const TYPE_UNICODE= 'unicode';
   const TYPE_BINARY= 'binary';

   const INDEX_PRIMARY='primary';
   const INDEX_UNIQUE='unique';
   const INDEX_KEY='key';


   public static $index=array(
       'primary'=>'primary key',
       'unique'=>'unique key',
       'key'=>'key',
   );

   public static $map=array(

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

    public function type( $name , $length='', $decimals='', $options='' )
    {
       $args = func_get_args();
       $method =  array_shift( $args );
       $method =  strtolower($method);

       static $type = null;
       if( $type===null )
       {
          $type = array( self::TYPE_UNSIGNED,self::TYPE_ZEROFILL,self::TYPE_ASCII,self::TYPE_UNICODE,self::TYPE_BINARY);
       }

       if( isset( self::$map[ $method ] ) )
       {
           $end =  array_pop( $args );
           if( !in_array($end,$type) )
           {
               array_push($args,$end);
               $this->type = sprintf($method, implode(',', $args) );
           }
           $this->type =  !empty($args) ? sprintf($method, implode(',', $args), $end ) : sprintf($method,$end );
       }
       throw new Error('not exists column type.',2100);
    }

    public function index( $name )
    {
        if( isset( self::$index[ $name ]) )
        {
            $this->index = self::$index[ $name ];
        }
        return $this;
    }

    public function demand( $flag=true )
    {
       $this->demand= $flag ? 'not null' : 'null';
    }

    public function defaults()
    {

    }

} 