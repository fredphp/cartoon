<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class ComicCategory extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'comic_category';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text'
    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            if (!$row['weigh']) {
                $pk = $row->getPk();
                $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
            }
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
