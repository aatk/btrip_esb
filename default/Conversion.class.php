<?php
/*
 * Ver 1.0.0
 */
class Conversion extends ex_class
{

    private $metod;
    private $connectionInfo;
    private $supplier;
    private $cloud;
    //private $debugclass;

    public function __construct($metod = "", $debug = false)
    {
        $this->debugclass = $debug;
        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем

        parent::__construct($_SESSION["i4b"]["connectionInfo"]);
        $this->metod = $metod;
        $this->supplier = $this->agent;

        $this->cloud = new Cloud($metod, $this->debugclass);
        $this->cloud->SetDirname("cloudfiles");
    }

    public function CreateDB() {
        /* Описание таблиц для работы с пользователями*/
        //print_r("11111");

        $info["returndoc"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "sever_id" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "typedoc"  => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "lastdoc"  => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "lastdate"  => array('type' => 'datetime', 'null' => 'NOT NULL')
        );
        $info["listdoc"] = array(
            "id" => array('type' => 'int(15)', 'null' => 'NOT NULL', 'inc' => true),
            "sever_id" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "typedoc"  => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "doc"  => array('type' => 'varchar(250)', 'null' => 'NOT NULL'),
            "date"  => array('type' => 'datetime', 'null' => 'NOT NULL'),
            "md5"  => array('type' => 'varchar(50)', 'null' => 'NOT NULL')
        );

        $this->create($this->connectionInfo['database_type'], $info);

        $res = $this->error();
        if ((int)$res[0] == 0) {
            $result = ["result" => true];
        } else {
            $result = ["result" => false, "message" => $res];
        }
        //надо бы обернуть ошибку, как нибудь в будущем :)
        return $result;
    }

    public function Init($param)
    {
        $result = array();
        if ($param[count($param)-1] == "debug") {
            print_r("debug class");
            print_r($param);
            print_r($_REQUEST["q"]);
            $this->debugclass = true;
        }

        if (($this->metod == "POST") && (isset($param[0])) && (isset($param[1]))) {
            if ($param[0] == "airamadeus") {
                //$result = $this->airamadeus($param[1]);
            } elseif ($param[0] == "xml") {
                //$result = $this->xml($param[1]);
            } elseif ($param[0] == "registerdb") {
                $result = $this->registerdb($param[1], $param[2]);

            } elseif ($param[0] == "unregisterdb") {
                $result = $this->unregisterdb($param[1], $param[2]);

            }


        } elseif (($this->metod == "GET")) {
            if ($param[0] == "test") {
                //$result = $this->test($param[1]);

            } elseif ( ($param[0] == "createdb") ) {
                $result = $this->createdb();

            } elseif ( ($param[0] == "getdoc") ) {
                $result = $this->getdoc($param[1], $param[2], $param[3]);

            } elseif ( ($param[0] == "setdoc") ) {
                $result = $this->setdoc($param[1], $param[2], $param[3]);

            } elseif ( ($param[0] == "getlist") ) {
                $result = $this->getlist($param[1], $param[2], $param[3]);

            } elseif ( ($param[0] == "getlistbydate") ) {
                $result = $this->getlistbydate($param[1], $param[2]);

            } elseif ( ($param[0] == "getsource") ) {
                $result = $this->getsource($param[1], $param[2]);

            } elseif ($param[0] == "getstatusregisterdb") {
                $result = $this->getstatusregisterdb($param[1], $param[2]);

            } elseif ($param[0] == "updatelistdoc") {
                $result = $this->updatelistdoc();

            }




        }

        return $result;
    }

    public function addtolist($typedoc, $filename, $md5 = "") {
        //
        $result = false;
        $retlist = [];
        $typedoc = mb_strtolower($typedoc);

        $wer = $this->select("returndoc", ["sever_id"], ["typedoc" => $typedoc]);
        foreach ($wer as $item) {
            if (!in_array($item["sever_id"], $retlist)) {
                $retlist[] = $item["sever_id"];
                $data = [
                    "sever_id" => $item["sever_id"],
                    "typedoc" => $typedoc,
                    "doc" => $filename,
                    "date" => date("Y-m-d H:i:s"),
                    "md5" => $md5
                ];
                $this->insert("listdoc", $data);
            }
        }
        return ["result" => $result];
    }

    private function getdoc($sever_id, $typedoc, $numdoc) {

        $typedoc = mb_strtolower($typedoc);
        $data = [
            "sever_id" => $sever_id,
            "typedoc" => $typedoc,
            "lastdoc" => $numdoc
        ];

        $where = ["AND" => [
            "sever_id" => $sever_id,
            "typedoc" => $typedoc
        ]];

        $lastdocstr = (float)$numdoc;
        $date = new DateTime();
        $date->setTimestamp($lastdocstr);
        $datadir = $date->format('Ymd');

        $file  = 'tmp/'.mb_strtolower($this->supplier.'/'.$typedoc.'/'.$datadir.'/'.$numdoc.'.post');
        $vfile  = mb_strtolower($this->supplier.'/'.$typedoc.'/'.$datadir.'/'.$numdoc.'.post');
        if (file_exists($file)) {
            if ($this->debugclass) {
                print_r("file - ".$file);
            }
            //Возвращаем в теле ответ
            $source = file_get_contents($file);
            echo $source;
            exit();
        } elseif ($this->cloud->vfile_exists($vfile)){
            if ($this->debugclass) {
                print_r("vfile - ".$vfile);
            }
            $source = $this->cloud->vfile_get_contents($vfile);
            echo $source;
            exit();
        };

        return ["result" => false, "message" => "no file"];
    }

