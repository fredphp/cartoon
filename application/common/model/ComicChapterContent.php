<?php

namespace app\common\model;

use think\Model;
use app\common\library\CacheService;

class ComicChapterContent extends Model
{
    // 表名（使用 mha_ 前缀）
    protected $table = 'mha_comic_chapter_content';

    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    protected $append = ['images_arr'];

    protected static function init()
    {
        self::afterInsert(function ($row) {
            CacheService::clearChapter(null, $row->chapter_id);
        });

        self::afterUpdate(function ($row) {
            CacheService::clearChapter(null, $row->chapter_id);
        });

        self::afterDelete(function ($row) {
            CacheService::clearChapter(null, $row->chapter_id);
        });
    }

    public function getImagesAttr($value)
    {
        if (!$value) return [];
        $list = json_decode($value, true);
        if (!is_array($list)) return [];
        foreach ($list as &$item) {
            if ($item && !preg_match('/^https?:\/\//', $item)) {
                $item = cdnurl($item, true);
            }
        }
        return $list;
    }

    public function setImagesAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return $value;
    }

    public function getImagesArrAttr($value, $data)
    {
        return $this->getImagesAttr($data['images'] ?? '');
    }

    public function chapter()
    {
        return $this->belongsTo('ComicChapter', 'chapter_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
