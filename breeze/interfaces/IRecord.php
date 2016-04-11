<?php
namespace breeze\interfaces;

interface IRecord
{
    
    /**
     * 更新数据集，调用此方法如同操作数据库中的 update 语句。<br/>
     * 在调用此方法前必须先调用 <code>bind()</code> 的方法来设置需要更新的数据。<br/>
     * 如需要同时更新多个表可以使用连接方法 join()。更新多个表数据需要在列名中指定对应的表名如：array('test.column'=>'value',...);
     * @return boolean 如果成功返回 true ,失败返回 false
     * @see \breeze\interfaces\IRecord::bind()
     * @see \breeze\interfaces\IActive::join()
     */
    public function set();

    /**
    * 获取指定表名的数据集。 <br/>
    * 调用此方法前必须通过 table() 来设置表名。
    * @param Number $offset=0
    * @param Number $count=1000
    * @return IAdapter
    * @see \breeze\interfaces\IActive::table()
    */
    public function get( $count=1000 ,$offset=0 );
    
    /**
     * 以数组的方式添加数据集。
     * @param array $value 要指定的数据集 array('column'=>'value',...) 或者是 array( array('column'=>'value',...),... )
     * @return boolean 如果成功返回 true ,失败返回 false
     */
    public function add( array $value );
    
    /**
     * 删除数据集
     * @return boolean 如果成功返回 true ,失败返回 false
     */
    public function remove();
    
    /**
     * 复制表中的数据
     * @param string $toTable  将数据复制到这个表
     * @return boolean 如果成功返回 true ,失败返回 false
     */   
    public function copy( $toTable );

    /**
     * 绑定一组影响数据集的数据。<br/>
     * 当数据集中的列名与 $column 和 $value 相匹配时则更新 $update 中对应的数据。<br/>
     * 此接口允许你同时更新多行数据。在需要同时更新多行数据时 $update 必须是一个数字索引的二维数组并且在每行元素的数组中必须存在与 $column 相匹配的列名。并将忽略$value参数。<br/>
     * 如果在指定 $update 参数中出现了与 $column 相同的列名，则会用 $value 覆盖 $update 中的值 (更新多行数据除外)。<br/>
     * 如果只为 $column 指定了 true 其它参数都为空，则认为 $update 部分是一个引用。也就是当调用 add() 或者 copy() 方法之前调用了bind(true)，那么 $update 会由内部实现后传递需要更新的数据。<br/>
     * 如果只为 $column 指定了字符串则认为是一个主键。主键在 $update 元素中必须出,因为所有的赋值都基于主键。<br/>
     * 如果 $value 和 $update 都是数组那么两者会合并成一个数组，合并的结果由 array_mereg 返回。<br/>
     * 注意：如果$column参数是一个数组则会应用到所有的数据集，相当于更新全表。<br/>
     * @param mixed  $column 指定绑定的列名
     * @param string $value 指定绑定列的值
     * @param array  $update  需要更新的数据
     * @return \breeze\database\Database
     */
     public function bind($column, $value='' , array $update=null );
    
}

?>