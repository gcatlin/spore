<?php

require_once __DIR__ . '/ProcessTest.php';

class SubprocessTest extends ProcessTest {
	public function getProcess($args=array(), $auto_start=true) {
		return new Subprocess('cat', $args, $auto_start);
	}
	
	// pipes
}