<?php

namespace breeze\interfaces;
use breeze\database\Strainer;

interface IActive
{
    /**
     * 指定要操作的表
     * @param string $table
     * @return \breeze\database\Database
     */
    public function table( $table );

    /**
     * 需要获取哪些字段的数据
     * @param string $field
     * @return \breeze\database\Database
    */
    public function columns( $columns );

    /**
     * 指定条件查询
     * @param mixed $column 指定字段允许是一数组。
     * @param mixed $value 指定要进行比较的值允许是一个数组。
     * @param string $strainer=EQUAL 要使用的过滤器
     * @param string $type=‘AND’ 在使用比较多个条件时是使用 "AND | OR | <>" 的表达式。
     * @return \breeze\database\Strainer|\breeze\database\Database
    */
    public function where( $column=null ,$value=null ,$strainer=Strainer::EQUAL, $type='AND' );

    /**
     * 指定排序的方式。此方法与 group() 的 $rollup 参数是相互排斥的。
     * @param mixed $field 要指定哪些字段按特定的值进行排序（ 允许是一个数组的形式指定 ）。如果指定的数组是一个数字索引则会统一使用  $type 提供的值来排序数据据。
     * @param string $type=‘ASC’ 进行排序的方式 。 如果指定的是一个数字索引的数组则会把相应下标的值赋值给 $field 为数组的排序方式，否则此值会被忽略。
     * @return \breeze\database\Database
    */
    public function order( $column, $type='ASC' );
    
    /**
     * 指定分组的方式
     * @param string $column 要按哪些字段进行分组（ 允许是一个数组 ）
     * @param string $order  排序
     * @param boolean $rollup 使用此参数时需要注意与 order() 方法,这两个值是相互排斥的。
     * @return \breeze\database\Database
     */
    public function group( $column, $order='ASC', $rollup=false );
    
    /**
     * 对分组后指定筛选条件
     * @return \breeze\database\Database
     */
    public function having( $condition, $expre='AND' );
    
    /**
     * 使用联式操作
     * @param string $table
     * @param mixed  $correlation 关联字段
     * @param string $type='LEFT' 链式的类型  left right outer inner
     * @return \breeze\database\Database
    */
    public function join( $table, $correlation , $type='left' );
    
    /**
     * 联合查询
     * @param  mixed $union=null
     * @return \breeze\database\Database
     */
    public function union( $union=null );
    
    /**
     * 结束联合查询
     * @return \breeze\database\Database
     */
    public function endUnion();
    
    /**
     * 去掉重复的行
     * @return \breeze\database\Database
     */
    public function distinct();
    
    /**
     * 指定执行特定语句是的延迟状态
     * @return \breeze\database\Database
     */
    public function delay();

    /**
     * 指定执行特定语句时是否忽略错误
     * @return \breeze\database\Database
     */
    public function ignore();
    
    /**
     * 加快删除操作 只针对 MyISAM 表
     * @return \breeze\database\Database
     */
    public function quick();
    
    /**
     * 是否禁止缓存结果集，针对数据库而言。
     * @param boolean $enable 如果是 true 缓存结果集
     * @return \breeze\database\Database
     */
    public function cache( $enable=true );

    /**
     * 调用存储过程
     * @return \breeze\database\Database
     */
    public function procedure($name,$param);
    
    /**
     * 在读取表时加锁
     * @return \breeze\database\Database
     */
    public function lock();

    /**
     * 指定数据行的偏移量
     * @param number $count=1000
     * @param number $offset=0
     * @return \breeze\database\Database
     */
     public function limit($count=1000,$offset=0);
     
     /**
      * 指定引用的表名。
      * 此方法对于删除多个表数据时非常有用
      * @param string $table 指定要引用的表名，允许是一个数组。
      * @return \breeze\database\Database
      */
     public function using( $table );
}

?>