<?php


$config=array();

$config['phpfile']='this is php file';

$config['URL_PARAM']='filter(0)=filter(0)delimiter&';

$config['URL_SUFFIX']='.php?';

$config['URL_SCRIPTNAME']='filter(0)delimiter.';


$config['database']=array(
        'host'=>'localhost',
        'user'=>'root',
        'password'=>'',
        'database'=>'test',
        'type'=>'mysql',
);

/*$config['render']=function()
{
    $smarty=new Smarty();
    $smarty->setTemplateDir(__VIEW__);
    $smarty->setCompileDir(APP_PATH.DIRECTORY_SEPARATOR.'compile');
    $smarty->setCacheDir(APP_PATH.DIRECTORY_SEPARATOR.'cache');
    $smarty->caching=true;
    return $smarty;
}*/



?>