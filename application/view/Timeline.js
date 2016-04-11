
/********************************************************
 @class : 时间轴模拟类。
          这个时间轴实现了添加关键侦和跳转到指定的侦进行播放或者停止动作。
          实现了重复播放多少次或者倒放的功能，时间轴的播放时长是由每个添加的关键侦所决定的。
 @param: fps 播放速率,按每秒来计算,默认为24侦每秒
 @example
var tl= new Timeline(60).addFrame(function(){
        console.log( this.current() +'>>'+this.__name__ )
    },3,'one').reverse( true ).addFrame(function(){
        console.log( this.current() +'>>'+this.__name__ )
    },21,'two')
    tl.play();
**********************************************************/

(function(window,undefined)
{
    'use strict';

     var animationSupport=true,prefix='webkit',
         requestAnimationFrame=window.requestAnimationFrame,
         cancelAnimationFrame=window.cancelAnimationFrame,
         vendors = ['webkit','moz','o','ms'],
         now = window.performance && (performance.now || performance.mozNow || performance.msNow ||
                                      performance.oNow || performance.webkitNow);

    for( var x = 0; x < vendors.length && !requestAnimationFrame ; x++ )
    {
        prefix = vendors[ x ];
        requestAnimationFrame = window[ prefix + 'RequestAnimationFrame'] ;
        cancelAnimationFrame  = window[ prefix + 'CancelAnimationFrame'] || window[ prefix + 'CancelRequestAnimationFrame'];
    }



    if( !requestAnimationFrame )
    {
        requestAnimationFrame = function(callback)
        {
            return window.setTimeout(callback, 16.7 );
        };
        cancelAnimationFrame = function(id)
        {
            window.clearTimeout(id);
        };
        animationSupport=false;
    }

    /**
     * @private
     * @returns {*|Function|now|jQuery.now|f.fx.now|cZ.now}
     */
     function getTime()
     {
        return ( ( now && now.call( performance ) ) || ( new Date().getTime() ) );
     };

    /**
     * @private
     * @param type
     * @param data
     */
    function dipatcher( type , data )
    {
        if( this.hasEventListener(type) )
        {
            var event= new TimelineEvent( type, data );
            this.dispatchEvent( event );
        }
    }

    /**
     * @private
     * @param name
     * @returns {*}
     */
    function getFrameIndexByName( name )
    {
        for( var index in __frame__) if(  __frame__[ index ].name===name )
        {
            return index;
        }
        return -1;
    }

    /**
     * @private
     * @param index
     * @returns {*}
     */
    function getFrameByIndex(frames, index )
    {
        if( typeof index === 'number' )
        {
            for( var i in frames ) if( frames[ i ].start <= index && frames[ i ].end >= index )
            {
                return i;
            }
        }
        return -1;
    }

    function Timeline( fps )
    {
        this.__smooth__ = fps===true;
        this.__fps__=( parseFloat(fps) || 50 );
        this.__interval__= this.__smooth__ ? 0 : Math.max(1000 / this.__fps__, 20 );
        this.__length__=0;
        this.__frame__=[];
        this.__current__=0;
        this.__repeats__=1;
        this.__reverse__=false;
        this.__tid__=null;
        this.__strict__=false;
        this.__pauseTime__=0;
        this.__pauseTimes__=0;
        this.__paused__=false;
        this.__time__=0;
        this.__startTime__=0;
        this.__lastTime__=0;
        this.__counter__=0;
        this.__flag__=true;
        this.__delay__=0;
        this.__elements__=[];
        EventDispatcher.call(this);
    }

    Timeline.prototype=new EventDispatcher();
    Timeline.prototype.constructor=Timeline;


    function TimelineEvent(src, props){BreezeEvent.call(this,src,props);}
    TimelineEvent.prototype=new BreezeEvent();
    TimelineEvent.prototype.constructor=TimelineEvent;
    TimelineEvent.PLAY='play';
    TimelineEvent.STOP='stop';
    TimelineEvent.FINISH='finish';
    TimelineEvent.REPEAT='repeat';
    TimelineEvent.REVERSE='reverse';
    TimelineEvent.ADD_FRAME='addFrame';
    TimelineEvent.REMOVE_FRAME='removeFrame';
    TimelineEvent.PAUSE='pause';
    window.TimelineEvent=TimelineEvent;

    if( animationSupport )
    {
        EventDispatcher.SpecialEvent(TimelineEvent.FINISH,function(element,listener,type,useCapture,dispatcher)
        {
            EventDispatcher.addListener(element,listener,'webkitAnimationEnd',useCapture,TimelineEvent.FINISH,function(event)
            {
                event = new BreezeEvent(event);
                event.type= TimelineEvent.FINISH;
                EventDispatcher.dispatchEvent(event);
            })
        })
    }

    Timeline.prototype.bind=function( elements )
    {
        this.__elements__=  [].concat( elements );
        EventDispatcher.call(this,this.__elements__);
        return this;
    }

    /**
     * 重复播放多少次-1为无限循环播放
     * @param number
     * @returns {Timeline}
     */
    Timeline.prototype.repeat=function( num )
    {
        num= parseInt(num) || 1;
        if( num<0 )
        {
            this.__repeats__=-1;
        }else
        {
           this.__repeats__=Math.max(num,1);
        }
        return this;
    }

    /**
     * 动画函数
     * @param fn
     * @returns {*|string}
     */
    Timeline.prototype.timingFunction=function( fn )
    {
        if( fn )
        {
           this.__function__= fn ;
        }
        return this.__function__ || 'Linear';
    }

    /**
     * 当播放头到达结尾时是否需要倒转播放
     * @param val
     * @returns {Timeline}
     */
    Timeline.prototype.reverse=function( val )
    {
        this.__reverse__=!!val;
        return this;
    }

    /**
     * 开始播放时间轴
     * @returns {Timeline}
     */
    Timeline.prototype.play=function()
    {
        this.gotoAndPlay( Math.min(this.__current__+1, this.__length__ ) );
        return this;
    }

    /**
     * 停止播放时间轴
     * @returns {Timeline}
     */
    Timeline.prototype.stop=function()
    {
        if( this.__tid__ !==null ){
            cancelAnimationFrame( this.__tid__ );
            this.__tid__=null;
        }
        dipatcher.call(this, TimelineEvent.STOP );
        this.__paused__=false;
        this.__isPlaying__=false;
        this.__time__=0;
        this.__lastTime__=0;
        this.__startTime__=0;
        this.__pauseTime__=0;
        this.__counter__=0;
        this.__delay__=0;
        return this;
    }

    /**
     * 暂停播放
     * @returns {Timeline}
     */
    Timeline.prototype.pause=function()
    {
        if( !this.__paused__ )
        {
            this.__paused__=true;
            this.__isPlaying__=false;
            if( this.__tid__ !==null ){
                cancelAnimationFrame( this.__tid__ );
                this.__tid__=null;
            }
            dipatcher.call(this, TimelineEvent.PAUSE );
            this.__pauseTime__=getTime();
        }
        return this;
    }

    /**
     * 获取已播放的时长,以毫秒为单位
     * @returns {number|*}
     */
    Timeline.prototype.time=function()
    {
        return Math.round( this.__time__ );
    }

    /**
     * 获取当前动画需要持续的总时长。
     * @returns {number}
     */
    Timeline.prototype.calculateDuration=function( length )
    {
        var interval =  this.__interval__;
        if( this.__smooth__ )
        {
            interval =  this.__d__ <= 0 ? 16.7 : this.__d__ ;
        }

        if( typeof length ==='number' )
        {
           return Math.round( length * interval-interval );
        }

         length =  this.__length__;
         var val = Math.round( length * interval * this.__repeats__ );
         val =this.__reverse__ ? val * 2 : val;
         return Math.round( val-interval );
    }

    /**
     * 时间轴的计数器，当前播放放了多少个侦格。
     * @returns {number}
     */
    Timeline.prototype.counter = function()
    {
        return this.__counter__;
    }

    /**
     * 播放头需要播放的总侦格的长度
     */
    Timeline.prototype.length=function()
    {
        var val= this.__length__ * this.__repeats__;
        return this.__reverse__ ? val * 2 : val;
    }

    /**
     * 设置播放时的延时时间
     * @param delay
     */
    Timeline.prototype.delay=function( delay )
    {
        this.__delay__ = (parseFloat( delay ) || 0) * 1000;
    }

    /**
     * 跳转到指定祯并播放
     * @param index 播放头的位置, 从1开始
     */
    Timeline.prototype.gotoAndPlay=function( index )
    {
        if( this.__isPlaying__ )
            return false;

        if( animationSupport === true )
        {
            return CSS3Animation.call(
                this,
                this.__frame__,
                this.__length__ * this.__interval__ / 1000,
                this.__repeats__,
                this.__reverse__,
                this.__delay__,
                this.__length__,
                index );
        }

        var self=this;

        //是否启用延时播放
        if( this.__delay__ > 0 )
        {
            var delay= this.__delay__;
            this.__delay__=0;
            setTimeout(function(){
                self.gotoAndPlay( index );
            },delay);
            return true;
        }

        index=Math.max(index-1,0);

        this.__isPlaying__=true;
        this.__paused__=false;
        this.__current__=index;

        //统计暂停总时间
        this.__pauseTimes__+=this.__pauseTime__ > 0 ? getTime()-this.__pauseTime__ : 0
        this.__pauseTime__=0;

        var fn =  getFunByName( this.timingFunction() );

        var frame,
            n=Math.max( this.__reverse__ ? this.__repeats__* 2 : this.__repeats__ ,1),//需要重复播放的次数
            duration=this.calculateDuration(), //此时间轴需要持续的总时间
            length=this.__length__* n, //此时间轴的总长度包括重复的次数
            running=function(val)
            {
                if( !self.__isPlaying__ )
                   return;

                var curtime=getTime() - self.__pauseTimes__ ;
                var t= Math.round( curtime - self.__startTime__ );
                var a= self.__counter__ > 0 ? Math.round( self.__interval__ / Math.max( t-self.__interval__* self.__counter__, 1 ) ) : 0 ;
                var d=  curtime - self.__lastTime__ ;

                if( duration-t <= self.__interval__ )
                {
                    a=0;
                    t=duration;
                }

                self.__time__=t;
                self.__d__=d;

                //tick
                if( d >= a || self.__smooth__ )
                {
                    //记录最近一次播放的时间
                    self.__lastTime__= curtime;
                    self.__counter__++;

                    //console.log( d ,a, self.__interval__ )

                    //播放开始
                    if(  self.__current__===0 )
                        dipatcher.call(self, TimelineEvent.PLAY );

                    //根据播放头的位置找到关键侦的位置
                    index = getFrameByIndex(self.__frame__ , self.__current__);

                    self.__index__= index;

                    //定位到指定的关键侦
                    frame=self.__frame__[ index ];

                    //调用关键侦上的方法
                    if( frame )
                    {
                        self.__name__=frame.name;
                        setStyle.call(self, frame.property , frame , fn );
                        //frame.fn.call( self );
                    }

                    var finish = self.__strict__ ? duration <= t : self.__counter__ >= length;

                    //  self.__repeats__ === -1 表示不限重复次数

                    //播放完成.
                    if( !frame || ( finish && self.__repeats__ !== -1 ) )
                    {
                        self.stop();
                        dipatcher.call(self, TimelineEvent.FINISH,{time:t} );
                        self.__counter__=0;
                    }
                    //将播放头向后移动, flag 为 false 时将播放头向前移动
                    else
                    {
                        //严格模式，根据时间定位播放头
                        if( self.__strict__ )
                        {
                            var b=Math.round( t / self.__interval__ ) % self.__length__ + 1 ;
                            var val= self.__flag__ ? b : self.__length__ - b -1  ;
                            self.__current__ = Math.min( Math.max( val, 0 ) , self.__length__ -1 );

                            //播放头是否到结尾
                            if( self.__counter__ % self.__length__===0 )
                            {
                                self.__current__ = self.__flag__ ? self.__length__ : -1 ;
                            }
                        }
                        //移动播放头
                        else
                        {
                            self.__flag__ ? self.__current__++ : self.__current__--;
                        }

                       var isRev = !self.__flag__  && self.__current__ < 0;

                        //播放头是否在结尾和开始的位置
                        if( ( self.__flag__ && self.__current__ >= self.__length__ ) || isRev  )
                        {
                            //需要重复的次数
                            if( self.__repeats__=== -1 ||  self.__counter__ < length )
                            {
                                self.__current__= 0;
                                //倒放
                                if( self.__reverse__ )
                                {
                                    isRev && dipatcher.call(self, TimelineEvent.REPEAT );
                                    self.__flag__=!self.__flag__;
                                    self.__current__=self.__flag__ ? 0 : self.__length__-1;
                                    dipatcher.call(self, TimelineEvent.REVERSE );
                                }else
                                {
                                    dipatcher.call(self, TimelineEvent.REPEAT );
                                }
                            }
                        }
                    }
                }
                self.__tid__=requestAnimationFrame( running );
            };

        //记录开始的时间
        if( this.__lastTime__ === 0 )
           this.__lastTime__ = this.__startTime__= getTime() ;

        //根据播放头的位置,计算从0到指定播放头所需要的时间增量
        if(  this.__current__ > 0 &&  this.__counter__=== 0 )
        {
            this.__startTime__ -= this.__current__ * this.__interval__;
            this.__lastTime__   = this.__startTime__;
        }

        //计数器初始值设置为与播放头同等
        if( this.__counter__ === 0 )
            this.__counter__=this.__current__;

        running(0);
        return true;
    }

    /**
     * 跳转到指定祯并停止
     * @param index
     */
    Timeline.prototype.gotoAndStop=function( index )
    {
        this.__current__= Math.max(index-1,0);
        index=getFrameByIndex(this.__frame__,this.__current__);
        if( !this.__frame__[ index ] )
        {
            console.log('index invaild');
            return false;
        }
       this.stop();
       this.__name__=this.__frame__[index].name;
       this.__frame__[index].fn.call(this);
    }

    /**
     * 每秒播放多少侦
     * @returns {number|*}
     */
    Timeline.prototype.fps=function()
    {
       return this.__fps__;
    }

    /**
     * 是否严格按时间来播放
     * @param val true 按时间来播放, false 按侦数来播放,默认为 true
     * @returns {Timeline}
     */
    Timeline.prototype.strict=function( val )
    {
       this.__strict__= this.__smooth__ ? false : !!val;
       return this;
    }

    /**
     * 当前播放到的侦
     * @returns {number}
     */
    Timeline.prototype.current=function()
    {
       return this.__current__+1;
    }

    /**
     * 获取当前侦的名称
     * @returns {*}
     */
    Timeline.prototype.currentName=function()
    {
        return this.__name__;
    }

    /**
     * 添加关键侦
     * @param fn
     * @param duration
     * @param name
     * @returns {Timeline}
     */
    Timeline.prototype.addFrame=function(property,duration,name)
    {
        var start=this.__length__;
        this.__length__ += duration ;
        var frame={'name': name || this.__frame__.length ,'property':property,start:start,end:this.__length__ ,'initValue':{}};
        this.__frame__.push( frame );
        dipatcher.call(this, TimelineEvent.ADD_FRAME , {data:frame,duration:duration} );
        return this;
    }

    /**
     * 清除所有的侦格
     */
    Timeline.prototype.cleanFrame=function()
    {
        this.__frame__=[];
        this.__length__=0;
    }

    /**
     * 删除关键侦或者裁剪时间轴
     * @param index  侦的名称或者播放头的位置
     * @param several 是否为关键侦,还是侦格
     * @returns {boolean}
     */
    Timeline.prototype.removeFrame=function( index , several )
    {
        index= typeof  index !== 'number' ? getFrameIndexByName(index) : index;
        index=getFrameByIndex(this.__frame__,index);
        if( index >=0 ) {
           var normal= typeof several === 'number';
           var frame= normal===true ? this.__frame__[index] : this.__frame__.splice(index,1)[0];
           if( frame )
           {
               var old=frame;
               var length=normal===true ? several : frame.end-frame.start,len=this.__frame__.length ;
               var i=index;
               while( i < len )
               {
                   frame=this.__frame__[ i ];
                   frame.start-=length;
                   frame.end-=length;
                   i++;
               }
               this.__length__ -=length;
               dipatcher.call(this, TimelineEvent.REMOVE_FRAME , {data:old,index:index,length:length,normal:normal} );
           }
           return true;
        }
        return false;
    }

    /*
     * Tween.js
     * t: current time（当前时间）
     * b: beginning value（初始值）
     * c: change in value（变化量）
     * d: duration（持续时间）
     */
    function setStyle( property , frame,fn )
    {
        var d = this.calculateDuration(frame.end - frame.start);
       // var durat = this.calculateDuration();
        var t = this.time()-this.calculateDuration( frame.start );
            t =  Math.min(d,t);

        for(var i in this.__elements__ )
        {
             var elem = this.__elements__[ i ];
             for( var p in property )
             {
                 var c = property[p];
                 var b=0;
                 //var percent = Math.round( this.time() / durat * 100 );
                 //var per =Math.round( this.calculateDuration(frame.end) / durat * 100 );

                 if( typeof Breeze !== 'undefined' && elem instanceof Breeze )
                 {

                     if( typeof frame.initValue[p] === 'undefined' )
                     {
                         frame.initValue[p]= parseFloat( elem.style( p ) ) || 0;
                     }

                     b = frame.initValue[p];
                     var v= fn( t, Math.min(b,c), Math.max(b,c), d );

                    // console.log( per , percent, (percent / per)*v , v)

                     v = Math.abs(b-v) ;

                     elem.style(  p , v );

                 }else
                 {
                     if( typeof frame.initValue[p] === 'undefined' )
                     {
                         frame.initValue[p]= parseFloat( elem.style[p] ) || 0;
                     }
                     b = frame.initValue[p];
                     var v= fn( t, Math.min(b,c), Math.max(b,c),d );
                     v = Math.abs(b-v);
                     elem.style[ p ] = v;
                 }
             }
        }
    }

    function getFunByName( name )
    {
        if( typeof  name  === 'string' && typeof Tween === 'object' )
        {
            name =  name.split('.');
            var fn=Tween;
            for( var i in name )
            {
                if( typeof fn[ name[i] ] === 'function' )
                {
                    fn= fn[ name[i] ];
                }
            }
            return  typeof fn === 'function' ? fn : null;
        }
        return null;
    }


    function CSS3Animation(frames, duration,repeats,reverse,delay,length,index )
    {
        var frame;
        var name = 'an'+( Math.round(new Date().getTime()/1000) + Math.floor( Math.random()*1000 ) ) ;
        var stylename= 'animation-'+name;

        var  css=[];
        for( var i in frames )
        {
            frame =frames[ i ];
            css.push( Math.round( frame.end / length * 100 ) + '% {');
            for( var p in frame.property )
            {
                css.push( p + ':' +  frame.property[p] + ';' );
            }
            css.push( '}' );
        }

       var am_prefix = prefix==='' ? '' : '-'+prefix+'-';

       css.unshift('@'+am_prefix+'keyframes ' + name + '{');
       css.push('}');
       css.push( '.'+stylename+'{' );

        var param = {
            'name':name,
            'duration':duration+'s',
            'iteration-count':(repeats || 1),
            'delay':(delay || 0)+'s',
            'fill-mode':'forwards',  //both backwards none forwards
            'direction': !!reverse ? 'alternate' : 'normal',  // alternate-reverse  reverse alternate normal
            'timing-function':'linear',  //ease  ease-in  ease-out  cubic-bezier  linear
            'play-state':'running' //paused running
        }
        for( var p in  param )
        {
            css.push(am_prefix+'animation-'+p+':'+param[p]+';');
        }
        css.push('}');
        css = css.join("\r\n");

       var style =  document.createElement('style');
           style.setAttribute('id',name);
       var head = document.getElementsByTagName('head')[0];
           style.innerHTML= css;
        head.appendChild( style );

        for(var i in this.__elements__ )
        {
            var elem = this.__elements__[ i ];
            if( typeof Breeze !== 'undefined' && elem instanceof Breeze )
            {
                elem.addClass( stylename );
            }else
            {
                var old = elem.getAttribute('class') || '';
                elem.setAttribute('class', old +' '+stylename );
            }
        }

    }

    window.Timeline=Timeline;

})(window)
