<?php

require_once('classes/CURLAccess.php');

class JBTAccess extends CURLAccess {
	protected $country_code;
	protected $francise_id;

	public function __construct() {
		parent::__construct();
		$this->host = "www.juiceplusvirtualoffice.com";
		$this->origin = "https://www.juiceplusvirtualoffice.com";
		$this->referer = "https://www.juiceplusvirtualoffice.com/esuite/control/mainView";
		$this->useragent = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36";
	}

	public function close() {
		$this->disconnect();
	}

	public function changeCountry($code) {
		if ($this->country_code == $code) {
			return true;
		}
		$offset = substr(date("O"), 0, 3);
		$date = date("D M d Y H:i:s \G\M\TO ($offset)");
		$this->init("https://www.juiceplusvirtualoffice.com/esuite/distributor/changecountry.soa?pcountry=$code&date=" . urlencode($date));
		$this->get();
		$response = $this->connect();

		preg_match("/\<ajax-response>.*?\<status>success<\/status>/is", $response, $matches);

		if (!empty($matches)) {
			$this->country_code = $code;
			return true;
		}

		return false;
	}

	public function getCountry($response, $return = false) {
		preg_match("/\<select name\=\"pcountry\" id\=\"selChangeCountry\" onchange\=\"changeCountry\(\);\">.*?" . 
			"\<option value\=\"([0-9]*?)\"[ \t\n]*?selected\=\"selected\".*?>.*?\<\/option>" . 
			".*?\<\/select>/is", $response, $matches);
		
		if (!empty($matches[1])) {
			$country_code = $matches[1];
		}

		if (!isset($country_code)) {
			return false;
		}

		if ($return) {
			return $country_code;
		}

		$this->country_code = $country_code;
	}

	public function login($username, $password, $francise_id) {
		$this->init("https://www.juiceplusvirtualoffice.com/esuite/control/mainView");
		
		$this->post(array(
			"USERNAME" => $username, 
			"PASSWORD" => $password
		));

		$response = $this->connect();
		$this->getCountry($response);

		$result = (bool) $this->success() && strpos($response, $francise_id) !== FALSE;

		if ($result) {
			$this->francise_id = $francise_id;
		}

		return $result;
	}

	public function downloadDistributors($data, $filename) {
		$this->init("https://www.juiceplusvirtualoffice.com/esuite/control/drbConstraintsProcess");

		// Defaults are based on actual captured inputs when form is submitted
		$defaults = array(
			'cameFrom' => 'Constraints.jsp',
			'goTo' => 'output',
			'reportName' => 'downlineSearch',
			'groups' => 'false',
			'finVarName' => 'null',
			'downlineSearch$$LEVELS_DEEP$$entry' => 'ALL',
			'downlineSearch$$FIRST_NAME$$entry' => '',
			'downlineSearch$$FIRST_NAME$$precision' => 'startsWith',
			'downlineSearch$$LAST_NAME$$entry' => '',
			'downlineSearch$$LAST_NAME$$precision' => 'startsWith',
			'downlineSearch$$TL_USER_ID$$entry' => 'AU015932',
			'downlineSearch$$RANK_ID$$entry' => 'ALL',
			'downlineSearch$$PV_START$$entry' => '',
			'downlineSearch$$PV_END$$entry' => '',
			'downlineSearch$$BEGIN_DATE$$entry' => '',
			'downlineSearch$$END_DATE$$entry' => '',
			'downlineSearch$$CLUB_LEVEL$$list' => '',
			'downlineSearch$$CITY$$entry' => '',
			'downlineSearch$$CITY$$precision' => 'startsWith',
			'downlineSearch$$STATE_CODE$$entry' => '',
			'downlineSearch$$STATE_CODE$$precision' => 'startsWith',
			'downlineSearch$$ZIP$$entry' => '',
			'downlineSearch$$DISTANCE$$entry' => '',
			'downlineSearch$$AREA_CODE$$entry' => '',
			'downlineSearch$$AREA_CODE$$precision' => 'startsWith',
			'outputType' => 'comma',
		);

		$post = array_merge($defaults, $data);
		$this->post($post);

		$file = fopen($filename, 'w');
		$this->downloadTo($file);
		$this->connect();
		$info = $this->info();
		fclose($file);
		$success = (bool) $this->success();
		$this->disconnect();

		$head = file_get_contents('.tmpfile',false, null, 0, 300);
		return $success && 
			strpos($head, 'The information on this report is as of') !== FALSE;
	}

