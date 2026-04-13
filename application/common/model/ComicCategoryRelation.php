<?php

namespace app\common\model;

use think\Model;

/**
 * 漫画分类关联模型
 */
class ComicCategoryRelation extends Model
{
    // 表名（使用 mha_ 前缀，独立于 fa_ 前缀，需显式声明）
    protected $table = 'mha_comic_category_relation';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;

    /**
     * 关联漫画
     */
    public function comic()
    {
        return $this->belongsTo('Comic', 'comic_id', 'id');
    }

    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo('ComicCategory', 'category_id', 'id');
    }

    /**
     * 获取漫画所属的分类ID列表
     *
     * @param int $comicId
     * @return array
     */
    public static function getCategoryIdsByComicId($comicId)
    {
        return self::where('comic_id', $comicId)->column('category_id');
    }

    /**
     * 获取分类下的漫画ID列表
     *
     * @param int $categoryId
     * @return array
     */
    public static function getComicIdsByCategoryId($categoryId)
    {
        return self::where('category_id', $categoryId)->column('comic_id');
    }

    /**
     * 设置漫画的分类（先删后增）
     *
     * @param int $comicId
     * @param array $categoryIds
     */
    public static function setComicCategories($comicId, $categoryIds = [])
    {
        self::where('comic_id', $comicId)->delete();
        foreach ($categoryIds as $categoryId) {
            self::create([
                'comic_id'    => $comicId,
                'category_id' => $categoryId,
            ]);
        }
    }
}
