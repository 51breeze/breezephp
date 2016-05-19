<?php
namespace breeze\interfaces;

interface IRecord
{
    
    /**
     * 更新数据集，调用此方法如同操作数据库中的 update 语句。<br/>
     * 如需要同时更新多个表可以使用连接方法 join()。
     * 更新多个表数据需要在列名中指定对应的表名如：array('test.column'=>'value',...);
     * @param array $data 要更新的数据集，可以是一个二维数组来更新多条数据。
     * @param string $primary 指定数据字段中的主键。更新多条数据时必须指定，如果没有指定系统会默认从表结构中获取。
     * @return boolean 如果成功返回 true ,失败返回 false
     */
    public function set( array $data, $primary='' );


    /**
     * 保存数据集。
     * @param array $data 要保在的数据集
     * @param string $primary 主键名
     * @return boolean
     */
    public function save( array $data , $primary='' );

    /**
     * 在添加数据集时，当指定的主键已经存在时更新指定的数据。
     * @param string $primary 主键名
     * @param array $update 要更新的数据
     * @return \breeze\database\Database
     */
    public function on( $primary , array $update=null );

    /**
    * 获取数据集。 <br/>
    * @param Mixed $type=Database::FIELD 如果是布尔值则按照字段对值的形式返回单行数据。
    * @param boolean $single=false
    * @return mixed
    */
    public function get($type=Database::FIELD, $single=false);
    
    /**
     * 以数组的方式添加数据集。
     * @param array $data 要指定的数据集 array('column'=>'value',...) 或者是 array( array('column'=>'value',...),... )
     * @return boolean 如果成功返回 true ,失败返回 false
     */
    public function add( array $data );
    
    /**
     * 删除指定的数据集
     * @return boolean 如果成功返回 true ,失败返回 false
     */
    public function remove();

    /**
     * 清空表中的所有数据
     * @param string $table=null
     * @return boolean 如果成功返回 true ,失败返回 false
     */
    public function clean( $table=null );

    /**
     * 复制表中的数据到指定的表中
     * @param string $to 将数据复制到这个表
     * @return boolean 如果成功返回 true ,失败返回 false
     */   
    public function copy( $to );

}

?>