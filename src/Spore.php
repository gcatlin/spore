<?php

/*
Basidium?

autoloader
logging
streams
processing/ipc/fork/popen/signals
programs (usage, getopt)

goals:
make it reallly easy to:
- to write cli programs
- fork
- manage sub-processes
- communicate between parent and child
- channels (like go), using named pipes?

$p = new Process(callback|command, args);


// self pipe stuff
http://cr.yp.to/docs/selfpipe.html
http://en.wikipedia.org/wiki/SIGCHLD
http://stackoverflow.com/questions/282176/waitpid-equivalent-with-timeout
http://stackoverflow.com/questions/340283/avoiding-a-fork-sigchld-race-condition
http://lwn.net/Articles/177897/
http://osiris.978.org/~alex/safesignalhandling.html
http://en.wikipedia.org/wiki/Event_loop
http://www.ruby-forum.com/topic/131859
http://www.mofeel.net/1204-comp-unix-programmer/9145.aspx
http://fixunix.com/unix/538259-sigchild-wait-select.html
http://evbergen.home.xs4all.nl/unix-signals.html
http://www.tuxradar.com/practicalphp/16/1/6
http://php.net/pcntl_signal
http://werxltd.com/wp/2010/08/23/process-forking-with-php/

// from Node.js
public function stdin() {}
public function stdout() {}
public function stderr() {}
public function args() {}
public function env() {}
public function pid() {}
public function memoryUsage() {}
public function execPath() {}

should this be a Subprocess?


what if child exits before wait() is called?

// Resources
http://svn.python.org/view/python/trunk/Lib/multiprocessing/
http://docs.python.org/library/multiprocessing.html
http://docs.python.org/library/subprocess.html
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
http://thethoughtlab.blogspot.com/2007/04/non-blocking-io-with-php-mio.html
https://github.com/nrk/phpmio


http://en.wikipedia.org/wiki/Stream_(computing)
http://en.wikipedia.org/wiki/Standard_streams
http://en.wikipedia.org/wiki/Redirection_(computing)
http://en.wikipedia.org/wiki/Pipeline_(Unix)

http://www.gnu.org/software/libc/manual/html_mono/libc.html#I_002fO-on-Streams
http://us.php.net/manual/en/intro.stream.php

@TODO fpassthru, fscanf, fstat, ftruncate, tmpfile?
@TODO check if stream is valid (e.g. before calling fseek)
@TODO? StreamWrappers for head, tail, sed, functionality?

separate class for select()ing on an array of streams?
*/

$included_files = get_included_files();
if ($included_files[0] == __FILE__) {
	// function f($message) {
	// 	echo $message;
	// }
	// 
	// require dirname(__FILE__).'/Process.php';
	// $ps = new ProcessSet();
	// $ps->add(new Process('f', "Hello world!\n"));  // auto-starts
	// $ps->add(new Process('f', "Hi!\n"));  // auto-starts
	// $ps->add(new Process('f', "Hey!\n"));  // auto-starts
	// $ps->wait();
	
	// goal create a program that can launch/control/watch other programs
	$spawner = new Spawner();

}