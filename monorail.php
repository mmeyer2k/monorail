<?php

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    require __DIR__ . '/../../autoload.php';
}

$redis = new Predis\Client;

$cmd = $argv[1] ?? null;

if ($cmd === 'work') {

    $single = in_array('--single', $argv ?? []);
    $daemon = in_array('--daemon', $argv ?? []);

    $tube = array_reduce($argv, function ($carry, $item) {
        if (strpos($item, '--tube=') === 0) {
            return substr($item, 7);
        }

        return $carry;
    }, 'default');

    foreach (range(1, 5) as $priority) {
        while (true) {
            // Move any delayed items into active once their times have passed
            $wompwomp = $redis->zrangebyscore("monorail:$tube:$priority:delayed", '-inf', time(), [
                'LIMIT' => 100,
            ]);

            if (!$wompwomp) {
                break;
            }

            //$redis->multi();

            foreach ($wompwomp as $womp) {
                $redis->lpush("monorail:$tube:$priority:active", $womp);
                $redis->zrem("monorail:$tube:$priority:delayed", $womp);
            }

            //$redis->exec();
        }

        $task = (new \mmeyer2k\Monorail\Queue)
            ->tube($tube)
            ->priority($priority);

        while ($task->pending()) {
            $task->work();
        }
    }
}