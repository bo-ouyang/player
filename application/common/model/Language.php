<?php
/**
 * 多语言
 */
namespace app\common\model;
use think\Model;
use Cache;

class Language extends Model
{
    // 主键
    protected $pk = 'language_id';
	// 表名
    protected $name = 'language';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    // 追加属性
    protected $append = [];
    // 多语言缓存键
    private static $_cacheKey = 'language_list';

    /**
     * 获取所语言列表
     */
    public function getLanguageList($status = null)
    {
        $list = Cache::get(self::$_cacheKey);
        if (empty($list)) {
            $data = is_null($status) ? $this->order('sort asc')->select() : self::where('status', $status)->order('sort asc')->select();
            $list = $data->toArray();
            Cache::set(self::$_cacheKey, $list, 86400);
        }

        return $list;
    }

    /**
     * 转换图片路径
     */
    public function getImageAttr($value)
    {
        return !empty($value) ? medias_url($value) : '';
    }
}