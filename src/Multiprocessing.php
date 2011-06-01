<?php

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