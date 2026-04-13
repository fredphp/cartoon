<?php

namespace app\common\model;

use think\Model;

/**
 * 用户收藏模型
 */
class UserFavorite extends Model
{
    // 表名（使用 mha_ 前缀，独立于 fa_ 前缀，需显式声明）
    protected $table = 'mha_user_favorite';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 关联漫画
     */
    public function comic()
    {
        return $this->belongsTo('Comic', 'comic_id', 'id');
    }

    /**
     * 判断用户是否已收藏某漫画
     *
     * @param int $userId
     * @param int $comicId
     * @return bool
     */
    public static function hasFavorited($userId, $comicId)
    {
        return self::where('user_id', $userId)
            ->where('comic_id', $comicId)
            ->count() > 0;
    }

    /**
     * 获取用户收藏的漫画ID列表
     *
     * @param int $userId
     * @return array
     */
    public static function getFavoritedIds($userId)
    {
        return self::where('user_id', $userId)
            ->column('comic_id');
    }
}
