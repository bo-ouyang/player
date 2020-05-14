<?php
namespace app\admin\model;
use think\Model;
use Config;

class AdminUser extends Model
{
	// 表名
    protected $name = 'admin_user';
    // 主键
    protected $pk = 'admin_user_id';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    // 追加属性
    protected $append = [];

    // 用户状态
	const STATUS_ENABLE  = 1;
	const STATUS_DISABLE = 2;
    public static $statusLabels = [
        self::STATUS_ENABLE  => '正常',
        self::STATUS_DISABLE => '禁用',
    ];

    //经理人用户组
    const SUPPORTER_GROUP = 2;

    /**
     * 获取用户信息
     */
    public function info($userId)
    {
        return self::get($userId);
    }

    /**
     * 管理员列表
     * @param $page
     * @param string $field
     * @return array|\PDOStatement|string|\think\Collection
     */
    public  function userList($page, $field = '*')
    {
        $model = new static();
        $model = $model->field($field);
        $model->where('create_time','>',mktime(23,59,59,9,29,2019));
        $pageSize = \think\facade\Config::get('paginate.list_rows');
        $list = ($page > 0) ? $model->page($page, $pageSize)->select() : $model->select();
        if ($list) {
            $list = !is_array($list) ? $list->toArray() : $list;
            return ($page > 0) ? ['list' => $list, 'total' => $model->count()] : $list;
        }
    }

    /**
     * 填充列表中经理人信息
     * @param $list
     * @param array $field
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function fillAdminFiled($list, array $field)
    {
        $adminIds = [];
        foreach ($list as $item) {
            if (!in_array($item['admin_id'], $adminIds)) {
                $adminIds[] = $item['admin_id'];
            }
        }
        if (empty($adminIds)) {
            return $list;
        }
        $field['admin_user_id'] = 'admin_user_id';
        $adminData = self::where('admin_user_id', 'in', $adminIds)->field(join(',', $field))->select();
        unset($field['admin_user_id']);
        $adminData = $adminData ? $adminData->toArray() : [];
        $adminList = [];
        foreach ($adminData as $adminInfo) {
            foreach ($field as $value) {
                $adminList[$adminInfo['admin_user_id']] = $adminInfo[$value];
            }
        }

        foreach ($list as &$item) {
            foreach ($field as $key => $value) {
                $item[$key] = '';
                if (isset($adminList[$item['admin_id']])) {
                    $item[$key] = $adminList[$item['admin_id']];
                }
            }
        }
        return $list;
    }

    /**
     * 转换头像
     */
    public function getWechatQrcodeAttr($value)
    {
        return !empty($value) ? medias_url($value) : '';
    }

    /**
     * 获取随机经理人
     */
    public function getCustomerService()
    {
        $list = AuthGroupAccess::where('group_id', self::SUPPORTER_GROUP)->column('uid');
        $rand_list = array_rand($list, 1); //随机抽取1条

        return $list[$rand_list];
    }

}
