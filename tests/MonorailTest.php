<?php

class MonorailTest extends \PHPUnit\Framework\TestCase
{
    function setUp()
    {
        $redis = new \Predis\Client;

        $redis->flushall();

        foreach (range(1, 9) as $p) {
            foreach (range(0, 9) as $i) {
                echo "pushing priority:$p #$i\n";
                (new \mmeyer2k\Monorail\Task)
                    ->priority($p)
                    ->push(function () {
                        $redis = new \Predis\Client;
                        $redis->incr('test_count');
                    });
            }
        }

        foreach (range(1, 9) as $p) {
            foreach (range(0, 9) as $i) {
                echo "pushing priority:$p #$i\n";
                (new \mmeyer2k\Monorail\Task)
                    ->priority($p)
                    ->push(function () {
                        $redis = new \Predis\Client;
                        $redis->incr('test_count_failed');
                        throw new \Exception("WoMp wOmP");
                    });
            }
        }

        foreach (range(1, 9) as $p) {
            foreach (range(0, 9) as $i) {
                echo "pushing priority:$p #$i [delayed]\n";
                (new \mmeyer2k\Monorail\Task)
                    ->priority($p)
                    ->delay(1)
                    ->push(function () {
                        $redis = new \Predis\Client;
                        $redis->incr('test_count_delayed');
                    });
            }
        }

        foreach (range(1, 9) as $p) {
            foreach (range(0, 9) as $i) {
                echo "pushing priority:$p #$i [delayed][tube2]\n";
                (new \mmeyer2k\Monorail\Task)
                    ->priority($p)
                    ->tube('tube2')
                    ->delay(1)
                    ->push(function () {
                        $redis = new \Predis\Client;
                        $redis->incr('test_count_delayed_tube2');
                    });
            }
        }

        sleep(2);

        require __DIR__ . '/../work.php';
    }

    function testCounters()
    {
        $redis = new \Predis\Client;

        $this->assertEquals(90, (int)$redis->get("test_count"));

        $this->assertEquals(90, (int)$redis->get("test_count_delayed"));
    }
}