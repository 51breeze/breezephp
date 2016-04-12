<?php

namespace breeze\interfaces;

interface ICache
{

    function get( $key );

    function set( $key , $data , $expire=3600 );

    function clean( $key=null );


} 