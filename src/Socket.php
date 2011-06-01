<?php

// http://gnu.april.org/software/libc/manual/html_node/Sockets.html#Sockets
// http://nanoserv.si.kz/current/nanoserv.php

/**
 * 
 */
class Socket
{
	const Event_Connect = 1;//Accept
	const Event_Data = 2;
	const Event_End = 4;
	const Event_Timeout = 8;
	const Event_Drain = 16;
	const Event_Close = 32;
	
	const State_Ready = 'ready';
	const State_Open = 'open';
	const State_Opening = 'opening';
	const State_ReadOnly = 'readOnly';
	const State_WriteOnly = 'writeOnly';
	
	protected $address;
	protected $blocking;
	protected $port;	
	protected $handle;
	protected $listeners = array();
	
	/**
	 * 
	 */
	public static function open() {
		// use fsockopen()
	}
	
	/**
	 * 
	 */
	public static function pair($domain=AF_INET, $type=SOCK_STREAM, $protocol=SOL_TCP) {
		socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $sockets);
		return array(new Socket($sockets[0]), new Socket($sockets[1]));
	}
	
	/**
	 * 
	 */
	public function __construct($domain=AF_INET, $type=SOCK_STREAM, $protocol=SOL_TCP) {
		if (is_resource($domain) && get_resource_type($domain) == 'Socket') {
			$this->handle = $domain;
		} else {
			$this->handle = socket_create($domain, $type, $protocol);
			socket_set_option($this->handle, SOL_SOCKET, SO_REUSEADDR, 1);
			socket_set_nonblock($this->handle);
			$this->blocking = false;
		}
	}
	
	/**
	 * 
	 */
	public function accept() {
		$peer_socket = socket_accept($this->handle);
		// $socket->emit(TcpSocket::Event_Connect);
		return new Socket($peer_socket); // this socket should not be able to accept()
	}
	
	/**
	 * 
	 */
	public function bind($address=0, $port=0) {
		socket_bind($this->handle, $address, $port);
		if ($port === 0 || $address === 0) {
			socket_getsockname($this->handle, $address, $port); // TODO timeouts, errors
		}
		$this->address = $address;
		$this->port = $port;
		return $this;
	}
	
	/**
	 * 
	 */
	public function close() {
		if (!$this->connected) {
		  socket_shutdown($this->handle, 1);//remote host yet can read
		  usleep(500);//wait remote host -- make this smarter?
		  socket_shutdown($this->handle, 0);//close reading
		  socket_close($this->handle);//finaly we can free resource
			$this->connected = false;
		}
		return $this;
	}
	
	/**
	 * 
	 */
	public function connnect($address, $port=0) {
		socket_connect($this->handle, $address, $port);
		$this->connected = true;
		return $this;
	}
	
	/**
	 * 
	 */
	public function getResource() {
		return $this->handle;
	}
	
	/**
	 * 
	 */
	public function isBlocking() {
		return $this->blocking;
	}
	
	/**
	 * 
	 */
	public function listen($backlog=0) {//use SOMAXCONN?
		socket_listen($this->handle, $backlog);
		return $this;
	}
	
	/**
	 * 
	 */
	public function read($length) {
		return socket_read($this->handle, $length);
	}
	
	/**
	 * 
	 */
	public function select($timeout=0) { //rename to isReady()?
		socket_select($r = array($this->handle), $w=null, $e=null, $timeout);
		return $r;
	}
	
	/**
	 * 
	 */
	public function write($data) {
		return socket_write($this->handle, $data);
	}
}

// var_dump($fd);
// var_dump(stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP));
