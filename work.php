<?php

$single = in_array($argv, '--single');

$redis = new Predis\Client;

foreach (range(1, 9) as $priority) {
    $task = (new \mmeyer2k\Monorail\Task)
        ->priority($priority);

    while ($task->pending()) {
        $task->work();
    }
}