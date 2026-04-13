<?php

namespace app\common\model;

use think\Model;

class ComicPurchase extends Model
{

    // 表名
    protected $name = 'comic_purchase';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;

    /**
     * 检查用户是否已购买某章节
     * @param int $userId 用户ID
     * @param int $chapterId 章节ID
     * @return bool
     */
    public static function hasPurchased($userId, $chapterId)
    {
        return self::where('user_id', $userId)
            ->where('chapter_id', $chapterId)
            ->find() !== null;
    }

    /**
     * 获取用户已购买的章节ID列表
     * @param int $userId 用户ID
     * @param int $comicId 漫画ID
     * @return array
     */
    public static function getPurchasedIds($userId, $comicId)
    {
        return self::where('user_id', $userId)
            ->where('comic_id', $comicId)
            ->column('chapter_id');
    }
}
