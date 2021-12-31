<?php
/**
 * Created by PhpStorm.
 * User: a.tkachenko
 * Date: 25.06.2015
 * Time: 12:40
 */

class Threads {

    private $host   = '';
    private $port   = '';
    private $server = '';

    public function __construct($connectionInfo) {
        $this->host = $connectionInfo['host']; //"phaseit.net:80"
        $this->port = isset($connectionInfo['port']) ? $connectionInfo['port'] : "80";
        $this->server = $this->host.(($this->port == "") ? $this->port : ":").$this->port;
    }

    public function GET($get) {
        $convenient_read_block=8192;
        $timeout=10;
        $id = 0;
        $sockets=array();
        $errno = "";
        $errstr = "";
        //print_r($get);

        $s=stream_socket_client($this->server, $errno, $errstr, $timeout, STREAM_CLIENT_ASYNC_CONNECT|STREAM_CLIENT_CONNECT);
        if ($s) {
            $sockets[$id++]=$s;
            $http_message="GET /".$get." HTTP/1.0\r\nHost: ".$this->host."\r\n\r\n";
            fwrite($s, $http_message);
            $result = $s;
        } else {
            $result = array("error" => $errstr, "errno" => $errno);
        }
        return $result;
    }

}