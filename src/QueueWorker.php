<?php

namespace mmeyer2k\Monorail;

class QueueWorker
{
    public static function consoleLog(string $msg)
    {
    }

    public static function popJob(\Predis\Client $redis, string $tube): bool
    {
        $jobRaw = $redis->lindex("monorail:$tube:active", -1);

        $job = json_decode($jobRaw);

        if (!$job) {
            return false;
        }

        $sem = sem_get(666);

        sem_acquire($sem);

        echo "processing... [$job->id]\n";

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
                $redis->rpoplpush("queue:active", "queue:failed");
            } else {
                $redis->rpoplpush("queue:active", "queue:active");
            }

            echo "failed...     [$job->id][$fails][$exmsg]\n";
        } else {

            $redis->rpop("queue:active");

            $redis->del("queue:failed:$job->id");

            echo "processed...  [$job->id]\n";
        }

        $redis->exec();

        sem_release($sem);

        return true;
    }
}
