<?php

namespace mmeyer2k\Monorail;

use SuperClosure\Serializer;
use Predis\Client;

class Task
{
    private $redis;
    private $priority;
    private $delay;

    public function __construct(\Predis\Client $redis = null)
    {
        if ($redis === null) {
            $redis = new Client();
        }

        $this->redis = $redis;
    }

    public function priority(int $priority = 5): self
    {
        $this->priority = $priority;

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
            $this->redis->zadd("monorail:$this->priority:delayed", $score, $job);
        } else {
            $this->redis->lpush("monorail:$this->priority:active", $job);
        }
    }
}
