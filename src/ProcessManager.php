<?php

class ProcessManager {
	protected $self_pipe;
	protected $processes;
	protected $unreaped_processes;
	
	public static function instance() {
		if ($this->instance === null) {
			// 	self::$self_pipe = Stream::openSocketPair();
			// 	pcntl_signal(SIGCHLD, array('Process', 'reapChildren'));
		}
	}
	
	public function sigchld() {
		// registered as SIGCHLD signal handler
		// pushes a byte into self-pipe
	}
	
	public function reapTerminatedChildren() {
		// registered as SIGCHLD signal handler
		// pushes a byte into self-pipe
	}
	
	public function wait() {}
	public function waitpid() {}
	public function waitAny() {}
	public function waitAll() {}
}

// onFork()
// self::$pipes = null;

// onWait()
// $read = array(self::$self_pipe[1]);
// $write = $except = null;
// $tv_sec = ($timeout === null ? null : max(0, (int) $timeout));
// $tv_usec = ($timeout === null ? null : max(0, ($timeout - $tv_sec) * 1000000));
// pcntl_signal_dispatch();
// if (stream_select($read, $write, $except, $tv_sec, $tv_usec)) {
// }
