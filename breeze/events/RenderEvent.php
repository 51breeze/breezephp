<?php

namespace breeze\events;

/**
 *
 * 如果缓存不在有效期内必须使用 BreezeEvent->preventDefault() 来阻止后续事务执行
 * Class RenderEvent
 * @package breeze\events
 */

class RenderEvent extends  Event
{
    /**
     * 设置缓存数据
     */
    const SET_CACHE_DATA='setCacheData';

    /**
     * 获取缓存数据
     */
    const GET_CACHE_DATA='getCacheData';

    /**
     * 判断缓存的有效期。
     */
    const IS_CACHE_EXPIRE='isCacheExpire';

    /**
     * 获取缓存的哈希值
     */
    const GET_HASH='getHash';

    /**
     * 模板是否有更新
     */
    const UPDATED='updated';

    /**
     * 注册调试信息
     */
    const DEBUGING='debuging';

    /**
     * 开始编译
     */
    const COMPILE_START='compile_start';

    /**
     * 编译完成
     */
    const COMPILE_DONE='compile_done';

    /**
     * 设置需要缓存的内容
     */
    public $content=null;

    /**
     * 缓存的内容哈希值
     */
    public $hash=null;

    /**
     * 缓存的有效期
     */
    public $expire=null;

    /**
     * 编译后保存的路径
     */
    public $compile_path=null;

    /**
     * 模板文件的路径
     */
    public $template_path=null;

    //=========================
    //  模板解析时用到的属性
    //=========================


    /**
     * @var array
     * 标签上的属性
     */
    public $attr=array();

    /**
     * @var array
     * 返回的结果
     */
    public $result=array();

    /**
     * @var array
     * 错误的标签,编译不通过
     */
    public $error=array();

    /**
     * @var array
     * 需要修正的标签，编译通过
     */
    public $warning=array();

    /**
     * @var null
     * 根标签
     */
    public $root=null;

    public $cacheEnable;
    public $debugEnable;

}