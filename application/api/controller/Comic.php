<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Comic;

/**
 * 漫画接口
 */
class Comic extends Api
{
    // 无需登录的接口
    protected $noNeedLogin = ['index', 'detail'];
    // 无需鉴权的接口
    protected $noNeedRight = ['*'];

    /**
     * 漫画列表（分页）
     *
     * @ApiMethod (GET)
     * @ApiParams (name="page", type="integer", required=false, description="页码，默认1")
     * @ApiParams (name="pagesize", type="integer", required=false, description="每页条数，默认10")
     * @ApiParams (name="status", type="string", required=false, description="状态筛选: normal/hidden")
     */
    public function index()
    {
        $page = $this->request->param('page/d', 1);
        $pagesize = $this->request->param('pagesize/d', 10);
        $status = $this->request->param('status', 'normal');

        // 限制每页最大条数
        $pagesize = min($pagesize, 50);

        $where = [
            'deletetime' => null,
        ];
        if ($status) {
            $where['status'] = $status;
        }

        $list = Comic::where($where)
            ->order('weigh', 'desc')
            ->order('id', 'desc')
            ->page($page, $pagesize)
            ->select();

        $total = Comic::where($where)->count();

        $this->success('', [
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'pagesize'   => $pagesize,
            'total_page' => ceil($total / $pagesize),
        ]);
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

        $comic = Comic::where('id', $id)
            ->where('deletetime', null)
            ->where('status', 'normal')
            ->find();

        if (!$comic) {
            $this->error(__('Comic not found'));
        }

        $this->success('', $comic);
    }
}
