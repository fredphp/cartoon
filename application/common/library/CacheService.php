<?php

namespace app\common\library;

use think\Cache;
use app\common\model\Ad;
use app\common\model\Comic;
use app\common\model\ComicCategory;
use app\common\model\ComicCategoryRelation;
use app\common\model\ComicChapter;
use app\common\model\ComicChapterContent;

/**
 * 缓存服务
 * 
 * 统一管理 API 缓存，支持：
 * 1. Tag 分组清除（后台修改时按标签清缓存）
 * 2. 互斥锁防缓存击穿（热点 key 过期时只有一个请求回源）
 * 3. TTL 抖动防缓存雪崩（±10% 随机偏移）
 * 4. 定时任务主动刷新（cron 提前续期，用户永远命中缓存）
 */
class CacheService
{
    // ========== TTL 配置（秒） ==========
    const TTL_BANNER    = 1800;  // 30分钟
    const TTL_CATEGORY  = 3600;  // 1小时
    const TTL_COMIC     = 1800;  // 30分钟
    const TTL_CHAPTER   = 900;   // 15分钟
    const TTL_RECOMMEND = 600;   // 10分钟
    const TTL_LATEST    = 1800;  // 30分钟

    // 互斥锁 TTL
    const LOCK_TTL = 5;

    // Tag 标签（分组清除）
    const TAG_BANNER    = 'api_banner';
    const TAG_CATEGORY  = 'api_category';
    const TAG_COMIC     = 'api_comic';
    const TAG_CHAPTER   = 'api_chapter';
    const TAG_RECOMMEND = 'api_recommend';
    const TAG_LATEST    = 'api_latest';

    /**
     * 带互斥锁的缓存读取（防击穿）
     * 
     * @param string   $key      缓存Key
     * @param int      $ttl      缓存TTL
     * @param callable $callback 回源函数
     * @param string   $tag      缓存标签
     * @param bool     $force    强制刷新（定时任务用，跳过缓存读取直接回源）
     * @return mixed
     */
    protected static function remember($key, $ttl, $callback, $tag = null, $force = false)
    {
        // 非强制模式：先读缓存
        if (!$force) {
            $data = cache($key);
            if ($data !== false && $data !== null) {
                return $data;
            }
        }

        // 缓存未命中 或 强制刷新 → 获取互斥锁
        $lockKey = $key . ':lock';
        if (cache($lockKey)) {
            // 其他进程正在回源，短暂等待后重试
            usleep(200000); // 200ms
            $data = cache($key);
            return ($data !== false && $data !== null) ? $data : [];
        }

        // 加锁
        cache($lockKey, 1, self::LOCK_TTL);

        try {
            $data = $callback();
            if ($data === null || $data === false) {
                $data = [];
            }
            // TTL 抖动 ±10%，防雪崩（强制刷新时不抖动，保持精确TTL）
            if ($force) {
                $actualTtl = $ttl;
            } else {
                $jitter = intval($ttl * 0.1);
                $actualTtl = $ttl + mt_rand(-$jitter, $jitter);
            }

            if ($tag) {
                cache($key, $data, $actualTtl, $tag);
            } else {
                cache($key, $data, $actualTtl);
            }
        } catch (\Exception $e) {
            $data = [];
        } finally {
            cache($lockKey, null);
        }

        return $data;
    }

    // ================================================================
    //  Banner 广告
    // ================================================================

    /**
     * 获取 Banner 广告列表
     */
    public static function getBanner($position = 'banner', $limit = 5, $force = false)
    {
        $key = 'mha:api:banner:' . $position . ':' . $limit;
        return self::remember($key, self::TTL_BANNER, function () use ($position, $limit) {
            return Ad::where('status', 'normal')
                ->where('position', $position)
                ->where('deletetime', null)
                ->order('weigh', 'desc')
                ->order('id', 'desc')
                ->limit($limit)
                ->field('id,title,image,link,position')
                ->select()
                ->toArray();
        }, self::TAG_BANNER, $force);
    }

    /**
     * 清除 Banner 缓存
     */
    public static function clearBanner()
    {
        Cache::clear(self::TAG_BANNER);
    }

    // ================================================================
    //  分类
    // ================================================================

    /**
     * 获取分类列表
     */
    public static function getCategoryList($force = false)
    {
        $key = 'mha:api:category:list';
        return self::remember($key, self::TTL_CATEGORY, function () {
            return ComicCategory::where('status', 'normal')
                ->where('deletetime', null)
                ->order('weigh', 'desc')
                ->order('id', 'asc')
                ->field('id,name,image,weigh')
                ->select()
                ->toArray();
        }, self::TAG_CATEGORY, $force);
    }

