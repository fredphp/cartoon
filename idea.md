# 漫画平台 — 逻辑构思图 & 缓存架构

---

## 一、缓存架构设计

### 核心思路：永不过期 + 定时刷新 + 后台即时清缓存

```
┌─────────────────────────────────────────────────────────────────┐
│                     缓存架构全景图                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐    每5分钟     ┌──────────────────────┐       │
│  │  Cron 定时   │ ──────────→  │  CacheService         │       │
│  │  php think   │   force=true │  .refreshAll()        │       │
│  │  cache:refresh│             │  强制回源刷新缓存       │       │
│  └─────────────┘              └──────────┬───────────┘       │
│                                          │                    │
│                                          ▼                    │
│  ┌─────────────┐    修改数据    ┌──────────────────────┐       │
│  │  后台管理    │ ──────────→  │  Model afterXxx 钩子  │       │
│  │  FastAdmin   │   清缓存     │  CacheService::clear() │       │
│  └─────────────┘              └──────────┬───────────┘       │
│                                          │                    │
│                                          ▼                    │
│                               ┌──────────────────────┐       │
│                               │   Redis / File 缓存   │       │
│                               │                      │       │
│                               │  api:banner:*        │ 30min │
│                               │  api:category:*      │ 60min │
│                               │  api:comic:*         │ 30min │
│                               │  api:chapter:*       │ 15min │
│                               │  api:recommend:*     │ 10min │
│                               │  api:latest:*        │ 30min │
│                               └──────────┬───────────┘       │
│                                          │                    │
│                                          ▼                    │
│                               ┌──────────────────────┐       │
│                               │   MySQL 数据库        │       │
│                               │   (mha_ 前缀)         │       │
│                               └──────────────────────┘       │
│                                                                 │
│  ┌─────────────┐    读取缓存    ┌──────────────────────┐       │
│  │  前端用户    │ ──────────→  │  API Controller       │       │
│  │  H5 / APP   │   命中率高    │  CacheService::getXxx()│       │
│  └─────────────┘              └──────────────────────┘       │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 防缓存击穿机制

```
用户请求 → cache($key) 命中？
              │
              ├── 是 → 返回缓存数据（0ms）
              │
              └── 否 → 获取互斥锁 cache($key:lock)
                          │
                          ├── 锁已被占 → 等200ms → 重试读缓存
                          │
                          └── 获得锁 → 回源查询DB → 写入缓存 → 释放锁
```

### 防缓存雪崩机制

- 每个缓存Key的TTL加 ±10% 随机抖动
- 如 TTL=1800s，实际TTL在 1620~1980s 之间随机
- 避免大量Key同时过期导致雪崩

### Cron 定时刷新（防击穿核心）

```bash
# crontab 配置，每5分钟执行
*/5 * * * * php /path/to/think cache:refresh >> /tmp/cache_refresh.log 2>&1
```

**为什么每5分钟？**
- 最短TTL是推荐ID池(10分钟)
- Cron间隔 < 最短TTL/2，保证在缓存过期前续期
- 用户请求永远命中缓存，不会击穿到DB

**force=true 机制：**
- `refreshAll()` 调用 `getXxx($force=true)`
- `remember()` 检测到 `$force=true` 时跳过缓存读取，直接回源
- 回源后覆盖写入缓存，用户无感知

### 后台修改即时清缓存

```
后台操作（增删改） → Model afterInsert/afterUpdate/afterDelete
                        │
                        ├── Ad 变更 → clearBanner()
                        ├── Comic 变更 → clearComic() + clearRecommend() + clearLatest() + clearCategory()
                        ├── ComicChapter 变更 → clearChapter()
                        ├── ComicChapterContent 变更 → clearChapter()
                        ├── ComicCategory 变更 → clearCategory()
                        └── ComicCategoryRelation 变更 → clearCategory() + 清指定漫画详情缓存
