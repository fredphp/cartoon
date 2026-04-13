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
        'is_free_text',
        'status_text'
    ];
    

    
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
