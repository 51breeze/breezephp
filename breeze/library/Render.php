<?php

namespace breeze\library;
use breeze\core\Error;
use breeze\core\EventDispatcher;
use breeze\core\Lang;
use breeze\core\Single;
use breeze\events\RenderEvent;
use breeze\interfaces\IRender;
use breeze\interfaces\ISingle;

/**
 * 视图渲染器
 * Class Render
 * @package breeze\library
 */
class Render extends EventDispatcher implements IRender
{
    /**
     * @public
     * 版本号
     */
    const VERSION='1.0.0';

    /**
     * @public
     * 文件编码
     */
    public $charset='UTF-8';

    /**
     * @public
     * 开启调试
     */
    public $debug=true;

    /**
     * @public
     * 编译后的路径
     */
    public $compile_path='';

    /**
     * @public
     * 模板文件的路径
     */
    public $template_path='';

    /**
     * @public
     * 模板文件的后缀
     */
    public $template_suffix='.php';

    /**
     * @public
     * 缓存文件的路径
     */
    public $cache_path='';

    /**
     * @public
     * 缓存文件的后缀
     */
    public $cache_suffix='.html';

    /**
     * @public
     * 缓存的有效期
     */
    public $cache_expire=86400;

    /**
     * @public
     * 是否将模板写入缓存
     */
    public $cacheEnable=true;

    /**
     * @public
     * 是否需要将内容压缩存放
     */
    public $compression=false;

    /**
     * @public
     * 左定界符
     */
    public $leftDelimit='<';

    /**
     * @public
     * 右定界符
     */
    public $rightDelimit='>';

    /**
     * @public
     * 闭合标签
     */
    public $closeTag='/';

    /**
     * @private
     * 分枝标签
     */
    protected $branchTags=array('elseif'=>'if','case'=>'switch','default'=>'switch','else'=>'empty' );

    /**
     * @private
     * 必须成对出现的标签
     */
    protected $pairTags=array('loop','while','dowhile','if','switch','empty','for');

    /**
     * @private
     * 普通标签
     */
    protected $tags=array('var','echo','call','empty','include');

    /**
     * @private
     * 之后需要处理的标签
     */
    protected $afterTags=array('include');

    /**
     * @private
     * 压缩算法,默认算法的优先级是从左到右。
     */
    protected $compressionFun=array('deflate','gzip');

    /**
     * @private
     */
    protected $compressed=null;

    /**
     * @private
     */
    protected $variable=array();

    /**
     * @private
     */
    protected $tpl_content=null;

    /**
     * @private
     */
    private $mbstring_overload=false;

    /**
     * Constructs.
     */
    public function __construct( $options=null )
    {
        if( is_array($options) || is_object($options) ) foreach( $options as $prop=>$value)
        {
            if( property_exists($this,$prop ) )
            {
                $this->$prop = $value;
            }
        }
        Part::initialize( $this , $this->fetchTags() );
        $this->mbstring_overload = ini_get('mbstring.func_overload') & 2;
    }

    /**
     * @param $tagname
     * @param $callback
     * @param bool $paired
     * @param null $group
     */
    public function extension($tagname,$callback,$paired=false,$group=null)
    {
        $tagname=time($tagname);
        if( $paired===true )
           array_push( $this->pairTags,$tagname );
        else
           array_push( $this->tags,$tagname );

        if( $group!==null )
        {
            if( !in_array($group,$this->pairTags) )
                throw new Error( Lang::info(4006) );
            $this->branchTags[ $tagname ]=$group;
        }

        if( !is_callable($callback) )
            throw new Error( Lang::info(4007) );
        $this->addEventListener($tagname,$callback);
    }

    /**
     * 指定一组变量
     * @param $name
     * @param $value
     */
    public function assign( $name, $value=null )
    {
        if( is_string($name) )
        {
            $this->variable[ $name ]=$value;
            return $this;
        }

        if( is_array($name) )
        {
            $this->variable = array_merge($this->variable, $name);
            return $this;
        }
        trigger_error('invalid variable name', E_USER_WARNING );
    }

    /**
     * 输出指定的模板
     * @param $name
     * @return string
     */
    public function dispaly( $name, $cache=true, $debug=true )
    {
        return $this->fetch( $name ,$cache , $debug);
    }

