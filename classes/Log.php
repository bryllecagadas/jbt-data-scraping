<?php

class Log {
	private static $instance = null;
	private $logfile = '.log';

	protected  function __construct() {
	}

	public static function GetInstance() {
		if (!self::$instance) {
			self::$instance = new Log();
		}

		return self::$instance;
	}

	public function message($message) {
		error_log(date("[Y-m-d H:i:s] ") . $message . "\n", 3, '.log');
	}
}