<?php

class MonorailTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Number of iterations to run per priority level and tube
     *
     * @var int
     */
    private $n = 3;

    function setUp()
    {
        $redis = new \Predis\Client;

        $redis->flushall();

        foreach (range(1, 9) as $p) {
            foreach (range(0, $this->n - 1) as $i) {
                (new \mmeyer2k\Monorail\Task)
                    ->priority($p)
                    ->push(function () {
                        $redis = new \Predis\Client;
                        $redis->incr('test_count');
                    });

                (new \mmeyer2k\Monorail\Task)
                    ->priority($p)
                    ->push(function () {
                        $redis = new \Predis\Client;
                        $redis->incr('test_count_failed');
                        throw new \Exception("WoMp wOmP");
                    });

                (new \mmeyer2k\Monorail\Task)
                    ->priority($p)
                    ->delay(1)
                    ->push(function () {
                        $redis = new \Predis\Client;
                        $redis->incr('test_count_delayed');
                    });

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

        $this->assertEquals(9 * $this->n, (int)$redis->get("test_count"));

        $this->assertEquals(9 * $this->n, (int)$redis->get("test_count_delayed"));

        $this->assertEquals(9 * $this->n * 3, (int)$redis->get("test_count_failed"));

        $this->assertEquals(0, (int)$redis->zcard("monorail:default:1:delayed"));
    }
}