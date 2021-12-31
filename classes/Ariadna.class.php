<?php

class Ariadna extends ex_classlite
{

    private $metod;
    private $Auth;

    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        parent::__construct();
        $this->metod = $metod;
        $this->debugclass = $debug;
        $this->Auth = new Auth();
    }


    private function jv3($jsonv)
    {
        $services = [];
        $serviceblank = $this->get_empty_v3();

        $serviceblank["creationdate"] = $this->DTV($jsonv, ["@attributes", "RegistrationDate"]);
        $serviceblank["Synh"] = "ada_" . $this->DTV($jsonv, ["@attributes", "Id"]);

        $serviceblank["creationdate"] = $this->DTV($jsonv, ["@attributes", "RegistrationDate"], "", "Y-m-d");

        $serviceblank["EndDate"] = $this->DTV($jsonv, ["@attributes", "EndDate"], "", "Y-m-d");


        $Accommodations = $Accommodation = $this->DTV($jsonv, ["AccommodationList", "Accommodation"]);
        if (isset($Accommodation["@attributes"])) {
            $Accommodations = [];
            $Accommodations[] = $Accommodation;
        }

        foreach ($Accommodations as $Accommodation) {

            $service = $serviceblank;

            $dd = $this->DTV($Accommodation, ["@attributes", "ArrivalDate"]);
            $tt = $this->DTV($Accommodation, ["@attributes", "ArrivalTime"]);
            if ($tt == "") {
                $tt = "00:00";
            }
            $service["ServiceStartDate"] = $this->DTV([$dd . " " . $tt], [0], "", "Y-m-d H:i");


            $dd = $this->DTV($Accommodation, ["@attributes", "DepartureDate"]);
            $tt = $this->DTV($Accommodation, ["@attributes", "DepartureTime"]);
            if ($tt == "") {
                $tt = "00:00";
            }
            $service["ServiceEndDate"] = $this->DTV([$dd . " " . $tt], [0], "", "Y-m-d H:i");

            $service["date"] = $service["ServiceEndDate"];


            //Поставщик
            $service["price"] = (float)$this->DTV($jsonv, ["DocumentList", "Document", "@attributes", "Amount"]);
            $service["amount"] = $service["price"];
            $service["amountVAT"] = (float)$this->DTV($jsonv, ["DocumentList", "Document", "@attributes", "VAT"]);
            if ($service["amountVAT"] == 0) {
                $service["VATrate"] = -1;
            } else {
                $service["VATrate"] = round($service["amountVAT"] / $service["price"] * 118);
            }

            //Клиент
            $service["pricecustomer"] = (float)$this->DTV($Accommodation, ["@attributes", "Price"]);
            $service["amountclient"] = (float)$service["pricecustomer"];
            $service["VATratecustomer"] = $service["VATrate"];
            if ($service["VATratecustomer"] == -1) {
                $service["amountVATcustomer"] = 0;
            } else {
                $service["amountVATcustomer"] = round($service["pricecustomer"] / (100 + $service["VATratecustomer"]) * $service["VATratecustomer"]);
            }
            $service["AmountServices"] = $service["amountclient"];


            $service["supplier"] = ["INN" => "7730557534", "KPP" => "773001001", "Name" => "Эй энд Эй"];
            $service["Supplier"] = $service["supplier"];

            $service["nomenclature"] = "Проживание";
            $idStatus = (int)$this->DTV($Accommodation, ["Status", "@attributes", "Id"]);
            if ($idStatus == 65) {
                $service["TypeOfTicket"] = "S";
            } else {
                $service["TypeOfTicket"] = "V";
            }

            $City = $this->DTV($Accommodation, ["City", "@attributes", "Name"]);
            $service["CityArrival"] = $City;
            $service["PlaceArrival"] = $City;
            $service["Arrival"] = $service["ServiceStartDate"];

            $HotelName = $this->DTV($Accommodation, ["Hotel", "@attributes", "Name"]);
            $service["HotelName"] = $HotelName;

            $diff = strtotime($service["ServiceEndDate"]) - strtotime($service["ServiceStartDate"]);
            $service["Night"] = round(abs($diff) / 60 / 60 / 24);


            $Product = $this->DTV($Accommodation, ["Product", "@attributes", "RoomName"]);
            $service["NumberTypeName"] = $Product;


            //$service["contractor"] = ["INN" => "", "KPP" => "", "Name" => ""];;

            $sSecondeds = $this->DTV($Accommodation, ["Persons", "Person"]);
            $Secondeds = $sSecondeds;
            if (isset($sSecondeds["@attributes"])) {
                $Secondeds = [];
                $Secondeds[] = $sSecondeds;
            }
            foreach ($Secondeds as $valueS) {
                $Seconded = [];

                $Name = $this->DTV($valueS, ["@attributes", "Name"]);
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

                $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
                $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);

                $service["Seconded"][] = $Seconded;
            }

            $service["date"] = $service["ServiceEndDate"];
            $services[] = $service;
        }


        $jsonv3["services"] = $services;

        return $jsonv3;
    }

    public function getinfo($params)
    {

        $result = ["result" => false];

        if ($this->Auth->userauth()) {
            if ($params[1] == "v3") {

                $result["result"] = true;

                $text = $this->phpinput;
                $Parser = new Parser($this->metod);
                $Parser->SetUseParser("Ariadna", md5($text), $this->Auth->getuserid());

                $textxml = $this->phpinput;
                $textxml = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $textxml);
                $xml = simplexml_load_string($textxml);
                $jsonv = $this->object2array($xml);
                //print_r($jsonv);

                $result["jsonv"] = $jsonv;
                $result["jsonv3"] = $this->jv3($jsonv);

            } else {
                $result["error"] = "Not for this version";
            }
        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }

}
