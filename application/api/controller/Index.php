<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\CacheService;
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
        $bannerLimit = min($this->request->param('banner_limit/d', 5), 10);
        $recommendLimit = min($this->request->param('recommend_limit/d', 6), 20);
        $latestLimit = min($this->request->param('latest_limit/d', 10), 30);

        // Banner广告 - 走缓存
        $banner = CacheService::getBanner('banner', $bannerLimit);

        // 推荐漫画 - ID池缓存 + 随机取子集
        $recommend = CacheService::getRecommendList($recommendLimit);

        // 最新漫画 - 走缓存
        $latest = CacheService::getLatestList($latestLimit);

        $this->success('', [
            'banner'    => $banner,
            'recommend' => $recommend,
            'latest'    => $latest,
        ]);
    }
}
