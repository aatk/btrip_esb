<?php


class Reports extends ex_class
{

    private $metod;
    private $auth;
    private $server1C;

    public function __construct($metod = "")
    {
        $this->auth = base64_encode("Логин:Пароль");            //TODO Логин и пароль к системе 1С
        $this->server1C = "http://test.test.test:8080/UT/hs/";       //TODO Адрес опубликованного сервера 1с
        parent::__construct($_SESSION["i4b"]["connectionInfo"]);
        $this->metod = $metod;
    }

    public function Init($param)
    {

        $result = array();

        if (($this->metod == "POST") && (isset($param[0])) && (isset($param[1]))) {
            if ($param[0] == "createid") {
                //$result = $this->createid($param[1]);
            }
        } elseif (($this->metod == "GET")) {
            if ($param[0] == "findocs") {
                $result = $this->findocs($param[1]);
            } elseif ($param[0] == "etikets") {
                $result = $this->etikets($param[1]);
            } elseif ($param[0] == "findoc") {
                $result = $this->findoc($param[1], $param[2]);
            } elseif ($param[0] == "etiket") {
                $result = $this->etiket($param[1], $param[2]);
            } elseif ($param[0] == "reports") {
                $result = $this->getreports($param[1]);
            } elseif ($param[0] == "generatereport") {
                $result = $this->generatereport($param[1]);
            } elseif ($param[0] == "downloadreport") {
                $result = $this->downloadreport($param[1]);
            } elseif ($param[0] == "mytravels") {
                $result = $this->mytravels($param[1]);
            } elseif ($param[0] == "mytravel") {
                $result = $this->mytravel($param[1], $param[2]);
            } elseif ($param[0] == "getdoc") {
                $result = $this->getdoc($param[1]);
            } elseif ($param[0] == "myautorizations") {
                $result = $this->myautorizations($param[1]);
            } elseif ($param[0] == "myautorization") {
                $result = $this->myautorization($param[1], $param[2]);
            }
        }

        return $result;
    }

    private function file_force_download($file, $delfile)
    {

        if (file_exists($file)) {
            // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
            // если этого не сделать файл будет читаться в память полностью!
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            $filename = iconv("cp1251", "utf-8", basename($file));

            // заставляем браузер показать окно сохранения файла
            header('Content-Description: File Transfer');
            //header("Content-Type: application/pdf");//
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename*=UTF-8' . urlencode($filename));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            // читаем файл и отправляем его пользователю
            readfile($file);
            if ($delfile === true) {
                unlink($file);
            };
            exit;
        } else {
            print_r(dirname($_SERVER["SCRIPT_FILENAME"]));
        }
    }

    private function file_download($file, $delfile)
    {
        if (file_exists($file)) {
            // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
            // если этого не сделать файл будет читаться в память полностью!
            if (ob_get_level()) {
                ob_end_clean();
            }

            $filename = iconv("cp1251", "utf-8", basename($file));

            // заставляем браузер показать окно сохранения файла
            header('Content-Description: File Transfer');
            header("Content-Type: application/pdf");//
            //header('Content-Type: application/octet-stream');
            header('Content-Disposition: filename*=UTF-8'.urlencode($filename));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            // читаем файл и отправляем его пользователю
            readfile($file);
            if ($delfile == true) {
                unlink($file);
            };
            exit;
        }
    }

