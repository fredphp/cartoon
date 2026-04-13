<?php

namespace app\common\model;

use think\Model;
use app\common\library\CacheService;

class ComicCategoryRelation extends Model
{
    // 表名（使用 mha_ 前缀）
    protected $table = 'mha_comic_category_relation';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = false;

    protected static function init()
    {
        self::afterInsert(function ($row) {
            CacheService::clearCategory();
            // 漫画分类变更，清除该漫画详情缓存
            cache('mha:api:comic:detail:' . $row->comic_id, null);
        });

        self::afterDelete(function ($row) {
            CacheService::clearCategory();
            cache('mha:api:comic:detail:' . $row->comic_id, null);
        });
    }

    public function comic()
    {
        return $this->belongsTo('Comic', 'comic_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo('ComicCategory', 'category_id', 'id');
    }

    public static function getCategoryIdsByComicId($comicId)
    {
        return self::where('comic_id', $comicId)->column('category_id');
    }

    public static function getComicIdsByCategoryId($categoryId)
    {
        return self::where('category_id', $categoryId)->column('comic_id');
    }

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
