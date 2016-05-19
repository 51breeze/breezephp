<?php

namespace breeze\interfaces;

interface ISingle
{

    public function __construct();

    /**
     * 获取指定类的实例对象
     * @return ISingle
     */
    public static function getInstance();

}