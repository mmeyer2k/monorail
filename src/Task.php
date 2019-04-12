<?php

namespace mmeyer2k\Monorail;

use SuperClosure\Serializer;
use Predis\Client;

class Task
{
    private $redis;
    private $tube;
    private $delay;

    public function __construct(\Predis\Client $redis = null)
    {
        if ($redis === null) {
            $redis = new Client();
        }

        $this->redis = $redis;
    }

    public function tube(string $tube = 'default'): self
    {
        $this->tube = $tube;

        return $this;
    }

    public function delay(int $delay = 0): self
    {
        $this->delay = $delay;

        return $this;
    }

    public function push(\Closure $closure)
    {
        $id = md5(mt_rand());

        $serializer = new Serializer();

        $serialized = $serializer->serialize($closure);

        $job = json_encode([
            'id' => $id,
            'closure' => $serialized,
        ]);

        if ($this->delay) {
            $score = time() + $this->delay;
            $this->redis->zadd("monorail:$this->tube:delayed", $score, $job);
        } else {
            $this->redis->lpush("monorail:$this->tube:active", $job);
        }
    }
}
