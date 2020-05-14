<?php
/**
 * 首页
 */
namespace app\index\controller;
use Exception;
use think\facade\Hook;
use think\facade\Config;
use think\facade\Env;
/*use Hook;
use Config;
use Env;*/
use random\Random;

use app\common\exception\System as SystemException;
use app\common\exception\User as UserException;
use app\common\library\Output;

class Home extends Common
{
	public function index(){
		echo 111;
	}
	/**
	 * 语言列表
	 */
	public function languageList()
	{


        try {
        	$config = Config::get('app.model_status');
            $data = model('common/Language')->getLanguageList($config['enable']);
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
	}

    /**
     * 获取合约地址
     */
    public function contractAddress()
    {
        try {
            $data = service('index/Home')->getContractAddress();
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 根据转币地址查询邀请码
     */
    public function queryCode()
    {
        try {
            $data = service('index/Home')->queryCode($this->request->post('address'));
        } catch (UserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 解码密文
     */
    public function decodeString()
    {
        try {
            $data = service('index/Home')->decodeString($this->request->post('address'), $this->request->post('amount'), $this->request->post('key'));
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 系统统计
     * @return object
     * @throws Exception
     */
    public function statistic()
    {
        try {
            $data = service('index/Home')->statistic();
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 彩蛋详情
     * @return object
     * @throws Exception
     */
    public function eggDetail()
    {
        try {
            $data = service('index/Home')->eggDetail();
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 彩蛋订单
     * @return object
     * @throws Exception
     */
    public function eggOrder()
    {
        try {
            $inviteCode = $this->request->post('type', 1);
            $page = $this->request->post('page', 1);
            $listRows = $this->request->post('list_rows', Config::get('paginate.list_rows'));
            $data = service('index/Home')->eggOrder($inviteCode, $page, $listRows);
        } catch (UserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 彩蛋开奖结果
     * @return object
     * @throws Exception
     */
    public function eggReward()
    {
        try {
            $inviteCode = $this->request->post('type', 1);
            $page = $this->request->post('page', 1);
            $listRows = $this->request->post('list_rows', Config::get('paginate.list_rows'));
            $data = service('index/Home')->eggReward($inviteCode, $page, $listRows);
        } catch (UserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 获取系统配置
     * @return object
     * @throws Exception
     */
    public function config()
    {
        try {
            $key = $this->request->post('key');
            $data = service('index/Home')->config($key);
        } catch (UserException $userE) {
            return Output::error($userE->getCode(), $userE->getMessage());
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }

    /**
     * 股东列表
     * @return object
     * @throws Exception
     */
    public function gradeList()
    {
        try {
            $grade = $this->request->post('grade');
            $page = $this->request->post('page', 1);
            $listRows = $this->request->post('list_rows', Config::get('paginate.list_rows'));
            $data = service('index/Home')->gradeList($grade, $page, $listRows);
        } catch (Exception $e) {
            throw $e;
        }

        return Output::success($data);
    }
}
