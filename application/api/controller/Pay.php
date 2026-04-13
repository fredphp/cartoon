<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\ComicPurchase;
use app\common\model\ComicChapter;
use addons\epay\library\Service;

/**
 * 章节付费接口
 */
class Pay extends Api
{
    // 所有接口都需要登录
    protected $noNeedLogin = [];
    // 无需鉴权的接口
    protected $noNeedRight = ['*'];

    /**
     * 购买章节
     *
     * @ApiMethod (POST)
     * @ApiParams (name="chapter_id", type="integer", required=true, description="章节ID")
     * @ApiParams (name="pay_type", type="string", required=false, description="支付方式: wechat/alipay，仅真实支付时需要")
     */
    public function purchase()
    {
        $chapterId = $this->request->param('chapter_id/d', 0);
        if (!$chapterId) {
            $this->error(__('Invalid parameters'));
        }

        $userId = $this->auth->id;

        // 验证章节是否存在
        $chapter = ComicChapter::where('id', $chapterId)
            ->where('status', 'normal')
            ->where('deletetime', null)
            ->find();
        if (!$chapter) {
            $this->error(__('Chapter not found'));
        }

        // 免费章节无需购买
        if ($chapter->is_free === '1' || $chapter->price <= 0) {
            $this->error(__('This chapter is free, no need to purchase'));
        }

        // 检查是否已购买
        if (ComicPurchase::hasPurchased($userId, $chapterId)) {
            $this->error(__('Already purchased'));
        }

        $price = $chapter->price;
        $comicId = $chapter->comic_id;

        // 检测 epay 插件是否已配置
        $epayConfigured = $this->isEpayConfigured();

        if ($epayConfigured) {
            // ========== 真实支付流程 ==========
            $payType = $this->request->param('pay_type', 'wechat');
            if (!in_array($payType, ['wechat', 'alipay'])) {
                $payType = 'wechat';
            }

            // 生成唯一订单号
            $outTradeNo = 'CP' . date('YmdHis') . mt_rand(100000, 999999);

            // 订单标题
            $title = $chapter->title;

            // 回调通知地址
            $notifyUrl = $this->request->domain() . '/api/pay/notify';
            $returnUrl = $this->request->domain() . '/api/pay/returnx';

            // 将业务参数存入缓存，回调时取回
            $orderData = [
                'user_id'      => $userId,
                'chapter_id'   => $chapterId,
                'comic_id'     => $comicId,
                'price'        => $price,
                'out_trade_no' => $outTradeNo,
            ];
            cache('pay_order_' . $outTradeNo, $orderData, 3600);

            // 调用 epay 提交订单
            $params = [
                'type'         => $payType,
                'out_trade_no' => $outTradeNo,
                'title'        => $title,
                'amount'       => $price,
                'method'       => 'web',
                'notifyurl'    => $notifyUrl,
                'returnurl'    => $returnUrl,
            ];

            $result = Service::submitOrder($params);

            $this->success(__('Order created'), [
                'out_trade_no' => $outTradeNo,
                'pay_type'     => $payType,
                'price'        => $price,
                'pay_data'     => $result,
            ]);
        } else {
            // ========== 模拟购买（epay 未配置时） ==========
            ComicPurchase::create([
                'user_id'    => $userId,
                'chapter_id' => $chapterId,
                'comic_id'   => $comicId,
                'price'      => $price,
            ]);

            $this->success(__('Purchase successful (simulated)'), [
                'chapter_id' => $chapterId,
                'price'      => $price,
                'mode'       => 'simulated',
            ]);
        }
    }

    /**
     * 查询是否已购买
     *
     * @ApiMethod (GET)
     * @ApiParams (name="chapter_id", type="integer", required=true, description="章节ID")
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
     * 支付回调通知（epay 异步通知）
     * 此方法无需登录
     */
    public function notify()
    {
        $paytype = $this->request->param('paytype', 'wechat');
        $pay = Service::checkNotify($paytype);

        if (!$pay) {
            return json(['code' => 'FAIL', 'message' => '失败'], 500);
        }

        // 获取回调数据
        $data = Service::isVersionV3() ? $pay->callback() : $pay->verify();

        try {
            // 微信V3回调格式不同
            if (Service::isVersionV3() && $paytype === 'wechat') {
                $data = $data['resource']['ciphertext'];
            }

            $outTradeNo = $data['out_trade_no'] ?? '';

            // 从缓存取回订单数据
            $orderData = cache('pay_order_' . $outTradeNo);
            if (!$orderData) {
                \think\Log::record("支付回调：订单缓存不存在 {$outTradeNo}", "error");
            } else {
                $userId = $orderData['user_id'];
                $chapterId = $orderData['chapter_id'];
                $comicId = $orderData['comic_id'];
                $price = $orderData['price'];

                // 创建购买记录（防重复）
                if (!ComicPurchase::hasPurchased($userId, $chapterId)) {
                    ComicPurchase::create([
                        'user_id'    => $userId,
                        'chapter_id' => $chapterId,
                        'comic_id'   => $comicId,
                        'price'      => $price,
                    ]);
                }

                // 清除缓存
                cache('pay_order_' . $outTradeNo, null);

                \think\Log::record("支付回调成功：用户{$userId}购买章节{$chapterId}，金额{$price}");
            }
        } catch (\Exception $e) {
            \think\Log::record("支付回调逻辑错误:" . $e->getMessage(), "error");
        }

        // 必须执行，告知支付平台已收到通知
        if (Service::isVersionV3()) {
            return $pay->success()->getBody()->getContents();
        } else {
            return $pay->success()->send();
        }
    }

    /**
     * 支付成功跳转（同步返回）
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
     * 检测 epay 是否已配置（微信或支付宝任一配置完整即视为可用）
     *
     * @return bool
     */
    private function isEpayConfigured()
    {
        try {
            $config = get_addon_config('epay');
            if (!$config) {
                return false;
            }

            // 检查微信配置
            $wechat = $config['wechat'] ?? [];
            $wechatConfigured = !empty($wechat['appid']) && !empty($wechat['mch_id']) && !empty($wechat['key']);

            // 检查支付宝配置
            $alipay = $config['alipay'] ?? [];
            $alipayConfigured = !empty($alipay['app_id']) && !empty($alipay['private_key']);

            return $wechatConfigured || $alipayConfigured;
        } catch (\Exception $e) {
            return false;
        }
    }
}
