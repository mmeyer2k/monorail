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

        foreach (['default', 'tube2'] as $t) {
            foreach (range(1, 9) as $p) {
                foreach (range(0, $this->n - 1) as $i) {
                    (new \mmeyer2k\Monorail\Task)
                        ->priority($p)
                        ->tube($t)
                        ->push(function () use ($t) {
                            $redis = new \Predis\Client;
                            $redis->incr("count:basic:$t");
                        });

                    (new \mmeyer2k\Monorail\Task)
                        ->priority($p)
                        ->tube($t)
                        ->push(function () use ($t) {
                            $redis = new \Predis\Client;
                            $redis->incr("count:failure:$t");
                            throw new \Exception("WoMp wOmP");
                        });

                    (new \mmeyer2k\Monorail\Task)
                        ->priority($p)
                        ->tube($t)
                        ->delay(1)
                        ->push(function () use ($t) {
                            $redis = new \Predis\Client;
                            $redis->incr("count:delayed:$t");
                        });

                }
            }
        }

        sleep(2);

        require __DIR__ . '/../work.php';
    }

    function testCounters()
    {
        $redis = new \Predis\Client;
        foreach (['default', 'tube2'] as $t) {
            $this->assertEquals(9 * $this->n, (int)$redis->get("count:basic:$t"));

            $this->assertEquals(9 * $this->n, (int)$redis->get("count:delayed:$t"));

            $this->assertEquals(9 * $this->n * 3, (int)$redis->get("count:failure:$t"));
        }
    }

    function testKeySizes()
    {
        $redis = new \Predis\Client;

        $this->assertEquals(0, (int)$redis->zcard("monorail:default:1:delayed"));
    }
}