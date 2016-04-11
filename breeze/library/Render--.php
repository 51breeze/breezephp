<?php

namespace breeze\library;
use breeze\core\Error;
use breeze\core\EventDispatcher;
use breeze\core\Lang;
use breeze\core\Singleton;
use breeze\events\RenderEvent;
use breeze\interfaces\ISingleton;

/**
 * 视图渲染器
 * Class Render
 * @package breeze\library
 */
class Render__ extends EventDispatcher implements ISingleton
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
    public $cache_expire=3600;

    /**
     * @public
     * 是否将模板写入缓存
     */
    public $cacheEnable=true;

    /**
     * @public
     * 是否需要将内容压缩存放
     */
    public $compression=true;

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
     * @param $tagname
     * @param $fun
     * @param bool $paired
     * @param null $group
     */
    public function extension($tagname,$fun,$paired=false,$group=null)
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

        if( !is_callable($fun) )
            throw new Error( Lang::info(4007) );
        $this->addEventListener($tagname,$fun);
    }

    /**
     * Constructs.
     */
    public function __construct()
    {
        Singleton::register(get_called_class(),$this);
        Part::initialize( $this , $this->fetchTags() );
        $this->mbstring_overload = ini_get('mbstring.func_overload') & 2;
        $this->addEventListener( RenderEvent::UPDATED , array( $this,'update' ) );
    }

    /**
     * 获取分枝标签
     * @return array
     */
    public function & fetchBranchTags()
    {
       return $this->branchTags;
    }

    /**
     * @return Render
     * @see \breeze\interfaces\ISingleton::getInstance()
     */
    public static function getInstance(array $param=array())
    {
        return Singleton::getInstance(get_called_class(),$param);
    }

    /**
     * 指定一组变量
     * @param $name
     * @param $value
     */
    public function assign( $name, $value )
    {
        if( is_string($name) )
            $this->variable[$name]=$value;
        else
            trigger_error(Lang::info(1101),E_USER_WARNING );
    }

    /**
     * 输出指定的模板
     * @param $name
     */
    public function dispaly( $name='', $cache=true )
    {
        echo $this->render( $name ,$cache );
    }

    /**
     * 取出指定的模板内容
     * @param $name
     */
    public function fetch($name='',$cache=true)
    {
        return $this->render(  $name  ,$cache);
    }

    /**
     * @private
     */
    public function getTemplatePath($name)
    {
        $tpl_path=ltrim($this->template_path,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name.$this->template_suffix;
        if( !file_exists( $tpl_path ) )
            throw new Error( Lang::info(1102) );
        return $tpl_path;
    }

    /**
     * @private
     */
    public function getCompilePath( $name )
    {
        return ltrim($this->compile_path,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.md5($name).'.php';
    }

    /**
     * @private
     */
    public function getCachePath( $name )
    {
        return ltrim($this->cache_path,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name.$this->cache_suffix;
    }

    /**
     * @private
     */
    private function render( $name , $cacheEnable=false )
    {
        $name=empty( $name ) ? METHOD : $name;

        if( !is_string($name) || empty($name) )
        {
            throw new Error('file name cannot is empty');
        }

        //获取压缩方法名
        $compres=$this->getCompression();

        //缓存文件路径
        $cache_path=$this->getCachePath( sprintf('%s%s',$name,!empty($compres) ? '-'.$compres : '' ) );

        //是否获取缓存内容
        $cacheEnable=$this->cacheEnable & $cacheEnable;
        $html=$cacheEnable ? $this->getCacheData( $cache_path ) : null;

        if( $html===null )
        {
            //编译后的文件路径
            $compile_path=$this->getCompilePath( $name );

            //如果没有编译则编译模板文件
            file_exists( $compile_path ) || $this->compileTemplate( $compile_path , $name );

            //当前内部变量
            $__RENDER__           = $this;
            $__EVENT__            = null;
            empty($this->variable) || extract( $this->variable ,EXTR_IF_EXISTS OR EXTR_REFS );

            //开启缓冲区
            ob_start();
            include( $compile_path );
            if( $__EVENT__ instanceof RenderEvent && $__EVENT__->prevented )
            {
                ob_clean();
                include( $compile_path );
            }
            $html=ob_get_clean();

            //压缩数据
            //$this->compressionHandle($html,$compres);

            //设置缓存数据
            $cacheEnable && $this->setCacheData( $html , $cache_path );

        }
        return $html;
    }

    /**
     * @private
     */
    protected  function update( RenderEvent $event )
    {
        if( $event->result['md5'] !== md5_file( $this->getTemplatePath( $event->result['tpl'] ) )
            || self::VERSION!==$event->result['version'] )
        {
            $event->preventDefault();
            $this->compileTemplate( $event->result['compile'], $event->result['tpl'] );
        }
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
    private function compileTemplate( $compile_path, $tpl_name,array $elements=null )
    {
        //模板文件路径
        $tpl_path=$this->getTemplatePath( $tpl_name );
        $this->tpl_content = file_get_contents( $tpl_path );

        //获取所有的模板标签
        $elements===null && $elements=$this->fetchElements( $this->fetchTags() );

        $error=array();
        $warning=array();
        $data=$this->compiling( $elements, $error, $warning );

        if( !empty($error) )
        {
            $error=$this->getLineNumber( $error );
            foreach($error as $line=>&$msg)
            {
                $msg=sprintf('on line [%s] => %s',$line,$msg);
            }
            throw new Error( implode(PHP_EOL,$error) );
        }

        $map=array();
        foreach( $elements as $item ) $map+=$item;
        $data=$this->replace( $data, $map );

        if( !empty($warning) )
        {
            $warning=$this->getLineNumber( $warning );
            foreach($warning as $line=>&$item )
            {
                $item=sprintf('<li>in line[%s] %s</li>',$line,$item);
            }

            $warning=implode(PHP_EOL,$warning);
            $temp='<?php if( $__RENDER__->debug===true ){'.PHP_EOL;
            $temp.='echo "<script>",PHP_EOL;';
            $temp.='echo "var template_warning=\''.$warning.'\'";';
            $temp.='echo "</script>",PHP_EOL;';
            $temp.=PHP_EOL.'}?>';
            $data=$temp.$data;
        }

        $data=$this->internal( $tpl_path ,$tpl_name , $compile_path).$data;
        file_put_contents( $compile_path, $data );
        $this->data=null;
    }

    /**
     * @private
     */
    private function internal( $tpl_path ,$name , $compile_path )
    {
        $info=array(
            'md5'=>md5_file( $tpl_path ),
            'version'=>self::VERSION,
            'tpl'=>$name,
            'compile'=>$compile_path,
        );
        $data='<?php if( !( $__RENDER__ instanceof '.get_class().' ) ) die(\'Access denied!\');'.PHP_EOL;
        $data.='$__TPL_INFO__='.var_export($info,true).';'.PHP_EOL;
        $data.='if( $__RENDER__->debug===true && $__EVENT__===null || $__RENDER__::VERSION!==$__TPL_INFO__[\'version\'] ){';
        $data.='$__EVENT__= new \breeze\events\RenderEvent( \breeze\events\RenderEvent::UPDATED );';
        $data.='$__EVENT__->result=& $__TPL_INFO__;'.PHP_EOL;
        $data.='if( $__RENDER__->hasEventListener( \breeze\events\RenderEvent::UPDATED ) && !$__RENDER__->dispatchEvent( $__EVENT__ ) )';
        $data.='return null;}?>'.PHP_EOL;
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
    private function substr($str,$start,$length)
    {
        return $this->mbstring_overload ?  mb_substr($str,$start,$length,'latin1') : substr( $str,$start,$length );
    }

    /**
     * @private
     */
    private function compiling( $elements , array & $error=array(), array & $warning=array() )
    {

       // print_r( $elements );
      //  exit;


        //合并分枝标签
        if( !empty($this->branchTags) )
        {
            foreach( $this->branchTags as $branch=>$root )
            {
                if( isset( $elements[ $root ] ,$elements[ $branch ] ) )
                {
                    $branchData=$elements[ $root ]+$elements[ $branch ];
                    ksort( $branchData );
                    $elements[ $root ]=$branchData;
                    unset( $elements[ $branch ] );
                }
            }
        }

        $resultData=array();
        $leftDelimitLen=$this->strlen( $this->leftDelimit );
        $rightDelimitLen=$this->strlen( $this->rightDelimit );




        //检查标签元素是否正确
        foreach( $elements as $tag=>& $item )
        {
            $item=$this->checkTagElement( $item, $tag , $error , $leftDelimitLen,$rightDelimitLen);

            //将标签转换成对应的语法
            foreach( $item as & $val )
            {
                $result=$this->dispatcher($tag, $val );
                if( $result instanceof RenderEvent )
                {
                     $key=key($val);
                     !empty( $result->warning ) && $warning[ $key ]=implode(',' , ( array ) $result->warning );
                     empty( $result->error ) ? $resultData+=$result->result : $error[ $key ]=implode(',' , ( array ) $result->error );
                }
            }
        }
        ksort( $resultData );
        return $resultData;
    }

    /**
     * @private
     */
    private function dispatcher($tag, array & $item )
    {
        $attr    = $this->getAttribute( is_array( $item ) ? current( array_slice($item,0,1) ) : $item );
        $tagname = array_splice($attr,0,1);
        $tagname = key( $tagname );
        if( !empty($tagname) )
        {
            $tagname=strtolower( trim($tagname) );
            if( $this->hasEventListener( $tagname ) )
            {
                $event=new RenderEvent( $tagname );
                $event->attr   = $attr;
                $event->result = $item;
                $event->root   = $tag;
                $event->content   = & $this->tpl_content;
                $this->dispatchEvent( $event );
                return $event;
            }
        }
        return false;
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
    private function checkTagElement( array $tagElement, $root, &$error=array(),$leftDelimitLen ,$rightDelimitLen )
    {

        //创建一个数组迭代器
        $iterator=new \ArrayObject( $tagElement );
        $iterator = $iterator->getIterator();
        $data=array();

        $branchs=!empty($this->branchTags) ? array_intersect( $this->branchTags ,(array) $root ) : null ;
        $branchs=!empty( $branchs ) ? sprintf('/^%s%s/i', $this->leftDelimit, implode('|',array_keys( $branchs ) ) ) : '';
        $paired=in_array( $root, $this->pairTags );
        $seek = $paired===true ? 0 : -1 ;

        $first=false;
        if( $paired && $iterator->valid() )
        {
            $first = $iterator->current();
            $first = ( stripos( $first,$root )===$leftDelimitLen && stripos( $first,$this->closeTag )===false ) ? true : false;
        }

        //直接定位到下一个标签
        while( $iterator->valid() && ++$seek < $iterator->count() )
        {
            $iterator->seek( $seek );
            $closeTag=$iterator->current();
            $closeIndex=$iterator->key();
            $closeTagPos=strpos( $closeTag, $this->closeTag );

            $rigthClosed=$this->strlen($closeTag)-$rightDelimitLen-1===$closeTagPos;
            $leftClosed=$closeTagPos===$leftDelimitLen;

            //如果不是一个关闭标签
            if( $closeTagPos===false || !( $leftClosed || $rigthClosed ) )
               continue;

            //普通单一闭合标签
            if( $rigthClosed                                               //单闭合标签
                && ( $paired===false                                       //可以不成对出现
                || ( $first===true && preg_match($branchs,$closeTag) ) ) ) //允许出现的分枝闭合标签 <if><elseif condition='name' /> <switch><case value='str' /> <default />
            {
                $data[]=array($closeIndex=>$closeTag);
                $iterator->offsetUnset( $closeIndex );
                --$seek;
                continue;
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
                if( $beginPos===false || ($beginPos > $leftDelimitLen && $this->strlen($beginTag)-$rightDelimitLen-1 > $beginPos )   )
                {
                    $closeTagName=trim( $closeTagPos===$leftDelimitLen ? $this->substr($closeTag,$leftDelimitLen+1,-1) : $this->substr($closeTag,$leftDelimitLen,-($rightDelimitLen+1) ) ) ;

                    //如果是一个成对的标签 <tag></tag>
                    if( $closeTagPos===$leftDelimitLen && stripos( $beginTag,$closeTagName,$leftDelimitLen)===$leftDelimitLen )
                    {
                        $data[]=array($iterator->key()=>$beginTag,$closeIndex=>$closeTag);
                        $iterator->offsetUnset( $closeIndex );
                        $iterator->offsetUnset( $iterator->key() );
                        $seek-=2;
                        break;
                    }
                }
            }
        }
        $error+=$iterator->getArrayCopy();
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
             $index=key( $match[0] );
             $elements[ $match[1][$index][0] ][$key]=$item;
             //$tag[$key]=$item;
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
        if( $this->compressed===null && $this->compression===true && CLI===false && extension_loaded('zlib') )
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
    private function getCacheData( $filename  )
    {
        if( $this->isCacheExpire( $filename ) )
        {
            if( $this->hasEventListener(RenderEvent::GET_CACHE_DATA) )
            {
                $event=new RenderEvent( RenderEvent::GET_CACHE_DATA );
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
    private function setCacheData( &$content, $filename )
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

        $event->result[$begin]=sprintf('<?php foreach( $%s as $%s => $%s){?>',
            @$event->attr['name'],
            !empty($event->attr['key']) ? $event->attr['key'] : 'key' ,
            !empty($event->attr['value']) ? $event->attr['value'] : 'value' );
        $event->result[ $close ]='<?php }?>';
    }

    /**
     * echo 标签
     * @param name 必须
     * @param call 可选
     */
    static private function part_echo( RenderEvent $event )
    {
        @list($begin,$close)=array_keys( $event->result );

        if( empty($event->error)  )
        {
            //$event->warning[]=Lang::info(4005, $event->attr['name'] );
        }
        $event->result[ $begin ]=sprintf('<?php echo $%s;?>',@$event->attr['name'] );
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

        if( !empty( $event->error )  )
        {
            $event->preventDefault();
            return false;
        }

        $condition = self::parseCondition( $event->attr['condition'] );
        $condition = preg_replace('/(\w+)(?=[\,\)\s]|$)/is','$\\1',$condition);

        //检查变量是否定义
        preg_match_all('/\$(\w+)(?=[\s\,\)]|$)/is',$condition,$math);
        $math=array_unique( $math[1] );

        $undefinedv=array();
        foreach( $math as $var ) if( !isset( $$var ) )
        {
            $undefinedv[]=$var;
        }

        if( !empty($undefinedv) )
            $event->warning[]=Lang::info(4005,implode(',',$undefinedv) );

        //检查方法是否定义
        preg_match_all('/\w+\s*(?=[\(])/is',$condition,$math);
        $math=array_unique( $math[0] );

        $undefinedf=array();
        foreach( $math as $var ) if( !is_callable( $var ) && stripos('empty,isset,list',$var)===false )
        {
            $undefinedf[]=$var;
        }

        if( !empty($undefinedf) )
        {
            $event->error[]=Lang::info(4001,implode(',',$undefinedf) );
            $event->preventDefault();
            return false;
        }

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

        if( !is_callable( @$event->attr['name'] ) )
        {
            $event->error[] = Lang::info(4001,@$event->attr['name'] );
            $event->preventDefault();
            return ;
        }

        if( !empty( $event->attr['param'] ) )
           $param=preg_replace('/(\w+)(?=[\,\s]|$)/is','$\\1',$event->attr['param'] );
        if( isset( $event->attr['echo'] ) )
            $echo='echo ';

        $param=preg_replace('/&\s*\$/','$',$param);
        $event->result[ $begin ]=sprintf('<?php %s%s(%s); ?>',$echo,@$event->attr['name'],$param);
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

       if( !empty( $event->error ) )
       {
          $event->preventDefault();
          return;
       }

       $start=(int)$event->attr['start'];
       $length=(int)$event->attr['length'];
       $step=isset( $event->attr['step'] ) ? max( (int)$event->attr['step'],1 ) : 1 ;
       if( !is_numeric( $start ) && !( isset( $$start ) && !is_numeric( $$start ) )  )
           $event->warning[]=Lang::info( 4004 );

       if( !is_numeric( $length ) && !( isset( $$length ) && !is_numeric( $$length ) )  )
           $event->warning[]=Lang::info( 4004 );

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
        {
           $event->preventDefault();
           $event->error[]=Lang::info(4002);
           return;
        }
        $event->result[ $begin ]=sprintf('<?php switch( $%s ){?>', @$event->attr['name'] );
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
        $event->result[ $begin ]='';

        if( isset($close) ) $event->result[ $close ] = '';
    }

}