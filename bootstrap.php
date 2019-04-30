<?php

require __DIR__ . '/vendor/autoload.php';

$redis = new \Predis\Client;

$redis->flushall();

foreach (['default', 'tube2'] as $t) {
    foreach (range(1, 3) as $p) {
        foreach (range(1, 5) as $i) {
            (new \mmeyer2k\Monorail\Queue)
                ->priority($p)
                ->tube($t)
                ->push(function () use ($t) {
                    $redis = new \Predis\Client;
                    $redis->incr("count:basic:$t");
                });

            (new \mmeyer2k\Monorail\Queue)
                ->priority($p)
                ->tube($t)
                ->push(function () use ($t) {
                    $redis = new \Predis\Client;
                    $redis->incr("count:failure:$t");
                    throw new \Exception("WoMp wOmP");
                });

            (new \mmeyer2k\Monorail\Queue)
                ->priority($p)
                ->tube($t)
                ->delay(1)
                ->push(function () use ($t) {
                    $redis = new \Predis\Client;
                    $redis->incr("count:delayed:$t");
                });

            (new \mmeyer2k\Monorail\Queue)
                ->priority($p)
                ->tube($t)
                ->delay(100000)
                ->push(function () use ($t) {
                    $redis = new \Predis\Client;
                    $redis->incr("count:delayed:long:$t");
                });

        }
    }
}

sleep(2);

$mono = __DIR__ . '/monorail.php';
`php $mono work`;
`php $mono work --tube=tube2`;