<?php

namespace app\common\model;

use think\Model;

/**
 * 漫画模型
 */
class Comic extends Model
{
    // 表名（使用 mha_ 前缀，独立于 fa_ 前缀，需显式声明）
    protected $table = 'mha_comic';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 软删除字段
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text',
    ];

    /**
     * 状态列表
     */
    public static function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }

    /**
     * 状态文本获取器
     */
    public function getStatusTextAttr($value, $data)
    {
        $list = $this->getStatusList();
        return isset($data['status']) ? $list[$data['status']] : '';
    }

    /**
     * 封面图获取器 - 自动补全域名
     */
    public function getCoverAttr($value)
    {
        if ($value && !preg_match('/^https?:\/\//', $value)) {
            $value = cdnurl($value, true);
        }
        return $value;
    }
}
