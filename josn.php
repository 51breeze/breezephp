<?php




//header('Content-Type:application/json');

$callback=$_GET['callback'];

$js='';
$js.=$callback.'({data:"别过"});';

$js.="";

echo $js;

/*$data=array();

$data['data']=array('name'=>'中国人民解放军','age'=>456);
$data['ret']=1;


echo json_encode($data);*/