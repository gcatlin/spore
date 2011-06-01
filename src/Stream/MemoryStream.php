<?php

/**
 * StringStream??
 */
class MemoryStream extends Stream {
	/**
	 * 
	 */
	public function __construct($stream) {
		if (!is_resource($stream)) {
			$this->stream = fopen('php://memory', 'w+b');
		}
		parent::__construct($stream);
	}
}