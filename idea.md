# 漫画平台 — 逻辑构思图 & 问题检查 & 缓存方案

---

## 一、数据库表前缀问题 ⚠️ 严重

### 问题描述

`.env` 中 `prefix = fa_`，但实际数据库表全为 `mha_` 前缀。

当前 Model 中表名声明方式不一致：

| Model | 声明方式 | 实际解析的表名 | 是否正确 |
|-------|---------|--------------|---------|
| Comic | `$name = 'comic'` | `fa_comic` | ❌ 表不存在 |
| ComicChapter | `$name = 'comic_chapter'` | `fa_comic_chapter` | ❌ |
| ComicChapterContent | `$name = 'comic_chapter_content'` | `fa_comic_chapter_content` | ❌ |
| ComicPurchase | `$table = 'mha_comic_purchase'` | `mha_comic_purchase` | ✅ |
| ComicCategory | `$name = 'comic_category'` | `fa_comic_category` | ❌ |
| ComicCategoryRelation | `$table = 'comic_category_relation'` | `fa_comic_category_relation` | ❌ 缺少 mha_ |
| UserFavorite | `$name = 'user_favorite'` | `fa_user_favorite` | ❌ |
| UserFollow | `$name = 'user_follow'` | `fa_user_follow` | ❌ |
| Ad | `$name = 'ad'` | `fa_ad` | ❌ |

### 解决方案

**方案A（推荐）**：修改 `.env` 中 `prefix = mha_`，所有 Model 保持 `$name` 声明即可。

**方案B**：不修改 `.env`，所有 Model 统一使用 `$table = 'mha_xxx'` 显式声明。

> ⚠️ 方案A 需确保 FastAdmin 系统表（admin/auth/config 等）也使用了 `mha_` 前缀（从 SQL 导出看确实如此）。  
> ⚠️ `ComicCategoryRelation` 即使选方案A也需修复：`$table = 'comic_category_relation'` → 缺少前缀。

---

## 二、API 接口问题检查

### 2.1 Pay 控制器 — `notify` 未排除登录校验

```php
protected $noNeedLogin = [];
```

`notify()` 和 `returnx()` 是支付平台回调，无法携带用户 Token，必须排除登录：

```php
protected $noNeedLogin = ['notify', 'returnx'];
```

### 2.2 Chapter 控制器 — `detail` 未排除登录校验

```php
protected $noNeedLogin = ['index'];
```

`detail()` 允许未登录用户查看免费章节（只看前2张），应加入：

```php
protected $noNeedLogin = ['index', 'detail'];
```

### 2.3 Comic 控制器 — 缺少分类信息

`detail()` 返回的漫画详情缺少该漫画所属的分类列表，前端无法展示分类标签。

### 2.4 Comic 控制器 — 缺少章节统计

`detail()` 返回的漫画详情缺少总章数、最新章节等信息。

### 2.5 Favorite 控制器 — total 统计不准确

`index()` 中的 `$total` 统计了所有收藏（含漫画已删除的），但 `list` 过滤了已删除漫画，导致 `total > 实际列表数`，分页会出空页。

### 2.6 Follow 控制器 — 同上 total 问题

`index()` 中 `$total` 统计了所有关注（含被关注者已删除的），但 `list` 过滤了已删除用户。

### 2.7 Category 控制器 — `comics()` 方法 N+1 查询

先查 `comicIds`，再 `WHERE id IN (comicIds)` 两次查询，可以直接用 JOIN 优化。

### 2.8 Index 控制器 — 推荐漫画随机算法问题

当前用 `mt_rand + LIMIT offset,n` 实现随机，但：
- 每次请求的 `count()` 查询开销大
- offset 大时性能差
- 结果可能重复

### 2.9 Ad 控制器 — 同时查 `position` 和 `type` 冗余

表中有 `position` 和 `type` 两个字段，含义重叠。应统一用 `position`，废弃 `type`。

---

## 三、缓存优化方案

### 3.1 缓存基础设施

项目已安装 `faredis` 插件（Redis 管理工具），ThinkPHP 5.x 内置缓存驱动支持 Redis。

**缓存配置** (`application/extra/redis.php` 或 `config.php`):

```php
'cache' => [
    'type'   => 'redis',
    'host'   => '127.0.0.1',
    'port'   => 6379,
    'prefix' => 'mha:',
    'expire' => 3600,
],
```

如果 Redis 不可用，ThinkPHP 默认使用文件缓存，代码兼容。

