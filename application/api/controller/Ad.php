<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\CacheService;

/**
 * 广告接口
 */
class Ad extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 广告列表（按位置）
     *
     * @ApiMethod (GET)
     */
    public function index()
    {
        $position = $this->request->param('position', 'banner');
        $limit = min($this->request->param('limit/d', 5), 20);

        $allowPositions = ['banner', 'popup', 'inline'];
        if (!in_array($position, $allowPositions)) {
            $this->error(__('Invalid position'));
        }

        $list = CacheService::getBanner($position, $limit);

        $this->success('', $list);
    }
}