    /**
     * 获取分类下漫画（分页）
     */
    public static function getCategoryComics($categoryId, $page, $pagesize, $force = false)
    {
        $key = 'mha:api:category:' . $categoryId . ':comics:' . $page . ':' . $pagesize;
        return self::remember($key, self::TTL_CATEGORY, function () use ($categoryId, $page, $pagesize) {
            $comicIds = ComicCategoryRelation::where('category_id', $categoryId)
                ->column('comic_id');

            if (empty($comicIds)) {
                return ['list' => [], 'total' => 0, 'total_page' => 0];
            }

            $list = Comic::where('id', 'in', $comicIds)
                ->where('status', 'normal')
                ->where('deletetime', null)
                ->field('id,title,cover,description,author_id,status,createtime')
                ->order('weigh', 'desc')
                ->order('id', 'desc')
                ->page($page, $pagesize)
                ->select()
                ->toArray();

            $total = Comic::where('id', 'in', $comicIds)
                ->where('status', 'normal')
                ->where('deletetime', null)
                ->count();

            return [
                'list'       => $list,
                'total'      => $total,
                'total_page' => ceil($total / $pagesize),
            ];
        }, self::TAG_CATEGORY, $force);
    }

    /**
     * 清除分类缓存
     */
    public static function clearCategory()
    {
        Cache::clear(self::TAG_CATEGORY);
    }

    // ================================================================
    //  漫画
    // ================================================================

    /**
     * 获取漫画列表（分页）
     */
    public static function getComicList($page, $pagesize, $status = 'normal', $force = false)
    {
        $key = 'mha:api:comic:list:' . $status . ':' . $page . ':' . $pagesize;
        return self::remember($key, self::TTL_COMIC, function () use ($page, $pagesize, $status) {
            $where = ['deletetime' => null];
            if ($status) {
                $where['status'] = $status;
            }

            $list = Comic::where($where)
                ->order('weigh', 'desc')
                ->order('id', 'desc')
                ->page($page, $pagesize)
                ->select()
                ->toArray();

            $total = Comic::where($where)->count();

            return [
                'list'       => $list,
                'total'      => $total,
                'page'       => $page,
                'pagesize'   => $pagesize,
                'total_page' => ceil($total / $pagesize),
            ];
        }, self::TAG_COMIC, $force);
    }

    /**
     * 获取漫画详情
     */
    public static function getComicDetail($id, $force = false)
    {
        $key = 'mha:api:comic:detail:' . $id;
        return self::remember($key, self::TTL_COMIC, function () use ($id) {
            $comic = Comic::where('id', $id)
                ->where('deletetime', null)
                ->where('status', 'normal')
                ->find();
            if (!$comic) {
                return [];
            }
            $data = $comic->toArray();
            // 追加分类信息
            $data['category_ids'] = ComicCategoryRelation::getCategoryIdsByComicId($id);
            // 追加章节统计
            $data['chapter_count'] = ComicChapter::where('comic_id', $id)
                ->where('status', 'normal')
                ->where('deletetime', null)
                ->count();
            return $data;
        }, self::TAG_COMIC, $force);
    }

    /**
     * 清除漫画缓存
     */
    public static function clearComic($id = null)
    {
        Cache::clear(self::TAG_COMIC);
        // 漫画变更同时影响推荐和最新
        Cache::clear(self::TAG_RECOMMEND);
        Cache::clear(self::TAG_LATEST);
        // 漫画变更影响分类下的漫画
        Cache::clear(self::TAG_CATEGORY);
    }

    // ================================================================
    //  章节
    // ================================================================

    /**
     * 获取章节列表
     */
    public static function getChapterList($comicId, $force = false)
    {
        $key = 'mha:api:chapter:list:' . $comicId;
        return self::remember($key, self::TTL_CHAPTER, function () use ($comicId) {
            return ComicChapter::where('comic_id', $comicId)
                ->where('status', 'normal')
                ->where('deletetime', null)
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->field('id,comic_id,title,price,is_free,sort,createtime')
                ->select()
                ->toArray();
        }, self::TAG_CHAPTER, $force);
    }

    /**
     * 获取章节详情（原始数据，不含付费墙逻辑）
     */
    public static function getChapterDetail($id, $force = false)
    {
        $key = 'mha:api:chapter:detail:' . $id;
        return self::remember($key, self::TTL_CHAPTER, function () use ($id) {
            $chapter = ComicChapter::where('id', $id)
                ->where('status', 'normal')
                ->where('deletetime', null)
                ->find();
            if (!$chapter) {
                return [];
            }

            $content = ComicChapterContent::where('chapter_id', $id)->find();
            $allImages = $content ? $content->getAttr('images_arr') : [];
            $totalImages = count($allImages);

            return [
                'id'           => $chapter->id,
                'comic_id'     => $chapter->comic_id,
                'title'        => $chapter->title,
                'price'        => $chapter->price,
                'is_free'      => $chapter->is_free,
                'sort'         => $chapter->sort,
                'createtime'   => $chapter->createtime,
                'all_images'   => $allImages,
                'total_images' => $totalImages,
            ];
        }, self::TAG_CHAPTER, $force);
    }

