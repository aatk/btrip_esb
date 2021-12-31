<?php

class Marketplace extends ex_component
{

    private $connectionInfo;
    private $metod;
    private $defaultuser;

    private $Auth;

    public function __construct($metod = "")
    {
        $this->defaultuser = [
            "login" => "test@test.test", //TODO указать пользователя по умолчанию (администратора)
            "password" => "test"         //TODO указать пароль по умолчанию (администратора)
        ];
        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем настройки подключения к БД
        parent::__construct($metod, $this->connectionInfo);

        $this->metod = $metod;

        $this->Auth = new Auth();
    }

    public function CreateDB()
    {
        /* Описание таблиц для работы с пользователями*/
        $info["mpcomponents"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "md5" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "name" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "md5filecomponent" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "md5fileinfo" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "md5filecomponentpublic" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),

            "delfield" => array('type' => 'int(1)', 'null' => 'NOT NULL'),
            "iduser" => array('type' => 'int(15)', 'null' => 'NOT NULL'),

            "public" => array('type' => 'int(1)', 'null' => 'NOT NULL'),
            "version" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "privateserverclass" => array('type' => 'int(1)', 'null' => 'NOT NULL'),
            "type" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "minversion" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "maxversion" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "senddumptodeveloper" => array('type' => 'int(1)', 'null' => 'NOT NULL'),

            "cost" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "astransaction" => array('type' => 'int(1)', 'null' => 'NOT NULL'),
            "hash" => array('type' => 'varchar(50)', 'null' => 'NOT NULL')
        );

        $info["mpuserdb"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "iduser" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "dbname" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "servername" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "typeservername" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "delfield" => array('type' => 'int(1)', 'null' => 'NOT NULL')
        );
        $info["mpdbcomponents"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "iddb" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "idcomponent" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "delfield" => array('type' => 'int(1)', 'null' => 'NOT NULL')
        );
        $info["mpusercomponents"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "iduser" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "idcomponent" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "delfield" => array('type' => 'int(1)', 'null' => 'NOT NULL')
        );
        $info["mperrordumps"] = array(
            "id" => array('type' => 'int(15)', 'null' => 'NOT NULL', 'inc' => true),
            "iduser" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "iddb" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "idcomponent" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "md5filedump" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "delfield" => array('type' => 'int(1)', 'null' => 'NOT NULL')
        );
        $info["mpuserbalance"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "iduser" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "balance" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "kreditline" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "delfield" => array('type' => 'int(1)', 'null' => 'NOT NULL')
        );
        $info["mpuserpurchasecomp"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "iduser" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "idcomponent" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "datepurchase" => array('type' => 'date', 'null' => 'NOT NULL'),
            "delfield" => array('type' => 'int(1)', 'null' => 'NOT NULL')
        );

