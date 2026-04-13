<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Exception;

/**
 * 配置管理
 *
 * @icon   fa fa-sliders
 * @remark 统一管理所有插件配置，按分类Tab切换
 */
class Settings extends Backend
{
    protected $noNeedRight = ['index', 'save'];

    /**
     * 配置分类定义
     * 每个分类对应一个Tab，包含一个或多个插件配置
     */
    protected $categories = [
        'payment' => [
            'title'  => '支付配置',
            'icon'   => 'fa fa-credit-card',
            'addons' => ['epay'],
        ],
        'third' => [
            'title'  => '第三方登录',
            'icon'   => 'fa fa-share-alt',
            'addons' => ['third'],
        ],
        'seo' => [
            'title'  => 'SEO推送',
            'icon'   => 'fa fa-search',
            'addons' => ['baidupush'],
        ],
        'security' => [
            'title'  => '安全配置',
            'icon'   => 'fa fa-shield',
            'addons' => ['banip'],
        ],
        'editor' => [
            'title'  => '编辑器配置',
            'icon'   => 'fa fa-edit',
            'addons' => ['ueditor'],
        ],
        'login' => [
            'title'  => '登录页面',
            'icon'   => 'fa fa-sign-in',
            'addons' => ['loginbgindex'],
        ],
        'redis' => [
            'title'  => 'Redis配置',
            'icon'   => 'fa fa-database',
            'addons' => ['faredis'],
        ],
        'log' => [
            'title'  => '日志配置',
            'icon'   => 'fa fa-file-text-o',
            'addons' => ['log'],
        ],
    ];

    /**
     * 查看配置（Tab页）
     */
    public function index()
    {
        $tabList = [];

        foreach ($this->categories as $key => $category) {
            $tabData = [
                'key'     => $key,
                'title'   => $category['title'],
                'icon'    => $category['icon'],
                'tips'    => [],
                'groups'  => [],  // 按 addon 分组的配置
                'hasItem' => false,
            ];

            foreach ($category['addons'] as $addonName) {
                // 检查插件是否存在
                $addonDir = ADDON_PATH . $addonName . DIRECTORY_SEPARATOR;
                if (!is_dir($addonDir)) {
                    continue;
                }

                $fullConfig = get_addon_fullconfig($addonName);
                if (empty($fullConfig)) {
                    continue;
                }

                $groupItems = [];

                foreach ($fullConfig as $item) {
                    // 跳过内部提示字段
                    if ($item['name'] === '__tips__') {
                        $tabData['tips'][] = $item['value'];
                        continue;
                    }

                    $value = $item['value'];
                    $type  = $item['type'];
                    $rule  = isset($item['rule']) ? $item['rule'] : '';
                    $tip   = isset($item['tip']) ? $item['tip'] : '';

                    // 多选值拆分为数组
                    if (in_array($type, ['checkbox', 'selects'])) {
                        $value = is_array($value) ? $value : explode(',', $value);
                    }

                    // 处理 content 字段（下拉/单选/多选的选项列表）
                    $content = [];
                    if (!empty($item['content'])) {
                        $content = is_array($item['content']) ? $item['content'] : (json_decode($item['content'], true) ?: []);
                    }

                    // array 类型转为 JSON 字符串供前端 fieldlist 使用
                    $valueJson = '';
                    if ($type === 'array' && is_array($value)) {
                        $valueJson = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    }

                    $groupItems[] = [
                        'addon'     => $addonName,
                        'name'      => $item['name'],
                        'title'     => __($item['title']),
                        'type'      => $type,
                        'value'     => $value,
                        'valueJson' => $valueJson,
                        'content'   => $content,
                        'rule'      => $rule,
                        'tip'       => htmlspecialchars($tip),
                        'extend'    => isset($item['extend']) ? $item['extend'] : '',
                    ];

                    $tabData['hasItem'] = true;
                }

                if (!empty($groupItems)) {
                    $tabData['groups'][] = [
                        'addon' => $addonName,
                        'items' => $groupItems,
                    ];
                }
            }

            $tabList[$key] = $tabData;
        }

        // 设置第一个Tab为活跃
        $first = true;
        foreach ($tabList as &$tab) {
            $tab['active'] = $first;
            $first = false;
        }
        unset($tab);

        $this->view->assign('tabList', $tabList);
        return $this->view->fetch();
    }

    /**
     * 保存配置
     */
    public function save()
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }

        $this->token();

        $addon = $this->request->post('addon', '', 'trim');
        $row = $this->request->post('row/a', [], 'trim');

        if (!$addon || empty($row)) {
            $this->error(__('Invalid parameters'));
        }

        // 验证插件存在
        $addonDir = ADDON_PATH . $addon . DIRECTORY_SEPARATOR;
        if (!is_dir($addonDir)) {
            $this->error(__('Addon not found'));
        }

        // 获取当前完整配置
        $fullConfig = get_addon_fullconfig($addon);
        if (empty($fullConfig)) {
            $this->error(__('Addon config not found'));
        }

        // 构建新的配置值映射
        foreach ($fullConfig as &$item) {
            if ($item['name'] === '__tips__') {
                continue;
            }

            $name = $item['name'];

            if (isset($row[$name])) {
                $value = $row[$name];

                // 处理 array 类型
                if ($item['type'] === 'array') {
                    if (is_array($value) && isset($value['key'])) {
                        // fieldlist 格式：从 key 和 value 构建关联数组
                        $arr = [];
                        $keys = $value['key'];
                        $vals = isset($value['value']) ? $value['value'] : [];
                        foreach ($keys as $i => $k) {
                            $k = trim($k);
                            if ($k === '') {
                                continue;
                            }
                            $arr[$k] = isset($vals[$i]) ? $vals[$i] : '';
                        }
                        $value = $arr;
                    }
                } elseif (is_array($value)) {
                    // checkbox/selects 等多选值，转为逗号分隔
                    $value = implode(',', $value);
                }

                $item['value'] = $value;
            }
        }
        unset($item);

        try {
            set_addon_fullconfig($addon, $fullConfig);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success(__('Update successful'));
    }
}
