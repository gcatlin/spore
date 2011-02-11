<?php

/*
autoloader
logging
streams
processing/ipc/fork/popen/signals
programs (usage, getopt)


*/

/*
// self pipe stuff
http://cr.yp.to/docs/selfpipe.html
http://stackoverflow.com/questions/282176/waitpid-equivalent-with-timeout
http://stackoverflow.com/questions/340283/avoiding-a-fork-sigchld-race-condition
http://lwn.net/Articles/177897/
http://osiris.978.org/~alex/safesignalhandling.html
http://en.wikipedia.org/wiki/Event_loop
http://www.ruby-forum.com/topic/131859
http://www.mofeel.net/1204-comp-unix-programmer/9145.aspx
http://fixunix.com/unix/538259-sigchild-wait-select.html
http://en.wikipedia.org/wiki/SIGCHLD
http://www.tuxradar.com/practicalphp/16/1/6
http://us3.php.net/pcntl_signal
*/

// from Node.js
// public function stdin() {}
// public function stdout() {}
// public function stderr() {}
// public function args() {}
// public function env() {}
// public function pid() {}
// public function memoryUsage() {}
// public function execPath() {}

// should this be a Subprocess?

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

// what if child exits before wait() is called?


/**
 * An iterable collection of process objects
 */
// @TODO ??? rename ProcessCollection?
class ProcessSet extends SplObjectStorage
{
	/**
	 *
	 */
	protected $unreaped;
	
	/**
	 *
	 */
	public function __construct($processes=null) {
		$this->unreaped = new SplObjectStorage();
		if ($processes !== null) {
			$processes = (array) $processes;
			foreach ($processes as $process) {
				$this->attach($process);
			}
		}
	}

	/**
	 *
	 */
	public function __destruct() {
		// $this->terminate();
		// $this->wait();
	}

	/**
	 *
	 */
	public function add(Process $process) {
		$this->attach($process);
		return $this;
	}

	/**
	 *
	 */
	public function addAll($processes) {
		foreach ($processes as $process) {
			$this->attach($process);
		}
		return $this;
	}

	// /**
	//  *
	//  */
	// public function addClones(Process $prototype, $num_clones) {
	// 	$num_clones = max(1, (int) $num_clones);
	// 	for ($i = 0; $i < $num_clones; $i++) {
	// 		$this->attach(clone $prototype);
	// 	}
	// 	return $this;
	// }
	
	/**
	 *
	 */
	public function attach(Process $process) {
		parent::attach($process);
		$this->unreaped->attach($process);
	}
	
	/**
	 *
	 */
	public function detach(Process $process) {
		parent::detach($process);
		$this->unreaped->detach($process);
	}
	
	/**
	 *
	 */
	public function remove(Process $process) {
		$this->detach($process);
		return $this;
	}
	
	/**
	 *
	 */
	public function removeAll($processes) {
		foreach ($processes as $process) {
			$this->detach($process);
		}
		return $this;
	}
	
	/**
	 * 
	 */
	public function send() {
		//send a message to each process?
	}

	/**
	 *
	 */
	public function start() {
		foreach ($this as $process) {
			$process->start();
		}
		return $this;
	}

	/**
	 *
	 */
	public function terminate() {
		foreach ($this as $process) {
			$process->terminate();
		}
		return $this;
	}

	/**
	 *
	 */
	public function wait($timeout=null) {
		$reaped = array();
		if (count($this->unreaped) > 0) {
			// return immediately
			if ($timeout === 0 || $timeout < 0) {
				$reaped = $this->reap();
			}
			
			// no timeout specified
			elseif ($timeout === null) {
				// @TODO ??? select on a self-pipe?
				while (count($this->unreaped) > 0) {
					$reaped = array_merge($reaped, $this->reap());
					usleep(100000);
				}
			}
			
			// timeout specified
			else {
				// @TODO ??? select on a self-pipe?
				$end = microtime(true) + $timeout;
				while (count($this->unreaped) > 0 && microtime(true) < $end) {
					$reaped = array_merge($reaped, $this->reap());
					usleep(100000);
				}
			}
		}
		return $reaped;
	}

	/**
	 *
	 */
	protected function reap() {
		$reaped = array();
		foreach ($this->unreaped as $process) {
			if (!$process->isAlive()) {
				$reaped[] = $process;
				$this->unreaped->detach($process);
			}
		}
		return $reaped; // @TODO ??? return a ProcessSet?
	}
}

