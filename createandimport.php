<?php

$soapUsername = 'testuser';
$soapPassword = 'testpass';
$soapHost = '127.0.0.1';
$soapPort = '7878';

$dbUsername = 'mangos';
$dbPassword = 'password';

$client = new SoapClient(NULL, array(
	'location'	=>	"http://$soapHost:$soapPort/",
	'uri'		=>	'urn:MaNGOS',
	'style'		=>	SOAP_RPC,
	'login'		=>	$soapUsername,
	'password'	=>	$soapPassword,
));

$account_name = $_POST['account_name'];
$account_pw = $_POST['account_pw'];

$character_name = ucfirst($_POST['character_name']);
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
		} else if(strpos($chunk, 'ZyID') !== false) {
			// Add the item.
			$money = trim($line_split[$k + 1]);
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

unset($result);

function levelCharacter($attempts) {
	global $client;
	global $character_name;

	echo "Calling levelCharacter...<br>\n";
	$command = 'character level ' . $character_name . ' 60';
	echo $command . "<br>";
	try {
		$result = $client->executeCommand(new SoapParam($command, 'command'));
	} catch(Exception $e) {
		echo 'Character level exception: ', $e->getMessage(), "\n<br>";
		if($attempts > 0) {
			levelCharacter($attempts - 1);
		} else {
			return 1;
		}
	}
	if(!isset($result)) {
		echo "Character level set failed!<br>\n";
		return 1;
	}
}

// Write does not take place immediately so try to level the character repeatedly until it succeeds.

levelCharacter(200);

// Send items to character.
foreach($item_arr as $item) {
	$command = 'send items ' . $character_name . ' "Imported Item" "Enjoy!" ' . $item . ':count1';

	unset($result);

	try {
		$result = $client->executeCommand(new SoapParam($command, 'command'));
	} catch(Exception $e) {
		echo 'Item send exception: ', $e->getMessage(), "<br>\n";
		$result = true; // Set the result so the loop can finish, since some items custom to other servers could fail.
	}

	if(!isset($result)) {
		echo "Character item send failed!<br>\n";
		return 1;
	}
}

// Send imported money to character.
$command = 'send money ' . $character_name . ' "Imported Money" "Enjoy!" ' . $money;

unset($result);

try {
	$result = $client->executeCommand(new SoapParam($command, 'command'));
} catch(Exception $e) {
	echo 'Money send exception: ', $e->getMessage(), "<br>\n";
	$result = true; // Set the result so the loop can finish, since some items custom to other servers could fail.
}

if(!isset($result)) {
	echo "Character money send failed!<br>\n";
	return 1;
}

// Send training money to character.
$command = 'send money ' . $character_name . ' "Imported Money" "Enjoy!" 3000000';

unset($result);

try {
	$result = $client->executeCommand(new SoapParam($command, 'command'));
} catch(Exception $e) {
	echo 'Money send exception: ', $e->getMessage(), "<br>\n";
	$result = true; // Set the result so the loop can finish, since some items custom to other servers could fail.
}

if(!isset($result)) {
	echo "Character money send failed!<br>\n";
	return 1;
}

$pdo = new PDO('mysql:host=127.0.0.1;dbname=mZero_characters;charset=utf8', $dbUsername, $dbPassword);

$statement = $pdo->query("select guid from characters where name='" . $character_name . "'");
$row = $statement->fetch(PDO::FETCH_ASSOC);
$guid = $row['guid'];

$statement = $pdo->query("select skill,max from character_skills where guid='" . $row['guid'] . "'");
$rows = $statement->fetchAll(PDO::FETCH_ASSOC);

foreach($rows as $row) {
	$skill = $row['skill'];
	$max = $row['max'];
	$statement = $pdo->query("update character_skills set value='300',max='300' where skill='" . $skill . "'");
}

$warrior_spells_array = array(
    '71', '202', '266', '674', '676', '750', '871', '1161', '1180', '1672', '1680', '1719', '2331', '2332', '2334', '2337', '2458', '2565', '2687', '3101', '3127', '3170', '3171', '3173', '3176', '3177', '3274', '3276', '3277', '3278', '3447', '5011', '5246', '6554', '7179', '7373', '7836', '7837', '7841', '7845', '7928', '7934', '11551', '11556', '11567', '11574', '11578', '11581', '11585', '11601', '11605', '11993', '12296', '12679', '12753', '12764', '12765', '12792', '12800', '12809', '12811', '12818', '12856', '12958', '12975', '13001', '16466', '16538', '18499', '20230', '20560', '20569', '20662', '23925', '33391'
);

if($character_class == "1") { // Warrior
    foreach($warrior_spells_array as $spell_id) {
        $statement = $pdo->query("insert into character_spell (guid, spell, active, disabled) values (" . $guid . ", " . $spell_id . ", 1, 0)");
    }
}

echo "Success!\n";

?>

