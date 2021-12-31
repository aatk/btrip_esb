<?php

class PortbiletClient extends ex_component
{
    private $metod;
    private $gds;

    private $Auth;
    private $Catalogs;//$getCatalogItems


    public function __construct($metod = "")
    {
        parent::__construct($metod);
        $this->metod = $metod;

        if (isset($this->POST["connector"])) {
            $this->gds = $this->POST;
        } else {
            $this->gds = $_SESSION["i4b"][mb_strtolower($this->classname)];
        }

        $this->Auth = new Auth();
        $this->Catalogs = new Catalogs();

    }

    public function getsettings()
    {
        $settings = [
            "ftpurl" => "",
            "ftplogin" => "",
            "ftppass" => "",
            "delfile" => "true"
        ];

        return $settings;
    }

    private function importCatalog($json)
    {
        $result = $json;

        $cashCode = [];
        $newjson = [];
        foreach ($json["services"] as $service) {

            if ($service["PlaceDeparture"] != "") {
                $DepartureCode = $service["PlaceDeparture"];

                $res = $this->Catalogs->getCatalogItems("Propertys", ["value" => $DepartureCode]);
                if ($res) {
                    $idplace = $res[0]["place"];
                    $res = $this->Catalogs->getCatalogItems("Places", ["id" => $idplace]);
                    if ($res) {
                        $service["PlaceDeparture"] = $res[0]["place"];

                        $findCity = true;
                        do {
                            if ($res[0]["type"] == "Город") {
                                $service["CityDeparture"] = $res[0]["place"];
                                $cashCode[$DepartureCode] = $service["CityDeparture"];
                            }
                            if (($res[0]["parent"] == 0) || ($res[0]["type"] == "Город")) $findCity = false;
                            $res = $this->Catalogs->getCatalogItems("Places", ["id" => $res[0]["parent"]]);
                        } while ($findCity);
                    }
                }
            }

            if ($service["PlaceArrival"] != "") {
                $ArrivalCode = $service["PlaceArrival"];

                $res = $this->Catalogs->getCatalogItems("Propertys", ["value" => $ArrivalCode]);
                if ($res) {
                    $idplace = $res[0]["place"];
                    $res = $this->Catalogs->getCatalogItems("Places", ["id" => $idplace]);
                    if ($res) {
                        $service["PlaceArrival"] = $res[0]["place"];

                        $findCity = true;
                        do {
                            if ($res[0]["type"] == "Город") {
                                $service["CityArrival"] = $res[0]["place"];
                                $cashCode[$ArrivalCode] = $service["CityArrival"];
                            }
                            if (($res[0]["parent"] == 0) || ($res[0]["type"] == "Город")) $findCity = false;
                            $res = $this->Catalogs->getCatalogItems("Places", ["id" => $res[0]["parent"]]);
                        } while ($findCity);
                    }
                }

            }

            if ($service["Route"] != "") {
                $NewRoute = [];
                $Route = explode("-", $service["Route"]);
                foreach ($Route as $RouteItem) {
                    $findcode = trim($RouteItem);
                    if (array_key_exists($findcode, $cashCode)) {
                        $NewRoute[] = $cashCode[$findcode];
                    } else {

                        $res = $this->Catalogs->getCatalogItems("Propertys", ["value" => $ArrivalCode]);
                        if ($res) {
                            $idplace = $res[0]["place"];
                            $res = $this->Catalogs->getCatalogItems("Places", ["id" => $idplace]);
                            if ($res) {
                                $findCity = true;
                                do {
                                    if ($res[0]["type"] == "Город") {
                                        $NewRoute[] = $res[0]["place"];
                                    }
                                    if (($res[0]["parent"] == 0) || ($res[0]["type"] == "Город")) $findCity = false;
                                    $res = $this->Catalogs->getCatalogItems("Places", ["id" => $res[0]["parent"]]);
                                } while ($findCity);
                            }
                        }
                    }
                }

                if (count($Route) == count($NewRoute)) {
                    $Route = $NewRoute;
                }
                $service["Route"] = $Route;
            }

            $newjson[] = $service;
        }

        $json["services"] = $newjson;

        return $result;
    }

    public function getinfo($params)
    {
        $result = ["result" => false];

        if ($this->Auth->userauth()) {
            $result["result"] = true;

            $text = $this->phpinput;

            $userinfo = $this->Auth->getusersessioninfo();
            $basicauth = [
                "username" => $userinfo["login"],
                "password" => $userinfo["basicpassword"]
            ];

            $exparam = [
                "basicauth" => $basicauth
            ];

            $this->gds["connector"] = 1;
            $post = $this->gds;
            $post["fileinfo"] = $text;

            $res = $this->http_c_post("https://btrip.ru/Parser/Portbilet/", $post, $exparam);

            if (isset($res["content"])) {
                $result = json_decode($res["content"], true);
                $result = $this->importCatalog($result);
            }

        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }

    /*
     *          Список заказов
     */

    public function loadorders($param1)
    {

        $result = ["result" => false];

        if ($this->Auth->userauth()) {

            $serverid = $param1[1];
            if ($serverid != "") {

                $tmpdir = $_SERVER["DOCUMENT_ROOT"] . "/tmp";

                if (isset($this->gds["ftpurl"])) {
                    $ordersid = [];

                    $url = $this->gds["ftpurl"];
                    $conn = ftp_connect($url);
                    $login_result = ftp_login($conn, $this->gds["ftplogin"], $this->gds["ftppass"]);

                    $files = ftp_nlist($conn, ".");
                    foreach ($files as $filename) {
                        $fileinfo = pathinfo($filename);
                        $ordersid[] = $fileinfo["basename"];
                        $localfile = $tmpdir . "/" . $fileinfo["basename"];
                        file_put_contents($localfile, "");
                        ftp_get($conn, $localfile, $filename, FTP_BINARY, 0);

                        if (isset($this->gds["delfile"])) {
                            if ($this->gds["delfile"] == "true") {
                                ftp_delete($conn, $filename);
                            }
                        }

                        $orderxml = file_get_contents($localfile);
                        //unlink($localfile);
                        $this->phpinput = $orderxml;
                        $this->savetiket([], 1);

                        $xml = simplexml_load_string($orderxml);
                        $json = $this->object2array($xml);
                        $id = $this->DTV($json, ["header", "@attributes", "ord_id"]);
                        //print_r($id);
                        //запишем транзакцию в лог
                        $this->settransaction($id);
                    }

                    ftp_close($conn);

                    $nowdate = date("Y-m-d H:i:s", time());
                    $this->update("returndoc", ["lastdate" => $nowdate], ["AND" => ["sever_id" => $serverid, "typedoc" => $this->classname]]);

                    $result = ["result" => true, "orders" => $ordersid];
                }
            } else {
                $result["error"] = "Server id fail";
            }

        } else {
            $result["error"] = "Authorization fail";
        }

        return $result;
    }


}
