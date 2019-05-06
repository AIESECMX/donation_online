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
      "amount" => $product->amount,
      "globalDiscount" => isset($product->globalDiscount)?$product->globalDiscount:null
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
      "ogt" => isset($ey->ogt)?$ey->ogt:null,
      "oge" => isset($ey->oge)?$ey->oge:null,
    ));
    if (isset($ey->localDiscount)) {
      if(isset($ey->localDiscount->ogv) || isset($ey->localDiscount->ogt) || isset($ey->localDiscount->oge)) {
        $redis->sadd(LOCAL_DISCOUNTS,$ey->name);
        $redis->hmset(LOCAL_DISCOUNTS.":".$ey->name,array(
          "ogv" => isset($ey->localDiscount->ogv)?$ey->localDiscount->ogv:null,
          "ogt" => isset($ey->localDiscount->ogt)?$ey->localDiscount->ogt:null,
          "oge" => isset($ey->localDiscount->oge)?$ey->localDiscount->oge:null,
        ));
      }
    }
  }
}

$out = (object)[];

//Retrieve products from Redis and form array
$products = [];
$redis_prods = $redis->smembers(REDIS_PROD);
foreach ($redis_prods as $product) {
  $temp = (object)$redis->hgetall(REDIS_PROD.":".$product);

  //Convert to integer from strings. (Note in globalDiscount: Empty string shall mean null)
  $temp->amount = intval($temp->amount);
  //If there's a discount going on, then show the discounted price, else, show normal price
  if($redis->exists(DISCOUNTS[$product])) {
    $temp->globalDiscount = $temp->globalDiscount === "" ? null : intval($temp->globalDiscount);
  } else {
    $temp->globalDiscount = null;
  }

  $products[] = $temp;
}
$out->products = $products;

//Retrieve all Discounted EYs and form array
$discounts = [];
$redis_eynames = $redis->smembers(LOCAL_DISCOUNTS);
foreach ($redis_eynames as $eyname) {
  $temp = (object)$redis->hgetall(LOCAL_DISCOUNTS.":".$eyname);

  //Convert to integer from strings. (Note in globalDiscount: Empty string shall mean null)
  $temp->ogv = $temp->ogv === "" ? null : intval($temp->ogv);
  $temp->ogt = $temp->ogt === "" ? null : intval($temp->ogt);
  $temp->oge = $temp->oge === "" ? null : intval($temp->oge);
  $temp->name = $eyname;

  $discounts[] = $temp;
}
$out->localDiscounts = $discounts;

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