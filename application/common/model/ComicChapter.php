<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;
use app\common\library\CacheService;

class ComicChapter extends Model
{
    use SoftDelete;

    // 表名（使用 mha_ 前缀）
    protected $table = 'mha_comic_chapter';

    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    protected $append = ['is_free_text', 'status_text'];

    protected static function init()
    {
        self::afterInsert(function ($row) {
            CacheService::clearChapter($row->comic_id, null);
        });

        self::afterUpdate(function ($row) {
            CacheService::clearChapter($row->comic_id, $row->id);
        });

        self::afterDelete(function ($row) {
            CacheService::clearChapter($row->comic_id, $row->id);
        });
    }

    public function getIsFreeList()
    {
        return ['0' => __('Is_free 0'), '1' => __('Is_free 1')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }

    public function getIsFreeTextAttr($value, $data)
    {
        $value = $value ?: ($data['is_free'] ?? '');
        $list = $this->getIsFreeList();
        return $list[$value] ?? '';
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    public function comic()
    {
        return $this->belongsTo('Comic', 'comic_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function content()
    {
        return $this->hasOne('ComicChapterContent', 'chapter_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
