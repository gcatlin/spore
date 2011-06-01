<?php

/**
 * Process objects represent activity that is run in a separate process.
 */
class Process
{
	/**
	 * Array passed to the callback when invoked by the run() method. It
	 * defaults to null, meaning no arguments are passed.
	 */
	protected $args;

	/**
	 * A callback to be invoked by the run() method. It defaults to null,
	 * meaning nothing is invoked.
	 */
	protected $callback;

	/**
	 * The process ID. Before the process is spawned, this will be null.
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
	 * Whether or not the child process has exited.
	 */
	protected $is_alive = false;

	/**
	 * Whether or not this is the parent process.
	 */
	protected $is_parent = true;
	
	/**
	 * Whether or not a child process was spawned.
	 */
	protected $started = false;
	
	// protected $id;
	// protected $name;
	// protected static $next_id = 1;

	/**
	 * Creates a new process object.
	 *
	 * 'callback' will be invoked by the run() method. It defaults to null,
	 * meaning nothing is called. By default, no arguments are passed to the
	 * callback.
	 */
	public function __construct($callback=null, $args=null, $auto_start=true) {
		$this->callback = (is_callable($callback) ? $callback : null);
		$this->args = ($args !== null ? (array) $args : null);
		// $this->id = self::$next_id++;
		// $this->name = 'Process-'.self::$next_id++;
		if ($auto_start) {
			$this->start();
		}
	}

	/**
	 * Terminates the child process and waits for it to complete.
	 */
	public function __destruct() {
		// $this->terminate()->wait();
	}

	/**
	 * Return the process ID. Before the process is spawned, this will be null.
	 */
	public function getPid() {
		return $this->pid;
	}

	/**
	 * Return the child’s exit code. This will be null if the process has not yet
	 * terminated. A negative value -N indicates that the child was terminated
	 * by signal N.
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Tests if the process is alive.
	 *
	 * Roughly, a process object is alive from the moment the start() method
	 * returns until the child process terminates. Calling this has the side
	 * effect of checking to see if the child process has exited.
	 */
	public function isAlive() {
		$this->wait(0);
		return $this->is_alive;
	}

	/**
	 * Tests if the process is the child process.
	 */
	public function isChild() {
		return !$this->is_parent;
	}

	/**
	 * Tests if the process is the parent process.
	 */
	public function isParent() {
		return $this->is_parent;
	}

	/**
	 * Method representing the process's activity.
	 *
	 * You may override this method in a subclass. The standard run() method
	 * invokes the callback passed to the object's constructor with arguments.
	 */
	public function run() {
		if (!$this->is_parent && $this->is_alive && $this->callback !== null) {
			return call_user_func_array($this->callback, $this->args);
		}
	}

	/**
	 * Start the process's activity.
	 *
	 * It arranges for the object's run() method to be invoked in a separate
	 * process. It should not be called more than once.
	 */
	public function start() {
		// @TODO parent/child sockets
		
		if ($this->is_parent && !$this->started) {
			$pid = pcntl_fork();

			// Error
			if ($pid == -1) {
				throw new Exception('cannot fork');
			}

			// Parent process
			elseif ($pid > 0) {
				$this->started = true;
				$this->is_alive = true;
				$this->pid = $pid;
			}

			// Child process
			else {
				$this->is_parent = false;
				$this->started = true;
				$this->is_alive = true;
				$this->pid = getmypid();
				$status = $this->run();
				exit($status === null ? 0 : $status);
			}
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
		if ($this->is_parent && $this->is_alive) {
			posix_kill($this->pid, SIGTERM);
		}
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
		if ($this->is_parent && $this->is_alive) {
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
