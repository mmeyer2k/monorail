<?php

namespace mmeyer2k\Monorail;

use SuperClosure\Serializer;
use Predis\Client;

class Queue extends Requeue
{
    private $redis;

    /**
     * Queue constructor.
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

        $serialized = (new Serializer())->serialize($closure);

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

            // Create the tube key prefix
            $prefix = "monorail:$this->tube:$this->priority";

            // Get the first job off of the active queue
            $jobRaw = $this->redis->lindex("$prefix:active", -1);

            // Decode the job json blob
            $job = json_decode($jobRaw);

            // Deserialize the job
            $closure = (new \SuperClosure\Serializer())->unserialize($job->closure);

            // Increment job failed counter here and save back to redis
            // in case something causes this entire process to fail
            $fails = $this->redis->incr("$prefix:failed:$job->id");

            try {
                $ret = $closure();
            } catch (\Exception $e) {
                $exmsg = $e->getMessage();

                $destination = $fails >= 3 ? "$prefix:failed" : "$prefix:active";

                $this->redis->rpoplpush("$prefix:active", $destination);

                echo "failed...     [$job->id][$fails][$exmsg]\n";
            }

            if (is_a($ret, TaskRequeue::class)) {
                // Requeue the class
            } else {
                $this->redis->rpop("$prefix:active");
            }

            // Since this job was successful, remove anything stored in the failed jobs accumulator
            $this->redis->del("$prefix:failed:$job->id");

            echo "processed...  [$job->id]\n";

        });
    }
}
