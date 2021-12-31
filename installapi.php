<?php
echo "<h1>START INSTALL</h1>\r\n";

require_once 'settings.php'; //подключаем настройки
require_once 'autoload.php'; //подключаем автозагрузку доп.классов

echo "<h1>".$_SERVER["SERVER_NAME"]."</h1>\r\n";
echo "<h1>CREATE DB</h1>\r\n";

$directories = ['classes', 'private', 'default', 'client'];

/* Устанавливаем все БД */
foreach ($directories as $dir) {
    echo "<h2>$dir</h2>\r\n";
    $files1 = scandir($dir);
    foreach ($files1 as $value) {
        if (!in_array($value,array(".",".."))) {
            $class = pathinfo($dir."/".$value);
            $class = (str_ireplace(".class","",$class["filename"]));
            echo "<p>Устанавливаем модуль $class</p>\r\n";
            if (class_exists($class)) {
                if (method_exists($class,'CreateDB')) {
                    $newobject = loader($class);
                    $newobject->CreateDB();
                }
                echo "<p>Закончили с $class</p>\r\n";
            }

        }
    }
}


echo "<h1>Install Module</h1>\r\n";
/* Устанавливаем все преднастройки */
foreach ($directories as $dir) {
    echo "<h2>$dir</h2>\r\n";
    $files1 = scandir($dir);
    foreach ($files1 as $value) {
        if (!in_array($value,array(".",".."))) {
            $class = pathinfo($dir."/".$value);
            $class = (str_ireplace(".class","",$class["filename"]));
            if (class_exists($class)) {
                if (method_exists($class,'InstallModule')) {
                    echo "<p>Настраиваем модуль $class</p>\r\n";
                    $newobject = loader($class);
                    $newobject->InstallModule();
                }
            }

        }
    }
}


echo "<h1>END INSTALL</h1>\r\n";
