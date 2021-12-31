<?php
/*
 *
 * Version 0.0.2
 *
 *
 */
class Cache extends db_connect
{

    private $metod;
    private $request;
    private $md5request;
    private $url;
    private $connectionInfo;

    public function __construct($metod = "", $connectionInfo = null, $intype = "")
    {
        if (isset($connectionInfo)) {
            $this->connectionInfo = $connectionInfo; //Прочитаем настройки подключения к БД
        } else {
            $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем настройки подключения к БД
        }
        parent::__construct($this->connectionInfo);    //на тот случай если мы будем наследовать от класса

        //
        if (is_array($intype)) {
            $type = $intype["type"];
            $server = $type["SERVER"];
            $this->request = $type["phpinput"]; //file_get_contents("php://input");
        } else {
            $type = $intype;
            $server = $_SERVER["SERVER_NAME"];
            $this->request = file_get_contents("php://input");
        }

        $q = $_GET["q"];

        $this->url = $server.$q;
        $this->metod = $_SERVER["REQUEST_METHOD"];
        $this->md5request = md5($this->url."".$this->request);

    }

    public function Run($type = false) {

        if ($type) {
            $result = $this->ExecutiveESB($this->metod, $this->url, $this->request, $this->md5request, true);
        } else {
            $result = $this->ExecutiveESB($this->metod, $this->url, $this->request, $this->md5request);
        }
        return $result;
    }

    public function Cache($Responce) {
        //Кешируем ответ
        $Service = $this->CreateService();
        $result = $this->SaveToCache($this->url, $this->md5, $Service, $this->request, $Responce);
        return $result;
    }

    private function CreateService() {
        /*
        "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
        "name" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
        "url" => array('type' => 'varchar(1500)', 'null' => 'NOT NULL'),

        "cache" => array('type' => 'boolean', 'null' => 'NOT NULL'),
        "lefetime" => array('type' => 'datetime', 'null' => 'NOT NULL'),
        "periodtime" => array('type' => 'datetime', 'null' => 'NOT NULL'),
        */

        $res = false;

        $url = $this->url;
        $URLList = $this->GenareteListURL($url);
        $ServiceList = $this->FindService($URLList);

        if (count($ServiceList) > 0) {
            $res = $ServiceList[0];
        } else {
            //
            $dates = [];
            $dates["name"] = "Autocreate: ".$url;
            $dates["url"] = $url;
            $dates["cache"] = true;
            $dates["lefetime"] = $new_date = mktime(0, 0, 0, 0, 1, 0);;

            $res = $this->insert("services", $dates);
        }

        return $res;
    }

    private function GenareteListURL($url) {
        $list = [];
        $query = parse_url($url);
        $hostname = $query["hostname"];
        $q = $query["path"];

        $q_build = [];
        $q_ar = explode("/", $q);
        foreach ($q_ar as $value) {
            $q_build[] = $value;
            $q_s = implode("/", $q_build);
            $list[]= $hostname.$q_s;
        }
        return $list;
    }

    private function FindService($urllist) {
        $servies = $this->select("services", "*", ["OR" => ["url" => $urllist] ]);
        return $servies;
    }

    private function HaveCache($md5) {
        $have = $this->get("cache", "*", ["md5" => $md5]);
        return $have;
    }

    private function URLSupplier($Service) {
        $Supplier = $this->get("suppliers", "*",["id" => $Service["supplier"] ] );
        return $Supplier["url"];
    }


    private function GenerationURL($URLSupplier, $URL) {
        $URL_ar = explode("/", $URL);
        $URLSupplier_ar = explode("/", $URLSupplier);

        foreach ($URL_ar as $key => $value) {
            if (trim($URLSupplier_ar[$key]) == "") {
                $URLSupplier_ar[$key] = $value;
            }
        }

        $urlret = implode("/", $URLSupplier_ar);
        return $urlret;
    }

    private function ExecutiveCURL($metod, $URLSupplier, $request, $WhiteForEnd) {
        $result = true;

        $ch = [];
        $ch[] = curl_init();

        // устанавливаем опции
        curl_setopt($ch[0], CURLOPT_URL, $URLSupplier);
        curl_setopt($ch[0], CURLOPT_HEADER, 0);
        curl_setopt($ch[0], CURLOPT_CUSTOMREQUEST, $metod);
        curl_setopt($ch[0], CURLOPT_POSTFIELDS, $request);


        //create the multiple cURL handle
        $mh = curl_multi_init();

        // добавляем обработчики
        curl_multi_add_handle($mh,$ch[0]);

        $running = null;
        // выполняем запросы
        do {
            curl_multi_exec($mh, $running);
        } while (($running > 0) && $WhiteForEnd);

        // освободим ресурсы
        curl_multi_remove_handle($mh, $ch[0]);
        curl_multi_close($mh);

        return $result;
    }