    private function findocs($nom)
    {

        /*
        $content = $_GET;
        unset($content["q"]);

        $path = $this->server1C."findocs/" . $nom;
        //$auth = base64_encode("Администратор:");
        $opts = [
            'http' => [
                'method' => "GET",
                'header' =>
                    "Authorization: Basic $this->auth\r\n" .
                    "Content-Type: application/json; charset=utf-8\r\n",
                'content' => json_encode($content)
            ]
        ];
        $json = file_get_contents($path, 0, stream_context_create($opts));

        if ($json === false) {
            $json["result"] = false;
        };
        */

        if ($nom == 1) {
            $elems = [];
            $elems[] = ["guid" => "1", "number" => "00005555", "date" => "01.07.2017", "type" => "s", "typename" => "Счет", "summ" => "100000", "summtext" => "100 000", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "3", "number" => "00004755", "date" => "02.07.2017", "type" => "sf", "typename" => "Счет-Фактура", "summ" => "150000", "summtext" => "150 000", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "2", "number" => "00008975", "date" => "03.07.2017", "type" => "a", "typename" => "Акт", "summ" => "45600", "summtext" => "45 600", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "1", "number" => "00010395", "date" => "04.07.2017", "type" => "s", "typename" => "Счет", "summ" => "899400", "summtext" => "899 400", "description" => "Любой текст описывающий услуги или электронный билет"];
        } elseif ($nom == 2) {
            $elems = [];
            $elems[] = ["guid" => "2", "number" => "00034488", "date" => "05.07.2017", "type" => "s", "typename" => "Акт", "summ" => "100000", "summtext" => "343 300", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "3", "number" => "00377528", "date" => "06.07.2017", "type" => "sf", "typename" => "Счет-Фактура", "summ" => "150000", "summtext" => "444 000", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "2", "number" => "00088221", "date" => "07.07.2017", "type" => "a", "typename" => "Акт", "summ" => "45600", "summtext" => "22 600", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "3", "number" => "03837220", "date" => "08.07.2017", "type" => "s", "typename" => "Счет-Фактура", "summ" => "899400", "summtext" => "678 400", "description" => "Любой текст описывающий услуги или электронный билет"];
        } elseif ($nom == 3) {
            $elems = [];
            $elems[] = ["guid" => "2", "number" => "00003355", "date" => "09.07.2017", "type" => "s", "typename" => "Акт", "summ" => "100000", "summtext" => "23 320", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "2", "number" => "00088372", "date" => "10.07.2017", "type" => "sf", "typename" => "Акт", "summ" => "150000", "summtext" => "153 007", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "1", "number" => "00988724", "date" => "11.07.2017", "type" => "a", "typename" => "Счет", "summ" => "45600", "summtext" => "75 602", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "1", "number" => "65748900", "date" => "12.07.2017", "type" => "s", "typename" => "Счет", "summ" => "899400", "summtext" => "59 780", "description" => "Любой текст описывающий услуги или электронный билет"];
        }
        $json = ["count" => 30, "list" => $nom, "onlist" => 12, "elems" => $elems];


        return $json;
    }

