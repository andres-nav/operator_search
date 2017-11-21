#!/usr/bin/php 
<?php

$errno = 0;
$errstr= "";
$fh = stream_socket_server("tcp://0.0.0.0:1922", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
if ($fh === FALSE)
  die("stream_socket_server() failed\n");

$ba = array();
$clients=array();
stream_set_blocking($fh, 0);
define(EOF, "\n");
define(LOGIN_LIMIT, "15724800");

$redis = new Redis();
$doconnect = true;
for (;;)
{
  if ($doconnect) {
      $redis->connect('redis', 6379, 1, NULL, 1000); // 1 sec timeout, 1000ms delay between reconnection attempts.
      $doconnect = false;
  }
  $a = array($fh);
  $n = stream_select($a, $ba, $ba, 4);
  while (count($a))
  {
    $s = array_pop($a);
    if ($s == $fh)
    {
      $fd = stream_socket_accept($fh);
      if ($fd !== FALSE) 
      {
        stream_set_blocking($fd, 1);
        $req = fgets($fd, 4096);
        echo("req:$req\n");

        if (preg_match("#^get\s+([A-Za-z0-9_.-]+)@pathfinder\.gr#i", $req, $ma) & count($ma) == 2)
        {
          $username=strtolower($ma[1]);
          try {
            if ($redis->exists($username)) {
              $lastlogin=$redis->get($username);
              #echo($lastlogin." - ".time()." = ".(time() - $lastlogin));
              if ((time() - $lastlogin) > LOGIN_LIMIT) {
                echo("User Inactive -> " . $username . "\n");
                fputs($fd, "200 550 User Inactive".EOF);
              } else {
                echo("200 OK\n");
                $ret_text="200 OK".PHP_EOL;
                fputs($fd, $ret_text, strlen($ret_text));
              }
            } else {
              echo("User not found -> " . $username . "\n");
              fputs($fd, "200 551 User not found".EOF);
            }
          } catch( RedisException $re) {
            echo($re->getMessage());
            fputs($fd, "200 450 Temporary User Lookup Error".EOF, 120);
            $doconnect=true;
          }
        } else {
          echo("Not a Pathfinder email address\n");
          fputs($fd, "552 552 Invalid" .EOF);
        }
        fflush($fd);
        fclose($fd);
      } else {
        echo("Failure");
      }
    }
  }
}
?>

