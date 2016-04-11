/**
 * Created by Administrator on 15-7-26.
 */

(function(window,undefined )
{
    function makeData(data,template,forIndex)
    {
        var html=[];
        var tpl = template;
        if( Breeze.isObject(data,true)  && !Breeze.isEmpty(data) )
        {
            var flag=false;
            for(var i in data )
            {
               if( Breeze.isObject(data[i],true)  )
               {
                   var val = makeData(data[i],template,i);
                   if( val.length > 0 )
                   {
                       html.push( val ) ;
                   }
                   flag=true;

               }else
               {
                    tpl = tpl.replace(new RegExp('{'+ i.replace(/\s+/,'')+'}','ig'),data[i]);
                    tpl = tpl.replace(/\{column\}/ig,i);
               }
            }
            if( !flag  )
            {
                tpl=tpl.replace(/\{forIndex\}/ig,forIndex);
                tpl=tpl.replace(/\{.*?\}/,'');
                html.push( tpl )
            }
        }
        return html;
    }

    function merge_option(target,column,option)
    {
        var opt={}
        if( typeof column === 'string' )
        {
            if( Breeze.isFunction(option) )
            {
                opt.column=column;
                opt.callback=option;

            }else if( Breeze.isObject(option) )
            {
                opt=option;
            }

        }else if( Breeze.isObject(column) )
        {
            opt=column;
        }
        return Breeze.extend( target, opt  );
    }

    function DataGrid( target )
    {

        var theadTemplate='';
        var tbodyTemplate='';
        var makeHtml=[];
        var dataRender = null;
        var header="<th>{value}</th>";
        var body="<td>{value}</td>";
        var container="<table style='width: 100%'>\r\n<thead>{columns}</thead>\r\n<tbody>{contents}</tbody>\r\n</table>";
        var columnItem={};
        var bindData=[];
        var ismaked=false;
        var plus_data={
            'template':{},
            'option':{}
        }

        /**
         * 根据数据项编译成html
         */
        var doMake=function()
        {
            if( dataRender )
            {
                if( target instanceof Breeze )
                {
                    var html=  container.replace('{columns}', theadTemplate )
                    html = html.replace('{contents}', makeData(dataRender.toArray(),tbodyTemplate).join("\r\n") );
                    html = Breeze( html );
                    target.html( html )
                    bindAction( html );

                    target.find('[data-bind]').each(function(elem){

                        var index = this.property('data-index');
                        var name = this.property('data-bind');
                        var item = dataRender.indexToItem( index );
                        var bind = new BindData()
                            bind.bind(item,name);
                          this.addEventListener(BreezeEvent.BLUR,function(event){

                              var name = this.property('data-bind');
                              var value = this.property('value')
                                  bind.setProperty(name,value)
                          })
                    })

                }
            }
        }

        // 为每行绑定动作行为
        var bindAction=function( target )
        {
            target.find('[data-action]').each(function(elem,index){

                var action = this.property('data-action');
                if( plus_data.option[ action ] )
                {
                    var option = plus_data.option[ action ];
                    if( option.cursor )
                        this.style('cursor', option.cursor );

                    this.addEventListener( option.eventType , function(event)
                    {
                        var index =  this.property('data-index');
                        if( typeof option.callback ==='function' )
                        {
                            option.callback.call(this,index, dataRender, event );
                        }
                    })
                }
            })
        }

        this.plus=function(action,column,defualt,option)
        {
            option = merge_option(defualt,column,option || {} );
            option.template=option.template.replace(/(\<\s*(\w+))/ig,function(){

                var attr = [];
                var tag = RegExp.$2.toLowerCase();
                if( option.bindable )
                {
                    attr.push('data-bind="{column}"');
                }
                if( tag==='input' )
                {
                    attr.push('value="{'+column+'}" name="{column}"');

                }else if( tag==='select' || tag==='textarea' )
                {
                    attr.push('name="{column}"');
                }
                return RegExp.$1+' data-index="{forIndex}" data-action="'+action+'" '+attr.join(' ');
            });

            if( !plus_data.template[ column ] )
            {
                plus_data.template[ column ]=[];
            }
            plus_data.template[ column ].push( option.template );
            plus_data.option[ action ]= option;
        }

        /**
         * 编译一个显示列名行的模板
         * @param columns
         * @param header
         * @param body
         * @returns {DataGrid}
         */
        this.makeTemplate= function(columns,header,body)
        {
            if( Breeze.isObject(columns,true) && theadTemplate==='' )
            {
                theadTemplate='<tr>';
                tbodyTemplate='<tr data-row="{forIndex}">';
                for( var i in  columns )
                {
                    var field = columns[i];
                    if( Breeze.isObject( field ) && ( item.icon || item.label ) )
                    {
                        field='<i class="'+( item.icon || '' )+'"></i>';
                        field+='<span>'+( item.label || '' )+'</span>';
                    }

                   theadTemplate += header.replace('{value}', field )
                   if( plus_data.template[ i ] )
                   {
                       tbodyTemplate += body.replace('{value}', plus_data.template[ i ].join('\r\n') );
                   }else
                   {
                       tbodyTemplate += body.replace('{value}', '{'+ i.replace(/\s+/,'')+'}' );
                   }
                }
                theadTemplate+="</tr>";
                tbodyTemplate+="</tr>";
            }
            return this;
        }

        /**
         * 指定一个自定义列名行的模板
         * @param template
         * @returns {DataGrid}
         */
        this.theadTemplate=function( template )
        {
            theadTemplate = template;
            return this;
        }

        /**
         * 指定一个自定义内容行的模板
         * @param template
         * @returns {DataGrid}
         */
        this.tbodyTemplate=function( template )
        {
            tbodyTemplate = template;
            return this;
        }

        /**
         * 在模板中添加需要编辑的操作
         * @param column
         * @param option
         */
        this.edit=function(column,option)
        {
            this.plus('edit',column,{
                'template':'<a>编辑</a>',
                'callback':null,
                'eventType':MouseEvent.CLICK,
                'cursor':'pointer'
            }, option );
            return this;
        }

        /**
         * 在模板中添加需要移除的操作
         * @param column
         * @param option
         */
        this.remove=function(column,option)
        {
            this.plus('remove',column,{
                'template':'<a>删除</a>',
                'callback':null,
                'eventType':MouseEvent.CLICK,
                'cursor':'pointer'
            },option);
            return this;
        }

        /**
         * 设置指定的列名
         * @param columns
         * @returns {DataGrid}
         */
        this.columns=function( columns )
        {
            columnItem = columns;
            if ( Breeze.isString(columns) )
            {
                columnItem = columns.split(',')
            }
            return this;
        }

        /**
         * 设置指定列中需要设置的组件
         * @param column
         * @param option
         * @returns {DataGrid}
         */
        this.component=function(column,option)
        {
            this.plus('component',column,{
                'eventType':[MouseEvent.CLICK],
                'template':'<input />',
                'dataGroup':[],
                'property':{},
                'callback':null,
                'bindable':false
            }, option );
            return this;
        }

        /**
         * 设置数据属性
         * @param data
         * @returns {DataGrid}
         */
        this.dataProfile=function(data)
        {
            this.makeTemplate(columnItem, header, body);
            this.dataRender().source( data );
            return this;
        }

        /**
         * 获取数据渲染项
         * @returns {DataRender}
         */
        this.dataRender=function()
        {
            if( !dataRender )
            {
                dataRender=new DataRender();
                dataRender.addEventListener(DataRenderEvent.ITEM_ADD,function(event){

                    if( !isNaN(event.index) )
                    {
                        var list = Breeze('[data-row]:gt('+event.index+')', target )
                        Breeze('[data-row="'+event.index+'"]',target).removeElement();
                        list.each(function(elem){
                            var val= this.property('data-row');
                            this.property('data-row', val-1 );
                            Breeze('[data-index]', elem ).property('data-index',  val-1 )
                        })

                    }else
                    {
                        doMake();
                    }
                });
            }
            return dataRender
        }

    }

   window.DataGrid= DataGrid;

})( window )
