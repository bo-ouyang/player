<?php
/**
 * 首页服务类
 */
namespace app\index\service;

use app\common\model\Egg;
use app\common\model\EggOrder;
use app\common\model\EggReward;
use app\common\model\RechargeOrder;
use app\common\model\SystemStatistic;
use app\common\model\TokenOrder;
use app\common\model\User;
use think\Db;
use think\facade\Config;
use think\facade\Env;
/*use Config;
use Env;*/
use app\common\model\User as UserModel;
use app\common\model\Config as ConfigModel;
use app\common\exception\User as UserException;
use app\common\library\Wallet;
use math\BCMath;


class Home
{

	/**
	 * 根据转币地址查询邀请码
	 */
	public function queryCode($address)
	{
		if (!$address) {
			return false;
		}
		$time=mktime(23,59,59,9,28,2019);
		$user = UserModel::where(['origin_address' => $address])->where('create_time','>',$time)->find();
        if (!$user) {
            throw_new(UserException::class, UserException::E_INVITE_CODE);
        }

        $user = $user->toArray();
       /* if (strtolower($user['address']) != strtolower($user['origin_address'])) {
            throw_new(UserException::class, UserException::E_INVITE_CODE);
        }*/
        $pUserId = User::getFieldByUserId($user['user_id'], 'parent_id');
        $pInviteCode = User::getFieldByUserId($pUserId, 'invite_code');

        return ['invite_code' => $user['invite_code'], 'super_code' => $pInviteCode];
	}

	/**
	 * 获取合约总金额
	 */
	public function getContractAddress()
	{

        return [
        	'contract_address' => Config::get('app.contract_address'),
            'egg_address'      => Config::get('app.egg_address'),
            'token_address'    => Config::get('app.token_address'),
        ];
	}

	/**
	 * 解码密文
	 */
	public function decodeString($address, $amount, $prvKey)
	{
		if (!$address || !$amount || !$prvKey) {
			return false;
		}

		$pubKey = Env::get('extend_path') . 'rsa/public.key';
		$originAddress = Wallet::decodeAddress($address, $pubKey, $prvKey);
		$originAmount = Wallet::decodeAddress($amount, $pubKey, $prvKey);

		return [
			'address' => $originAddress,
			'amount'  => $originAmount
		];
	}

    /**
     * 统计数据
     * @return array
     */
	public function statistic()
    {
	    $u_1001         = mktime(23,59,59,9,29,2019);
        $configModel        = model('common/Config');
        $tokenNumber        = $configModel->getConfig(ConfigModel::KEY_TOTAL_TOKEN);//token发行数量
        $tokenDestroy       = $configModel->getConfig(ConfigModel::KEY_TOTAL_DESTROY);//token销毁数量
        $volume             = $configModel->getConfig(ConfigModel::KEY_VOLUME);
        $tokenLimit         = $configModel->getConfig(ConfigModel::KEY_TOKEN_LIMIT);//token每日购买限制


        $incJackpot         = model('common/SystemStatistic')->getStatistic(SystemStatistic::KEY_TOTAL_STATIC);//奖池总额
        $incPerformance     = model('common/SystemStatistic')->getStatistic(SystemStatistic::KEY_TOTAL_PERFORMANCE);//平台业绩

	    $incJackpotFake     = model('common/SystemStatistic')->getStatistic(SystemStatistic::KEY_FAKE_TOTAL_STATIC);//奖池总额
	    $incPerformanceFake = model('common/SystemStatistic')->getStatistic(SystemStatistic::KEY_FAKE_TOTAL_PERFORMANCE);//平台业绩

	    $EGG            = model('common/SystemStatistic')->getStatistic(SystemStatistic::KEY_TOTAL_EGG_AMOUNT);//彩蛋业绩
        $beginTime      = strtotime(date('Y-m-d', strtotime('-1 day')));
        $endTime        = $beginTime + (3600 * 24);
        $yPerformance   = RechargeOrder::where('is_validate', RechargeOrder::VALIDATE_COMPLETE)->whereBetweenTime('create_time', $beginTime, $endTime)->sum('amount');
        $superUser      = model('common/User')->where('is_super', User::SUPER_YES)->where('create_time','>',$u_1001)->count();
        $primary        = model('common/User')->where('grade', User::GRADE_PRIMARY)->where('create_time','>',$u_1001)->count();
        $middle         = model('common/User')->where('grade', User::GRADE_MIDDLE)->where('create_time','>',$u_1001)->count();
        $high           = model('common/User')->where('grade', User::GRADE_HIGH)->where('create_time','>',$u_1001)->count();
        $super          = model('common/User')->where('grade', User::GRADE_SUPER)->where('create_time','>',$u_1001)->count();
        $one            = model('common/User')->where('grade', User::GRADE_ONE)->where('create_time','>',$u_1001)->count();
        $todayTokenNumber = TokenOrder::where('create_time', '>', strtotime(date('Y-m-d')))->where('is_validate', TokenOrder::VALIDATE_COMPLETE)->sum('number');
        return [
            'token_number' => $tokenNumber,
            'token_destroy' => $tokenDestroy,
            'total_static' => BCMath::add($incJackpot,$incJackpotFake,2),
            'total_performance' => BCMath::add($incPerformance,$incPerformanceFake,2),
            'yesterday_performance' => $yPerformance,
            'total_egg' => $EGG,
            'super_user' => $superUser,
            'volume' => $volume,
            'primary' => $primary,
            'middle' => $middle,
            'high' => $high,
            'super' => $super,
            'one' => $one,
            'today_token_number' => $todayTokenNumber,
            'token_limit' => $tokenLimit,
	        'sys_detail'=>1
        ];
    }