    private function updatelistdoc()
    {
        $sel = $this->select("listdoc", ["id", "doc"], ["date" => "0000-00-00 00:00:00"]);
        foreach ($sel as $line) {
            $timestr = (float)str_replace(".post", "", $line["doc"]);
            $datefirst = new DateTime();
            $datefirst->setTimestamp($timestr);
            $ff = $datefirst->format("Y-m-d H:i:s");

            $this->update("listdoc", ["date" => $ff], ["id" => $line["id"]]);
        }
    }

    private function setdoc($sever_id, $typedoc, $numdoc) {

        $typedoc = mb_strtolower($typedoc);

        $data = [
            "sever_id" => $sever_id,
            "typedoc" => $typedoc,
            "lastdoc" => $numdoc
        ];

        $where = ["AND" => [
            "sever_id" => $sever_id,
            "typedoc" => $typedoc
        ]];

        //Запишем в БД последний отданный файл

        if ($this->has("returndoc", ["sever_id" => $sever_id])) {

            $id = $this->get("returndoc", "id", $where);
            if ($id === false) {
                $this->insert("returndoc", $data);
            } else {
                $this->update("returndoc", $data, ["id" => $id]);
            }

            $hasw = ["AND" => [
                "sever_id" => $sever_id,
                "typedoc" => $typedoc,
                "doc" => (string)$numdoc.".post"
            ]];
            $er[] = $hasw;
            $this->delete("listdoc", $hasw);
            $er[] = $this->error();

            return ["result" => true, "error" => $er, "tmp" => $hasw];
        }

        return ["result" => false, "error" => "Неверная БД"];
    }

    private function getlist($sever_id, $typedoc, $lastdoc = null) {

        $typedoc = mb_strtolower($typedoc);
        $filemd5 = [];

        $result = ["result" => true];
        $and = ["sever_id" => $sever_id, "typedoc" => $typedoc ];

        $serverdir = $_SERVER["DOCUMENT_ROOT"];

        //var_dump($this->debugclass); // 27407 29165  29170 29171
        if ($this->debugclass) {
            var_dump(trim($lastdoc));
        }

        if ($this->debugclass) {
            var_dump($lastdoc);
        }

        if (isset($lastdoc)) {
            //print_r("111");

            $datefirst = new DateTime();
            $datefirst->setTimestamp((float)$lastdoc);
            //$daycount = 1+(int)$datefirst->diff( new DateTime() )->format("%d");

            $message = [];

            $ff = $datefirst->format("Y-m-d H:i:s");
            $and["date[>]"] = $ff;

            $ndocs = $this->select("listdoc", "*", ["AND" => $and, "ORDER" => ["id" => "ASC"]]);
            foreach ($ndocs as $doc) {
                $value = $doc["doc"];
                $timestr = (float)str_replace(".post", "", $value);
                $message[] = $timestr;

                $date = new DateTime();
                $date->setTimestamp($timestr);
                $datadir = $date->format('Ymd');
                $dir    = $serverdir.'/tmp/'.mb_strtolower($this->supplier.'/'.$typedoc.'/'.$datadir.'/');
                if (file_exists($dir.$value)) {
                    $filemd5[] = md5_file($dir.$value);
                } else {
                    $dir    = $this->supplier.'/'.ucfirst($typedoc).'/'.$datadir.'/';
                    if ($this->cloud->vfile_exists($dir.$value)) {
                        $infomd5 = $this->cloud->vfile_info($dir.$value);
                        $filemd5[] = $infomd5;
                        //$filecontent = $this->cloud->vfile_get_contents($dir.$value);
                        //$filemd5[] = md5($filecontent);  //Здесь работает
                    } else {
                        $filemd5[] = $dir;
                    }
                    //$filecontent = $this->cloud->vfile_get_contents($dir.$value);
                    //$filemd5[] = md5($filecontent);
                }
            }
            $result["message"] = $message;

        } else {

            $message = [];
            $ndocs = $this->select("listdoc", "*", ["AND" => $and]);
            foreach ($ndocs as $doc) {
                $value = $doc["doc"];
                $timestr = (float)str_replace(".post", "", $value);
                $message[] = $timestr;

                $date = new DateTime();
                $date->setTimestamp($timestr);
                $datadir = $date->format('Ymd');
                $dir    = $serverdir.'/tmp/'.mb_strtolower($this->supplier.'/'.$typedoc.'/'.$datadir.'/');
                if (file_exists($dir.$value)) {
                    $filemd5[] = md5_file($dir.$value);
                } else {
                    $dir = $this->supplier.'/'.ucfirst($typedoc).'/'.$datadir.'/';
                    if ($this->cloud->vfile_exists($dir.$value)) {
                        $infomd5 = $this->cloud->vfile_info($dir.$value);
                        $filemd5[] = $infomd5["md5"];
                    } else {
                        $filemd5[] = $dir;
                    }

                }
            }
            if (count($message) == 0) {
                $result = ["result" => false, "message" => "no data"];
            } else {
                array_multisort($message);
                array_unique($message);
                $result["message"] = $message;
            }
        }

        if (isset($result["message"]) && is_array($result["message"])) {

            $nowindex = 0;
            $fileslist = [];
            foreach ($result["message"] as $value) {

                $date = new DateTime();
                $date->setTimestamp($value);
                $datadir = $date->format('Ymd H:i:s');

                $fileslist[] = ["filename" => $value, "filedate" => $datadir, "filemd5" => $filemd5[$nowindex]];
                $nowindex++;
            }
            $result["fileslist"] = $fileslist;
        }

        return $result;
    }

