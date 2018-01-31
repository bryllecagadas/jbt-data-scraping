<?php

require_once("classes/Log.php");

class JBTDb {
	protected $PDO;

	public function __construct($username, $password, $database) {
		$log = Log::GetInstance();

		try {
			$this->PDO = new PDO("mysql:dbname=$database", $username, $password);
		} catch (Exception $e) {
			$log->message($e->getMessage());
		}
	}

	public function getCustomer($filters) {
		return $this->get('customers', $filters);
	}

	public function getDistributor($filters) {
		return $this->get('distributors', $filters);
	}


	public function getDistributorCustomer($filters) {
		return $this->get('distributor_customers', $filters);
	}

	private function get($table, $filters) {
		$sql_filters = array();
		foreach ($filters as $name => $value) {
			$sql_filters[] = "$name = :$name";
		}

		$sql_string = "SELECT * FROM $table";
		if ($sql_filters) {
			$sql_string .= " WHERE " . implode(" AND ", $sql_filters);
		}

		$db_statement = $this->PDO->prepare($sql_string);
		$db_statement->execute($filters);
		return $db_statement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function saveReport($data) {
		foreach($data as $distributor) {
			$db_distributor = $this->saveDistributor($distributor);

			if (!isset($distributor['customers'])) {
				continue;
			}

			foreach ($distributor['customers'] as $customer) {
				$db_customer = $this->saveCustomer($customer);

				$this->saveDistributorCustomer(array(
					'distributor_id' => $db_distributor['distributor_id'],
					'customer_id' => $db_customer['customer_id'],
					'aro_num' => $customer['aro_num'],
					'entry_date' => $customer['entry_date'],
					'ship_date' => $customer['ship_date'],
				));
			}
		}
	}

	public function saveCustomer($customer_data) {
		if (!isset($customer_data['first_name']) || !isset($customer_data['last_name'])) {
			return;
		}

		$customer = $this->getCustomer(array(
			'first_name' => $customer_data['first_name'],
			'last_name' => $customer_data['last_name'],
		));

		if (!$customer) {
			unset($customer_data['aro_num']);
			unset($customer_data['entry_date']);
			unset($customer_data['ship_date']);

			$now = date("Y-m-d H:i:s");

			$db_statement = $this->PDO->prepare(
				"INSERT INTO customers (first_name, last_name, type, status, home_phone, city, state, postalcode, created) " . 
				"VALUES (:first_name, :last_name, :type, :status, :home_phone, :city, :state, :postalcode, :created)"
			);

			$customer_data['created'] = $now;
			$db_statement->execute($customer_data);
			$customer = $customer_data;
			$customer['customer_id'] = $this->PDO->lastInsertId();
		} else {
			$customer = $customer[0];
		}

		return $customer;
	}

	public function saveDistributor($distributor_data) {
		if (!isset($distributor_data['fin'])) {
			return;
		}

		$distributor = $this->getDistributor(array('fin' => $distributor_data['fin']));
		
		if (!$distributor) {
			unset($distributor_data['customers']);
			$now = date("Y-m-d H:i:s");

			$distributor_data += array(
				"level" => "",
				"fin" => "",
				"first_name" => "",
				"last_name" => "",
				"signup_date" => "",
				"title" => "",
				"home_phone" => "",
				"cell_phone" => "",
				"distributor" => "",
				"voicecom" => "",
				"email" => "",
				"state" => "",
				"club" => ""
			);

			$db_statement = $this->PDO->prepare(
				"INSERT INTO distributors (level, fin, first_name, last_name, title, signup_date, home_phone, cell_phone, distributor, voicecom, email, state, club, created) " . 
				"VALUES (:level, :fin, :first_name, :last_name, :title, :signup_date, :home_phone, :cell_phone, :distributor, :voicecom, :email, :state, :club, :created)"
			);

			$distributor_data['created'] = $now;
			$db_statement->execute($distributor_data);
			$distributor = $distributor_data;
			$distributor['distributor_id'] = $this->PDO->lastInsertId();
		} else {
			$distributor = $distributor[0];
		}

		return $distributor;
	}

	public function saveDistributorCustomer($distributor_customer_data) {
		if (
			!isset($distributor_customer_data['aro_num']) || 
			!isset($distributor_customer_data['distributor_id']) || 
			!isset($distributor_customer_data['customer_id'])
		) {
			return;
		}

		$distributor_customer = $this->getDistributorCustomer(array('aro_num' => $distributor_customer_data['aro_num']));
		
		if (!$distributor_customer) {
			$now = date("Y-m-d H:i:s");

			$db_statement = $this->PDO->prepare(
				"INSERT INTO distributor_customers (distributor_id, customer_id, aro_num, entry_date, ship_date, created) " . 
				"VALUES (:distributor_id, :customer_id, :aro_num, :entry_date, :ship_date, :created)"
			);

			$distributor_customer_data['created'] = $now;
			$db_statement->execute($distributor_customer_data);
			$distributor_customer = $distributor_customer_data;
			$distributor_customer['distributor_customer_id'] = $this->PDO->lastInsertId();
		} else {
			$distributor_customer = $distributor_customer[0];
		}

		return $distributor_customer;
	}
}