    /**
     * 取出指定的模板内容
     * @param $name
     */
    protected function fetch( $name, $cache=true, $debug=true )
    {
        if( !is_string($name) || empty($name) )
        {
            throw new Error('file name cannot is empty');
        }

        //是否获取缓存内容
        $cacheEnable=($this->cacheEnable || $cache) && $debug!==true;

        //获取压缩方法名
        $compres=$this->getCompression();

        $html=null;
        if( $cacheEnable )
        {
            //缓存文件路径
            $cache_path=$this->getCachePath( sprintf('%s%s',$name,!empty($compres) ? '-'.$compres : '' ) );
            $html=$cacheEnable ? $this->getCacheHtml( $cache_path ) : null;
        }

        if( $html===null )
        {
            $html=$this->compileTemplate( $name , $this->variable ,$cache,$debug);

            if( $debug===true )
            {
                $html.= $this->debuginfo( $this->debuginfo );

            }else
            {
                //压缩数据
                $this->compressionHandle($html,$compres);
            }

            //设置缓存数据
            if( $cacheEnable )
            {
                $this->setCacheHtml( $html , $cache_path );
            }
        }
        return $html;
    }

    /**
     * 获取分枝标签
     * @return array
     */
    protected function & fetchBranchTags()
    {
        return $this->branchTags;
    }

