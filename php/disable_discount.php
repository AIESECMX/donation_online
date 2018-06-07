<?php

require_once './vendor/autoload.php';

$redis = new Predis\Client(['port' => 6380]);

$redis->flushall();
echo "Discounts disabled, system restablished.\n";