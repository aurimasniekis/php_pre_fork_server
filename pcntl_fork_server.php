<?php
/*

PHP is UNIX - Simple preforking echo server in PHP
**************************************************

The code here is related to:
http://tomayko.com/writings/unicorn-is-unix
http://jacobian.org/writing/python-is-unix/
http://plasmasturm.org/log/547/ - perl is unix

As of PHP 4.3.0 PCNTL uses ticks as the signal handle callback mechanism, which is much faster than the previous mechanism.
This change follows the same semantics as using "user ticks". You must use the declare() statement to specify the locations in your program,
where callbacks are allowed to occur for the signal handler to function properly.
*/
declare(ticks = 1);

/*
Create a socket, bind it to localhost:4242, and start
istening. Runs once in the parent; all forked children
inherit the socket's file descriptor.
*/
$acceptor = socket_create(AF_INET, SOCK_STREAM, 6);
socket_set_option($acceptor, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($acceptor, '127.0.0.1', 4242);
socket_listen($acceptor, 10);

function shutdown(){
        global $acceptor;
        socket_close($acceptor);
}

function sig_handler(){
        echo "bailing",PHP_EOL;
        exit;
}

/*
Close the socket when we exit the parent or any child process.
*/
register_shutdown_function('shutdown');


/*
Fork you some child processes.
*/
for($i = 0; $i < 3; $i++):
        $pid = pcntl_fork();
        /*
        pcntl_fork() returns 0 in the child process and the child's
        process id in the parent. So if pid == 0 then we're in
        the child process.
        */
        if($pid == 0):
                /*
                now we're in the child process
                */
                $childpid = posix_getpid();
                printf("Child %d listening on 127.0.0.1:4242".PHP_EOL, $childpid);
                        while(1):
                                $socket = socket_accept($acceptor);
                                socket_write($socket, "Child ".$childpid." echo> ");
                                $msg = socket_read($socket,2048);
                                socket_write($socket, $msg);
                                socket_close($socket);
                                printf("Child %d echo'd: '%s'".PHP_EOL, $childpid, trim($msg));
                        endwhile;
                exit($i);
        endif;
endfor;

/*
Trap (Ctrl-C) interrupts, write a note, and exit immediately
in parent. This trap is not inherited by the forks because it
runs after forking has commenced.
*/
pcntl_signal(SIGINT,'sig_handler');

/*
Sit back and wait for all child processes to exit.
*/
while(pcntl_waitpid(0, $status) != -1):
        $status = pcntl_wexitstatus($status);
        printf("Child %s exited".PHP_EOL,$status);
endwhile;
?>
