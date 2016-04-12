<?php

namespace breeze\library;

class Page {

	/**
	 * 每页显示多少行数据
	 * @var Number
	 */
	public $pageRows=20;

	/**
	 * 共有多少条数据
	 * @var Number
	 */
	public $totalRows;
	
	/**
	 * 要显示链接的按扭数
	 * @var Number
	 */
	public $linkNums=6;
	
	/**
	 * 当前显示的页数
	 * @var Number
	 */
	public $currentPage=1;
	
	/**
	 * 接收面页参数名
	 * @var String
	 */
	public $pageName='page';
	
	/**
	 * 跳转的url地址,如果此url中带有 ｛page｝ 标签则会把此标签替换中对应的数字。
	 * @var String
	 */
	 public $url='';
	
	//起始行数
	private $firstRows;
	
	//当前页面的链接数
	private $current_link_nums;
	
	//总页数
	private $totalPage;
	
	// 分页显示定制
	private $config=array('header'=>'条记录','prev'=>'上一页','next'=>'下一页','first'=>'第一页','last'=>'最后一页',
			'theme'=>'%first%  %prePage%  %linkPage%  %nextPage%  %last%');

	public function __construct($totalRows ,$pageRows=20 ,$linkNums=6,$currentPage=0)
	{
		$this->totalRows=$totalRows;
		$this->linkNums=$linkNums;
		$this->pageRows=$pageRows;
		
		if( $currentPage > 0 )
		   $this->currentPage=$currentPage;
		
	}
	
	/**
	 * 显示分页
	 * @param array $config
	 */
	public function show( $config=array() )
	{
		
		if( $this->totalRows < 1 )
			return '';
		
		$this->config=array_merge($this->config,$config);
		
		$this->totalPage=ceil( $this->totalRows / $this->pageRows );
		
		$this->currentPage=min( max($this->currentPage,1), $this->totalPage );
		
		$this->firstRows=$this->pageRows*($this->currentPage-1);
		
		$offsetLinkNum=ceil( $this->currentPage / $this->linkNums );
		
		$linkPage='';
		
		for( $i=1 ; ( $i<= $this->linkNums && $i<=$this->totalPage ) ; $i++ )
		{
			$page=($offsetLinkNum-1)*$this->linkNums+$i;
			
			if( $page!=$this->currentPage )
			{
			   $linkPage.='<a href="'.$i.'" >'.$i.'</a>';
			   
			}else
			{
				$linkPage.='<a>'.$i.'</a>';
			}
			
		}
		
		$prePage=( $this->currentPage > 1 ) ? 'href="'.($this->currentPage-1).'"' : '';
		$prePage='<a '.$prePage.'>'.$this->config['prev'].'</a>';
		
		$nextPage=( $this->currentPage < $this->totalPage ) ? 'href="'.($this->currentPage+1).'"' : '';
		$nextPage='<a '.$nextPage.'>'.$this->config['next'].'</a>';
		
		$firstPage='<a href="1">'.$this->config['first'].'</a>';
		$lastPage='<a href="'.$this->totalPage.'">'.$this->config['last'].'</a>';
		
		$page=str_replace( 
				 array('%first%','%prePage%','%linkPage%','%nextPage%','%last%'), 
				 array($firstPage,$prePage,$linkPage,$nextPage,$lastPage) ,
				 $this->config['theme'] 
		   );
		
		return $page;

	}
	
   /**
   * 获取偏移的行数
   * @param unknown $name
   */
	public function offset()
	{
	   return $this->firstRows;  	
	}
	
	/**
	 * 获取每页显示的行数
	 * @param unknown $name
	 */
	public function rows()
	{
		return $this->pageRows;
	}

}

?>