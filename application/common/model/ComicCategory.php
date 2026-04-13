<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;
use app\common\library\CacheService;

class ComicCategory extends Model
{
    use SoftDelete;

    // 表名（使用 mha_ 前缀）
    protected $table = 'mha_comic_category';

    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    protected $append = ['status_text'];

    protected static function init()
    {
        self::afterInsert(function ($row) {
            if (!$row['weigh']) {
                $pk = $row->getPk();
                $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
            }
            CacheService::clearCategory();
        });

        self::afterUpdate(function ($row) {
            CacheService::clearCategory();
        });

        self::afterDelete(function ($row) {
            CacheService::clearCategory();
        });
    }

    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    public function getImageAttr($value)
    {
        if ($value && !preg_match('/^https?:\/\//', $value)) {
            $value = cdnurl($value, true);
        }
        return $value;
    }

    public function getComicCountAttr($value, $data)
    {
        return ComicCategoryRelation::where('category_id', $data['id'])->count();
    }

    public function comics()
    {
        return $this->belongsToMany('Comic', 'mha_comic_category_relation', 'comic_id', 'category_id');
    }
}
