<?php
require_once '../vendor/autoload.php';
//require '/home/webmaster/vendor/phpmailer/phpmailer/PHPMailerAutoload.php';
//require '/home/webmaster/wp-config-files/gis_lib/vendor/autoload.php';

try{
	$configs = include('./payment_config.php');
	//$configs = include('/home/webmaster/wp-config-files/payment_congif.php');

}catch (Exception $e) {
	$lc_n=$_POST["committee"];
        $logdataa=date('[Y/m/d H:i:s]').": 500 Could not Load, $lc_n, ".$e->getMessage().PHP_EOL;
        file_put_contents("./error.log",$logdataa,FILE_APPEND);
	//	header("Location:https://aiesec.org.mx/error_de_transaccion/");		
}

$test = TRUE;
if (!$test) {
	Openpay::setId($configs['openpay_id']);
	Openpay::setApiKey($configs['openpay_private_key']);
	Openpay::setProductionMode(true);
	$openpay = Openpay::getInstance($configs['openpay_id'],$configs['openpay_private_key']);
}
else{
	$openpay = Openpay::getInstance($configs['openpay_sandbox_id'],$configs['openpay_sandbox_private_key']);
}

$nombre = $_POST["amount"];
$nombre = $_POST["name_participant"];
$pieces = explode(" ", $nombre);
$apellido = NULL;
switch (sizeof($pieces)) {
	case 1:
	$nombre = $pieces[0];
	$apellido = $pieces[0];
		//# code...
	break;

	case 2:
	$nombre = $pieces[0];
	$apellido = $pieces[1];
		//# code...
	break;

	case 3:
	$nombre = $pieces[0];
	$apellido = $pieces[1]." ".$pieces[2];
		//# code...
	break;

	case 4:
	$nombre = $pieces[0]." ".$pieces[1];
	$apellido = $pieces[2]." ".$pieces[3];
	
		//# code...
	break;
	
	default:
	
	$apellido = " - ";
	
		//# code...
	break;
}
$committees_j = './pay_online_mailing.json';
$json_com = file_get_contents($committees_j, false, stream_context_create($arrContextOptions)); 
$committee_mails = json_decode($json_com,true); 
$mails = $committee_mails[$_POST["committee"]];
$mail_def = $mails["def"];
$mail_pr = "";
$program_fee = 3950;
$program = '';
$logging_flag = false;
switch ($_POST["amount"]) {
	case 2950:
	//case 1: //caso ogv
	//$program_fee =(int)$configs['ogv_cost'];
	$program = 'Voluntario Global';
	$mail_pr = $mails["ogv"];
	$logging_flag = true;
	break;
	case 3950:
	//case 1: //caso ogv
	//$program_fee =(int)$configs['ogv_cost'];
	$program = 'Voluntario Global';
	$mail_pr = $mails["ogv"];
	break;
	case 4450:
        //case 2: //caso oge
        //$program_fee = (int)$configs['oge_cost'];
	$program = 'Emprendedor Global';
	$mail_pr = $mails["ogt"];
	break;
	case 5950:
        //case 3: //caso ogt
        //$program_fee = (int)$configs['ogt_cost'];
	$program = 'Talento Global';
	$mail_pr = $mails["ogt"];
	break;
	default:
	$program = 'AIESEC';
	$mail_pr = $mails["ogv"];
	break;
}



try{

	$customerData = array(
		'name' => $nombre,
		'last_name' => $apellido,
		'phone_number' => $_POST["phone_number"],
		'email' => $_POST["email"]);
	$customer = $openpay->customers->add($customerData);

	$chargeData = array(
		'method' => 'card',
		'source_id' => $_POST["token_id"],
		'amount' => $_POST["amount"],//$program_fee;
		'description' => '{"comite":'.$_POST["committee"].',"programa":'.$program.'}',
		'device_session_id' => $_POST["deviceIdHiddenFieldName"],
		'customer' => $customerData

		);
	$charge = $openpay->charges->create($chargeData);
	//var_dump($charge);
	//$charge = (array)$charge;
	//getting the mail body
	//	{pay_order}
	//	{date}
	//	{name}
	//	{progrma} 
	//	{price}
	//	{card}
	$mail_body = file_get_contents('../mail.html',TRUE);
	$mail_body = str_replace("{pay_order}",$charge->authorization."",$mail_body);
	$mail_body = str_replace("{date}",$charge->operation_date,$mail_body);
	$mail_body = str_replace("{name}",$_POST["name_participant"],$mail_body);
	$mail_body = str_replace("{progrma}",$program,$mail_body);
	$mail_body = str_replace("{price}",$charge->amount,$mail_body);
	$mail_body = str_replace("{card}",substr($charge->card->card_number,-4),$mail_body);

	//sending confirmation mail




//echo "<br>mail 1 <br>".$mail_def;
//echo "<br>mail 2 <br>".$mail_pr;

	//header("Location:https://aiesec.org.mx/gracias-por-tu-donativo/");



/*
	//mark as paid in expa
	////mark as paid in expa
	if(isset($_POST["app_id"])){
		$expa_keys = include('/home/webmaster/Automatizations/payments/expa_acess.php');
		$user = new \GISwrapper\AuthProviderCombined(htmlspecialchars($expa_keys['expa_user']), htmlspecialchars($expa_keys['expa_pass']));

		//GETTING THE acces token
		//GETTING THE acces token
		//GETTING THE acces token
		$gis = new \GISwrapper\GIS($user);
		$user_id =$gis->current_person->get()->person->id;
		$session_token=$user->getToken();

		$params = '{"application":{"paid":true}}'

		$url = "https://gis-api.aiesec.org/v2/applications/".$_POST["app_id"].".json?access_token=".$session_token;
		$ch = curl_init(); 				// such as http://example.com/example.xml
		$headers = array('Content-Type: application/json');
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$data = curl_exec($ch);
		curl_close($ch);
	}
	*/
	/////mark as paid in expa
	/////mark as paid in expa


	$mail = new PHPMailer(); // create a new object
	$mail->IsSMTP(); 
	$mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
	$mail->SMTPAuth = true; // authentication enabled
	$mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
	$mail->Host = "smtp.gmail.com";
	$mail->Port = 465; // or 587
	$mail->IsHTML(true);
	$mail->Username = $configs["mailing_adress"];
	$mail->Password = $configs["mailing_adress_pass"];
	$mail->SetFrom($configs["mailing_adress"],'AIESEC MEXICO');
	$mail->Subject = "Donativo a AIESEC Mexico A.C.";
	$mail->Body = $mail_body;
	$mail->AddAddress($_POST["email"]);
	$mail->addCC($mail_def);
	$mail->addCC($mail_pr);
	$mail->addCC('webmaster@aiesec.org.mx');
	//$mail->addCC('finance.legal@aiesec.org.mx');
	$mail->Send();

	if($logging_flag == true) {
		//Discount logging
		$lc_n=$_POST["committee"];
		$logdataa=date('[Y/m/d H:i:s]').": $nombre $apellido, $lc_n".PHP_EOL;
		file_put_contents("./discounted_donations.log",$logdataa,FILE_APPEND);
	}

	echo "Done";


}catch (Exception $e) {
	$lc_n=$_POST["committee"];
        $logdataa=date('[Y/m/d H:i:s]').": $nombre $apellido, $lc_n, ".$e->getMessage().PHP_EOL;
        file_put_contents("./error.log",$logdataa,FILE_APPEND);
	header("Location:https://aiesec.org.mx/error_de_transaccion/?err=".$e->getMessage());		
}
//header("Location:https://aiesec.org.mx/gracias-por-tu-donativo/");




?>
