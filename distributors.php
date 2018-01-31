<?php
require_once("classes/Config.php");
require_once("classes/JBTAccess.php");
require_once("classes/JBTDb.php");

$config = Config::GetInstance();
$log = Log::GetInstance();
$jbt = new JBTAccess();
$log->message('JBT Distributor Started');

$countries = array(
	"Australia" => "036", 
	"New Zealand" => "554", 
	"Canada" => "124",
	"USA" => "840"
);

if ($jbt->login($config->username, $config->password, $config->franciseId)) {
	$log->message('JBT Distributor Login Successful');
	foreach ($countries as $country => $value) {
		$log->message("JBT Distributor Changing Country to " . $country);
		if (!$jbt->changeCountry($value)) {
			$log->message("JBT Distributor Failed Changing Country to " . $country);
			continue;
		}

		$log->message('JBT Distributor Download Started');
		$result = $jbt->downloadDistributors(array(
			'downlineSearch$$TL_USER_ID$$entry' => $config->franciseId,
		), '.tmpdistributors');

		if ($result) {
			$log->message('JBT Distributor Download Successful');
			$data = $jbt->parseDistributors('.tmpdistributors');
			if (!$data) {
				$log->message("JBT Distributor File Not Parsed.");
				end_script();
			}
			$db = new JBTDb($config->db_user, $config->db_password, $config->db_name);
			$db->saveReport($data);
			$log->message("JBT Distributor Saved.");
		}
	}
}

end_script();

function end_script() {
	global $log;
	$log->message("JBT Distributor Ended");
	exit;
}
