<?php

require __DIR__ . "/../vendor/autoload.php";

foreach (range(0, 1000) as $r) {
    // Queue a job with the basic syntax default tube no delay
    (new \mmeyer2k\Monorail\Task)->push(function () {
        \Redis::incr('test_count');
    });
}