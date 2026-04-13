<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;
use app\common\library\CacheService;

class Ad extends Model
{
    use SoftDelete;

    // 表名（使用 mha_ 前缀）
    protected $table = 'mha_ad';

    protected $autoWriteTimestamp = 'integer';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    protected $append = ['type_text', 'status_text', 'position_text'];

    protected static function init()
    {
        self::afterInsert(function ($row) {
            if (!$row['weigh']) {
                $pk = $row->getPk();
                $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
            }
            CacheService::clearBanner();
        });

        self::afterUpdate(function ($row) {
            CacheService::clearBanner();
        });

        self::afterDelete(function ($row) {
            CacheService::clearBanner();
        });
    }

    public function getTypeList()
    {
        return ['banner' => __('Banner'), 'popup' => __('Popup'), 'inline' => __('Inline')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }

    public function getPositionList()
    {
        return ['banner' => '首页轮播', 'popup' => '弹窗广告', 'inline' => '信息流广告'];
    }

    public function getTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['type'] ?? '');
        $list = $this->getTypeList();
        return $list[$value] ?? '';
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    public function getPositionTextAttr($value, $data)
    {
        $value = $data['position'] ?? '';
        $list = $this->getPositionList();
        return $list[$value] ?? '';
    }

    public function getImageAttr($value)
    {
        if ($value && !preg_match('/^https?:\/\//', $value)) {
            $value = cdnurl($value, true);
        }
        return $value;
    }
}
