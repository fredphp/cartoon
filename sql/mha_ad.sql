-- ============================================================
-- 广告模块 - 建表SQL
-- 表前缀: mha_
-- ============================================================

CREATE TABLE `mha_ad` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '广告标题',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '广告图片',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '跳转链接',
  `type` enum('banner','popup','inline') NOT NULL DEFAULT 'banner' COMMENT '广告类型',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '排序权重',
  `createtime` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `weigh` (`weigh`,`id`),
  KEY `status` (`status`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告表';
