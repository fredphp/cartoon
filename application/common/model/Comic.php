<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;
use app\common\library\CacheService;

class Comic extends Model
{
    use SoftDelete;

    // 表名（使用 mha_ 前缀，独立于 fa_ 前缀）
    protected $table = 'mha_comic';

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
            CacheService::clearComic();
        });

        self::afterUpdate(function ($row) {
            CacheService::clearComic($row->id);
        });

        self::afterDelete(function ($row) {
            CacheService::clearComic($row->id);
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

    public function getCoverAttr($value)
    {
        if ($value && !preg_match('/^https?:\/\//', $value)) {
            $value = cdnurl($value, true);
        }
        return $value;
    }
}
