<?php

define('ROOT',dirname(__FILE__).'/');

function loader($class, $metod = "", $debug = false) {
    if ($metod == "") {
        return new $class();
    } else {
        return new $class($metod, null, $debug);
    }
}

function mb_ucfirst($str, $encoding='UTF-8') {
    $str = mb_ereg_replace('^[\ ]+', '', $str);
    $str = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding).
    mb_substr($str, 1, mb_strlen($str), $encoding);
    return $str;
}

function clean_classname($classname) {

    /*НАДО БЕЗОПАСНО ОБРАБОТАТЬ ПЕРЕД EVAL*/
    $notAllow = Array('/', '\\', '"', ':', '*', '?', '<', '>', '|', '%');
    $classname = str_replace($notAllow, '', $classname);
    $classname = mb_substr($classname,0,50,'utf-8');
    $classname = mb_ucfirst($classname);

    //$classname = mysql_escape_string($classname);   //PHP 7 - не работает
    /*ЗАКОНЧИЛИ ОБРЕЗАНИЕ*/
    return $classname;
}

function class_autoload($class_name) {
  $file = ROOT.'classes/'.$class_name.'.class.php';
  if (file_exists($file) == false )
    return false;
  require_once ($file);
}


function class_private($class_name) {
  $file = ROOT.'private/'.$class_name.'.class.php';
  if (file_exists($file) == false )
    return false;
  require_once ($file);
}

function class_default($class_name) {
  $file = ROOT.'default/'.$class_name.'.class.php';
  if (file_exists($file) == false )
    return false;
  require_once ($file);
}

function class_client($class_name) {
    $file = ROOT.'client/'.$class_name.'.class.php';
    if (file_exists($file) == false )
        return false;
    require_once ($file);
}

spl_autoload_register('class_autoload');
spl_autoload_register('class_private');
spl_autoload_register('class_default');
spl_autoload_register('class_client');
