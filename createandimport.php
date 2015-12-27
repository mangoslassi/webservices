<?php

$soapUsername = 'testuser';
$soapPassword = 'testpass';
$soapHost = '127.0.0.1';
$soapPort = '7878';

$client = new SoapClient(NULL, array(
	'location'	=>	"http://$soapHost:$soapPort/",
	'uri'		=>	'urn:MaNGOS',
	'style'		=>	SOAP_RPC,
	'login'		=>	$soapUsername,
	'password'	=>	$soapPassword,
));

$account_name = $_POST['account_name'];
$account_pw = $_POST['account_pw'];

$character_name = $_POST['character_name'];
$character_contents = $_POST['luafile_contents'];

$character_race = $_POST['character_race'];
$character_class = $_POST['character_class'];
$character_gender = $_POST['character_gender'];
$character_skin = $_POST['character_skin'];
$character_face = $_POST['character_face'];
$character_hairStyle = $_POST['character_hairStyle'];
$character_hairColor = $_POST['character_hairColor'];
$character_facialHair = $_POST['character_facialHair'];
$character_outfitId = $_POST['character_outfitId'];

$item_arr = array();

// Parse contents
$contents_split = explode("\n", $character_contents);

foreach($contents_split as $line) {
	$line_split = explode(':', $line);

	foreach($line_split as $k => $chunk) {
		if(strpos($chunk, '|Hitem') !== false) {
			// Add the item.
			$item = trim($line_split[$k + 1]);
			
			array_push($item_arr, $item);
		}
	}
}

$command = 'account create ' . $account_name . ' ' . $account_pw;

try {
	$result = $client->executeCommand(new SoapParam($command, 'command'));
} catch(Exception $e) {
	echo 'Account create exception: ', $e->getMessage(), "\n";
	return 1;
}

if(!isset($result)) {
	echo 'Account creation failed!<br>';
	return 1;
}

$command = 'character create ' . $account_name . ' ' . $character_name . ' ' . $character_race . ' ' . $character_class . ' ' . $character_gender . ' ' . $character_skin . ' ' . $character_face . ' ' . $character_hairStyle . ' ' . $character_hairColor . ' ' . $character_facialHair . ' ' . $character_outfitId;

unset($result);

try {
	$result = $client->executeCommand(new SoapParam($command, 'command'));
} catch(Exception $e) {
	echo 'Character create exception: ', $e->getMessage(), "\n";
	return 1;
}

if(!isset($result)) {
	echo "Character creation failed!<br>\n";
	return 1;
}

$command = 'character level ' . $character_name . ' 60';

try {
	$result = $client->executeCommand(new SoapParam($command, 'command'));
} catch(Exception $e) {
	echo 'Character level exception: ', $e->getMessage(), "\n";
	return 1;
}

if(!isset($result)) {
	echo "Character level set failed!<br>\n";
	return 1;
}

foreach($item_arr as $item) {
	$command = 'send items ' . $character_name . ' "Imported Item" "Enjoy!" ' . $item . ':count1';

	try {
		$result = $client->executeCommand(new SoapParam($command, 'command'));
	} catch(Exception $e) {
		echo 'Item send exception: ', $e->getMessage(), "<br>\n";
	}

	if(!isset($result)) {
		echo "Character item send failed!<br>\n";
		return 1;
	}
}

echo "Success!\n";

?>

