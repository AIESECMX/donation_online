<?php
require_once '../vendor/autoload.php';
require_once './constants.php';

$redis = new Predis\Client(['port' => 6380]);

//If there's some data missing, reload everything from the configuration file
if( !$redis->exists(REDIS_EY) || !$redis->exists(REDIS_PROD) ) {
  $redis->flushall(); //Flush all existing data
  $config_settings = json_decode(file_get_contents(CONFIG_FILE)); //true for associative array

  //Add all products to Redis store
  $products = $config_settings->products;
  foreach ($products as $product) {
    $redis->sadd(REDIS_PROD,$product->id);
    $redis->hmset(REDIS_PROD.":".$product->id,array(
      "id" => $product->id,
      "name" => $product->name,
      "amount" => $product->amount
    ));
  }

  //Add all EYs to Redis store
  $eys = $config_settings->eys;
  foreach ($eys as $ey) {
    $redis->sadd(REDIS_EY,$ey->name);
    $redis->hmset(REDIS_EY.":".$ey->name,array(
      "name" => $ey->name,
      "fin" => $ey->fin,
      "ogv" => $ey->ogv,
      "ogta" => isset($ey->ogta)?$ey->ogta:null,
      "ogta_st" => isset($ey->ogta_st)?$ey->ogta_st:null,
      "ogte" => isset($ey->ogte)?$ey->ogte:null,
    ));
  }
}

$out = (object)[];

//Retrieve products from Redis and form array
$products = [];
$redis_prods = $redis->smembers(REDIS_PROD);
foreach ($redis_prods as $product) {
  $temp = (object)$redis->hgetall(REDIS_PROD.":".$product);

  //Convert to integer from strings.
  $temp->amount = intval($temp->amount);
  $products[] = $temp;
}
$out->products = $products;

//Retrieve EYs from Redis and form array
$eys = [];
$redis_eys = $redis->smembers(REDIS_EY);
foreach ($redis_eys as $ey) {
  $temp = (object)$redis->hgetall(REDIS_EY.":".$ey);

  //Don't show emails on the front-end
  unset($temp->fin);
  unset($temp->ogv);
  unset($temp->ogt);
  unset($temp->oge);

  $eys[] = $temp;
}
$out->eys = $eys;

echo json_encode($out);