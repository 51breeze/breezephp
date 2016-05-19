<?php
namespace breeze\events;

class StructureEvent extends Event
{
    /**
     * 索引变更
     */
    const CHANGE='structureChange';

    /**
     * 索引转成字符串
     */
    const TO_STRING='structureToString';

    /**
     * 属性名
     */
    public $name;

    /**
     * 老的数据值
     */
    public $oldValue;

    /**
     * 新的数据值
     */
    public $newValue;

    /**
     * 事件类型为 toString 时所引用的结果值
     * @var string
     */
    public $result='';

}

?>