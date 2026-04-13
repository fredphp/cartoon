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

        if ($userId == $followUserId) {
            $this->error(__('Cannot follow yourself'));
        }

        $followUser = User::where('id', $followUserId)->find();
        if (!$followUser) {
            $this->error(__('User not found'));
        }

        if (UserFollow::hasFollowed($userId, $followUserId)) {
            $this->error(__('Already followed'));
        }

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
        $pagesize = min($this->request->param('pagesize/d', 10), 50);

        $userId = $this->auth->id;

        // 先查有效用户，再关联关注表，保证 total 与 list 一致
        $list = UserFollow::alias('f')
            ->join('mha_user u', 'f.follow_user_id = u.id', 'INNER')
            ->where('f.user_id', $userId)
            ->field('f.id,f.user_id,f.follow_user_id,f.createtime,u.username,u.nickname,u.avatar')
            ->order('f.id', 'desc')
            ->page($page, $pagesize)
            ->select();

        $total = UserFollow::alias('f')
            ->join('mha_user u', 'f.follow_user_id = u.id', 'INNER')
            ->where('f.user_id', $userId)
            ->count();

        $this->success('', [
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'pagesize'   => $pagesize,
            'total_page' => ceil($total / $pagesize),
        ]);
    }
}
