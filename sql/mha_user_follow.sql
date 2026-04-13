-- ============================================================
-- 关注作者模块 - 建表SQL
-- 表前缀: mha_ (独立于 FastAdmin 默认 fa_ 前缀)
-- ============================================================

CREATE TABLE `mha_user_follow` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID（关注者）',
  `follow_user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '被关注的用户ID（作者）',
  `createtime` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_follow` (`user_id`, `follow_user_id`),
  KEY `user_id` (`user_id`),
  KEY `follow_user_id` (`follow_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户关注表';
