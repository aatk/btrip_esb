<?php

class Iway extends ex_classlite
{

    private $metod;
    private $classname;
    private $gds;

    private $Auth;

    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        parent::__construct();
        $this->metod = $metod;

        if (isset($this->POST["connector"])) {
            $this->gds = $this->POST;
        } else {
            $this->gds = $_SESSION["i4b"][$this->classname];
        }

        $this->debugclass = $debug;
        $this->Auth = new Auth();
    }

    private function jv3($injsonv)
    {
        $services = [];

        foreach ($injsonv as $jsonv) {
            $service = $this->get_empty_v3();

            $service["Synh"] = "iway_" . $this->DTV($jsonv, ["OrderID"]);
            $service["manager"] = (string)$this->DTV($jsonv, ["UserID"]);


            $dd = $this->DTV($jsonv, ["Date"]);
            try {
                $format = "Y-m-d H:i";
                $date = DateTime::createFromFormat($format, $dd);
                $service["date"] = $date->format("YmdHis"); //!!!!!
            } catch (Exception $e) {
                $service["date"] = "";
            }

            $dd = $this->DTV($jsonv, ["DateArrival"]);
            try {
                $format = "Y-m-d H:i";
                $date = DateTime::createFromFormat($format, $dd);
                $service["ServiceStartDate"] = $date->format("YmdHis"); //!!!!!
            } catch (Exception $e) {
                $service["ServiceStartDate"] = "";
            }

            //$service["date"] = $service["creationdate"];

            $service["price"] = $this->DTV($jsonv, ["Price"]);
            $service["amount"] = $service["price"];

            $service["pricecustomer"] = $service["price"];
            $service["amountclient"] = $service["price"];

            $service["AmountServices"] = $service["price"];

            //Currency
            $service["nomenclature"] = "Трансфер";
            $service["TypeOfTicket"] = "S";

            $service["CarClass"] = $this->DTV($jsonv, ["CarClass"]);

            $service["supplier"] = ["INN" => "5407479940", "KPP" => "540701001", "Name" => "АйВэй Трансфер"];
            $service["Supplier"] = $service["supplier"];

            $service["AddressDeparture"] = $this->DTV($jsonv, ["LocationAddressObject", "address"]);
            $service["AddressDestination"] = $this->DTV($jsonv, ["DestinationAddressObject", "address"]);

            $service["Route"] = $service["AddressDeparture"]." - ".$service["AddressDestination"];


            $sSecondeds = $this->DTV($jsonv, ["Passengers"]);
            $Secondeds = $sSecondeds;

            foreach ($Secondeds as $valueS) {
                $Seconded = [];

                $Name = $this->DTV($valueS, ["Name"]);
                $ars = explode(" ", $Name);

                $Seconded["FirstName"] = $this->DTV($ars, [1]);
                $Seconded["LastName"] = $this->DTV($ars, [0]);
                $Seconded["SurName"] = $this->DTV($ars, [2]);
                $Seconded["FirstNameLatin"] = "";
                $Seconded["LastNameLatin"] = "";
                $Seconded["SurNameLatin"] = "";

                $Seconded["DocumentNumber"] = "";
                $Seconded["BirthDay"] = "";
                $Seconded["DocType"] = "";
                $Seconded["Phone"] = $this->DTV($valueS, ["Phone"]);
                $Seconded["Email"] = $this->DTV($valueS, ["Email"]);

                $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
                $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);

                $service["Seconded"][] = $Seconded;
            }

            $services[] = $service;
        }
        $jsonv3["services"] = $services;

        return $jsonv3;
    }

    public function getinfo($params)
    {

        $result = ["result" => false];

        if ($this->Auth->userauth()) {
        //if (true) {
            if ($params[1] == "v3") {

                $text = $this->phpinput;
                $Parser = new Parser($this->metod);
                $Parser->SetUseParser("Iway", md5($text), $this->Auth->getuserid());

                $result["result"] = true;

                $textxml = $this->phpinput;
                $withoutOrders = str_replace("Orders=", "", $textxml);
                $iwayjson = urldecode($withoutOrders);
                $objjs = json_decode($iwayjson, true);

                $result["jsonv"] = $objjs;
                $result["jsonv3"] = $this->jv3($objjs);

            } else {
                $result["error"] = "Not for this version";
            }
        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }

//    private function gettoken()
//    {
//
//        $data = '{"user_id":"' . $this->gds["userId"] . '", "password": "' . $this->gds["userPass"] . '"}';
//        $url = $this->gds["serveraddr"] . "/transnextgen/v1/auth/login";
//
//        $res = $this->http_c_post($url, $data);
//
//        $token = false;
//        $js = json_decode($res["content"], true);
//        if (isset($js["result"]) && $js["result"] != null) {
//            $token = $js["result"]["token"];
//        }
//
//        return $token;
//    }

//    public function loadorders($param1)
//    {
//
//        $token = $this->gettoken();
//        if ($token) {
//
//            $result = ["result" => true, "msg" => $token];
//        } else {
//            $result = ["result" => false, "error" => "Bad token"];
//        }
//
//        return $result;
//    }


}
