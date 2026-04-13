<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\CacheService;

/**
 * 漫画接口
 */
class Comic extends Api
{
    protected $noNeedLogin = ['index', 'detail'];
    protected $noNeedRight = ['*'];

    /**
     * 漫画列表（分页）
     *
     * @ApiMethod (GET)
     */
    public function index()
    {
        $page = $this->request->param('page/d', 1);
        $pagesize = min($this->request->param('pagesize/d', 10), 50);
        $status = $this->request->param('status', 'normal');

        $data = CacheService::getComicList($page, $pagesize, $status);

        $this->success('', $data);
    }

    /**
     * 漫画详情
     *
     * @ApiMethod (GET)
     * @ApiParams (name="id", type="integer", required=true, description="漫画ID")
     */
    public function detail()
    {
        $id = $this->request->param('id/d', 0);
        if (!$id) {
            $this->error(__('Invalid parameters'));
        }

        $comic = CacheService::getComicDetail($id);
        if (empty($comic)) {
            $this->error(__('Comic not found'));
        }

        $this->success('', $comic);
    }
}
