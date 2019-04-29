<?php

namespace mmeyer2k\Monorail;

use SuperClosure\Serializer;
use Predis\Client;

class Task extends Requeue
{
    private $redis;

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
     * @param \Closure $closure
     */
    public function push(\Closure $closure)
    {
        $id = md5(mt_rand());

        $serializer = new Serializer();

        $serialized = $serializer->serialize($closure);

        $job = json_encode([
            'id' => $id,
            'tries' => $this->tries,
            'closure' => $serialized,
        ]);

        if ($this->delay) {
            $zscore = time() + $this->delay;
            $this->redis->zadd("monorail:$this->tube:$this->priority:delayed", $zscore, $job);
        } else {
            $this->redis->lpush("monorail:$this->tube:$this->priority:active", $job);
        }
    }

    public function pending(): bool
    {
        return $this->redis->llen("monorail:$this->tube:$this->priority:active") > 0;
    }

    public function work()
    {
        return \mmeyer2k\SemLock::synchronize("monorail:semlock:$this->tube:$this->priority", function () {
            // Get the first job off of the active queue
            $jobRaw = $this->redis->lindex("monorail:$this->tube:$this->priority:active", -1);

            // Decode the job json blob
            $job = json_decode($jobRaw);

            // Deserialize the job
            $closure = (new \SuperClosure\Serializer())->unserialize($job->closure);

            // Increment job failed counter here and save back to redis
            // in case something causes this entire process to fail
            $fails = $this->redis->incr("monorail:$this->tube:$this->priority:failed:$job->id");

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
