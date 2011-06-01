<?php

class ProcessTest extends PHPUnit_Framework_TestCase {
	protected $process;
	
	public function setup() {
		$this->process = $this->getProcess();
	}
	
	public function teardown() {
		$this->process->terminate()->wait();
	}
	
	public function getProcess($args=array(), $auto_start=true) {
		return new Process(function() { fgets(STDIN); }, $args, $auto_start);
	}
	
	public function test_ProcessStartsAutomaticallyByDefault() {
		self::assertTrue($this->process->isAlive());
	}
	
	public function test_ProcessCanBeStartedManually() {
		$this->process = $this->getProcess(array(), false);
		self::assertFalse($this->process->isAlive());
		$this->process->start();
		self::assertTrue($this->process->isAlive());
	}
	
	public function test_PidIsAccessible() {
		self::assertNotNull($this->process->getPid());
		self::assertNotEquals(getmypid(), $this->process->getPid());
	}
	
	public function test_ExitStatusIsNullIfProcessHasNotTerminated() {
		self::assertNull($this->process->getExitStatus());
	}
	
	/**
	 * @dataProvider dataProvider_ProcessExitStatusIsNegativeIfTerminatedBySignal
	 */
	public function test_ExitStatusIsNegativeIfTerminatedBySignal($signal_method, $expected_exit_status) {
		$this->process->$signal_method()->wait();
		self::assertEquals($expected_exit_status, $this->process->getExitStatus());
	}
	
	public function dataProvider_ProcessExitStatusIsNegativeIfTerminatedBySignal() {
		return array(
			array('interrupt', -SIGINT),
			array('kill',      -SIGKILL),
			array('terminate', -SIGTERM),
		);
	}
	
	public function test_ExitStatusIsBetweenZeroAnd254IfExitedNormally() {
		$p = Process::exec(function() {});
		self::assertEquals(0, $p->getExitStatus());

		$p = Process::exec(function() { return false; });
		self::assertEquals(0, $p->getExitStatus());

		$p = Process::exec(function() { return true; });
		self::assertEquals(1, $p->getExitStatus());

		$p = Process::exec(function() { return -1; });
		self::assertEquals(0, $p->getExitStatus());

		$p = Process::exec(function() { return 255; });
		self::assertEquals(254, $p->getExitStatus());

		$p = Process::exec(function() { return 'not-an-integer'; });
		self::assertEquals(0, $p->getExitStatus());
	}
	
	public function test_ParametersArePassedToCallback() {
		$p = Process::exec(function($a, $b) { return $a + $b; }, array(1, 2));
		self::assertEquals(3, $p->getExitStatus());
	}
	
	// public function test_ProcessCanBeWrittenTo() {
	// 	$p = new Process('cat');
	// 	$message = 'test';
	// 
	// 	$this->process->stdin->write($message);
	// 	$this->process->stdin->close();
	// 	self::assertEquals($message, $this->process->stdout->readAll());
	// }
	
	// public function test_SignalChild() {
	// }
	// 
	// public function test_CallbackReturnValueIsAccessible() {
	// 	$p = Process::exec(function() {});
	// 	$this->process->wait();
	// 	self::assertNull($this->process->getReturnValue());
	// 
	// 	$p = Process::exec(function() { return 'Hello, world!'; });
	// 	$this->process->wait();
	// 	self::assertEquals('Hello, world!', $this->process->getReturnValue());
	// }
	// 
	// public function test_SendMessageToChild() {
	// 	$p = new Process(function() {});
	// 	$this->process->write('hi')->wait();
	// 	self::assertEquals('', $this->process->read());
	// }
	// 
	
	// process nesting
	// ClosingResourceInParentOrChildClosesForBoth
}