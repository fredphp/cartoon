-- 配置管理菜单 SQL
-- 执行方式：在数据库中直接执行以下 SQL，或后台通过「SQL 命令行」执行

-- 主菜单：配置管理
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(0, 'settings', '配置管理', 'fa fa-sliders', '', '', '统一管理所有插件配置', 1, 'addtabs', '', 'pzgl', 'peizhiguanli', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- 获取刚插入的菜单 ID（请根据实际 ID 替换下面的 @pid）
SET @pid = LAST_INSERT_ID();

-- 子菜单：插件配置
INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@pid, 'settings/index', '查看', 'fa fa-circle-o', '', '', '', 0, 'none', '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

SET @index_id = LAST_INSERT_ID();

INSERT INTO `fa_auth_rule` (`pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@index_id, 'settings/save', '保存', 'fa fa-circle-o', '', '', '', 0, 'none', '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
