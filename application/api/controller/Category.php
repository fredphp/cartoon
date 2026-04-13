<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\ComicCategory;
use app\common\model\ComicCategoryRelation;
use app\common\model\Comic;

/**
 * 分类导航接口
 */
class Category extends Api
{
    // 所有接口无需登录
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口
    protected $noNeedRight = ['*'];

    /**
     * 分类列表
     *
     * @ApiMethod (GET)
     * @ApiParams (name="with_comic_count", type="integer", required=false, description="是否返回漫画数量: 1=是")
     */
    public function index()
    {
        $withComicCount = $this->request->param('with_comic_count/d', 0);

        $list = ComicCategory::where('status', 'normal')
            ->where('deletetime', null)
            ->order('weigh', 'desc')
            ->order('id', 'asc')
            ->field('id,name,image,weigh')
            ->select();

        // 附加漫画数量
        if ($withComicCount) {
            foreach ($list as $item) {
                $item->comic_count = ComicCategoryRelation::where('category_id', $item->id)->count();
            }
        }

        $this->success('', $list);
    }

    /**
     * 分类下的漫画列表（分页）
     *
     * @ApiMethod (GET)
     * @ApiParams (name="category_id", type="integer", required=true, description="分类ID")
     * @ApiParams (name="page", type="integer", required=false, description="页码，默认1")
     * @ApiParams (name="pagesize", type="integer", required=false, description="每页条数，默认10")
     */
    public function comics()
    {
        $categoryId = $this->request->param('category_id/d', 0);
        if (!$categoryId) {
            $this->error(__('Invalid parameters'));
        }

        // 验证分类是否存在
        $category = ComicCategory::where('id', $categoryId)
            ->where('status', 'normal')
            ->where('deletetime', null)
            ->find();
        if (!$category) {
            $this->error(__('Category not found'));
        }

        $page = $this->request->param('page/d', 1);
        $pagesize = $this->request->param('pagesize/d', 10);
        $pagesize = min($pagesize, 50);

        // 获取该分类下的漫画ID
        $comicIds = ComicCategoryRelation::where('category_id', $categoryId)
            ->column('comic_id');

        if (empty($comicIds)) {
            $this->success('', [
                'category'    => ['id' => $category->id, 'name' => $category->name],
                'list'        => [],
                'total'       => 0,
                'page'        => $page,
                'pagesize'    => $pagesize,
                'total_page'  => 0,
            ]);
        }

        // 查询漫画列表
        $list = Comic::where('id', 'in', $comicIds)
            ->where('status', 'normal')
            ->where('deletetime', null)
            ->field('id,title,cover,description,author_id,status,createtime')
            ->order('weigh', 'desc')
            ->order('id', 'desc')
            ->page($page, $pagesize)
            ->select();

        $total = Comic::where('id', 'in', $comicIds)
            ->where('status', 'normal')
            ->where('deletetime', null)
            ->count();

        $this->success('', [
            'category'    => ['id' => $category->id, 'name' => $category->name],
            'list'        => $list,
            'total'       => $total,
            'page'        => $page,
            'pagesize'    => $pagesize,
            'total_page'  => ceil($total / $pagesize),
        ]);
    }
}
