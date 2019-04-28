<?php

namespace mmeyer2k\Monorail;

use mmeyer2k\SemLock;

class Worker
{
    public static function popJob(\Predis\Client $redis, int $priority): bool
    {
        return SemLock::synchronize("monorail:semlock:$priority", function () use ($redis, $priority) {
            // Get the first job off of the active queue
            $jobRaw = $redis->lindex("monorail:$priority:active", -1);

            // Decode the job json blob
            $job = json_decode($jobRaw);

            // Deserialize the job
            $closure = (new \SuperClosure\Serializer())->unserialize($job->closure);

            // Increment job failed counter here and save back to redis
            // in case something causes this entire process to fail
            $fails = $redis->incr("monorail:$priority:failed:$job->id");

            $exmsg = '';
            $ex = null;

            try {
                $ret = $closure();
            } catch (\Exception $e) {
                $ex = $e;
            }

            $redis->multi();

            if ($ex !== null) {
                if ($fails >= 3) {
                    $redis->rpoplpush("monorail:$priority:active", "monorail:$priority:failed");
                } else {
                    $redis->rpoplpush("monorail:$priority:active", "monorail:$priority:active");
                }

                echo "failed...     [$job->id][$fails][$exmsg]\n";
            } else {

                if (is_a($ret, TaskRequeue::class)) {
                    // Requeue the class
                } else {
                    $redis->rpop("monorail:$tube:active");
                }

                $redis->del("monorail:$tube:failed:$job->id");

                echo "processed...  [$job->id]\n";
            }

            $redis->exec();
        });
    }
}
