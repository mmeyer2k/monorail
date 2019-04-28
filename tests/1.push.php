<?php

require __DIR__ . "/../vendor/autoload.php";

foreach (range(1, 9) as $p) {
    foreach (range(0, 9) as $i) {
        echo "pushing priority:$p #$i\n";
        (new \mmeyer2k\Monorail\Task)
            ->priority($p)
            ->push(function () {
                \Redis::incr('test_count');
            });
    }
}

foreach (range(1, 9) as $p) {
    foreach (range(0, 9) as $i) {
        echo "pushing priority:$p #$i [delayed]\n";
        (new \mmeyer2k\Monorail\Task)
            ->priority($p)
            ->delay(2)
            ->push(function () {
                \Redis::incr('test_count_delayed');
            });
    }
}

foreach (range(1, 9) as $p) {
    foreach (range(0, 9) as $i) {
        echo "pushing priority:$p #$i [delayed][tube2]\n";
        (new \mmeyer2k\Monorail\Task)
            ->priority($p)
            ->tube('tube2')
            ->delay(2)
            ->push(function () {
                \Redis::incr('test_count_delayed_tube2');
            });
    }
}