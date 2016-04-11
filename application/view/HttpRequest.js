/*
 * BreezeJS HttpRequest class.
 * version: 1.0 Beta
 * Copyright © 2015 BreezeJS All rights reserved.
 * Released under the MIT license
 * https://github.com/51breeze/breezejs
 */

/**
 * Http 请求类
 */
(function(undefined)
{
    'use strict'

    var XMLHttpRequest=window.XMLHttpRequest || window.ActiveXObject
        ,ScriptRequest=function()
        {
            var script= document.createElement( 'script' )
                ,self=this
                ,headElement = document.head || document.getElementsByTagName( "head" )[0];

            window._JSONP_CALLBACK_=function(data)
            {
                self.responseText=data;
                self.readyState=4;
                self.status=200;
                if ( script.parentNode === headElement )
                    headElement.removeChild( script );
                if ( typeof self.onreadystatechange==='function' )
                    self.onreadystatechange( event );
                self.onreadystatechange=null;
            };

            EventDispatcher.call(this, [script] );
            self.readyState=0;
            self.status=0;

            /**
             * 创建一个元素对象
             * @param url
             */
            this.open=function(method,url)
            {
                this.url=url;
                self.readyState=2;
                self.status=1;
            }

            /**
             * 将请求对象添加到文档中，并执行请求动作。
             * @param data
             */
            this.send=function( data )
            {
                 data = typeof data==='string' ? data : '';
                 this.readyState=3;
                 this.status=2;

                 if( typeof this.url==='string' )
                 {
                     this.url+=( !/\?/.test(this.url) ? '?t=' : '&t=')+ ( new Date() ).getTime() ;
                     this.url+='&callback=_JSONP_CALLBACK_';
                     data !== '' && ( this.url+='&'+data );
                     script.setAttribute('type','text/javascript');
                     script.setAttribute('src', this.url );
                     headElement.appendChild( script );
                 }
            }

            /**
             * 终止请求
             */
            this.abort=function()
            {
                if ( script.parentNode === headElement )
                    headElement.removeChild( script );
                self.onreadystatechange=null;
            }
        }

        ,localUrl = location.href
        ,patternUrl = /^([\w\+\.\-]+:)(?:\/\/([^\/?#:]*)(?::(\d+))?)?/
        ,protocol = /^(?:about|app|app\-storage|.+\-extension|file|res|widget):$/
        ,patternHeaders = /^(.*?):[ \t]*([^\r\n]*)\r?$/mg
        ,localUrlParts = patternUrl.exec( localUrl.toLowerCase() ) || []
        ,lastModified={}
        ,etag={}
        ,HttpRequest=function( setting , target )
        {
            //为动态加载的对象实现事件监听
            this.target= target && ('onload' in target ||  'onreadystatechange' in target) ? target : null;

            //构建基本参数
            var options={
                async:true
                ,dataType: HttpRequest.TYPE.HTML
                ,method:'GET'
                ,timeout:30
                ,charset:'UTF-8'
                ,header:{
                    'contentType':  HttpRequest.FORMAT.X_WWW_FORM_URLENCODED
                    ,'Accept':HttpRequest.ACCEPT.HTML
                    ,'X-Requested-With':'XMLHttpRequest'
                }
            }

            EventDispatcher.call(this);

            if( typeof setting ==='object')for( var key in setting )
                options[ key ]=setting[key];
            else if ( typeof setting ==='string' && setting.toUpperCase() in HttpRequest.TYPE  )
                options.dataType=setting;

            if( !options.url && this.target )
                options.url=this.target.src || this.target.href || null;

            var responseHeaders=null
                ,dataType=options.dataType.toLowerCase()
                ,httpRequest
                ,isStoped=false
                ,isSend=false
                ,timeoutTimer=null
                ,self=this
                ,response=function()
                {
                    if( HttpRequest ) switch (dataType)
                    {
                       case HttpRequest.TYPE.HTML :
                       case HttpRequest.TYPE.JSONP :
                           return httpRequest.responseText;
                       case HttpRequest.TYPE.XML :
                           return httpRequest.responseXML;
                       case HttpRequest.TYPE.JSON :
                           var result = eval("(" + httpRequest.responseText + ")");
                           return result;
                    }
                    return '';
                }
                ,done=function( status , event )
                {
                    var target = event ? event.target : this;
                    if( status >=300 && self.hasEventListener( HttpEvent.ERROR ) )
                    {
                        self.dispatchEvent( new HttpEvent( HttpEvent.ERROR ,{status:status,data:response(),target:target} ) );

                    }else  if ( status >= 200 )
                    {
                        if ( ( lastModified = self.getResponseHeader( "Last-Modified" ) ) )
                            lastModified[ 'modified' ] = lastModified;
                        if ( ( etag = self.getResponseHeader( "Etag" ) ) )
                            etag[ 'modified' ] = etag;

                        if( self.hasEventListener( HttpEvent.SUCCESS ) )
                            self.dispatchEvent( new HttpEvent( HttpEvent.SUCCESS ,{status:status,data:response(),target:target }) )

                    }else
                    {
                        if( status===0 && self.hasEventListener( HttpEvent.CANCEL ) )
                            self.dispatchEvent( new HttpEvent( HttpEvent.CANCEL ,{status:-1,data:'',target:target}) )
                        else( self.hasEventListener( HttpRequest.TIMEOUT ) )
                            self.dispatchEvent( new HttpEvent( HttpEvent.TIMEOUT,{status:0,data:'',target:target} ) )
                        httpRequest.abort();
                    }
                    isSend=false;
                    isStoped=false;
                }
                ,stateChange=function( event )
                {
                    if( httpRequest.readyState==4  && isSend )
                    {
                        if ( timeoutTimer )
                        {
                            clearTimeout( timeoutTimer );
                            timeoutTimer=null;
                        }

                        if( !self.hasEventListener( HttpEvent.DONE ) ||
                          self.dispatchEvent( new HttpEvent( HttpEvent.DONE,{status:httpRequest.status,data:response(),target:target} ) ) )
                          done( httpRequest.status ,event );
                    }
                }

            /**
             * 设置Http请求头信息
             * @param name
             * @param value
             * @returns {HttpRequest}
             */
            this.setRequestHeader=function( name, value )
            {
                if ( !isSend  )
                    options.header[ name ] = value;
                return this;
            }

            /**
             * 获取已经响应的头信息
             * @param name
             * @returns {null}
             */
            this.getResponseHeader=function( name )
            {
                if( dataType === HttpRequest.TYPE.JSONP )
                   return null;
                if ( !responseHeaders && isSend && httpRequest.readyState===4  )
                {
                    responseHeaders = {};
                    var match;
                    while( ( match = patternHeaders.exec( httpRequest.getAllResponseHeaders() ) ) ) {
                        responseHeaders[ match[1].toLowerCase() ] = match[ 2 ];
                    }
                }
                return name===undefined ? responseHeaders : responseHeaders[ name.toLowerCase() ];
            }

            /**
             * 取消请求
             * @returns {Boolean}
             */
            this.cancel=function()
            {
                if ( httpRequest && isSend )
                {
                    isStoped=true;
                    httpRequest.abort();
                    done( 0 );
                    return true
                }
                return false;
            }

            /**
             * 打开一个Http 连接，并初始化http 状态。
             */
            this.open=function( url, method, async  )
            {
                typeof url ==='string' && ( options.url=url );

                if( typeof method==='string' )
                {
                    method=method.toUpperCase()
                    if( method in HttpRequest.METHOD )
                        options.method=method;
                }

                options.async = async !== false;

                if(  typeof options.url ==='string' )
                {
                    //当请求类型不是 jsonp 时并且是一个完整的http url 时判断是否跨域
                    var isurl;
                    if( dataType !== 'jsonp' && ( isurl=patternUrl.exec( options.url.toLowerCase() ) ) )
                    {
                        var cross = !!(( isurl[ 1 ] != localUrlParts[ 1 ] || isurl[ 2 ] != localUrlParts[ 2 ] ||
                            ( isurl[ 3 ] || ( isurl[ 1 ] === "http:" ? 80 : 443 ) ) !=
                                ( localUrlParts[ 3 ] || ( localUrlParts[ 1 ] === "http:" ? 80 : 443 ) ) ) );
                        if( cross )
                            throw new Error('HttpRequest does not support cross-domain,if necessary set "dataType" for the "jsonp"')
                    }

                }else
                {
                    throw new Error('HttpRequest url cannot for empty and url must is a string');
                }

                try{
                    httpRequest = dataType === 'jsonp' ? new ScriptRequest() : new XMLHttpRequest("Microsoft.XMLHTTP");
                }catch (error)
                {
                    throw new Error('HttpRequest the client does not support')
                }

                httpRequest.onreadystatechange=stateChange;
                if( !this.hasEventListener(HttpEvent.OPEN) ||
                    this.dispatchEvent( new HttpEvent( HttpEvent.OPEN ,{'target':this,'options':options}) ) )
                    httpRequest.open(options.method,options.url,options.async );
                return this;
            }

            /**
             * 发送请求
             * @param data
             * @returns {HttpRequest}
             */
            this.send=function( data )
            {
                if( !isSend && httpRequest && !isStoped )
                {
                   isSend=true;
                   if( typeof httpRequest.setRequestHeader === 'function')
                   {
                       if( !/charset/i.test(options.header.contentType) )
                           options.header.contentType +=';'+ options.charset;
                        try {
                            var name
                            for ( name in options.header )
                            {
                                httpRequest.setRequestHeader( name, options.header[ hname ] );
                            }
                        } catch(e){}
                   }

                    if( httpRequest.overrideMimeType && options.header.Accept )
                       httpRequest.overrideMimeType( options.header.Accept )

                    timeoutTimer=setTimeout( done, options.timeout * 1000 );
                    httpRequest.send( typeof data ==='string' ? data : null );
                    return true;
                }
                return false;
            }
        }

        /**
         * 继承事件类
         * @type {Object|Function}
         */
        HttpRequest.prototype   = new EventDispatcher();
        ScriptRequest.prototype = new EventDispatcher();

        /**
         * Difine constan HttpRequest accept type
         */
        HttpRequest.ACCEPT={
            XML:"application/xml,text/xml",
            HTML:"text/html",
            TEXT:"text/plain",
            JSON:"application/json, text/javascript",
            ALL:"*/*"
        };

        /**
         * Difine constan HttpRequest contentType data
         */
        HttpRequest.FORMAT={
            X_WWW_FORM_URLENCODED:"application/x-www-form-urlencoded",
            JSON:"application/json"
        };

        /**
         * Difine constan HttpRequest dataType format
         */
        HttpRequest.TYPE={
            HTML:'html',
            XML:'xml',
            JSON:'json',
            JSONP:'jsonp'
        }

        /**
         * Difine HttpRequest method
         */
        HttpRequest.METHOD={
            GET:'GET',
            POST:'POST',
            PUT:'PUT'
        };

        window.HttpRequest=HttpRequest;

})();
