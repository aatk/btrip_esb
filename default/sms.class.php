<?php

class sms {

  private $id;

  public function __construct($id) {
      //parent::__construct($connectionInfo);   //на тот случай если мы будем наследовать от класса
      $this->id = $id;
  }

  public function Init($param){
    $function = $param[0];
    if (method_exists($this, $function)) {
      $args  = array_slice($param, 1);
      $result = call_user_func_array(array($this, $function), $args);
    } else {
      $result["error"] = "I don't know this function!";
    }
    return $result;
  }

  private function send($to, $message) {
    $url = 'http://sms.ru/sms/send?api_id='.$this->id.'&to='.$to.'&text='.urlencode(iconv("windows-1251","utf-8",$message));
    $body = file_get_contents($url);
    return $body;
  }

}

?>
