<?php
namespace breeze\interfaces;

use breeze\database\Prepare;
use breeze\database\Database;

interface IAdapter
{
    
    /**
     * 连接数据库
     * @return Adapter
     */
    public function connet();

    /**
     * 关闭数据库
     * @return boolean
     */
    public function close();

    
    /**
     * 开启事务处理
     * @return Adapter
     */
    public function transaction();

    
    /**
     * 提交事务
     * @return Adapter
     */
    public function commit();

    /**
     * 回滚事务
     * @return Adapter
     */
    public function rollback();

    
    /**
     * 获取数据库产生的错误号
     * @return Number
     */
    public function getErrorCode();
    
    /**
     * 获取数据库产生的错误信息
     * @return string
     */
    public function getErrorInfo();

    /**
     * 获取最后受影响的 ID , 这个ID受主键的影响，只有设置的主键才能获取到正确的值。
     */
    public function getLastInsertId();

    /**
     * 发送一条预处理语句
     * @return Prepare
     */
    public function prepare( $sql );

    /**
     * 执行查询语句
     * @return IResult
     */
    public function query( $sql );

    /**
     * 执行sql语句
     * @return boolean
     */
    public function execute( $sql );
    
    
    /**
     * 最近一次执行查询的sql语句
     * @return String
     */
    public function lastQuery();
    
    /**
     * 清空表数据
     * @param string $table
     */
    public function truncate( $table='' );
    
    /**
     * 转义一个字符串
     * @param string $val
     * @return string
     */
    public function escape( $val='' );
    
    /**
     * 从一个查询集中获取数据
     * @param int $type
     * @param boolean $rows=true 以二维数组形式返回, false 只返回单行数据
     * @return mixed
     */
    public function fetch( $type=Database::FIELD, $rows=true );
    
}

?>