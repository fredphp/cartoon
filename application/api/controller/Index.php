<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Ad;
use app\common\model\Comic;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页数据
     *
     * @ApiMethod (GET)
     * @ApiParams (name="banner_limit", type="integer", required=false, description="Banner数量，默认5")
     * @ApiParams (name="recommend_limit", type="integer", required=false, description="推荐漫画数量，默认6")
     * @ApiParams (name="latest_limit", type="integer", required=false, description="最新漫画数量，默认10")
     */
    public function index()
    {
        $bannerLimit = $this->request->param('banner_limit/d', 5);
        $recommendLimit = $this->request->param('recommend_limit/d', 6);
        $latestLimit = $this->request->param('latest_limit/d', 10);

        // 限制最大数量
        $bannerLimit = min($bannerLimit, 10);
        $recommendLimit = min($recommendLimit, 20);
        $latestLimit = min($latestLimit, 30);

        // Banner广告 - 按权重排序
        $banner = Ad::where('status', 'normal')
            ->where('type', 'banner')
            ->where('deletetime', null)
            ->order('weigh', 'desc')
            ->order('id', 'desc')
            ->limit($bannerLimit)
            ->select();

        // 推荐漫画 - 随机推荐
        $recommendTotal = Comic::where('status', 'normal')
            ->where('deletetime', null)
            ->count();
        $recommend = [];
        if ($recommendTotal > 0) {
            // 随机偏移，取一页数据
            $recommendOffset = $recommendTotal > $recommendLimit
                ? mt_rand(0, $recommendTotal - $recommendLimit)
                : 0;
            $recommend = Comic::where('status', 'normal')
                ->where('deletetime', null)
                ->order('id', 'asc')
                ->limit($recommendOffset, $recommendLimit)
                ->select();
            // 打乱顺序
            if (count($recommend) > 0) {
                $recommend = collection($recommend)->shuffle();
            }
        }

        // 最新漫画 - 按创建时间倒序
        $latest = Comic::where('status', 'normal')
            ->where('deletetime', null)
            ->order('createtime', 'desc')
            ->order('id', 'desc')
            ->limit($latestLimit)
            ->select();

        $this->success('', [
            'banner'   => $banner,
            'recommend' => $recommend,
            'latest'   => $latest,
        ]);
    }
}
