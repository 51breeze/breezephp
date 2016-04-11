/*
 * BreezeJS : EventDispatcher class.
 * version: 1.0 Beta
 * Copyright © 2015 BreezeJS All rights reserved.
 * Released under the MIT license
 * https://github.com/51breeze/breezejs
 */

(function(window,undefined){

   var dataName='__DISPATCHS_DATA__'

   /**
    * 添加节点元素事件
    * @type {Function}
    */
   ,addListener=document.addEventListener ?
    function(type,listener,useCapture){this.addEventListener(type,listener,useCapture)} :function(type,listener,useCapture){this.attachEvent(type,listener)}

   /**
    * 手动移除节点元素事件
    * @type {Function}
    */
    ,removeListener=document.removeEventListener ?
     function(type,listener,useCapture){this.removeEventListener(type,listener,useCapture)} :function(type,listener,useCapture){this.detachEvent(type,listener)}

     ,dispather=document.dispatchEvent ?
          function(type){this.dispatchEvent(type)} :function(type){this.fireEvent(type,listener)}

   ,msie=navigator.userAgent.match(/msie ([\d.]+)/i)

    /**
     * 事件名是否需要加 on
     * @type {string}
     */
   ,onPrefix= msie && msie[1] < 9 ? 'on' : ''

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
    ,bind={}

    /**
     * 为每个节点元素绑定事件。
     * 通过这些节点元素事件在设备上的响应来触发用户注册的事件。
     * @param type
     * @param useCapture
     * @param handle
     */
    ,forEachAddListener=function( proxyType,type,useCapture,handle,dataItem , dataGroup )
    {
        if( typeof proxyType !=='string' )
           return;

        var target= this.dispatchTargets()
            ,len= target.length || 0
            ,element
            ,win
            ,index=0;

        if( !agreed.test( proxyType ) )
           proxyType=proxyType.toLowerCase();

        do{
            element=target[ index ] || target;
            win=element.document && element.document.nodeType===9 ? element : element.defaultView || element.contentWindow || element.parentWindow;
            if( (element.nodeType && element.nodeType===1) || element===win || (win && element===win.document) )
            {
                if( indexByElement(dataGroup,element) < 0 )
                {
                    handle=handle || this.__dispatchHandle__;
                    proxyType=onPrefix+( mapeventname[proxyType] || proxyType );
                    if( !bind[type] || !bind[type].call(this,element,handle,proxyType,useCapture)  )
                    {
                        dataItem.target=element;
                        addListener.call( element, proxyType, handle ,useCapture);
                    }
                }
            }
            index++;
        }while( index < len );
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

        var event = event || window.event
            ,breezeEvent={}
            ,target=event.target || event.srcElement
            ,currentTarget=event.currentTarget
            ,type=onPrefix==='on' && event.type ? event.type.replace(/^on/i,'') : event.type || type;

        type=getMapType( type );

        if( PropertyEvent && type === PropertyEvent.PROPERTY_CHANGE )
        {
            breezeEvent=new Breeze.PropertyEvent( event );
            breezeEvent.property= Breeze.isFormElement(target) ? 'value' : 'innerHTML';
            breezeEvent.newValue=target[ breezeEvent.property ];

        }else if( /^mouse|click$/i.test(type) && MouseEvent )
        {
            breezeEvent=new MouseEvent( event );
            breezeEvent.pageX= event.x || event.clientX || event.pageX;
            breezeEvent.pageY= event.y || event.clientY || event.pageY;

            if( event.offsetX===undefined && target && Breeze )
            {
                var offset=Breeze.getPosition(target);
                event.offsetX=breezeEvent.pageX-offset.left;
                event.offsetY=breezeEvent.pageY-offset.top;
            }

            breezeEvent.offsetX = event.offsetX;
            breezeEvent.offsetY = event.offsetY;
            breezeEvent.screenX= event.screenX;
            breezeEvent.screenY= event.screenY;

        }else if(BreezeEvent)
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
        var self=this;

        this.__dispatchTarget__=[].concat( element || [] );

        this.__dispatchHandle__=function(event)
        {
            self.dispatchEvent( createEvent( event ) );
        }
        this[dataName]={};
        return this;
    };

    //Constructor
    EventDispatcher.prototype.constructor=EventDispatcher;

    /**
     * 获取代理事件的元素目标
     * @returns {array}
     */
    EventDispatcher.prototype.dispatchTargets=function( index )
    {
        var target=[];
        if( this.__dispatchTarget__.length > 0  )
        {
            target=this.__dispatchTarget__;

        }else if( typeof this.toArray === 'function' )
        {
            target = this.toArray();
        }
        return typeof index ==='number' ? target[ index ] : this.forEachCurrentItem ? [ this.forEachCurrentItem ] : target;
    }

    /**
     * 判断是否有指定类型的侦听器
     * @param type
     * @returns {boolean}
     */
    EventDispatcher.prototype.hasEventListener=function( type )
    {
        return !!( this[ dataName ] && this[ dataName ][type] );
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

        if( type instanceof Array )
        {
            var len=type.length;
            while( len > 0 )this.addEventListener(type[--len],listener,useCapture,priority);
            return;
        }
        if( typeof listener !=='function' )
            return false;

        priority = parseInt( priority ) || 0;

        var data =this[dataName] || (this[dataName]={})
            ,specialEvent=specialEvents[ type ]
            ,index;

        var dataGroup = data[ type ] || (data[ type ]=[]);
        var item = {fn:listener,pri:priority,capture:useCapture,target:null};

        dataGroup.push( item );
        dataGroup.sort(function(a,b)
        {
            //按权重排序，值大的在前面
            return a.pri=== b.pri ? 0 : (a.pri > b.pri ? 1 : -1);
        })

        //特定事件
        if( specialEvent )
        {
            var handle=type+'Handle',
                self=this;
            if( this[ handle ]===undefined )
            {
                this[ handle ]= function(event){
                    event=event || window.event
                    if( event ){
                        specialEvent.handle.call(self, createEvent(event) );
                    }
                };
            }
           var proxyType=specialEvent.proxyEvents instanceof Array  ? specialEvent.proxyEvents : [ specialEvent.proxyEvents ];
           for( index in proxyType )forEachAddListener.call(this, proxyType[ index ] ,type , useCapture,this[ handle ] , item , dataGroup );
        }
        //普通事件
        else
        {
            forEachAddListener.call(this,type,type,useCapture,null,item, dataGroup );
        }
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
        if( typeof listener !=='function' )
            return false;
        var data = this[dataName] || (this[dataName]={});
        var length=data[type] ? data[type].length : 0;
        var handle=type+'Handle';
        var specialEvent=specialEvents[ type ];
        var ret=false;

        while( length>0 )
        {
            --length;
            if( data[type][ length ].fn===listener )
            {
                data[type].splice(length,1);
                ret=true;
                break;
            }
        }

        if( ret && this.__dispatchTarget__ )
        {
            var target= this.dispatchTargets();
            var len= target.length,i= 0,element;
            for( ; i < len; i++ )
            {
                element=target[i];
                if( ( element.nodeType && element.nodeType===1 )  || element===document || element===window )
                {
                    type=mapeventname[type] || type;
                    if( specialEvent && this[handle] )
                    {
                        var index,proxyType= specialEvent.proxyEvents instanceof Array ? specialEvent.proxyEvents : [ specialEvent.proxyEvents ];
                        for( index in proxyType ){

                            removeListener.call( element,( onPrefix+proxyType[index] ).toLowerCase(), this[ handle ] ,useCapture );
                        }
                        delete this[ handle ];
                    }else
                    {
                        removeListener.call( element,onPrefix+type,this.__dispatchHandle__ ,useCapture);
                    }
                }
            };
        }
        return ret;
    }

    /**
     * 清除所有类型的侦听器
     * @param fn 一个回调过滤函数或者是一个要删除的事件类型。
     * 过滤函数只有返回 true 的情况下才会清除绑定在元素上的事件。
     * @returns {void}
     */
    EventDispatcher.prototype.cleanEventListener=function( type , callback )
    {
        var data = this[ dataName ];
        if( data )
        {
            if( typeof type !=='string' ) for( type in data )
            {
                this.cleanEventListener( type, callback );
            }else if( data[type] && data[type].length >0 )
            {
                var elements=  this.dispatchTargets()
                    ,is = typeof callback ==='function'
                    ,isElem=callback && callback.nodeType===1
                    ,has=false
                    ,handle=type+'Handle'
                    ,len= elements.length;

                for( i=0 ; i < len; i++ )
                {
                    element=elements[ i ];
                    if( (!is && !isElem) || ( is && callback.call(this,element,i,type,data) ) || ( isElem && callback===element ) )
                    {
                        removeListener.call( element,onPrefix+(mapeventname[type] || type), this[handle] || this.__dispatchHandle__ );
                    }else if( !has )
                    {
                        has=true;
                    }
                }
                //如果没有代理事件对象了，就删除整个类型的所有事件。
                if( !has )delete data[ type ];
            }
        }
        return this;
    }

    /**
     * 调度指定事件
     * @param event
     * @returns {boolean}
     */
    EventDispatcher.prototype.dispatchEvent=function( event )
    {
        var type=event.type || event,
            length,
            listener,
            data = this[dataName] || (this[dataName]={});

        if( data[ type ] === undefined )
            return false;
        length=data[ type ].length;

        var isHtml = Breeze.isHTMLElement(event.target);
        while( length >0 )
        {
            --length;
            if( !isHtml || event.target === data[ type ][ length ].target )
            {
                listener = data[ type ][ length ];
                event.target = data[ type ][ length ].target ||  this;
                listener.fn.call( this , event );
                if( event.isPropagationStopped===true )
                    return false;
            }
        }
        return true;
    }

    /**
     * 扩展事件类型。
     * 此功能主要是模拟一些在设备上不支持的事件类型。通过绑定设备上的其它事件来触发特定事件来达到模拟效果。
     * @param type 需要代理的事件类型,一个对象 {'customType': 'eventType' }  customType 自定义事件类型,
     *             eventType 绑定到的事件类型  允许一个数或者一个字符串
     * @param callback 创建一个特定事件的回调函数。此函数必须使用 this.dispatchEvent(BreezeEvent) 自行调度事件，
     *                 否则扩展的事件将不会生效。
     */
    EventDispatcher.expandHandle=function(name,type,callback)
    {
       if( typeof name==='object' ) for( var key in name )
       {
           EventDispatcher.expandHandle(key,name[ key ],type);

       }else if( typeof callback ==='function' && typeof name ==='string' && type )
       {
            specialEvents[ name ]={
                proxyEvents:type,
                handle:callback
            };
        }
    }

    /**
     * 在绑定事件之前修复一些不兼容的事件
     * @param type
     * @param callback 需要修复的回调函数。此函数接受两个参数 element,handle 。
     * 如果不想后续的事务执行返回true。
     */
    EventDispatcher.bindBefore=function(type,callback)
    {
        if( type instanceof Array )for(var i in type)
        {
            bind[ type[i] ]=callback;
        }else
        {
            bind[type]=callback;
        }
    }

    /**
     * 监测加载对象上的就绪状态
     * @param event
     * @param type
     */
    var readyState=function( event , type )
    {
        var target=  event.srcElement || event.target || this.dispatchTargets(0);
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
        if( !this.__D__ &&  ( ( eventType && /load/i.test(eventType) ) ||
            ( readyState && /loaded|complete/.test( readyState ) ) ) && this.hasEventListener( type ) )
        {
            this.__D__=true;
            delete this.___loading___;
            this.dispatchEvent( new BreezeEvent( type, {target:target } ) );
            this.cleanEventListener( type );
            delete this.__D__;
        }
    }

    //扩展 load 事件的handle
    EventDispatcher.expandHandle( BreezeEvent.LOAD, BreezeEvent.READY_STATE_CHANGE ,function(event)
    {
        delete this.__D__;
        event.type=BreezeEvent.LOAD;
        readyState.call(this,event,BreezeEvent.LOAD );
    })

    //绑定 load 事件时的构子
    EventDispatcher.bindBefore( BreezeEvent.LOAD ,function(element,handle,proxyType)
    {
        if( element.contentWindow )
        {
            this.addEventListener( BreezeEvent.READY, handle ,true, 10000 );
        }else
        {
            handle({'srcElement':element});
            addListener.call(  element ,proxyType, handle);
            addListener.call(  element ,onPrefix+'readystatechange', handle);
        }
        return true;
    });

    //扩展 ready 事件的handle
    EventDispatcher.expandHandle( BreezeEvent.READY,BreezeEvent.READY,function(event)
    {
        readyState.call(this,event,BreezeEvent.READY);
    })

    // 绑定ready事件时的构子
    EventDispatcher.bindBefore( BreezeEvent.READY ,function(element,handle,proxyType)
    {
        var doc = element.contentWindow ?  element.contentWindow.document : element.ownerDocument || element.document || element,
            win= doc &&  doc.nodeType===9 ? doc.defaultView || doc.parentWindow : null;

        if( !win || !doc )return;

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
                    handle( {'srcElement':element} );
                }
                doCheck();
            }
        }

        handle({'srcElement':element});
        addListener.call( win , onPrefix=='' ? 'DOMContentLoaded' : 'onload' , handle );
        addListener.call( doc , onPrefix+'readystatechange', handle);
        return true;
    });

    window.EventDispatcher=EventDispatcher;

})(window)