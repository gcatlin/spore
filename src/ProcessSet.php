<?php

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

	/**
	 *
	 */
	public function attach($process, $data=null) {
		parent::attach($process);
		$this->unreaped->attach($process);
	}
	
	/**
	 *
	 */
	public function detach($process) {
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