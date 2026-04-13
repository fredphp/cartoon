<?php

namespace addons\faredis;

use app\common\library\Menu;
use think\Addons;

/**
 * 插件
 */
class Faredis extends Addons
{

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        $menu = [
            [
                'name'    => 'faredis/index',
                'title'   => 'Redis数据管理工具',
                'ismenu'  => 1,
                'sublist' => []
            ]
        ];
        Menu::create($menu);
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        Menu::delete("faredis/index");
        return true;
    }

    /**
     * 插件启用方法
     * @return bool
     */
    public function enable()
    {
        Menu::enable("faredis/index");
        return true;
    }

    /**
     * 插件禁用方法
     * @return bool
     */
    public function disable()
    {
        Menu::disable("faredis/index");
        return true;
    }
}
