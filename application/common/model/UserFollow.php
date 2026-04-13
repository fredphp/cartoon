<?php

namespace app\common\model;

use think\Model;

/**
 * 用户关注模型
 */
class UserFollow extends Model
{
    // 表名（使用 mha_ 前缀，独立于 fa_ 前缀，需显式声明）
    protected $table = 'mha_user_follow';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    /**
     * 关联被关注的用户（作者）
     */
    public function followUser()
    {
        return $this->belongsTo('User', 'follow_user_id', 'id');
    }

    /**
     * 关联关注者
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id');
    }

    /**
     * 判断用户是否已关注某作者
     *
     * @param int $userId
     * @param int $followUserId
     * @return bool
     */
    public static function hasFollowed($userId, $followUserId)
    {
        return self::where('user_id', $userId)
            ->where('follow_user_id', $followUserId)
            ->count() > 0;
    }

    /**
     * 获取用户关注的作者ID列表
     *
     * @param int $userId
     * @return array
     */
    public static function getFollowedIds($userId)
    {
        return self::where('user_id', $userId)
            ->column('follow_user_id');
    }

    /**
     * 获取用户的粉丝数
     *
     * @param int $userId
     * @return int
     */
    public static function getFollowerCount($userId)
    {
        return self::where('follow_user_id', $userId)->count();
    }

    /**
     * 获取用户的关注数
     *
     * @param int $userId
     * @return int
     */
    public static function getFollowingCount($userId)
    {
        return self::where('user_id', $userId)->count();
    }
}