$included_files = get_included_files();
if (__FILE__ == $included_files[0]) {
	function f($message) {
		echo $message;
	}
	
	require dirname(__FILE__).'/Process.php';
	$ps = new ProcessSet();
	$ps->add(new Process('f', "Hello world!\n"));  // auto-starts
	$ps->add(new Process('f', "Hi!\n"));  // auto-starts
	$ps->add(new Process('f', "Hey!\n"));  // auto-starts
	$ps->wait();
}

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


/*
Resources
	http://svn.python.org/view/python/trunk/Lib/multiprocessing/
	http://docs.python.org/library/multiprocessing.html
	http://docs.python.org/library/threading.html
	http://docs.python.org/library/thread.html

	http://java.sun.com/j2se/1.4.2/docs/api/java/lang/Process.html
	http://java.sun.com/j2se/1.4.2/docs/api/java/lang/Thread.html

	http://citeseerx.ist.psu.edu/viewdoc/download?doi=10.1.1.102.7252&rep=rep1&type=pdf

	http://docstore.mik.ua/orelly/perl/cookbook/ch16_01.htm
	http://pleac.sourceforge.net/pleac_php/processmanagementetc.html

	http://docstore.mik.ua/orelly/perl/cookbook/ch17_01.htm
	http://docstore.mik.ua/orelly/perl/cookbook/ch17_13.htm

	http://en.wikipedia.org/wiki/Process_(computing)
	http://en.wikipedia.org/wiki/Spawn_(computing)
	http://en.wikipedia.org/wiki/Filter_(Unix)
	http://en.wikipedia.org/wiki/Pipeline_(Unix)
	http://en.wikipedia.org/wiki/Interprocess_communication
	http://en.wikipedia.org/wiki/Message_passing
	http://en.wikipedia.org/wiki/Process_management_(computing)
	http://en.wikipedia.org/wiki/Berkeley_sockets

	Beej's Guide to Unix IPC
	http://beej.us/guide/bgipc/output/html/multipage/index.html

	Network Programming Unix Pipes
	http://www.cs.sunysb.edu/~cse533/asgn1/pipes.html

	Python: How to fork and return text from the child process
	http://www.myelin.co.nz/post/2003/3/13/#200303135

	Something Like Threading - PHP Process Forking and Interprocess Communication
	http://www.workingsoftware.com.au/index.php?h=WebLog&author_id=1&entry_id=61

	Socket Programming HOWTO
	http://www.amk.ca/python/howto/sockets/

	A Socket-based IPC Tutorial
	http://people.cis.ksu.edu/~singh/CIS725/Fall99/programs/sock_ipc_tut.html

	Using Shared Memory from PHP
	http://onlamp.com/lpt/a/4832

	Inter-Process Communication in PHP
	http://zez.org/article/articleprint/46/
 
 	phpmio
	http://phpmio.svn.sourceforge.net/viewvc/phpmio/trunk/
 */

if (empty($loaded_multiprocessing_lib)) {
	$multiprocessing_dir = (defined('__DIR__') ? __DIR__ : dirname(__FILE__));

	require_once $multiprocessing_dir . '/Connection.php';
	require_once $multiprocessing_dir . '/ConnectionSet.php';
	require_once $multiprocessing_dir . '/Manager.php';
	require_once $multiprocessing_dir . '/Mutex.php';
	require_once $multiprocessing_dir . '/Process.php';
	require_once $multiprocessing_dir . '/ProcessPool.php';
	require_once $multiprocessing_dir . '/ProcessPool_AsyncResult.php';
	require_once $multiprocessing_dir . '/ProcessSet.php';
	require_once $multiprocessing_dir . '/Pool.php';
	require_once $multiprocessing_dir . '/Queue.php';
	require_once $multiprocessing_dir . '/Semaphore.php';
	
	$loaded_multiprocessing_lib = true;
}

class Multiprocessing
{
	public static function getActiveChildren() {
		// Return list of all live children of the current process.
		//
		// Calling this has the side affect of “joining” any processes which have already finished.
		$active = array();
		foreach (Multiprocessing::$children as $process) {
			if ($process->isActive()) {
				$active[] = $process;
			}
		}
		return $active;
	}

