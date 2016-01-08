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

        $arr_size = count($item_arr);
        $item_count = '1';
        $item_enchant = '0';

	foreach($line_split as $k => $chunk) {
		if(strpos($chunk, '|Hitem') !== false) {
			// Add the item.
			$item = trim($line_split[$k + 1]);
		}

                if(isset($item)) {
                        if(strpos($chunk, $item) !== false) {
                                $item_enchant = trim($line_split[$k + 1]);
                        }
                }

                if(strpos($chunk, '|Hitmcount') !== false) {
                        $item_count = trim($line_split[$k + 1]);
                        $item_a = array($item, $item_count, $item_enchant);
			array_push($item_arr, $item_a);
                }

                if(strpos($chunk, 'ZyID') !== false) {
			// Add the item.
			$money = trim($line_split[$k + 1]);
		}
	}

        if($arr_size == count($item_arr) && isset($item)) {
                // Array size didn't increase.. Need to add the array item manually.
                $item_a = array($item, $item_count, $item_enchant);
                array_push($item_arr, $item_a);
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
	$command = 'send items ' . $character_name . ' "Imported Item" "Enjoy!" ' . $item[0] . ':' . $item[1];

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
/*
 * Made by hand.

$warrior_spells_array = array(
    '71', '202', '266', '355', '674', '676', '750', '871', '1161', '1180', '1672', '1680', '1719', '2331', '2332', '2334', '2337', '2458', '2565', '2687', '3101', '3127', '3170', '3171', '3173', '3176', '3177', '3274', '3276', '3277', '3278', '3447', '5011', '5246', '6554', '7179', '7373', '7836', '7837', '7841', '7845', '7928', '7934', '11551', '11556', '11567', '11574', '11578', '11581', '11585', '11597', '11601', '11605', '11993', '12296', '12679', '12753', '12764', '12765', '12792', '12800', '12809', '12811', '12818', '12856', '12958', '12975', '13001', '16466', '16538', '18499', '20230', '20560', '20569', '20662', '23925', '33391'
);

$paladin_spells_array = array(
    '750', '1020', '1038', '1044', '1152', '3127', '4987', '5573', '10278', '10293', '10301', '10308', '10310', '10314', '10318', '10326', '10329', '13819', '19746', '19752', '19838', '19854', '19896', '19898', '19900', '19943', '19979', '20164', '20208', '20215', '20216', '20235', '20239', '20261', '20271', '20293', '20308', '20349', '20357', '20361', '20729', '20924', '20930', '24173', '24239', '25780', '25782', '25829', '25836', '25890', '25894', '25895'
);
*/

/* Generated from ".learn all_myclass" gm command. */

$warrior_spells_array = array(
    '71', '355', '676', '871', '1161', '1672', '1680', '1719', '2458', '2565', '2687', '5246', '6554', '7373', '11556', '11574', '11578', '11581', '11585', '11597', '11605', '12288', '12292', '12296', '12323', '12327', '12328', '12659', '12664', '12666', '12679', '12697', '12704', '12707', '12708', '12714', '12727', '12753', '12764', '12765', '12785', '12792', '12800', '12803', '12807', '12809', '12811', '12815', '12818', '12833', '12838', '12856', '12861', '12867', '12879', '12886', '12944', '12958', '12962', '12963', '12974', '12975', '13002', '13048', '16466', '16492', '16494', '16542', '18499', '20496', '20499', '20501', '20503', '20505', '20560', '20569', '20617', '20647', '20662', '21553', '23588', '23695', '23880', '23885', '23886', '23887', '23888', '23889', '23890', '23891', '23894', '23925', '25286', '25288', '25289', '26188'
);

$paladin_spells_array = array(
    '1038', '1044', '1152', '4987', '10278', '10293', '10301', '10308', '10310', '19746', '19752', '19896', '19898', '19900', '19943', '19968', '19979', '19980', '19981', '19982', '19993', '20048', '20059', '20064', '20066', '20092', '20100', '20105', '20113', '20121', '20137', '20142', '20147', '20150', '20164', '20175', '20182', '20184', '20185', '20186', '20187', '20188', '20193', '20200', '20208', '20215', '20216', '20217', '20218', '20235', '20239', '20245', '20256', '20261', '20266', '20271', '20280', '20281', '20282', '20283', '20284', '20285', '20286', '20293', '20300', '20301', '20302', '20303', '20308', '20332', '20337', '20344', '20345', '20346', '20349', '20354', '20355', '20357', '20361', '20425', '20470', '20489', '20729', '20914', '20920', '20924', '20928', '20930', '20961', '20962', '20967', '20968', '24239', '25290', '25291', '25292', '25780', '25781', '25829', '25836', '25890', '25895', '25898', '25899', '25902', '25903', '25911', '25912', '25913', '25914', '25916', '25918', '25957', '25988', '25997', '26021', '26023'
);

$druid_spells_array = array(
    '2782', '2893', '8946', '9835', '9853', '9858', '9863', '9885', '9901', '9907', '9910', '9912', '16818', '16820', '16825', '16835', '16840', '16847', '16862', '16864', '16880', '16901', '16906', '16913', '16920', '16926', '16933', '16938', '16941', '16944', '16951', '16954', '16961', '16968', '16975', '16979', '16999', '17007', '17055', '17061', '17068', '17073', '17078', '17082', '17108', '17113', '17116', '17122', '17124', '17249', '17329', '17392', '17402', '18562', '18658', '18960', '20748', '21850', '22812', '22839', '24858', '24866', '24894', '24946', '24972', '24977', '25297', '25298', '25299', '29166'
);

$mage_spells_array = array(
    '130', '475', '759', '1953', '2139', '2855', '3552', '3561', '3562', '3565', '6085', '7301', '10053', '10054', '10059', '10140', '10157', '10161', '10170', '10174', '10187', '10193', '10199', '10202', '10207', '10216', '10220', '10225', '10230', '11129', '11368', '11416', '11419', '11958', '12042', '12043', '12051', '12341', '12342', '12350', '12351', '12353', '12360', '12400', '12469', '12472', '12475', '12484', '12485', '12486', '12488', '12490', '12497', '12503', '12519', '12536', '12571', '12577', '12592', '12598', '12605', '12606', '12654', '12826', '12842', '12848', '12873', '12953', '12985', '13021', '13033', '13043', '15053', '15060', '16758', '16766', '16770', '18460', '18464', '18809', '22783', '23028', '24530', '25304', '25306', '25345', '28270', '28271', '28272', '28332', '28574', '28595', '28609', '28612', '29076', '29440', '29447'
);

$warlock_spells_array = array(
    '603', '688', '691', '697', '712', '1010', '5857', '6215', '6358', '7870', '11675', '11678', '11681', '11682', '11684', '11689', '11695', '11700', '11704', '11708', '11713', '11717', '11719', '11722', '11726', '11730', '11763', '11767', '11771', '11775', '11780', '11785', '17728', '17752', '17782', '17787', '17792', '17803', '17808', '17814', '17836', '17854', '17918', '17923', '17924', '17926', '17928', '17932', '17937', '17953', '17958', '17959', '18073', '18095', '18123', '18127', '18129', '18134', '18136', '18178', '18181', '18183', '18219', '18223', '18275', '18288', '18313', '18372', '18393', '18540', '18647', '18693', '18696', '18701', '18704', '18707', '18708', '18710', '18746', '18752', '18756', '18768', '18773', '18775', '18788', '18825', '18830', '18871', '18881', '18932', '18938', '19007', '19028', '19443', '19480', '19647', '19736', '20757', '23825', '25307', '25309', '25311'
);

$hunter_spells_array = array(
    '982', '1002', '1543', '3043', '3045', '5116', '5118', '5384', '13159', '13163', '13544', '13809', '14266', '14268', '14271', '14273', '14275', '14276', '14277', '14280', '14287', '14295', '14301', '14305', '14311', '14315', '14317', '14325', '15632', '19153', '19160', '19235', '19245', '19259', '19263', '19287', '19300', '19373', '19377', '19390', '19415', '19420', '19425', '19431', '19458', '19468', '19490', '19494', '19500', '19503', '19511', '19556', '19558', '19560', '19573', '19574', '19575', '19577', '19587', '19592', '19596', '19602', '19612', '19620', '19625', '19801', '20190', '20895', '20904', '20906', '20910', '24131', '24133', '24134', '24135', '24283', '24295', '24297', '24387', '24395', '24396', '24397', '24406', '24691', '25294', '25295', '25296'
);

$rogue_spells_array = array(
    '921', '1725', '1769', '1787', '1833', '1842', '1857', '2094', '5277', '6774', '8643', '11198', '11269', '11275', '11286', '11290', '11294', '11297', '11305', '13750', '13791', '13792', '13803', '13807', '13845', '13852', '13856', '13863', '13866', '13867', '13872', '13875', '13877', '13964', '13969', '13973', '13980', '14065', '14066', '14071', '14075', '14081', '14083', '14095', '14117', '14137', '14142', '14148', '14159', '14161', '14164', '14167', '14169', '14173', '14176', '14177', '14179', '14183', '14185', '14195', '14251', '14278', '14983', '16720', '17348', '18429', '25300', '25302', '30893', '30895', '30906', '30920', '31016'
);

$shaman_spells_array = array(
    '197', '199', '526', '2484', '2645', '2870', '6495', '8012', '8143', '8166', '8170', '8177', '10408', '10414', '10428', '10432', '10438', '10463', '10468', '10473', '10479', '10497', '10538', '10587', '10601', '10605', '10614', '10623', '11315', '15112', '15208', '16089', '16108', '16112', '16116', '16120', '16130', '16161', '16164', '16166', '16188', '16189', '16198', '16208', '16209', '16213', '16217', '16221', '16225', '16229', '16234', '16240', '16268', '16269', '16274', '16284', '16287', '16291', '16293', '16295', '16301', '16305', '16309', '16362', '16387', '16544', '16582', '17359', '17364', '17489', '18848', '20608', '20777', '21169', '25012', '25076', '25357', '25359', '25361', '25908', '26363', '26364', '26365', '26366', '26367', '26369', '26370', '28998', '29000', '29065', '29080', '29088', '29180', '29191', '29193', '29202', '29228'
);

$priest_spells_array = array(
    '528', '552', '988', '1706', '2053', '6064', '10060', '10876', '10890', '10894', '10901', '10909', '10912', '10917', '10934', '10938', '10942', '10947', '10952', '10953', '10955', '10958', '14528', '14751', '14767', '14769', '14771', '14772', '14774', '14777', '14783', '14787', '14791', '15011', '15012', '15014', '15017', '15018', '15031', '15261', '15286', '15310', '15311', '15316', '15317', '15320', '15326', '15330', '15334', '15338', '15356', '15363', '15448', '15473', '15487', '17191', '17325', '18535', '18550', '18555', '18807', '19243', '19275', '20711', '20770', '21564', '23455', '23458', '23459', '25314', '25315', '25316', '27681', '27683', '27790', '27801', '27803', '27804', '27805', '27816', '27840', '27841', '27871', '27904'
);

if($character_class == "1") { // Warrior
    foreach($warrior_spells_array as $spell_id) {
        $statement = $pdo->query("insert into character_spell (guid, spell, active, disabled) values (" . $guid . ", " . $spell_id . ", 1, 0)");
    }
} else if($character_class == "2") { // Paladin
    foreach($paladin_spells_array as $spell_id) {
        $statement = $pdo->query("insert into character_spell (guid, spell, active, disabled) values (" . $guid . ", " . $spell_id . ", 1, 0)");
    }
} else if($character_class == "3") { // Hunter
    foreach($hunter_spells_array as $spell_id) {
        $statement = $pdo->query("insert into character_spell (guid, spell, active, disabled) values (" . $guid . ", " . $spell_id . ", 1, 0)");
    }
} else if($character_class == "4") { // Rogue
    foreach($rogue_spells_array as $spell_id) {
        $statement = $pdo->query("insert into character_spell (guid, spell, active, disabled) values (" . $guid . ", " . $spell_id . ", 1, 0)");
    }
} else if($character_class == "5") { // Priest
    foreach($priest_spells_array as $spell_id) {
        $statement = $pdo->query("insert into character_spell (guid, spell, active, disabled) values (" . $guid . ", " . $spell_id . ", 1, 0)");
    }
} else if($character_class == "7") { // Shaman
    foreach($shaman_spells_array as $spell_id) {
        $statement = $pdo->query("insert into character_spell (guid, spell, active, disabled) values (" . $guid . ", " . $spell_id . ", 1, 0)");
    }
} else if($character_class == "8") { // Mage
    foreach($mage_spells_array as $spell_id) {
        $statement = $pdo->query("insert into character_spell (guid, spell, active, disabled) values (" . $guid . ", " . $spell_id . ", 1, 0)");
    }
} else if($character_class == "9") { // Warlock
    foreach($warlock_spells_array as $spell_id) {
        $statement = $pdo->query("insert into character_spell (guid, spell, active, disabled) values (" . $guid . ", " . $spell_id . ", 1, 0)");
    }
} else if($character_class == "11") { // Druid
    foreach($druid_spells_array as $spell_id) {
        $statement = $pdo->query("insert into character_spell (guid, spell, active, disabled) values (" . $guid . ", " . $spell_id . ", 1, 0)");
    }
}

// Add item enchants to 'sent' items.
// This must be done because mangoszero provides no way to specify the item enchant using the send item command.
$statement = $pdo->query("select item_guid from mail_items where receiver=(select guid from characters where name='" . $character_name . "')");
$rows = $statement->fetchAll(PDO::FETCH_ASSOC);

foreach($rows as $row) {
        $item_guid = $row['item_guid'];
        $statement = $pdo->query("select data from item_instance where guid=" . $item_guid);
        $data_row = $statement->fetch(PDO::FETCH_ASSOC);
        $data = $data_row['data'];
        $data_split = explode(" ", $data);
        $fnd = false;
        foreach($item_arr as $itm) {
                if($data_split[3] == $itm[0]) {
                        $data_split[22] = $itm[2];
                        $fnd = true;
                        break;
                }
        }

        if(!$fnd) {
            echo "Error occured while trying to update item enchants... Item not found!<br>\n";
            echo "Failure!<br>\n";
            return false;
        }

        $data = implode(" ", $data_split);

        $statement = $pdo->query("update item_instance set data='" . $data . "' where guid=" . $item_guid);
}

echo "Success!\n";

?>

