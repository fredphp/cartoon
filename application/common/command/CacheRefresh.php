<?php

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\library\CacheService;

/**
 * 缓存定时刷新命令
 * 
 * 用法：php think cache:refresh
 * 
 * 建议加入 crontab，每5分钟执行一次：
 * */5 * * * * php /path/to/think cache:refresh >> /tmp/cache_refresh.log 2>&1
 */
class CacheRefresh extends Command
{
    protected function configure()
    {
        $this->setName('cache:refresh')
            ->setDescription('定时刷新热点缓存（防止缓存击穿）');
    }

    protected function execute(Input $input, Output $output)
    {
        $startTime = microtime(true);
        $output->writeln('<info>[' . date('Y-m-d H:i:s') . '] 开始刷新缓存...</info>');

        $results = CacheService::refreshAll();

        foreach ($results as $tag => $status) {
            if ($status === 'ok') {
                $output->writeln("<info>  ✓ {$tag}: 刷新成功</info>");
            } else {
                $output->writeln("<error>  ✗ {$tag}: {$status}</error>");
            }
        }

        $elapsed = round((microtime(true) - $startTime) * 1000);
        $output->writeln("<info>缓存刷新完成，耗时 {$elapsed}ms</info>");

        return 0;
    }
}
