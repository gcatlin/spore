<?php

/**
 * 
 */
class Stream {
	/**
	 * 
	 */
	protected $is_interactive;
	
	/**
	 * 
	 */
	protected $is_readable;
	
	/**
	 * 
	 */
	protected $is_seekable;
	
	/**
	 * 
	 */
	protected $is_writable;
	
	/**
	 * 
	 */
	protected $stream;
	
	/**
	 * 
	 */
	protected $stream_type;
	
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
	public static function openSocketPair($blocking=false) {
		// make non-blocking by default?
		$streams = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

		$stream0 = new Stream($streams[0], 'r+');
		$stream0->setBlocking($blocking);
		
		$stream1 = new Stream($streams[1], 'r+');
		$stream1->setBlocking($blocking);

		return array($stream0, $stream1);
	}
	
	/**
	 * 
	 */
	public function __construct($stream, $mode='r+') {
		if (is_resource($stream) && get_resource_type($stream) == 'stream') {
			$this->stream = $stream;
		} elseif ($stream === (string) $stream) {
			// @TODO parse url and determine whether fopen, or stream_socket_client should be used
			// @TODO use gethostbyname() to use local DNS cache
			$this->stream = @fopen($stream, $mode);
		} else {
			throw new Exception('Invalid stream supplied');
		}

		// @TODO move to separate method
		$mode = array_fill_keys(str_split($mode), true);
		$this->is_readable = (isset($mode['r']) || isset($mode['+']));
		$this->is_writable = (isset($mode['w']) || isset($mode['+']) || isset($mode['a']) || isset($mode['x']) || isset($mode['c']));

		// http://us.php.net/manual/en/transports.inet.php
		// http://us.php.net/manual/en/transports.unix.php
		// use fopen()? file, http, ftp, php, zlib, data, glob, phar, ssh2, rar, ogg, expect
		// use fsockopen()? tcp, udp, ssl, sslv2, sslv3, tls, unix, udg
		// use stream_socket_client()? instead of fsockopen?
		
		// make non-blocking by default?

		// @TODO create a new stream via fopen?
		// @TODO defer opening the stream?
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
			$this->flush();
			return @fclose($this->stream);
		}
	}
	
	/**
	 * Flush the write buffers of the stream if applicable.
	 *
	 * This does nothing for read-only and non-blocking streams.
	 */
	public function flush() {
		// @TODO verify that the stream is still open?
		return @fflush($this->stream);
	}
	
	/**
	 * Returns an iterator for iterating over lines from this stream.
	 */
	public function getDescriptor() {
		return (int) $this->stream;
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
	 * Returns the absolute path to the current terminal device that is open on
	 * the stream.
	 */
	public function getTerminalName() {
		return @posix_ttyname($this->stream);
	}
	
	/**
	 * Returns a label describing the underlying implementation of the stream.
	 */
	public function getType() {
		if ($this->stream_type === null) {
			$this->_loadMetaData();
		}
		return $this->stream_type;
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
			$this->is_interactive = @posix_isatty($this->stream);
		}
		return $this->is_interactive;
	}
	
	/**
	 * 
	 * 
	 */
	public function isLocal() {
		if ($this->is_local === null) {
			$this->is_local = @stream_is_local($this->stream);
		}
		return $this->is_local;
	}
	
	/**
	 * Returns true if the stream can be read from. If false, read() will raise
	 * IOError.
	 */
	public function isReadable() {
		if ($this->is_readable === null) {
			$this->_loadMetaData();
		}
		return $this->is_readable;
	}
	
	/**
	 * Tells whether this stream is ready to be read from.
	 */
	public function isReadyToRead($timeout=0) {
		return $this->_select($this->stream, null, $timeout);
	}

	/**
	 * Tells whether this stream is ready to be written to.
	 */
	public function isReadyToWrite($timeout=0) {
		return $this->_select(null, $this->stream, $timeout);
	}

	/**
	 * Returns true if the stream supports random access. If false, seek(), 
	 * tell() and truncate() will raise IOError.
	 */
	public function isSeekable() {
		if ($this->is_seekable === null) {
			$this->_loadMetaData();
		}
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
		if ($this->is_writable === null) {
			$this->_loadMetaData();
		}
		return $this->is_writable;
	}
	
	/**
	 * Reads up to $length bytes from the stream.
	 */
	public function read($length=1) {
		$length = max(0, (int) $length);
		return $this->_scan(@fread($this->stream, $length));
	}

	/**
	 * Reads the remaining bytes from the stream.
	 */
	public function readAll($length=-1, $offset=-1) {
		// @TODO allow arbitrary negative lengths and offsets like substr
		
		// var_dump($length, $offset, $this->isSeekable(), $this->stream);
		// var_dump(stream_get_contents($this->stream));
		// if ($offset != 0 && $this->isSeekable()) {
			return @stream_get_contents($this->stream, $length, $offset);
		// }
		
		$string = '';
		while ($this->isReadyToRead()) {
			$string .= $this->readLine();
		}
		return $string;
	}

	/**
	 * Reads a line from the stream.
	 */
	public function readLine() {
		// stream_get_line()?
		return $this->_scan(@fgets($this->stream));
	}
	
	/**
	 * Resets the stream.
	 */
	public function seek($offset=0, $whence=SEEK_SET) {
		if ($this->isSeekable()) {
			return @fseek($this->stream, $offset, $whence);
		}
		return -1;
	}
	
	/**
	 * 
	 */
	public function setBlocking($block=true) {
		return @stream_set_blocking($this->stream, ($block ? 1 : 0));
	}

	/**
	 * Return the current stream position.
	 */
	public function stat() {
		return @fstat($this->stream);
	}

	/**
	 * Return the current stream position.
	 */
	public function tell() {
		return @ftell($this->stream);
	}

	/**
	 * Truncates the stream to a given length.
	 */
	public function truncate($size) {
		$size = max(0, (int) $size);
		
		if ($this->isWritable()) {
			return @ftruncate($this->stream, $size);
		}
	}

	/**
	 * Writes the contents of string to the stream.
	 */
	public function write($string) {
		if ($this->isWritable()) {
			$length = strlen($string);
			for ($written = 0; $written < $length; $written += $fwrite) {
				$fwrite = @fwrite($this->stream, substr($string, $written));
				if ($fwrite === false) {
					return $written;
				}
			}
			return $written;
		}
		return false;
	}
	
	/**
	 * 
	 */
	// public function appendFilter(StreamFilter $filter) {
	// 	stream_filter_append($this->stream, $filter->getName());
	// }
	
	/**
	 * 
	 */
	// public function prependFilter(StreamFilter $filter) {
	// 	
	// }

	/**
	 * 
	 */
	protected function _loadMetaData() {
		$meta_data = @stream_get_meta_data($this->stream);
		$this->stream_type = $meta_data['stream_type'];
		$this->is_seekable = $meta_data['seekable'];

		// @TODO move to separate method
		$mode = array_fill_keys(str_split($meta_data['mode']), true);
		$this->is_readable = (isset($mode['r']) || isset($mode['+']));
		$this->is_writable = (isset($mode['w']) || isset($mode['+']) || isset($mode['a']) || isset($mode['x']) || isset($mode['c']));
	}

	/**
	 * 
	 */
	protected function _select($read=null, $write=null, $timeout=null) {
		$r = ($read === null ? null : array($read));
		$w = ($write === null ? null : array($write));
		$e = $w;
		$tv_sec = ($timeout === null ? null : max(0, (int) $timeout));
		$tv_usec = ($timeout === null ? null : max(0, ($timeout - $tv_sec) * 1000000));
		return (stream_select($r, $w, $e, $tv_sec, $tv_usec) == 1);
	}

	/**
	 * 
	 */
	protected function _scan($string) {
		if ($string === false && @feof($this->stream)) {
			return false;
		}
		return $string;
	}
}
