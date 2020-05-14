<?php
/**
 * 会员服务类
 * @author chat
 * @version 1.0
 */
namespace app\admin\service;

use app\common\exception\User as UserException;
use app\common\model\CashLog;
use app\common\model\CurrentOrder;
use app\common\model\RegularOrder;
use app\common\model\User;
use app\common\model\UserBind;
use app\common\model\UserWallet;
use math\BCMath;
use think\Exception;
use think\Facade\Config;
use think\Db;

class Member
{
    /**
     * 会员列表
     * @param $inviteCode
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public function list($inviteCode, $param)
    {
        $model = model('common/User')->alias('u')
            ->join('one_user p','p.user_id=u.parent_id', 'left')
            ->join('one_user_wallet w','w.user_id=u.user_id', 'left')
            ->order('u.user_id DESC');
        if (!empty($param['address'])) {
            $model = $model->where('u.origin_address', 'LIKE', "%{$param['address']}%");
        }
        if (!empty($param['invite_code'])) {
            $model = $model->where("u.invite_code='{$param['invite_code']}' or p.invite_code='{$param['invite_code']}'");
        }
        if (!empty($param['is_super'])) {
        	if($param['is_super']==3||$param['is_super']==1){
		        $model = $model->where('u.is_fake', '<>',1);
	        }else{
		        $model = $model->where('u.is_super', $param['is_super']);
	        }

        }
        if (!empty($inviteCode)) {
            $userId = User::getFieldByInviteCode($inviteCode, 'user_id');
            if (!empty($userId)) {
                $userIds = UserBind::where('parent_id', $userId)->column('user_id');
                array_unshift($userIds, $userId);
                $model = $model->where('u.user_id', 'IN', $userIds);
            }
        }
		$time = mktime(23,59,59,9,28,2019);
        $userList = $model->where('u.create_time','>',$time)
	        ->field('u.user_id, u.invite_code,u.is_super, p.invite_code as parent_invite_code,u.origin_address,u.grade,u.create_time,w.*')
            ->page($param['page'], Config::get('paginate.list_rows'))->select();
        $userList = $userList ? $userList->toArray() : [];
        $total = $model->count();
        foreach ($userList as &$value) {
            $value['share_number'] = User::where('parent_id', $value['user_id'])->count();
            $value['total_invest'] = BCMath::add($value['desert_amount'], $value['oasis_amount'], 12);
            $value['total_reward'] = $value['desert_profit'] + $value['oasis_profit'] + $value['team_profit'] + $value['egg_profit'] + $value['invite_profit'] + $value['super_profit'];
            $value['create_time']   =User::where(['user_id'=>$value['user_id']])->value('create_time');
            //总业绩
            $child = UserBind::where('parent_id', $value['user_id'])->column('user_id');
            $child[] = $value['user_id'];
            $totalPerformance = UserWallet::where('user_id', 'in', $child)->field('sum(desert_amount) + sum(oasis_amount) as total')->select();
            $value['total_performance'] = $totalPerformance[0]['total'];
        }

        return ['list' => $userList, 'total' => $total];
    }

    /**
     * 变更用户上级
     * @param $userId
     * @param $parentId
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function parentChange($userId, $inviteCode)
    {
        return false;
        $parentId = User::getFieldByInviteCode($inviteCode, 'user_id');
        $originParentId = User::getFieldByUserId($userId, 'parent_id');
        if (empty($parentId)) {
            return false;
        }
        $bindData[] = [
            'parent_id' => $parentId,
            'level'     => 1
        ];
        //用户所有新上级
        $parentList = UserBind::where('user_id', $parentId)->select();
        $parentList = $parentList ? $parentList->toArray() : [];
        foreach ($parentList as $key => $value) {
            $bindData[] = [
                'parent_id' => $value['parent_id'],
                'level'     => $value['level'] + 1
            ];
        }

        //新上级新增下级
        $childList = UserBind::where('parent_id', $userId)->select();
        $childList = $childList ? $childList->toArray() : [];
        $addChild = [];
        foreach ($childList as $key => $value) {
            $addChild[] = [
                'parent_id' => $parentId,
                'level'     => $value['level'] + 1
            ];
        }

        Db::startTrans();
        try {
            //用户删除所有老上级
            $result = UserBind::where('user_id', $userId)->delete();
            if (!$result) {
                Db::rollback();
                return false;
            }

            //用户更新直接上级
            $result = model('common/User')->where('user_id', $userId)->update(['parent_id' => $parentId]);
            if (!$result) {
                Db::rollback();
                return false;
            }
            foreach ($bindData as &$item) {
                $item['user_id'] = $userId;
            }
            //用户添加所有上级
            $result = model('common/UserBind')->saveAll($bindData);
            if (!$result) {
                Db::rollback();
                return false;
            }

            //新用户添加下级
            $result = model('common/UserBind')->saveAll($addChild);
            if (!$result) {
                Db::rollback();
                return false;
            }

            //老用户删除下级
            foreach ($childList as $key => $value) {
                $result = UserBind::where('user_id', $value['user_id'])->where('parent_id', $originParentId)->delete();
                if (!$result) {
                    Db::rollback();
                    return false;
                }
            }

        } catch (Exception $e) {
            Db::rollback();
            return false;
        }
        Db::commit();
        return true;
    }

    /**
     * 设置超级节点
     * @param $userId
     * @param $group
     * @return int|string
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function groupEdit($userId, $group)
    {
        return User::where('user_id', $userId)->update(['is_super' => $group, 'update_time' => time()]);
    }

    public function bindRedress($userId, $originParentId, $parentId)
    {
        $userChild = UserBind::where('parent_id', $userId)->select();
        $userChild = $userChild ? $userChild->toArray() : [];
        Db::startTrans();
        foreach ($userChild as $key => $value) {
            //删除多余的子级
            $result = UserBind::where('parent_id', $originParentId)->where('user_id', $value['user_id'])->where('level', $value['level'] + 1)->count();
            if ($result) {
                $result = UserBind::where('parent_id', $originParentId)->where('user_id', $value['user_id'])->where('level', $value['level'] + 1)->delete();
                if (!$result) {
                    echo 1;
                    print_r($value);
                    Db::rollback();
                    return false;
                }
            }

            //添加父级
            $result = UserBind::where('user_id', $value['user_id'])->where('parent_id', $parentId)->count();
            if (!$result) {
                $result = UserBind::insert([
                    'user_id'   => $value['user_id'],
                    'parent_id' => $parentId,
                    'level'     => $value['level'] + 1,
                ]);
                if (!$result) {
                    echo 2;
                    print_r([
                        'user_id'   => $value['user_id'],
                        'parent_id' => $parentId,
                        'level'     => $value['level'] + 1,
                    ]);
                    Db::rollback();
                    return false;
                }
            }
        }
        Db::commit();
        return true;
    }

    /**
     * 会员添加
     * @param $data
     * @return mixed
     */
    public function memberAdd($data)
    {
        $userInfo = User::where(['origin_address' => $data['address']])->find();
        if (!empty($userInfo)) {
            throw_new(UserException::class, UserException::E_EXISTS);
        }
        $parentId = User::getFieldByInviteCode($data['invite_code'], 'parent_id');
        if (empty($parentId)) {
            $parentId = 1;
        }
        $data['parent_id'] = $parentId;
        return service('index/User')->userAdd($data);
    }
}