	/**
	 *
	 */
	public static function getConnectionPair($duplex=true) {
		// if ($duplex) {
			// returns a pair of Connection objects
			$pipes = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
			if (!$pipes) {
				throw new Exception(socket_strerror(socket_last_error()));
			}
		// } else {
		// 
		// }
		return array(new Connection($pipes[0]), new Connection($pipes[1]));
	}

	public static function ftok() {
		static $i = 0;
		$i++;
		return ftok(__FILE__, chr($i));
	}
}

$included_files = get_included_files();
if (__FILE__ == $included_files[0]) {
	function f($q, $m) {
		$q->put($m);
	}
	
	$m = array(42, null, 'hello');
	$q = new Queue();
	$p = new Process('f', array($q, $m));
	var_dump($q->get());
	$p->wait();

	
	function g($c, $m) {
		$c->send($m);
		$c->close();
	}

	list($c1, $c2) = Multiprocessing::getConnectionPair();
	$m = array(42, null, 'hello');
	$p = new Process('g', array($c2, $m));
	var_dump($c1->recv());
	$p->wait();
}

// http://en.wikipedia.org/wiki/Stream_(computing)
// http://en.wikipedia.org/wiki/Standard_streams
// http://en.wikipedia.org/wiki/Redirection_(computing)
// http://en.wikipedia.org/wiki/Pipeline_(Unix)

// http://www.gnu.org/software/libc/manual/html_mono/libc.html#I_002fO-on-Streams
// http://us.php.net/manual/en/intro.stream.php

// @TODO fpassthru, fscanf, fstat, ftruncate, tmpfile?
// @TODO check if stream is valid (e.g. before calling fseek)
// @TODO? StreamWrappers for head, tail, sed, functionality?

// separate class for select()ing on an array of streams?

/**
 * 
 */
class Stream {
	/**
	 * 
	 */
	protected $stream;
	
	/**
	 * 
	 */
	public static function getTransports() {
		return stream_get_transports();
	}
	
	/**
	 * move to StreamWrapper?
	 */
	public static function getWrappers() {
		return stream_get_wrappers();
	}
	
	/**
	 * in a Network stream?
	 */
	public static function openSocketPair() {
		// make non-blocking by default?
	}
	
	/**
	 * 
	 */
	public static function open() {
		// http://us.php.net/manual/en/transports.inet.php
		// http://us.php.net/manual/en/transports.unix.php
		// use fopen()?
		// use fsockopen()?
		// use stream_socket_client()?
		// use stream_socket_pair()?
		
		// make non-blocking by default?
	}
	
	/**
	 * 
	 */
	public function __construct($stream) {
		if (is_resource($stream) && get_resource_type($stream) == 'stream') {
			$this->stream = $stream;
		} elseif (is_object($stream) && $stream instanceof Stream) {
			$this->stream = $stream->getResource();
		}
		
		$meta_data = stream_get_meta_data($this->stream);
		$this->mode = $meta_data['mode'];
		$this->is_seekable = $meta_data['seekable'];
		$this->is_readable = ($meta_data['mode'] == 'r');//r,+
		$this->is_writable = ($meta_data['mode'] == 'w');//w,a,x,+
	}

	/**
	 * Flush and close this stream.
	 *
	 * This method has no effect if the stream is already closed. Once the 
	 * stream is closed, any operation on the stream (e.g. reading or writing) 
	 * will raise a ValueError.
	 *
	 * As a convenience, it is allowed to call this method more than once; only
	 * the first call, however, will have an effect.
	 */
	public function close() {
		if (is_resource($this->stream)) {
			return fclose($this->stream);
		}
	}
	
	/**
	 * Flush the write buffers of the stream if applicable.
	 *
	 * This does nothing for read-only and non-blocking streams.
	 */
	public function flush() {
		// @TODO verify that the stream is still open?
		return fflush($this->stream);
	}
	
	/**
	 * Returns an iterator for iterating over lines from this stream.
	 */
	public function getIterator() {
		return new StreamIterator($this);
	}
	
	/**
	 * Returns the PHP resource for this stream.
	 */
	public function getResource() {
		return $this->stream;
	}
	
	/**
	 * Returns true if the stream is closed.
	 */
	public function isClosed() {
	}
	
