<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\CacheService;
use app\common\model\ComicPurchase;

/**
 * 漫画章节接口
 */
class Chapter extends Api
{
    // index 和 detail 都无需登录（detail 内部判断登录状态做付费墙）
    protected $noNeedLogin = ['index', 'detail'];
    protected $noNeedRight = ['*'];

    /**
     * 章节列表（按漫画ID）
     *
     * @ApiMethod (GET)
     */
    public function index()
    {
        $comicId = $this->request->param('comic_id/d', 0);
        if (!$comicId) {
            $this->error(__('Invalid parameters'));
        }

        $list = CacheService::getChapterList($comicId);

        // 如果用户已登录，标记是否已购买
        if ($this->auth->isLogin()) {
            $purchasedIds = ComicPurchase::getPurchasedIds($this->auth->id, $comicId);
            foreach ($list as &$item) {
                $item['is_purchased'] = in_array($item['id'], $purchasedIds);
            }
            unset($item);
        } else {
            foreach ($list as &$item) {
                $item['is_purchased'] = false;
            }
            unset($item);
        }

        $this->success('', $list);
    }

    /**
     * 章节详情
     *
     * @ApiMethod (GET)
     * @ApiParams (name="id", type="integer", required=true, description="章节ID")
     */
    public function detail()
    {
        $id = $this->request->param('id/d', 0);
        if (!$id) {
            $this->error(__('Invalid parameters'));
        }

        $chapter = CacheService::getChapterDetail($id);
        if (empty($chapter)) {
            $this->error(__('Chapter not found'));
        }

        $isFree = $chapter['is_free'] === '1';
        $isPurchased = false;
        $images = $chapter['all_images'];

        if (!$isFree) {
            // 付费章节：检查购买状态
            if ($this->auth->isLogin()) {
                $isPurchased = ComicPurchase::hasPurchased($this->auth->id, $id);
            }
            if (!$isPurchased) {
                // 未购买：只返回前2张
                $images = array_slice($images, 0, 2);
            }
        }

        $this->success('', [
            'id'           => $chapter['id'],
            'comic_id'     => $chapter['comic_id'],
            'title'        => $chapter['title'],
            'price'        => $chapter['price'],
            'is_free'      => $chapter['is_free'],
            'is_purchased' => $isFree || $isPurchased,
            'sort'         => $chapter['sort'],
            'createtime'   => $chapter['createtime'],
            'images'       => $images,
            'total_images' => $chapter['total_images'],
        ]);
    }
}
