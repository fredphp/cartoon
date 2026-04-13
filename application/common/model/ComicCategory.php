<?php

namespace app\common\model;

use think\Model;

/**
 * 漫画分类模型
 */
class ComicCategory extends Model
{
    // 表名（使用 mha_ 前缀，独立于 fa_ 前缀，需显式声明）
    protected $table = 'mha_comic_category';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 软删除
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
     * 图标获取器 - 自动补全域名
     */
    public function getImageAttr($value)
    {
        if ($value && !preg_match('/^https?:\/\//', $value)) {
            $value = cdnurl($value, true);
        }
        return $value;
    }

    /**
     * 获取分类下的漫画数量
     */
    public function getComicCountAttr($value, $data)
    {
        return ComicCategoryRelation::where('category_id', $data['id'])->count();
    }

    /**
     * 关联漫画（多对多）
     */
    public function comics()
    {
        return $this->belongsToMany('Comic', 'mha_comic_category_relation', 'comic_id', 'category_id');
    }
}