    private function getlistbydate($typedoc, $datadir) {

        $result = ["result" => true];
        $typedoc = mb_strtolower($typedoc);
        $filemd5 = [];

        if ($datadir != "") {

            $message = [];

            $serverdir = $_SERVER["DOCUMENT_ROOT"];
            $dir    = $serverdir.'/tmp/'.mb_strtolower($this->supplier.'/'.$typedoc.'/'.$datadir.'/');

            $files = scandir($dir);
            foreach ($files as $value) {
                if (($value != ".") && ($value != "..")) {
                    $timestr = (float)str_replace(".post", "", $value);
                    $message[] = $timestr;
                    $filemd5[] = md5_file($value);
                }
            }

            $dir    = $this->supplier.'/'.ucfirst($typedoc).'/'.$datadir.'/';
            $files = $this->cloud->vscandir($dir);
            foreach ($files as $value) {
                if (($value != ".") && ($value != "..")) {
                    $timestr = (float)str_replace(".post", "", $value);
                    $message[] = $timestr;

                    $filecontent = $this->cloud->vfile_get_contents($dir.$value);
                    $filemd5[] = md5($filecontent);
                }
            }

            array_multisort($message);
            array_unique($message);
            $result["message"] = $message;

        } else {
            $result = ["result" => false, "message" => "no data"];
        }


        if (isset($result["message"]) && is_array($result["message"])) {

            $nowindex = 0;
            $fileslist = [];
            foreach ($result["message"] as $value) {

                $date = new DateTime();
                $date->setTimestamp($value);
                $datadir = $date->format('Ymd H:i:s');

                $fileslist[] = ["filename" => $value, "filedate" => $datadir, "filemd5" => $filemd5[$nowindex]];
                $nowindex++;
            }
            $result["fileslist"] = $fileslist;
        }

        return $result;
    }

    private function getsource($typedoc, $idsource) {

        $result = [];

        $metod = $this->metod;
        $wClass = [];
        $comand = '$wClass = new ' . ucfirst($typedoc) . '($metod);';
        eval($comand);

        $param = ["getsource", $idsource];
        $result = $wClass->Init($param);

        return $result;
    }




    private function registerdb($sever_id, $typedoc) {
        $result = ["result" => false];
        $typedoc = mb_strtolower($typedoc);
        $hasservice = $this->has("returndoc", ["AND" => ["sever_id" => $sever_id, "typedoc" => $typedoc ]]);
        if (!$hasservice) {
            $this->insert("returndoc", ["sever_id" => $sever_id, "typedoc" => $typedoc ]);
            $result = ["result" => true];
        }
        return $result;
    }

    private function unregisterdb($sever_id, $typedoc) {
        $result = ["result" => false];
        $typedoc = mb_strtolower($typedoc);
        $hasservice = $this->has("returndoc", ["AND" => ["sever_id" => $sever_id, "typedoc" => $typedoc ]]);
        if ($hasservice) {
            $this->delete("listdoc", ["AND" => ["sever_id" => $sever_id, "typedoc" => $typedoc ]]);
            $this->delete("returndoc", ["AND" => ["sever_id" => $sever_id, "typedoc" => $typedoc ]]);
            $result = ["result" => true];
        }
        return $result;
    }

    private function getstatusregisterdb($sever_id, $typedoc) {
        $result = ["result" => false, "status" => false];
        $typedoc = mb_strtolower($typedoc);
        $hasservice = $this->has("returndoc", ["AND" => ["sever_id" => $sever_id, "typedoc" => $typedoc ]]);
        if ($hasservice) {
            $result = ["result" => true, "status" => true];
        }
        return $result;
    }

}

?>