    private function etikets($nom)
    {
        /*
         *
         $content = $_GET;
        unset($content["q"]);

        $path = $this->server1C."etikets/" . $nom;
        //$auth = base64_encode("Администратор:");
        $opts = [
            'http' => [
                'method' => "GET",
                'header' =>
                    "Authorization: Basic $this->auth\r\n" .
                    "Content-Type: application/json; charset=utf-8\r\n",
                'content' => json_encode($content)
            ]
        ];
        $json = file_get_contents($path, 0, stream_context_create($opts));

        if ($json === false) {
            $json["result"] = false;
        };
        */


        if ($nom == 1) {
            $elems = [];
            $elems[] = ["guid" => "1", "number" => "00005555", "date" => "01.07.2017", "type" => "s", "typename" => "Авиабилет", "sent" => "Ткаченко А.А.", "summ" => "100000", "summtext" => "100 000", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "3", "number" => "dje39903", "date" => "10.07.2017", "type" => "s", "typename" => "Ваучер на проживание", "sent" => "Ткаченко А.А.", "summ" => "100000", "summtext" => "100 000", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "2", "number" => "aas33gg6", "date" => "10.07.2017", "type" => "s", "typename" => "Билет на аэроэкспресс", "sent" => "Ткаченко А.А.", "summ" => "100000", "summtext" => "100 000", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "2", "number" => "ll399443", "date" => "15.07.2017", "type" => "s", "typename" => "Билет на аэроэкспресс", "sent" => "Ткаченко А.А.", "summ" => "100000", "summtext" => "100 000", "description" => "Любой текст описывающий услуги или электронный билет"];
        } elseif ($nom == 2) {
            $elems = [];
            $elems[] = ["guid" => "1", "number" => "epprj544", "date" => "25.07.2017", "type" => "s", "typename" => "Авиабилет", "sent" => "Ткаченко А.А.", "summ" => "100000", "summtext" => "54 330", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "3", "number" => "322", "date" => "10.00.2017", "type" => "s", "typename" => "Ваучер на проживание", "sent" => "Ткаченко А.А.", "summ" => "100000", "summtext" => "45 230", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "1", "number" => "nne44", "date" => "15.08.2017", "type" => "s", "typename" => "Авиабилет", "sent" => "Ткаченко А.А.", "summ" => "100000", "summtext" => "45 230", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "1", "number" => "9084dd", "date" => "16.08.2017", "type" => "s", "typename" => "Авиабилет", "sent" => "Ткаченко А.А.", "summ" => "100000", "summtext" => "78 230", "description" => "Любой текст описывающий услуги или электронный билет"];
        } elseif ($nom == 3) {
            $elems = [];
            $elems[] = ["guid" => "2", "number" => "ru5643f", "date" => "22.09.2017", "type" => "s", "typename" => "Билет на аэроэкспресс", "sent" => "Ткаченко А.А.", "summ" => "100000", "summtext" => "7 654", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "3", "number" => "mkj3333", "date" => "23.09.2017", "type" => "s", "typename" => "Ваучер на проживание", "sent" => "Ткаченко А.А.", "summ" => "100000", "summtext" => "10 000", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "3", "number" => "kjsdfv555", "date" => "26.10.2017", "type" => "s", "typename" => "Ваучер на проживание", "sent" => "Ткаченко А.А.", "summ" => "100000", "summtext" => "50 000", "description" => "Любой текст описывающий услуги или электронный билет"];
            $elems[] = ["guid" => "1", "number" => "YES-11222", "date" => "31.10.2017", "type" => "s", "typename" => "Авиабилет", "sent" => "Ткаченко А.А.", "summ" => "100000", "summtext" => "10 560", "description" => "Любой текст описывающий услуги или электронный билет"];
        }
        $json = ["count" => 2, "list" => $nom, "onlist" => 20, "elems" => $elems];

        return $json;
    }

    private function findoc($guid, $typedoc)
    {
        $json = [];
        if ($typedoc == "pdf") {
            if ($guid == "1") {
                $this->file_force_download("../tmp/4.pdf", false); //Счет
            } elseif ($guid == "2") {
                $this->file_force_download("../tmp/4.pdf", false); //Акт
            } elseif ($guid == "3") {
                $this->file_force_download2("../tmp/5.pdf"); //Счет-фактура
            } else {

                $content = $_GET;
                unset($content["q"]);

                $path = $this->server1C."findoc/" . $guid . "/" . $typedoc . "/";
                //$auth = base64_encode("Администратор:");
                $opts = [
                    'http' => [
                        'method' => "GET",
                        'header' =>
                            "Authorization: Basic $this->auth\r\n" .
                            "Content-Type: application/json; charset=utf-8\r\n",
                        'content' => json_encode($content)
                    ]
                ];
                $json = file_get_contents($path, 0, stream_context_create($opts));

                if ($json === false) {
                    $json["result"] = false;
                } else {
                    $mtmpfname = tempnam("../tmp", "FOO");
                    file_put_contents($mtmpfname, $json);

                    rename($mtmpfname, $mtmpfname . ".pdf");
                    $mtmpfname = $mtmpfname . ".pdf";
                    $this->file_download($mtmpfname, true);
                    //$this->file_force_download($mtmpfname, true);
                    //exit;
                }
            }
        } else {
            $json = [
                "guid" => "f000-f000-f000-f000",
                "number" => "00005555",
                "date" => "01.07.2017",
                "type" => "s",
                "typename" => "Счет",
                "summ" => "100000",
                "summtext" => "100 000",
                "description" => "Любой текст описывающий услуги или электронный билет",
                "services" => [[
                    "number" => "444555",
                    "service" => "Авиабилет",
                    "summ" => "50000",
                    "description" => "Любой текст описывающий услуги или электронный билет",
                    "cfo" => "Руководство",
                    "carrier" => "Аэрофлот",
                    "route" => "Москва - Берлин - Париж"
                ]]
            ];
        };

        return $json;
    }

