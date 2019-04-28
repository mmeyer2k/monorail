<?php

require __DIR__ . "/../vendor/autoload.php";

foreach (range(0, 9) as $p) {
    foreach (range(0, 999) as $i) {
        echo "pushing priority:$p #$i\n";

        // Queue a job with the basic syntax default tube no delay
        (new \mmeyer2k\Monorail\Task)
            ->priority($p)
            ->push(function () {
                \Redis::incr('test_count');
            });


        echo "pushing priority:$p #$i\n [delayed]";
        // Queue a job with the basic syntax default tube no delay
        (new \mmeyer2k\Monorail\Task)
            ->priority($p)
            ->delay(2)
            ->push(function () {
                \Redis::incr('test_count_delayed');
            });
    }
}