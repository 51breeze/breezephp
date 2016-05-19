<?php

namespace application\controller;

use application\model\User;
use breeze\core\Controller;
use breeze\core\Error;
use breeze\core\Single;
use breeze\core\View;
use breeze\database\Strainer;
use breeze\database\Parameter;
use breeze\database\Mysql;
use breeze\database\Whereis;
use breeze\utils\Utils;

class Index extends Controller
{

	public function index()
	{

     /* require'./application/libs/smarty/Smarty.php';
      $smarty= new \Smarty();

      $smarty->setTemplateDir(__VIEW__);
      $smarty->setCompileDir( Utils::directory(APP_PATH.DIRECTORY_SEPARATOR.'compile'.DIRECTORY_SEPARATOR.'view') );
      $smarty->allow_php_templates=true;
      $smarty->setCacheDir(APP_PATH.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'view');
     // $smarty->force_cache=false;
      $smarty->caching=true;
      $smarty->assign('title','99999');
      $smarty->display('index.html');

       // $a=preg_replace_callback('~<file name/>~',function($m){
         //  return gzencode($m[0],7);
       // },$a);*/


    // $this->assign('test','5655665555');
    // $this->dispaly('breezejs');


      $user = new User();

        $user->getUser();


       // var_dump(  );

        exit;


     //  $this->system()->



	    $param=new Parameter();

	    $cofing=$this->app->config('database');

	    $param->host=$cofing['host'];
	    $param->user=$cofing['user'];
	    $param->password=$cofing['password'];
	    $param->database=$cofing['database'];
	    $param->port=3306;



	    $mysql=new Mysql($param);
	    $mysql->connet();
	    
	    
	   $complex= new Whereis( $mysql );
	   $complex->item('username','66666','equal');
	   $complex->item('username','pppp','equal','or');
	   $complex->item('username','ssss','equal','and');
	   
	   $complex->begin()->item('username','hjhj','not_equal','or')->item('username','101','not_equal','or')
	           ->begin()->item('username','hjhj','not_equal')->begin()
	                    ->item('username','sdf','equal','and')
	                    ->item('username','1010','equal','or');

	   
	  // $mysql->set(array(array('uuuu'=>'yyed','username'=>'999','id'=>1),array('uuuu'=>'ssdfd','username'=>'666','id'=>2)),null,'username','id');


	   $data=array();
	   $data['id']=12;
	   $data['username']='9999999';
	   $data['password']='99[pppppp';
	   $data['email']='yejun@qq.breeze';
	   
	   $list=array();
	   $list[]=$data;

       // unset( $data['id'] );
	   $data['id']=13;
       $data['username']='131313';
	   $data['password']='ppp test pass';
       $data['email']='===sdfsdfdsfds===';
	   $list[]=$data;

       $da= $mysql


             //=========== set() ===================

          /*  ->bind('id','14',$list)
            ->bind('username','yejun',$data)

            ->table('user')
            ->set()*/

           //============  get() ================

          /*->where('user.id',12)
            ->where('user.id',13,Strainer::EQUAL,'OR')
            ->table('user')
            //->columns(array('user.username','test.username as tt'))
            ->columns('user.username,test.username as tt')
            ->join('test','test.id=user.id')
            //->union('select username,\'ppp\' from user where id in(14,16,18)')
               //->union()->table('user')->columns('username,"pppppp"')->where('id',array(12,15,16,18),Strainer::IN)
            //->endUnion()
            ->group('user.username','asc',true)
        ->having('sum(user.id) < 1')
            ->having('sum(user.id) = 12','or')
           // ->order('user.id','asc')
            ->get()
            ->fetch();*/

        //============  add() ================

       /*->ignore()
        ->table('user')
        ->bind('id',null,array('id'=>'5666','username'=>'dsfdsfsdf'))
        ->add($list)*/


        //====================remove()==================
      /* ->table('user as u,test t')
        ->where('t.id',13)
        ->where('t.id=u.id')
        ->remove()*/

        //====================copy()==================

            /*->table('user u')
            ->columns('username,password,"iiiii" as email')
            ->limit(22)
            ->copy('test')*/
           ;


            //->ignore()

              // ->bind(null)

            //->bind('email','9990',array('username'=>'123'))
           // ->bind(array('username'=>'123====','email'=>'@using(`user`.`email`)'))

            //->bind('password','uuuu',array('username'=>$fun,'email'=>'pppp'))
           // ->bind('password','55550',array('username'=>$fun))


             // ->bind('email','888',array('username'=>'888','password'=>'@ using(ppp.aaa)'))
             // ->bind('username','444',array('password'=>'555','email'=>'444--333','ppp'=>'fdfds'))

            //->columns(array('username','password','email'))
            //->copy('user','user',array('username','password','email'));

           //->using('user,test')
           //->where('username','@using(test.user)')
          // ->where('id','22' )
           //->remove('user,ccc,test,bbbb');

           //->bind('u.id',$list)
          // ->join('test','test.id=user.id')
        /*   ->table('user')
           ->columns('user.id as id1,username')
           ->union(null)
           ->columns('id,username')->table('user')->where('id',13)
           ->endUnion()
           ->order('user.id','desc')
           ->group('username')
           ->having('count(username)>=2')
           ->get()
           ->fetch()*/
          //  ->bind('username',null,array('username'=>'yejun','password'=>'dfdsfds'))
            //->table('test')
          //  ->add( array('username'=>'yejun','password'=>'dfdsfds','tytyty') )

        ;



        //->bind('columns','value','unique')
        //->bind('column','value','newValue')
	    // $da=$mysql->append($list,'','user','id',$on);

	   /*

	   $mysql->where()->item('username','ooooo123');
	  
	  
	   $da= $mysql->union()->where('username','66666')->table('user')->columns('*')->endUnion();
	   
	   
	   
            	  $mysql->union()->where('username','')->columns('*')->table('user')->where(
            	           array(
            	                  'usernam="dfsdf"',
            	                   'usernam="dsfdffd"',
            	                  'username'=>'',
            	           ))
            	           
            	  ->where()->item('username','yyyyy','equal','or');

            	  $mysql->get('user');
            	  
            	  */


	   //->delete('user');





       echo $mysql->lastQuery(),"\r\n";
	    var_dump(  $da,$mysql->getErrorInfo() );

		echo "Index->index controller";	

	}
	
	public function user()
	{
        $this->app()->cookie('yejun','1234-3600',3600);
        $this->app()->cookie('yejun11','rty78-20',20);
		echo "Index->user controller";
	}

    public function test()
    {
        $c=  $this->app()->cookie();
        echo "Index->test controller";
    }

}

?>