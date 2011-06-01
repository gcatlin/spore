<?php

class Channel {
	protected $closed;
	protected $stream;
	protected $streams;
	
	public function __construct(Stream $stream1=null, Stream $stream2=null) {
		if ($stream1 === null || $stream2 === null) {
			$this->streams = Stream::openSocketPair();
		} else {
			$this->streams = array($stream1, $stream2);
		}

		// @TODO move this somewhere sensible and static
		$this->pid = getmypid();
		$this->closed = false;
	}
	
	public function __destruct() {
		if ($this->stream !== null || !$this->closed) {
			$this->close();
		}
		// @TODO handle non-null $this->streams
	}
	
	/**
	 * Close the channel.
	 *
	 * This is called automatically when the channel is garbage collected.
	 */
	public function close() {
		if ($this->stream === null) {
			$this->initialize();
		}
		
		$this->closed = $this->stream->close();
	}
	
	/**
	 *
	 */
	public function getStream() {
		if ($this->stream === null) {
			$this->initialize();
		}

		return $this->stream;
	}

	/**
	 *
	 */
	public function initialize() {
		if ($this->pid === getmypid()) {
			$this->stream = $this->streams[0];
			unset($this->streams[1]);
		} else {
			$this->stream = $this->streams[1];
			unset($this->streams[0]);
		}
		$this->streams = null;
	}

	/**
	 * Return whether there is any data available to be read.
	 *
	 * EITHER:
	 * If timeout is not specified then an infinite timeout is used. If timeout 
	 * is zero it will return immediately. If timeout is greater than zero then 
	 * this specifies the maximum time in seconds to block.
	 *
	 * OR:
	 * If timeout is not specified then it will return immediately. If timeout 
	 * is a number then this specifies the maximum time in seconds to block. If 
	 * timeout is null then an infinite timeout is used.
	 */
	public function poll($timeout=null) { // @TODO make 0 the default?
		if ($this->stream === null) {
			$this->initialize();
		}

		return $this->stream->isReadyToRead();
	}

	/**
	 * Return an object sent from the other end of the channel using write(). 
	 * 
	 * Raises EOFError if there is nothing left to receive and the other end was 
	 * closed.
	 */	
	public function read() {
		if ($this->stream === null) {
			$this->initialize();
		}

		return unserialize(base64_decode(rtrim($this->stream->readLine())));
	}
	
	/**
	 * Send an object to the other end of the channel which should be read 
	 * using read().
	 *	
	 * The data will be serialized.
	 *
	 * Returns the number of bytes written to the socket.
	 */
	public function write($item) {
		if ($this->stream === null) {
			$this->initialize();
		}
		
		// @TODO don't serialize int, float, string ???

		$serialized = base64_encode(serialize($item)) . "\n";
		return $this->stream->write($serialized);
	}
}
