<?php

class Parser extends ex_class
{

    private $metod;
    private $Auth;
    private $connectionInfo;

    public function CreateDB()
    {
        $info["Parser_counter"] = [
            "id" => ['type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true],
            "class" => ['type' => 'varchar(55)', 'null' => 'NOT NULL'],
            "user" => ['type' => 'int(15)', 'null' => 'NOT NULL'],
            "count" => ['type' => 'int(15)', 'null' => 'NOT NULL']
        ];

        $info["Parser_fileparse"] = [
            "id" => ['type' => 'int(15)', 'null' => 'NOT NULL', 'inc' => true],
            "createdate" => ['type' => 'datetime', 'null' => 'NOT NULL'],
            "updatedate" => ['type' => 'datetime', 'null' => 'NOT NULL'],
            "class" => ['type' => 'varchar(55)', 'null' => 'NOT NULL'],
            "user" => ['type' => 'int(15)', 'null' => 'NOT NULL'],
            "md5file" => ['type' => 'varchar(55)', 'null' => 'NOT NULL'],
            "count" => ['type' => 'int(15)', 'null' => 'NOT NULL'],
            "serverinfo" => ['type' => 'text', 'null' => 'NOT NULL']
        ];


        $this->create($this->connectionInfo['database_type'], $info);
    }

    public function InstallModule()
    {
        $this->Auth->setrule("Parser-Btrip-FullParse", "Parser-Btrip-FullParse", "Get the full version of the analyzers", true);
    }


    private function GetUserCountAll($name, $userid = 0)
    {
        $where = [
            "AND" =>
                [
                    "createdate[>]" => date("Y-m-d H:i:s", strtotime("-14 day"))
                ]
        ];

        if ($userid != 0) {
            $where["AND"]["user"] = $userid;
        }

        $res = $this->select("Parser_fileparse",
            [
                "createdate",
                "count"
            ],
            $where
        );

        $dates = [];
        for ($i=13; $i>=0; $i--) {
            $key = date("Y-m-d\T00:00:00.000\Z", strtotime("-$i day"));
            $dates[] = $key;
        }

        $tableres = [];
        foreach ($res as $value) {
            $key = date("Y-m-d\T00:00:00.000\Z", strtotime($value["createdate"]));
            $tableres[$name][$key] += $value["count"];
        }

        $newtableres = [];
        foreach ($tableres as $key => $value) {
            $line = [];
            foreach ($dates as $date) {
                $line[] = isset($value[$date]) ? $value[$date] : 0;
            }

            //$newtableres[$key] = $line;
            $newtableres[] = [
                "name" => $key,
                "data" => $line
            ];
        }

        return ["dates" => $dates, "series" => $newtableres];
    }


    private function GetUserCount($userid = 0)
    {

        $where = [
            "AND" =>
                [
                    "createdate[>]" => date("Y-m-d H:i:s", strtotime("-14 day"))
                ]
        ];

        if ($userid != 0) {
            $where["AND"]["user"] = $userid;
        }

        $res = $this->select("Parser_fileparse",
            [
                "class",
                "createdate",
                "count"
            ],
            $where
        );


        $dates = [];
        for ($i=13; $i>=0; $i--) {
            $key = date("Y-m-d\T00:00:00.000\Z", strtotime("-$i day"));
            $dates[] = $key;
        }

//        $datesstr = implode('" , "', $dates);
//        $datesstr = '["'.$datesstr.'"]';

        $tableres = [];
        foreach ($res as $value) {
            $key = date("Y-m-d\T00:00:00.000\Z", strtotime($value["createdate"]));
            $tableres[$value["class"]][$key] += 1;//$value["count"];
        }

        $newtableres = [];
        foreach ($tableres as $key => $value) {
            $line = [];
            foreach ($dates as $date) {
                $line[] = isset($value[$date]) ? $value[$date] : 0;
            }

            //$newtableres[$key] = $line;
            $newtableres[] = [
                "name" => $key,
                "data" => $line
            ];
        }

        return ["dates" => $dates, "series" => $newtableres];
    }

    public function GetStatistic()
    {
        $userid = 0;
        if (!$this->Auth->is_su()){
            $userid = $this->Auth->getuserid();
        }

        $allcounter = $this->select("Parser_counter", "*");
        $AllCount = 0;
        $AllUserCount = 0;

        $userParser = [];
        $ParserCount = [];

        foreach ($allcounter as $value) {
            $AllCount += $value["count"];
            $ParserCount[$value["class"]] += $value["count"];

            if ($value["user"] == $userid) {
                $AllUserCount += $value["count"];
                $userParser[$value["class"]] += $value["count"];
            }
        }

        $ProcentFull = round($AllUserCount / $AllCount * 100, 2);

        $GetUserCount = $this->GetUserCount($userid);

        $GetUserCountAll = $this->GetUserCountAll("You files", $userid);
        $GetAllUserCountAll = $this->GetUserCountAll("Total files");

        $merges = array_merge($GetUserCountAll["series"], $GetAllUserCountAll["series"]);
        $Static = [
            "AllCount" => $AllCount,
            "AllUserCount" => $AllUserCount,
            "ProcentFull" => $ProcentFull,

            "dates" => $GetUserCount["dates"],
            "series" => $GetUserCount["series"],

            "seriestotal" => $merges,

            "ParserCount" => $ParserCount,
            "userParser" => $userParser,
        ];

        return $Static;
    }

    public function __construct($metod = "", $debug = false)
    {
        $this->metod = $metod;
        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем настройки подключения к БД
        parent::__construct($this->connectionInfo, $debug);

        $this->Auth = new Auth();
    }


    public function SetUseParser($class, $md5file, $userid)
    {
        $serverinfo = json_encode($this->SERVER, JSON_UNESCAPED_UNICODE);

        $Parser_counter = [
            "class" => $class,
            "user" => $userid
        ];

        $info = $this->get("Parser_counter", ["id"], ["class" => $class, "user" => $userid]);
        if (!isset($info["id"])) {
            $Parser_counter["count"] = 1;
            $id = $this->insert("Parser_counter", $Parser_counter);
        } else {
            $Parser_counter["count[+]"] = 1;
            $this->update("Parser_counter", $Parser_counter, ["id" => $info["id"]]);
        }


        $Parser_fileparse =[
            "class" => $class,
            "user" => $userid,
            "md5file" => $md5file,
            "serverinfo" => $serverinfo,
            "updatedate" => date("Y-m-d H:i:s")
        ];

        $info = $this->get("Parser_fileparse", ["id"], ["class" => $class, "user" => $userid, "md5file" => $md5file]);
        if (!isset($info["id"])) {
            $Parser_fileparse["count"] = 1;
            $Parser_fileparse["createdate"] = date("Y-m-d H:i:s");
            $id = $this->insert("Parser_fileparse", $Parser_fileparse);
        } else {
            $Parser_fileparse["count[+]"] = 1;
            $this->update("Parser_fileparse", $Parser_fileparse, ["id" => $info["id"]]);
        }


    }

    public function Init($Params)
    {
        if ($Params[count($Params)-1] == "debug") {
            $this->debugclass = true;
            unset($Params[count($Params)-1]);
        }

        if ($this->debugclass) {
            print_r("DEBUG Parser\r\n");
        }

        $this->Auth->userauth();

        $result = array();
        $result["result"] = false;
        $result["error"] = "Error function call";

        if ($this->metod == "POST") {
            if ($Params[0] == "Amadeus") {
                $result = $this->Amadeus($Params);
            } elseif ($Params[0] == "Galileo") {
                $result = $this->Galileo($Params);
            } elseif ($Params[0] == "Sabre") {
                $result = $this->Sabre($Params);
            } elseif ($Params[0] == "Sirena") {
                $result = $this->Sirena($Params);
            } elseif ($Params[0] == "S7") {
                $result = $this->S7($Params);
            }

            elseif ($Params[0] == "Academservice") {
                $result = $this->Academservice($Params);
            } elseif ($Params[0] == "Ariadna") {
                $result = $this->Ariadna($Params);
            } elseif ($Params[0] == "Bronevik") {
                $result = $this->Bronevik($Params);
            } elseif ($Params[0] == "Hotelbook") {
                $result = $this->Hotelbook($Params);
            } elseif ($Params[0] == "Ostrovok") {
                $result = $this->Ostrovok($Params);
            } elseif ($Params[0] == "HotelBeds") {
                $result = $this->HotelBeds($Params);
            }


            elseif ($Params[0] == "Im") {
                $result = $this->Im($Params);
            } elseif ($Params[0] == "Cexpress") {
                $result = $this->Cexpress($Params);
            } elseif ($Params[0] == "Teletrain") {
                $result = $this->Teletrain($Params);
            } elseif ($Params[0] == "Ufs") {
                $result = $this->Ufs($Params);
            }


            elseif ($Params[0] == "Iway") {
                $result = $this->Iway($Params);
            }


            elseif ($Params[0] == "Myagent") {
                $result = $this->Myagent($Params);
            } elseif ($Params[0] == "Corteoscb") {
                $result = $this->Corteoscb($Params);
            } elseif ($Params[0] == "Portbilet") {
                $result = $this->Portbilet($Params);
            } elseif ($Params[0] == "Aviacenter") {
                $result = $this->Aviacenter($Params);
            }



        }

        return $result;
    }

    private function Amadeus($Params)
    {
        $result = ["result" => false];

        if (class_exists("AmadeusLite")) {
            $fileinfo = $this->POST["fileinfo"];
            unset($this->POST["fileinfo"]);

            $AmadeusLite = loader("AmadeusLite", $this->metod, $this->debugclass);
            $AmadeusLite->POST = $this->POST;
            $AmadeusLite->phpinput = $fileinfo;

            $result = $AmadeusLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found AmadeusLite parser"];

        }

        return $result;
    }

    private function Galileo($Params)
    {
        $result = ["result" => false];

        if (class_exists("GalileoLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $GalileoLite = loader("GalileoLite", $this->metod, $this->debugclass);
            $GalileoLite->phpinput = $fileinfo;
            $result = $GalileoLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found GalileoLite parser"];

        }

        return $result;
    }
    private function Sabre($Params)
    {
        $result = ["result" => false];

        if (class_exists("SabreLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $SabreLite = loader("SabreLite", $this->metod, $this->debugclass);
            $SabreLite->phpinput = $fileinfo;
            $result = $SabreLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found SabreLite parser"];

        }

        return $result;
    }
    private function Sirena($Params)
    {
        $result = ["result" => false];

        if (class_exists("SirenaLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $SirenaLite = loader("SirenaLite", $this->metod, $this->debugclass);
            $SirenaLite->phpinput = $fileinfo;
            $result = $SirenaLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found SirenaLite parser"];

        }

        return $result;
    }
    private function S7($Params)
    {
        $result = ["result" => false];

        if (class_exists("S7Lite")) {
            $fileinfo = $this->POST["fileinfo"];

            $SirenaLite = loader("S7Lite", $this->metod, $this->debugclass);
            $SirenaLite->phpinput = $fileinfo;
            $result = $SirenaLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found S7Lite parser"];

        }

        return $result;
    }

    private function Academservice($Params)
    {
        $result = ["result" => false];

        if (class_exists("AcademserviceLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $ModelLite = loader("AcademserviceLite", $this->metod, $this->debugclass);
            $ModelLite->phpinput = $fileinfo;
            $result = $ModelLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found AcademserviceLite parser"];

        }

        return $result;
    }
    private function Ariadna($Params)
    {
        $result = ["result" => false];

        if (class_exists("AriadnaLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $ModelLite = loader("AriadnaLite", $this->metod, $this->debugclass);
            $ModelLite->phpinput = $fileinfo;
            $result = $ModelLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found AriadnaLite parser"];

        }

        return $result;
    }
    private function Bronevik($Params)
    {
        $result = ["result" => false];

        if (class_exists("BronevikLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $ModelLite = loader("BronevikLite", $this->metod, $this->debugclass);
            $ModelLite->phpinput = $fileinfo;
            $result = $ModelLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found BronevikLite parser"];

        }

        return $result;
    }
    private function Hotelbook($Params)
    {
        $result = ["result" => false];

        if (class_exists("HotelbookLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $ModelLite = loader("HotelbookLite", $this->metod, $this->debugclass);
            $ModelLite->phpinput = $fileinfo;
            $result = $ModelLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found HotelbookLite parser"];

        }

        return $result;
    }
    private function Ostrovok($Params)
    {
        $result = ["result" => false];

        if (class_exists("OstrovokLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $ModelLite = loader("OstrovokLite", $this->metod, $this->debugclass);
            $ModelLite->phpinput = $fileinfo;
            $result = $ModelLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found OstrovokLite parser"];

        }

        return $result;
    }
    private function HotelBeds($Params)
    {
        $result = ["result" => false];

        if (class_exists("HotelBedsLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $ModelLite = loader("HotelBedsLite", $this->metod, $this->debugclass);
            $ModelLite->phpinput = $fileinfo;
            $result = $ModelLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found HotelBedsLite parser"];

        }

        return $result;
    }

    private function Im($Params)
    {
        $result = ["result" => false];

        if (class_exists("ImLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $ModelLite = loader("ImLite", $this->metod, $this->debugclass);
            $ModelLite->phpinput = $fileinfo;
            $result = $ModelLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found ImLite parser"];

        }

        return $result;
    }
    private function Cexpress($Params)
    {
        $result = ["result" => false];

        if (class_exists("CexpressLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $ModelLite = loader("CexpressLite", $this->metod, $this->debugclass);
            $ModelLite->phpinput = $fileinfo;

            $result = $ModelLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found CexpressLite parser"];

        }

        return $result;
    }
    private function Teletrain($Params)
    {
        $result = ["result" => false];

        if (class_exists("TeletrainLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $ModelLite = loader("TeletrainLite", $this->metod, $this->debugclass);
            $ModelLite->phpinput = $fileinfo;
            $result = $ModelLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found TeletrainLite parser"];

        }

        return $result;
    }
    private function Ufs($Params)
    {
        $result = ["result" => false];

        if (class_exists("UfsLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $ModelLite = loader("UfsLite", $this->metod, $this->debugclass);
            $ModelLite->phpinput = $fileinfo;
            $result = $ModelLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found UfsLite parser"];

        }

        return $result;
    }

    private function Iway($Params)
    {
        $result = ["result" => false];

        if (class_exists("IwayLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $ModelLite = loader("IwayLite", $this->metod, $this->debugclass);
            $ModelLite->phpinput = $fileinfo;
            $result = $ModelLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found IwayLite parser"];

        }

        return $result;
    }

    private function Myagent($Params)
    {
        $result = ["result" => false];


        if (class_exists("MyagentLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $ModelLite = loader("MyagentLite", $this->metod, $this->debugclass);
            $ModelLite->phpinput = $fileinfo;
            $result = $ModelLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found MyagentLite parser"];

        }

        return $result;
    }
    private function Corteoscb($Params)
    {
        $result = ["result" => false];

        if (class_exists("CorteoscbLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $ModelLite = loader("CorteoscbLite", $this->metod, $this->debugclass);
            $ModelLite->phpinput = $fileinfo;
            $result = $ModelLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found CorteoscbLite parser"];

        }

        return $result;
    }
    private function Portbilet($Params)
    {
        $result = ["result" => false];

        if (class_exists("PortbiletLite")) {
            $fileinfo = $this->POST["fileinfo"];

            $ModelLite = loader("PortbiletLite", $this->metod, $this->debugclass);
            $ModelLite->phpinput = $fileinfo;
            $result = $ModelLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found PortbiletLite parser"];

        }

        return $result;
    }

    private function Aviacenter($Params)
    {
        $result = ["result" => false];

        if (class_exists("AviacenterLite")) {
            $fileinfo = $this->POST["fileinfo"];
            unset($this->POST["fileinfo"]);

            $ModelLite = loader("AviacenterLite", $this->metod, $this->debugclass);
            $ModelLite->POST = $this->POST;
            $ModelLite->phpinput = $fileinfo;

            $result = $ModelLite->getinfo($Params);
        }
        else {
            $result["errors"] = ["not found AviacenterLite parser"];

        }

        return $result;
    }


}