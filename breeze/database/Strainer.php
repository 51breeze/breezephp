<?php

namespace breeze\database;
use breeze\core\Error;
use breeze\core\Lang;

/**
 * sql 筛选器
 * Class Strainer
 * @package breeze\database
 */

class Strainer
{

    //=========================================
    //  定义过滤器名
    //=========================================

    /**
     * 等于比较
     */
    const EQUAL          ='EQUAL';
    
    /**
     * 不等比较
     */
    const NOT_EQUAL        ='NOT_EQUAL';
    
    /**
     * 大于比较
     */
    const GT             ='GT';
    
    /**
     * 小于比较
     */
    const LT             ='LT';
    
    /**
     * 小于等于比较
     */
    const LT_EQUAL       ='LT_EQUAL';
    
    /**
     * 大于等于比较
     */
    const GT_EQUAL       ='GT_EQUAL';
    
    /**
     * 模糊匹配
     */
    const LIKE           ='LIKE';
    
    /**
     * 非模糊匹配
     */
    const NOT_LIKE       ='NOT_LIKE';
    
    /**
     * 正则匹配
     */
    const REGEXP         ='REGEXP' ; 
    

    /**
     * 非正则匹配
     */
    const NOT_REGEXP     ='NOT_REGEXP' ;
    
    
    /**
     * 不存在
     */
    const NOT_EXISTS     ='NOT_EXISTS';
    
    /**
     * 存在
     */
    const EXISTS         ='EXISTS';

    /**
     * 比较两个字符是否完全相等
     */
    const STRCMP         ='STRCMP';
    
    /**
     * 包括是
     */
    const IN             ='IN';

    /**
     * 包括非
     */
    const NOT_IN         ='NOT_IN';
    
    /**
     * 按位左移
     */
    const BLS            ='BLS' ;
    
    /**
     * 按位右移
     */
    const BRS            ='BRS';
    
    /**
     * 按位异或
     */
    const BXOR           ='BXOR';
    
    /**
     * 按位或
     */
    const BOR            ='BOR';
    
    /**
     * 按位与
     */
    const BAND           ='BAND';


    /**
     * sql筛选器
     */
    private static $strainer=array(
        'EQUAL'=> '=',
        'NOT_EQUAL'=>'!=',
        'GT'=>'>',
        'LT'=>'<',
        'LT_EQUAL'=>'<=',
        'GT_EQUAL'=>'>=',
        'LIKE'=>'LIKE',
        'NOT_LIKE'=>'NOT LIKE',
        'REGEXP'=>'REGEXP' ,
        'NOT_REGEXP'=>'NOT REGEXP' ,
        'NOT_EXISTS'=>'NOT EXISTS(value)',
        'EXISTS'=>'EXISTS(value)',
        'STRCMP'=>'STRCMP(field,value)',
        'IN'=>'IN(value)',
        'NOT_IN'=>'NOT IN(value)',
        'BLS'=>'<<' ,
        'BRS'=>'>>',
        'BXOR'=>'^',
        'BOR'=>'|',
        'BAND'=>'&',
    );

    /**
     * 获取指定名称的过滤器
     * @param $name 过滤器名
     * @return string
     */
    public static function get( $name )
    {
        $name=strtoupper($name);
        if( !isset( self::$strainer[$name] ) )
            throw new Error( Lang::info(2009) );
        return self::$strainer[$name];
    }

    /**
     * 判断是否存在指定名称的过滤器
     * @param $name 过滤器名
     * @return bool
     */
    public static function exists( $name )
    {
       $name=strtoupper($name);
       return isset( self::$strainer[$name] );
    }
    
}



?>