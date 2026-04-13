<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\ComicChapter;
use app\common\model\ComicChapterContent;
use app\common\model\ComicPurchase;

/**
 * 漫画章节接口
 */
class Chapter extends Api
{
    // 无需登录的接口
    protected $noNeedLogin = ['index'];
    // 无需鉴权的接口
    protected $noNeedRight = ['*'];

    /**
     * 章节列表（按漫画ID）
     *
     * @ApiMethod (GET)
     * @ApiParams (name="comic_id", type="integer", required=true, description="漫画ID")
     */
    public function index()
    {
        $comicId = $this->request->param('comic_id/d', 0);
        if (!$comicId) {
            $this->error(__('Invalid parameters'));
        }

        $list = ComicChapter::where('comic_id', $comicId)
            ->where('status', 'normal')
            ->where('deletetime', null)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->field('id,comic_id,title,price,is_free,sort,createtime')
            ->select();

        // 如果用户已登录，标记是否已购买
        $purchasedIds = [];
        if ($this->auth->isLogin()) {
            $purchasedIds = ComicPurchase::getPurchasedIds($this->auth->id, $comicId);
        }

        foreach ($list as $item) {
            $item->is_purchased = in_array($item->id, $purchasedIds);
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

        $chapter = ComicChapter::where('id', $id)
            ->where('status', 'normal')
            ->where('deletetime', null)
            ->find();

        if (!$chapter) {
            $this->error(__('Chapter not found'));
        }

        // 获取章节内容
        $content = ComicChapterContent::where('chapter_id', $id)->find();
        $images = $content ? $content->getAttr('images_arr') : [];

        // 判断是否可以查看全部内容
        $isFree = $chapter->is_free === '1';
        $isPurchased = false;

        if (!$isFree) {
            // 付费章节：检查是否已购买
            if ($this->auth->isLogin()) {
                $isPurchased = ComicPurchase::hasPurchased($this->auth->id, $id);
            }

            if (!$isPurchased) {
                // 未购买：只返回前2张图片
                $images = array_slice($images, 0, 2);
            }
        }

        $this->success('', [
            'id'           => $chapter->id,
            'comic_id'     => $chapter->comic_id,
            'title'        => $chapter->title,
            'price'        => $chapter->price,
            'is_free'      => $chapter->is_free,
            'is_purchased' => $isFree || $isPurchased,
            'sort'         => $chapter->sort,
            'createtime'   => $chapter->createtime,
            'images'       => $images,
            'total_images' => $content ? count($content->getAttr('images_arr')) : 0,
        ]);
    }
}
