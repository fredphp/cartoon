<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\ComicPurchase;
use app\common\model\ComicChapter;
use app\common\library\CacheService;
use addons\epay\library\Service;

/**
 * 章节付费接口
 */
class Pay extends Api
{
    // notify/returnx 是支付平台回调，无法携带 Token
    protected $noNeedLogin = ['notify', 'returnx'];
    protected $noNeedRight = ['*'];

    /**
     * 购买章节
     *
     * @ApiMethod (POST)
     */
    public function purchase()
    {
        $chapterId = $this->request->param('chapter_id/d', 0);
        if (!$chapterId) {
            $this->error(__('Invalid parameters'));
        }

        $userId = $this->auth->id;

        // 验证章节
        $chapter = ComicChapter::where('id', $chapterId)
            ->where('status', 'normal')
            ->where('deletetime', null)
            ->find();
        if (!$chapter) {
            $this->error(__('Chapter not found'));
        }

        if ($chapter->is_free === '1' || $chapter->price <= 0) {
            $this->error(__('This chapter is free, no need to purchase'));
        }

        if (ComicPurchase::hasPurchased($userId, $chapterId)) {
            $this->error(__('Already purchased'));
        }

        $price = $chapter->price;
        $comicId = $chapter->comic_id;
        $epayConfigured = $this->isEpayConfigured();

        if ($epayConfigured) {
            // ========== 真实支付 ==========
            $payType = $this->request->param('pay_type', 'wechat');
            if (!in_array($payType, ['wechat', 'alipay'])) {
                $payType = 'wechat';
            }

            $outTradeNo = 'CP' . date('YmdHis') . mt_rand(100000, 999999);
            $notifyUrl = $this->request->domain() . '/api/pay/notify';
            $returnUrl = $this->request->domain() . '/api/pay/returnx';

            $orderData = [
                'user_id'      => $userId,
                'chapter_id'   => $chapterId,
                'comic_id'     => $comicId,
                'price'        => $price,
                'out_trade_no' => $outTradeNo,
            ];
            cache('pay_order_' . $outTradeNo, $orderData, 3600);

            $result = Service::submitOrder([
                'type'         => $payType,
                'out_trade_no' => $outTradeNo,
                'title'        => $chapter->title,
                'amount'       => $price,
                'method'       => 'web',
                'notifyurl'    => $notifyUrl,
                'returnurl'    => $returnUrl,
            ]);

            $this->success(__('Order created'), [
                'out_trade_no' => $outTradeNo,
                'pay_type'     => $payType,
                'price'        => $price,
                'pay_data'     => $result,
            ]);
        } else {
            // ========== 模拟购买 ==========
            ComicPurchase::create([
                'user_id'    => $userId,
                'chapter_id' => $chapterId,
                'comic_id'   => $comicId,
                'price'      => $price,
            ]);

            // 清除章节缓存（购买状态变了）
            CacheService::clearChapter($comicId, $chapterId);

            $this->success(__('Purchase successful (simulated)'), [
                'chapter_id' => $chapterId,
                'price'      => $price,
                'mode'       => 'simulated',
            ]);
        }
    }

    /**
     * 查询是否已购买
     */
    public function check()
    {
        $chapterId = $this->request->param('chapter_id/d', 0);
        if (!$chapterId) {
            $this->error(__('Invalid parameters'));
        }

        $userId = $this->auth->id;
        $purchased = ComicPurchase::hasPurchased($userId, $chapterId);

        $this->success('', [
            'chapter_id'   => $chapterId,
            'is_purchased' => $purchased,
        ]);
    }

    /**
     * 支付回调通知
     */
    public function notify()
    {
        $paytype = $this->request->param('paytype', 'wechat');
        $pay = Service::checkNotify($paytype);
        if (!$pay) {
            return json(['code' => 'FAIL', 'message' => '失败'], 500);
        }

        $data = Service::isVersionV3() ? $pay->callback() : $pay->verify();

        try {
            if (Service::isVersionV3() && $paytype === 'wechat') {
                $data = $data['resource']['ciphertext'];
            }

            $outTradeNo = $data['out_trade_no'] ?? '';
            $orderData = cache('pay_order_' . $outTradeNo);

            if (!$orderData) {
                \think\Log::record("支付回调：订单缓存不存在 {$outTradeNo}", "error");
            } else {
                $userId = $orderData['user_id'];
                $chapterId = $orderData['chapter_id'];
                $comicId = $orderData['comic_id'];
                $price = $orderData['price'];

                if (!ComicPurchase::hasPurchased($userId, $chapterId)) {
                    ComicPurchase::create([
                        'user_id'    => $userId,
                        'chapter_id' => $chapterId,
                        'comic_id'   => $comicId,
                        'price'      => $price,
                    ]);
                }

                cache('pay_order_' . $outTradeNo, null);

                // 清除章节缓存
                CacheService::clearChapter($comicId, $chapterId);

                \think\Log::record("支付回调成功：用户{$userId}购买章节{$chapterId}，金额{$price}");
            }
        } catch (\Exception $e) {
            \think\Log::record("支付回调逻辑错误:" . $e->getMessage(), "error");
        }

        if (Service::isVersionV3()) {
            return $pay->success()->getBody()->getContents();
        } else {
            return $pay->success()->send();
        }
    }

    /**
     * 支付成功跳转
     */
    public function returnx()
    {
        $paytype = $this->request->param('paytype', 'wechat');
        if (Service::checkReturn($paytype)) {
            echo '签名错误';
            return;
        }
        $this->success(__('Payment successful'));
    }

    /**
     * 检测 epay 是否已配置
     */
    private function isEpayConfigured()
    {
        try {
            $config = get_addon_config('epay');
            if (!$config) return false;

            $wechat = $config['wechat'] ?? [];
            $alipay = $config['alipay'] ?? [];

            $wechatOk = !empty($wechat['appid']) && !empty($wechat['mch_id']) && !empty($wechat['key']);
            $alipayOk = !empty($alipay['app_id']) && !empty($alipay['private_key']);

            return $wechatOk || $alipayOk;
        } catch (\Exception $e) {
            return false;
        }
    }
}
