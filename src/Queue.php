<?php

namespace mmeyer2k\Monorail;

class Queue
{
    public static function push(\Predis\Client $redis, \Closure $closure, string $tube = 'default', int $delay = 0)
    {
        $id = md5(mt_rand());

        $serializer = new \SuperClosure\Serializer();

        $serialized = $serializer->serialize($closure);

        $job = json_encode([
            'id' => $id,
            'closure' => $serialized,
        ]);

        $redis->lpush("monorail:$tube:active", $job);
    }
}
