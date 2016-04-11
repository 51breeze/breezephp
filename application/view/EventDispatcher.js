/*
 * BreezeJS : EventDispatcher class.
 * version: 1.0 Beta
 * Copyright © 2015 BreezeJS All rights reserved.
 * Released under the MIT license
 * https://github.com/51breeze/breezejs
 */

(function(window,undefined){

    var dataName='__DISPATCHS_DATA__'
    , globlaEvent=null
   ,msie=navigator.userAgent.match(/msie ([\d.]+)/i)

   /**
    * 事件名是否需要加 on
    * @type {string}
    */
   ,onPrefix= msie && msie[1] < 9 ? 'on' : ''

   /**
    * 添加节点元素事件
    * @type {Function}
    */
   ,addListener=document.addEventListener ?
    function(type,listener,useCapture){this.addEventListener(type,listener,useCapture)} : function(type,listener,useCapture){this.attachEvent(type,listener)}

   /**
    * 手动移除节点元素事件
    * @type {Function}
    */
    ,removeListener=document.removeEventListener ?
     function(type,listener,useCapture){this.removeEventListener(type,listener,useCapture)} :function(type,listener,useCapture){this.detachEvent(type,listener)}

     ,dispather=document.dispatchEvent ?
          function(type){
              var evt = document.createEvent('Event');
                  evt.initEvent(type,true,true);
              this.dispatchEvent(evt)
          } : onPrefix==='on' ?
           function(type){
               this['data-event-'+type]=Math.random();
            } :
           function(type){this.fireEvent(type)}

    /**
     * 统一事件名
     * @type {propertychange: string}
     */
    ,mapeventname= onPrefix==='' ? {'propertychange':'input','ready':'readystatechange'} :{'ready':'readystatechange'}

    ,agreed=new RegExp( 'webkitAnimationEnd|webkitAnimationIteration','i')

   /**
    * 特定的一些事件类型。
    * 这些事件类型在设备上不被支持，只有通过其它的事件来模拟这些事件的实现。
    * @type {}
    */
    ,specialEvents={}
    ,bindBeforeProxy={}

    /**
     * 为每个节点元素绑定事件。
     * 通过这些节点元素事件在设备上的响应来触发用户注册的事件。
     * @param type
     * @param useCapture
     * @param handle
     */
    ,forEachAddListener=function( proxyType,type,useCapture,dataItem )
    {
        if( typeof proxyType !=='string' )
           return;

        var target= this.dispatchTargets()
            ,len= target.length || 0
            ,element
            ,index=0;

        if( !agreed.test( proxyType ) )
           proxyType=proxyType.toLowerCase();

        proxyType=mapeventname[proxyType] || proxyType;

        do{
             element=target[ index ] || this;
             if( !bindBeforeProxy[type] || !bindBeforeProxy[type].call(this,element,dataItem,proxyType,useCapture)  )
             {
                EventDispatcher.addListener.call(this,element, dataItem, type,useCapture);
             }
            index++;
        }while( index < len );
    }

   ,addItem=function(target,type,listener )
   {
      var  data = getData(target,type);
           data.items.push( listener );

       if(  data.items.length > 1 )
       {
           data.items.sort(function(a,b)
           {
               //按权重排序，值大的在前面
               return a.priority=== b.priority ? 0 : (a.priority < b.priority ? 1 : -1);
           })
       }
       return true;
   }
   ,getData=function(target,type)
   {
       type=type.toLowerCase();
       var  dataGroup = target[dataName] || ( target[dataName]={} );
       return dataGroup[ type ] || ( dataGroup[ type ]={'items':[],'handle':null,'capture':true,'type':''} );
   }
   ,removeData=function(target,type)
    {
        if( target[dataName] )
        {
           delete target[dataName][type];
            return true;
        }
        return false
    }

   ,indexByElement=function(dataGroup,element)
   {
       for( var j in dataGroup ) if( dataGroup[j].target === element )
       {
         return j;
       }
       return -1;
   }
   ,getMapType=function( type )
   {
       if( typeof mapeventname === 'object') for( var i in mapeventname)
       if( mapeventname[i] === type ){
           type=i
           break;
       }
       return type;
   }

    /**
     * 根据原型事件创建一个Breeze BreezeEvent
     * @param event
     * @returns {*}
     */
    ,createEvent=function( event )
    {
        if( event instanceof BreezeEvent )
            return event;

        var event=event || window.event;
        if( !event )
          return null;

        var breezeEvent={}
            ,target=event.target || event.srcElement
            ,currentTarget=event.currentTarget || target
            ,type=onPrefix==='on' && event.type ? event.type.replace(/^on/i,'') : event.type;

        //ie9 以下 如果不是自定义事件
        if( onPrefix==='on' && typeof event.propertyName==='string' && event.propertyName !=="" )
        {
            if( event.propertyName.match(/data-event-(\w+)/i) )
               type=RegExp.$1;
            else if(event.propertyName==='activeElement')
               return null;
        }

       // type=getMapType( type );

        if( typeof PropertyEvent !=='undefined' && type === PropertyEvent.PROPERTY_CHANGE )
        {
            breezeEvent=new PropertyEvent( event );
            breezeEvent.property= Breeze.isFormElement(target) ? 'value' : 'innerHTML';
            breezeEvent.newValue=target[ breezeEvent.property ];

        }else if( /^mouse|click$/i.test(type) && typeof MouseEvent !=='undefined' )
        {
            breezeEvent=new MouseEvent( event );
            breezeEvent.pageX= event.x || event.clientX || event.pageX;
            breezeEvent.pageY= event.y || event.clientY || event.pageY;

            if( event.offsetX===undefined && target && Breeze )
            {
                var offset=Breeze.position(target);
                event.offsetX=breezeEvent.pageX-offset.left;
                event.offsetY=breezeEvent.pageY-offset.top;
            }

            breezeEvent.offsetX = event.offsetX;
            breezeEvent.offsetY = event.offsetY;
            breezeEvent.screenX= event.screenX;
            breezeEvent.screenY= event.screenY;

        }else if( typeof BreezeEvent !=='undefined' )
        {
            breezeEvent=new BreezeEvent( event );
            breezeEvent.altkey= !!event.altkey;
            breezeEvent.button= event.button;
            breezeEvent.ctrlKey= !!event.ctrlKey;
            breezeEvent.shiftKey= !!event.shiftKey;
            breezeEvent.metaKey= !!event.metaKey;
        }

        breezeEvent.type=type;
        breezeEvent.target=target || this;
        breezeEvent.currentTarget=currentTarget || this;
        breezeEvent.timeStamp = event.timeStamp;
        breezeEvent.relatedTarget= event.relatedTarget;
        return breezeEvent;
    }

    /**
     * EventDispatcher Class
     * 事件调度器，所有需要实现事件调度的类都必须继承此类。
     * @param object
     * @returns {EventDispatcher}
     * @constructor
     */
    function EventDispatcher( element )
    {
        if( !(this instanceof EventDispatcher) )
            return new EventDispatcher(element);
        this.__dispatchTarget__=[].concat( element || [] );
        this.__bindType__={};
        return this;
    };

    /**
     * 添加侦听器到元素中
     * @param element
     * @param dataItem
     * @param type
     * @param useCapture
     * @param dataKey
     * @param callbackHandle
     * @returns {boolean}
     */
    EventDispatcher.addListener=function( element, listener, type, useCapture , dataKey , callbackHandle )
    {
        dataKey = dataKey || type;
        if( !(listener instanceof  EventDispatcher.Listener) )
        {
            throw new Error('listener invalid, must is EventDispatcher.Listener');
        }

        listener.target = element;
        if( element instanceof EventDispatcher )
        {
            addItem(element, dataKey, listener );
            return true;
        }

        var win=element.document && element.document.nodeType===9 ? element : element.defaultView || element.contentWindow || element.parentWindow;
        if( (element.nodeType && element.nodeType===1) || element===win || (win && element===win.document) )
        {
            addItem(element, dataKey, listener );
            var data = getData(element,dataKey );

            //每个元素只添加一个类型的事件
            if( data.items.length===1 )
            {
                var handle = callbackHandle || EventDispatcher.dispatchEvent;
                data.capture = !!useCapture;
                data.handle = handle;
                data.type=onPrefix+type;

                //ie9 以下统一用 onpropertychange 来触发自定义事件
                if( onPrefix === 'on' )
                {
                    addListener.call( element, 'onpropertychange', handle ,data.capture);
                    if( type==='propertychange' )
                      return true;
                }
                addListener.call( element, data.type, handle ,data.capture);
            }
            return true;
        }
        return false;
    }

    /**
     * 调度指定侦听项
     * @param event
     * @param listeners
     * @returns {boolean}
     */
    EventDispatcher.dispatchEvent=function(event, listeners )
    {
        event=createEvent( event );

        //获取需要调度的侦听器
        listeners = listeners || getData( event.currentTarget, event.type).items;

        if( !event || listeners.length < 1 )return false;

        if( event.type === 'click' )
        {
            removeListener.call(  event.currentTarget, 'click', EventDispatcher.dispatchEvent , true );
        }

        //初始化一个全局事件
        globlaEvent = event= globlaEvent ? Breeze.extend(globlaEvent,event) : event;
        if( !globlaEvent.currentTarget )
            return false;

        //标记这个事件的对象已调度
        globlaEvent.currentTarget.dispatched=true;

        var length=0;
        while(  length < listeners.length )
        {
            var item = listeners[ length ]

            if( item instanceof EventDispatcher.Listener )
            {
                //设置Breeze 对象的当前对象
                if( typeof item.currentTarget.current ==='function' )
                {
                    item.currentTarget.current( event.currentTarget );
                }
                //调度侦听项
                item.callback.call( item.currentTarget , event );
                if( event && event.isPropagationStopped===true )
                    break;
            }
            length++;
        }
        globlaEvent=null;
        return true;
    }

    //Constructor
    EventDispatcher.prototype.constructor=EventDispatcher;

    /**
     * 获取代理事件的元素目标
     * @returns {array}
     */
    EventDispatcher.prototype.dispatchTargets=function( index )
    {
         var target=this.__dispatchTarget__;
         if( this.forEachCurrentItem )
         {
             target=[ this.forEachCurrentItem ];

         }else if( typeof Breeze !== 'undefined' && this instanceof Breeze)
         {
             target=this;
         }
         return typeof index ==='number' ? target[ index ] : target;
    }

    /**
     * 判断是否有指定类型的侦听器
     * @param type
     * @returns {boolean}
     */
    EventDispatcher.prototype.hasEventListener=function( type )
    {
        return !!this.__bindType__[type];
    }

    /**
     * 添加侦听器
     * @param type
     * @param listener
     * @param priority
     * @returns {Breeze}
     */
    EventDispatcher.prototype.addEventListener=function(type,listener,useCapture,priority)
    {
        if( !(listener instanceof EventDispatcher.Listener) )
        {
            listener=new EventDispatcher.Listener(listener,priority,useCapture,this);
        }

        if( type instanceof Array )
        {
            var len=type.length;
            while( len > 0 )this.addEventListener(type[--len],listener,useCapture,priority);
            return this;
        }

        var target= this.dispatchTargets()
            ,len= target.length || 0
            ,element
            ,index=0;

        var oldtype = type;
        type= !agreed.test( type ) ? type.toLowerCase() : type;
        var proxytype=mapeventname[type] || type;
        var special = bindBeforeProxy[type];

        do{

            element=target[ index ] || this;
            if( target[ index ] instanceof EventDispatcher )
            {
                this.__bindType__[ oldtype ]=true;
                target[ index ].addEventListener(oldtype,listener,useCapture,priority);

            }else if( !special || !special.callback(element,listener,type,useCapture,this)  )
            {
                this.__bindType__[ oldtype ]=true;
                EventDispatcher.addListener.call(this, element, listener, proxytype, useCapture);
            }
            index++;

        }while( index < len );
        return this;
    }

    /**
     * 移除指定类型的侦听器
     * @param type
     * @param listener
     * @returns {boolean}
     */
    EventDispatcher.prototype.removeEventListener=function(type,listener,useCapture)
    {
        if( type==='*' )
        {
            for( var t in this.__bindType__ )
            {
               this.removeEventListener(t,listener,useCapture);
            }
            return true;
        }

        var target= this.dispatchTargets();
        var use = typeof useCapture ==='undefined' ? null : useCapture;

        for( var b=0 ; b<target.length; b++ )
        {
            if( target[b] instanceof EventDispatcher )
            {
                target[b].removeEventListener(type,listener,useCapture);
            }else
            {
                var data = getData( target[b],type);
                var item=data.items;
                var length =  item.length;
                if( typeof listener ==='function' || use !==null ) while( length > 0 )
                {
                    --length;
                    if( ( !listener || item[ length ].callback===listener ) && ( use===null || use===item[ length ].capture ) )
                        item.splice(length,1);

                }else
                {
                    item.splice(0,length);
                }

                if( item.length < 1 )
                {
                    removeListener.call( target[b], data.type , data.handle , data.capture  );
                    if( onPrefix==='on' ){
                        removeListener.call( target[b], 'onpropertychange', data.handle , data.capture );
                    }
                    this.__bindType__[type]=null;
                }
            }
        }
        return true;
    }

    /**
     * 调度指定事件
     * @param event
     * @returns {boolean}
     */
    EventDispatcher.prototype.dispatchEvent=function( event )
    {
        globlaEvent=event= typeof event === 'string'  ? new BreezeEvent(event) :  event;
        var target = this.dispatchTargets()
        if( target )for( var i=0; i < target.length ; i++ )
        {
            if( event.isPropagationStopped===true  || !target[i] )
               return false;

            if( target[i] instanceof EventDispatcher )
            {
              if( !target[i].dispatchEvent(event) )
                return false;

            }else
            {
                target[i].dispatched=false;

                //通过浏览器来发送
                if( target[i] && ( (typeof target[i].nodeName === 'string' && (target[i].nodeType===1 || target[i].nodeType===9 )) || target[i].window )  )
                {
                    dispather.call( target[i], event.type );
                }

                //没有派发的事件需要手动派发
                if( target[i].dispatched===false )
                {
                    event.currentTarget=target[i];
                    event.target=target[i];
                    EventDispatcher.dispatchEvent( event );
                }
            }

        }

        //本身事件对象
        event.currentTarget=this;
        event.target=this;
        EventDispatcher.dispatchEvent( event );

        var result = event.isPropagationStopped;
        globlaEvent=null;
        return !result;
    }

    /**
     * 事件侦听器
     * @param callback
     * @param priority
     * @param capture
     * @param currentTarget
     * @param target
     * @constructor
     */
    EventDispatcher.Listener=function(callback,priority,capture,currentTarget,target)
    {
        if( typeof callback !=='function' )
           throw new Error('callback not is function in EventDispatcher.Listener')

        if(  !(currentTarget instanceof EventDispatcher) )
            throw new Error('currentTarget not is EventDispatcher in EventDispatcher.Listener');

        this.callback=callback;
        this.priority=parseInt(priority) || 0;
        this.capture=!!capture;
        this.currentTarget=currentTarget; //当前调度对象
        this.target=target; //html元素
    }
    EventDispatcher.Listener.prototype.constructor= EventDispatcher.Listener;

    /**
     * 特定事件扩展器
     * @param type
     * @param callback
     * @param handle
     * @returns {EventDispatcher.SpecialEvent}
     * @constructor
     */
    EventDispatcher.SpecialEvent=function(type,callback)
    {
        if( !(this instanceof  EventDispatcher.SpecialEvent) )
        {
            return new EventDispatcher.SpecialEvent(type,callback);
        }

        var __callback__;
        var __type__;

        /**
         * 绑定元素之前的回调
         * @param element
         * @param item
         * @param type
         * @param useCapture
         * @returns {*}
         */
        this.callback=function(element,listener,type,useCapture,dispatcher)
        {
            if( typeof __callback__ ==='function' )
            {
                return __callback__.call(this,element,listener,type,useCapture,dispatcher);
            }
            return false;
        }

        /**
         * 获取类型
         * @returns {*}
         */
        this.getType=function()
        {
            return __type__;
        }

        /**
         * 设置特定事件在绑定元素之前所需要执行的函数
         * @param callback
         * @returns {EventDispatcher.SpecialEvent}
         */
        this.setCallback=function(callback)
        {
            if( typeof callback !== 'function' )
            {
                throw new Error('callback not is function');
            }
             __callback__=callback;
            return this;
        }

        /**
         * 设置特定事件的类型，可以是一个数组。
         * @param type
         * @returns {EventDispatcher.SpecialEvent}
         */
        this.setType=function(type)
        {
            if( type instanceof Array )for(var i in type)
            {
                bindBeforeProxy[ type[i] ]=this;
            }else
            {
                bindBeforeProxy[type]=this;
            }
            __type__ = type;
            return this;
        }

        if( callback )
            this.setCallback(callback);

        if( type )
            this.setType(type);
    }
    EventDispatcher.SpecialEvent.prototype.constructor=EventDispatcher.SpecialEvent;

    /**
     * 监测加载对象上的就绪状态
     * @param event
     * @param type
     */
    var readyState=function( event , type, dispatcher )
    {
        var target=  event.srcElement || event.target;
        var nodeName=  typeof target.nodeName ==='string' ? target.nodeName.toLowerCase() : '';
        var readyState=target.readyState;
        var eventType= event.type || null;

        if( onPrefix === 'on' )
        {
            //iframe
            if( nodeName==='iframe' )
            {
                readyState=target.contentWindow.document.readyState;
            }//window
            else if( target.window && target.document )
            {
                readyState=target.document.readyState;
            }
        }

        //ie9以下用 readyState来判断，其它浏览器都使用 load or DOMContentLoaded
        if( !this.__STATE__ && ( ( eventType && /load/i.test(eventType) ) || ( readyState && /loaded|complete/.test( readyState ) ) )  )
        {
            this.__STATE__=true;
            event.type = type;
            this.___loading___=false;
            EventDispatcher.dispatchEvent( event );
            dispatcher.removeEventListener( BreezeEvent.READY );

        }
    }

    //定义 load 事件
    EventDispatcher.SpecialEvent(BreezeEvent.LOAD,
    function(element,listener,type,useCapture,dispatcher)
    {
        var self =  this;
        var handle=function(event)
        {
            event= createEvent( event || window.event )
            if( event )
            {
                readyState.call(self,event,BreezeEvent.LOAD , dispatcher );
            }
        };

        if( element.contentWindow )
        {
            dispatcher.addEventListener( BreezeEvent.READY, listener ,true, 10000 );

        }else
        {
            handle({'srcElement':element});
            EventDispatcher.addListener(element, listener, type , useCapture , BreezeEvent.LOAD,handle );
            EventDispatcher.addListener(element, listener, 'readystatechange' , useCapture , BreezeEvent.LOAD,handle );
        }
        return true;

    })

    // 定义ready事件
    EventDispatcher.SpecialEvent(BreezeEvent.READY,function(element,listener,type,useCapture,dispatcher)
    {
        var doc = element.contentWindow ?  element.contentWindow.document : element.ownerDocument || element.document || element,
            win= doc &&  doc.nodeType===9 ? doc.defaultView || doc.parentWindow : null;
        if( !win || !doc )return;

        var self =  this;
        var handle=function(event)
        {
            event= createEvent( event || window.event )
            if( event )
            {
                readyState.call(self,event,BreezeEvent.READY,dispatcher);
            }
        }

        EventDispatcher.addListener(win, listener, onPrefix=='' ? 'DOMContentLoaded' : 'load' , useCapture , BreezeEvent.READY,handle );
        EventDispatcher.addListener(doc, listener, 'readystatechange', useCapture, BreezeEvent.READY,handle );

        //ie9 以下，并且是一个顶级文档或者窗口对象
        if( onPrefix ==='on' && !element.contentWindow )
        {
            var toplevel = false;
            try {
                toplevel = window.frameElement == null;
            } catch(e) {}
            if ( toplevel && document.documentElement.doScroll )
            {
                this.___loading___=true;
                var doCheck=function()
                {
                    if ( !this.___loading___ )
                        return;
                    try {
                        document.documentElement.doScroll("left");
                    } catch(e) {
                        setTimeout( doCheck, 1 );
                        return;
                    }
                    handle( {'srcElement':doc} );
                }
                doCheck();
            }
        }
        handle({'srcElement':doc});
        return true;
    });

    window.EventDispatcher=EventDispatcher;

})(window)