<?php

class ChannelTest extends PHPUnit_Framework_TestCase {
	public function test_1() {
		$c = new Channel();
		$p = new Process(function($_c) { $_c->write($_c->read()); }, array($c));
		$c->write('abc');
		$p->wait();
		self::assertTrue($c->poll());
		self::assertEquals('abc', $c->read());
	}

	public function test_ChannelStreamsHaveDifferentDescriptors() {
		$c = new Channel();
		$p = new Process(function($_c) { $_c->write($_c->getStream()->getDescriptor()); }, array($c));
		$p->wait();
		self::assertNotEquals((string) $c->getStream()->getDescriptor(), $c->read());
	}
}