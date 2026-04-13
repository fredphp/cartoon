<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\UserFavorite;
use app\common\model\Comic;

/**
 * 收藏接口
 */
class Favorite extends Api
{
    // 所有接口都需要登录（收藏、取消收藏、我的收藏列表）
    protected $noNeedLogin = [];
    // 无需鉴权的接口
    protected $noNeedRight = ['*'];

    /**
     * 收藏漫画
     *
     * @ApiMethod (POST)
     * @ApiParams (name="comic_id", type="integer", required=true, description="漫画ID")
     */
    public function add()
    {
        $comicId = $this->request->param('comic_id/d', 0);
        if (!$comicId) {
            $this->error(__('Invalid parameters'));
        }

        // 验证漫画是否存在
        $comic = Comic::where('id', $comicId)
            ->where('deletetime', null)
            ->where('status', 'normal')
            ->find();
        if (!$comic) {
            $this->error(__('Comic not found'));
        }

        $userId = $this->auth->id;

        // 检查是否已收藏
        if (UserFavorite::hasFavorited($userId, $comicId)) {
            $this->error(__('Already favorited'));
        }

        // 创建收藏记录
        UserFavorite::create([
            'user_id'  => $userId,
            'comic_id' => $comicId,
        ]);

        $this->success(__('Favorite added successfully'));
    }

    /**
     * 取消收藏
     *
     * @ApiMethod (POST)
     * @ApiParams (name="comic_id", type="integer", required=true, description="漫画ID")
     */
    public function remove()
    {
        $comicId = $this->request->param('comic_id/d', 0);
        if (!$comicId) {
            $this->error(__('Invalid parameters'));
        }

        $userId = $this->auth->id;

        $favorite = UserFavorite::where('user_id', $userId)
            ->where('comic_id', $comicId)
            ->find();

        if (!$favorite) {
            $this->error(__('Not favorited'));
        }

        $favorite->delete();

        $this->success(__('Favorite removed successfully'));
    }

    /**
     * 我的收藏列表（分页，含漫画信息）
     *
     * @ApiMethod (GET)
     * @ApiParams (name="page", type="integer", required=false, description="页码，默认1")
     * @ApiParams (name="pagesize", type="integer", required=false, description="每页条数，默认10")
     */
    public function index()
    {
        $page = $this->request->param('page/d', 1);
        $pagesize = $this->request->param('pagesize/d', 10);

        // 限制每页最大条数
        $pagesize = min($pagesize, 50);

        $userId = $this->auth->id;

        $list = UserFavorite::where('user_id', $userId)
            ->with(['comic' => function ($query) {
                $query->where('deletetime', null)
                      ->where('status', 'normal')
                      ->field('id,title,cover,description,author_id,status,createtime');
            }])
            ->order('id', 'desc')
            ->page($page, $pagesize)
            ->select();

        // 过滤掉漫画已被删除的收藏项
        $list = $list->filter(function ($item) {
            return $item->comic !== null;
        });

        $total = UserFavorite::where('user_id', $userId)->count();

        $this->success('', [
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'pagesize'   => $pagesize,
            'total_page' => ceil($total / $pagesize),
        ]);
    }
}
