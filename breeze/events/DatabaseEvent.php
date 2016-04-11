<?php
namespace breeze\events;

class DatabaseEvent extends Event
{
    /**
     * 在查询之前
     */
    const BEFORE='before';
    
    /*
     * 在查询之后
     */
    const AFTER='after';
    
    /**
     * SQL变更
     */
    const SQL_CHANGE='sqlChange';
    
    /**
     * 已经设置的的sql语名
     */
    public $sql;
    
    /**
     * 查询后的原型结果。
     */
    public $result;

    /**
     * 当前需要设置的属性名
     */
    public $name;
    
    /**
     * 当前需要设置的值
     */
    public $value;
    
    /**
     * 在拼接变量时所使用的分隔符
     */
    public $separate;
    
    /**
     * 是否使用追加的方式来设置变量否则将履盖变量
     */
    public $append;

}

?>