<?php

class CURLAccess {
	private $ch;

	protected $headers;
	protected $host;
	protected $origin;
	protected $current_page;
	protected $referer;
	protected $useragent;

	protected function connect() {
		$response = curl_exec($this->ch);
		$this->current_page = curl_getinfo($this->ch, CURLINFO_EFFECTIVE_URL);
		return $response;
	}

	protected function disconnect() {
		$return = curl_close($this->ch);
		$this->ch = null;
		return $return;
	}

	protected function downloadTo($file_handler) {
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 0);
		curl_setopt($this->ch, CURLOPT_FILE, $file_handler);
	}

	protected function follow() {
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
	}

	protected function get() {
		curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
	}

	protected function headers($header) {
		$this->headers[] = $header;
	}

	protected function info() {
		return curl_getinfo($this->ch);
	}

	protected function init($url) {
		if (!$this->ch) {
			$this->ch = curl_init($url);
		} else {
			curl_setopt($this->ch, CURLOPT_URL, $url);
		}

		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);

		if ($this->referer) {
			curl_setopt($this->ch, CURLOPT_REFERER, $this->referer);
		}

		$headers = $this->headers;
		if ($this->host) {
			$headers[] = "Host: " . $this->host;
		}

		if ($this->origin) {
			$headers[] = "Origin: " . $this->origin;
		}

		if ($this->useragent) {
			$headers[] = "User-Agent: " . $this->useragent;
		}

		if ($headers) {
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
		}

		curl_setopt($this->ch, CURLOPT_COOKIEFILE, '.cookie');
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, '.cookie');

		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);

		return $this->ch;
	}

	protected function post($data) {
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($data));
	}

	protected function referer($referer) {
		$this->referer = $referer;
	}

	protected function success() {
		$info = $this->info();
		return $info['http_code'] == 200;
	}

	public function __construct() {

	}
}