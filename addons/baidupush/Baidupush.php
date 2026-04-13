<?php

namespace addons\baidupush;

use addons\baidupush\library\Push;
use app\common\library\Menu;
use think\Addons;

/**
 * 百度推送插件
 */
class Baidupush extends Addons
{

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        $menu = [
            [
                'name'    => 'baidupush',
                'title'   => '百度推送管理',
                'icon'    => 'fa fa-paper-plane',
                'sublist' => [
                    ['name' => 'baidupush/index', 'title' => '查看'],
                    ['name' => 'baidupush/normal', 'title' => '普通收录提交'],
                    ['name' => 'baidupush/daily', 'title' => '快速收录提交'],
                ]
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
        Menu::delete('baidupush');
        return true;
    }

    /**
     * 插件启用方法
     * @return bool
     */
    public function enable()
    {
        Menu::enable('baidupush');
        return true;
    }

    /**
     * 插件禁用方法
     * @return bool
     */
    public function disable()
    {
        Menu::disable('baidupush');
        return true;
    }

    /**
     * 实现钩子方法
     * @param mixed $params URL数组
     * @param null  $extra
     * @return mixed
     */
    public function baidupush($params, $extra = null)
    {
        $config = get_addon_config('baidupush');
        $statusArr = explode(',', $config['status']);
        $urls = is_string($params) ? [$params] : $params;
        $extra = $extra ? $extra : 'urls';
        foreach ($statusArr as $index => $item) {
            if ($extra == 'urls' || $extra == 'append') {
                Push::connect(['type' => $item])->realtime($urls);
            } elseif ($extra == 'del' || $extra == 'delete') {
                Push::connect(['type' => $item])->delete($urls);
            }
        }
    }

}