    private function etiket($guid, $namedoc)
    {

        $content = $_GET;
        unset($content["q"]);

        $path = $this->server1C."etiket/" . $guid . "/" . $namedoc;
        //$auth = base64_encode("Администратор:");
        $opts = [
            'http' => [
                'method' => "GET",
                'header' =>
                    "Authorization: Basic $this->auth\r\n" .
                    "Content-Type: application/json; charset=utf-8\r\n",
                'content' => json_encode($content)
            ]
        ];
        $json = file_get_contents($path, 0, stream_context_create($opts));

        if ($json === false) {
            $json["result"] = false;
        } else {
            $mtmpfname = tempnam("../tmp", "FOO");
            file_put_contents($mtmpfname, $json);

            rename($mtmpfname, $namedoc);
            //$mtmpfname = $mtmpfname.".".$typedoc;
            //$this->file_download($mtmpfname, true);
            $this->file_force_download($namedoc, true);
            //exit;
        }

        /*
        if ($typedoc == "pdf") {
            if ($guid == "1") {
                $this->file_download("../tmp/2.pdf"); //Авиабилет
            } elseif ($guid == "2") {
                $this->file_download("../tmp/3.pdf"); //Аэроэкспресс
            } elseif ($guid == "3") {
                $this->file_download("../tmp/7.pdf"); //Ваучер
            }
        }

        $json = [
            "guid" => "f000-f000-f000-f000",
            "number" => "00005555",
            "date" => "01.07.2017",
            "type" => "s",
            "typename" => "Авиабилет",
            "summ" => "100000",
            "summtext" => "100 000",
            "description" => "Любой текст описывающий услуги или электронный билет"
        ];
        */

        return $json;
    }


    private function getreports($nom)
    {
        /*
        $content = $_GET;
        unset($content["q"]);

        $path = $this->server1C."reports/";
        //$auth = base64_encode("Администратор:");
        $opts = [
            'http' => [
                'method' => "GET",
                'header' =>
                    "Authorization: Basic $this->auth\r\n" .
                    "Content-Type: application/json; charset=utf-8\r\n",
                'content' => json_encode($content)
            ]
        ];
        $json = file_get_contents($path, 0, stream_context_create($opts));

        if ($json === false) {
            $json["result"] = false;
        };
        */

        $filtres = [
            ["name" => "fromdata", "description" => "Дата от", "type" => "date", "element" => "input"],
            ["name" => "todata", "description" => "до", "type" => "date", "element" => "input"],
            ["name" => "typetiket", "description" => "Тип документа", "type" => "checkbox", "element" => "checkbox", "elements" => [
                "Авиабилеты", "ЖДБилеты", "АэроЭкспресс"
            ]],
        ];


        $filtres2 = [
            ["name" => "fromdata", "description" => "Дата от", "type" => "date", "element" => "input"],
            ["name" => "todata", "description" => "до", "type" => "date", "element" => "input"],
            ["name" => "typetiket", "description" => "Тип документа", "type" => "checkbox", "element" => "checkbox", "elements" => [
                "Счета", "Счет-Фактура", "Акт"
            ]],
        ];


        $elems = [];
        $elems[] = ["text" => "Финансовые отчеты", "nodes" => [
            ["text" => "Где деньги?", "guid" => "f123", "filtres" => $filtres2],
            ["text" => "Деньги здесь?", "guid" => "f123", "filtres" => $filtres2],
        ]];
        $elems[] = ["text" => "Бухгалтерские отчеты", "nodes" => [
            ["text" => "Отчет - последний день сдачи", "guid" => "f123", "filtres" => $filtres2],
            ["text" => "Отчет - зачем нам корректировки", "guid" => "f123", "filtres" => $filtres2]
        ]];
        $elems[] = ["text" => "Отчет по бюджетированию", "guid" => "f123", "filtres" => $filtres];
        $elems[] = ["text" => "Отчеты по Авиабилетам", "nodes" => [
            ["text" => "Куда чаще всего летаем?", "guid" => "f123", "filtres" => $filtres],
            ["text" => "Сколько стоят билеты?", "guid" => "f123", "filtres" => $filtres],
            ["text" => "Срез по маршрутам", "guid" => "f123", "filtres" => $filtres],
            ["text" => "Кто больше всех летает", "guid" => "f123", "filtres" => $filtres],
            ["text" => "Кто лучше всех летает", "guid" => "f123", "filtres" => $filtres]
        ]];
        $elems[] = ["text" => "Отчеты по Проживанию", "nodes" => [
            ["text" => "Как там на чужбине", "guid" => "f123", "filtres" => $filtres]
        ]];
        $elems[] = ["text" => "Отчеты Командированных", "nodes" => [
            ["text" => "Это не я - меня подставили", "guid" => "f123", "filtres" => $filtres]
        ]];
        $json = $elems;

        return $json;
    }

