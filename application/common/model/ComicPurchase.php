<?php

namespace app\common\model;

use think\Model;

/**
 * 漫画购买记录模型
 * 统一购买记录表，供 Chapter 和 Pay 控制器共用
 */
class ComicPurchase extends Model
{
    // 表名（使用 mha_ 前缀，独立于 fa_ 前缀，需显式声明）
    protected $table = 'mha_comic_purchase';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;

    /**
     * 关联章节
     */
    public function chapter()
    {
        return $this->belongsTo('ComicChapter', 'chapter_id', 'id');
    }

    /**
     * 检查用户是否已购买某章节
     *
     * @param int $userId
     * @param int $chapterId
     * @return bool
     */
    public static function hasPurchased($userId, $chapterId)
    {
        return self::where('user_id', $userId)
            ->where('chapter_id', $chapterId)
            ->find() !== null;
    }

    /**
     * 获取用户已购买的章节ID列表（按漫画筛选）
     *
     * @param int $userId
     * @param int $comicId
     * @return array
     */
    public static function getPurchasedIds($userId, $comicId)
    {
        return self::where('user_id', $userId)
            ->where('comic_id', $comicId)
            ->column('chapter_id');
    }
}