```

---

## 二、缓存Key与TTL清单

| 缓存Key | TTL | 标签 | Cron刷新 | 说明 |
|---------|-----|------|---------|------|
| `mha:api:banner:{position}:{limit}` | 30min | api_banner | ✅ | 广告按位置+数量缓存 |
| `mha:api:category:list` | 1h | api_category | ✅ | 分类列表 |
| `mha:api:category:{id}:comics:{page}:{pagesize}` | 1h | api_category | ❌ | 分类下漫画（按需缓存） |
| `mha:api:comic:list:{status}:{page}:{pagesize}` | 30min | api_comic | ✅前3页 | 漫画列表 |
| `mha:api:comic:detail:{id}` | 30min | api_comic | ❌ | 漫画详情（含分类+章节数） |
| `mha:api:chapter:list:{comicId}` | 15min | api_chapter | ❌ | 章节列表 |
| `mha:api:chapter:detail:{id}` | 15min | api_chapter | ❌ | 章节详情（含图片列表） |
| `mha:api:recommend:ids` | 10min | api_recommend | ✅ | 推荐ID池 |
| `mha:api:latest:{limit}` | 30min | api_latest | ✅ | 最新漫画 |

**不缓存的接口（与用户状态强绑定）：**
- 收藏（add/remove/index）
- 关注（add/remove/index）
- 购买（purchase/check）
- 支付回调（notify/returnx）

---

## 三、API 接口关系图

```
┌─────────────────────────────────────────────────────────────────┐
│                       前端请求 → API 路由                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  GET  /api/index/index          首页（banner+推荐+最新）         │
│       ├── CacheService::getBanner()         缓存30min          │
│       ├── CacheService::getRecommendList()  ID池缓存10min      │
│       └── CacheService::getLatestList()     缓存30min          │
│                                                                 │
│  GET  /api/comic/index          漫画列表（分页）                 │
│       └── CacheService::getComicList()       缓存30min          │
│  GET  /api/comic/detail         漫画详情                        │
│       └── CacheService::getComicDetail()     缓存30min          │
│                                                                 │
│  GET  /api/category/index       分类列表                        │
│       └── CacheService::getCategoryList()    缓存1h             │
│  GET  /api/category/comics      分类下漫画（分页）               │
│       └── CacheService::getCategoryComics()  缓存1h             │
│                                                                 │
│  GET  /api/chapter/index        章节列表                        │
│       ├── CacheService::getChapterList()     缓存15min          │
│       └── ComicPurchase::getPurchasedIds()   实时（用户相关）    │
│  GET  /api/chapter/detail       章节详情（付费墙）               │
│       ├── CacheService::getChapterDetail()   缓存15min          │
│       └── ComicPurchase::hasPurchased()      实时（用户相关）    │
│                                                                 │
│  POST /api/favorite/add         收藏（不缓存）                   │
│  POST /api/favorite/remove      取消收藏（不缓存）               │
│  GET  /api/favorite/index       我的收藏（不缓存，JOIN查询）      │
│                                                                 │
│  POST /api/follow/add           关注（不缓存）                   │
│  POST /api/follow/remove        取消关注（不缓存）               │
│  GET  /api/follow/index         我的关注（不缓存，JOIN查询）      │
│                                                                 │
│  POST /api/pay/purchase         购买章节 → 清章节缓存            │
│  GET  /api/pay/check            查询是否购买（不缓存）           │
│  POST /api/pay/notify           支付回调 → 清章节缓存            │
│  GET  /api/pay/returnx          支付跳转                        │
│                                                                 │
│  GET  /api/ad/index             广告列表                        │
│       └── CacheService::getBanner()          缓存30min          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 四、付费逻辑流程图

```
用户点击阅读章节
       │
       ▼
  GET /api/chapter/detail?id=123
       │
       ▼
  CacheService::getChapterDetail(123) → 命中缓存
       │
       ├── is_free = '1' ──────────→ 返回全部图片 ✅
       │
       └── is_free = '0' (付费章节)
              │
              ├── 用户未登录 → 返回前2张 + 提示登录
              │
              ├── 用户已登录 → ComicPurchase::hasPurchased() 实时查询
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
              │               │   支付平台异步回调
              │               │       │
              │               │   POST /api/pay/notify
              │               │       ├── 写入 purchase 记录
              │               │       └── CacheService::clearChapter() 清缓存
              │               │
              │               └── epay未配置 → 直接写入 purchase 记录 + 清缓存
              │
              └── 再次请求 → 缓存已清，重新从DB加载 → 返回全部图片 ✅
```

---

## 五、数据库表一览（mha_ 前缀）

| 表名 | Model | $table 声明 | 用途 |
|------|-------|------------|------|
| mha_comic | Comic | ✅ mha_comic | 漫画表 |
| mha_comic_chapter | ComicChapter | ✅ mha_comic_chapter | 章节表 |
| mha_comic_chapter_content | ComicChapterContent | ✅ mha_comic_chapter_content | 章节内容表 |
| mha_comic_purchase | ComicPurchase | ✅ mha_comic_purchase | 购买记录表 |
| mha_comic_category | ComicCategory | ✅ mha_comic_category | 分类表 |
| mha_comic_category_relation | ComicCategoryRelation | ✅ mha_comic_category_relation | 分类关联表 |
| mha_user_favorite | UserFavorite | ✅ mha_user_favorite | 收藏表 |
| mha_user_follow | UserFollow | ✅ mha_user_follow | 关注表 |
| mha_ad | Ad | ✅ mha_ad | 广告表 |

---

## 六、Cron 配置

```bash
# 添加到系统 crontab
# 每5分钟刷新热点缓存，防止缓存击穿
*/5 * * * * php /path/to/project/think cache:refresh >> /tmp/cache_refresh.log 2>&1
```

手动测试：
```bash
php think cache:refresh
```

---

