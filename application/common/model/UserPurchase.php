<?php

namespace app\common\model;

use think\Model;

/**
 * 用户购买记录模型
 */
class UserPurchase extends Model
{
    // 表名（使用 mha_ 前缀，独立于 fa_ 前缀，需显式声明）
    protected $table = 'mha_user_purchase';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 关联章节
     */
    public function chapter()
    {
        return $this->belongsTo('ComicChapter', 'chapter_id', 'id');
    }

    /**
     * 判断用户是否已购买某章节
     *
     * @param int $userId
     * @param int $chapterId
     * @return bool
     */
    public static function hasPurchased($userId, $chapterId)
    {
        return self::where('user_id', $userId)
            ->where('chapter_id', $chapterId)
            ->count() > 0;
    }

    /**
     * 获取用户已购买的章节ID列表（按漫画）
     *
     * @param int $userId
     * @param int|null $comicId 传则按漫画筛选
     * @return array
     */
    public static function getPurchasedIds($userId, $comicId = null)
    {
        $query = self::where('user_id', $userId);
        if ($comicId) {
            $chapterIds = \app\common\model\ComicChapter::where('comic_id', $comicId)
                ->column('id');
            if (empty($chapterIds)) {
                return [];
            }
            $query = $query->where('chapter_id', 'in', $chapterIds);
        }
        return $query->column('chapter_id');
    }
}
