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
        $defaultTab = '';

        foreach ($this->categories as $key => $category) {
            $tabData = [
                'key'   => $key,
                'title' => $category['title'],
                'icon'  => $category['icon'],
                'items' => [],
                'tips'  => [],
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

                foreach ($fullConfig as $item) {
                    // 跳过内部提示字段
                    if ($item['name'] === '__tips__') {
                        $tabData['tips'][] = $item['value'];
                        continue;
                    }

                    $value = $item['value'];

                    // 处理不同类型的值
                    if (in_array($item['type'], ['checkbox', 'selects'])) {
                        $value = explode(',', $value);
                    }

                    // array 类型保持原样（作为关联数组传递给视图）
                    // 处理 content 字段
                    $content = [];
                    if (!empty($item['content'])) {
                        if (is_string($item['content'])) {
                            $content = json_decode($item['content'], true) ?: [];
                        } else {
                            $content = $item['content'];
                        }
                    }

                    $tabData['items'][] = [
                        'addon'  => $addonName,
                        'name'   => $item['name'],
                        'title'  => __($item['title']),
                        'type'   => $item['type'],
                        'value'  => $value,
                        'content' => $content,
                        'rule'   => $item['rule'] ?? '',
                        'tip'    => htmlspecialchars($item['tip'] ?? ''),
                        'extend' => $item['extend'] ?? '',
                    ];
                }
            }

            $tabList[$key] = $tabData;
            if (empty($defaultTab) && !empty($tabData['items'])) {
                $defaultTab = $key;
            }
        }

        // 设置第一个有内容的Tab为活跃
        $index = 0;
        foreach ($tabList as &$tab) {
            $tab['active'] = ($index === 0);
            $index++;
        }
        unset($tab);

        $this->view->assign('tabList', $tabList);
        $this->view->assign('defaultTab', $defaultTab);
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
        $newValues = [];
        foreach ($fullConfig as &$item) {
            if ($item['name'] === '__tips__') {
                continue;
            }

            $name = $item['name'];

            if (isset($row[$name])) {
                $value = $row[$name];

                // 处理 array 类型
                if ($item['type'] === 'array') {
                    if (is_array($value) && isset($value['field'])) {
                        // fieldlist 格式：从 field/key 和 field/value 构建关联数组
                        $arr = [];
                        $keys = $value['key'] ?? [];
                        $vals = $value['value'] ?? [];
                        foreach ($keys as $i => $k) {
                            $k = trim($k);
                            if ($k === '') {
                                continue;
                            }
                            $arr[$k] = isset($vals[$i]) ? $vals[$i] : '';
                        }
                        $value = $arr;
                    } elseif (is_array($value)) {
                        // 如果已经是关联数组，直接使用
                        $value = $value;
                    }
                } elseif (is_array($value)) {
                    // checkbox/selects 等多选值，转为逗号分隔
                    $value = implode(',', $value);
                }

                $item['value'] = $value;
                $newValues[$name] = $value;
            }
        }
        unset($item);

        try {
            set_addon_fullconfig($addon, $fullConfig);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        // 尝试刷新插件
        try {
            \addons\Service::refresh();
        } catch (Exception $e) {
            // 刷新失败不影响保存
        }

        $this->success(__('Update successful'));
    }
}
