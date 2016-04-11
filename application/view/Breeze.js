/*
 * BreezeJS : JavaScript framework
 * version: 1.0 Beta
 * Copyright © 2015 BreezeJS All rights reserved.
 * Released under the MIT license
 * https://github.com/51breeze/breezejs
 */

(function(window,undefined ){ "use strict";

    if( typeof Sizzle==='undefined' )
        throw new Error('Breeze require Sizzle engine ');

    /**
     * @private
     */
    var version='1.0.0'
    ,slice =function(start,end)
    {
        if( typeof this.length !=='number' )
           return [];
       start=start || 0;
       end=this.length;
       if( Breeze.isBrowser( Breeze.BROWSER_IE , 9 ) )
       {
           var index=0,len=this.length,items=[];
           while( index < len && len > 0 )
           {
             items[index]=this[ index ];
             ++index;
           }
           return items.slice(start,end);
       }
       return Array.prototype.slice.call(this,start,end);
    }
    ,splice= Array.prototype.splice
    ,indexOf=Array.prototype.indexOf ? Array.prototype.indexOf : function(searchElement)
    {
       var len=this.length,i=0;
       for( ; i<len; i++ )if( this[i]===searchElement )
            return i;
        return -1;
    }
    ,isSimple = /^.[^:#\[\.,]*$/
    ,breezeCounter=0

    /**
     * Breeze Class
     * @param selector
     * @param context
     * @constructor
     */
    ,Breeze=function(selector,context)
    {
        if( typeof selector === 'function' )
            return Breeze.ready( selector );

        if( !(this instanceof Breeze) )
            return new Breeze( selector,context );
        else if( !Breeze.isDefined(selector) && !Breeze.isDefined(context) )
            return this;
        else if( selector instanceof Breeze )
            return selector;

        EventDispatcher.call( this );

        this.context = this.getContext( context );

        if( Breeze.isString( selector ) )
        {
            selector=Breeze.trim(selector);
            if( selector.charAt(0) === "<" && selector.charAt( selector.length - 1 ) === ">" && selector.length >= 3 )
            {
                var result=Breeze.createElement( selector )
                doMake( this, [result] );

            }else if( context instanceof Breeze )
            {
                doMake( this, context.find( selector ).toArray() );
                this.context = context.getContext();

            }else
            {
                doMake( this, Sizzle( selector, this.context ) );
            }

        }else if( selector && Breeze.isHTMLContainer(selector) || Breeze.isWindow(selector) )
        {
            doMake( this ,[selector] );
        }

        this.__rootElement__= this[0];
        this.__COUNTER__=++breezeCounter;
    }

    /**
     * 缓存数据代理类。
     * 此缓存的数据都是在对象本身进行存储，所以此类只是一个对于中间层的封装，提供一个简便的操作。
     * @private
     */
    ,defaultCacheName='__CACHE_PROXY_DATA__'
    ,getCacheRef=function( namespace ){
        var object= this[ defaultCacheName ] || ( this[ defaultCacheName ]={} );
        return namespace===undefined ? object :  object[ namespace ] || ( object[ namespace ]={} )
    }
    ,CacheProxy={
        set:function(name,value,namespace)
        {
            var object= getCacheRef.call(this,namespace)
            return  name ? object[name]=value : object;
        },
        get:function(name,namespace)
        {
            var object= getCacheRef.call(this,namespace)
            return name ? object[ name ] : object;
        },
        remove:function(name,namespace)
        {
            var object= getCacheRef.call(this,namespace)
            var value = object[name];
            delete object[name];
            return value;
        },
        removeAll:function(namespace)
        {
            if( namespace===undefined )
            {
                delete this[ defaultCacheName ];
            }else if( this[ defaultCacheName ] && this[ defaultCacheName ][namespace] )
            {
                delete this[ defaultCacheName ][namespace]
            }
        },
        getAll:function(namespace)
        {
            return Breeze.extend( {}, getCacheRef.call(this,namespace) );
        },
        hasNamespace:function(namespace)
        {
            return this[defaultCacheName] && !( typeof this[defaultCacheName][ namespace ] === 'undefined' );
        },
        hasProperty:function(name,namespace)
        {
            var object= getCacheRef.call(this,namespace)
            return !( typeof object[ name ] === 'undefined' );
        }
    }
    ,breezeInstance='__BREEZE_INSTANCE__'
    ,addBreezeInstance=function(element,instance)
    {
        var data = CacheProxy.get.call( element );
        removeBreezeInstance(element,instance);
        data[breezeInstance]===undefined && ( data[breezeInstance]=[] );
        data[ breezeInstance ].push( instance );
    }
    ,getBreezeInstance=function(element)
    {
        return CacheProxy.get.call( element , breezeInstance ) || [];
    }
    ,removeBreezeInstance=function(element,instance)
    {
        var data=CacheProxy.get.call( element, breezeInstance );
        if( Breeze.isArray( data ) )
        {
             var index = indexOf.call(data, instance );
             index >=0 && data.splice(index,1);
        }
    }

    /**
     * 重新编译元素到Breeze对象上
     * @param target
     * @param elems
     * @param reverted
     * @returns {Breeze}
     */
    ,doMake=function( target, elems ,clear , reverted ,uniqueSort )
    {
        if( target.__internal_return__ )
           return elems;

        var j = 0, i = target.length ;
        if( clear===true )
        {
            var revers=[]
            target.each(function(item,index)
            {
                //target.removeEventListener('*');
                if( reverted !== true )
                {
                    revers.unshift( item );
                }
                splice.call(target,index,1)
            })

            if( reverted !== true )
            {
                if( !target['__REVERTS__'] )
                    target['__REVERTS__']=[];
                target['__REVERTS__'].concat( revers );
            }
            i = target.length
        }

        while ( elems[j] !== undefined )
        {
            addBreezeInstance( elems[j], target );
            target[ i++ ] = elems[ j++ ];
        }

        target.length = i;

        if( uniqueSort && target.length > 1 )
        {
            var ret=Sizzle.uniqueSort( target.toArray() );
            if( ret.length !== target.length ){
               return doMake(target,ret,true,true,false);
            }
        }
        return target;
    }

    ,doGrep=function( elements, strainer, invert )
    {
        if( !Breeze.isFunction(strainer) )
            return elements;
        var ret,matches = [],i = 0,length = elements.length,expect = !invert;
        for( ; i < length; i++ )
        {
            ret = !strainer( elements[ i ], i );
            if ( ret !== expect )matches.push( elements[ i ] );
        }
        return matches;
    }

    ,doFilter=function( elements, strainer, exclude )
    {
        if ( Breeze.isFunction( strainer ) )
        {
            return doGrep( elements, function( elem, i ){
                return !!strainer.call( elem, i, elem ) !== exclude;
            });
        }

        if ( Breeze.isHTMLElement( strainer ) )
        {
            return doGrep( elements, function( elem ) {
                return ( elem === strainer ) !== exclude;
            });
        }

        if ( typeof strainer === "string" )
        {
            if ( isSimple.test( strainer ) ) {
                return doFind( elements,strainer, exclude );
            }
            strainer = doFind( elements,strainer );
        }

        return doGrep( elements, function( elem ) {
            return ( Breeze.inObject( strainer, elem ) >= 0 ) !== exclude;
        });
    }

    ,doFind = function( elements, selector, exclude )
    {
        var elem = elements[ 0 ];
        if ( exclude ) selector = ":not(" + selector + ")";
        return elements.length === 1 && elem.nodeType === 1 ? ( Sizzle.matchesSelector( elem, selector ) ? [ elem ] : [] ) :
            Sizzle.matches( selector, doGrep( elements, function( elem ) { return elem.nodeType === 1; }));
    }

    /**
     * 递归查找指定属性名的节点元素
     * @param propName
     * @param flag
     * @param strainer
     * @returns {Array}
     */
    ,doRecursion=function(propName,flag,strainer,notSort)
    {
        var target,len=!!this.forEachCurrentItem ? 1 : this.length,i= 0,currentItem,
            ret=[],hasStrainer=Breeze.isFunction( strainer);
        for( ; i< len ; i++)
        {
            target= currentItem=this.forEachCurrentItem || this[i];
            while( currentItem && ( currentItem=currentItem[ propName ] ) )
            {
                hasStrainer ? flag=strainer.call( target ,currentItem,ret ) :
                              currentItem.nodeType===1 && ( ret=ret.concat( currentItem ) );
                if( flag !== true )break;
            }
        }
        if( ret.length > 1 && !notSort )ret=Sizzle.uniqueSort( ret );
        return ret;
    }

    ,dispatchEventAll=function(target,element,event)
    {
        var bz=getBreezeInstance( element ),i=0, len= bz.length,old=target ;
        do{
            if( target instanceof EventDispatcher && target.hasEventListener( event.type ) && !target.dispatchEvent( event ) )
               return false;
            target=bz[ i ];
            target === old && ( target=null );
        }while( i<len && (++i) );
        return true;
    }
   ,dispatchElementEvent=function( target, parent, child , type )
    {
        var event=new ElementEvent( type )
        event.parent=parent;
        event.target=child
        event.currentTarget=target;
        return dispatchEventAll(target, /add/i.test(type) ? parent : child , event );
    }
    ,dispatchPropertyEvent=function(target,newValue,oldValue,property,element,type)
    {
        type = type || PropertyEvent.PROPERTY_CHANGE;
        var event=new PropertyEvent( type )
        event.newValue=newValue;
        event.oldValue=oldValue;
        event.property=property;
        event.target=element;
        event.currentTarget=target;
        return dispatchEventAll(target,element,event);
    }
    ,fix={
        attrMap:{
            'tabindex'       : 'tabIndex',
            'readonly'       : 'readOnly',
            'for'            : 'htmlFor',
            'maxlength'      : 'maxLength',
            'cellspacing'    : 'cellSpacing',
            'cellpadding'    : 'cellPadding',
            'rowspan'        : 'rowSpan',
            'colspan'        : 'colSpan',
            'usemap'         : 'useMap',
            'frameborder'    : 'frameBorder',
            'class'          : 'className',
            'contenteditable': 'contentEditable'
        }
        ,cssMap:{}
    }
    ,cssAalpha = /alpha\([^)]*\)/i
    ,cssOpacity = /opacity=([^)]*)/
    ,cssUpperProp = /([A-Z]|^ms)/g
    ,cssNum = /^[\-+]?(?:\d*\.)?\d+$/i
    ,cssNumnonpx = /^-?(?:\d*\.)?\d+(?!px)[^\d\s]+$/i
    ,cssOperator = /^([\-+])=([\-+.\de]+)/
    ,cssMargin = /^margin/
    ,cssShow = { position: "absolute", visibility: "hidden", display: "block" }
    ,cssExpand = [ "Top", "Right", "Bottom", "Left" ]
    ,cssDashAlpha = /-([a-z]|[0-9])/ig
    ,cssPrefix = /^-ms-/
    ,cssCamelCase = function( all, letter )
    {
        return ( letter + "" ).toUpperCase();
    }
    ,cssNumber={
        "fillOpacity": true,
        "fontWeight": true,
        "lineHeight": true,
        "opacity": true,
        "orphans": true,
        "widows": true,
        "zIndex": true,
        "zoom": true
    }
    ,cssHooks={}
    //+/-=value运算
    ,operatorValue=function ( value, increase,ret )
    {
        ret = ret ===undefined ? typeof value === "string" ?  cssOperator.exec( value ) : null : ret
        if ( ret && increase>0 )value = ( +( ret[1] + 1 ) * +ret[2] ) + increase;
        return value;
    }
    ,getWidthOrHeight=function( elem, name, border )
    {
        name=name.toLowerCase();
        var doc= elem.document || elem.ownerDocument || elem,
            docElem=doc.documentElement || {},
            val     = name === "width" ? elem.offsetWidth : elem.offsetHeight,
            i       = name === "width" ? 1 : 0,
            len     = 4;

        if( Breeze.isDocument(elem) || Breeze.isWindow(elem) || elem===docElem )
        {
            name=Breeze.ucfirst( name );
            if( Breeze.isWindow(elem) )
            {
                val=Math.max(
                    elem[ "inner" + name ] || 0,
                    ( Breeze.isBrowser(Breeze.BROWSER_IE) || 9 ) < 9 ?  docElem[ "offset" + name ] : 0,
                    docElem[ "client" + name ] || 0
                );

            }else
            {
                val=Math.max(
                    document.body[ "scroll" + name ] || 0, document[ "scroll" + name ] || 0
                    ,document.body[ "offset" + name ] || 0, document[ "offset" + name ] || 0
                    ,docElem[ "client" + name ] || 0
                );
                val+=docElem['client'+cssExpand[ i+2 ]] || 0;
            }

        }else if ( val > 0 )
        {
            var margin=( Breeze.isBrowser( Breeze.BROWSER_IE) || 10 ) < 9  ;
            for ( ; i < len; i += 2 )
            {
                //val -= parseFloat( Breeze.style( elem, "padding" + cssExpand[ i ] ) ) || 0;
                //如果没有指定带border 宽，默认不带边框的宽
                if( border )
                  val -= parseFloat( Breeze.style( elem, "border" + cssExpand[ i ] + "Width" ) ) || 0;

                //ie9 以下 offsetWidth 会包括 margin 边距。
                if( margin )
                  val -= parseFloat( Breeze.style( elem, "margin" + cssExpand[ i ] + "Width" ) ) || 0;
            }

        }else
        {
            val= parseInt( Breeze.style(elem,name) ) || 0;
            for ( ; i < len; i += 2 ) val += parseFloat( Breeze.style( elem, "padding" + cssExpand[ i ] + "Width" ) ) || 0;
        }
        return val || 0;
    }
    ,getChildNodes=function(element,selector,flag)
    {
        var ret=[]
            ,isfn=Breeze.isFunction(selector);
        if( element.hasChildNodes() )
        {
            var len=element.childNodes.length,index= 0,node;
            while( index < len )
            {
                node=element.childNodes.item(index);
                if( ( isfn && selector.call(this,node,index) ) || selector==='*' || node.nodeType===1  )
                     ret.push( node )
                if( flag===true && ret.length >0 )break;
                ++index;
            }
        }
        return ret;
    }
    ,outerHtml=function( element )
    {
        var html='';
        if( typeof element.outerHTML==='string' )
        {
            html=element.outerHTML;
        }else
        {
            var cloneElem=Breeze.clone( element,true),div
            if( cloneElem )
            {
                div=document.createElement( 'div' )
                div.appendChild( cloneElem );
                html=div.innerHTML;
            }
        }
        return html;
    }

    ,getStyle
    ,getPosition
    ,getOffsetPosition=function( elem ,local)
    {
        var top = 0,left = 0,width=0,height=0,stageWidth=0,stageHeight=0;
        if( Breeze.isHTMLElement(elem) )
        {
            stageWidth=getWidthOrHeight(elem.ownerDocument,'width')
            stageHeight=getWidthOrHeight(elem.ownerDocument,'height');
            do{
                top  += parseFloat( Breeze.style(elem,'borderTopWidth') )  || 0;
                left += parseFloat( Breeze.style(elem,'borderLeftWidth') ) || 0;
                top  +=elem.offsetTop;
                left +=elem.offsetLeft;
                elem=elem.offsetParent;
            }while( !local && elem )
        }
        return { 'top': top, 'left': left ,'right' : stageWidth-width-left,'bottom':stageHeight-height-top};
    };
    ; // end private variable


    //======================================================================
    //  Define Breeze Constant
    //======================================================================

    /**
     * 获取元素的样式
     * @param elem
     * @param name
     * @returns {*}
     */
    if( document.defaultView && document.defaultView.getComputedStyle )
    {
        //fix.cssMap['float']='cssFloat';
        getStyle= function( elem, name )
        {
            if( name === undefined || name==='cssText')
                return (elem.style || {} ).cssText || '';

            name=Breeze.styleName( name );
            if( cssHooks[name] && cssHooks[name].get )return cssHooks[name].get.call(elem) || '';

            var ret='',computedStyle;
            if( name==='' )return '';

            computedStyle=document.defaultView.getComputedStyle( elem, null )
            if( computedStyle )
            {
                ret = computedStyle.getPropertyValue( name );
                ret = ret === "" && Breeze.hasStyle(elem) ? elem.style[name] : ret;
            }
            return ret;
        };

    }else
    {
        fix.cssMap['float']='styleFloat';
        fix.cssMap['alpha']='opacity';
        getStyle=function( elem, name )
        {
            if( name === undefined || name==='cssText' )
                return (elem.style || elem.currentStyle || {} ).cssText || '';

            name=Breeze.styleName( name );
            if( name==='' )return '';

            var left='', rsLeft,hook=cssHooks[name]
                ,style = elem.style && elem.style[ name ] ? elem.style : elem.currentStyle && elem.currentStyle || elem.style
                ,ret = style[ name ] || '';

            if( hook && hook.get )
                ret=hook.get.call(elem,style) || '';

            //在ie9 以下将百分比的值转成像素的值
            if( cssNumnonpx.test( ret ) )
            {
                left = elem.style.left;
                rsLeft = elem.runtimeStyle && elem.runtimeStyle.left;
                if ( rsLeft )elem.runtimeStyle.left = elem.currentStyle.left;
                elem.style.left = name === "fontSize" ? "1em" : ret;
                ret = elem.style.pixelLeft + "px";
                elem.style.left = left;
                if ( rsLeft )elem.runtimeStyle.left = rsLeft;
            }
            return ret;
        };

        cssHooks.opacity={
            get: function( style )
            {
                return cssOpacity.test( style.filter || "" ) ? parseFloat( RegExp.$1 ) / 100 : 1;
            },
            set: function( style, value )
            {
                value=isNaN(value) ? 1 : Math.max( ( value > 1 ? ( Math.min(value,100) / 100 ) : value ) , 0 )
                var opacity = "alpha(opacity=" + (value* 100) + ")", filter = style.filter || "";
                style.zoom = 1;
                style.filter = Breeze.trim( filter.replace(cssAalpha,'') + " " + opacity );
                return true;
            }
        };
    }

    cssHooks.width={get: function( style ){return getWidthOrHeight(this,'width',true);}}
    cssHooks.height={get: function( style ){return getWidthOrHeight(this,'height',true);}}

    /**
     * 设置元素的样式
     * @param elem
     * @param name|cssText|object 当前name的参数是 cssText|object 时会忽略value 参数
     * @param value 需要设置的值
     */
    Breeze.style=function( elem, name, value )
    {
        if ( !Breeze.hasStyle(elem) )
            return false;

        var flag=false;

        //清空样式
        if( typeof name==='string' && /\w+[\-\_]\s*:.*?(?=;|$)/.test(name) )
        {
            value=name;
            name='cssText';
            flag=true;
        }
        //在现有样式的基础上合并。
        else if( Breeze.isObject(name) )
        {
            value=getStyle( elem )+' '+Breeze.serialize(name,'style');
            name='cssText';
            flag=true;
        }

        name = Breeze.styleName( name );
        if( flag===false && !Breeze.isScalar( value ) )
        {
            return getStyle(elem,name);
        }

        var style=elem.style;

        if( !flag )
        {
            var type = typeof value,ret,
                hook=cssHooks[name];
            if ( type === "number" && isNaN( value ) )return false;
            if ( type === "string" && (ret=cssOperator.exec( value )) )
            {
                value =operatorValue(value, parseFloat( getStyle( elem, name ) ) , ret );
                type = "number";
            }
            if ( value == null )return false;
            if ( type === "number" && !cssNumber[ name ] )
                value += "px";
            if( hook && hook.set && hook.set.call(elem,style,value)===true )return true;
        }

        try{
            style[name]=value;
        }catch( e ){}
        return true;
    }

    /**
     * 获取或者设置滚动条的位置
     * @param element
     * @param prop
     * @param val
     * @returns {number|void}
     */
    Breeze.scroll=function(element,prop,val)
    {
        var is=Breeze.isWindow( element );
        if( Breeze.isHTMLContainer( element) || is  )
        {
            var win= is ? element : element.nodeType===9 ? elem.defaultView || elem.parentWindow : null;
            var p= /left/i.test(prop) ? 'pageXOffset' : 'pageYOffset'
            if( val===undefined )
            {
                return win ? p in win ? win[ p ] : win.document.documentElement[ prop ] :  element[ prop ];
            }
            if( win ){
                win.scrollTo( p==='pageXOffset' ? val : Breeze.scroll(element,'scrollLeft'),
                              p==='pageYOffset' ? val : Breeze.scroll(element,'scrollTop') );
            }else{
                element[ prop ] = val;
            }
            return true;
        }
        return false;
    }

    /**
     * 获取元素相对舞台坐标位置
     * @param elem
     * @returns {object}
     */
    if ( "getBoundingClientRect" in document.documentElement )
    {
        getPosition = function( elem,local )
        {
            if( local )return getOffsetPosition(elem,true);
            var value={ 'top': 0, 'left': 0 ,'right' : 0,'bottom':0}
                ,box
                ,doc=elem.ownerDocument
                ,docElem= doc && doc.documentElement;

            try {
                box = elem.getBoundingClientRect();
            } catch(e) {
                box=value;
            }

            if ( !docElem || !Breeze.isContains( docElem, elem ) )
                return value;

            var body = doc.body,
            win = Breeze.getWindow( doc ),
            clientTop  = docElem.clientTop  || body.clientTop  || 0,
            clientLeft = docElem.clientLeft || body.clientLeft || 0,
            scrollTop  = win.pageYOffset ||  docElem.scrollTop  || body.scrollTop,
            scrollLeft = win.pageXOffset ||  docElem.scrollLeft || body.scrollLeft;
            value.top  = box.top  + scrollTop  - clientTop
            value.left = box.left + scrollLeft - clientLeft
            value.right = box.right - scrollLeft + clientLeft
            value.bottom = box.bottom - scrollTop + clientTop
            return value;
        };
    }
    else
    {
        getPosition = getOffsetPosition;
    }

    /**
     * 设置元素相对舞台坐标位置
     * @param elem
     * @param property left|top|right|bottom
     * @param value 需要设置的值。如果是一个布尔值则获取相对本地的位置
     * @returns {object}
     */
    Breeze.position=function( elem, property, value )
    {
        if( !Breeze.hasStyle( elem ) )
            return { 'top': 0, 'left': 0 ,'right' : 0,'bottom':0};

        var options=property;
        var position=getPosition(elem,value);
        if( !Breeze.isObject(property) )
        {
            if( !Breeze.isString(property) )
                return position;
            if( !Breeze.isScalar(value) || typeof value ==='boolean')
                return position[ property ];
            options={};
            options[property]=value;
        }

        if ( Breeze.style( elem, "position") === "static" )
            Breeze.style(elem,'position','relative');

        for( var i in options )
        {
            var ret=cssOperator.exec( options[i] )
            options[i]= ret ? operatorValue( options[i] , position[i] || 0 , ret ) : parseFloat( options[i] ) || 0;
            Breeze.style(elem,i,options[i]);
        }
        return true;
    }

    /**
     * 统一规范的样式名
     * @param name
     * @returns {string}
     */
    Breeze.styleName=function( name )
    {
        if( typeof name !=='string' )
          return name;
        name=name.replace( cssPrefix, "ms-" ).replace( cssDashAlpha, cssCamelCase );
        name = fix.cssMap[name] || name;
        return name.replace( cssUpperProp, "-$1" ).toLowerCase();
    }

    /**
     * 把颜色的值转成16进制形式 #ffffff
     * @param color
     * @returns {string}
     */
    Breeze.toHexColor = function( color )
    {
        var colorArr,strHex = "#", i,hex;
        if( /^\s*RGB/i.test( color ) )
        {
            colorArr = color.replace(/(?:[\(\)\s]|RGB)*/gi,"").split(",")
            for( i=0; i< colorArr.length && strHex.length <= 7 ; i++ )
            {
                hex = Number( colorArr[i] ).toString( 16 );
                if( hex === "0" )hex += hex;
                strHex += hex;
            }

        }else
        {
            colorArr = color.replace(/^\s*#/,"").split("");
            for( i=0; i<colorArr.length && strHex.length <= 7 ; i++)
            {
                strHex += ( colorArr[i]+colorArr[i] );
            }
        }
        return strHex;
    };

    /**
     * 把颜色的值转成RGB形式 rgb(255,255,255)
     * @param color
     * @returns {string}
     */
    Breeze.toRgbColor = function( color )
    {
        if( color )
        {
            color=Breeze.toHEX( color );
            var colorArr = [],i=1;
            for( ; i<7; i+=2 )colorArr.push( parseInt( "0x"+color.slice(i,i+2) ) );
            return "RGB(" + colorArr.join(",") + ")";
        }
        return color;
    };

    /**
     * 判断元素是否有Style
     * @param elem
     * @returns {boolean}
     */
    Breeze.hasStyle=function( elem )
    {
        return !( !elem || !elem.nodeType || elem.nodeType === 3 || elem.nodeType === 8 || !elem.style );
    }

    /**
     * 编译一个方法
     * @param fn
     * @returns {*}
     */
    Breeze.makeFunction=function( fn ){
        return fn;
    }

    /**
     * 取得当前的时间戳
     * @returns {number}
     */
    Breeze.time=function()
    {
        return ( new Date() ).getTime();
    }

    /**
     * 将字符串的首字母转换为大写
     * @param str
     * @returns {string}
     */
    Breeze.ucfirst=function( str )
    {
        return str.charAt(0).toUpperCase()+str.substr(1);
    }

    /**
     * 获取元素所在的窗口对象
     * @param elem
     * @returns {window|null}
     */
    Breeze.getWindow=function ( elem )
    {
        elem=Breeze.isHTMLElement(elem) ? elem.ownerDocument : elem ;
        return Breeze.isWindow( elem ) ? elem : elem.nodeType === 9 ? elem.defaultView || elem.parentWindow : null;
    }

    /**
     * 把一个对象序列化为一个字符串
     * @param object 要序列化的对象
     * @param type   要序列化那种类型,可用值为：url 请求的查询串,style 样式字符串。 默认为 url 类型
     * @param group  是否要用分组，默认是分组（只限url 类型）
     * @return string
     */
    Breeze.serialize=function( object, type ,group )
    {
        var str=[],key,joint='&',separate='=',val='',prefix=Breeze.isBoolean(group) ? null : group;
        type = type || 'url';
        group = ( group !== false );
        if( type==='style' )
        {
            joint=';';
            separate=':';
            group=false;
        }
        for( key in object )
        {
            val=object[key]
            key=type==='style' ? Breeze.styleName(key) : key;
            key=prefix ? prefix+'[' + key +']' : key;
            str=str.concat(  typeof val==='object' ? Breeze.serialize( val ,type , group ? key : false ) : key + separate + val  );
        }
        return str.join( joint );
    }

    /**
     * 将一个已序列化的字符串反序列化为一个对象
     * @param str
     * @returns {{}}
     */
    Breeze.unserialize=function( str )
    {
         var object={},index,joint='&',separate='=',val,ref,last,group=false;
         if( /\w+[\-\_]\s*\=.*?(?=\&|$)/.test( str ) )
         {
             str=str.replace(/^&|&$/,'')
             group=true;

         }else if( /\w+[\-\_]\s*\:.*?(?=\;|$)/.test( str ) )
         {
             joint=';';
             separate=':';
             str=str.replace(/^;|;$/,'')
         }
        str=str.split( joint )
        for( index in str )
        {
            val=str[index].split( separate )
            if( group &&  /\]\s*$/.test( val[0] ) )
            {
                ref=object,last;
                val[0].replace(/\w+/ig,function(key){
                    last=ref;
                    ref=!ref[ key ] ? ref[ key ]={} : ref[ key ];
                })
                last && ( last[ RegExp.lastMatch ]=val[1] );
            }else
            {
               object[ val[0] ]=val[1];
            }
        }
        return object;
    }

    /**
     * 克隆节点元素
     * @param nodeElement
     * @returns {Node}
     */
    Breeze.clone=function( nodeElement ,deep )
    {
        if( !Breeze.isXMLDoc( nodeElement ) && nodeElement.cloneNode )
        {
            return nodeElement.cloneNode( !!deep );
        }
        if( typeof nodeElement.nodeName==='string' )
        {
            var node = document.createElement( nodeElement.nodeName  );
            if( node )Breeze.cloneAttr(node,nodeElement);
            return node;
        }
        return null;
    }

    /**
     * 克隆元素的属性
     * @param targetElement
     * @param srcElement
     * @returns {*}
     */
    Breeze.cloneAttr=function(targetElement,srcElement)
    {
        if( Breeze.isHTMLElement(targetElement) && Breeze.isHTMLElement(srcElement) )
        {
            if( targetElement.mergeAttributes )
                targetElement.mergeAttributes( srcElement )
            else
            {
                var i= 0,item;
                while( item=srcElement.attributes.item( i++ ) )
                    targetElement.setAttribute(item.nodeName,item.nodeValue )
            }
        }
        return targetElement;
    }

    /**
     * 判断元素是否有指定的属性名
     * @param element
     * @param name
     * @returns {boolean}
     */
    Breeze.hasAttribute=function(element,name)
    {
       return typeof element.hasAttributes === 'function' ? element.hasAttributes( name ) : !!element[name];
    }

    /**
     * 判断变量是否已定义
     * @param val,...
     * @returns {boolean}
     */
    Breeze.isDefined=function()
    {
        var i=arguments.length;
        while( i>0 ) if( typeof arguments[ --i ] === 'undefined' )
            return false;
        return true;
    }

    /**
     * 判断是否为数组
     * @param val
     * @returns {boolean}
     */
    Breeze.isArray=function( val )
    {
        return val instanceof Array;
    }

    /**
     * 判断是否为函数
     * @param val
     * @returns {boolean}
     */
    Breeze.isFunction=function( val ){
        return typeof val === 'function';
    }

    /**
     * 判断是否为布尔类型
     * @param val
     * @returns {boolean}
     */
    Breeze.isBoolean=function( val ){
        return typeof val === 'boolean';
    }

    /**
     * 判断是否为字符串
     * @param val
     * @returns {boolean}
     */
    Breeze.isString=function( val )
    {
        return typeof val === 'string';
    }

    /**
     * 判断是否为一个标量
     * 只有对象类型或者Null不是标量
     * @param {boolean}
     */
    Breeze.isScalar=function( val )
    {
        var t=typeof val;
        return t==='string' || t==='number' || t==='float' || t==='boolean';
    }

    /**
     * 判断是否为数字类型
     * @param val
     * @returns {boolean}
     */
    Breeze.isNumber=function( val )
    {
        return typeof val === 'number';
    }

    /**
     * 判断是否为一个空值
     * @param val
     * @returns {boolean}
     */
    Breeze.isEmpty=function( val )
    {
        if( val===null || val==='' || val===false || val==0 || val===undefined )
            return true;

        if( Breeze.isObject(val,true) )
        {
            var ret;
            for( ret in val )break;
            return ret===undefined;
        }
        return false;
    }

    /**
     * 判断是否为一个可遍历的对象
     * @param val
     * @param flag
     * @returns {boolean}
     */
    Breeze.isObject=function( val , flag )
    {
        if( !val || val.nodeType || Breeze.isWindow(val) )
           return false;
        return typeof val === 'object' || ( flag===true && Breeze.isArray(val) ) ;
    }

    /**
     * 判断在指定的父元素中是否包含指定的子元素
     * @param parent
     * @param child
     * @returns {boolean}
     */
    Breeze.isContains=function( parent, child )
    {
        if( Breeze.isHTMLElement(parent) && Breeze.isHTMLElement(child) )
            return Sizzle.contains(parent,child);
        return false;
    }

    var formPatternReg=/select|input|textarea|button/i;

    /**
     * 判断是否为一个表单元素
     * @param element
     * @returns {boolean}
     */
    Breeze.isFormElement=function(element,exclude)
    {
        if( element && typeof element.nodeName ==='string' )
        {
            var ret=formPatternReg.test( element.nodeName );
            return ret && exclude !== undefined ? exclude !== Breeze.nodeName( element )  : ret;
        }
        return false;
    }

    /**
     * 以小写的形式返回元素的节点名
     * @param element
     * @returns {string}
     */
    Breeze.nodeName=function( element )
    {
        return  element && element.nodeName ? element.nodeName.toLowerCase() : '';
    }

    /**
     * 判断在父节点中是否可以添加移除子节点
     * @param parentNode
     * @param childNode
     */
    Breeze.isAddRemoveChildNode=function(parentNode,childNode)
    {
        var nodename=Breeze.nodeName( parentNode );
        if( nodename=='input' || nodename=='button' || Breeze.isEmpty(nodename) || parentNode===childNode || Breeze.isContains(childNode,parentNode) )
            return false;
        if( nodename=='select' )
            return Breeze.nodeName( childNode )==='option';
        else if( nodename=='textarea' )
            return childNode && childNode.nodeType===3;
        return true;
    }

    /**
     * 一组代表某个浏览器的常量
     * @type {string}
     */
    Breeze.BROWSER_IE='IE';
    Breeze.BROWSER_FIREFOX='FIREFOX';
    Breeze.BROWSER_CHROME='CHROME';
    Breeze.BROWSER_OPERA='OPERA';
    Breeze.BROWSER_SAFARI='SAFARI';
    Breeze.BROWSER_MOZILLA='MOZILLA';

    var __client__;

    /**
     * 判断是否为指定的浏览器
     * @param type
     * @returns {string|null}
     */
    Breeze.isBrowser=function( type,version,expr ){
        version= version !==undefined ? parseFloat(version) : undefined;
        expr = expr || '<';
        if( typeof __client__ === 'undefined' )
        {
            __client__ = {};
            var ua = navigator.userAgent.toLowerCase();
            var s;
            (s = ua.match(/msie ([\d.]+)/))             ? __client__[Breeze.BROWSER_IE]       = Number(s[1]) :
            (s = ua.match(/firefox\/([\d.]+)/))         ? __client__[Breeze.BROWSER_FIREFOX]  = Number(s[1]) :
            (s = ua.match(/chrome\/([\d.]+)/))          ? __client__[Breeze.BROWSER_CHROME]   = Number(s[1]) :
            (s = ua.match(/opera.([\d.]+)/))            ? __client__[Breeze.BROWSER_OPERA]    = Number(s[1]) :
            (s = ua.match(/version\/([\d.]+).*safari/)) ? __client__[Breeze.BROWSER_SAFARI]   = Number(s[1]) :
            (s = ua.match(/^mozilla\/([\d.]+)/))        ? __client__[Breeze.BROWSER_MOZILLA]  = Number(s[1]) : null ;
        }
        var result = __client__[type];
        if( result && version !== undefined )
            eval('result = result ' +expr.replace(/\s*/,'') +' version;' );
        return result;
    }

    if( Breeze.isBrowser(Breeze.BROWSER_IE,9) )
    {
        fix.attrMap['class']='className';
    }

    /**
     * 判断是否为一个HtmlElement类型元素,document 不属性于 HtmlElement
     * @param element
     * @returns {boolean}
     */
    Breeze.isHTMLElement=function( element )
    {
        return typeof HTMLElement==='object' ? element instanceof HTMLElement : element && element.nodeType === 1;
    }

    /**
     * 判断是否为一个html容器元素。
     * @param element
     * @returns {boolean|*|boolean}
     */
    Breeze.isHTMLContainer=function( element )
    {
       return Breeze.isHTMLElement( element ) || Breeze.isDocument(element);
    }

    /**
     * 判断是否为窗口对象
     * @param obj
     * @returns {boolean}
     */
    Breeze.isWindow=function( obj ) {
        return obj != null && obj == obj.window;
    }

    /**
     * 决断是否为文档对象
     * @param obj
     * @returns {*|boolean}
     */
    Breeze.isDocument=function( obj )
    {
        return obj && obj.nodeType===9;
    }

    /**
     * 判断是否为一个XML的文档格式
     * @returns {boolean}
     */
    Breeze.isXMLDoc=Sizzle.isXML

    /**
     * 查找指定的值是否在指定的对象中,如果存在返回对应的键名否则返回null。
     * @param object
     * @param val
     * @returns {*}
     */
    Breeze.inObject=function( object, val )
    {
        var key;
        if( Breeze.isObject(object,true) )for( key in object  ) if( object[ key ]===val )
            return key;
        return null;
    }

    var TRIM_LEFT = /^\s+/,TRIM_RIGHT = /\s+$/;

    /**
     * 去掉左右的空白
     * @param val
     * @returns {string}
     */
    Breeze.trim=function( val )
    {
        return typeof val==='string' ? val.replace( TRIM_LEFT, "" ).replace( TRIM_RIGHT, "" ) : '';
    }

    /**
     * 遍历一个对象
     * @param object
     * @param fn
     * @param refObj
     * @returns {*}
     */
    Breeze.forEach=function( object ,fn ,refObj )
    {
        var index= 0, result;
        if( this instanceof Breeze && Breeze.isFunction(object) )
        {
            refObj=fn;
            fn=object;
            object=this;
        }
        refObj=refObj || this;

        if( !Breeze.isFunction( fn ) )
           return refObj;

        if( object instanceof Breeze )
        {
            refObj=refObj || object;
            fn=fn;

            if( object.__current__ )
            {
                object.forEachCurrentItem=undefined;
                object.__current__=false;
            }

            if( object.forEachCurrentItem !== undefined )
            {
                result=fn.call( refObj ,object.forEachCurrentItem,object.forEachCurrentIndex);
            }else
            {
                var items=slice.call(object,0),len=items.length;
                for( ; index < len ; index++ )
                {
                    object.forEachCurrentItem=items[ index ];
                    object.forEachCurrentIndex=index;
                    result=fn.call( refObj,object.forEachCurrentItem,index);
                    if( result !== undefined )
                        break;
                }
                object.forEachCurrentItem=undefined;
            }

        }else if( Breeze.isHTMLContainer(object) || Breeze.isWindow(object) )
        {
            result=fn.call( refObj ,object,0 );
            return result === undefined ?  refObj : result;

        }else if( Breeze.isObject(object,true) )
        {
            refObj || this;
            for( index in object )
            {
                result = fn.call( refObj,object[index],index );
                if( result !== undefined )
                    break;
            }
        }
        return result === undefined ?  refObj : result;
    }

    /**
     * 合并其它参数到指定的 target 对象中
     * 如果只有一个参数则只对 Breeze 本身进行扩展。
     * @returns Object
     */
    Breeze.extend=function(){

        var options, name, src, copy, copyIsArray, clone,
            target = arguments[0] || {},
            i = 1,
            length = arguments.length,
            deep = false;

        if ( typeof target === "boolean" )
        {
            deep = target;
            target = arguments[1] || {};
            i++;
        }

        if ( length === i )
        {
            target = this;
            --i;
        }else if ( typeof target !== "object" &&  typeof target !== "function" )
        {
            target = {};
        }



        for ( ; i < length; i++ ) {
            // Only deal with non-null/undefined values
            if ( (options = arguments[ i ]) != null ) {
                // Extend the base object
                for ( name in options ) {
                    src = target[ name ];
                    copy = options[ name ];

                    // Prevent never-ending loop
                    if ( target === copy ) {
                        continue;
                    }

                    // Recurse if we're merging plain objects or arrays
                    if ( deep && copy && ( Breeze.isObject(copy) || (copyIsArray = Breeze.isArray(copy)) ) )
                    {
                        if ( copyIsArray ) {
                            copyIsArray = false;
                            clone = src && Breeze.isArray(src) ? src : [];
                        } else {
                            clone = src && Breeze.isObject(src) ? src : {};
                        }

                        // Never move original objects, clone them
                        target[ name ] = Breeze.extend( deep, clone, copy );

                        // Don't bring in undefined values
                    } else if ( copy !== undefined )
                    {
                        target[ name ] = copy;
                    }
                }
            }
        }
        return target;
    }

    var singleTagExp=/^<(\w+)\s*\/?>(?:<\/\1>|)$/;

    /**
     * 创建HTML元素
     * @param html 一个html字符串
     * @returns {Node}
     */
    Breeze.createElement=function( html )
    {
        if( Breeze.isString(html) )
        {
            html=Breeze.trim( html );
            var match;
            if( html.charAt(0) === "<" && html.charAt( html.length - 1 ) === ">" && html.length >= 3
                && ( match=singleTagExp.exec(html) ) )
                return document.createElement( match[1] );

            var div = document.createElement( "div")
                div.innerHTML =  html;
            var len=div.childNodes.length;
            if(  len > 1 )
            {
                var fragment= document.createDocumentFragment();
                while( len > 0 )
                {
                    --len;
                    fragment.appendChild( div.childNodes.item(0) );
                }
                return fragment;
            }
            div=div.childNodes.item(0);
            return div.parentNode.removeChild( div );

        }else if ( Breeze.isHTMLElement(html) && html.parentNode )
           return Breeze.clone(html,true);
        throw new Error('Breeze.createElement param invalid')
    }

    /**
     * 使用Sizle选择器选择元素
     * @param selector
     * @param context
     * @param results
     * @param seed
     * @returns {Array}
     */
    Breeze.sizzle=function(selector,context,results, seed)
    {
        return Sizzle( selector, context, results, seed);
    }

    /**
     * 格式化输出
     * @format
     * @param [...]
     * @returns {string}
     */
    Breeze.sprintf=function()
    {
        var str='',i= 1,len=arguments.length,param
        if( len > 0 )
        {
           str=arguments[0];
           for( ; i< len ; i++ )
           {
                param=arguments[i];
                str=str.replace(/%(s|d|f)/,function(all,method)
                {
                    return param;
                })
           }
           str.replace(/%(s|d|f)/,'');
        }
        return str;
    }

    var crc32Table = "00000000 77073096 EE0E612C 990951BA 076DC419 706AF48F E963A535 9E6495A3 0EDB8832 79DCB8A4 " +
        "E0D5E91E 97D2D988 09B64C2B 7EB17CBD E7B82D07 90BF1D91 1DB71064 6AB020F2 F3B97148 84BE41DE 1ADAD47D " +
        "6DDDE4EB F4D4B551 83D385C7 136C9856 646BA8C0 FD62F97A 8A65C9EC 14015C4F 63066CD9 FA0F3D63 8D080DF5 " +
        "3B6E20C8 4C69105E D56041E4 A2677172 3C03E4D1 4B04D447 D20D85FD A50AB56B 35B5A8FA 42B2986C DBBBC9D6 " +
        "ACBCF940 32D86CE3 45DF5C75 DCD60DCF ABD13D59 26D930AC 51DE003A C8D75180 BFD06116 21B4F4B5 56B3C423 " +
        "CFBA9599 B8BDA50F 2802B89E 5F058808 C60CD9B2 B10BE924 2F6F7C87 58684C11 C1611DAB B6662D3D 76DC4190 " +
        "01DB7106 98D220BC EFD5102A 71B18589 06B6B51F 9FBFE4A5 E8B8D433 7807C9A2 0F00F934 9609A88E E10E9818 " +
        "7F6A0DBB 086D3D2D 91646C97 E6635C01 6B6B51F4 1C6C6162 856530D8 F262004E 6C0695ED 1B01A57B 8208F4C1 " +
        "F50FC457 65B0D9C6 12B7E950 8BBEB8EA FCB9887C 62DD1DDF 15DA2D49 8CD37CF3 FBD44C65 4DB26158 3AB551CE " +
        "A3BC0074 D4BB30E2 4ADFA541 3DD895D7 A4D1C46D D3D6F4FB 4369E96A 346ED9FC AD678846 DA60B8D0 44042D73 " +
        "33031DE5 AA0A4C5F DD0D7CC9 5005713C 270241AA BE0B1010 C90C2086 5768B525 206F85B3 B966D409 CE61E49F " +
        "5EDEF90E 29D9C998 B0D09822 C7D7A8B4 59B33D17 2EB40D81 B7BD5C3B C0BA6CAD EDB88320 9ABFB3B6 03B6E20C " +
        "74B1D29A EAD54739 9DD277AF 04DB2615 73DC1683 E3630B12 94643B84 0D6D6A3E 7A6A5AA8 E40ECF0B 9309FF9D " +
        "0A00AE27 7D079EB1 F00F9344 8708A3D2 1E01F268 6906C2FE F762575D 806567CB 196C3671 6E6B06E7 FED41B76 " +
        "89D32BE0 10DA7A5A 67DD4ACC F9B9DF6F 8EBEEFF9 17B7BE43 60B08ED5 D6D6A3E8 A1D1937E 38D8C2C4 4FDFF252 " +
        "D1BB67F1 A6BC5767 3FB506DD 48B2364B D80D2BDA AF0A1B4C 36034AF6 41047A60 DF60EFC3 A867DF55 316E8EEF " +
        "4669BE79 CB61B38C BC66831A 256FD2A0 5268E236 CC0C7795 BB0B4703 220216B9 5505262F C5BA3BBE B2BD0B28 " +
        "2BB45A92 5CB36A04 C2D7FFA7 B5D0CF31 2CD99E8B 5BDEAE1D 9B64C2B0 EC63F226 756AA39C 026D930A 9C0906A9 " +
        "EB0E363F 72076785 05005713 95BF4A82 E2B87A14 7BB12BAE 0CB61B38 92D28E9B E5D5BE0D 7CDCEFB7 0BDBDF21 " +
        "86D3D2D4 F1D4E242 68DDB3F8 1FDA836E 81BE16CD F6B9265B 6FB077E1 18B74777 88085AE6 FF0F6A70 66063BCA " +
        "11010B5C 8F659EFF F862AE69 616BFFD3 166CCF45 A00AE278 D70DD2EE 4E048354 3903B3C2 A7672661 D06016F7 " +
        "4969474D 3E6E77DB AED16A4A D9D65ADC 40DF0B66 37D83BF0 A9BCAE53 DEBB9EC5 47B2CF7F 30B5FFE9 BDBDF21C " +
        "CABAC28A 53B39330 24B4A3A6 BAD03605 CDD70693 54DE5729 23D967BF B3667A2E C4614AB8 5D681B02 2A6F2B94 " +
        "B40BBE37 C30C8EA1 5A05DF1B 2D02EF8D";

    Breeze.crc32 = function(  str, crc )
    {
        if( crc === undefined ) crc = 0;
        var n = 0; //a number between 0 and 255
        var x = 0; //an hex number
        crc = crc ^ (-1);
        for( var i = 0, iTop = str.length; i < iTop; i++ )
        {
            n = ( crc ^ str.charCodeAt( i ) ) & 0xFF;
            x = "0x" + crc32Table.substr( n * 9, 8 );
            crc = ( crc >>> 8 ) ^ x;
        }
        return Math.abs( crc ^ (-1) );
    };

    var __rootEvent__;

    /**
     * 全局事件调度器
     * @returns {EventDispatcher}
     */
    Breeze.rootEvent=function()
    {
        if( !__rootEvent__ )
            __rootEvent__=new EventDispatcher( document );
        return __rootEvent__;
    }

    /**
     * 文档准备就绪时回调
     * @param callback
     * @return {EventDispatcher}
     */
    Breeze.ready=function( callback )
    {
        return Breeze.rootEvent().addEventListener( BreezeEvent.READY , callback );
    }

    /**
     * 导入一个可执行的脚本文件。通常是 js,css 文件。
     * @param file 脚本的文件地址。
     * @param callback 成功时的回调函数。
     */
    Breeze.require=function( file , callback )
    {
        var script;
        if( typeof file !== 'string' )
        {
            script=file;
            file= file.src || file.href;
        }

        var type = file.match(/\.(css|js)(\?.*?)?$/i)
        if( !type )throw new Error('import script file format of invalid');

        file+=( !type[2] ? '?t=' : '&t=')+Breeze.time();

        type=type[1];
        type=type.toLowerCase() === 'css' ? 'link' : 'script';

        if( !script )
        {
            var head=document.getElementsByTagName('head')[0];
            var ref=Sizzle( type +':last,:last-child',head )[0];
            ref = ref ? ref.nextSibling : null;
            script=document.createElement( type );
            head.insertBefore(script,ref);
        }

        script.onload=script.onreadystatechange=function(event)
        {
            if( !script.readyState || /loaded|complete/.test( script.readyState ) )
            {
               script.onload=script.onreadystatechange=null;
               if( typeof callback ==='function' )
                   callback( event );
            }
        }

        if( type==='link' )
        {
            script.setAttribute('rel', 'stylesheet');
            script.setAttribute('type','text/css');
            script.setAttribute('href', file );
        }else
        {
            script.setAttribute('type','text/javascript');
            script.setAttribute('src', file );
        }
    }

    /**
     * 判断是否为一个框架元素
     * @param element
     * @returns {boolean}
     */
    Breeze.isFrame=function( element )
    {
        if( element && typeof element.nodeName ==='string' )
        {
            var nodename = element.nodeName.toLowerCase()
            if( nodename === 'iframe' || nodename==='frame' )
               return true;
        }
        return false;
    }

    //======================================================================================
    //  Extends module class
    //======================================================================================

    /**
     * Extends EventDispatcher Class
     * @type {EventDispatcher}
     */
    Breeze.prototype=new EventDispatcher();

    //============================================================
    //  Defined Instance Propertys
    //============================================================

    //Breeze 构造方法
    Breeze.prototype.constructor=Breeze;

    //此对象的所有者
    Breeze.prototype.owner=null;

    //每个Breeze对象的DOM元素的作用域
    Breeze.prototype.context=null;

    // 选择器已获取到的DOM个数
    Breeze.prototype.length=0;

    Breeze.prototype.forEachCurrentItem= undefined ;

    Breeze.prototype.forEachCurrentIndex=undefined;

    //============================================================
    //  Defined Public Method
    //============================================================

    /**
     * 返回此对象名称
     * @returns {string}
     */
    Breeze.prototype.toString=function()
    {
        return 'Breeze Object '+this.__COUNTER__;
    }

    /**
     * 以数组的形式返回已选择的所有元素集
     * @returns {Array}
     */
    Breeze.prototype.toArray=function()
    {
        return slice.call( this );
    }

    /**
     * 判断指定的选择器是否在于当前匹配的项中。
     * @param selector
     * @returns {*}
     */
    Breeze.prototype.has=function( selector )
    {
        if( this.length===1 )
            return Sizzle.matchesSelector( this[0], selector );
        return Breeze.isFunction(selector) ? !!doFilter(this.toArray(),selector,false).length : !!Sizzle.matches( selector,this.toArray()).length;
    }

    /**
     * 遍历元素集
     * @type {Function}
     */
    Breeze.prototype.each=Breeze.forEach;

    /**
     * 回撒到指定步骤的选择器所匹配的元素,不包含初始化的步骤。
     * @param step
     * @returns {Breeze}
     */
    Breeze.prototype.revert=function( step )
    {
        var len= this['__REVERTS__'] ? this['__REVERTS__'].length : 0;
        step = step === undefined ? (this['__REVERT_STEP__'] || len)-1 : ( step=parseInt( step ) ) < 0 ? step+len : step ;
        step=Math.min(Math.max(step,0),len-1);
        if( len > 0 && this['__REVERTS__'][ step ] )
        {
            this['__REVERT_STEP__']=step;
            doMake( this, this['__REVERTS__'][ step ],true , true );
        }
        return this;
    }


    //==================================================
    // 指定元素进行操作
    //==================================================


    /**
     * 指定操作位于索引处的元素。
     * 此操作不会筛选，也不会保存到恢复的队列中。
     * @param index
     * @returns {Breeze}
     */
    Breeze.prototype.index=function( index )
    {
        if( index === undefined )
           return this.forEachCurrentIndex;
        if( index >= this.length || !this[index] )
           throw new Error('Index out range')
        this.forEachCurrentItem=this[index];
        this.forEachCurrentIndex=index;
        return this;
    }

    /**
     * 获取或者指定当前要操作的节点元素
     * @param element
     * @returns {Breeze|nodeElement}
     */
    Breeze.prototype.current=function( element )
    {
        if( element===true )
        {
            element=this.forEachCurrentItem || this[0];

        }else if( element === undefined )
        {
            return this.forEachCurrentItem || this[0];
        }
        if( element.nodeType===1 || element.nodeType===9 || Breeze.isWindow(element) || element===null )
        {
            //如果当前指定的是同一个对象则清空
             this.__current__= this.forEachCurrentItem === element;
             this.forEachCurrentItem=element;
        }
        return this;
    }

    //==================================================
    // 筛选匹配元素
    //==================================================

    /**
     * 在此上下文中添加选择器所匹配的元素
     * @param selector
     */
    Breeze.prototype.add=function( selector )
    {
       var ret=Breeze.sizzle(selector,this.context);
       if( ret.length > 0 )doMake( this, ret , false );
       return this;
    }

    /**
     * 筛选指定开始和结束索引值的元素。
     * @returns {Breeze}
     */
    Breeze.prototype.range=function(startIndex,endIndex)
    {
        return doMake( this, slice.call( this ,startIndex,endIndex),true);
    }

    /**
     * 筛选元素等于指定的索引
     * @param index
     * @returns {Breeze}
     */
    Breeze.prototype.eq=function( index )
    {
        return doMake( this,[ this[index] ],true);
    }

    /**
     * 筛选大于索引的元素
     * @param index
     * @returns {Breeze}
     */
    Breeze.prototype.gt=function( index )
    {
        return doMake( this, doGrep(this,function(elem,i){
            return i > index;
        }),true);
    }

    /**
     * 筛选小于索引的元素
     * @param index
     * @returns {Breeze}
     */
    Breeze.prototype.lt=function( index )
    {
        return doMake( this, doGrep(this,function(elem,i){
            return i < index;
        }),true);
    }

    /**
     * 筛选元素不等于指定筛选器的元素
     * @param index
     * @returns {Breeze}
     */
    Breeze.prototype.not=function( selector )
    {
        if( Breeze.isNumber(selector) )
            selector=this.get( selector );
        else if( Breeze.isString( selector ) )
        {
            return doMake(this, doFind(this,selector,true) ,true);
        }
        return doMake( this,doGrep(this,function(elem){ return selector !==elem; }) ,true );
    }

    /**
     * 从元素集中来检索符合回调函数的元素
     * @param elems
     * @param callback
     * @param invert
     * @returns {Breeze}
     */
    Breeze.prototype.grep=function( strainer, invert )
    {
        return doMake( this,doGrep(this,strainer,invert),true);
    }

    /**
     * 获取上下文。
     * @returns {HTMLElement}
     */
    Breeze.prototype.getContext=function( context )
    {
        if( context !== undefined )
        {
            if( context instanceof Breeze )
              return context.getContext();
            context = Breeze.isString(context) ? Sizzle(context,document)[0] : context;
        }
        var target = context || this.forEachCurrentItem || this[0] || this.context;
        if( Breeze.isFrame( target ) && target.contentWindow )
            return target.contentWindow.document;
        return Breeze.isHTMLContainer( target ) ? target :  document ;
    }

    /**
     * 查找当前匹配的第一个元素下的指定选择器的元素
     * @param selector
     * @returns {Breeze}
     */
    Breeze.prototype.find=function( selector )
    {
        return doMake( this, Sizzle(selector ) , true );
    }

    /**
     * 返回符合过滤器条件的元素集
     * @param strainer
     * @returns {Breeze}
     */
    Breeze.prototype.filter=function(strainer)
    {
        return doMake( this, doFilter(this,strainer,false) ,true );
    }

    /**
     * 查找所有匹配元素的父级元素或者指定selector的父级元素（不包括祖辈元素）
     * @param selector
     * @returns {Breeze}
     */
    Breeze.prototype.parent=function(selector)
    {
        var ret=doRecursion.call(this,'parentNode',false);
        return doMake(this , Breeze.isDefined(selector) ? doFind(ret,selector) : ret ,true );
    }

    /**
     * 查找所有匹配元素的祖辈元素或者指定selector的祖辈元素。
     * @param selector
     * @returns {Breeze}
     */
    Breeze.prototype.parents=function( selector )
    {
        var is = Breeze.isFunction(selector);
        var ret=doRecursion.call(this,'parentNode',true, selector===undefined ? null : function(element,ret)
        {
            if(  ( is && ( element=selector.call(this,element) ) ) ||
                 ( element.nodeType===1 && Sizzle.matchesSelector( element, selector ) ) )
            {
                ret.push(element);
                return false;
            }
            return true;
        });
        return doMake(this , ret ,true );
    }

    /**
     * 获取所有匹配元素向上的所有同辈元素,或者指定selector的同辈元素
     * @param name
     * @returns {Breeze}
     */
    Breeze.prototype.prevAll=function( selector )
    {
        var ret=doRecursion.call(this,'previousSibling',true);
        return doMake(this , Breeze.isDefined(selector) ? doFind(ret,selector) : ret ,true );
    }

    /**
     * 获取所有匹配元素紧邻的上一个同辈元素,或者指定selector的同辈元素
     * @param name
     * @returns {Breeze}
     */
    Breeze.prototype.prev=function(selector)
    {
        var ret=doRecursion.call(this,'previousSibling',false);
        return doMake(this , Breeze.isDefined(selector) ? doFind(ret,selector) : ret ,true );
    }

    /**
     * 获取所有匹配元素向下的所有同辈元素或者指定selector的同辈元素
     * @param name
     * @returns {Breeze}
     */
    Breeze.prototype.nextAll=function( selector )
    {
        var ret=doRecursion.call(this,'nextSibling',true);
        return doMake(this , Breeze.isDefined(selector) ? doFind(ret,selector) : ret ,true );
    }

    /**
     * 获取每一个匹配元素紧邻的下一个同辈元素或者指定selector的同辈元素
     * @param name
     * @returns {Breeze}
     */
    Breeze.prototype.next=function(selector)
    {
        var ret=doRecursion.call(this,'nextSibling',false);
        return doMake(this , Breeze.isDefined(selector) ? doFind(ret,selector) : ret ,true );
    }

    /**
     * 获取每一个匹配元素的所有同辈元素
     * @param name
     * @returns {Breeze}
     */
    Breeze.prototype.siblings=function(selector)
    {
        var ret=[].concat( doRecursion.call(this,'previousSibling',true,null,true) , doRecursion.call(this,'nextSibling',true,null,true) )
        ret=Sizzle.uniqueSort( ret );
        if( Breeze.isDefined(selector) )ret= doFind(ret,selector);
        return doMake( this ,ret, true);
    }

    /**
     * 查找所有匹配元素的所有子级元素，不包括孙元素
     * @param selector 如果是 * 返回包括文本节点的所有元素。不指定返回所有HTMLElement 元素。
     * @returns {Breeze}
     */
    Breeze.prototype.children=function( selector )
    {
        Breeze.isString( selector ) && ( selector=Breeze.trim(selector) );
        var ret=[];
        this.each(function(element)
        {
           if( !Breeze.isFrame( element ) )
             ret=ret.concat( selector==='*' ? slice.call( element.childNodes,0 ) : Sizzle( '*' ,element) );
        })
        var has=Breeze.isString(selector) && selector!=='*';
        if( this.length > 1 && !has )
            ret= Sizzle.uniqueSort(ret);
        return doMake(this , has ? doFind(ret,selector) : ret ,true );
    }

    //=================================================
    // DOM Element 操作,这是一些破坏性的操作
    //=================================================

    // 外部操作

    /**
     * 为当前所匹配每个项的位置添加元素
     * @param element 要添加的元素
     * @param index 是否添加到元素的前面
     * @returns {Breeze}
     */
    Breeze.prototype.addTarget=function( element,index )
    {
        var before = !!index;
        if( typeof index === 'number' )
        {
            this.index( index );
            before=false;
        }
        this.each(function(parent){
            this.current( parent.parentNode ).addChildAt( element, before ? parent : parent.nextSibling );
        })
        return this;
    }

    /**
     * 删除当前匹配的元素
     * @param index 删除指定位置的元素,如果不指定则会删除所有匹配的元素。
     * @returns {Breeze}
     */
    Breeze.prototype.removeElement=function( index )
    {
        if( typeof index==='number' )
            this.index( index );
        this.each(function(element){
            this.removeChildAt( element );
        })
        return this;
    }

    //内部操作


    /**
     * 移除指定的子级元素
     * @param childElemnet|selector
     * @returns {Breeze}
     */
    Breeze.prototype.removeChild=function( childElemnet )
    {
        if( typeof childElemnet==='string' )
        {
            this.each(function(elem)
            {
                var children=Sizzle(childElemnet,elem), b=0,len=children.length;
                for( ; b<len ; b++)if( children[i] && children[i].nodeType===1 && children[i].parentNode )
                {
                    this.removeChildAt( children[i] );
                }
            })

        }else
        {
            this.removeChildAt( childElemnet );
        }
        return this;
    }

    /**
     * 移除子级元素
     * @param childElemnet|index|fn  允许是一个节点元素或者是相对于节点列表中的索引位置（不包括文本节点）。
     *        也可以是一个回调函数过滤要删除的子节点元素。
     * @returns {Breeze}
     */
    Breeze.prototype.removeChildAt=function( index )
    {
        var is=false;
        if(  index !== undefined && index.parentNode ){
            this.current( index.parentNode )
            is=true;
        }else if( !Breeze.isNumber( index ) )
            throw new Error('Invalid param the index. in removeChildAt');

        return this.each(function(parent)
        {
            var child= is ? index : this.getChildAt( index );
            if( removeChild(this,parent,child) && is )
              return this;
        });
    }

    var removeChild= function(target,parent,child)
    {

        if( child && parent.hasChildNodes() && child.parentNode === parent &&
            dispatchElementEvent(target,parent,child,ElementEvent.BEFORE_REMOVE ) )
        {
            var result=parent.removeChild( child );
            dispatchElementEvent(target,parent,child,ElementEvent.REMOVED );

            // 从 Breeze 实例中移除
            if( child && child.nodeType===1 )
            {
                var bz= getBreezeInstance(child),len=bz.length,b=0;
                for(  ;b<len; b++ ) if( bz[b] instanceof Breeze )
                {
                    bz[b].removeEventListener('*');
                    bz[b].not( child );
                }
            }
            return !!result;
        }
        return false;
    }

    /**
     * 添加子级元素（所有已匹配的元素）
     * @param childElemnet
     * @returns {Breeze}
     */
    Breeze.prototype.addChild=function( childElemnet )
    {
        return this.addChildAt(childElemnet,-1);
    }

    /**
     * 在指定位置加子级元素（所有已匹配的元素）。
     * 如果 childElemnet 是一个已存在的元素，那么会先删除后再添加到当前匹配的元素中后返回，后续匹配的元素不会再添加此元素。
     * @param childElemnet 要添加的子级元素
     * @param index | refChild | fn(node,index,parent)  要添加到的索引位置
     * @returns {Breeze}
     */
    Breeze.prototype.addChildAt=function(childElemnet,index)
    {
        if( childElemnet instanceof Breeze )
        {
            var target =[].concat( childElemnet.__rootElement__ )
            return Breeze.forEach(target,function(child){
                this.addChildAt(child,index)
            },this)
        }

        if( index===undefined )
            throw new Error('Invalid param the index. in addChildAt');

        var isElement= childElemnet && childElemnet.nodeType && typeof childElemnet.nodeName === 'string';
        return this.each(function(parent)
        {
            try{
                var child=isElement ? childElemnet : Breeze.createElement( childElemnet );
            }catch(e){
                throw new Error('not is a HTMLElement type the childElemnet in addChildAt');
            }
            if( dispatchElementEvent(this,parent,child,ElementEvent.BEFORE_ADD ) )
            {
                if( child.parentNode )
                {
                   this.removeChildAt( child );
                   this.current(parent);
                }

                var refChild=index !== undefined && index.parentNode && index.parentNode===parent ? index : null;
                    !refChild && ( refChild=this.getChildAt( typeof index==='number' ? index : index ) );
                    refChild && (refChild=index.nextSibling);
                parent.insertBefore( child , refChild || null );
                dispatchElementEvent(this,parent,child,ElementEvent.ADDED );
            }
            if( isElement ) return this;
        })
    }

    /**
     * 返回指定索引位置的子级元素( 匹配选择器的第一个元素 )
     * 此方法只会计算节点类型为1的元素。
     * @param index | refChild | fn(node,index,parent)
     * @returns {Node|null}
     */
    Breeze.prototype.getChildAt=function( index )
    {
        return this.each(function(parent)
        {
            var childNodes,child=null;
            if( parent.hasChildNodes() )
            {
                if( typeof index === 'function' )
                {
                    child=getChildNodes.call(this, parent ,index ,true)[0];

                }else if( typeof index === 'number' )
                {
                    childNodes=getChildNodes.call(this,parent);
                    index=index < 0 ? index+childNodes.length : index;
                    child=index >= 0 && index < childNodes.length ? childNodes[index] : null;
                }
            }
            return child;
        })
    }

    /**
     * 返回子级元素的索引位置( 匹配选择器的第一个元素 )
     * @param childElemnet | selector
     * @returns {Number}
     */
    Breeze.prototype.getChildIndex=function( childElemnet )
    {
        if( typeof childElemnet==='string' )
        {
            childElemnet=Sizzle.matches( childElemnet, this.toArray() )[0];
            if( !childElemnet )return -1;
            this.current( childElemnet.parentNode );
        }
        return this.each(function(parent)
        {
            if( childElemnet.parentNode===parent )
            {
                var index=-1;
                getChildNodes(parent,function(node){
                      if( node.nodeType === 1 )index++;
                      return node===childElemnet;
                },true)
                return index;
            }
            return -1;
        });
    }

    /**
     * 用指定的元素来包裹当前所有匹配到的元素
     * @param element
     * @returns {Breeze}
     */
    Breeze.prototype.wrap=function( element )
    {
       var is=Breeze.isFunction( element );
       return this.each(function(elem,index)
       {
            var wrap=Breeze.createElement( is ? element.call(this,elem,index) : element );
            this.current( elem.parentNode ).addChildAt( wrap , elem );
            this.current( wrap ).addChildAt( elem ,-1);
       });
    }

    /**
     * 取消当前所有匹配元素的父级元素。不指定选择器则默认为父级元素，否则为指定选择器的祖辈元素。
     * 父级或者祖辈元素只能是body的子元素。
     * @param selector
     * @returns {Breeze}
     */
    Breeze.prototype.unwrap=function( selector )
    {
        var is= selector === undefined;
        return this.each(function(elem)
        {
            this.__internal_return__=true;
            var parent= is ?  elem.parentNode : this.parents( selector )[0];

            if( parent && parent.ownerDocument && Sizzle.contains( parent.ownerDocument.body, parent ) )
            {
               var children=this.current( parent ).children('*');
               if( parent.parentNode )
               {
                   this.current( parent.parentNode );
                   var len=children.length,i=0;
                   while( i<len ){
                      this.addChildAt( children[ i++ ], parent );
                   }
                   this.removeChildAt( parent );
               }
            }
            this.__internal_return__=false;
        });
    }

    /**
     * 获取或者设置 html
     * @param html
     * @returns {string | Breeze}
     */
    Breeze.prototype.html=function( html , outerHtml )
    {
        outerHtml = !!outerHtml;
        var write= html !== undefined || Breeze.isBoolean(html);
        if( !write && this.length < 1 ) return '';
        return this.each(function(elem)
        {
            if( !write )
            {
                html = html===true ? outerHtml(elem) : elem.innerHTML;
                return html.replace( defaultCacheName ,'');
            }

            if( elem.hasChildNodes() )
            {
                var nodes=elem.childNodes;
                var len=nodes.length,b=0;
                for( ; b < len ; b++ ) if( nodes[b] && nodes[b].nodeType===1 )
                {
                   if( !removeChild( this, elem, nodes[b] ) )
                     return this;
                }
            }

            elem.innerHTML='';
            if( Breeze.isString(html) )
            {
                if( outerHtml ) {
                    elem.outerHTML = html;
                }else
                {
                    elem.innerHTML = html;
                }
            }else
            {
                if( outerHtml && elem.parentNode && elem.parentNode.ownerDocument && Breeze.isContains(elem.parentNode.ownerDocument.body, elem.parentNode) )
                {
                    this.current( elem.parentNode );
                }
                this.addChild( html );
            }
        });
    }

    //访问和操作属性值
    function access(name,newValue,callback,eventProp,eventType)
    {
        var write= newValue !== undefined;
        if( !write && this.length < 1 )return '';
        return this.each(function(elem)
        {
            var oldValue= callback.get.call(elem,name);
            if( !write ) return oldValue;
            if( oldValue !== newValue )
            {
                callback.set.call(elem,name,newValue);
                eventProp && dispatchPropertyEvent(this,newValue,oldValue,eventProp,elem,eventType);
            }
        });
    }

    /**
     * 获取或者设置文本内容
     * @param text
     * @returns {}
     */
    Breeze.prototype.text=function( value )
    {
       return access.call(this,'text',value,{
           get:function(prop){
                return Sizzle.getText(this) || '';
           },
           set:function(prop,newValue){
               typeof this.textContent === "string" ? this.textContent=newValue : this.innerText=newValue;
           }
        },'text');
    }

    /**
     * 设置所有匹配元素的样式
     * @param name
     * @param value
     * @returns {Breeze}
     */
    Breeze.prototype.style=function( name,value )
    {
        if( typeof name === 'string' && /^\s*\w+[\-\_]\s*:.*?(?=;|$)/.test(name)  )
        {
            value=name;
            name='cssText';
        }else if( Breeze.isObject(name) )
        {
            value=Breeze.serialize(name,'style');
            name='cssText';
        }
        return access.call(this,name,value,{
            get:function(prop){
                return Breeze.style(this,prop) || '';
            },
            set:function(prop,newValue){
                Breeze.style(this,prop,newValue);
            }
        },name,PropertyEvent.PROPERTY_STYLE_CHANGE);
    }

    /**
     * 为当前每个元素设置数据缓存
     * @param name
     * @param value
     * @returns {Breeze}
     */
    Breeze.prototype.data=function(name,value)
    {
        return access.call(this,name,value,{
            get:function(prop){
                return CacheProxy.get.call( this ,prop,'__DATASET__') || '';
            },
            set:function(prop,newValue){
                CacheProxy.set.call( this ,prop,newValue,'__DATASET__')
            }
        });
    }

    var __property__={
        'className':true,
        'innerHTML':true,
        'value'    :true
    }

    /**
     * 为每一个元素设置属性值
     * @param name
     * @param value
     * @returns {Breeze}
     */
    Breeze.prototype.property=function(name,value )
    {
        name=fix.attrMap[name] || name;
        var lower=name.toLowerCase();
        if( lower==='innerhtml' )
          return this.html(value);
        else if( lower === 'style' )
          throw new Error('the style property names only use style method to operate in property');
        return access.call(this,name,value,{
            get:function(prop){
                return ( __property__[ prop ] ? this[ prop ] : this.getAttribute( prop ) ) || '';
            },
            set:function(prop,newValue){
                if( newValue === null )
                {
                    __property__[ prop ] ? delete this[ prop ] : this.removeAttribute( prop );
                    return;
                }
                __property__[ prop ] ? this[ prop ]=newValue : this.setAttribute( prop,newValue );
            }
        },name);
    }

    /**
     * 判断是否有指定的类名
     * @param className
     * @returns {boolean}
     */
    Breeze.prototype.hasClass=function( className )
    {
        var value=this.property('class')
        return value === '' ? false : typeof className==='string' ?
               new RegExp('(\\s|^)' + className + '(\\s|$)').test( value ) : true ;
    }

    /**
     * 添加指定的类名
     * @param className
     * @returns {Breeze}
     */
    Breeze.prototype.addClass=function( className )
    {
        if( typeof className==='string' && !this.hasClass(className) )
        {
            var oldClass=this.property('class');
            this.property('class',Breeze.trim(oldClass+" " + className))
        }
        return this;
    }

    /**
     * 移除指定的类名或者清除所有的类名。
     * @param className
     * @returns {Breeze}
     */
    Breeze.prototype.removeClass=function(className)
    {
        if( className !==undefined && typeof className === 'string' )
        {
            var value=this.property('class') || '';
            var reg = new RegExp('(\\s|^)' + className + '(\\s|$)');
            var newVal=value.replace(reg, '');
            if( value!==newVal )this.property('class',newVal );
        }else
        {
            this.property('class', null );
        }
        return this;
    }

    var __size__=function(prop,value)
    {
        var border = typeof value==='boolean' ? value : ( value===undefined || value==='border' );
        value = (value===undefined || value==='border' || typeof value==='boolean') ? undefined : parseFloat( value );
        return access.call(this,prop, value,{
            get:function(prop){
                return getWidthOrHeight(this, prop, border );
            },
            set:function(prop,newValue){
                Breeze.style(this,prop,newValue);
            }
        },prop.toLowerCase());
    }

    var __scroll__=function(prop,value)
    {
        return access.call(this,prop, value,{
            get:function(prop){
                return Breeze.scroll(this,'scroll'+prop);
            },
            set:function(prop,newValue){
                Breeze.scroll(this,'scroll'+prop,newValue);
            }
        },'scroll'+prop );
    }

    var __position__=function(prop,value)
    {
        var local= true;
        if( typeof value==='boolean' )
        {
            local=!value;
            value=undefined;
        }
        return access.call(this,prop, value,{
            get:function(prop){
                return Breeze.position(this,prop,local);
            },
            set:function(prop,newValue){
                Breeze.position(this,prop,newValue);
            }
        },prop );
    }

    /**
     * 设置所有匹配元素的宽度
     * @param val
     * @returns {Breeze}
     */
    Breeze.prototype.width=function( value )
    {
        return __size__.call(this,'width',value);
    }

    /**
     * 获取匹配第一个元素的高度
     * @param border 是否包括边框的宽度
     * @returns {Number}
     */
    Breeze.prototype.height=function( value )
    {
        return __size__.call(this,'height',value);
    }

    /**
     * 设置元素滚动条左边的位置
     * @returns {Breeze}
     */
    Breeze.prototype.scrollLeft=function(val)
    {
        return __scroll__.call(this,'Left',val)
    }

    /**
     * 获取元素滚动条顶边的位置
     * @returns {number}
     */
    Breeze.prototype.scrollTop=function(val)
    {
        return __scroll__.call(this,'Top',val)
    }

    /**
     * 获取或者设置相对于父元素的左边位置
     * @param val 如果是布尔类型则会返回坐标位置。 true 相对于本地, false 相对于全局的坐标位置，默认为 false。
     * @returns {number|Breeze}
     */
    Breeze.prototype.left=function( val )
    {
        return __position__.call(this,'left',val)
    }

    /**
     * 获取或者设置相对于父元素的顶边位置
     * @param val 如果是布尔类型则会返回坐标位置。 true 相对于本地, false 相对于全局的坐标位置，默认为 false。
     * @returns {number|Breeze}
     */
    Breeze.prototype.top=function( val )
    {
        return __position__.call(this,'top',val)
    }

    /**
     * 获取或者设置相对于父元素的右边位置
     * @param val 如果是布尔类型则会返回坐标位置。 true 相对于本地, false 相对于全局的坐标位置，默认为 false。
     * @returns {number|Breeze}
     */
    Breeze.prototype.right=function( val )
    {
        return __position__.call(this,'right',val)
    }

    /**
     * 获取或者设置相对于父元素的底端位置
     * @param val 如果是布尔类型则会返回坐标位置。 true 相对于本地, false 相对于全局的坐标位置，默认为 false。
     * @returns {number|Breeze}
     */
    Breeze.prototype.bottom=function( val )
    {
        return __position__.call(this,'bottom',val)
    }

    var __point__=function(left,top,local)
    {
        var target=this.forEachCurrentItem || this[0];
        var point={}
        point['left']=left || 0;
        point['top']=top || 0;
        if( target && target.parentNode )
        {
            var offset=Breeze.position( target.parentNode );
            if( local )
            {
                point['left']+=offset['left'];
                point['top']+=offset['top'];
            }else
            {
                point['left']-=offset['left'];
                point['top']-=offset['top'];
            }
        }
        return point;
    }

    /**
     * 将本地坐标点转成相对视图的全局点
     *  @param left
     *  @param top
     *  @returns {object} left top
     */
    Breeze.prototype.localToGlobal=function( left,top  )
    {
       return __point__.call(this,left,top,true);
    }

    /**
     *  将视图的全局点转成相对本地坐标点
     *  @param left
     *  @param top
     *  @returns {object}  left top
     */
    Breeze.prototype.globalToLocal=function( left,top )
    {
        return __point__.call(this,left,top,false);
    }

    /**
     * 设置当前元素的显示或者隐藏
     * @param flag false 为隐藏
     * @returns {Breeze}
     */
    Breeze.prototype.display=function( flag )
    {
        flag= Breeze.isBoolean(flag) ? flag : true;
        return this.current(true).style('display',flag ? 'block' : 'none');
    }

    /**
     * 执行一个动画
     * @param options
     * @param callback
     * @returns {*}
     */
    Breeze.prototype.animation=function( options ,callback )
    {
        var tl=  new Timeline().bind( this.toArray() )
        options=[].concat( options );
        for( var i in options )
        {
            var d = options[i].duration;
            delete options[i].duration
            tl.addFrame( options[i] , d );
        }
        if( typeof callback === 'function' )
        {
           tl.addEventListener(TimelineEvent.FINISH,callback);
        }
        tl.play();
        return tl;
    }

    /**
     * 将一个元素淡入
     * @param duration
     * @param callback
     * @returns {Breeze}
     */
    Breeze.prototype.fadeIn=function(duration,callback)
    {
        this.style('opacity',0);
        this.animation({'opacity':1,'duration':duration},callback);
        return this;
    }

    /**
     * 将一个元素淡出
     * @param duration
     * @param callback
     * @returns {Breeze}
     */
    Breeze.prototype.fadeOut=function(duration,callback)
    {
        this.style('opacity',1);
        this.animation({'opacity':0,'duration':duration},callback);
        return this;
    }

    window.Breeze=Breeze;

})( window )
