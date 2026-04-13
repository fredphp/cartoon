<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Ad;

/**
 * 广告接口
 */
class Ad extends Api
{
    // 所有接口无需登录
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口
    protected $noNeedRight = ['*'];

    /**
     * 广告列表（按位置）
     *
     * @ApiMethod (GET)
     * @ApiParams (name="position", type="string", required=false, description="广告位置: banner/popup/inline，默认banner")
     * @ApiParams (name="limit", type="integer", required=false, description="数量，默认5")
     */
    public function index()
    {
        $position = $this->request->param('position', 'banner');
        $limit = $this->request->param('limit/d', 5);
        $limit = min($limit, 20);

        // 校验位置值
        $allowPositions = ['banner', 'popup', 'inline'];
        if (!in_array($position, $allowPositions)) {
            $this->error(__('Invalid position'));
        }

        $list = Ad::where('status', 'normal')
            ->where('position', $position)
            ->where('deletetime', null)
            ->order('weigh', 'desc')
            ->order('id', 'desc')
            ->limit($limit)
            ->field('id,title,image,link,position,weigh')
            ->select();

        $this->success('', $list);
    }
}