    /**
     * @private
     */
    protected  function getTemplatePath($name)
    {
        $tpl_path=ltrim($this->template_path,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name.$this->template_suffix;
        if( !file_exists( $tpl_path ) )
            throw new Error( Lang::info(1102) );
        return $tpl_path;
    }

    /**
     * @private
     */
    protected function getCompilePath( $name )
    {
        return ltrim($this->compile_path,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.md5($name).'.php';
    }

    /**
     * @private
     */
    protected function getCachePath( $name )
    {
        return ltrim($this->cache_path,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name.$this->cache_suffix;
    }

    /**
     * @private
     */
    private function fetchTags()
    {
        static $tags=null;
        $tags===null && $tags=array_merge( $this->pairTags ,$this->tags ,array_keys( $this->branchTags ) );
        return $tags;
    }

    /**
     * @private
     */
    private $debuginfo=array();

    /**
     * @private
     */
    private function compileTemplate( $tpl_name , array $variable ,$cacheEnable=false,$debugEnable=false)
    {
        //模板文件路径
        $tpl_path          = $this->getTemplatePath( $tpl_name );
        $compile_path      = $this->getCompilePath( $tpl_name );
        $error             = array();
        $warning           = array();
        $event             = null;
        $data              = null;
        $error_handler     = false;
        $__RENDER__        = $this;

        ob_start();

        /*
         * 发送开始事件
         * 可以接入其它编译器和选择其它模板内容进行编译,当侦听器使用 $event->preventDefault() 方法后会跳过默认编译器。
         */
        if( $this->hasEventListener( RenderEvent::COMPILE_START ) )
        {
            $event=new RenderEvent( RenderEvent::COMPILE_START );
            $event->content= & $this->tpl_content;
            $event->compile_path= & $compile_path;
            $event->template_path= & $tpl_path;

            //已经编译好的数据
            if( !$this->dispatchEvent( $event ) )
                $data= $event->content;
        }

        //导出变量到当前作用域
        if( !empty( $variable ) )
        {
            extract( $variable ,EXTR_IF_EXISTS OR EXTR_REFS );
        }

        //判断此模板是否编译过
        if( DEBUG===false &&  $data===null && file_exists( $compile_path ) )
        {
            $updated=false;

            //检查是否有更新
            $this->hasEventListener( RenderEvent::UPDATED ) ||
            $this->addEventListener(RenderEvent::UPDATED,function(RenderEvent $event)use( &$updated,$tpl_path,$__RENDER__)
            {
                if( $event->result['md5'] !== md5_file( $tpl_path ) || Render::VERSION !== $event->result['version'] )
                {
                    $event->preventDefault();
                    $updated=true;
                }
            });

            include( $compile_path );
            if( $updated===false )
            {
               return ob_get_clean();
            }

        }
        //开如一个新的编译
        else
        {
            if( !isset( $this->debuginfo[ $tpl_path ] ) )
                $this->debuginfo[ $tpl_path ]=array();

            $debug= & $this->debuginfo[ $tpl_path ];

            /**
             * 捕获 trigger_error() 抛出的错误信息。
             * 至于在什么时候来触发这些错误信息，必须在编译中实现。
             */
            $this->hasEventListener( RenderEvent::DEBUGING ) ||
            $this->addEventListener( RenderEvent::DEBUGING ,function(RenderEvent $event) use( $__RENDER__ , & $error_handler, &$debug )
            {
                if( $error_handler===false )
                {
                    $error_handler=true;
                    set_error_handler( function($errno , $errstr ,$errfile,$errline ) use( $event , $__RENDER__, &$debug )
                    {
                        $pos=strpos($errstr,':');
                        if( $pos===false )
                            return;
                        $name=trim( $__RENDER__->substr($errstr,$pos+1) );
                        if( stripos($errstr,'variable')!==false && stripos($errstr,'array')===false )
                        {
                            $line=array_search($name, $event->result['var'] );
                            $line===false || $debug[]=Lang::info(4009,'在第'.$line,$name);

                        }else if(stripos($errstr,'function')!==false)
                        {
                            $line=array_search($name, $event->result['fun'] );
                            $line===false || $debug[]=Lang::info(4010,'在第'.$line,$name);
                        }else if(stripos($errstr,'array')!==false)
                        {
                            $line=array_search($name, $event->result['var'] );
                            $line===false || $debug[]=Lang::info(4011,'在第'.$line,$name);
                        }

                    },E_ALL);
                }
            });
        }

        //获取模板文件的内容
        if( empty( $this->tpl_content ) )
        {
            $this->tpl_content = file_get_contents( $tpl_path );
        }

        /*
         * 系统默认编译器
         * 当编译器获取到每个正确的模板标签后将发起解析的指令，这个指令由每个对应的侦听器解析完后返回。
         * 如果要改变某个标签的语法则可以添加特定的侦听器来改变。
         */
        if( $data===null )
        {
            $data=$this->compiler( $error, $warning , $cacheEnable,$debugEnable );

            //处理错误信息
            $error_str='';
            $minline=0;
            empty( $error['unclosed'] ) || $error_str=implode(PHP_EOL, $this->getErrorInfo( $error['unclosed'] ,$minline,Lang::info(4002) ) ).PHP_EOL;
            empty( $error['error']    ) || $error_str.=implode(PHP_EOL,$this->getErrorInfo( $error['error'], $minline,'' ) );
            if( !empty( $error_str ) ) throw new Error( $error_str,502,$tpl_path,$minline );

        }

        //添加内部代码
        $data=$this->internal( $tpl_path ,$tpl_name , $compile_path, $warning ).$data ;
        $this->tpl_content=null;
        $write=true;

        /*
         * 发送完成事件
         * 当模板编译完成后发起此事件，这主要是改变并处理自定义的错误信息和一些模板语法的检查与替换工作。
         */
        if( $this->hasEventListener( RenderEvent::COMPILE_DONE ) )
        {
            $event=new RenderEvent( RenderEvent::COMPILE_DONE );
            $event->content= & $data;
            $event->error =  & $error;
            $event->warning =  & $warning;
            $event->compile_path= & $compile_path;
            $event->template_path=& $tpl_path;
            $write=$this->dispatchEvent( $event );
        }

        if( $write )file_put_contents( $compile_path, $data );
        eval('?>'.$data);
        if( $error_handler===true )restore_error_handler();
        return ob_get_clean();
    }

    /**
     * @private
     */
    private function debuginfo( & $debug )
    {
        $info='';
        if( !empty($debug) && $this->debug===true )
        {
            $info=PHP_EOL.'<script>'.PHP_EOL;
            foreach( $debug as $file=>$item )
            {
                if( !empty($item) )
                $info.='var debug_info="<h2>'.$file.'</h2><li>'.implode('</li>'.PHP_EOL.'<li>',$item).'</li>";'.PHP_EOL;
            }
            $info.='</script>';
        }
        return $info;
    }

    /**
     * @private
     */
    private function getErrorInfo( array $error ,& $minline=0,$str,$tag='',$endtag='')
    {
        $error=$this->getLineNumber( $error );
        foreach( $error as $line=>&$msg )
        {
            $minline===0 && $minline=$line;
            $msg=sprintf('%son line %s [ %s %s ]%s',$tag,$line,$msg,$str,$endtag);
        }
        return $error;
    }

    /**
     * @private
     */
    private function internal($tpl_path ,$name , $compile_path, $warning )
    {
        $info=array(
            'md5'=>md5_file( $tpl_path ),
            'version'=>self::VERSION,
            'tpl'=>$name,
            'compile'=>$compile_path,
        );
        $data='<?php if( !( $__RENDER__ instanceof '.get_class().' ) ) die(\'Access denied!\');'.PHP_EOL;
        $data.='$__COMPILE_INFO__='.var_export($info,true).';'.PHP_EOL;
        $data.='if( $__RENDER__->hasEventListener( \breeze\events\RenderEvent::UPDATED )'.PHP_EOL;
        $data.='&& ($__RENDER__->debug===true || $__RENDER__::VERSION!==$__TPL_INFO__[\'version\']) ){'.PHP_EOL;
        $data.='$__EVENT__= new \breeze\events\RenderEvent( \breeze\events\RenderEvent::UPDATED );'.PHP_EOL;
        $data.='$__EVENT__->result=& $__COMPILE_INFO__;'.PHP_EOL;
        $data.='if( !$__RENDER__->dispatchEvent( $__EVENT__ ) )';
        $data.='return;}'.PHP_EOL;
        if( !empty( $warning ) )
        {
            empty( $warning['var'] ) || $warning['var'] = $this->getLineNumber( $warning['var'] );
            empty( $warning['fun'] ) || $warning['fun'] = $this->getLineNumber( $warning['fun'] );
            $data.='if( $__RENDER__->debug===true && $__RENDER__->hasEventListener( \breeze\events\RenderEvent::DEBUGING ) ){'.PHP_EOL;
            $data.='$__DEBUG_EVENT__= new \breeze\events\RenderEvent( \breeze\events\RenderEvent::DEBUGING );'.PHP_EOL;
            $data.='$__DEBUG_EVENT__->result='.var_export( $warning ,true).';'.PHP_EOL;
            $data.='if( !empty($__DEBUG_EVENT__->result) ) $__RENDER__->dispatchEvent( $__DEBUG_EVENT__ );}';
        }
        $data.='?>'.PHP_EOL;
        return $data;
    }

    /**
     * @private
     */
    private function replace( & $data, & $elements )
    {
        $startIndex=0;
        $contet='';
        foreach( $data  as $length=>$syntax )
        {
            $contet.=$this->substr($this->tpl_content,$startIndex,$length-$startIndex);
            $contet.=$syntax;
            $startIndex=$length + $this->strlen( $elements[$length] );
        }
        $contet.=$this->substr($this->tpl_content,$startIndex, $this->strlen( $this->tpl_content )-$startIndex);
        $contet=preg_replace('~\?\>\s*\n*\t*\r*\<\?php~is','',$contet);
        return $contet;
    }

    /**
     * @private
     */
    private function strlen( $str )
    {
        return $this->mbstring_overload ?  mb_strlen($str,'latin1') : strlen( $str );
    }

    /**
     * @private
     */
    private function substr($str,$start,$length=null)
    {
        return $this->mbstring_overload ?  mb_substr($str,$start,$length,'latin1') : substr( $str,$start,$length===null ? $this->strlen($str)-$start : $length );
    }

    /**
     * @private
     */
    private function compiler(  array & $error=array(), array & $warning=array() , $cacheEnable,$debugEnable )
    {
        $elements        = $this->fetchElements( $this->fetchTags() );
        $leftDelimitLen  = $this->strlen( $this->leftDelimit  );
        $rightDelimitLen = $this->strlen( $this->rightDelimit );
        $closeLen        = $this->strlen( $this->closeTag     );

        //创建一个数组迭代器
        $iterator = new \ArrayObject( $elements );
        $iterator = $iterator->getIterator();
        $data     = array();
        $seek     = -1;

        while( $iterator->valid() && ++$seek < $iterator->count() )
        {
            $iterator->seek( $seek );
            $closeTag=$iterator->current();
            $closeIndex=$iterator->key();
            $closeTagPos=strpos( $closeTag, $this->closeTag, $leftDelimitLen );

            $rigthClosed=$this->strlen($closeTag)-$rightDelimitLen-1===$closeTagPos;
            $leftClosed=$closeTagPos===$leftDelimitLen;

            //如果不是一个关闭标签
            if( $closeTagPos===false || !( $leftClosed || $rigthClosed ) )
                continue;

            //普通单一闭合标签
            if( $rigthClosed )
            {
                $closeTagName=trim( $this->substr($closeTag,$leftDelimitLen,( $endlen=strpos($closeTag,' ') ) ? $endlen-1 : $closeTagPos-$closeLen ) );
                $variable=$closeTagName.'_normal';
                $$variable=isset( $$variable ) ? $$variable : in_array( strtolower($closeTagName),$this->tags );

                if( $$variable===true )
                {
                    $data+=$this->dispatcher( $closeTagName, array($closeIndex=>$closeTag) , $error, $warning,$cacheEnable,$debugEnable );
                    $iterator->offsetUnset( $closeIndex );
                    --$seek;
                    continue;
                }
            }

            $cursor=$seek;

            //向上流对称的起始标签
            while( $iterator->valid() && $cursor > 0 )
            {
                --$cursor;
                $iterator->seek( $cursor );
                $beginTag=$iterator->current();
                $beginPos=strpos($beginTag,$this->closeTag,$leftDelimitLen);

                //如果紧邻的上个元素是一个开始标签
                if( $beginPos===false || ( $beginPos > $leftDelimitLen && $this->strlen($beginTag)-$rightDelimitLen-1 > $beginPos )   )
                {
                    $rigthClosed || $closeTagName=trim( $this->substr($closeTag, $closeTagPos+$closeLen , -$rightDelimitLen ) );

                    //如果是一个成对的标签 <tag></tag>
                    if( $leftClosed && stripos( $beginTag,$closeTagName,$leftDelimitLen)===$leftDelimitLen )
                    {
                        $data+=$this->dispatcher( $closeTagName, array($iterator->key()=>$beginTag,$closeIndex=>$closeTag) ,
                            $error, $warning,$cacheEnable,$debugEnable );
                        $iterator->offsetUnset( $closeIndex );
                        $iterator->offsetUnset( $iterator->key() );
                        $seek-=2;
                        break;
                    }
                    //如果是一个分枝
                    else if( $rigthClosed && !empty( $this->branchTags[ $closeTagName ] )  )
                    {
                        $beginTagName=trim( $this->substr($beginTag,$leftDelimitLen,( $endlen=strpos($beginTag,' ') ) ? $endlen-1 : $rightDelimitLen-1 ) );
                        $variable=$beginTagName.'_'.$closeTagName.'_branched';

                        //判断是否指定根标签下的分枝
                        $$variable=isset( $$variable ) ? $$variable : is_string( $this->branchTags[ $closeTagName ] ) ?
                            strcasecmp($this->branchTags[ $closeTagName ],$beginTagName)===0 :
                            in_array( strtolower($beginTagName), $this->branchTags[ $closeTagName ] );
                        if( $$variable===true )
                        {
                            $data+=$this->dispatcher( $closeTagName,array($closeIndex=>$closeTag), $error, $warning,$cacheEnable,$debugEnable );
                            $iterator->offsetUnset( $closeIndex );
                            --$seek;
                            break;
                        }
                    }
                }
            }
        }

        $error=array('error'=>$error,'unclosed'=>$iterator->getArrayCopy() );
        if( !empty( $error['error'] ) || !empty( $error['unclosed'] ) )
            return null;

        ksort( $data );
        return $this->replace( $data, $elements );
    }

    /**
     * @private
     */
    private function dispatcher( $tagname, array $item , & $error, & $warning, $cacheEnable,$debugEnable )
    {
        $tagname = strtolower( trim( $tagname ) );
        if( $this->hasEventListener( $tagname ) )
        {
            $key=key( $item );
            $event            = new RenderEvent( $tagname );
            $event->attr      = $this->getAttribute( reset( $item )  );
            $event->result    = & $item;
            $event->root      = $tagname;
            $event->content   = & $this->tpl_content;
            $event->cacheEnable=$cacheEnable;
            $event->debugEnable=$debugEnable;
            $this->dispatchEvent( $event );
            empty( $event->warning['var'] ) || $warning['var'][ $key ] = $event->warning['var'];
            empty( $event->warning['fun'] ) || $warning['fun'][ $key ] = $event->warning['fun'];
            empty( $event->error )          || $error[ $key ]          = implode(',' , ( array ) $event->error );
            return $event->result;
        }
        return array();
    }

    /**
     * @private
     */
    private $patternAttr='~(\w+)(\s*=\s*([\"\'])([^\\3]*?)\\3)*~is';

    /**
     * @private
     */
    private function getAttribute( $str )
    {
        $data=array();
        if( is_string($str) )
        {
            $str=str_replace(array('\"','\''),array('@####@','@##@'),$str);
            preg_match_all( $this->patternAttr , $str, $match , PREG_SET_ORDER );
            array_shift( $match );
            foreach( $match as $index=>$item )
            {
                @list(,$name,,,$value)=$item;
                $data[ $name ]=!isset($value) ? null : str_replace(array('@####@','@##@'),array('"',"'"),$value);
            }
        }
        return $data;
    }

    /**
     * @private
     */
    private function getLineNumber( array $data )
    {
        $line=array();
        if( !empty($data) )
        {
            foreach( $data as $len=> &$err )
            {
                $count=substr_count( $this->tpl_content, "\n" ,0,$len );
                $count++;
                $line[$count]=$err;
            }
        }
        return $line;
    }

    /**
     * @private
     */
    private function fetchElements( array $tags )
    {
       $item= implode('|',$tags);
       $pattern=sprintf('~%s%s?(%s)[^%s%s]*%s~is',$this->leftDelimit,$this->closeTag,$item,$this->leftDelimit,$this->rightDelimit,$this->rightDelimit);
       preg_match_all($pattern,$this->tpl_content,$match,PREG_OFFSET_CAPTURE );
       $elements=array();
       while( list($item,$key)=current( $match[0] ) )
       {
            // $index=key( $match[0] );
            // $elements[ $match[1][$index][0] ][$key]=$item;
             $elements[$key]=$item;
             next( $match[0] );
       }
       $match=null;
       return $elements;
    }

    /**
     * @private
     */
    private function compressionHandle( & $data, $compres )
    {
        if( $this->compression===true && !empty($compres) )
        {
            switch( $compres )
            {
                case 'deflate' :
                    $data=gzdeflate($data,7);
                    break;
                case 'gzip' :
                    $data=gzencode($data,7);
                    break;
            }
        }
    }

    /**
     * @private
     */
    private function getCompression()
    {
        if( $this->compressed===null && $this->compression===true && IS_CLI===false && extension_loaded('zlib') )
        {
            $this->compressed='';
            $encode=$this->getAcceptEncode();
            if( is_array( $this->compressionFun ) && !empty($encode) ) foreach( $this->compressionFun as $fun)
            {
               if( stripos( $encode, $fun )!==false )
               {
                   $this->compressed=$fun;
                   break;
               }
            }
        }
        return $this->compressed===null ? '' : $this->compressed ;
    }

    /**
     * @private
     */
    private function getAcceptEncode()
    {
        return isset($_SERVER["HTTP_ACCEPT_ENCODING"]) ? $_SERVER["HTTP_ACCEPT_ENCODING"] : @getenv('HTTP_ACCEPT_ENCODING') ;
    }

    /**
     * @private
     */
    private function isCacheExpire( $filename )
    {
        $expire=(int) $this->cache_expire;
        if( $this->hasEventListener(RenderEvent::IS_CACHE_EXPIRE) )
        {
            $event=new RenderEvent( RenderEvent::IS_CACHE_EXPIRE );
            $event->hash = $filename;
            $event->expire = $expire;
            return $this->dispatchEvent( $event );
        }
        return file_exists($filename) && $expire+filemtime($filename) > time();
    }

    /**
     * @private
     */
    private function getCacheHtml( $filename  )
    {
        if( $this->isCacheExpire( $filename ) )
        {
            if( $this->hasEventListener(RenderEvent::GET_CACHE_DATA) )
            {
                $event=new RenderEvent( RenderEvent::GET_CACHE_DATA );
                $event->expire= $this->cache_expire;
                $event->hash = $filename;
                if( !$this->dispatchEvent( $event ) )
                {
                    return $event->content;
                }
            }
            return file_get_contents( $filename );
        }
        return null;
    }

    /**
     * @private
     */
    private function setCacheHtml( &$content, $filename )
    {
       if( $this->hasEventListener(RenderEvent::SET_CACHE_DATA) )
       {
          $event=new RenderEvent( RenderEvent::SET_CACHE_DATA );
          $event->content = &$content;
          $event->hash = &$filename;
          if( !$this->dispatchEvent( $event ) )
              return;
       }
       file_put_contents($filename,$content);
    }

}


/**
 *
 * Render Tag Library
 *
 * Class Part
 * @package breeze\library
 */
class Part
{
    /**
     * @var array
     */
    static private $expression=array(
        array('gt','lt','eq','noteq','eqt','noteqt'),
        array('>','<','=','!=','===','!=='),
    );

    /**
     * @private
     */
    static private function parseCondition( $condition )
    {
        $condition=str_replace( self::$expression[0],self::$expression[1], $condition );
        $condition=preg_replace(array('/or/i','/and/i'),array('||','&&'), $condition );
        return $condition;
    }

    /**
     * @private
     */
    static private function isVariable(  RenderEvent $event , $str ,$flag=false)
    {
        if( $flag===false )
        {
            preg_match_all('/\$(\w+)(?=[\s\,\)]|$)/is',$str,$math);
            $math=array_unique( $math[1] );
            foreach( $math as $var ) if( !isset( $$var ) )
            {
                self::concat( $event->warning[ 'var' ] , $var );
            }
        }else if( !isset( $$str ) )
        {
            self::concat( $event->warning[ 'var' ] , $str );
        }
    }

    /**
     * @private
     */
    static private function concat( &$data , $value )
    {
        if( !empty( $data ) )
        {
            $data=( array )$data;
            array_push($data,$value);
        }
        else $data=$value;
    }

    /**
     * @private
     */
    static private function isFunction(  RenderEvent $event , $str,$flag=false )
    {
        if( $flag===false )
        {
            preg_match_all('/\w+\s*(?=[\(])/is',$str,$math);
            $math=array_unique( $math[0] );
            foreach( $math as $var ) if( !is_callable( $var ) && stripos('empty,isset,list',$var)===false )
            {
                self::concat( $event->warning[ 'fun' ] , $var );
            }
        }else if( !is_callable( $str ) && stripos('empty,isset,list',$str)===false )
        {
            self::concat( $event->warning[ 'fun' ] , $str );
        }
    }

    /**
     * @private
     */
   static public function initialize( Render $render , array $tags )
   {
       foreach( $tags as $name )
       {
           $render->addEventListener( strtolower( trim($name) ) , array('\breeze\library\Part','dispatcher') );
       }
   }

    /**
     * @param RenderEvent $event
     */
    static public function dispatcher( RenderEvent $event )
    {
       $fun='part_'.$event->type;
       if( method_exists('\breeze\library\Part',$fun ) )
       {
           if( !in_array( $event->type, array('if','elseif','else','for','while','dowhile','case','default') ) )
           {
               if( empty( $event->attr['name'] ) )
               {
                   $event->error[]=Lang::info(4003,'name');
                   $event->preventDefault();
               }
           }
          $event->stopPropagation();
          self::$fun( $event );
       }
    }

    /**
     * loop 标签
     * 此标签必须成对出不能使用单闭合。即：<loop />
     * @param name 必须
     * @param key  可选
     * @param value 可选
     */
    static private function part_loop( RenderEvent $event )
    {
        $index= array_keys( $event->result );
        @list($begin,$close)=$index;
        if( !isset($begin,$close) )
        {
            $event->error[]=Lang::info(4002);
            $event->preventDefault();
            return;
        }

        if( !empty( $event->error ) )
            return;

        self::isVariable( $event,$event->attr['name'],true );

        $event->result[$begin]=sprintf('<?php if( is_array( $%s ) ){ foreach( $%s as $%s => $%s){?>',
            $event->attr['name'],
            $event->attr['name'],
            !empty($event->attr['key']) ? $event->attr['key'] : 'key' ,
            !empty($event->attr['value']) ? $event->attr['value'] : 'value' );
        $event->result[ $close ]='<?php }}else{ trigger_error(\'Variables must be an array type: '.$event->attr['name'].'\',E_USER_NOTICE);}?>';
    }

    /**
     * echo 标签
     * @param name 必须
     * @param call 可选
     */
    static private function part_echo( RenderEvent $event )
    {
        @list($begin,$close)=array_keys( $event->result );

        if( !empty($event->error)  )
            return;
        self::isVariable( $event,$event->attr['name'],true );
        $event->result[ $begin ]=sprintf('<?php echo $%s;?>',$event->attr['name'] );
        if( isset( $close ) ) $event->result[ $close ]='';
    }

    /**
     * var 标签
     * @param name 必须
     * @param value 必须
     */
    static private function part_var( RenderEvent $event )
    {

        if( !isset( $event->attr['value'] ) )
        {
            $event->error[]=Lang::info(4003,'value');
            $event->preventDefault();
        }
        if( !empty($event->error) )
            return;

        @list($begin,$close)=array_keys( $event->result );

        if( preg_match('/(array|new\s*\w+)\s*\(.*?\)/i', $event->attr['value']) )
            $event->result[ $begin ]=sprintf('<?php $%s=%s;?>', $event->attr['name'], $event->attr['value']);
        else
           $event->result[ $begin ]=sprintf('<?php $%s=\'%s\';?>', $event->attr['name'], $event->attr['value']);

        if( isset( $close ) ) $event->result[ $close ]='';
    }

    /**
     * if 标签
     * @param condition 必须
     */
    static private function part_if( RenderEvent $event, $return=false,$paired=true )
    {
        @list($begin,$close)=array_keys( $event->result );
        if( !is_numeric($close) && $paired===true )
            $event->error[]=Lang::info(4002);

        if( empty($event->attr['condition']) )
            $event->error[]=Lang::info(4003,'condition');
        else
        {
            $condition = preg_replace('/(\w+)(?=[\,\)\s]|$)/is','$\\1', self::parseCondition( $event->attr['condition'] ) );

            //检查变量是否定义
            self::isVariable($event,$condition);

            //检查方法是否定义
            self::isFunction($event,$condition);
        }

        if( !empty( $event->error )  )
            return false;

        if( $return===true )
          return $condition;

        $event->result[ $begin ]=sprintf('<?php if( %s ){?>',$condition);
        $event->result[ $close ]='<?php }?>';
    }

    /**
     * elseif 标签
     * @param condition 必须
     */
    static private function part_elseif( RenderEvent $event)
    {
        @list($begin,$close)=array_keys( $event->result );
        $condition=self::part_if( $event, true ,false);
        if( $condition===false )
            return;
        $event->result[ $begin ]=sprintf('<?php }elseif( %s ){?>',$condition);
        if( isset( $close ) ) $event->result[ $close ]='';
    }

    /**
     * else 标签
     */
    static private function part_else( RenderEvent $event)
    {
        @list($begin,$close)=array_keys( $event->result );
        $event->result[ $begin ]='<?php }else{ ?>';
        if( isset( $close ) ) $event->result[ $close ]='';
    }

    /**
     * call 标签
     * @param string name 函数名
     * @param string param 参数 可选
     */
    static private function part_call( RenderEvent $event)
    {
        @list($begin,$close)=array_keys( $event->result );
        $param='';
        $echo='';

        if( !empty( $event->attr['param'] ) )
        {
           $param=preg_replace('/(\w+)(?=[\,\s]|$)/is','$\\1',$event->attr['param'] );
           self::isVariable($event,$param);
           self::isFunction($event,$param);
        }

        self::isFunction($event,$event->attr['name'],true);

        if( !empty( $event->error ) )
            return;

        if( isset( $event->attr['echo'] ) )
            $echo='echo ';

        $param=preg_replace('/&\s*\$/','$',$param);
        $event->result[ $begin ]=sprintf('<?php if(function_exists(\'%s\')) %s%s(%s); else trigger_error(\'Undefined function: %s\',E_USER_ERROR); ?>',
                                         $event->attr['name'],$echo,$event->attr['name'],$param,$event->attr['name']);
        if( isset( $event->result[ $close ] ) ) $event->result[ $close ]='';
    }

    /**
     * for 标签
     * @param start 必须  可以是一个变量名
     * @param length 必须  可以是一个变量名
     * @param step  可选
     */
    static private function part_for( RenderEvent $event)
    {
       @list($begin,$close)=array_keys( $event->result );

       if( !isset($begin,$close) )
          $event->error[]=Lang::info( 4002 );

       if( !isset( $event->attr['start'],$event->attr['length'] ) )
           $event->error[]=Lang::info( 4002 ,'start,length');

       $start=(int)$event->attr['start'];
       $length=(int)$event->attr['length'];
       $step=isset( $event->attr['step'] ) ? max( (int)$event->attr['step'],1 ) : 1 ;
       if( !is_numeric( $start ) && !( isset( $$start ) && !is_numeric( $$start ) )  )
           $event->warning[]=Lang::info( 4004 );

       if( !is_numeric( $length ) && !( isset( $$length ) && !is_numeric( $$length ) )  )
           $event->warning[]=Lang::info( 4004 );

       if( !empty( $event->error ) )
            return;

        $length = intval( !is_numeric( $length ) ? @$$length : $length );
        $start  = intval( !is_numeric( $start )  ? @$$start  : $start  );

       $event->result[ $begin ]=sprintf('<?php for( $start = %s; $start < %s; $start+=%s ){ ?>',$start, $length * $step ,$step);
       $event->result[ $close ]='<?php }?>';
    }

    /**
     * while 标签
     * @param condition 必须
     */
    static private function part_while( RenderEvent $event)
    {
        @list($begin,$close)=array_keys( $event->result );
        if( $condition=self::part_if( $event, true ) )
        {
           $event->result[ $begin ]=sprintf('<?php while( %s ){?>',$condition);
           $event->result[ $close ]='<?php }?>';
        }
    }

    /**
     * dowhile 标签
     * @param condition 必须
     */
    static private function part_dowhile( RenderEvent $event)
    {
        @list($begin,$close)=array_keys( $event->result );
        if( $condition=self::part_if( $event, true ) )
        {
            $event->result[ $begin ]='<?php do{?>';
            $event->result[ $close  ]=sprintf('<?php }while( %s );?>',$condition);
        }
    }

    /**
     * switch 标签
     * @param name 必须
     */
    static private function part_switch( RenderEvent $event)
    {
        @list($begin,$close)=array_keys( $event->result );
        if( !isset($begin,$close) )
           $event->error[]=Lang::info(4002);

        self::isVariable($event,$event->attr['name'],true);

        if( !empty($event->error) )
            return ;

        $event->result[ $begin ]=sprintf('<?php switch( $%s ){?>', $event->attr['name'] );
        $event->result[ $close ]='<?php }?>';
    }

    /**
     * case 标签
     * @param value
     */
    static private function part_case( RenderEvent $event )
    {
        @list($begin,$close)=array_keys( $event->result );
        $event->result[ $begin ]=sprintf('<?php case \'%s\' :  ?>', @$event->attr['value'] );
        if( isset($close) ) $event->result[ $close ] = '<?php break; ?>';
    }

    /**
     * default 标签
     */
    static private function part_default( RenderEvent $event )
    {
        @list($begin,$close)=array_keys( $event->result );
        $event->result[ $begin ]='<?php default :  ?>';
        if( isset($close) ) $event->result[ $close ] = '';
    }

    /**
     * include 标签
     */
    static private function part_include( RenderEvent $event )
    {
        @list($begin,$close)=array_keys( $event->result );
        self::isVariable($event,$event->attr['name'],true);
        if( !empty($event->error) )
            return;
        $event->result[ $begin ]=sprintf('<?php echo $__RENDER__->fetch(\'%s\',%s,false); ?>',
                                          $event->attr['name'], $event->cacheEnable ? 'true' : 'false');
        if( isset($close) ) $event->result[ $close ] = '';
    }

}