	/**
	 * 彩蛋详情
	 * @return array
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function eggDetail()
	{
		$time    = mktime(0,0,0,10,1,2019);
		$eggInfo = Egg::where('status', Egg::STATUS_PENDING)->where('create_time','>',$time)->select();
		$data    = [];
		for ($i = 1;$i <= 4;$i++) {
			$data[$i] = [
				'name' => Egg::$quotaLabel[$i],
				'amount' => 0,
				'quota' => model('common/Config')->getConfig(Egg::$quotaKeyMap[$i]),
				'num'=>Egg::$quotaKeyUser[$i],
			];
		}
		if (empty($eggInfo)) {
			return $data;
		}
		$eggInfo = $eggInfo->toArray();
		foreach ($eggInfo as $key => $info) {
			$data[$info['type']]['amount'] = $info['amount'];
			$data[$info['type']]['quota'] = $info['quota'];
		}

		return $data;
	}

	/**
	 * 彩蛋订单
	 * @param $type
	 * @param $page
	 * @param int $listRows
	 * @return array
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function eggOrder($type, $page, $listRows = 20)
	{
		$time  = mktime(0,0,0,10,1,2019);
		$model = EggOrder::alias('eo')
				->leftJoin('one_user u', 'eo.user_id=u.user_id')
				->field('eo.create_time,eo.amount,u.origin_address')
				->where('type', $type)
				->where('eo.create_time','>',$time);
		$list  = $model->order('order_id DESC')->page($page, $listRows)->select();
		//$list = $list ? $list->toArray() : [];
		//$list = [];
		$total = 0;
		return ['list' => $list, 'total'=> $total];
	}

	/**
	 * 彩蛋开奖结果
	 * @param $type
	 * @param $page
	 * @param int $listRows
	 * @return array
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function eggReward($type, $page, $listRows = 20)
	{
		$time  = mktime(0,0,0,10,1,2019);
		$model = EggReward::alias('er')
				->leftJoin('one_user u', 'er.user_id=u.user_id')
				->leftJoin('one_egg e', 'e.egg_id=er.egg_id')
				->field('er.egg_id,er.user_id,er.create_time,er.amount,u.origin_address')
				->where('e.type', $type)
				->where('er.create_time','>',$time);
		$list  = $model->order('reward_id DESC')->page($page, $listRows)->select();
		$list  = $list ? $list->toArray() : [];
		foreach ($list as &$item) {
			$item['cost'] = EggOrder::where('user_id', $item['user_id'])->where('egg_id', $item['egg_id'])->sum('amount');
		}
		return ['list' => $list, 'total'=> $model->count()];
	}





    /**
     * 获取配置
     * @param $key
     * @return mixed
     */
    public function config($key)
    {
        return ConfigModel::getFieldByKey($key, 'value');
    }

    /**
     * 股东列表
     * @param $grade
     * @param $page
     * @param $listRows
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function gradeList($grade, $page, $listRows)
    {
    	$time  = mktime(23,59,59,9,28,2019);
        $model = User::alias('u')
                ->leftJoin('one_upgrade_log l', 'l.user_id = u.user_id AND u.grade = l.grade')
                ->where('u.grade', $grade)->where('l.create_time','>',$time)->field('invite_code, origin_address, l.create_time');
        $list  = $model->order('log_id DESC')->page($page, $listRows)->select();
        $list  = $list ? $list->toArray() : [];
        return ['list' => $list, 'total' => $model->count()];
    }
}
