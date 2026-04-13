<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

/**
 * 广告模型
 */
class Ad extends Model
{
    use SoftDelete;

    // 表名（使用 mha_ 前缀，独立于 fa_ 前缀，需显式声明）
    protected $table = 'mha_ad';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'type_text',
        'status_text',
        'position_text',
    ];

    /**
     * 插入后自动设置权重
     */
    protected static function init()
    {
        self::afterInsert(function ($row) {
            if (!$row['weigh']) {
                $pk = $row->getPk();
                $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
            }
        });
    }

    /**
     * 广告类型列表
     */
    public function getTypeList()
    {
        return ['banner' => __('Banner'), 'popup' => __('Popup'), 'inline' => __('Inline')];
    }

    /**
     * 状态列表
     */
    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }

    /**
     * 广告位置列表
     */
    public function getPositionList()
    {
        return [
            'banner'  => '首页轮播',
            'popup'   => '弹窗广告',
            'inline'  => '信息流广告',
        ];
    }

    /**
     * 广告类型文本
     */
    public function getTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['type'] ?? '');
        $list = $this->getTypeList();
        return $list[$value] ?? '';
    }

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    /**
     * 位置文本
     */
    public function getPositionTextAttr($value, $data)
    {
        $value = $data['position'] ?? '';
        $list = $this->getPositionList();
        return $list[$value] ?? '';
    }

    /**
     * 图片获取器 - 自动补全域名
     */
    public function getImageAttr($value)
    {
        if ($value && !preg_match('/^https?:\/\//', $value)) {
            $value = cdnurl($value, true);
        }
        return $value;
    }
}