    private function generatereport($guid)
    {
        $content = $_GET;
        unset($content["q"]);

        $path = $this->server1C."generatereport/" . $guid . "/pdf";
        //$auth = base64_encode("Администратор:");
        $opts = [
            'http' => [
                'method' => "GET",
                'header' =>
                    "Authorization: Basic $this->auth\r\n" .
                    "Content-Type: application/json; charset=utf-8\r\n",
                'content' => json_encode($content)
            ]
        ];
        $json = file_get_contents($path, 0, stream_context_create($opts));

        if ($json === false) {
            $json["result"] = false;
        } else {
            $mtmpfname = tempnam("../tmp", "FOO");
            file_put_contents($mtmpfname, $json);
            rename($mtmpfname, $mtmpfname.".pdf");
            $this->file_download($mtmpfname.".pdf", true);
        }
        //$this->file_download("../tmp/temp.pdf");
    }

    private function downloadreport($guid)
    {
        $content = $_GET;
        unset($content["q"]);

        $path = $this->server1C."generatereport/" . $guid . "/pdf";
        //$auth = base64_encode("Администратор:");
        $opts = [
            'http' => [
                'method' => "GET",
                'header' =>
                    "Authorization: Basic $this->auth\r\n" .
                    "Content-Type: application/json; charset=utf-8\r\n",
                'content' => json_encode($content)
            ]
        ];
        $json = file_get_contents($path, 0, stream_context_create($opts));

        if ($json === false) {
            $json["result"] = false;
        } else {
            $mtmpfname = tempnam("../tmp", "FOO");
            file_put_contents($mtmpfname, $json);
            rename($mtmpfname, $mtmpfname.".pdf");
            $this->file_force_download($mtmpfname.".pdf", true);
        }
        //$this->file_force_download("../tmp/temp.pdf");
    }




    private function mytravels($userguid)
    {

        $json = [];
        if ($userguid == "1") {
            $elems = [];
            $elems[] = ["guid" => "1", "status" => 1, "number" => "00003355", "date" => "09.07.2017", "summ" => "100000", "summtext" => "23 320", "description" => "На CES в LA"];
            $elems[] = ["guid" => "2", "status" => 2, "number" => "00088372", "date" => "10.07.2017", "summ" => "150000", "summtext" => "153 007", "description" => "Заключить договор с AX"];
            $elems[] = ["guid" => "3", "status" => 3, "number" => "00988724", "date" => "11.07.2017", "summ" => "45600", "summtext" => "75 602", "description" => "Иванов Воронеж"];
            $elems[] = ["guid" => "4", "status" => 3, "number" => "65748900", "date" => "12.07.2017", "summ" => "899400", "summtext" => "59 780", "description" => "Цикловое Минск"];
            $elems[] = ["guid" => "5", "status" => 3, "number" => "65748900", "date" => "12.07.2017", "summ" => "899400", "summtext" => "59 780", "description" => "Командировка Новый Уренгой"];

            $json = ["count" => 5, "list" => 1, "onlist" => 4, "elems" => $elems];
        }
        return $json;
    }

