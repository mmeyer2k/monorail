<?php

require __DIR__ . "/../vendor/autoload.php";

foreach (range(0, 99) as $r) {
    echo "pushing $r\n";
    // Queue a job with the basic syntax default tube no delay
    (new \mmeyer2k\Monorail\Task)->push(function () {
        \Redis::incr('test_count');
    });
}

foreach (range(0, 99) as $r) {
    echo "pushing delayed $r\n";
    // Queue a job with the basic syntax default tube no delay
    (new \mmeyer2k\Monorail\Task)->delay(2)->push(function () {
        \Redis::incr('test_count');
    });
}