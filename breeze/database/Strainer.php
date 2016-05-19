<?php

namespace breeze\database;
use breeze\core\Error;
use breeze\core\Lang;
use breeze\events\DatabaseEvent;

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
    const NOT_STRCMP     ='NOT_STRCMP';

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
    private static $map=array(
        'EQUAL'=> ' = ',
        'NOT_EQUAL'=>' != ',
        'GT'=>' > ',
        'LT'=>' < ',
        'LT_EQUAL'=>' <= ',
        'GT_EQUAL'=>' >= ',
        'LIKE'=>' LIKE ',
        'NOT_LIKE'=>' NOT LIKE ',
        'REGEXP'=>' REGEXP ' ,
        'NOT_REGEXP'=>' NOT REGEXP ' ,
        'NOT_EXISTS'=>' NOT EXISTS(value) ',
        'EXISTS'=>' EXISTS(value) ',
        'STRCMP'=>' STRCMP(field,value) ',
        'NOT_STRCMP'=>' !STRCMP(field,value) ',
        'IN'=>' IN(value) ',
        'NOT_IN'=>' NOT IN(value) ',
        'BLS'=>' << ' ,
        'BRS'=>' >> ',
        'BXOR'=>' ^ ',
        'BOR'=>' | ',
        'BAND'=>' & ',
    );

    /**
     * @private
     */
    private $database;

    /**
     * @private
     */
    private $data=array();

    /**
     * Contructs.
     */
    public function __construct( Database $database )
    {
        $this->database=$database;
    }

    /**
     * 结束当前过滤器操作。
     * @return Database
     */
    public function end()
    {
        return $this->database;
    }

    /**
     * 清除过滤器。
     * @return Strainer
     */
    public function clean()
    {
        $this->data=array();
        return $this;
    }

    /**
     * 添加一个需要筛选的数据项
     * @param string $column
     * @param string $value
     * @param string $strainer
     * @param string $logical
     * @return Strainer
     */
    public function setItem($column=null,$value=null,$strainer=Strainer::EQUAL,$logical='AND')
    {
        if( is_array($column) )
        {
            foreach($column as $field=>$val )
            {
                array_push( $this->data, array($field,$val,$strainer,$logical) );
            }

        }else
        {
            array_push( $this->data, array($column,$value,$strainer,$logical) );
        }
        return $this;
    }

    /**
     * 获取已经设置的过滤器
     * @return array
     */
    public function getItems()
    {
        return $this->data;
    }

    /**
     * 是否有设置过滤器
     * @return array
     */
    public function hasItem()
    {
        return !empty($this->data);
    }

    /**
     * @return Strainer
     * @see Strainer::EQUAL
     */
    public function eq($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::EQUAL,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::NOT_EQUAL
     */
    public function noteq($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::NOT_EQUAL,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::IN
     */
    public function in($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::IN,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::NOT_IN
     */
    public function notin($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::NOT_IN,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::EXISTS
     */
    public function is($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::EXISTS,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::NOT_EXISTS
     */
    public function notis($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::NOT_EXISTS,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::STRCMP
     */
    public function strcmp($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::STRCMP,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::NOT_STRCMP
     */
    public function notstrcmp($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::NOT_STRCMP,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::REGEXP
     */
    public function pegexp($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::REGEXP,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::REGEXP
     */
    public function notpegexp($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::NOT_REGEXP,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::LIKE
     */
    public function like($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::LIKE,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::NOT_LIKE
     */
    public function notlike($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::NOT_LIKE,$logical);
    }


    /**
     * @return Strainer
     * @see Strainer::GT
     */
    public function gt($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::GT,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::GT_EQUAL
     */
    public function gteq($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::GT_EQUAL,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::LT
     */
    public function lt($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::LT,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::LT_EQUAL
     */
    public function lteq($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::LT_EQUAL,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::BLS
     */
    public function bls($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::BLS,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::BRS
     */
    public function brs($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::BRS,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::BXOR
     */
    public function bxor($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::BXOR,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::BOR
     */
    public function bor($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::BOR,$logical);
    }

    /**
     * @return Strainer
     * @see Strainer::BAND
     */
    public function band($column,$value,$logical='AND')
    {
        return $this->setItem($column,$value,self::BAND,$logical);
    }

    /**
     * 将筛选器转换中字符串的表达形式
     * @param  boolean $logical=false 是否在返回字符串前面加上首个筛选条件的逻辑符。‘and’ 或者 ‘or’
     * @return string;
     */
    public function toString()
    {
        $data='';
        foreach( $this->data as $item )
        {
            $issub=false;
            list($column,$value,$strainer,$logical) = $item;
            if( $column instanceof Strainer )
            {
                $val = $column->toString();
                $issub=true;

            }else
            {
                $val=$this->combine( $column,$value,$strainer,$logical);
            }

            $logical=$this->database->logical( $logical ,'AND');
            if( !empty($val) )
            {
                $val = $issub===true ? sprintf('( %s )', $val ) : $val;
                $data.= empty($data)?  $val : $logical.$val ;
            }
        }
        return $data;
    }

    /**
     * 组合查询条件
     * @param string|array $column 列名
     * @param mixed $value 需要过滤的值
     * @param string $strainer 过滤器
     * @return array|mixed|string
     * @throws \breeze\core\Error
     */
    private function combine($column,$value=null,$strainer='EQUAL')
    {
        //确保 $column 已定义并且是一个标量
        if( empty($column) || !is_string($column) )
        {
            throw new Error( Lang::info(2010) );
        }

        //如果值为 null 则认为不需要再解析
        if( is_null($value) )
            return $column;

        $strainer=strtoupper($strainer);
        if( !isset( self::$map[$strainer] ) )
            throw new Error( Lang::info(2009) );

        $strainer= self::$map[$strainer];
        $column=$this->database->backQuote($column);
        $value= $this->parseValue( $this->database->escape($value) , $strainer);

        //替换列名
        $strainer=( stripos($strainer,'field') !== false )  ? str_replace('field', $column, $strainer ) : $column.$strainer;
        //替换值
        $strainer=( stripos($strainer,'value') !== false )  ? str_replace('value', $value , $strainer ) : $strainer.$value;
        return $strainer;
    }

    /**
     * @private
     */
    private function parseValue( $value, $filtrate )
    {
        if( $this->database->isUsing($value) )
        {
            $value= $this->database->backQuote( $value );
            return $value;
        }
        if( is_array($value) )
        {
            $value=stripos($filtrate,'in')!==false ? implode("','", $value ) : implode('', $value ) ;
        }
        return sprintf('\'%s\'',$value);
    }
    
}



?>