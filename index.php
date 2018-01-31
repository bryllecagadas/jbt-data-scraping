<?php

require_once("classes/Config.php");
require_once("classes/JBTAccess.php");
require_once("classes/JBTDb.php");
require_once("classes/Log.php");

$config = Config::GetInstance();
$log = Log::GetInstance();
$jbt = new JBTAccess();

$log->message('JBT Report Started');
if ($jbt->login($config->username, $config->password, $config->franciseId)) {
	$log->message('JBT Report Login Successful');

	if (!$jbt->changeCountry("036")) {
		$log->message("JBT Distributor Failed Changing Country to Australia");
		end_script();
	}

	$log->message('JBT Report Download Started');
	$result = $jbt->downloadReport(array(
		'distributors$$TL_USER_ID$$entry' => $config->franciseId,
		'distributors$$LEVEL$$entry' => 'ALL',
		'outputType' => 'comma',
		'distributors$$BEGIN_DATE$$entry' => '01/15/2018',
		'distributors$$END_DATE$$entry' => '01/16/2018',
		// 'distributors$$BEGIN_DATE$$entry' => date("m/d/Y"),
		// 'distributors$$END_DATE$$entry' => date("m/d/Y", strtotime("tomorrow"))
	), '.tmpreport');

	if ($result) {
		$log->message('JBT Report Download Successful');
		$data = $jbt->parseReport('.tmpreport');
		if (!$data) {
			$log->message("JBT Report File Not Parsed.");
			end_script();
		}
		$db = new JBTDb($config->db_user, $config->db_password, $config->db_name);
		$db->saveReport($data);
	}
}

end_script();

function end_script() {
	global $log;
	$log->message("JBT Report Ended");
	exit;
}