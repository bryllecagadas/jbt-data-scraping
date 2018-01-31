<?php

class Config {
	private static $instance = null;

	protected  function __construct() {
		$config = json_decode(file_get_contents('.config'));
		foreach ($config as $name => $value) {
			$this->$name = $value;
		}
	}

	public static function GetInstance() {
		if (!self::$instance) {
			self::$instance = new Config();
		}

		return self::$instance;
	}
}