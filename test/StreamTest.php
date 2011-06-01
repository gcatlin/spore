<?php

class StreamTest extends PHPUnit_Framework_TestCase {
	protected $stream;
	protected $stream1;
	protected $stream2;
	
	public function setup() {
		$this->stream = new Stream('php://memory');
		list($this->stream1, $this->stream2) = Stream::openSocketPair();
	}
	
	public function teardown() {
		$this->stream->close();
		$this->stream1->close();
		$this->stream2->close();
	}
	
	public function test_NewStreamIsOpenedOnInstantiation() {
		$stream_resource = $this->stream->getResource();
		self::assertInternalType('resource', $stream_resource);
		self::assertEquals('stream', get_resource_type($stream_resource));
		self::assertEquals('MEMORY', $this->stream->getType());
		self::assertEquals((int) $stream_resource, $this->stream->getDescriptor());
	}

	public function test_CreatingSocketPairReturnsArrayOfTwoStreamObjects() {
		self::assertInstanceOf('Stream', $this->stream1);
		self::assertEquals('generic_socket', $this->stream1->getType());

		self::assertInstanceOf('Stream', $this->stream2);
		self::assertEquals('generic_socket', $this->stream2->getType());
	}
	
	public function test_ReadReturnsSpecifiedNumberOfBytesFromStream() {
		$this->stream1->write('hello');
		self::assertEquals('h', $this->stream2->read(1));
		self::assertEquals('ell', $this->stream2->read(3));
		self::assertEquals('o', $this->stream2->read(1));
	}
	
	public function test_ReadAllReturnsAllRemaingBytesFromStream() {
		// $this->stream1->setBlocking(false);
		// $this->stream2->setBlocking(false);
		$this->stream1->write('hello');
		self::assertEquals('hello', $this->stream2->readAll());
	}
	
	public function test_WriteWritesToStreamAndReturnsNumberOfBytesWritten() {
		$message = 'hello';
		$message_length = strlen($message);
		self::assertEquals($message_length, $this->stream1->write($message));
		self::assertEquals($message, $this->stream2->read($message_length));
	}
}