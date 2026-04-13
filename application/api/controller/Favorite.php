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
    // 所有接口都需要登录
    protected $noNeedLogin = [];
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

        $comic = Comic::where('id', $comicId)
            ->where('deletetime', null)
            ->where('status', 'normal')
            ->find();
        if (!$comic) {
            $this->error(__('Comic not found'));
        }

        $userId = $this->auth->id;

        if (UserFavorite::hasFavorited($userId, $comicId)) {
            $this->error(__('Already favorited'));
        }

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
        $pagesize = min($this->request->param('pagesize/d', 10), 50);

        $userId = $this->auth->id;

        // 先查有效漫画ID，再关联收藏表，保证 total 与 list 一致
        $list = UserFavorite::alias('f')
            ->join('mha_comic c', 'f.comic_id = c.id', 'INNER')
            ->where('f.user_id', $userId)
            ->where('c.status', 'normal')
            ->where('c.deletetime', null)
            ->field('f.id,f.user_id,f.comic_id,f.createtime,c.title,c.cover,c.description,c.author_id')
            ->order('f.id', 'desc')
            ->page($page, $pagesize)
            ->select();

        $total = UserFavorite::alias('f')
            ->join('mha_comic c', 'f.comic_id = c.id', 'INNER')
            ->where('f.user_id', $userId)
            ->where('c.status', 'normal')
            ->where('c.deletetime', null)
            ->count();

        $this->success('', [
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'pagesize'   => $pagesize,
            'total_page' => ceil($total / $pagesize),
        ]);
    }
}
