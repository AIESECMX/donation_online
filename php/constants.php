<?php

define("CONFIG_FILE","../config.json");
// Product Constants
define("OGV","ogv");
define("OGT","ogt");
define("OGE","oge");

// Form Field Constants used in "index.html", "/js/data_config.js" and "/js/openpay_setup.js"
define("FIRST_NAME_FIELD","epFirstName");
define("LAST_NAME_FIELD","epLastName");
define("EMAIL_FIELD","email");
define("PHONE_FIELD","phone_number");
define("PRODUCT_FIELD","product");
define("EY_FIELD","committee");
define("ANTIFRAUD_FIELD","deviceIdHiddenFieldName");
define("TOKEN_FIELD","token_id");
define("AMOUNT_FIELD","amount");
define("FONDO_PERDIDO","Fondo Perdido");

// Redis store constants
const DISCOUNT_LIMIT = array(
  OGV => "ogv_discount_limit",
  OGT => "ogt_discount_limit",
  OGE => "oge_discount_limit",
);

const DISCOUNTS = array(
  OGV => "ogv_discount",
  OGT => "ogt_discount",
  OGE => "oge_discount",
); // These are set in Redis when there's a discount strategy for the product, and unset otherwise

define("LOCAL_DISCOUNTS","localDiscounts");
define("REDIS_EY","eys");
define("REDIS_PROD","products");

define("REDIS_DISCOUNT","globalDiscount");
define("REDIS_AMOUNT","amount");