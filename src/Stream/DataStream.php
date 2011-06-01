<?php

/**
 * StringStream??
 */
class DataStream extends Stream {
	/**
	 * 
	 */
	public function __construct($stream) {
		if (!is_resource($stream)) {
			$data_url = 'data:text/plain,' . (string) $stream; // @TODO urlencode? base64 encode?
			$this->stream = fopen($data_url);
		}
		parent::__construct($stream);
	}
	
	/**
	 * override?
	 */
	// public function open() {
	// 	$this->stream = fopen($data_url);
	// }
}