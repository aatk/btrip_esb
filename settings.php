<?php
    unset($_SESSION["db_connect"]);
    
    require 'default/PHPMailer/Exception.php';
    require 'default/PHPMailer/PHPMailer.php';
    require 'default/PHPMailer/SMTP.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    function adddinamicsetting (&$settings)
    {
        $dir = $_SERVER["DOCUMENT_ROOT"];
        $exdir = $dir . "/private/settings";
        if (!file_exists($dir . "/private"))
        {
            mkdir($dir . "/private");
        }
        if (!file_exists($dir . "/private" . "/settings"))
        {
            mkdir($dir . "/private" . "/settings");
        }
        $files1 = scandir($exdir);
        foreach ($files1 as $value)
        {
            if (!in_array($value, [".", ".."]))
            {
                $settingsjsonfile = $exdir . "/" . $value;
                
                $content = file_get_contents($settingsjsonfile);
                $json = json_decode($content, true);
                
                if (isset($json["Info"]))
                {
                    $setsetting = [];
                    foreach ($json["Info"] as $val)
                    {
                        $setsetting = array_merge($setsetting, $val);
                    }
                    if (isset($json["Name"]))
                    {
                        $settings[$json["Name"]] = $setsetting;
                    }
                    
                    if (isset($json["Name"]))
                    {
                        $settings[$json["Name"]] = $setsetting;
                    }
                }
            }
        }
    }
    
    $agent = explode(".", $_SERVER["HTTP_HOST"])[0];
    
    $settings["cms_system"] = "i4b_cms";
    $settings["agent"] = $agent;
    
    //Динамическое подключение настроек
    adddinamicsetting($settings);
    
    
    $settings["connectionInfo"] = [
        "database_type" => "mysql",
        "server" => "localhost",
        "database_name" => "",
        "username" => "",
        "password" => "",
        'charset' => 'utf8',
    ];
    
    
    $ApMailerConfig = [
        'smtp_username' => '',  //Смените на адрес своего почтового ящика.
        'smtp_port' => '465', // Порт работы.
        'smtp_host' => '',  //сервер для отправки почты mail.btrip.ru
        'smtp_password' => '',  //Измените пароль
        'smtp_debug' => true,  //Если Вы хотите видеть сообщения ошибок, укажите true вместо false
        'smtp_charset' => 'utf-8',    //кодировка сообщений. (windows-1251 или utf-8, итд)
        'smtp_from' => '' //Ваше имя - или имя Вашего сайта. Будет показывать при прочтении в поле "От кого"
    ];
    
    $settings['MailerConfig'] = $ApMailerConfig;
    
    if (isset($_SESSION["i4b"]))
    {
        $saa = $_SESSION["i4b"];
        if (isset($saa["auth"]))
        {
            $settings["auth"] = $saa["auth"];
        }
        else
        {
            $settings["auth"] = [];
        }
    }
    else
    {
        $settings["auth"] = [];
    }
    
    $_SESSION["i4b"] = $settings;
