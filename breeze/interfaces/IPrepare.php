<?php
namespace breeze\interfaces;

interface IPrepare
{
    
    /*
    * 执行一条预处理语句
    * @param string $sql 要执行的 sql 语句
    * @retrun mixed
    */
    public function prepare( $sql='' );
    
    
    /*
     * 绑定给预处理的参数
    * @param $key 参数名
    * @param $value 需要绑定的值
    */
    public function bindParam( $key ,$value );
    
    /*
    * 绑定预处理的结果到 php 变量。
    * @param $key 参数名
    * @param $value 需要绑定的值
    */
    public function bindResult();
 
}

?>