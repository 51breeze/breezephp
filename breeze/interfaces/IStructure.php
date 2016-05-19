<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-5-13
 * Time: 下午1:14
 */

namespace breeze\interfaces;

use breeze\database\structure\Column;
use breeze\database\structure\Index;

interface IStructure
{

    /**
     * 获取或者设置一个列名的结构
     * @param string|Index $name 如果是一个字符串则表示获取指定名称的索引,如果是一个索引结构表示添加一个新的索引
     * @return IStructure|Index
     * @throws \breeze\core\Error
     */
    public function index( $name , $remove=false );

    /**
     * 获取或者设置一个列名的结构
     * @param string|Column $name 如果是一个字符串则表示获取指定名称的列,如果是一个列的结构类型表示添加一个新的列
     * @return IStructure|Column
     * @throws \breeze\core\Error
     */
    public function column( $name , $remove=false );

    /**
     * 删除指定的索引
     * @param Index $index
     * @return IStructure
     */
    public function removeIndex( Index $index );

    /**
     * 删除一个列的结构
     * @param Column $column
     * @return IStructure
     */
    public function removeColumn( Column $column );

    /**
     * 把指定的列设为主键或者获取当前主键的列
     * @param Column $column
     * @return IStructure|Column
     */
    public function primary( Column $column=null );

    /**
     * 把指定的列设为自增或者获取当前自增的列
     * 自增列的字段类型必须为一个数字整型
     * @param $column
     * @return e|Column
     */
    public function increment( Column $column=null );

    /**
     * 获取此结构的表名
     * @return null
     */
    public function table();

    /**
     * 获取表的所有字段名
     * @return array
     */
    public function fields();

    /**
     * 获取表结构的所有索引信息
     * @return array|mixed
     */
    public function & getIndexInfo();

    /**
     * 获取表的结构列信息
     * @return array
     */
    public  function & getColumnInfo();

    /**
     * 获取设置字符集
     * @param null $charset
     * @return IStructure|string
     */
    public function charset( $charset=null );

    /**
     * 获取设置表的引擎
     * @param null $engine
     * @return IStructure|string
     */
    public function engine( $engine=null );

    /**
     * 同步到数据库
     * @return bool
     */
    public function save();

    /**
     * 输出表的结构
     * @return string
     */
    public function toString();

} 