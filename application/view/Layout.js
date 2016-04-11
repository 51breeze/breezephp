/*
 * BreezeJS Layout components.
 * version: 1.0 Beta
 * Copyright © 2015 BreezeJS All rights reserved.
 * Released under the MIT license
 * https://github.com/51breeze/breezejs
 */

/**
 * 约束布局组件。
 * 通过此组件可以轻松控制元素的大小及需要对齐的位置。
 * @example
 * <div>
 *   <layout left="10" right="10" top="50" bottom="10" horizontal="left" vertical="top" />
 * </div>
 */
+(function( window,undefined )
{
    var layouts=[]
    ,rootLayout
    ,position={'left':'Left','top':'Top','right':'Right','bottom':'Bottom'}
    ,size={'explicitWidth':'ExplicitWidth','explicitHeight':'ExplicitHeight'}
    ,range={'maxWidth':'MaxWidth','maxHeight':'MaxHeight','minWidth':'MinWidth','minHeight':'MinHeight'}
    ,align={'horizontal':'Horizontal','vertical':'Vertical'}
    ,horizontal=['left','center','right']
    ,vertical=['top','middle','bottom']
    ,gap={'gap':'Gap'}
    ,dispatchLayoutEvent = function(target,property,newVal,oldVal)
    {
        if( target.hasEventListener(PropertyEvent.PROPERTY_CHANGE) )
        {
            var event=new LayoutEvent( PropertyEvent.LAYOUT_CHANGE );
            event.newValue=newVal;
            event.oldValue=oldVal;
            event.property=property;
            target.dispatchEvent(  event );
        }
    }

    LayoutEvent=function(src, props){ BreezeEvent.call(this, src, props); }
    LayoutEvent.prototype=new BreezeEvent();
    LayoutEvent.prototype.property=null;
    LayoutEvent.prototype.oldValue=null;
    LayoutEvent.prototype.newValue=null;
    LayoutEvent.LAYOUT_CHANGE='layoutChange';
    LayoutEvent=LayoutEvent;

    //根据元素查找所属的父布局对象
    function getOwnerByElement( element )
    {
        var index;
        if( element && rootLayout && window.document.body !== element ) for( index in layouts )
            if( layouts[index][0]===element ) return layouts[index];
        return null;
    }

    /**
     * Layout
     * @param target
     * @constructor
     */
    function Layout( target )
    {
        this.length=1;
        this[0]=target;
        this.__COUNTER__=layouts.length;
        if( rootLayout )
        {
            if( !Breeze.isContains(window.document.body,target ) )
                 throw new Error('invalid target in Layout');
            if( this.getStyle('position') === "static" )
                this.setStyle('position',target.parentNode === window.document.body ? 'absolute' : 'relative' );
            this.setStyle('float','left')
        }

        this.childrenItem=[];
        EventDispatcher.call( this , target );
        layouts.push( this );
        var parent,owner;
        if( rootLayout ) while( parent=target.parentNode )
        {
            owner = getOwnerByElement( parent ) || rootLayout;
            if( owner !== this )
            {
                this.owner=owner;
                owner.childrenItem.push( this );
                break;
            }
        }
        this.setProperty('islayout',true);
    }

    /**
     * 继承 Breeze
     * @type {window.Breeze}
     */
    Layout.prototype=new Breeze();

    /**
     * 初始化根布局容器
     * @returns {Layout}
     */
    Layout.prototype.getRootLayout=function()
    {
        if( !(rootLayout instanceof Layout) )
        {
            rootLayout = new Layout( window.document.body );
            rootLayout.measureWidth=function(){ return this.current(window).width(); }
            rootLayout.measureHeight=function(){return this.current(window).height(); }
            rootLayout.addEventListener('resize',function(event)
            {
                rootLayout.updateDisplayList( rootLayout.measureWidth(), rootLayout.measureHeight() );
            })
        }
        return rootLayout;
    }

    /**
     * 输出布局组件名称
     * @returns {string}
     */
    Layout.prototype.toString=function()
    {
        return 'Layout '+this.__COUNTER__;
    }

    /**
     * 测量当前元素的宽度
     * @returns {Number|string}
     */
    Layout.prototype.measureWidth=function()
    {
        var margin=this.getMargin( this[0] );
        return this.width() + margin.left + margin.right;
    }

    /**
     * 测量当前元素的高度
     * @returns {Number|string}
     */
    Layout.prototype.measureHeight=function()
    {
        var margin=this.getMargin( this[0] )
        return this.height()+margin.top + margin.bottom;
    }

    /**
     * 根据当前的布局特性计算所需要的宽度
     * @returns {Number|string|*}
     */
    Layout.prototype.calculateWidth=function( parentWidth )
    {
        var width=this.getExplicitWidth();
        this.__w__=false;
        if( isNaN( width ) )
        {
            width=parentWidth;
            var left=this.getLeft() || 0,right=this.getRight() || 0;
            if( !isNaN(left) && !isNaN(right) )
            {
               width-= left + right;
               this.__w__=true;
            }
        }
        return this.getMaxOrMinWidth( width );
    }

    /**
     * 根据当前的布局特性计算所需要的高度
     * @returns {Number|string|*}
     */
    Layout.prototype.calculateHeight=function( parentHeight )
    {
        var height=this.getExplicitHeight();
        this.__h__=false;
        if( isNaN( height ) )
        {
            height=parentHeight;
            var top=this.getTop(),bottom=this.getBottom();
            if( !isNaN(top) && !isNaN(bottom) )
            {
                height-=top + bottom;
                this.__h__=true;
            }
        }
        return this.getMaxOrMinHeight( height );
    }

    /**
     * 获取布局已设置的最大或者最小宽度
     * @param width
     * @returns {*}
     */
    Layout.prototype.getMaxOrMinWidth=function( width )
    {
        if( this.getMaxWidth() < width )
            width=this.getMaxWidth();
        if( this.getMinWidth() > width )
            width=this.getMinWidth();
        return width;
    }

    /**
     * 获取布局已设置的最大或者最小高度
     * @param width
     * @returns {*}
     */
    Layout.prototype.getMaxOrMinHeight=function( height )
    {
        if( this.getMaxHeight() < height )
            height=this.getMaxHeight();
        if( this.getMinHeight() > height )
            height=this.getMinHeight();
        return height;
    }

    Layout.prototype.getMargin=function( target )
    {
        var i,margin={'left':'Left','top':'Top','right':'Right','bottom':'Bottom'}
        for( i in margin )
            margin[i]=parseInt( Breeze.getStyle(target,'margin'+margin[i]) ) || 0;
        return margin;
    }

    /**
     * 更新布局视图
     * @returns {Layout}
     */
    Layout.prototype.updateDisplayList=function(parentWidth,parentHeight)
    {
        var horizontalAlign=this.getHorizontal()
            ,verticalAlign=this.getVertical()
            ,gap=this.getGap()
            ,h=horizontalAlign===horizontal[1] ? 0.5 : horizontalAlign===horizontal[2] ? 1 : 0
            ,v=verticalAlign  ===vertical[1]   ? 0.5 : verticalAlign  ===vertical[2]   ? 1 : 0
            ,flag=h+v > 0
            ,grid=[]
            ,columns=[]
            ,x=gap,y=gap,maxHeight= 0,countHeight= 0,countWidth=0;

        var measureWidth=this.calculateWidth( parentWidth );
        var measureHeight=this.calculateHeight( parentHeight );

        //更新子布局的显示列表
        if( this.childrenItem.length > 0 )
        {
            var i;
            for( i in this.childrenItem )
                this.childrenItem[i].updateDisplayList(measureWidth,measureHeight);
        }
        if( this === rootLayout && !this.inRootLayout )return;
         //计算子级元素需要排列的位置
        this.children(':not([includeLayout=false])').each(function(child,index)
        {
            this.setStyle('position','absolute')
            var childWidth=this.width()
                ,childHeight=this.height()
                ,margin=this.getMargin( child )

            childWidth+=margin.left + margin.right;
            childHeight+=margin.top + margin.bottom;

            //从第二个子级元素开始，如于大于了容器宽度则换行
            if( x+childWidth+gap > measureWidth && index > 0 )
            {
                if( flag )
                {
                    columns.push( x );
                    grid.push( columns );
                    columns=[];
                }
                countHeight+=maxHeight;
                countWidth=Math.max(countWidth,x);
                y+=maxHeight;
                x=gap;
                maxHeight=0;
            }

            if( flag )
            {
                columns.push( {'target':child,'left':x,'top':y} );
            }else if( !this.getProperty('islayout') )
            {
                this.setStyle('left',x);
                this.setStyle('top' ,y);
            }

            x += childWidth+gap;
            maxHeight=Math.max(maxHeight,childHeight+gap);
            countWidth=Math.max(countWidth,x);

        }).revert();
        countHeight+=maxHeight+gap;

        var realWidth=measureWidth;
        var realHeight=measureHeight;
        if( !this.__h__ || !this.__w__ )
        {
            realWidth = this.getExplicitWidth()  || this.getMaxOrMinHeight( Math.max( measureWidth,countWidth )) ;
            realHeight= this.getExplicitHeight() || this.getMaxOrMinWidth( Math.max(measureHeight,countHeight) ) ;
        }

        //需要整体排列
        if( flag )
        {
            columns.push( countWidth );
            grid.push( columns )

            var items,size,xOffset,yOffset,index,b;
             xOffset= Math.floor( (realWidth-countWidth)*h ) ;
             yOffset= Math.floor( (realHeight-countHeight)*v ) ;

            for( index in grid )
            {
               items=grid[ index ].splice(0,grid[ index ].length-1);
               size=grid[ index ];
               for( b in items )
               {
                   Breeze.setStyle(items[b].target,'left',items[b].left+xOffset );
                   Breeze.setStyle(items[b].target,'top' ,items[b].top+yOffset );
               }
            }
        }
        this.width( realWidth );
        this.height( realHeight );
    }

    /**
     * 扩展位置约束方法(获取/设置)
     */
    Breeze.forEach(position,function(method,prop){

        Layout.prototype['get'+method]=Breeze.makeFunction(function()
        {
            return this===rootLayout ? 0 : this['__'+prop+'__'] || Breeze.getLocalPosition(this[0])[prop];
        })

        Layout.prototype['set'+method]=Breeze.makeFunction(function(val)
        {
            val=parseFloat( val );
            var oldVal=this['get'+method]();
            if( oldVal===val )return this;
            this['__'+prop+'__']=val;
            if( !isNaN(val) && this!==rootLayout )
            {
                val+=parseFloat( this.getStyle('margin'+method+'Width') ) || 0;
                this.setStyle('margin'+method+'Width','0px');
                this.setStyle(prop,val);
                dispatchLayoutEvent(this,prop,val,oldVal);
            }
            return this;
        })
    })

    /**
     * 扩展尺寸方法(获取)
     */
    Breeze.forEach(size,function(method,prop)
    {
        Layout.prototype['get'+method]=Breeze.makeFunction(function()
        {
            return this['__'+prop+'__'] || NaN;
        })

        Layout.prototype['set'+method]=Breeze.makeFunction(function(val)
        {
            val=parseFloat( val );
            var oldVal=this['get'+method]();
            if( val !== oldVal )
            {
                this['__'+prop+'__'] = val;
                dispatchLayoutEvent(this,prop,val,oldVal);
            }
            return this;
        })
    })

    /**
     * 扩展最小和最大尺寸方法(获取/设置)
     */
    Breeze.forEach(range,function(method,prop)
    {
        Layout.prototype['get'+method]=Breeze.makeFunction(function()
        {
            return this['__'+prop+'__'] || parseFloat( this.getStyle(prop) ) || NaN;
        })

        Layout.prototype['set'+method]=Breeze.makeFunction(function(val)
        {
            val=parseFloat(val);
            var oldVal=this['get'+method]();
            if( oldVal===val )return this;
            this['__'+prop+'__']=val;
            this.setStyle(prop,val);
            return this;
        })
    })

    /**
     * 扩展排列方位方法(获取/设置)
     */
    Breeze.forEach( align,function(method,prop)
    {
        var a= prop=='horizontal' ? horizontal : vertical;
        Layout.prototype['get'+method]=Breeze.makeFunction(function()
        {
            return this['__'+prop+'__'] || a[0];
        })

        Layout.prototype['set'+method]=Breeze.makeFunction(function(val)
        {
            val=val.toLocaleString();
            if( Breeze.inObject(a,val) !==null  )
            {
                var oldVal=this['get'+method]();
                if( oldVal===val )return this;
                this['__'+prop+'__']=val;
                dispatchLayoutEvent(this,prop,val,oldVal);
            }
            return this;
        });
    })

    /**
     * 扩展排列间隙方法(获取/设置)
     */
    Breeze.forEach( gap,function(method,prop)
    {
        Layout.prototype['get'+method]=Breeze.makeFunction(function()
        {
            return isNaN(this['__'+prop+'__']) ? 5 : this['__'+prop+'__'] ;
        })

        Layout.prototype['set'+method]=Breeze.makeFunction(function(val)
        {
            val=parseInt(val)
            if( !isNaN(val) )
            {
                var oldVal=this['get'+method]();
                if( oldVal===val )return this;
                this['__'+prop+'__']=val;
                dispatchLayoutEvent(this,prop,val,oldVal);
            }
            return this;
        });
    })


    //初始化布局组件
    Breeze.ready(function(){

        Layout.prototype.getRootLayout();
        var method=Breeze.extend({},position,size,range,align,gap);
        Breeze('layout').each(function(target)
        {
            var element=target.parentNode;
            if( element )
            {
                element.removeChild( target );
                var prop,layout,value;
                if( element !== window.document.body )
                {
                    layout = new Layout( element )
                }else
                {
                    //是否设置了根布局容器
                    rootLayout.inRootLayout=true;
                    layout=rootLayout;
                }
                for( prop in method )
                {
                    value=target.getAttribute( prop ) || NaN;
                    if( layout !== rootLayout || ( prop in gap || prop in align ) )
                    {
                       layout[ 'set'+method[prop] ]( value );
                    }
                }
            }
        })
        rootLayout.updateDisplayList( rootLayout.measureWidth(), rootLayout.measureHeight() );
    })

})( window )
