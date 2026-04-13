<?php

namespace app\common\model;

use think\Model;


class UserFavorite extends Model
{

    

    

    // 表名
    protected $name = 'user_favorite';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    /**
     * 关联漫画
     */
    public function comic()
    {
        return $this->belongsTo('Comic', 'comic_id', 'id');
    }

    /**
     * 判断用户是否已收藏某漫画
     */
    public static function hasFavorited($userId, $comicId)
    {
        return self::where('user_id', $userId)
            ->where('comic_id', $comicId)
            ->count() > 0;
    }

    /**
     * 获取用户收藏的漫画ID列表
     */
    public static function getFavoritedIds($userId)
    {
        return self::where('user_id', $userId)
            ->column('comic_id');
    }

}