    private function mytravel($userguid, $travelguid)
    {

        $json = [];
        if (($userguid == "1") && ($travelguid == "1")) {
            $elems = [];
            $elems[] = [
                "guid" => "1",
                "datefrom" => "09.07.2017",
                "timefrom" => "08:00",
                "dateto" => "09.07.2017",
                "timeto" => "08:00",
                "type" => "trf",
                "typename" => "Transfer",
                "description" => "Трансфер в домодедово",
                "docs" => [
                    [
                        "url" => "reports/getdoc/1",
                        "caption" => "tmp/1.jpg"
                    ]
                ]
            ];
            $elems[] = ["guid" => "2", "status" => 2, "datefrom" => "09.07.2017", "timefrom" => "10:00", "dateto" => "09.07.2017", "timeto" => "13:00", "type" => "air", "typename" => "Air", "summ" => "150000", "description" => "MOSCOW - NEW YORK", "docs" => [["url" => "reports/getdoc/2", "caption" => "tmp/2.jpg"]]];
            $elems[] = ["guid" => "3", "status" => 3, "datefrom" => "09.07.2017", "timefrom" => "14:00", "dateto" => "12.07.2017", "timeto" => "12:00", "type" => "htl", "typename" => "Hotel", "summ" => "45600", "description" => "MANDARINE HOTEL", "docs" => [["url" => "reports/getdoc/3", "caption" => "tmp/3.jpg"]]];
            $elems[] = ["guid" => "4", "status" => 3, "datefrom" => "12.07.2017", "timefrom" => "15:30", "dateto" => "12.07.2017", "timeto" => "15:30", "type" => "air", "typename" => "Air", "summ" => "899400", "description" => "NEW YORK - MOSCOW"];
            $elems[] = ["guid" => "5", "status" => 3, "datefrom" => "12.07.2017", "timefrom" => "18:00", "dateto" => "12.07.2017", "timeto" => "18:00", "type" => "trf", "typename" => "Transfer", "summ" => "899400", "description" => "Трансфер из внуково"];

            $json = ["count" => 5, "status" => 1, "description" => "На CES в LA", "elems" => $elems];
        }
        return $json;
    }

    private function getdoc($name)
    {
        $this->file_force_download("../tmp/" . $name . ".jpg");
        return $name;
    }

    private function myautorizations($userguid)
    {

        $elems = [];
        $elems[] = ["guid" => "1", "date" => "01.07.2017", "status" => "1", "summ" => "100000", "description" => "A"];
        $elems[] = ["guid" => "3", "date" => "02.07.2017", "status" => "2", "summ" => "150000", "description" => "B"];
        $elems[] = ["guid" => "2", "date" => "03.07.2017", "status" => "3", "summ" => "45600", "description" => "C"];

        $json = ["count" => 5, "list" => 1, "onlist" => 3, "elems" => $elems];

        return $json;
    }

