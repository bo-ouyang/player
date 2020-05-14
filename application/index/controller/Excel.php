<?php


namespace app\index\controller;


use app\common\model\RechargeOrder;
use think\Controller;
use think\Db;
use think\facade\Env;

class Excel extends Controller {
			public function excel(){
				$data['data'] = RechargeOrder::
					where(['is_true'=>1,'is_validate'=>2])
					->whereTime('create_time', 'yesterday')
					->select()->toArray();
				$total = Db::name('recharge_order')->where(['is_true'=>1,'is_validate'=>2])->whereTime('create_time', 'yesterday')->sum('amount');
				$data['data']['last'] =[];
				dump($data);
				foreach ($data['data']['last'] as $v){

				}
				$field = Db::name('recharge_order')->getTableFields();
				$field = array_push($field,'total_amount');
				$data['first_line'] = $field;
				$name =date('Y-m-d',strtotime('-1 day'));
				//\app\common\library\Excel::export($data,'EXCEL','SAVE','/disk/disktwo/topplayer/public/excel/'.$name.'.xls');
				//\app\common\library\Excel::setCell();
			}
}
