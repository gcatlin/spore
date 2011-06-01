<?php

/**
 * Process objects represent activity that is run in a separate process.
 */
class Process {
	/**
	 * Array passed to the command when invoked by the start() method. It
	 * defaults to null, meaning no arguments are passed.
	 */
	protected $args;

	/**
	 * A command to be invoked.
	 */
	protected $command;

	/**
	 * The child's exit status. This will be null if the process has not yet
	 * terminated. A negative value -N indicates that the child was
	 * terminated by signal N.
	 *
	 * This property is read-only accessible via getExitStatus()
	 */
	protected $exit_status;

	/**
	 * The process ID. Before the process is started, this will be null.
	 *
	 * This property is read-only accessible via getPid()
	 */
	protected $pid;

	/**
	 * Whether or not a child process was started.
	 */
	protected $started = false;
	
	/**
	 * Whether or not the child process has terminated.
	 */
	protected $terminated = false;
	
	/**
	 * 
	 */
	public static function exec($command, $args=null) {
		$p = new Process($command, $args, true);
		return $p->wait();
	}

	/**
	 * Creates a new process object.
	 *
	 * 'command' will be invoked by the start() method. By default, no arguments 
	 * are passed to the command.
	 */
	public function __construct($command=null, $args=array(), $auto_start=true) {
		$this->command = $command;
		$this->args = (array) $args;
		
		if ($auto_start) {
			$this->start();
		}
	}

	/**
	 * Terminates the child process and waits for it to complete.
	 */
	public function __destruct() {
		if ($this->started && !$this->terminated) {
			$this->terminate()->wait();
		}
	}

	/**
	 * Return the child's exit status. This will be null if the process has
	 * not yet terminated. A negative value -N indicates that the child was 
	 * terminated by signal N.
	 */
	public function getExitStatus() {
		return $this->exit_status;
	}

	/**
	 * Return the process ID. Before the process is spawned, this will be null.
	 */
	public function getPid() {
		return $this->pid;
	}

	/**
	 * Interrupt the process (using a SIGINT signal).
	 *
	 * Note that exit handlers will not be executed.
	 *
	 * Note that descendant processes of the process will not be killed;
	 * they will simply become orphaned.
	 */
	public function interrupt() {
		return $this->signal(SIGINT);
	}
	
	/**
	 * Tests if the child process is still alive.
	 *
	 * Roughly, a process object is alive from the moment the start() method
	 * returns until the child process terminates. Calling this has the side
	 * effect of checking to see if the child process has terminated.
	 */
	public function isAlive() {
		if ($this->started && !$this->terminated) {
			$this->wait(0);
			return !$this->terminated;
		}
		return false;
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
	 * Send a signal to the process.
	 */
	public function signal($signal) {
		if ($this->started && !$this->terminated) {
			posix_kill($this->pid, (int) $signal);
		}
		return $this;
	}

	/**
	 * Start the process's activity.
	 *
	 * It arranges for the object's command to be invoked in a separate
	 * process. Calling this method more than once has no effect.
	 */
	public function start() {
		if (!$this->started) {
			$pid = $this->run($this->command, $this->args);
			if ($pid == -1) {
				throw new Exception('cannot fork');
			}

			$this->started = true;
			$this->pid = $pid;
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
		$this->signal(SIGTERM);
		return $this;
	}

	/**
	 * Block the calling process until the process whose wait() method is
	 * called terminates or until the optional timeout (in seconds) occurs.
	 *
	 * If timeout is null then there is no timeout. If timeout is zero, it will
	 * return immediately.
	 */
	public function wait($timeout=null) {
		if ($this->started && !$this->terminated) {
			$wait_options = WUNTRACED | ($timeout === null ? 0 : WNOHANG);

			if ($timeout > 0) {
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
				if (pcntl_wifexited($status)) {
					$this->exit_status = pcntl_wexitstatus($status);
				} elseif (pcntl_wifsignaled($status)) {
					$this->exit_status = -1 * pcntl_wtermsig($status);
				} elseif (pcntl_wifstopped($status)) {
					$this->exit_status = -1 * pcntl_wstopsig($status);
				} else {
					$this->exit_status = $status;
				}
				$this->terminated = true;
			}
		}
		
		return $this;
	}

	/**
	 * 
	 */
	protected function run($command, $args) {
		$pid = @pcntl_fork();
		
		// Child process
		if ($pid === 0) {
			$return_value = call_user_func_array($command, $args);
			$exit_status = min(max(0, (int) $return_value), 254);
			exit($exit_status);
		}
		
		return $pid;
	}
}
