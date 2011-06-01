<?php

// http://docs.python.org/library/subprocess.html

//both
	// args, opened/started, is_alive, pid, status, signal/kill/term/int(), wait()
// piped
	// command, process, stdin/stdout/stderr?
// forked
	// callback, is_parent

// add a SubprocessCollection class?

/**
 * ForkedSubprocess vs PipedSubprocess?
 */
class Subprocess {
	/**
	 * 
	 */
	protected $command;

	/**
	 * Whether or not the process is alive (i.e. has not yet terminated).
	 */
	protected $is_alive;

	/**
	 * Whether or not the process was opened successfully.
	 */
	protected $opened;

	/**
	 * A resource
	 */
	protected $process;

	/**
	 * The process ID. Before the process is opened, this will be null.
	 *
	 * This property is read-only accessible via getPid()
	 */
	protected $pid;
	
	/**
	 * The child’s exit code. This will be null if the process has not yet
	 * terminated. A negative value -N indicates that the child was
	 * terminated by signal N.
	 *
	 * This property is read-only accessible via getStatus()
	 */
	protected $status;

	/**
	 * 
	 */
	public $stderr;

	/**
	 * 
	 */
	public $stdin;

	/**
	 * 
	 */
	public $stdout;

	/**
	 * 
	 */
	public static function exec($command, $args) { //exec? call?
		$p = new Subprocess($command, $args);
		$p->open();
		$p->wait();
		return $p;
		// return array($p->stdout->readAll(), $p->stderr->readAll());
	}
	
	/**
	 * 
	 */
	public static function fork($callback, $args) {
		return new ForkedSubprocess();
	}
	
	/**
	 * Connect to another process
	 */
	public static function pipe($command, $args) {
		return new PipedSubprocess($command, $args);
	}
	
	/**
	 * Connect to another process
	 */
	public static function popen($command, $args) {
		return new PipedSubprocess($command, $args);
	}
	
	/**
	 * Connect to another process
	 */
	public static function proc_open($command, $args) {
		return new Subprocess($command, $args);
	}
	
	/**
	 * 
	 */
	public function __construct($command, $args=array(), $pipes=null, $auto_start=false) {
		// http://php.net/manual/en/function.escapeshellcmd.php
		// http://php.net/manual/en/function.escapeshellarg.php
		// $command could be a callback or a string
		$this->command = $command;
	}
	
	/**
	 * 
	 */
	public function __destruct() {
		$this->close();
	}
	
	/**
	 * 
	 */
	public function close() {
		if ($this->process !== null) {
			$this->stdin->close();
			$this->stdout->close();
			$this->stderr->close();

			if (is_resource($this->process)) {
				proc_close($this->process);
			}
		}
	}
	
	/**
	 * Return the process ID. Before the process is opened, this will be null.
	 */
	public function getPid() {
		return $this->pid;
	}
	
	/**
	 * Return the exit code of the process. This will be null if the process has
	 * not yet terminated. A negative value -N indicates that the process was 
	 * terminated by signal N.
	 */
	public function getStatus() {
		return $this->status;
	}
		
	/**
	 * 
	 */
	public function interrupt() {
		return $this->signal(SIGINT);
	}
	
	/**
	 * Tests if the process is alive.
	 *
	 * Roughly, a process object is alive from the moment the start() method
	 * returns until the child process terminates. Calling this has the side
	 * effect of checking to see if the child process has exited.
	 */
	public function isAlive() { // same as subprocess.poll()
		$this->wait(0);
		return $this->is_alive;
	}

	/**
	 * Kill the process (using a SIGKILL signal).
	 *
	 * Note that exit handlers will not be executed.
	 *
	 * Note that descendant processes of the process will not be killed;
	 * they will simply become orphaned.
	 */
	public function kill() {
		return $this->signal(SIGKILL);
	}
	
	/**
	 * 
	 */
	public function open() {
		$descriptorspec = array(
			0 => array('pipe', 'r'),  // stdin is a pipe that the child will read from
			1 => array('pipe', 'w'),  // stdout is a pipe that the child will write to
			2 => array('pipe', 'w'),  // stderr is a pipe that the child will write to
		);
		
		$this->process = proc_open($this->command, $descriptorspec, $pipes);

		// set these all to non-blocking mode?
		$this->stdin = new Stream($pipes[0]);
		$this->stdout = new Stream($pipes[1]);
		$this->stderr = new Stream($pipes[2]);
		
		$status = proc_get_status($this->process);
		$this->pid = $status['pid'];
	}
	
	/**
	 * Send a signal to the process.
	 */
	public function signal($signal) {
		if ($this->is_alive) {
			posix_kill($this->pid, $signal);
		}
		return $this;
	}
	
	/**
	 * Terminate the process (using a SIGTERM signal).
	 *
	 * Note that exit handlers will not be executed.
	 *
	 * Note that descendant processes of the process will not be terminated;
	 * they will simply become orphaned.
	 */
	public function terminate() {
		return $this->signal(SIGTERM);
	}
	
	/**
	 * Block the calling process until the process whose wait() method is
	 * called terminates or until the optional timeout (in seconds) occurs.
	 *
	 * If timeout is null then there is no timeout. If timeout is zero, it will
	 * return immediately.
	 */
	public function wait($timeout=null) {
		if ($this->is_alive) {
			$wait_options = WUNTRACED | ($timeout === null ? 0 : WNOHANG);

			if ($timeout > 0) {
				// @TODO ??? select on a self-pipe
				// $read = $except = array($this->socket);
				// $write = null;
				// $tv_sec = ($timeout === null ? null : max(0, (int) $timeout));
				// $tv_usec = ($timeout === null ? null : max(0, ($timeout - $tv_sec) * 1000000));
				// $result = stream_select($read, $write, $except, $tv_sec, $tv_usec);
				$end = microtime(true) + $timeout;
				while (microtime(true) < $end) {
					$pid = pcntl_waitpid($this->pid, $status, $wait_options);
					if ($pid > 0) {
						break;
					}
					usleep(100000);
				}
			} else {
				$pid = pcntl_waitpid($this->pid, $status, $wait_options);
			}

			if ($pid == $this->pid) {
				$this->is_alive = false;
				$this->status = (pcntl_wifsignaled($status) ? -1 * pcntl_wtermsig($status) : $status);
			}
		}
		return $this;
	}
}
