<?php
//НИКОГДА НЕ СОХРАНЯЙТЕ В СЕССИЮ ОБЪЕКТ!!!!
//НЕ ИСПОЛЬЗУЙТЕ exit() - сломаются сессии.

//error_reporting( E_ERROR );
ini_set('display_errors','Off');
header('Access-Control-Allow-Origin: *');

require_once 'autoload.php'; //подключаем автозагрузку доп.классов
session_start(); //Стартуем сессию только после подключения библиотек
require_once 'settings.php'; //подключаем автозагрузку доп.классов

//Основной приниающий файл REST API isset($_SERVER["REDIRECT_QUERY_STRING"]) &&
if (isset($_REQUEST["q"])) {
    $metod = $_SERVER["REQUEST_METHOD"];
    $q = $_REQUEST["q"];
    unset($_REQUEST["q"]);
    $res = explode("/", $q);

    $class = clean_classname($res[0]);
    $param = array_slice($res, 1);

    if (class_exists($class)) {
        $wClass = loader($class, $metod);
        $result = $wClass->Init($param);

    } elseif (class_exists("Pages")) {


        $wClass = loader("Pages", $metod);
        $result = $wClass->Init($res);

    } else {
        $result['result'] = false;
        $result['error'] = "No such treatment";
        $result['msg'] = "$class";

    }
} elseif (class_exists("Pages")) {
    //станица index

    $metod = $_SERVER["REQUEST_METHOD"];

    $wClass = loader("Pages", $metod);
    $result = $wClass->Init([]);

} else {
    $result['result'] = false;
    $result['error'] = "Error handling to a REST API";
}


unset($_SESSION["db_connect"]);

if ((isset($result["result"]) && ($result["result"] === false)) || ($result === false)) {
    header('HTTP/1.1 500 Internal Server Error');
}

//Выводим результат из благополучно выходим
if (is_string($result)) {
    echo $result;
} elseif ($result instanceof SimpleXMLElement) {
    header('Content-Type: text/xml; charset-utf-8');
    echo $result->asXML();
} elseif (is_bool($result)) {
    //
} else {
    header("Content-type: application/json");
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}
