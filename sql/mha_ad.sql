-- ============================================================
-- 广告模块 - 建表SQL（更新版，含 position 字段）
-- 表前缀: mha_ (独立于 FastAdmin 默认 fa_ 前缀)
-- ============================================================

CREATE TABLE `mha_ad` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '广告标题',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '广告图片',
  `link` varchar(255) NOT NULL DEFAULT '' COMMENT '跳转链接',
  `position` varchar(30) NOT NULL DEFAULT 'banner' COMMENT '广告位置: banner=首页轮播,popup=弹窗,inline=信息流',
  `type` enum('banner','popup','inline') NOT NULL DEFAULT 'banner' COMMENT '广告类型',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '排序权重',
  `createtime` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `weigh` (`weigh`,`id`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  KEY `position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告表';

-- ============================================================
-- 增量更新：如果已有旧表，执行以下 ALTER 添加 position 和 link 字段
-- ============================================================
-- ALTER TABLE `mha_ad` ADD COLUMN `position` varchar(30) NOT NULL DEFAULT 'banner' COMMENT '广告位置' AFTER `url`;
-- ALTER TABLE `mha_ad` ADD COLUMN `link` varchar(255) NOT NULL DEFAULT '' COMMENT '跳转链接' AFTER `image`;
-- ALTER TABLE `mha_ad` ADD INDEX `position` (`position`);
-- ALTER TABLE `mha_ad` CHANGE COLUMN `url` `link` varchar(255) NOT NULL DEFAULT '' COMMENT '跳转链接';