### 3.2 缓存策略设计

| 接口 | 数据特征 | 缓存Key | TTL | 失效策略 |
|------|---------|---------|-----|---------|
| **首页 Banner** | 低频更新，高访问 | `api:banner:{position}` | 30min | 后台修改广告时清缓存 |
| **首页推荐** | 需随机感 | `api:recommend:ids` | 10min | 存ID列表，每次随机取 |
| **首页最新** | 新漫画上线才变 | `api:latest:{limit}` | 10min | 新增漫画时清缓存 |
| **分类列表** | 极低频更新 | `api:category:list` | 1h | 后台增删改分类时清缓存 |
| **分类下漫画** | 中频更新 | `api:category:{id}:comics:page{p}` | 15min | 后台修改漫画分类时清缓存 |
| **漫画详情** | 低频更新 | `api:comic:detail:{id}` | 30min | 后台编辑漫画时清缓存 |
| **漫画列表** | 分页查询 | `api:comic:list:page{p}` | 10min | 漫画状态变更时清缓存 |
| **章节列表** | 中频更新 | `api:chapter:list:{comic_id}` | 15min | 后台增删章节时清缓存 |
| **章节详情** | 低频更新 | `api:chapter:detail:{id}` | 30min | 后台编辑章节时清缓存 |

**不缓存**的接口（与用户状态强绑定）：
- 收藏（add/remove/index）— 每次结果不同
- 关注（add/remove/index）— 每次结果不同
- 购买（purchase/check）— 涉及支付，必须实时
- 支付回调（notify）— 必须实时

### 3.3 缓存实现示例

```php
// 首页 Banner 缓存
public function getBannerList($position, $limit)
{
    $key = 'api:banner:' . $position;
    $list = cache($key);
    if ($list === false) {
        $list = Ad::where('status', 'normal')
            ->where('position', $position)
            ->where('deletetime', null)
            ->order('weigh', 'desc')
            ->order('id', 'desc')
            ->limit($limit)
            ->field('id,title,image,link,position')
            ->select()
            ->toArray();
        cache($key, $list, 1800); // 30分钟
    }
    return $list;
}

// 分类列表缓存
public function getCategoryList()
{
    $key = 'api:category:list';
    $list = cache($key);
    if ($list === false) {
        $list = ComicCategory::where('status', 'normal')
            ->where('deletetime', null)
            ->order('weigh', 'desc')
            ->order('id', 'asc')
            ->field('id,name,image,weigh')
            ->select()
            ->toArray();
        cache($key, $list, 3600); // 1小时
    }
    return $list;
}

// 推荐漫画缓存（缓存ID列表，随机取子集）
public function getRecommendIds()
{
    $key = 'api:recommend:ids';
    $ids = cache($key);
    if ($ids === false) {
        $ids = Comic::where('status', 'normal')
            ->where('deletetime', null)
            ->column('id');
        cache($key, $ids, 600); // 10分钟
    }
    return $ids;
}
```

### 3.4 缓存清除策略

在 Model 层使用 ThinkPHP 的 `afterUpdate` / `afterDelete` / `afterInsert` 钩子自动清缓存：

```php
// Comic 模型中
protected static function init()
{
    self::afterInsert(function ($row) {
        cache('api:comic:list:*', null);
        cache('api:latest:*', null);
    });
    self::afterUpdate(function ($row) {
        cache('api:comic:detail:' . $row->id, null);
        cache('api:comic:list:*', null);
    });
    self::afterDelete(function ($row) {
        cache('api:comic:detail:' . $row->id, null);
    });
}
```

---

