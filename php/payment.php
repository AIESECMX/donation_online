<?php
require_once '../vendor/autoload.php';
require_once './constants.php';

/*
	TO-DO: Still need to exclude virtual expansions from the promo. Check with Kenia if this is
	going to be the case, or if all people will have the same pricing and VAM will just not count
	for the payments info.
*/

$test = FALSE;
$redis = new Predis\Client(['port' => 6380]); //Docker's redis instance is on port 6380

//Load config file, if cannot load, then send error because we won't be able to load Openpay keys
try{
	$url = "payment_config.php";
	if(!test) {
		$url = "/home/webmaster/wp-config-files/".$url;
	} else {
		$url = "../".$url;
	}

	$configs = include($url);

	// If $_POST data is not valid, then it might be a bot POSTing into the server
	// or something went wrong with validation in ../index.html
	if(!validateData()) {
		// If so, send back to donation form, in case it's a human
		header("Location:https://aiesec.org.mx/donation/?error=validation");
	}
} catch (Exception $e) {
	$lc_n=$_POST["committee"];
        $logdataa=date('[Y/m/d H:i:s]').": 500 Could not Load, $lc_n, ".$e->getMessage().PHP_EOL;
        file_put_contents("./error.log",$logdataa,FILE_APPEND);
    if(!$test) {
    	header("Location:https://aiesec.org.mx/error_de_transaccion/");	
    }
    else {
    	die("There was an error with payment config"); //Only for debug	
    }
}

//Set Openpay private keys to get Instance
if (!$test) {
	Openpay::setId($configs['openpay_id']);
	Openpay::setApiKey($configs['openpay_private_key']);
	Openpay::setProductionMode(true);
	$openpay = Openpay::getInstance($configs['openpay_id'],$configs['openpay_private_key']);
	//echo "Set Production Keys, Done! <br>\n";
}
else {
	Openpay::setSandboxMode(true);
	$openpay = Openpay::getInstance($configs['openpay_sandbox_id'],$configs['openpay_sandbox_private_key']);
	// "Set Sandbox Keys, Done! <br>\n";
}


// Set fields for Openpay charge
$product = $_POST[PRODUCT_FIELD];
$product_full = $redis->hget(REDIS_PROD.":".$product,"name"); // Product Full Name

$ey_tag = $_POST[EY_FIELD]; // This tag serves for unidentified donations, marked as "Fondo Perdido" (lost fund)
// Who to send the emails. Unidentified donations shall go to MCVP F&L and corresponding MCVP Operations (i.e. VAM)
$targetEy = $ey_tag === FONDO_PERDIDO ? "VAM" : $ey_tag;
$amount = (int)$_POST[AMOUNT_FIELD];

$finance_mail = $redis->hget(REDIS_EY.":".$targetEy,"fin");
$product_mail = $redis->hget(REDIS_EY.":".$targetEy,$product);

// Checks with Redis whether amount is correct. If it's incorrect, redirects back to form
// Logic: If there is discount, then check discounted price, if not, then check regular price

// PART 1: Added this bit for discount strategy. In the future, don't allow for eys with exceptions!!!
if($targetEy !== "VAM" ) {
// END PART 1

// Saves whether there is a discount going on
$discount = $redis->exists(DISCOUNTS[$product]);
if( !( $discount ? (int)$redis->hget(REDIS_PROD.":".$product,REDIS_DISCOUNT) === $amount :
	(int)$redis->hget(REDIS_PROD.":".$product,REDIS_AMOUNT) === $amount) ) {
	// "Amount is INCORRECT<br>";
	//die(); //DELETE this on production
	header("Location:https://aiesec.org.mx/donation/?error=amount");
}

// PART 2 of the bit added for discount strategy.
// If it falls through this else if it is VAM, then check regular amount
} else if((int)$redis->hget(REDIS_PROD.":".$product,REDIS_AMOUNT) !== $amount ) {
	//echo "Amount is INCORRECT<br>";
	//die(); //DELETE this on production
	header("Location:https://aiesec.org.mx/donation/?error=amount");
}
//END PART 2
//END of bit added