	public function downloadReport($data, $filename) {
		$this->init("https://www.juiceplusvirtualoffice.com/esuite/control/drbConstraintsProcess");

		// Defaults are based on actual captured inputs when form is submitted
		$defaults = array(
			"cameFrom" => "Constraints.jsp",
			"goTo" => "output",
			"reportName" => "allPreferredCustomers",
			"groups" => false,
			"finVarName" => null,
			'distributors$$FRONTLINE_USER$$entry' => -1,
			'1$$radio' => 'distributors$$USER_RADIO_BUTTON$$2',
			'distributors$$TL_USER_ID$$entry' => 'AU015932',
			'distributors$$LEVEL$$entry' => 'ALL',
			'distributors$$USER_LOGIN_ID$$entry' => '',
			'distributors$$RANK_ID$$list' => '',
			'distributors$$PRODUCT_ID$$list' => '',
			'distributors$$BEGIN_DATE$$entry' => '', // mm/dd/Y
			'distributors$$END_DATE$$entry' => '', // mm/dd/Y
			'distributors$$CUST_CITY$$entry' => '',
			'distributors$$CUST_ZIP$$entry' => '',
			'distributors$$AREA_CODE$$entry' => '',
			'outputType' => 'comma'
		);

		$post = array_merge($defaults, $data);
		$this->post($post);

		$file = fopen($filename, 'w');
		$this->downloadTo($file);
		$this->connect();
		$info = $this->info();
		fclose($file);
		$success = (bool) $this->success();
		$this->disconnect();

		$head = file_get_contents('.tmpfile',false, null, 0, 300);
		return $success && 
			strpos($head, 'The information on this report is as of') !== FALSE;
	}

	public function parseDistributors($filename) {
		$contents = file($filename);
		if (!$contents) {
			return;
		}

		$data = array();
		for ($index = 0; $index < count($contents); $index++) {
			$line = $contents[$index];
			if (strpos($line, "Level\t") === 0) {
				$index++;
				$raw = explode("\t", $contents[$index]);
				while(isset($raw[0]) && is_numeric($raw[0])) {
					$distributor = array(
						'level' => $raw[0],
						'fin' => $raw[1],
						'first_name' => $raw[2],
						'last_name' => $raw[3],
						'title' => $raw[4],
						'signup_date' => date('Y-m-d', strtotime($raw[5])),
						'home_phone' => $raw[6],
						'cell_phone' => $raw[7],
						'email' => $raw[8],
						'state' => $raw[9],
						'club' => $raw[10],
						'customers' => array()
					);

					$data[] = $distributor;

					$index++;
					$raw = explode("\t", $contents[$index]);
				}
			}
		}

		return $data;
	}

	public function parseReport($filename) {
		$contents = file($filename);
		if (!$contents) {
			return;
		}

		$data = array();
		for ($index = 0; $index < count($contents); $index++) {
			$line = $contents[$index];
			$distributor = array();
			if (strpos($line, "Level\t") === 0) {
				$index++;
				$raw = explode("\t", $contents[$index]);

				if (strpos($raw[7], '@') !== FALSE) {
					$email = $raw[7];
					$voicecom = $raw[8];
				} else {
					$email = $raw[8];
					$voicecom = $raw[7];
				}
				
				$distributor = $raw[6] == "N/A" ? "" : $raw[6];
				$distributor = array(
					'level' => $raw[0],
					'fin' => $raw[1],
					'first_name' => $raw[2],
					'last_name' => $raw[3],
					'title' => $raw[4],
					'home_phone' => $raw[5],
					'distributor' => $distributor,
					'voicecom' => $voicecom,
					'email' => $email,
					'customers' => array()
				);

				$index += 2;

				while (strpos($contents[$index], "\t") === 0) {
					$raw = explode("\t", $contents[$index]);
					
					$distributor['customers'][] = array(
						'aro_num' => $raw[1],
						'first_name' => $raw[2],
						'last_name' => $raw[3],
						'type' => $raw[4],
						'status' => $raw[5],
						'entry_date' => date("Y-m-d", strtotime($raw[6])),
						'ship_date' => date("Y-m-d", strtotime($raw[7])),
						'home_phone' => $raw[8],
						'city' => $raw[9],
						'state' => $raw[10],
						'postalcode' => $raw[11],
					);
					$index++;
				}

				$data[] = $distributor;
			}
		}

		return $data;
	}

}