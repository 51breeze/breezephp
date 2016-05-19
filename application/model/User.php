<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-4-27
 * Time: 下午9:59
 */

namespace application\model;

use breeze\core\Model;
use breeze\database\structure\Column;
use breeze\database\structure\ColumnType;

class User extends Model
{
    public function getUser()
    {
        //$s =  $this->db->where('id',12)->get();

       // $s =  $this->db->where('id','12')->set( array('username'=>'yejun566669999') );
      //  $s =  $this->db->save(array('username'=>'3333333','id'=>3), 'id');
        $s =  $this->db->on('id',array('username'=>'@using(id)','id'=>3) )->add(array('username'=>'3333333','id'=>3));


       // $s =  $this->db->query("SHOW TABLE STATUS FROM test WHERE NAME='test'")->fetch(true);

       // $this->structure()->getColumnByName('username')->type()->length(255);



        $this->structure()->column( new Column('goods', new ColumnType(ColumnType::VARCHAR, 250),true,'','goods' ) );


        echo  $this->structure()->toString();

      // var_dump(   );
    }
} 