## 四、逻辑构思图

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          漫画平台 · 系统架构                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────┐     ┌──────────────┐     ┌──────────────┐           │
│  │  前端(H5/APP) │     │  后台管理     │     │  支付平台     │           │
│  │  API调用      │     │  FastAdmin    │     │  微信/支付宝  │           │
│  └──────┬───────┘     └──────┬───────┘     └──────┬───────┘           │
│         │                    │                    │                    │
│         ▼                    ▼                    ▼                    │
│  ┌──────────────────────────────────────────────────────────┐         │
│  │                    API 层 (application/api)               │         │
│  │                                                          │         │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐  │         │
│  │  │  Index   │ │  Comic   │ │  Chapter │ │ Category │  │         │
│  │  │  首页    │ │  漫画    │ │  章节    │ │  分类    │  │         │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘  │         │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐  │         │
│  │  │ Favorite │ │  Follow  │ │   Pay    │ │    Ad    │  │         │
│  │  │  收藏    │ │  关注    │ │  付费    │ │  广告    │  │         │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘  │         │
│  └───────────────────────┬──────────────────────────────────┘         │
│                          │                                             │
│                          ▼                                             │
│  ┌──────────────────────────────────────────────────────────┐         │
│  │                  Model 层 (application/common/model)      │         │
│  │                                                          │         │
│  │  ┌────────────┐ ┌────────────┐ ┌────────────────────┐  │         │
│  │  │   Comic    │ │ComicChapter│ │ComicChapterContent │  │         │
│  │  │   漫画     │ │   章节     │ │    章节内容         │  │         │
│  │  └─────┬──────┘ └─────┬──────┘ └──────────┬─────────┘  │         │
│  │        │              │                     │             │         │
│  │  ┌─────┴──────┐ ┌─────┴──────┐ ┌──────────┴─────────┐  │         │
│  │  │ComicCate-  │ │ComicCate-  │ │   ComicPurchase    │  │         │
│  │  │  gory 分类 │ │  goryRelation│ │     购买记录       │  │         │
│  │  └────────────┘ │  分类关联  │ └────────────────────┘  │         │
│  │                 └────────────┘                           │         │
│  │  ┌────────────┐ ┌────────────┐ ┌────────────────────┐  │         │
│  │  │UserFavorite│ │ UserFollow │ │       Ad 广告       │  │         │
│  │  │   收藏     │ │   关注     │ │                    │  │         │
│  │  └────────────┘ └────────────┘ └────────────────────┘  │         │
│  └───────────────────────┬──────────────────────────────────┘         │
│                          │                                             │
│                          ▼                                             │
│  ┌──────────────────────────────────────────────────────────┐         │
│  │                    缓存层 (Redis/File)                    │         │
│  │                                                          │         │
│  │  api:banner:*     首页Banner      TTL: 30min            │         │
│  │  api:category:*   分类列表        TTL: 60min            │         │
│  │  api:comic:*      漫画详情/列表   TTL: 30min            │         │
│  │  api:chapter:*    章节列表/详情   TTL: 15min            │         │
│  │  api:recommend:*  推荐ID池        TTL: 10min            │         │
│  └───────────────────────┬──────────────────────────────────┘         │
│                          │                                             │
│                          ▼                                             │
│  ┌──────────────────────────────────────────────────────────┐         │
│  │                 数据库 (MySQL · mha_ 前缀)                │         │
│  │                                                          │         │
│  │  ┌──────────┐ ┌──────────────┐ ┌────────────────────┐  │         │
│  │  │mha_comic │ │mha_comic_    │ │mha_comic_chapter_  │  │         │
│  │  │  漫画表   │ │chapter 章节  │ │  content 章节内容   │  │         │
│  │  └──────────┘ └──────────────┘ └────────────────────┘  │         │
│  │  ┌──────────┐ ┌──────────────┐ ┌────────────────────┐  │         │
│  │  │mha_comic_│ │mha_comic_    │ │  mha_comic_        │  │         │
│  │  │category  │ │category_     │ │  purchase 购买记录  │  │         │
│  │  │  分类表   │ │relation 关联 │ │                    │  │         │
│  │  └──────────┘ └──────────────┘ └────────────────────┘  │         │
│  │  ┌──────────┐ ┌──────────────┐ ┌────────────────────┐  │         │
│  │  │mha_user_ │ │mha_user_     │ │     mha_ad         │  │         │
│  │  │favorite  │ │  follow 关注 │ │     广告表          │  │         │
│  │  │  收藏表   │ │              │ │                    │  │         │
│  │  └──────────┘ └──────────────┘ └────────────────────┘  │         │
│  └──────────────────────────────────────────────────────────┘         │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 五、API 接口关系图

