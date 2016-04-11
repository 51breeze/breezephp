(function()
{
    "use strict";

    function Ticker() {
        throw "Ticker cannot be instantiated.";
    }

    Ticker.RAF_SYNCHED = "synched";


    Ticker.RAF = "raf";


    Ticker.TIMEOUT = "timeout";


    Ticker.useRAF = false;


    Ticker.timingMode = null;


    Ticker.maxDelta = 0;


    Ticker.paused = false;

   var dispatcher=Ticker.dispatcher=new EventDispatcher();

   var  inited = false;


    var startTime = 0;


    var pausedTime=0;


    var ticks = 0;


    var  pausedTicks = 0;


    var interval = 50;


    var lastTime = 0;


    var times = null;


    var tickTimes = null;


    var timerId = null;


    var raf = true;


    Ticker.setInterval = function(val) {
        interval = val;
        if (!inited) { return; }
       setupTick();
    };


    Ticker.getInterval = function() {
        return interval;
    };


    Ticker.setFPS = function(value) {
        Ticker.setInterval(1000/value);
    };


    Ticker.getFPS = function() {
        return 1000/interval;
    };


    Ticker.init = function()
    {
        if ( inited ) { return; }
        inited = true;
        times = [];
        tickTimes = [];
        startTime = getTime();
        times.push( lastTime = 0 );
        interval = interval;
    };


    Ticker.reset = function()
    {
        if( raf )
        {
            var f = cancelAnimationFrame
                f && f( timerId );
        } else
        {
            clearTimeout( timerId );
        }
        dispatcher.cleanEventListener('tick');
        timerId =times = tickTimes = null;
        startTime = lastTime = ticks = 0;
        inited = false;
    };


    Ticker.getMeasuredTickTime = function(ticks)
    {
        var ttl=0, times=tickTimes;
        if (!times || times.length < 1) { return -1; }
        ticks = Math.min( times.length, ticks || ( Ticker.getFPS() | 0 ) );
        for (var i=0; i<ticks; i++) { ttl += times[i]; }
        return ttl / ticks;
    };

    Ticker.getMeasuredFPS = function(ticks)
    {
        var times = times;
        if (!times || times.length < 2) { return -1; }
        ticks = Math.min( times.length-1, ticks || ( Ticker.getFPS()|0 ) );
        return 1000/( ( times[0]-times[ticks] ) / ticks );
    };


    Ticker.setPaused = function(value)
    {
        Ticker.paused = value;
    };


    Ticker.getPaused = function()
    {

        return Ticker.paused;
    };


    Ticker.getTime = function(runTime)
    {
        return startTime ? getTime() - (runTime ? pausedTime : 0) : -1;
    };


    Ticker.getEventTime = function(runTime)
    {
        return startTime ? (lastTime || startTime) - (runTime ? pausedTime : 0) : -1;
    };


    Ticker.getTicks = function(pauseable)
    {
        return  ticks - ( pauseable ? pausedTicks : 0);
    };


    var now = window.performance && (performance.now || performance.mozNow || performance.msNow || performance.oNow || performance.webkitNow);
    var getTime = function()
    {
        return ( ( now && now.call( performance ) ) || ( new Date().getTime() ) ) - startTime;
    };

    var handleSynch = function()
    {
        timerId = null;
        setupTick();
        if ( getTime() - lastTime >= (interval-1)*0.97 )
        {
            tick();
        }
    };

    var handleRAF = function()
    {
        timerId = null;
        setupTick();
        tick();
    };


    var handleTimeout = function()
    {
        timerId = null;
        setupTick();
        tick();
    };

    var requestAnimationFrame=function()
    {
        return window.requestAnimationFrame    || window.webkitRequestAnimationFrame ||
               window.mozRequestAnimationFrame || window.oRequestAnimationFrame      ||
               window.msRequestAnimationFrame  || null;
    }

    var cancelAnimationFrame=function()
    {
       return window.cancelAnimationFrame    || window.webkitCancelAnimationFrame ||
              window.mozCancelAnimationFrame || window.oCancelAnimationFrame      ||
              window.msCancelAnimationFrame  ||  null;
    }


    var setupTick = function()
    {
        if (timerId != null) { return; }

        var mode = Ticker.timingMode || ( Ticker.useRAF && Ticker.RAF_SYNCHED );
        if ( mode == Ticker.RAF_SYNCHED || mode == Ticker.RAF )
        {
            var f = requestAnimationFrame();
            if (f) {
                timerId = f( mode == Ticker.RAF ? handleRAF : handleSynch );
                raf = true;
                return;
            }
        }
        raf = false;
        timerId = setTimeout( handleTimeout, interval );
    };


    var tick = function()
    {
        var paused = Ticker.paused;
        var time = getTime();
        var elapsedTime = time-lastTime;
        lastTime = time;
        ticks++;

        if( paused )
        {
            pausedTicks++;
            pausedTime += elapsedTime;
        }

        if ( dispatcher.hasEventListener("tick") )
        {
            var event = new Event("tick");
            var maxDelta = Ticker.maxDelta;
            event.delta = (maxDelta && elapsedTime > maxDelta) ? maxDelta : elapsedTime;
            event.paused = paused;
            event.time = time;
            event.runTime = time-pausedTime;
            dispatcher.dispatchEvent( event );
        }

        tickTimes.unshift( getTime()-time );
        while ( tickTimes.length > 100 ) {
            tickTimes.pop();
        }
        times.unshift( time );
        while (times.length > 100)
        {
            times.pop();
        }
    };


    window.Ticker=Ticker;


})();