    private function myautorization($userguid, $autoguid)
    {

        $json = [];
        if ($userguid == "1") {

            $json = [
                "status" => 1,
                "summ" => 45600,
                "autor" => "Петрович",
                "passagers" => "Иван",
                "datefrom" => "09.07.2017",
                "dateto" => "19.07.2017",
                "travelpolitic" => false,
                "route" => [
                    ["name" => "Москва-Барселона", "datefrom" => "09.07.2017"],
                    ["name" => "Барселона-Париж", "datefrom" => "14.07.2017"],
                    ["name" => "Париж-Москва", "datefrom" => "19.07.2017"]
                ],
                "information" => [
                    [
                        "name" => "Авиабилеты",
                        "colorbadge" => "red",
                        "badge" => "22000",
                        "lines" => [
                            ["name" => "Стоимость услуги", "value" => "22000"],
                            ["name" => "Крайний срок", "value" => "22.07.2017 15:00"],
                            ["name" => "Самый выгодный вариант", "value" => "18500"],
                            ["name" => "Упущенная выгода", "value" => "3500"]
                        ],
                        "addedinformation" => [
                            [
                                "type" => "block",
                                "name" => "Экстра поля",
                                "contents" => [
                                    ["name" => "Номер приказа", "value" => "1234"],
                                    ["name" => "Кост цент", "value" => "Москва"],
                                    ["name" => "Коды нарушени политики", "value" => "Превышение стоимости"],
                                ]
                            ],
                            [
                                "type" => "block",
                                "name" => "Нарушение тревел-политики",
                                "contents" => "Какой то текст который жужукает на пассажира"
                            ],
                            [
                                "type" => "block",
                                "name" => "Причина нарушения тревел-политики",
                                "contents" => "Предпочитаемое время пассажира"
                            ],
                            [
                                "type" => "route",
                                "name" => "Маршрут",
                                "contents" => [
                                    ["name" => "Москва-Барселона", "info" => "Бизнес класс", "datefrom" => "09.07.2017", "timefrom" => "15:00"],
                                    ["name" => "Барселона-Париж", "info" => "Бизнес класс", "datefrom" => "14.07.2017", "timefrom" => "15:00"],
                                    ["name" => "Париж-Москва", "info" => "Бизнес класс", "datefrom" => "19.07.2017", "timefrom" => "15:00"]
                                ]
                            ],
                            [
                                "type" => "route",
                                "name" => "Самый выгодный вариант",
                                "contents" => [
                                    ["name" => "Москва-Барселона", "info" => "Эконом класс", "datefrom" => "09.07.2017", "timefrom" => "15:00"],
                                    ["name" => "Барселона-Париж", "info" => "Эконом класс", "datefrom" => "14.07.2017", "timefrom" => "15:00"],
                                    ["name" => "Париж-Москва", "info" => "Эконом класс", "datefrom" => "19.07.2017", "timefrom" => "15:00"]
                                ]
                            ]
                        ]
                    ],
                    [
                        "name" => "Гостиница",
                        "colorbadge" => "red",
                        "badge" => "7546",
                        "lines" => [
                            ["name" => "Стоимость услуги", "value" => "7546"],
                            ["name" => "Крайний срок", "value" => "22.07.2017 15:00"],
                            ["name" => "Отель", "value" => "Корона 22.07 - 30.07"],
                            ["name" => "Проживающие", "value" => "Вася"]
                        ],
                        "addedinformation" => [
                            [
                                "type" => "block",
                                "name" => "Экстра поля",
                                "contents" => [
                                    ["name" => "Номер приказа", "value" => "1234"],
                                    ["name" => "Кост цент", "value" => "Москва"]
                                ]
                            ],
                            [
                                "type" => "block",
                                "name" => "Нарушение тревел-политики",
                                "contents" => "Какой то текст который жужукает на пассажира"
                            ],
                            [
                                "type" => "block",
                                "name" => "Причина нарушения тревел-политики",
                                "contents" => "Предпочитаемое время вылета пассажира"
                            ]
                        ]
                    ]
                ],
                "allautorization" => [
                    ["status" => 2, "info" => "Крыжановский С.А."],
                    ["status" => 1, "info" => "Медведев Д.А."],
                    ["status" => 3, "info" => "Путин В.В."],
                ]
            ];
        }

        return $json;
    }

    private function test($param1)
    {
        $json = '';
        return $param1;
    }

}