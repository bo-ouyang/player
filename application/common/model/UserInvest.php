<?php


namespace app\common\model;


use think\Model;

class UserInvest extends Model {
	protected $pk   = 'id';
	protected $name = 'user_invest';
	protected $autoWriteTimestamp = 'int';
	protected $updateTime = 'update_time';
}
