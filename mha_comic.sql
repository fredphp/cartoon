-- ============================================================
-- 漫画模块 - 建表SQL
-- 表前缀: mha_ (独立于 FastAdmin 默认 fa_ 前缀)
-- ============================================================

CREATE TABLE `mha_comic` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '漫画标题',
  `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '封面图',
  `description` text COMMENT '漫画简介',
  `author_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '作者ID',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '排序权重',
  `createtime` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `weigh` (`weigh`,`id`),
  KEY `status` (`status`),
  KEY `author_id` (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='漫画表';