        $res = $this->create($this->connectionInfo['database_type'], $info);

    }

    public function InstallModule()
    {
        /* Заполнение таблиц для работы с пользователями*/
        if (class_exists("Auth")) {
            $roles = [
                ["name" => "mp_addcomponent", "info" => "Access to add components", "disabled" => true],
                ["name" => "mp_delcomponent", "info" => "Access and removal of components", "disabled" => true],
                ["name" => "mp_buycomponent", "info" => "Access to purchase components", "disabled" => true],
            ];

            foreach ($roles as $value) {
                $this->Auth->setrule($value["name"], $value["name"], $value["info"], $value["disabled"]);
            }

            $users = $this->defaultuser;
            $users["password"] = md5($users["password"]);
            if (!$this->has("users", ["login" => $users["login"]])) {
                $this->insert("users", $users);
            } else {
                $this->update("users", $users, ["login" => $users["login"]]);
            }

        }
    }


    public function uploadcomponent($params)
    {

        $result = ["result" => false];
        $userid = $params["userid"];

        //if (($params["filenameprocessing"] != "") && ($params["filenameinfo"] != "")) {
        if (($params["filenameinfo"] != "")) {

            if (isset($params["idkey"])) {
                $finddatas = [
                    "id" => $params["idkey"],
                    "iduser" => $userid
                ];
            } else {
                $finddatas = [
                    "md5" => md5($params["nameprocessing"]),
                    "version" => $params["version"],
                    "iduser" => $userid
                ];
            }


            $infocomponent = $this->get("mpcomponents", ["id", "md5filecomponent", "md5fileinfo", "md5filecomponentpublic"], ["AND" => $finddatas]);
            //if ($infocomponent) {
            //$this->cloud->del($infocomponent["md5filecomponent"]);
            //$this->cloud->del($infocomponent["md5fileinfo"]);
            //$this->cloud->del($infocomponent["md5filecomponentpublic"]);
            //}

            $md5processing = $this->cloud->upload(["upload", "Marketplace", $params["nameprocessing"], $params["version"], $params["filenameprocessing"]], $params["fileprocessing"]);
            $md5info = $this->cloud->upload(["upload", "Marketplace", $params["nameprocessing"], $params["version"], $params["filenameinfo"]], $params["fileinfo"]);

            if (isset($params["filenameprocessingpublic"])) {
                $md5processingpublic = $this->cloud->upload(["upload", "Marketplace", $params["nameprocessing"], $params["version"], $params["filenameprocessingpublic"]], $params["fileprocessingpublic"]);
            } else {
                $md5processingpublic = $md5processing;
            }

            $datas = [
                "md5" => md5($params["nameprocessing"]),
                "name" => $params["nameprocessing"],
                "version" => $params["version"],
                "md5filecomponent" => $md5processing["id"],
                "md5fileinfo" => $md5info["id"],
                "md5filecomponentpublic" => $md5processingpublic["id"],
                "delfield" => 0,
                "iduser" => $userid,
                "hash" => $md5processing["id"],

                "public" => $params["public"],
                "privateserverclass" => $params["privateserverclass"],
                "type" => $params["type"],
                "minversion" => $params["minversion"],
                "senddumptodeveloper" => $params["senddumptodeveloper"],

                "cost" => $params["cost"],
                "astransaction" => $params["astransaction"]
            ];


            $idcomponent = $this->get("mpcomponents", "id", ["AND" => $finddatas]);
            if ($idcomponent) {
                $this->update("mpcomponents", $datas, ["id" => $idcomponent]);
            } else {
                $idcomponent = $this->insert("mpcomponents", $datas);
            }

            if ($idcomponent) {
                //Добавим в список доступных
                $result = ["result" => true, "id" => $idcomponent];
            }
        } else {
            $result["error"] = "Please set minimum 2 files: info.html + *.epf/*.efd/*.php";
        }
        return $result;
    }

    public function getbalance($params)
    {

        $result = ["result" => false];
        if ($this->Auth->userauth()) {
            $userid = $this->Auth->getuserid();
            $balance = $this->get("mpuserbalance", ["balance"], ["iduser" => $userid]);
            if ($balance === false) {
                $result["error"] = "Don't have balance";
            } else {
                $result = ["result" => true, "balance" => (int)$balance["balance"]];
            }
        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;

    }

    public function getcomponentslist($params)
    {

        $result = ["result" => false];
        if ($this->Auth->userauth()) {
            $userid = $this->Auth->getuserid();
            //$userid = $params["userid"];

            $allcomps = [];
            $mycomps = $this->select("mpcomponents", ["[>]usersinfo" => ["iduser" => "id"]], ["mpcomponents.id", "mpcomponents.senddumptodeveloper", "mpcomponents.name", "mpcomponents.version", "mpcomponents.cost", "mpcomponents.astransaction", "usersinfo.name(developername)", "mpcomponents.minversion"], ["AND" => ["mpcomponents.delfield" => 0, "mpcomponents.public" => 0, "mpcomponents.iduser" => $userid]]);
            if ($mycomps) {
                $mycompsnew = [];
                foreach ($mycomps as $value) {
                    $valuenew = $value;
                    $valuenew["typecomponent"] = "developed";

                    $valuenew["datepurchase"] = null;
                    $valuenew["purchase"] = false;
                    $mycompsnew[] = $valuenew;
                }
                $allcomps = $mycompsnew;
            }

            $sharecomps = $this->select("mpusercomponents", ["[<]mpcomponents" => ["idcomponent" => "id"], "[>]usersinfo" => ["mpcomponents.iduser" => "id"]], ["mpcomponents.id", "mpcomponents.senddumptodeveloper", "mpcomponents.name", "mpcomponents.version", "mpcomponents.cost", "mpcomponents.astransaction", "usersinfo.name(developername)", "mpcomponents.minversion"], ["AND" => ["mpusercomponents.delfield" => 0, "mpusercomponents.iduser" => $userid]]);
            if ($sharecomps) {
                $mycompsnew = [];
                foreach ($sharecomps as $value) {
                    $valuenew = $value;
                    $valuenew["typecomponent"] = "shared";

                    $valuenew["datepurchase"] = null;
                    $valuenew["purchase"] = false;
                    $mycompsnew[] = $valuenew;
                }
                $allcomps = array_merge($allcomps, $mycompsnew);
            }

            $mycompsnew = [];
            $comps = $this->query('SELECT mpcomponents.id, "mpcomponents"."name","mpcomponents"."version", "mpcomponents"."senddumptodeveloper","mpcomponents"."cost","mpcomponents"."astransaction","usersinfo"."name" AS "developername", "mpuserpurchasecomp"."datepurchase" AS "datepurchase" , "mpcomponents"."minversion"  AS "minversion" FROM "mpcomponents" LEFT JOIN "usersinfo" ON "mpcomponents"."iduser" = "usersinfo"."id" LEFT JOIN "mpuserpurchasecomp" ON "mpcomponents"."id" = "mpuserpurchasecomp"."idcomponent" AND "mpuserpurchasecomp"."iduser" = ' . $userid . ' WHERE "mpcomponents"."delfield" = 0 AND "mpcomponents"."public" = 1')->fetchAll();
            if ($comps) {
                foreach ($comps as $value) {
                    $valuenew = [];
                    foreach ($value as $key => $item) {
                        if (!is_int($key)) {
                            $valuenew[$key] = $item;
                        }
                    }
                    $valuenew["purchase"] = false;
                    if (!is_null($valuenew["datepurchase"])) {
                        $valuenew["purchase"] = true;
                    }
                    $valuenew["typecomponent"] = "public";
                    $mycompsnew[] = $valuenew;
                }
                $allcomps = array_merge($allcomps, $mycompsnew);
            }

            $result = ["result" => true, "table" => $allcomps];
        } else {
            $result["error"] = "Authorization fail";;
        }

        return $result;
    }

    public function getinfo($params)
    {
        $result = ["result" => false];

        $comps = $this->get("mpcomponents", ["md5fileinfo"], ["id" => $params[1]]);
        if ($comps) {
            $content = $this->cloud->getcontent($comps["md5fileinfo"], 'info.html');
            $result = $content;
        }

        return $result;
    }

    public function addcomponent($params)
    {
        $result = ["result" => false];

        if (class_exists("Auth")) {
            $auth = new Auth();
            if ($auth->userauth()) {
                $userid = $auth->getuserid();
                $idcomponent = $params[1];

                if ($auth->haveuserrole("mp_buycomponent")) {

                    $installtodb = [];
                    $dbs = $this->select("mpuserdb", ["id", "dbname"], ["iduser" => $userid]);
                    foreach ($dbs as $iddb) {
                        if (!$this->has("mpdbcomponents", ["AND" => ["iddb" => $iddb["id"], "idcomponent" => $idcomponent]])) {
                            $this->insert("mpdbcomponents", ["iddb" => $iddb["id"], "idcomponent" => $idcomponent]);
                        }
                        $installtodb[] = $iddb["dbname"];
                    }

                    if (count($installtodb) > 0) {
                        $result = ["result" => true, "dbs" => $installtodb];
                    } else {
                        $result["message"] = "No dbs found";
                    }
                } else {
                    $result["message"] = "You not have download components";
                }
            } else {
                $result["message"] = "Not autharization";
            }
        }

        return $result;
    }


    private function toolsavailable($userid, $cost)
    {
        $result = ["result" => false];
        $res = $this->get("mpuserbalance", ["id", "balance", "kreditline"], ["iduser" => $userid]);
        if ($res) {
            if (($res["balance"] + $res["kreditline"]) >= $cost) {
                $result = ["result" => true];
            }
        }
        return $result;
    }

    private function balance($userid, $cost, $operation)
    {
        $id = $this->get("mpuserbalance", ["id"], ["iduser" => $userid]);
        if ($id) {
            $this->update("mpuserbalance", ["balance[" . $operation . "]" => $cost], ["id" => $id["id"]]);
        } else {
            $insertcost = $cost;
            if ($operation = "-") $insertcost = -1 * $insertcost;
            $this->insert("mpuserbalance", ["iduser" => $userid, "balance" => $insertcost, "delfield" => 0]);
        }
    }

    public function getfulllistcomponents($params)
    {
        $result = ["result" => false];
        if (class_exists("Auth")) {
            $auth = $this->Auth;
            if ($auth->userauth()) {
                $userid = $auth->getuserid();

                $res = $this->getcomponentslist(["userid" => $userid]);
                if (isset($res["table"])) {
                    $allcomponents = $res["table"];
                    $result = ["result" => true, "list" => $allcomponents];
                }
            }
        }
        return $result;
    }

    public function downloadcomponent($params)
    {

        $content = ["result" => false];

        $md5filecomponent = "";
        $filename = "error_file";

        if (class_exists("Auth")) {

            $auth = new Auth();
            if ($auth->userauth()) {

                $userid = $auth->getuserid();
                $idcomponent = $params[1];
                //Получим список всех доступных компонент и если она доступна, то скачаем

                $datas = [
                    "md5",
                    "name",
                    "version",
                    "md5filecomponentpublic",
                    "md5fileinfo",
                    "delfield",
                    "cost",
                    "astransaction",
                    "iduser"
                ];

                $res = $this->get("mpcomponents", $datas, ["id" => $idcomponent]);

                $md5filecomponent = $res["md5filecomponentpublic"];
                $filename = $res["md5filecomponentpublic"] . ".zip";
                $content = $this->cloud->getcontent($md5filecomponent);

                if (($content != "") && ((int)$res["cost"] > 0)) {

                    $shared = $this->get("mpusercomponents", ["id"], ["AND" => ["iduser" => $userid, "idcomponent" => $idcomponent]]);
                    if ($shared) {
                        //Компоннта расшарена, и не требует покупки
                    } else {
                        //Запишем установку платной компоненты
                        $resp = $this->get("mpuserpurchasecomp", ["id"], ["AND" => ["iduser" => $userid, "idcomponent" => $idcomponent]]);
                        if (!$resp) {
                            $cost = (int)$res["cost"];

                            //Уменьшим баланс компании если это не стоимость за транзакцию
                            if ((int)$res["astransaction"] == 0) {

                                $toolsavailable = $this->toolsavailable($userid, $cost);
                                if ($toolsavailable["result"]) {
                                    //Нет записи что такая компонента уже есть
                                    $this->insert("mpuserpurchasecomp", ["iduser" => $userid, "idcomponent" => $idcomponent, "datepurchase" => date("Y-m-d H:i:s", time()), "delfield" => 0]);
                                    $this->balance($userid, $cost, "-");
                                } else {
                                    $content = ""; //Нет денег, нет компоненты
                                }
                            } else {
                                //Нет записи что такая компонента уже есть
                                $this->insert("mpuserpurchasecomp", ["iduser" => $userid, "idcomponent" => $idcomponent, "datepurchase" => date("Y-m-d H:i:s", time()), "delfield" => 0]);
                            }
                        }
                    }
                }
                else {
                    //
                    $content["errors"] = ["Error content or cost"];
                }

                //echo($content);
                //$content = base64_decode($content);
                //$this->cloud->content_force_download($content, $filename);
            }
            else {
                $content["errors"] = ["Error user login & password"];
            }

        }
        else {
            $content["errors"] = ["class Auth not install"];
        }

        if (($md5filecomponent != "") && ($content != "") && (!is_array($content))) {
            $this->cloud->download($md5filecomponent, $filename);
        }


        return $content;
    }

    public function downloadfullcomponent($params)
    {
        $content = "";
        $result = ["result" => false];
        if (class_exists("Auth")) {
            $auth = new Auth();
            if ($auth->userauth()) {
                $userid = $auth->getuserid();
                $idcomponent = $params[1];

                //Получим список всех доступных компонент и если она доступна, то скачаем

                $datas = [
                    "md5",
                    "name",
                    "version",
                    "md5filecomponent",
                    "md5fileinfo",
                    "delfield",
                    "iduser"
                ];
                $res = $this->get("mpcomponents", $datas, ["id" => $idcomponent]);
                $md5filecomponent = $res["md5filecomponent"];

                $this->cloud->download($md5filecomponent, $res["md5filecomponent"] . ".zip");
            }
        }

        return $content;
    }


    private function pr_componentinfo($idcomponent)
    {

        $datas = "*";
        $res = $this->get("mpcomponents", $datas, ["id" => $idcomponent]);
        $result = ["result" => true, "info" => $res];

        return $result;
    }

    public function getcomponentinfo($params)
    {

        $result = ["result" => false];
        if (class_exists("Auth")) {
            $auth = new Auth();
            if ($auth->userauth()) {
                $userid = $auth->getuserid();
                $idcomponent = $params[1];

                $datas = [
                    "id",
                    "md5",
                    "name",
                    "version",
                    "hash",
                    "minversion",
                    "maxversion"
                ];
                $res = $this->get("mpcomponents", $datas, ["id" => $idcomponent]);
                $result = ["result" => true, "info" => $res];
            }
        }

        return $result;
    }

    private function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }


    private function getmanifest(&$params)
    {
        $ssid = session_id();
        $tmldir = trim($_SERVER["DOCUMENT_ROOT"] . "/tmp/" . $ssid);
        mkdir($tmldir);
        $path = tempnam($tmldir, $ssid . ".zip");
        file_put_contents($path, $this->phpinput);

        $zip = new ZipArchive;
        $res = $zip->open($path);
        if ($res === TRUE) {

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $fileinfo = pathinfo($filename);

                if ($fileinfo["basename"] == 'info.html') {
                    copy("zip://" . $path . "#" . $filename, $tmldir . "/" . $fileinfo['basename']);

                    $params["fileinfo"] = file_get_contents($tmldir . "/" . $fileinfo['basename']);
                    $params["filenameinfo"] = $fileinfo['basename'];

                }

                elseif ($fileinfo["basename"] == 'manifest.json') {
                    copy("zip://" . $path . "#" . $filename, $tmldir . "/" . $fileinfo['basename']);

                    $manifest = trim(file_get_contents($tmldir . "/" . $fileinfo['basename']));
                    for ($j = 0; $j <= 31; ++$j) {
                        $manifest = str_replace(chr($j), "", $manifest);
                    }
                    $checkLogin = str_replace(chr(127), "", $manifest);
                    if (0 === strpos(bin2hex($manifest), 'efbbbf')) {
                        $manifest = substr($manifest, 3);
                    }
                    $jsonmanifest = json_decode($manifest, true);

                    $params["public"] = $jsonmanifest["public"];
                    $params["version"] = $jsonmanifest["version"];
                    $params["privateserverclass"] = $jsonmanifest["privateserverclass"];
                    $params["type"] = $jsonmanifest["type"];
                    $params["minversion"] = $jsonmanifest["minversion"];
                    $params["minversion"] = $this->DTV($jsonmanifest, ["minversion"]);
                    $params["maxversion"] = $this->DTV($jsonmanifest, ["maxversion"]);

                    $params["cost"] = $this->DTV($jsonmanifest, ["price"]);
                    $params["astransaction"] = $this->DTV($jsonmanifest, ["componentprice"]);

                    $params["senddumptodeveloper"] = $jsonmanifest["senddumptodeveloper"];
                    $params["nameprocessing"] = $jsonmanifest["name"];
                    $params["filenameprocessing"] = $params["nameprocessing"] . ".zip";

                    $params["filenameprocessingpublic"] = $ssid . ".public.zip";
                    $params["fileprocessingpublic"] = $this->phpinput;
                }
            }
            $zip->close();

        }
        else {
            $result["error"] = "Error file format";
        }

        $this->delTree($tmldir);
    }

    private function createcomponentv4(&$params)
    {
        $this->getmanifest($params);
    }

    private function createcomponentv3(&$params)
    {
        $ssid = session_id();
        $tmldir = trim($_SERVER["DOCUMENT_ROOT"] . "/tmp/" . $ssid);
        mkdir($tmldir);
        $path = tempnam($tmldir, $ssid . ".zip");
        file_put_contents($path, $this->phpinput);

        $pathpublic = tempnam($tmldir, $ssid . ".public.zip");
        $zippublic = new ZipArchive;
        $zippublic->open($pathpublic, ZipArchive::CREATE);

        $zip = new ZipArchive;
        $res = $zip->open($path);
        if ($res === TRUE) {


            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $fileinfo = pathinfo($filename);

                if ($fileinfo["basename"] == 'info.html') {
                    copy("zip://" . $path . "#" . $filename, $tmldir . "/" . $fileinfo['basename']);

                    $params["fileinfo"] = file_get_contents($tmldir . "/" . $fileinfo['basename']);
                    $params["filenameinfo"] = $fileinfo['basename'];

                } elseif ($fileinfo["basename"] == 'manifest.json') {
                    copy("zip://" . $path . "#" . $filename, $tmldir . "/" . $fileinfo['basename']);

                    $manifest = trim(file_get_contents($tmldir . "/" . $fileinfo['basename']));
                    for ($j = 0; $j <= 31; ++$j) {
                        $manifest = str_replace(chr($j), "", $manifest);
                    }
                    $checkLogin = str_replace(chr(127), "", $manifest);
                    if (0 === strpos(bin2hex($manifest), 'efbbbf')) {
                        $manifest = substr($manifest, 3);
                    }
                    $jsonmanifest = json_decode($manifest, true);

                    $params["public"] = $jsonmanifest["public"];
                    $params["version"] = $jsonmanifest["version"];
                    $params["privateserverclass"] = $jsonmanifest["privateserverclass"];
                    $params["type"] = $jsonmanifest["type"];
                    $params["minversion"] = $jsonmanifest["minversion"];
                    $params["minversion"] = $this->DTV($jsonmanifest, ["minversion"]);
                    $params["maxversion"] = $this->DTV($jsonmanifest, ["maxversion"]);

                    $params["cost"] = $this->DTV($jsonmanifest, ["price"]);
                    $params["astransaction"] = $this->DTV($jsonmanifest, ["componentprice"]);

                    $params["senddumptodeveloper"] = $jsonmanifest["senddumptodeveloper"];
                    $params["nameprocessing"] = $jsonmanifest["name"];
                    $params["filenameprocessing"] = $params["nameprocessing"] . ".zip";
                }

                if ($fileinfo["extension"] != 'php') {
                    copy("zip://" . $path . "#" . $filename, $tmldir . "/" . $fileinfo['basename']);
                    $zippublic->addFile($tmldir . "/" . $fileinfo['basename'], $fileinfo['basename']);
                }
            }
            $zip->close();
            $zippublic->close();

            if ($params["privateserverclass"]) {
                $params["filenameprocessingpublic"] = $ssid . ".public.zip";
                $params["fileprocessingpublic"] = file_get_contents($pathpublic);
            }

        } else {
            $result["error"] = "Error file format";
        }

        $this->delTree($tmldir);
    }

    public function createcomponent($inparams)
    {
        $result = ["result" => false];

        if (class_exists("Auth")) {
            $auth = new Auth();


            if ($auth->userauth()) {

                if ($auth->haveuserrole("mp_addcomponent")) {
                    $idkey = $inparams[1];
                    $format = $inparams[2];
                    $version = $inparams[3];
                    $params = [];

                    $errormessage = "";
                    if (isset($idkey) && ((string)$idkey != "0")) {
                        //Это получение исходников
                        $params["idkey"] = $idkey;
                    }
                    $params["userid"] = $auth->getuserid();

                    if (mb_strtolower($format) == "base64") {
                        $this->phpinput = base64_decode($this->phpinput);
                    }
                    $params["fileprocessing"] = $this->phpinput;

                    if ($version == "v4") {
                        $this->createcomponentv4($params);
                    }
                    else {
                        $this->createcomponentv3($params);
                    }

                    $result = $this->uploadcomponent($params);

                } else {
                    $result["error"] = "You haven't role add components";
                }
            } else {
                $result["error"] = "Error authorization";
            }
        } else {
            $result["error"] = "Error bus authorization";
        }

        return $result;
    }


    private function pr_hasuserdb($dbname, $iduser)
    {
        $result = ["result" => false];

        $params = [];
        $params["dbname"] = $dbname;
        $params["iduser"] = $iduser;
        $params["delfield"] = 0;

        $has = $this->get("mpuserdb", ["id"], ["AND" => $params]);
        if ($has) {
            $result = ["result" => true, "id" => $has["id"]];
        } else {
            $result["error"] = "Error DB exist";
        }
        return $result;
    }

    public function hasuserdb($inparams)
    {
        $result = ["result" => false];
        if (class_exists("Auth")) {
            $auth = new Auth();
            if ($auth->userauth()) {

                $dbname = $inparams[1];
                $iduser = $auth->getuserid();
                $result = $this->pr_hasuserdb($dbname, $iduser);

            } else {
                $result["error"] = "Error authorization";
            }
        } else {
            $result["error"] = "Error bus authorization";
        }

        return $result;
    }

    public function adduserdb($inparams)
    {
        $result = ["result" => false];
        if (class_exists("Auth")) {
            $auth = new Auth();
            if ($auth->userauth()) {

                $where = [];
                $where["dbname"] = $inparams[1];
                $where["iduser"] = $auth->getuserid();
                $has = $this->has("mpuserdb", ["AND" => $where]);

                $params = $where;
                $params["servername"] = $inparams[2];
                $params["typeservername"] = $inparams[3];
                $params["delfield"] = 0;
                if (!$has) {
                    $this->insert("mpuserdb", $params);
                    $result = ["result" => true];
                } else {
                    $del = $this->update("mpuserdb", $params, ["AND" => $where]);
                    $result = ["result" => true];
                }

                if (($params["servername"] != "") && ($params["typeservername"] != "")) {
                    $info = $auth->infouser($where["iduser"]);

                    $exparam["basicauth"] = [
                        "username" => $this->defaultuser["login"],
                        "password" => $this->defaultuser["password"]
                    ];
                    $res = $this->http_c_post($params["typeservername"] . "://" . $params["servername"] . "/marketplace/forseauth/", $info["info"], $exparam);
                }

            } else {
                $result["error"] = "Error authorization";
            }
        } else {
            $result["error"] = "Error bus authorization";
        }

        return $result;
    }

    public function deluserdb($inparams)
    {
        $result = ["result" => false];
        if (class_exists("Auth")) {
            $auth = new Auth();
            if ($auth->userauth()) {

                $params = [];
                $params["dbname"] = $inparams[1];
                $params["iduser"] = $auth->getuserid();

                $del = $this->update("mpuserdb", ["delfield" => 1], ["AND" => $params]);
                if ($del) {
                    $result = ["result" => true];
                } else {
                    $result["error"] = "Error DB detele";
                }

            } else {
                $result["error"] = "Error authorization";
            }
        } else {
            $result["error"] = "Error bus authorization";
        }

        return $result;
    }


    private function savedump($idcomponent, $iddb, $iduser, $content)
    {
        $result = ["result" => false];

        $componentinfo = $this->pr_componentinfo($idcomponent);

        $filename = date("YmdHis") . ".json";

        $md5processing = $this->cloud->upload([
            "upload",
            "Marketplace",
            $componentinfo["info"]["name"],
            $componentinfo["info"]["version"],
            "dumps",
            $iduser,
            $iddb,
            $filename
        ], $content);

        $dates = [
            "iduser" => $iduser,
            "iddb" => $iddb,
            "idcomponent" => $idcomponent,
            "md5filedump" => $md5processing["id"]
        ];
        $id = $this->insert("mperrordumps", $dates);
        if ($id) {
            //Отправить письмо
            $emailtext = "Пользователь системы ".$iddb." отправил отладочную информацию по загрузке компоненты: ".$componentinfo["info"]["name"]."\r\n
            id компоненты: ".$idcomponent."";
            $componentowner = $componentinfo["info"]["iduser"];
            $userinfo = $this->Auth->infouser($componentowner);
            $emailowner = ""; //После теста удалить  TODO email администратора
            if ($userinfo["result"]) {
                $emailowner = $userinfo["info"]["login"];
            }
            $Mail = new Mail();
            $Mail->SendMail([$emailowner], "Пользователь отправил отладку, из-за ошибки загруки", $emailtext, [["content" => $content, "filename" => $filename]]);
            $result = ["result" => true, "id" => $id, "emailowner"=> $emailowner];
        }

        return $result;
    }

    public function senddump($inparams)
    {
        $result = ["result" => false];
        if (class_exists("Auth")) {
            $auth = new Auth();
            if ($auth->userauth()) {

                $dbname = $inparams[1];
                $iduser = $auth->getuserid();
                $result = $this->pr_hasuserdb($dbname, $iduser);
                if ($result["result"]) {

                    $idcomponent = $inparams[2];
                    $iddb = $result["id"];
                    $content = $this->phpinput;
                    $result = $this->savedump($idcomponent, $iddb, $iduser, $content);


                } else {
                    $result["error"] = "DB not connect to Marketplace";
                }

            } else {
                $result["error"] = "Error authorization";
            }
        } else {
            $result["error"] = "Error bus authorization";
        }

        return $result;
    }

    private function vesioncompare($value1, $value2)
    {
        $ar1 = explode(".", $value1);
        $ar2 = explode(".", $value2);

        $result = "=";
        if (($value2 == "") || ($value1 == "")) {

        } else {
            foreach ($ar1 as $key => $val) {

                if ((int)$val > (int)$ar2[$key]) {
                    $result = ">";
                    break;
                } elseif ((int)$val < (int)$ar2[$key]) {
                    $result = "<";
                    break;
                }
            }
        }

        return $result;
    }

    private function pr_getupdatelist($listdb)
    {

        $result = ["result" => false];
        $returnlist = [];
        foreach ($listdb as $value) {

            $hash = $this->get("mpcomponents", ["id", "hash", "version", "minversion", "maxversion"], ["id" => $value["key"]]);
            if ($hash) {

                $mincompare = $this->vesioncompare($value["versiondb"], $hash["minversion"]);
                $maxcompare = $this->vesioncompare($value["versiondb"], $hash["maxversion"]);

                if (($hash["version"] != $value["version"]) &&
                    (($mincompare == ">") || ($mincompare == "=")) &&
                    (($maxcompare == "<") || ($maxcompare == "="))
                ) {
                    $vl = [];
                    $vl["key"] = $value["key"];
                    $vl["hash"] = $hash["hash"];
                    $vl["hash"] = $hash["version"];
                    $returnlist[] = $vl;
                }
            }
        }

        if (count($returnlist) > 0) {
            $result = ["result" => true, "listupdate" => $returnlist];
        } else {
            $result["error"] = "No updates";
        }

        return $result;
    }

    public function getupdatelist($inparams)
    {

        $result = ["result" => false];
        if (class_exists("Auth")) {
            $auth = new Auth();
            if ($auth->userauth()) {

                $strlistdb = $this->phpinput;
                $listdb = json_decode($strlistdb, true);
                $result = $this->pr_getupdatelist($listdb);

            } else {
                $result["error"] = "Error authorization";
            }
        } else {
            $result["error"] = "Error bus authorization";
        }

        return $result;
    }

    public function resetpassword($login)
    {
        $result = ["result" => false];
        if (class_exists("Auth")) {
            $auth = new Auth();
            $result = $auth->sendpass($login);
        } else {
            $result["error"] = "Error bus authorization";
        }
        return $result;
    }


    public function forseauth($inparams)
    {
        $result = ["result" => false];
        if (class_exists("Auth")) {
            $auth = new Auth();
            if ($auth->userauth()) {
                $login = $_POST["login"];
                $password = $_POST["password"];

                //print_r($_POST);
                $inparams = ["", $login, $password];
                $result = $auth->forseadduser($inparams);

            } else {
                $result["error"] = "Error authorization";
            }
        } else {
            $result["error"] = "Error bus authorization";
        }

        return $result;
    }

    public function installcomponent($inparams)
    {

        $result = ["result" => false];
        if (class_exists("Auth")) {
            $auth = new Auth();
            if ($auth->userauth()) {

                $userid = $auth->getuserid();
                $key = $inparams[1];

                //1. получить компонент из маркет плейса
                $exparam["basicauth"] = [
                    "username" => $this->defaultuser["login"],
                    "password" => $this->defaultuser["password"]
                ];
                $res = $this->http_c_post("https://btrip.ru/marketplace/downloadfullcomponent/" . $key . "/", "", $exparam);
                $zipcontent = $res["content"];

                $clientdir = trim($_SERVER["DOCUMENT_ROOT"] . "/client"); //если скрипт приватный надо копировать в папку private!!!

                $path = tempnam('/tmp', 'tmpzip_') . ".zip";
                file_put_contents($path, $zipcontent);
                //2. вытащить из архива php файл
                $zip = new ZipArchive;
                $res = $zip->open($path);
                if ($res === TRUE) {

                    $manifest = $zip->getFromName('manifest.json');//trim(file_get_contents($tmldir . "/" . $fileinfo['basename']));
                    for ($i = 0; $i <= 31; ++$i) {
                        $manifest = str_replace(chr($i), "", $manifest);
                    }
                    $checkLogin = str_replace(chr(127), "", $manifest);
                    if (0 === strpos(bin2hex($manifest), 'efbbbf')) {
                        $manifest = substr($manifest, 3);
                    }

                    $manifest = json_decode($manifest, true);
                    if (isset($manifest["privateserverclass"])) {
                        if ($manifest["privateserverclass"] == true) {
                            $clientdir = trim($_SERVER["DOCUMENT_ROOT"] . "/private");
                        }
                    }

                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        $fileinfo = pathinfo($filename);
                        if ($fileinfo["extension"] == "php") {
                            //3. записать его в папку Client
                            copy("zip://" . $path . "#" . $filename, $clientdir . "/" . $fileinfo['basename']);
                        }
                    }
                }

                //4.Удаляем временный архив
                unlink($path);


                $result = ["result" => true];
            } else {
                $result["error"] = "Error authorization";
            }
        } else {
            $result["error"] = "Error bus authorization";
        }
        return $result;
    }

    public function sharecomponent($inparams)
    {
        $result = ["result" => false];
        if (class_exists("Auth")) {
            $auth = new Auth();
            if ($auth->userauth()) {
                $userid = $auth->getuserid();
                $key = $inparams[1];
                $users = $this->phpinput;

                $where = ["AND" => ["iduser" => $userid, "id" => $key]];
                $res = $this->get("mpcomponents", ["id"], $where);
                if ($res) {

                    //$where = ["AND" => ["iduser" => $userid, "idcomponent" => $key]];
                    $this->delete("mpusercomponents", ["idcomponent" => $key]);

                    $usersar = json_decode($users, true);
                    foreach ($usersar as $value) {

                        $iduser = $this->get("users", ["id"], ["login" => $value["user"]]);
                        if ($iduser) {
                            $this->insert("mpusercomponents", ["iduser" => $iduser["id"], "idcomponent" => $key, "delfield" => 0]);
                        }
                    }
                    $result = ["result" => true, "message" => "Добавлен доступ к компоненте пользователям"];
                } else {
                    $result["message"] = "Компонент не принадлежит пользователю, изменение запрещено";
                }
            }
        }

        return $result;
    }

    public function getlistsharecomponent($inparams)
    {
        $result = ["result" => false];
        //print_r($inparams);
        if (class_exists("Auth")) {
            $auth = new Auth();
            if ($auth->userauth()) {
                $userid = $auth->getuserid();
                $key = $inparams[1];

                $res = $this->select("mpusercomponents",
                    [
                        "[>]mpcomponents" => ["idcomponent" => "id"],
                        "[>]users" => ["iduser" => "id"]
                    ],
                    [
                        "mpusercomponents.id",
                        "mpusercomponents.iduser",
                        "users.login(user)",
                        "mpusercomponents.idcomponent"
                    ], [
                        "AND" => [
                            "mpusercomponents.idcomponent" => $key,
                            "mpcomponents.iduser" => $userid,
                            "mpusercomponents.delfield" => 0
                        ]
                    ]
                );

                if ($res) {
                    $result = ["result" => true, "list" => $res];
                }


            }
        }

        return $result;
    }


    public function setsettingscomponent($inparams)
    {
        $result = ["result" => false];

        $dir = $_SERVER["DOCUMENT_ROOT"];
        $fname = $inparams[1].".json";
        $exdir = $dir."/private/settings";

        if (!file_exists($dir."/private")) {
            mkdir($dir."/private");
        }
        if (!file_exists($dir."/private"."/settings")) {
            mkdir($dir."/private"."/settings");
        }

        $json = $this->phpinput;
        file_put_contents($exdir."/".$fname, $json);

        return $result;
    }

}