<?php

namespace mmeyer2k\Monorail;

use mmeyer2k\SemLock;

class QueueWorker
{
    public static function popJob(\Predis\Client $redis, string $tube): bool
    {
        return SemLock::synchronize($tube, function () use ($redis, $tube) {
            $jobRaw = $redis->lindex("monorail:$tube:active", -1);

            $job = json_decode($jobRaw);

            $serializer = new \SuperClosure\Serializer();

            $closure = $serializer->unserialize($job->closure);

            // Increment job failed counter here and save back to redis
            // in case something causes this entire process to fail
            $fails = $redis->incr("monorail:$tube:failed:$job->id");

            $exmsg = '';

            try {
                $ret = $closure();
            } catch (\Exception $e) {
                $exmsg = $e->getMessage();
            }

            $redis->multi();

            if ($exmsg) {
                if ($fails >= 3) {
                    $redis->rpoplpush("monorail:$tube:active", "monorail:$tube:failed");
                } else {
                    $redis->rpoplpush("monorail:$tube:active", "monorail:$tube:active");
                }

                echo "failed...     [$job->id][$fails][$exmsg]\n";
            } else {

                $redis->rpop("monorail:$tube:active");

                $redis->del("monorail:$tube:failed:$job->id");

                echo "processed...  [$job->id]\n";
            }

            $redis->exec();

            return true;
        });
    }
}
