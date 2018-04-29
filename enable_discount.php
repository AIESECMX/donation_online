<?php

require_once './vendor/autoload.php';
require_once './php/constants.php';

$redis = new Predis\Client(['port' => 6380]);

$redis->set(DISCOUNTS[OGV],0);
$redis->set(DISCOUNTS[OGT],0);
$redis->set(DISCOUNTS[OGE],0);
$redis->set(DISCOUNT_LIMIT[OGV],44);
$redis->set(DISCOUNT_LIMIT[OGT],20);
$redis->set(DISCOUNT_LIMIT[OGE],30);
echo "Discounts set. Done.\n";