<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\UserFollow;
use app\common\model\User;

/**
 * 关注接口
 */
class Follow extends Api
{
    // 所有接口都需要登录
    protected $noNeedLogin = [];
    // 无需鉴权的接口
    protected $noNeedRight = ['*'];

    /**
     * 关注作者
     *
     * @ApiMethod (POST)
     * @ApiParams (name="follow_user_id", type="integer", required=true, description="被关注的用户ID")
     */
    public function add()
    {
        $followUserId = $this->request->param('follow_user_id/d', 0);
        if (!$followUserId) {
            $this->error(__('Invalid parameters'));
        }

        $userId = $this->auth->id;

        // 不能关注自己
        if ($userId == $followUserId) {
            $this->error(__('Cannot follow yourself'));
        }

        // 验证被关注的用户是否存在
        $followUser = User::where('id', $followUserId)->find();
        if (!$followUser) {
            $this->error(__('User not found'));
        }

        // 检查是否已关注
        if (UserFollow::hasFollowed($userId, $followUserId)) {
            $this->error(__('Already followed'));
        }

        // 创建关注记录
        UserFollow::create([
            'user_id'         => $userId,
            'follow_user_id'  => $followUserId,
        ]);

        $this->success(__('Followed successfully'), [
            'following_count' => UserFollow::getFollowingCount($userId),
            'follower_count'  => UserFollow::getFollowerCount($followUserId),
        ]);
    }

    /**
     * 取消关注
     *
     * @ApiMethod (POST)
     * @ApiParams (name="follow_user_id", type="integer", required=true, description="被取消关注的用户ID")
     */
    public function remove()
    {
        $followUserId = $this->request->param('follow_user_id/d', 0);
        if (!$followUserId) {
            $this->error(__('Invalid parameters'));
        }

        $userId = $this->auth->id;

        $follow = UserFollow::where('user_id', $userId)
            ->where('follow_user_id', $followUserId)
            ->find();

        if (!$follow) {
            $this->error(__('Not followed'));
        }

        $follow->delete();

        $this->success(__('Unfollowed successfully'), [
            'following_count' => UserFollow::getFollowingCount($userId),
            'follower_count'  => UserFollow::getFollowerCount($followUserId),
        ]);
    }

    /**
     * 我的关注列表（分页，含用户信息）
     *
     * @ApiMethod (GET)
     * @ApiParams (name="page", type="integer", required=false, description="页码，默认1")
     * @ApiParams (name="pagesize", type="integer", required=false, description="每页条数，默认10")
     */
    public function index()
    {
        $page = $this->request->param('page/d', 1);
        $pagesize = $this->request->param('pagesize/d', 10);

        // 限制每页最大条数
        $pagesize = min($pagesize, 50);

        $userId = $this->auth->id;

        $list = UserFollow::where('user_id', $userId)
            ->with(['followUser' => function ($query) {
                $query->field('id,username,nickname,avatar');
            }])
            ->order('id', 'desc')
            ->page($page, $pagesize)
            ->select();

        // 过滤掉用户已被删除的关注项
        $list = $list->filter(function ($item) {
            return $item->followUser !== null;
        });

        $total = UserFollow::where('user_id', $userId)->count();

        $this->success('', [
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'pagesize'   => $pagesize,
            'total_page' => ceil($total / $pagesize),
        ]);
    }
}
