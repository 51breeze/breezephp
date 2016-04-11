/*
 * BreezeJS Dictionary class.
 * version: 1.0 Beta
 * Copyright © 2015 BreezeJS All rights reserved.
 * Released under the MIT license
 * https://github.com/51breeze/breezejs
 */

(function(){

    /**
     * 可以使用非字符串作为键值的存储表
     * @constructor
     */
    function Dictionary()
    {
        if( !(this instanceof Dictionary) )
            return new Dictionary();

        var map=new Array()
            ,indexByKey=function(key)
            {
                var i = 0,len=map.length,isscalar=( typeof key==='string' );
                for(; i<len ; i++) if( ( isscalar && i===key ) || ( !isscalar && map[i] && map[i].key===key ) )
                    return i;
                return -1;
            }

        /**
         * 设置指定键值的数据,如果相同的键值则会覆盖之前的值。
         * @param key
         * @param value
         * @returns {*}
         */
        this.set=function(key,value)
        {
            if( typeof key==='string' )
                return map[ key ]=value;
            this.remove( key );
            map.push({'key':key,'value':value})
            return value;
        }

        /**
         * 获取已设置的值
         * @param key
         * @returns {*}
         */
        this.get=function( key )
        {
            if( typeof key==='string' )
                return map[ key ];
            var index=indexByKey(key);
            return index >=0 ?  map[ index ].value : null ;
        }

        /**
         * 返回所有已设置的数据
         * 数组中的每个项是一个对象
         * @returns {Array}
         */
        this.getAll=function()
        {
            return map;
        }

        /**
         * 返回有的key值
         * @returns {Array}
         */
        this.toKeys=function()
        {
            var value=[],i
            for( i in map ) value.push( typeof map[i] ==='object' ? map[i].key : i );
            return value;
        }

        /**
         * 删除已设置过的对象,并返回已删除的值（如果存在）否则为空。
         * @param key
         * @returns {*}
         */
        this.remove=function( key )
        {
            var value=null;
            if( typeof key==='string' )
            {
                value=map[ key ];
                delete map[ key ];
            }else
            {
                var index= indexByKey( key );
                if( index >=0  ){
                    value=map[index].value;
                    map.splice(index,1);
                }
            }
            return value;
        }

        /**
         * 返回已设置数据的总数
         * @returns {Number}
         */
        this.count=function()
        {
            return map.length;
        }
    }
    window.Dictionary=Dictionary;
})()