//Create the carge in Openpay (this is the part involving money, beware)
try {

	$customerData = array(
		'name' => $_POST[FIRST_NAME_FIELD],
		'last_name' => $_POST[LAST_NAME_FIELD],
		'phone_number' => $_POST[PHONE_FIELD],
		'email' => $_POST[EMAIL_FIELD]
	);
	$customer = $openpay->customers->add($customerData);

	$chargeData = array(
		'method' => 'card',
		'source_id' => $_POST[TOKEN_FIELD],
		'amount' => $amount,
		'description' => '{"comite":'.$ey_tag.',"producto":'.$product_full.'}',
		'device_session_id' => $_POST[ANTIFRAUD_FIELD],
		'customer' => $customerData
	);

	$charge = $openpay->charges->create($chargeData);
	//If we arrive to this part of the code it means charge was successful, thus increase discount counter
	if($discount && $targetEy !== "VAM") { //added special case for VAM
		$redis->incr(DISCOUNTS[$product]); // Another discounted donation has been submitted
		validateDonations(); // Checks whether all donations have been handed out
	}
	//var_dump($charge);
	//$charge = (array)$charge;
	//getting the mail body
	//	{pay_order}
	//	{date}
	//	{name}
	//	{producto} 
	//	{price}
	//	{card}
	$mail_body = file_get_contents('../mail.html',TRUE);
	$mail_body = str_replace("{pay_order}",$charge->authorization."",$mail_body);
	$mail_body = str_replace("{date}",$charge->operation_date,$mail_body);
	$mail_body = str_replace("{name}",$_POST[FIRST_NAME_FIELD]." ".$_POST[LAST_NAME_FIELD],$mail_body);
	$mail_body = str_replace("{product}",$product_full,$mail_body);
	$mail_body = str_replace("{donation}",$charge->amount,$mail_body);
	$mail_body = str_replace("{card}",substr($charge->card->card_number,-4),$mail_body);

	//sending confirmation mail
	//echo "<br><br><strong>Enviando correo, revisa tu bandeja con tu comprobante del donativo.</strong>";

	$mail = new PHPMailer(); // create a new object
	$mail->CharSet = "UTF-8";
	$mail->IsSMTP();
	//$mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
	$mail->SMTPAuth = true; // authentication enabled
	$mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
	$mail->Host = "smtp.gmail.com";
	$mail->Port = 465;
	$mail->IsHTML(true);
	$mail->Username = $configs["mailing_adress"];
	$mail->Password = $configs["mailing_adress_pass"];
	$mail->SetFrom($configs["mailing_adress"],"AIESEC M\u{00E9}xico A.C.");
	$mail->Subject = "Donativo a AIESEC M\u{00E9}xico A.C.";
	$mail->Body = $mail_body;
	$mail->AddAddress($_POST[EMAIL_FIELD]);
	if(!$test) {
		$mail->addCC($finance_mail);
		$mail->addCC($product_mail);
		$mail->addCC('finance.legal@aiesec.org.mx'); //Copiar MCVP F&L
	}
	$mail->addCC('webmaster@aiesec.org.mx'); //Copiar MCVP IM to identify possible bugs
	$mail->Send();

	if($discount === true) {
		//Discount logging
		$lc_n=$_POST["committee"];
		$logdataa=date('[Y/m/d H:i:s]').": $firstName $lastName, $lc_n".PHP_EOL;
		file_put_contents("./discounted_donations.log",$logdataa,FILE_APPEND);
	}

	//echo "<br><br><strong>Hecho, revisa tu correo para obtener tu comprobante.</strong>";

	header("Location:https://aiesec.org.mx/gracias-por-tu-donativo/");
	/* if(!$test) {
		header("Location:https://aiesec.org.mx/gracias-por-tu-donativo/");
	} */

// To improve from legacy: Add all the recommended Openpay Exceptions pls!
} catch (Exception $e) {
	$lc_n=$_POST["committee"];
        $logdataa=date('[Y/m/d H:i:s]').": $firstName $lastName, $lc_n, ".$e->getMessage().PHP_EOL;
        file_put_contents("./error.log",$logdataa,FILE_APPEND);
	header("Location:https://aiesec.org.mx/error_de_transaccion/?err=".$e->getMessage());		
}

function validateData() {
	$validation = true;
	$validation &= isset($_POST[FIRST_NAME_FIELD]) && $_POST[FIRST_NAME_FIELD]!=="" ;
	$validation &= isset($_POST[LAST_NAME_FIELD]) && $_POST[LAST_NAME_FIELD]!=="" ;
	$validation &= isset($_POST[EMAIL_FIELD]) && filter_var($_POST[EMAIL_FIELD], FILTER_VALIDATE_EMAIL);
	$validation &= isset($_POST[PRODUCT_FIELD]) && in_array($_POST[PRODUCT_FIELD],[OGV,OGT,OGE]);
	$validation &= isset($_POST[EY_FIELD]);
	$validation &= isset($_POST[PHONE_FIELD]);
	$validation &= isset($_POST[ANTIFRAUD_FIELD]);
	$validation &= isset($_POST[TOKEN_FIELD]);
	$validation &= isset($_POST[AMOUNT_FIELD]);
	return $validation;
}

function validateDonations() {
	global $redis;
	global $product;

	//If we've reached the limit of discounts for the product, then disable the discount
	if($redis->exists(DISCOUNTS[$product]) && $redis->exists(DISCOUNT_LIMIT[$product])
		&& $redis->get(DISCOUNTS[$product]) >= $redis->get(DISCOUNT_LIMIT[$product])) {
		$redis->del(DISCOUNTS[$product]);
	}	
}
