<?php

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    require __DIR__ . '/../../autoload.php';
}

$redis = new Predis\Client;

foreach (range(1, 9) as $priority) {
    while (true) {
        // Move any delayed items into active once their times have passed
        $wompwomp = $redis->zrangebyscore("monorail:default:$priority:delayed", '-inf', time(), [
            'LIMIT' => 100,
        ]);

        if (!$wompwomp) {
            break;
        }

        $redis->multi();

        foreach ($wompwomp as $womp) {
            $redis->lpush("monorail:default:$priority:active", $womp);
            $redis->zrem("monorail:default:$priority:delayed", $womp);
        }

        $redis->exec();
    }

    $task = (new \mmeyer2k\Monorail\Task)
        ->priority($priority);

    while ($task->pending()) {
        $task->work();
    }
}