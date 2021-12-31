<?php

class GalileoClient extends ex_component
{
    private $metod;
    private $Auth;
    private $Catalogs;


    public function __construct($metod = "")
    {
        parent::__construct($metod);
        $this->metod = $metod;

        $this->Auth = new Auth();
        $this->Catalogs = new Catalogs();
    }

    private function importCatalog($json)
    {
        $result = $json;

        $services = $this->DTV($json, ["jsonv3", "services"]);

        $cashCode = [];
        $newjson = [];
        foreach ($services as $service) {


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


                $service["Route"] = implode(" - ", $Route);
            }

            $newjson[] = $service;
        }

        $result["jsonv3"]["services"] = $newjson;

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

//            $this->gds["connector"] = 1;
//            $post = $this->gds;
            $post["fileinfo"] = $text;

            $res = $this->http_c_post("https://btrip.ru/Parser/Galileo/", $post, $exparam);

            if (isset($res["content"])) {
                $result = json_decode($res["content"], true);
                $result = $this->importCatalog($result);
            }

        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }


}