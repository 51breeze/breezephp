<?php

namespace breeze\core;

class Error extends \Exception
{
	
	public function __construct($message = "", $code = 0,$file=null, $line=null,Exception $previous = null)
	{
        $file===null || $this->file=$file;
        $line===null || $this->line=$line;
		parent::__construct($message, $code, $previous);
	}
	
	public function __toString()
	{
		return "[{$this->code}]: {$this->message} in {$this->file} {$this->line} line \n";
	}

}

?>