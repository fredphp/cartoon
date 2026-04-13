-- ============================================================
-- 漫画章节模块 - 建表SQL
-- 表前缀: mha_
-- ============================================================

-- ----------------------------
-- 章节表
-- ----------------------------
CREATE TABLE `mha_comic_chapter` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `comic_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '漫画ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '章节标题',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格(0=免费)',
  `is_free` enum('0','1') NOT NULL DEFAULT '1' COMMENT '是否免费:0=付费,1=免费',
  `sort` int(10) NOT NULL DEFAULT '0' COMMENT '排序(越小越前)',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `createtime` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  `deletetime` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `comic_id` (`comic_id`),
  KEY `sort` (`sort`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='漫画章节表';

-- ----------------------------
-- 章节内容表
-- ----------------------------
CREATE TABLE `mha_comic_chapter_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `chapter_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '章节ID',
  `images` text COMMENT '图片列表(JSON数组)',
  `createtime` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `chapter_id` (`chapter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='漫画章节内容表';

-- ----------------------------
-- 购买记录表
-- ----------------------------
CREATE TABLE `mha_comic_purchase` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `chapter_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '章节ID',
  `comic_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '漫画ID',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '购买价格',
  `createtime` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_chapter` (`user_id`,`chapter_id`),
  KEY `comic_id` (`comic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='漫画购买记录表';