```
┌─────────────────────────────────────────────────────────────────┐
│                       前端请求 → API 路由                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  GET  /api/index/index          首页（banner+推荐+最新）         │
│       ├── Ad::where(position=banner)                            │
│       ├── Comic::count() + LIMIT offset,n  随机推荐             │
│       └── Comic::order(createtime desc)     最新                │
│                                                                 │
│  GET  /api/comic/index          漫画列表（分页）                 │
│  GET  /api/comic/detail         漫画详情                        │
│       └── Comic::find(id)                                      │
│                                                                 │
│  GET  /api/category/index       分类列表                        │
│  GET  /api/category/comics      分类下漫画（分页）               │
│       ├── ComicCategoryRelation::column(comic_id)               │
│       └── Comic::where(id IN ...)                              │
│                                                                 │
│  GET  /api/chapter/index        章节列表（需comic_id）           │
│       └── ComicPurchase::getPurchasedIds()  标记已购             │
│  GET  /api/chapter/detail       章节详情（付费墙逻辑）            │
│       ├── ComicChapterContent::images_arr                       │
│       └── ComicPurchase::hasPurchased()     付费判定             │
│                                                                 │
│  POST /api/favorite/add         收藏（需登录）                   │
│  POST /api/favorite/remove      取消收藏（需登录）               │
│  GET  /api/favorite/index       我的收藏（分页，需登录）          │
│       └── UserFavorite::with('comic')                           │
│                                                                 │
│  POST /api/follow/add           关注作者（需登录）               │
│  POST /api/follow/remove        取消关注（需登录）               │
│  GET  /api/follow/index         我的关注（分页，需登录）          │
│       └── UserFollow::with('followUser')                        │
│                                                                 │
│  POST /api/pay/purchase         购买章节（需登录）               │
│       ├── epay已配置 → Service::submitOrder() 真实支付           │
│       └── epay未配置 → ComicPurchase::create()  模拟购买         │
│  GET  /api/pay/check            查询是否购买（需登录）           │
│  POST /api/pay/notify           支付回调（无需登录）⚠️           │
│  GET  /api/pay/returnx          支付跳转（无需登录）⚠️           │
│                                                                 │
│  GET  /api/ad/index             广告列表（按position）           │
│       └── Ad::where(position=xxx)                               │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 六、付费逻辑流程图

```
用户点击阅读章节
       │
       ▼
  GET /api/chapter/detail?id=123
       │
       ▼
  查询 mha_comic_chapter
       │
       ├── is_free = '1' ──────────→ 返回全部图片 ✅
       │
       └── is_free = '0' (付费章节)
              │
              ├── 用户未登录 → 返回前2张 + 提示登录
              │
              ├── 用户已登录 → 查询 mha_comic_purchase
              │       │
              │       ├── 有记录 → 返回全部图片 ✅
              │       │
              │       └── 无记录 → 返回前2张 + total_images + price
              │                        │
              │                        ▼
              │               用户点击购买按钮
              │                        │
              │               POST /api/pay/purchase
              │                        │
              │               ├── epay已配置 → 创建支付订单
              │               │       │
              │               │       ├── 微信/支付宝支付
              │               │       │       │
              │               │       │   POST /api/pay/notify (异步回调)
              │               │       │       │
              │               │       │       └── 写入 purchase 记录 ✅
              │               │       │
              │               │       └── 返回支付链接/二维码
              │               │
              │               └── epay未配置 → 直接写入 purchase 记录 ✅ (模拟)
              │
              └── 再次请求 detail → 返回全部图片 ✅
```

---

## 七、问题修复清单

| # | 问题 | 严重程度 | 修复方案 |
|---|------|---------|---------|
| 1 | 表前缀不一致，9个Model中7个用`$name`会解析到`fa_`前缀 | 🔴 严重 | 统一改`.env`的prefix为`mha_`，或所有Model改用`$table` |
| 2 | ComicCategoryRelation的`$table`缺`mha_`前缀 | 🔴 严重 | `$table = 'comic_category_relation'` → `$table = 'mha_comic_category_relation'` |
| 3 | Pay控制器 `notify`/`returnx` 需排除登录 | 🟡 中等 | `$noNeedLogin = ['notify', 'returnx']` |
| 4 | Chapter控制器 `detail` 需排除登录 | 🟡 中等 | `$noNeedLogin = ['index', 'detail']` |
| 5 | Favorite index 的 total 与 list 不一致 | 🟡 中等 | 先过滤再统计，或用子查询 |
| 6 | Follow index 的 total 与 list 不一致 | 🟡 中等 | 同上 |
| 7 | Comic detail 缺少分类信息 | 🟢 低 | 追加 ComicCategoryRelation 查询 |
| 8 | Comic detail 缺少章节统计 | 🟢 低 | 追加 ComicChapter::count() |
| 9 | Ad 表 position/type 字段冗余 | 🟢 低 | 统一用 position，废弃 type |
| 10 | 首页推荐随机算法性能 | 🟢 低 | 缓存ID池 + 随机取子集 |
