<?php
//CommissionAmount
class Academservice extends ex_classlite
{

    private $classname;
    private $metod;
    private $gds;
    private $Auth;


    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        parent::__construct();
        $this->metod = $metod;

        if (isset($this->POST["connector"])) {
            $this->gds = $this->POST;
        } else {
            $this->classname = strtolower(get_class($this));
            $this->gds = $_SESSION["i4b"][$this->classname];
            if ($this->gds === null) {
                $this->gds = $_SESSION["i4b"][get_class($this)];
            }
        }

        $this->debugclass = $debug;
        $this->Auth = new Auth();
    }


    private function order_info($id)
    {
        $RequestName = "OrderInfoRequest";
        $companyId = $this->gds["companyId"];
        $userId = $this->gds["userId"];
        $userPass = $this->gds["userPass"];
        $language = "ru";

        $xml = "<?xml version='1.0' encoding='utf-8'?><OrderInfoRequest BuyerId='" . $companyId . "' UserId='" . $userId . "' Password='" . $userPass . "' Language='ru' Id='" . $id . "' />"; // ArrivalDateTo='15.01.2018'  RegistrationDateFrom='09.01.2018' RegistrationDateTo='15.01.2018'/>";

        $request = [
            "RequestName" => $RequestName,
            "companyId" => $companyId,
            "userId" => $userId,
            "userPass" => $userPass,
            "language" => $language,
            "xml" => $xml
        ];

        //Id= 4930586
        $url = $this->gds["serveraddr"];
        $res = $this->http_c_post($url, $request);
        $content = $res["content"];

        $result = $content;

        return $result;
    }

    private function jv3($jsonv)
    {
        $services = [];


        $sAccommodationList = $this->DTV($jsonv, ["AccommodationList", "Accommodation"]);
        if (isset($sAccommodationList["@attributes"])) {
            $sAccommodationList = [];
            $sAccommodationList[] = $this->DTV($jsonv, ["AccommodationList", "Accommodation"]);
        }

        foreach ($sAccommodationList as $Accommodation) {
            $service = $this->get_empty_v3();

            $service["Synh"] = "acd_" . $this->DTV($Accommodation, ["@attributes", "Id"]); //$service["partner_order_id"];
            $service["manager"] = $this->DTV($jsonv, ["ContactPerson", "@attributes", "Email"]);

            $service["nomenclature"] = "Проживание";
            if ($this->DTV($jsonv, ["Status", "@attributes", "Code"]) == "65") {
                $service["TypeOfTicket"] = "S";
            } else {
                $service["TypeOfTicket"] = "V";
                $service["nomenclature"] = "ОтменаПроживания";
            }

            $service["TicketNumber"] = $this->DTV($Accommodation, ["@attributes", "VoucherId"]);


            //Поставщик
            $service["price"] = (float)$this->DTV($Accommodation, ["@attributes", "Price"]);
            $service["amount"] = (float)$service["price"];
            $service["amountVAT"] = (float)$this->DTV($Accommodation, ["@attributes", "VATIncludedInPrice"]);
            if ($service["amountVAT"] == 0) {
                $service["VATrate"] = -1;
            } else {
                $service["VATrate"] = 120;//round($service["amountVAT"] / $service["amount"] * 100);
            }
            $service["CommissionAmount"] = (float)$this->DTV($Accommodation, ["@attributes", "TravelAgencyCommission"]);

            //Клиент
            $service["amountclient"] = (float)$this->DTV($Accommodation, ["@attributes", "Price"]);
            $service["pricecustomer"] = $service["amountclient"];
            $service["amountVATcustomer"] = $service["amountVAT"];//(float)$this->DTV($order_data, ["amount_payable_vat"]);
            $service["VATratecustomer"] = $service["VATrate"];
            $service["AmountServices"] = $service["amountclient"];

            $service["supplier"] = ["INN" => "5024053441", "KPP" => "502401001", "Name" => "АКАДЕМСЕРВИС"];
            $service["Supplier"] = $service["supplier"];


            $dd = $this->DTV($Accommodation, ["@attributes", "ArrivalDate"]); //"checkout_at": "2018-09-05",
            $tt = $this->DTV($Accommodation, ["@attributes", "ArrivalTime"]); //"checkout_time": "12:00",
            try {
                $format = "d.m.Y H:i";
                $date = DateTime::createFromFormat($format, $dd . " " . $tt);// + ((int)$service["Night"] * 24 * 60 * 60);
                $service["ServiceStartDate"] = $date->format("YmdHis"); //!!!!!
            } catch (Exception $e) {
                $service["ServiceStartDate"] = "";
            }

            $dd = $this->DTV($Accommodation, ["@attributes", "DepartureDate"]); //"checkout_at": "2018-09-05",
            $tt = $this->DTV($Accommodation, ["@attributes", "DepartureTime"]); //"checkout_time": "12:00",
            try {
                $format = "d.m.Y H:i";
                $date = DateTime::createFromFormat($format, $dd . " " . $tt);// + ((int)$service["Night"] * 24 * 60 * 60);
                $service["ServiceEndDate"] = $date->format("YmdHis"); //!!!!!
            } catch (Exception $e) {
                $service["ServiceEndDate"] = "";
            }

            $diff = strtotime($service["ServiceEndDate"]) - strtotime($service["ServiceStartDate"]);
            $service["Night"] = round(abs($diff) / 60 / 60 / 24);


            $service["date"] = $service["ServiceEndDate"];


            $service["CityDeparture"] = $this->DTV($Accommodation, ["City", "@attributes", "Name"]);
            $service["PlaceDeparture"] = $this->DTV($Accommodation, ["City", "@attributes", "Name"]);

            $service["HotelName"] = $this->DTV($Accommodation, ["Hotel", "@attributes", "Name"]);


            $Room = $this->DTV($Accommodation, ["Product", "@attributes", "RoomName"]);
            $service["NumberTypeName"] = $Room;

            $service["TypeOfFood"] = $this->DTV($Accommodation, ["Meal", "@attributes", "Name"]);;


            $sSecondeds = $this->DTV($Accommodation, ["Persons", "Person"]);
            $Secondeds = $sSecondeds;
            if (isset($Secondeds["@attributes"])) {
                $Secondeds = [];
                $Secondeds[] = $sSecondeds;
            }
            foreach ($Secondeds as $valueS) {
                $Seconded = [];

                $Seconded["FirstName"] = $this->DTV($valueS, ["@attributes", "FirstName"]);
                $Seconded["LastName"] = $this->DTV($valueS, ["@attributes", "LastName"]);
                $Seconded["SurName"] = "";
                $Seconded["FirstNameLatin"] = "";
                $Seconded["LastNameLatin"] = "";
                $Seconded["SurNameLatin"] = "";

                $Seconded["DocumentNumber"] = $this->DTV($valueS, ["@attributes", "Passport"]);
                $Seconded["BirthDay"] = $this->DTV($valueS, ["@attributes", "BirthDate"]);
                $Seconded["DocType"] = "";

                $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
                $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);

                $service["Seconded"][] = $Seconded;
            }

            if ($service["nomenclature"] == "ОтменаПроживания") {
                $service["price"] = -1*$service["price"];
                $service["amount"] = -1*$service["amount"];
                $service["amountVAT"] = -1*$service["amountVAT"];

                $service["pricecustomer"] = -1*$service["pricecustomer"];
                $service["amountVATcustomer"] = -1*$service["amountVATcustomer"];
                $service["amountclient"] = -1*$service["amountclient"];

                $service["CommissionAmount"] = -1*$service["CommissionAmount"];
                $service["CommissionVATAmount"] = -1*$service["CommissionVATAmount"];
                $service["AmountServices"] = -1*$service["AmountServices"];
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
                $result["result"] = true;

                $text = $this->phpinput;
                $Parser = new Parser($this->metod);
                $Parser->SetUseParser("Academservice", md5($text), $this->Auth->getuserid());

                $textxml = $this->phpinput;

                $xml = simplexml_load_string($textxml);
                $jsonv = $this->object2array($xml);
                $id = $this->DTV($jsonv, ["@attributes", "Id"]);

                $textxml = $this->order_info($id);
                $xml = simplexml_load_string($textxml); //Реальный заказ
                $jsonv = $this->object2array($xml);

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
