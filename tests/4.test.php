<?php

require __DIR__ . "/../vendor/autoload.php";

function _e(string $msg)
{
    echo "$msg\n";
    exit(1);
}

$redis = new \Predis\Client;

$p = (int)$redis->get("test_count");
$e = 90;
if ($p !== $e) {
    _e("test_count value of [$p] is not equal to [$e]");
}