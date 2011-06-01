<?php

class Subprocess extends Process {
	public $stderr;
	public $stdin;
	public $stdout;

	protected $proc;
	
	public static function exec($command, $args=null) {
		$p = new Subprocess($command, $args, true);
		return $p->wait();
	}

	public function __destruct() {
		if ($this->started && !$this->terminated) {
			parent::__destruct();

			$this->stdout->close();
			$this->stderr->close();
			@proc_terminate($this->proc);
			@proc_close($this->proc);
		}
	}
	
	public function wait($timeout=null) {
		if ($this->started && !$this->terminated) {
			$this->stdin->close();
			parent::wait($timeout);
		}
		return $this;
	}
	
	protected function run($command, $args) {
		if (!empty($args)) {
			$descriptorspec = array(
				0 => $args[0], // stdin
				1 => $args[1], // stdout
				2 => $args[2], // stderr
			);
		} else {
			$descriptorspec = array(
				0 => array('pipe', 'r'), // stdin is a pipe that the child will read from
				1 => array('pipe', 'w'), // stdout is a pipe that the child will write to
				2 => array('pipe', 'w'), // stderr is a pipe that the child will write to
			);
		}
		
		$command = (strpos($command, 'exec ') !== 0 ? 'exec ' : '') . $command;
		$this->proc = @proc_open($command, $descriptorspec, $pipes);
		if ($this->proc) {
			$status = proc_get_status($this->proc);

			// set these all to non-blocking mode?
			// use null stream()?
			$this->stdin = new Stream($pipes[0], 'w');
			$this->stdout = new Stream($pipes[1], 'r');
			$this->stderr = new Stream($pipes[2], 'r');

			return $status['pid'];
		}
		return -1;
	}
}
