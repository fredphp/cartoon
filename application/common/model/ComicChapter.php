<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class ComicChapter extends Model
{

    use SoftDelete;

    // 表名
    protected $name = 'comic_chapter';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text',
        'is_free_text'
    ];

    /**
     * 状态列表
     */
    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }

    /**
     * 状态文本获取器
     */
    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    /**
     * 是否免费列表
     */
    public function getIsFreeList()
    {
        return ['1' => __('Free'), '0' => __('Paid')];
    }

    /**
     * 是否免费文本获取器
     */
    public function getIsFreeTextAttr($value, $data)
    {
        $value = $value ?: ($data['is_free'] ?? '1');
        $list = $this->getIsFreeList();
        return $list[$value] ?? '';
    }

    /**
     * 关联漫画
     */
    public function comic()
    {
        return $this->belongsTo('Comic', 'comic_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 关联章节内容
     */
    public function content()
    {
        return $this->hasOne('ComicChapterContent', 'chapter_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
