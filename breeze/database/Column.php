<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-5-9
 * Time: 下午8:46
 */

namespace breeze\database;

use breeze\core\Error;

class Column
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


    public function __construct()
    {

    }


    public function __call()
    {
       $args = func_get_args();
       $method =  array_shift( $args );

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
               return sprintf($method, implode(',', $args) );
           }
           return !empty($args) ? sprintf($method, implode(',', $args), $end ) : sprintf($method,$end );
       }
       throw new Error('not exists column type.',2100);
    }

/* [(length)] [UNSIGNED] [ZEROFILL]
| SMALLINT[(length)] [UNSIGNED] [ZEROFILL]
| MEDIUMINT[(length)] [UNSIGNED] [ZEROFILL]
| INT[(length)] [UNSIGNED] [ZEROFILL]
| INTEGER[(length)] [UNSIGNED] [ZEROFILL]
| BIGINT[(length)] [UNSIGNED] [ZEROFILL]
| REAL[(length,decimals)] [UNSIGNED] [ZEROFILL]
| DOUBLE[(length,decimals)] [UNSIGNED] [ZEROFILL]
| FLOAT[(length,decimals)] [UNSIGNED] [ZEROFILL]
| DECIMAL(length,decimals) [UNSIGNED] [ZEROFILL]
| NUMERIC(length,decimals) [UNSIGNED] [ZEROFILL]
| DATE
| TIME
| TIMESTAMP
| DATETIME
| CHAR(length) [BINARY | ASCII | UNICODE]
| VARCHAR(length) [BINARY]
| TINYBLOB
| BLOB
| MEDIUMBLOB
| LONGBLOB
| TINYTEXT [BINARY]
| TEXT [BINARY]
| MEDIUMTEXT [BINARY]
| LONGTEXT [BINARY]
| ENUM(value1,value2,value3,...)
| SET(value1,value2,value3,...)*/


} 