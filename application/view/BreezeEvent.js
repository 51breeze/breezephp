/*
 * BreezeJS BreezeEvent class.
 * version: 1.0 Bete
 * Copyright © 2015 BreezeJS All rights reserved.
 * Released under the MIT license
 * https://github.com/51breeze/breezejs
 */
(function(window,undefined){

    /**
     * BreezeEvent Class
     * 事件对象,处理指定类型的事件分发。
     * @param src
     * @param props
     * @constructor
     */
    function BreezeEvent( src, props )
    {
        if ( !(this instanceof BreezeEvent) )
            return new BreezeEvent( src, props );
        this.type = src;
        if ( src && src.type )
        {
            this.originalEvent = src;
            this.type = src.type;
            this.isDefaultPrevented = src.defaultPrevented ||
                src.defaultPrevented === undefined && src.returnValue === false ?
                true : false;
        }
        if ( props )for(var i in props)
           this[i]=props[i];
    };

    BreezeEvent.prototype = {
        target:null,
        currentTarget:null,
        isDefaultPrevented: false,
        isPropagationStopped: false,
        isImmediatePropagationStopped:false,

        preventDefault: function()
        {
             var e = this.originalEvent;
            this.isDefaultPrevented = true;
            if ( e )
            {
                e.preventDefault ? e.preventDefault() : e.returnValue = false
            }
        },

        stopPropagation: function()
        {
            var e = this.originalEvent;
            this.isPropagationStopped = true;
            if ( e && e.stopPropagation ) {
                // e.stopPropagation();
            }
        },

        stopImmediatePropagation: function()
        {
            var e = this.originalEvent;
            this.isImmediatePropagationStopped = true;
            this.isPropagationStopped = true;
            if ( e ) {
                !e.stopImmediatePropagation || e.stopImmediatePropagation();
                !e.stopPropagation ? e.cancelBubble=true : e.stopPropagation();
            }
        }
    };

    BreezeEvent.SUBMIT='submit';
    BreezeEvent.RESIZE='resize';
    BreezeEvent.SELECT='select';
    BreezeEvent.UNLOAD='unload';
    BreezeEvent.LOAD='load';
    BreezeEvent.READY_STATE_CHANGE='readystatechange';
    BreezeEvent.KEYPRESS='keypress';
    BreezeEvent.KEY_UP='keyup';
    BreezeEvent.KEY_DOWN='keydown';
    BreezeEvent.RESET='reset';
    BreezeEvent.FOCUS='focus';
    BreezeEvent.BLUR='blur';
    BreezeEvent.ERROR='error';
    BreezeEvent.COPY='copy';
    BreezeEvent.BEFORECOPY='beforecopy';
    BreezeEvent.CUT='cut';
    BreezeEvent.BEFORECUT='beforecut';
    BreezeEvent.PASTE='paste';
    BreezeEvent.BEFOREPASTE='beforepaste';
    BreezeEvent.SELECTSTART='selectstart';
    BreezeEvent.READY='ready';
    BreezeEvent.SCROLL='scroll';

    /**
     * ElementEvent
     * @param src
     * @param props
     * @constructor
     */
    function ElementEvent( src, props ){ BreezeEvent.call(this, src, props);}
    ElementEvent.prototype=new BreezeEvent();
    ElementEvent.prototype.parent=null;
    ElementEvent.prototype.constructor=ElementEvent;
    ElementEvent.ADDED='added';
    ElementEvent.REMOVED='removed';
    ElementEvent.BEFORE_ADD='beforeadd';
    ElementEvent.BEFORE_REMOVE='beforeremove';

    /**
     * PropertyEvent
     * @param src
     * @param props
     * @constructor
     */
    function PropertyEvent( src, props ){ BreezeEvent.call(this, src, props);}
    PropertyEvent.prototype=new BreezeEvent();
    PropertyEvent.prototype.property=null;
    PropertyEvent.prototype.newValue=null;
    PropertyEvent.prototype.oldValue=null;
    PropertyEvent.prototype.constructor=PropertyEvent;
    PropertyEvent.PROPERTY_CHANGE='propertychange';
    PropertyEvent.PROPERTY_COMMIT='propertycommit';
    PropertyEvent.PROPERTY_STYLE_CHANGE='propertystylechange';

    /**
     * MouseEvent
     * @param src
     * @param props
     * @constructor
     */
    function MouseEvent( src, props ){ BreezeEvent.call(this, src, props);}
    MouseEvent.prototype=new BreezeEvent();
    MouseEvent.prototype.constructor=MouseEvent;
    MouseEvent.prototype.pageX= NaN
    MouseEvent.prototype.pageY= NaN
    MouseEvent.prototype.offsetX=NaN
    MouseEvent.prototype.offsetY=NaN;
    MouseEvent.prototype.screenX= NaN;
    MouseEvent.prototype.screenY= NaN;
    MouseEvent.MOUSE_DOWN='mousedown';
    MouseEvent.MOUSE_UP='mouseup';
    MouseEvent.MOUSE_OVER='mouseover';
    MouseEvent.MOUSE_OUT='mouseout';
    MouseEvent.MOUSE_MOVE='mousemove';
    MouseEvent.CLICK='click';
    MouseEvent.DBLCLICK='dblclick';

    function HttpEvent( src, props ){ BreezeEvent.call(this, src, props);}
    HttpEvent.prototype=new BreezeEvent();
    HttpEvent.prototype.data=null;
    HttpEvent.SUCCESS = 'success';
    HttpEvent.ERROR   = 'error';
    HttpEvent.CANCEL  = 'cancel';
    HttpEvent.TIMEOUT = 'timeout';
    HttpEvent.OPEN    = 'open';

    //除了不分发 timeout 状态的事件，其它的状态都发。这个事件最先调度。
    HttpEvent.DONE    = 'done';


    window.HttpEvent=HttpEvent;
    window.BreezeEvent=BreezeEvent;
    window.ElementEvent=ElementEvent;
    window.PropertyEvent=PropertyEvent;
    window.MouseEvent=MouseEvent;

})(window)