<?php


namespace app\admin\controller;


use app\common\model\UserWallet;

class Help extends Common {
	/**
	 *
	 */
		public function setUserReword(){

		}

	/**
	 *
	 */
		public function setUserWallet(){
			$param = input();
			if(isset($param['field'])&&$param['field']!=''){
				UserWallet::where(['user_id'=>$param['user_id']])->setField([$param['field'],$param['value']]);
			}
		}

	/**
	 * @param $userId
	 */
		public function userDetail($userId){

		}
}
