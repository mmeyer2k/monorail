<?php declare(strict_types = 1);

class MonorailTest extends \PHPUnit\Framework\TestCase
{
    function testCounters()
    {
        $redis = new \Predis\Client;

        foreach (['default'] as $t) {
            $this->assertEquals(5 * 3, (int)$redis->get("count:basic:$t"));

            $this->assertEquals(5 * 3, (int)$redis->get("count:delayed:$t"));

            $this->assertEquals(5 * 3 * 3, (int)$redis->get("count:failure:$t"));
        }
    }

    function testKeySizes()
    {
        $redis = new \Predis\Client;

        foreach (['default'] as $t) {
            $this->assertEquals(5 * 3, (int)$redis->get("count:delayed:$t"));
            $this->assertEquals(0, (int)$redis->get("count:delayed:long:$t"));
        }
    }
}