    private function SaveToCache($url, $md5, $Service, $request, $Responce) {
        $result = true;

        //$fp = fopen($_FILES[ "file" ][ "tmp_name" ], "rb");
        $tempResponce = tmpfile();
        fwrite($tempResponce, $Responce);
        fseek($tempResponce, 0);

        $tempRequest = tmpfile();
        fwrite($tempRequest, $request);
        fseek($tempRequest, 0);

        $data = [
            "md5" => $md5,
            "datetime" => "NOW()",
            "service" => $Service["id"],
            "url" => $url,
            "request" => $tempRequest,
            "responce" => $tempResponce
        ];
        $this->insert("cache", $data);

        fclose($tempRequest); // происходит удаление файла
        fclose($tempResponce); // происходит удаление файла

        return $result;
    }

    private function GetSubscribers($Service) {
        $result = $this->select("subscribers", "*", ["service" => $Service["id"] ]);
        return $result;
    }

    public function ExecutiveESB($metod, $url, $request, $md5, $getresult = false) {

        $result = ["result" => false];

        $URLList = $this->GenareteListURL($url);
        $ServiceList = $this->FindService($URLList);
        $Responce = "";

        if (count($ServiceList) > 0) {
            foreach ($ServiceList as $Service) {
                if ($Service["processor"] <> "") {
                    //Есть обрабатываемый процессор, всё управление передается ему
                    $NameProcessor = $Service["processor"];
                    $Processor = [];
                    $command = '$Processor = new '.$NameProcessor.'($Service, $url, $q, $request, $md5)';
                    eval($command);
                    $Responce = $Processor->Run();

                } elseif ($Service["cache"] == "true") {
                    //Это кешируемый сервис
                    $CacheData = $this->HaveCache($md5);
                    if ($CacheData === null) {

                        $URLSupplier = $this->URLSupplier($Service);
                        $Responce = $this->ExecutiveCURL($metod, $URLSupplier, $request, true);
                        $this->SaveToCache($url, $md5, $Service, $request, $Responce);

                    } else {
                        $Responce = $CacheData["responce"];
                    }
                } elseif ($Service["cache"] == "false") {
                    //Это сервис точка-точка
                    $URLSupplier = $this->URLSupplier($Service);
                    $Responce = $this->ExecutiveCURL($metod, $URLSupplier, $request, true);
                }

                /* */
                $Subscribers = $this->GetSubscribers($Service);
                if ($Subscribers !== false) {
                    //Есть подписчики, отправим запрос им тоже
                    //$URLSupplier = $this->URLSupplier($Service);
                    foreach ($Subscribers as $Subscriber) {
                        $url_Subscriber = $this->GenerationURL($Subscriber["url"], $url);
                        $this->ExecutiveCURL($metod, $url_Subscriber, $request, false);
                    }
                }

            }
        } else {
            //Нет данных о сервисе просто посмотрим есть ли кеш
            $ResponceCache = $this->HaveCache($md5);
            if ($ResponceCache !== false){
                $Responce = $ResponceCache;
            }
        }

        if ($Responce != "") {
            if ($getresult) {
                $result = ["result" => true, "message" => $Responce];
            } else {
                echo $Responce;
                $result = ["result" => true];
            }
        };

        return $result;
    }

    public function CreateDB()
    {
        /* Описание таблиц для работы с пользователями*/

        $info["suppliers"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "name" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "url" => array('type' => 'varchar(1500)', 'null' => 'NOT NULL'),
        );

        $info["services"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "name" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "url" => array('type' => 'varchar(1500)', 'null' => 'NOT NULL'),

            "cache" => array('type' => 'boolean', 'null' => 'NOT NULL'),
            "lefetime" => array('type' => 'datetime', 'null' => 'NOT NULL'),
            "periodtime" => array('type' => 'datetime', 'null' => 'NOT NULL'),
            "supplier" => array('type' => 'int(11)', 'null' => 'NOT NULL'),
            "processor" => array('type' => 'varchar(90)', 'null' => 'NOT NULL')
        );

        $info["subscribers"] = array(
            "service" => array('type' => 'int(11)', 'null' => 'NOT NULL'),
            "supplier" => array('type' => 'int(11)', 'null' => 'NOT NULL')
        );

        $info["cache"] = array(
            "md5" => array('type' => 'varchar(32)', 'null' => 'NOT NULL'),
            "datetime" => array('type' => 'datetime', 'null' => 'NOT NULL'),
            "service" => array('type' => 'int(11)', 'null' => 'NOT NULL'),
            "url" => array('type' => 'varchar(1500)', 'null' => 'NOT NULL'),
            "request" => array('type' => 'blob', 'null' => 'NOT NULL'),
            "response" => array('type' => 'blob', 'null' => 'NOT NULL')
        );

        $this->create($this->connectionInfo['database_type'], $info);
    }


}


?>