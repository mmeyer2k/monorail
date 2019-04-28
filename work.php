<?php

$single = in_array($argv, '--single');

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    require __DIR__ . '/../../autoload.php';
}

$redis = new Predis\Client;

foreach (range(1, 9) as $priority) {
    $task = (new \mmeyer2k\Monorail\Task)
        ->priority($priority);

    while ($task->pending()) {
        $task->work();
    }
}