    /**
     * 清除章节缓存
     */
    public static function clearChapter($comicId = null, $chapterId = null)
    {
        Cache::clear(self::TAG_CHAPTER);
        // 章节变更影响漫画详情中的 chapter_count
        if ($comicId) {
            cache('mha:api:comic:detail:' . $comicId, null);
        }
    }

    // ================================================================
    //  推荐漫画
    // ================================================================

    /**
     * 获取推荐漫画 ID 池
     */
    public static function getRecommendIds($force = false)
    {
        $key = 'mha:api:recommend:ids';
        return self::remember($key, self::TTL_RECOMMEND, function () {
            return Comic::where('status', 'normal')
                ->where('deletetime', null)
                ->column('id');
        }, self::TAG_RECOMMEND, $force);
    }

    /**
     * 获取推荐漫画列表（从ID池随机取子集）
     */
    public static function getRecommendList($limit)
    {
        $ids = self::getRecommendIds();
        if (empty($ids)) {
            return [];
        }

        $count = count($ids);
        $limit = min($limit, $count);

        // 随机取 $limit 个ID
        $randomKeys = array_rand($ids, $limit);
        if (!is_array($randomKeys)) {
            $randomKeys = [$randomKeys];
        }
        $selectedIds = array_map(function ($k) use ($ids) {
            return $ids[$k];
        }, $randomKeys);

        // 查询漫画数据（小量查询不缓存，直接读库）
        $list = Comic::where('id', 'in', $selectedIds)
            ->where('status', 'normal')
            ->where('deletetime', null)
            ->field('id,title,cover,description,author_id,status,createtime')
            ->select();

        // 打乱顺序
        if (count($list) > 0) {
            $list = collection($list)->shuffle()->toArray();
        }

        return $list;
    }

    /**
     * 清除推荐缓存
     */
    public static function clearRecommend()
    {
        Cache::clear(self::TAG_RECOMMEND);
    }

    // ================================================================
    //  最新漫画
    // ================================================================

    /**
     * 获取最新漫画列表
     */
    public static function getLatestList($limit, $force = false)
    {
        $key = 'mha:api:latest:' . $limit;
        return self::remember($key, self::TTL_LATEST, function () use ($limit) {
            return Comic::where('status', 'normal')
                ->where('deletetime', null)
                ->order('createtime', 'desc')
                ->order('id', 'desc')
                ->limit($limit)
                ->field('id,title,cover,description,author_id,status,createtime')
                ->select()
                ->toArray();
        }, self::TAG_LATEST, $force);
    }

    /**
     * 清除最新漫画缓存
     */
    public static function clearLatest()
    {
        Cache::clear(self::TAG_LATEST);
    }

    // ================================================================
    //  定时任务：主动刷新所有热点缓存
    // ================================================================

    /**
     * 刷新所有热点缓存（由 cron 调用）
     * 
     * 核心思路：cron 每5分钟执行一次，远小于最短TTL(10分钟)
     * 每次刷新都强制回源（force=true），保证：
     * 1. 用户请求永远命中缓存（不会击穿到DB）
     * 2. 数据最多延迟5分钟（cron间隔）
     * 3. 互斥锁保护刷新过程（防并发击穿）
     *
     * @return array 刷新结果
     */
    public static function refreshAll()
    {
        $results = [];

        // 1. Banner（所有位置）
        try {
            foreach (['banner', 'popup', 'inline'] as $position) {
                self::getBanner($position, 10, true);  // force=true 强制回源
            }
            $results['banner'] = 'ok';
        } catch (\Exception $e) {
            $results['banner'] = 'error: ' . $e->getMessage();
        }

        // 2. 分类列表
        try {
            self::getCategoryList(true);
            $results['category'] = 'ok';
        } catch (\Exception $e) {
            $results['category'] = 'error: ' . $e->getMessage();
        }

        // 3. 推荐 ID 池
        try {
            self::getRecommendIds(true);
            $results['recommend'] = 'ok';
        } catch (\Exception $e) {
            $results['recommend'] = 'error: ' . $e->getMessage();
        }

        // 4. 最新漫画
        try {
            self::getLatestList(30, true);
            $results['latest'] = 'ok';
        } catch (\Exception $e) {
            $results['latest'] = 'error: ' . $e->getMessage();
        }

        // 5. 漫画列表前3页
        try {
            for ($page = 1; $page <= 3; $page++) {
                self::getComicList($page, 10, 'normal', true);
            }
            $results['comic_list'] = 'ok';
        } catch (\Exception $e) {
            $results['comic_list'] = 'error: ' . $e->getMessage();
        }

        return $results;
    }
}
