<?php
/**
 * 配置管理菜单安装脚本
 * 
 * 使用方式：在项目根目录执行
 * php think settings:install
 * 
 * 或在浏览器中访问（需先删除此文件）
 */

// 统一引导：必须调用 App::initCommon() 才能加载项目自身的 config.php 和 database.php
define('APP_PATH', __DIR__ . '/application/');
require __DIR__ . '/thinkphp/base.php';
\think\App::initCommon();

use think\Db;

echo "========================================\n";
echo "  配置管理菜单安装\n";
echo "========================================\n\n";

try {
    // 1. 检查是否已存在
    $exists = Db::name('auth_rule')->where('name', 'settings')->find();
    if ($exists) {
        echo "[!] 菜单 '配置管理' 已存在 (ID: {$exists['id']})，跳过创建。\n";
        
        // 检查子菜单
        $indexExists = Db::name('auth_rule')->where('name', 'settings/index')->find();
        if (!$indexExists) {
            Db::name('auth_rule')->insert([
                'pid'        => $exists['id'],
                'name'       => 'settings/index',
                'title'      => '查看',
                'icon'       => 'fa fa-circle-o',
                'ismenu'     => 0,
                'menutype'   => 'none',
                'createtime' => time(),
                'updatetime' => time(),
                'weigh'      => 0,
                'status'     => 'normal',
            ]);
            echo "[+] 已添加 settings/index 子菜单\n";
            
            $indexId = Db::name('auth_rule')->getLastInsID();
            Db::name('auth_rule')->insert([
                'pid'        => $indexId,
                'name'       => 'settings/save',
                'title'      => '保存',
                'icon'       => 'fa fa-circle-o',
                'ismenu'     => 0,
                'menutype'   => 'none',
                'createtime' => time(),
                'updatetime' => time(),
                'weigh'      => 0,
                'status'     => 'normal',
            ]);
            echo "[+] 已添加 settings/save 子菜单\n";
        }
        
        // 确保超级管理员组拥有权限
        $groupId = Db::name('auth_group')->where('id', 1)->value('rules');
        if ($groupId !== null) {
            $rules = explode(',', $groupId);
            $added = false;
            foreach (['settings', 'settings/index', 'settings/save'] as $ruleName) {
                $rule = Db::name('auth_rule')->where('name', $ruleName)->find();
                if ($rule && !in_array($rule['id'], $rules)) {
                    $rules[] = $rule['id'];
                    $added = true;
                }
            }
            if ($added) {
                Db::name('auth_group')->where('id', 1)->update(['rules' => implode(',', $rules)]);
                echo "[+] 已更新超级管理员权限\n";
            }
        }
        
        echo "\n[OK] 安装完成！请刷新后台页面查看「配置管理」菜单。\n";
        exit(0);
    }

    // 2. 插入主菜单
    $pid = Db::name('auth_rule')->insertGetId([
        'pid'        => 0,
        'name'       => 'settings',
        'title'      => '配置管理',
        'icon'       => 'fa fa-sliders',
        'url'        => '',
        'condition'  => '',
        'remark'     => '统一管理所有插件配置',
        'ismenu'     => 1,
        'menutype'   => 'addtabs',
        'extend'     => '',
        'py'         => 'pzgl',
        'pinyin'     => 'peizhiguanli',
        'createtime' => time(),
        'updatetime' => time(),
        'weigh'      => 0,
        'status'     => 'normal',
    ]);
    echo "[+] 创建主菜单「配置管理」(ID: {$pid})\n";

    // 3. 插入查看子菜单
    $indexId = Db::name('auth_rule')->insertGetId([
        'pid'        => $pid,
        'name'       => 'settings/index',
        'title'      => '查看',
        'icon'       => 'fa fa-circle-o',
        'ismenu'     => 0,
        'menutype'   => 'none',
        'createtime' => time(),
        'updatetime' => time(),
        'weigh'      => 0,
        'status'     => 'normal',
    ]);
    echo "[+] 创建子菜单「查看」(ID: {$indexId})\n";

    // 4. 插入保存子菜单
    $saveId = Db::name('auth_rule')->insertGetId([
        'pid'        => $indexId,
        'name'       => 'settings/save',
        'title'      => '保存',
        'icon'       => 'fa fa-circle-o',
        'ismenu'     => 0,
        'menutype'   => 'none',
        'createtime' => time(),
        'updatetime' => time(),
        'weigh'      => 0,
        'status'     => 'normal',
    ]);
    echo "[+] 创建子菜单「保存」(ID: {$saveId})\n";

    // 5. 更新超级管理员权限
    $adminGroup = Db::name('auth_group')->where('id', 1)->find();
    if ($adminGroup) {
        $rules = array_filter(explode(',', $adminGroup['rules']));
        $rules[] = $pid;
        $rules[] = $indexId;
        $rules[] = $saveId;
        Db::name('auth_group')->where('id', 1)->update([
            'rules' => implode(',', array_unique($rules)),
        ]);
        echo "[+] 已更新超级管理员权限\n";
    }

    echo "\n========================================\n";
    echo "  [OK] 安装成功！\n";
    echo "  请刷新后台页面，在左侧菜单中找到「配置管理」\n";
    echo "========================================\n";

} catch (\Exception $e) {
    echo "\n[ERROR] 安装失败: " . $e->getMessage() . "\n";
    exit(1);
}
