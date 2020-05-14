<?php
/**
 * 会员模型
 */
namespace app\common\model;
use think\Model;
use random\Random;
use app\common\library\Wallet;

class User extends Model
{
    // 主键
    protected $pk = 'user_id';
    // 表名
    protected $name = 'user';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    // 追加属性
    protected $append = [];
    // 设置自动完成
    protected $auto = ['address'];

    const STATUS_ABLE   = 1;
    const STATUS_UNABLE = 2;

    const SUPER_NO  = 1;
    const SUPER_YES = 2;

    const GRADE_PRIMARY = 1;
    const GRADE_MIDDLE  = 2;
    const GRADE_HIGH    = 3;
    const GRADE_SUPER   = 4;
    const GRADE_ONE     = 5;
    public static $groupLabel = [
        self::GRADE_PRIMARY   => '初级玩家',
        self::GRADE_MIDDLE    => '中级玩家',
        self::GRADE_HIGH      => '高级玩家',
        self::GRADE_SUPER     => '超级玩家',
        self::GRADE_ONE       => '康富人家',
        ];

    /**
     * 当前父ID集合
     * @var array
     */
    public static $parentIds = [];

    /**
     * 查找所有的上级ID
     */
    public function getUserPids($userId)
    {
        $pid = self::where(['user_id' => $userId])->value('agent_id');
        if ($pid) {
            self::$parentIds[] = $pid;
            // 有下线就一直循环找
            if ($pid != 1) {
                // Pid等于1则表示已查找到表中第一行
                $this->getUserPids($pid);
            }
        } else {
            return true;
        }
    }

    /**
     * 获取邀请码
     */
    public function getInviteCode()
    {
        $code = Random::numeric(6);
        $time = mktime(23,59,59,9,28,2019);
        $exists = self::where('invite_code', $code)->where('create_time','>',$time)->find();
        if ($exists) {
            $code = Random::numeric(6);
            $exists = self::where('invite_code', $code)->where('create_time','>',$time)->find();
            if ($exists) {
                self::getInviteCode();
            }
        }
        return $code;
    }

    /**
     * 获取钱包地址
     * @param $userId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserAddress($userId)
    {
        $userInfo = $this->where('user_id', $userId)->field('address')->find();
        if (!$userInfo) {
            return false;
        }

        $userInfo = $userInfo->toArray();
        return $userInfo['address'];
    }

    /**
     * 修改获取的数据
     */
    public function getAddressAttr($value)
    {
        if ($value) {
            return Wallet::decodeAddress($value);
        }

        return $value;
    }

    /**
     * 修改插入的数据
     */
    public function setAddressAttr($value)
    {
        if ($value) {
            return Wallet::encodeAddress($value);
        }

        return $value;
    }
}
