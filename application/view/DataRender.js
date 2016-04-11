/**
 * Created by Administrator on 15-8-7.
 */


(function(window,undefined){

    function DataRender()
    {
        if( !(this instanceof DataRender) )
        {
            return new DataRender();
        }

        EventDispatcher.call(this);
        var items=[];
        this.length = 0;

        /**
         * 调度事件
         * @param item
         * @param type
         */
        var dispatch=function(item,type,index)
        {
            if( this.hasEventListener(type) )
            {
                var event = new DataRenderEvent(type)
                event.item=item;
                event.index = index;
                this.dispatchEvent( event );
            }
        }

        /**
         * 添加数据项到指定的索引位置
         * @param item
         * @param index
         * @returns {DataRender}
         */
        this.addItem=function(item,index)
        {
            if( item )
            {
                index = typeof index === 'number' ? index : items.length;
                index = index < 0 ? index + items.length+1 : index;
                index = Math.min( items.length, Math.max( index, 0 ) )
                items.splice(index,0,item);
                this.length = items.length;
                dispatch.call(this,item,DataRenderEvent.ITEM_ADD,index);
                dispatch.call(this,item,DataRenderEvent.ITEM_CHANGED,index);
            }
            return this;
        }

        /**
         * 移除指定索引下的数据项
         * @param index
         * @returns {boolean}
         */
        this.removeItem=function( index )
        {
            index = index < 0 ? index+items.length : index;
            if( index < items.length )
            {
                var item=items.splice(index,1);
                this.length = items.length;
                dispatch.call(this,item,DataRenderEvent.ITEM_REMOVE,index);
                dispatch.call(this,item,DataRenderEvent.ITEM_CHANGED,index);
                return true;
            }
            return false;
        }

        /**
         * 根据索引位置返回数据项
         * @param index
         * @returns {*}
         */
        this.indexToItem=function( index )
        {
            index = parseInt( index )
            if( typeof index === 'number' )
            {
                index = index < 0 ? index+ items.length : index;
                index = Math.min( items.length-1, Math.max( index, 0 ) )
                return items[ index ];
            }
            return null;
        }

        /**
         * 根据数据项返回对应的索引
         * @param item
         * @returns {number}
         */
        this.itemToIndex=function( item )
        {
            return items.indexOf( item );
        }

        /**
         * 复制数据源
         * @returns {Array}
         */
        this.toArray=function()
        {
            return items.slice(0);
        }

        var httpRequest=null;
        var defaultOption={
            'method': HttpRequest.METHOD.GET,
            'dataType':HttpRequest.TYPE.JSON,
            'callback':null,
            'param':''
        };

        this.source=function( data , option )
        {
            if( typeof data==='string' )
            {
                var self=this;
                option= Breeze.extend({},defaultOption,option);
                httpRequest= new HttpRequest()
                httpRequest.open(data,option.method )
                httpRequest.send( option.param )
                httpRequest.addEventListener(HttpEvent.SUCCESS,function(event){

                    var data=null;
                    if( typeof option.callback === 'function' )
                    {
                        data=option.callback.call(self,event);
                    }else
                    {
                        data = event.data;
                    }
                    self.source( data );
                })

            }else
            {
                items=items.concat( data );
                dispatch.call(this,items,DataRenderEvent.ITEM_ADD,NaN);
                dispatch.call(this,items,DataRenderEvent.ITEM_CHANGED,NaN);
            }
        }
    }

    DataRender.prototype = new EventDispatcher()
    DataRender.prototype.constructor=DataRender;

    function DataRenderEvent( src, props ){ BreezeEvent.call(this, src, props);}
    DataRenderEvent.prototype=new BreezeEvent();
    DataRenderEvent.prototype.item=null;
    DataRenderEvent.prototype.index=NaN;
    DataRenderEvent.prototype.constructor=DataRenderEvent;
    DataRenderEvent.ITEM_ADD='itemAdd';
    DataRenderEvent.ITEM_REMOVE='itemRemove';
    DataRenderEvent.ITEM_CHANGED='itemChanged';

    window.DataRender=DataRender
    window.DataRenderEvent=DataRenderEvent

})(window)