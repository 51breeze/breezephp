/*
 * BreezeJS BindData class.
 * version: 1.0 Beta
 * Copyright © 2015 BreezeJS All rights reserved.
 * Released under the MIT license
 * https://github.com/51breeze/breezejs
 */

(function(window, undefined )
{
    /**
     * 数据双向绑定器
     * @param target 需要监听的对象。在这些对象上所做出的任何属性变化都会影响到通过 bind 方法所绑定到的数据源
     * @constructor
     */
    var form = /select|input|textarea|button/i;

    function Bindable( data )
    {
        if( !(this instanceof Bindable) )
            return new Bindable( data );

        /**
         * @private
         */
        var dataset=data || {}
            ,properties={}
            ,self=this
            ,commit = function(name,value)
            {
                if( properties )
                {
                    var callback=properties[name] || properties['*'];

                    if( Breeze.isFunction( callback ) )
                    {
                        return !!callback.call(dataset,name,value);

                    }else if( name in properties || '*' in properties )
                    {
                        if( ( (dataset.nodeType && typeof dataset.nodeName ==='string' && dataset != dataset.window) ||
                            typeof dataset === 'object' ) && dataset[ name ] !== value )
                        {
                            dataset[ name ] = value;
                            return true;

                        }else if( (dataset instanceof Bindable || dataset instanceof Breeze) && dataset.property(name) !== value )
                        {
                            return !!dataset.property(name,value);
                        }
                    }
                }
                return false;
            }

        EventDispatcher.call(this);

        /**
         * 绑定元素属性
         * @param target
         * @param property
         * @param callback
         * @returns {boolean}
         */
        this.bind=function(target,property,callback)
        {
            if( target && target.nodeType && typeof target.nodeName ==='string' )
            {
                this.forEachCurrentItem = target;
                properties[ property ]=callback;

                //元素属性触发时设置属性动作
                this.addEventListener(PropertyEvent.PROPERTY_CHANGE,function(event)
                {
                    if( event instanceof PropertyEvent && event.target )
                    {
                        this.property( property , event.newValue );
                    }
                });

                //元素离开焦点时触发设置属性动作
                this.addEventListener(BreezeEvent.BLUR,function(event){

                    if( event.target && event.target.nodeType && typeof event.target.nodeName === 'string' )
                    {
                        var prop=form.test( event.target.nodeName ) ? 'value' : 'innerHTML';
                        this.property(property, event.target[ prop ] );
                    }
                })
                return true;
            }
            return false;
        }

        /**
         * 获取绑定的数据源
         * @returns {{}}
         */
        this.data=function()
        {
            return dataset;
        }

        /**
         * 设置/获取属性
         * @param name
         * @param value
         */
        this.property=function(name,value)
        {
            if( value === undefined )
            {
                return (dataset instanceof Bindable || dataset instanceof Breeze) ? dataset.property(name) : dataset[name];
            }

            if( commit(name,value) )
            {
                var ev=new PropertyEvent(PropertyEvent.PROPERTY_CHANGE)
                ev.property=name;
                ev.newValue=value;
                self.dispatchEvent(ev);
            }
        }

        /**
         * 检查是否有指定的属性名
         * @param name
         * @returns {boolean}
         */
        this.hasProperty=function(name)
        {
            return typeof dataset[name] !== 'undefined';
        }
    }

    Bindable.prototype=new EventDispatcher();
    Bindable.prototype.constructor=Bindable;

    window.Bindable=Bindable;

})(window)