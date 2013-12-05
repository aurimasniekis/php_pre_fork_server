<?php
	
$lock = fopen('/tmp/a.pid', 'c+');
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    die('already running');
}
 
switch ($pid = pcntl_fork()) {
    case -1:
        die('unable to fork');
    case 0: // this is the child process
        break;
    default: // otherwise this is the parent process
        fseek($lock, 0);
        ftruncate($lock, 0);
    	fwrite($lock, $pid);
        fflush($lock);
        exit;
}
 
if (posix_setsid() === -1) {
     die('could not setsid');
}
 
fclose(STDIN);
fclose(STDOUT);
fclose(STDERR);
 
$stdIn = fopen('/dev/null', 'r'); // set fd/0
$stdOut = fopen('/dev/null', 'w'); // set fd/1
$stdErr = fopen('php://stdout', 'w'); // a hack to duplicate fd/1 to 2
 
pcntl_signal(SIGTSTP, SIG_IGN);
pcntl_signal(SIGTTOU, SIG_IGN);
pcntl_signal(SIGTTIN, SIG_IGN);
pcntl_signal(SIGHUP, SIG_IGN);
 
// do some long running work
sleep(300);