	/**
	 * Returns true if the stream is interactive (i.e. connected to a 
	 * terminal/tty device).
	 */
	public function isInteractive() {
		if ($this->is_interactive === null) {
			$this->is_interactive = posix_isatty($this->stream);
		}
		return $this->is_interactive;
	}
	
	/**
	 * Returns true if the stream can be read from. If false, read() will raise
	 * IOError.
	 */
	public function isReadable() {
		if ($this->is_readable === null) {
			$this->getMetaData();
		}
		return $this->is_readable;
	}
	
	/**
	 * Tells whether this stream is ready to be read from.
	 */
	public function isReadyToRead($timeout=0) {
		return $this->select(array($this->stream), null, $timeout);
	}

	/**
	 * Tells whether this stream is ready to be written to.
	 */
	public function isReadyToWrite($timeout=0) {
		return $this->select(null, array($this->stream), $timeout);
	}

	/**
	 * Returns true if the stream supports random access. If false, seek(), 
	 * tell() and truncate() will raise IOError.
	 */
	public function isSeekable() {
		return $this->is_seekable;
	}
	
	/**
	 * 
	 */
	// public function isTimedOut() {
	// 	// checks meta data
	// }
	
	/**
	 * 
	 */
	public function isWritable() {
		return $this->is_writable;
	}
	
	/**
	 * Reads up to $length bytes from the stream.
	 */
	public function read($length=1) {
		$length = max(0, (int) $length);
		return $this->scan(fread($this->stream, $length));
	}

	/**
	 * Reads the remaining bytes from the stream.
	 */
	public function readAll($length=-1, $offset=0) {
		return stream_get_contents($this->stream, $length, $offset);
	}

	/**
	 * Reads a line from the stream.
	 */
	public function readLine() {
		// stream_get_line()?
		return $this->scan(fgets($this->stream));
	}
	
	/**
	 * Resets the stream.
	 */
	public function seek($offset=0, $whence=SEEK_SET) {
		if ($this->is_seekable) {
			return fseek($this->stream, $offset, $whence);
		}
		return -1;
	}
	
	/**
	 * 
	 */
	// public function setBlocking($block=true) {
	// 	
	// }

	/**
	 * Resets the stream.
	 */
	// public function select($read=null, $write=null, $timeout=null) {
	// 	$tv_sec = ($timeout === null ? null : max(0, (int) $timeout));
	// 	$tv_usec = ($timeout === null ? null : max(0, ($timeout - $tv_sec) * 1000000));
	// 	return (stream_select($read, $write, $e=null, $tv_sec, $tv_usec) == 1);
	// }

	/**
	 * Return the current stream position.
	 */
	public function tell() {
		return ftell($this->stream);
	}

	/**
	 * Truncates the stream to a given length.
	 */
	public function truncate($size) {
		$size = max(0, (int) $size);
		
		if ($this->isWritable()) {
			return ftruncate($this->stream, $size);
		}
	}

	/**
	 * Writes the contents of string to the stream.
	 */
	public function write($string) {
		$length = strlen($string);
		for ($written = 0; $written < $length; $written += $fwrite) {
			$fwrite = fwrite($this->stream, substr($string, $written));
			if ($fwrite === false) {
				return $written;
			}
		}
		return $written;
	}
	
	/**
	 * 
	 */
	// public function appendFilter(Filter $filter) {
	// 	stream_filter_append($this->stream, $filter->getName());
	// }
	
	/**
	 * 
	 */
	// public function prependFilter(Filter $filter) {
	// 	
	// }

	/**
	 * 
	 */
	protected function scan($string) {
		if ($string === false && feof($this->stream)) {
			return false;
		}
		return $string;
	}
}

/**
 * 
 */
class Program {
	/**
	 * 
	 */
	protected $stdin;
	
	/**
	 * 
	 */
	protected $stdout;
	
	/**
	 * 
	 */
	protected $stderr;
	
	/**
	 * 
	 */
	public function __construct() {
		// @TODO pass these to the constructor?
		$this->stdin = new Stream(STDIN);
		$this->stdout = new Stream(STDOUT);
		$this->stderr = new Stream(STDERR);
	}
	
	/**
	 * Display help info.
	 */
	public function getHelp() {
		
	}
	
	/**
	 * Exits
	 */
	public function quit($status) {
		exit($status);
	}
	
	/**
	 * Do useful stuff.
	 */
	public function run($argc=0, $argv=array(), $env=null) {
	}
}
