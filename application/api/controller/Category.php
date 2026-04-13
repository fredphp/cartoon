<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\CacheService;
use app\common\model\ComicCategory;
use app\common\model\ComicPurchase;

/**
 * 分类导航接口
 */
class Category extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 分类列表
     *
     * @ApiMethod (GET)
     */
    public function index()
    {
        $list = CacheService::getCategoryList();

        $withComicCount = $this->request->param('with_comic_count/d', 0);
        if ($withComicCount) {
            $categoryIds = array_column($list, 'id');
            $countMap = [];
            if (!empty($categoryIds)) {
                $rows = \app\common\model\ComicCategoryRelation::where('category_id', 'in', $categoryIds)
                    ->group('category_id')
                    ->field('category_id, count(*) as cnt')
                    ->select();
                foreach ($rows as $row) {
                    $countMap[$row['category_id']] = $row['cnt'];
                }
            }
            foreach ($list as &$item) {
                $item['comic_count'] = $countMap[$item['id']] ?? 0;
            }
            unset($item);
        }

        $this->success('', $list);
    }

    /**
     * 分类下的漫画列表（分页）
     *
     * @ApiMethod (GET)
     */
    public function comics()
    {
        $categoryId = $this->request->param('category_id/d', 0);
        if (!$categoryId) {
            $this->error(__('Invalid parameters'));
        }

        // 验证分类
        $category = ComicCategory::where('id', $categoryId)
            ->where('status', 'normal')
            ->where('deletetime', null)
            ->find();
        if (!$category) {
            $this->error(__('Category not found'));
        }

        $page = $this->request->param('page/d', 1);
        $pagesize = min($this->request->param('pagesize/d', 10), 50);

        $data = CacheService::getCategoryComics($categoryId, $page, $pagesize);

        $this->success('', [
            'category'   => ['id' => $category->id, 'name' => $category->name],
            'list'       => $data['list'],
            'total'      => $data['total'],
            'page'       => $page,
            'pagesize'   => $pagesize,
            'total_page' => $data['total_page'],
        ]);
    }
}
