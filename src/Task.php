<?php

namespace mmeyer2k\Monorail;

use SuperClosure\Serializer;
use Predis\Client;

class Task
{
    private $redis;
    private $tube = 'default';
    private $delay = 0;
    private $priority = 5;

    /**
     * Task constructor.
     * @param Client|null $redis
     */
    public function __construct(\Predis\Client $redis = null)
    {
        if ($redis === null) {
            $redis = new Client();
        }

        $this->redis = $redis;
    }

    /**
     * @param string $tube
     * @return Task
     */
    public function tube(string $tube): self
    {
        $this->priority = $tube;

        return $this;
    }

    /**
     * @param int $priority
     * @return Task
     * @throws \InvalidArgumentException
     */
    public function priority(int $priority = 5): self
    {
        if ($priority > 9 || $priority < 1) {
            throw new \InvalidArgumentException("Priority values can only be 1 - 9");
        }

        $this->priority = $priority;

        return $this;
    }

    /**
     * @param int $delay
     * @return Task
     */
    public function delay(int $delay = 0): self
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * @param \Closure $closure
     */
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
            $this->redis->zadd("monorail:$this->tube:$this->priority:delayed", $score, $job);
        } else {
            $this->redis->lpush("monorail:$this->tube:$this->priority:active", $job);
        }
    }

    public function pending(): bool
    {
        return $this->redis->scard("monorail:$this->tube:$this->priority:active") > 0;
    }

    public function work()
    {
        return SemLock::synchronize("monorail:semlock:$this->tube:$this->priority", function () {
            // Get the first job off of the active queue
            $jobRaw = $this->redis->lindex("monorail:$this->tube:$this->priority:active", -1);

            // Decode the job json blob
            $job = json_decode($jobRaw);

            // Deserialize the job
            $closure = (new \SuperClosure\Serializer())->unserialize($job->closure);

            // Increment job failed counter here and save back to redis
            // in case something causes this entire process to fail
            $fails = $this->redis->incr("monorail:$this->tube->$this->priority:failed:$job->id");

            $exmsg = '';
            $ex = null;

            try {
                $ret = $closure();
            } catch (\Exception $e) {
                $ex = $e;
            }

            if ($ex !== null) {
                $prefix = "monorail:$this->tube:$this->priority";
                if ($fails >= 3) {
                    $this->redis->rpoplpush("$prefix:active", "$prefix:failed");
                } else {
                    $this->redis->rpoplpush("$prefix:active", "$prefix:active");
                }

                echo "failed...     [$job->id][$fails][$exmsg]\n";
            } else {

                if (is_a($ret, TaskRequeue::class)) {
                    // Requeue the class
                } else {
                    $this->redis->rpop("monorail:$this->tube:$this->priority:active");
                }

                $this->redis->del("monorail:$this->tube:$this->priority:failed:$job->id");

                echo "processed...  [$job->id]\n";
            }
        });
    }
}
