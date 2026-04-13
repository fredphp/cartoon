-- ============================================================
-- 章节付费模块 - 建表SQL
-- 注意: mha_comic_purchase 表已在章节模块中创建
-- 本文件为独立的 user_purchase 表（简化版，如需使用独立表可执行）
-- 表前缀: mha_ (独立于 FastAdmin 默认 fa_ 前缀)
-- ============================================================

CREATE TABLE IF NOT EXISTS `mha_user_purchase` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `chapter_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '章节ID',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '购买价格',
  `createtime` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_chapter` (`user_id`, `chapter_id`),
  KEY `user_id` (`user_id`),
  KEY `chapter_id` (`chapter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户购买记录表';
