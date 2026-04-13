<?php

namespace app\common\model;

use think\Model;

class ComicChapterContent extends Model
{

    // 表名
    protected $name = 'comic_chapter_content';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 追加属性
    protected $append = [
        'images_arr'
    ];

    /**
     * 图片列表获取器 - JSON转数组
     */
    public function getImagesAttr($value)
    {
        if (!$value) {
            return [];
        }
        $list = json_decode($value, true);
        if (!is_array($list)) {
            return [];
        }
        // 自动补全域名
        foreach ($list as &$item) {
            if ($item && !preg_match('/^https?:\/\//', $item)) {
                $item = cdnurl($item, true);
            }
        }
        return $list;
    }

    /**
     * 图片列表设置器 - 数组转JSON
     */
    public function setImagesAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return $value;
    }

    /**
     * 图片数组追加属性（用于API返回）
     */
    public function getImagesArrAttr($value, $data)
    {
        $images = $this->getImagesAttr($data['images'] ?? '');
        return $images;
    }

    /**
     * 关联章节
     */
    public function chapter()
    {
        return $this->belongsTo('ComicChapter', 'chapter_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
