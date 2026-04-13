-- ============================================================
-- 收藏模块 - 建表SQL
-- 表前缀: mha_ (独立于 FastAdmin 默认 fa_ 前缀)
-- ============================================================

CREATE TABLE `mha_user_favorite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `comic_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '漫画ID',
  `createtime` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_comic` (`user_id`, `comic_id`),
  KEY `user_id` (`user_id`),
  KEY `comic_id` (`comic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户收藏表';
