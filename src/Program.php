<?php

// make cli options easy
// handle cli option parsing before run() is called
// 

/**
 * 
 */
abstract class Program {
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
	abstract public function run($argc=0, $argv=array(), $env=null);
}
