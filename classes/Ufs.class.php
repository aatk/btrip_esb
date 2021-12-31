<?php

class Ufs extends ex_classlite
{

    private $metod;
    private $convert;
    private $gds;
    private $connectionInfo;
    private $classname;

    private $Auth;


    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        parent::__construct();
        $this->metod = $metod;
        $this->gds = $_SESSION["i4b"][$this->classname];

        $this->convert = new Conversion("INNER");
        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем

        $this->debugclass = $debug;
        $this->Auth = new Auth();
    }


    public function getinfo($params)
    {

        $result = ["result" => false];

        if ($this->Auth->userauth()) {
            if ($params[1] == "v3") {

                $text = $this->phpinput;
                $Parser = new Parser($this->metod);
                $Parser->SetUseParser("Ufs", md5($text), $this->Auth->getuserid());

                $textxml = $this->phpinput;

                $xml = simplexml_load_string("<xml>" . $textxml . "</xml>");
                $json = $this->object2array($xml);

                $v2 =[];
                if (isset($json["UFS_AE_Gate"])) {
                    $v3 = $this->v3_AE($json["UFS_AE_Gate"]);
                } else {
                    $v2 = $this->v2($textxml);
                    $v3 = $this->v3($v2);
                }


                $result = ["result" => true, "json" => $json, "jsonv2" => $v2, "jsonv3" => $v3];
            } else {
                $result["error"] = "Not for this version";
            }
        } else {
            $result["error"] = "Authorization fail";
        }

        return $result;
    }

    private function v3_AE($json)
    {

        $services = [];

        if ($this->debugclass) {
            print_r($json);
        }


        if (isset($json["Ticket"]["Id"])) {
            $blank = [];
            $blank[] = $json["Ticket"];
        } else {
            $blank = $json["Ticket"];
        }

        $DepartDate = $this->DTV($json, ["DepartDate"], "", "d.m.Y H:i:s"); //25.05.2019 00:00:00
        $CurTime = $this->DTV($json, ["CurTime"], "", "d.m.Y H:i:s"); //25.05.2019 00:00:00
        $Airport = $this->DTV($json, ["Airport"]);
        $Direction = $this->DTV($json, ["Direction"]);
        $Type = $this->DTV($json, ["Type"]);

        foreach ($blank as $Ticket) {
            $service = $this->get_empty_v3();


            if ($this->debugclass) {
                print_r($CurTime);
            }

            $service["nomenclature"] = "Аэроэкспресс";
            $service["date"] = $CurTime;
            $service["Depart"] = $DepartDate;

            $service["supplier"] = ["INN" => "7708510731", "KPP" => "770401001", "Name" => "УФС"];
            $service["Supplier"] = $service["supplier"];

            $Id = $this->DTV($Ticket, ["Id"]);
            $Price = $this->DTV($Ticket, ["Price"]);
            $PassengerFio = $this->DTV($Ticket, ["PassengerFio"]);


            $service["TicketNumber"] = $Id;
            $service["TypeOfTicket"] = "S";
            $pre = 1;
            if ($Type == "14") {
                $service["TypeOfTicket"] = "R";
                $pre = -1;
            }

            $service["Synh"] = $service["TypeOfTicket"] . $service["TicketNumber"];


            $service["ServiceStartDate"] = $DepartDate;


            $service["price"] = $pre * (float)$Price;
            $service["amount"] = $pre * (float)$Price;
            $service["pricecustomer"] = $pre * (float)$Price;
            $service["amountclient"] = $pre * (float)$Price;

            $Seconded = [];

            $Name = $this->mb_ucwords(mb_strtolower($PassengerFio));
            $ars = explode(" ", $Name);

            $Seconded["FirstName"] = $this->DTV($ars, [1]);
            $Seconded["LastName"] = $this->DTV($ars, [0]);
            $Seconded["SurName"] = $this->DTV($ars, [2]);
            $Seconded["FirstNameLatin"] = "";
            $Seconded["LastNameLatin"] = "";
            $Seconded["SurNameLatin"] = "";

            $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
            $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);

            $service["Seconded"][] = $Seconded;

            $services[] = $service;
        }


        $jsonv3["services"] = $services;

        return $jsonv3;
    }

    private function v2($content)
    {

        $xml = simplexml_load_string($content);

        $json = $this->object2array($xml->StationFrom->attributes());
        $StationFromCode = $json["@attributes"]["Code"];

        $json = $this->object2array($xml->StationTo->attributes());
        $StationToCode = $json["@attributes"]["Code"];


        $json = $this->object2array($xml);

        $service = [];
        $service["TrainNum"] = $json["TrainNum"];
        $service["Type"] = $json["Type"];
        try {
            $service["CreateTime"] = $json["CreateTime"];
            $format = "d.m.Y H:i:s";
            $date = DateTime::createFromFormat($format, $service["CreateTime"]);
            $service["CreateTime"] = $date->format("YmdHis");
        } catch (Exception $e) {
            $service["CreateTime"] = "";
        }


        try {
            $service["BookingTime"] = $json["BookingTime"];
            $format = "d.m.Y H:i:s";
            $date = DateTime::createFromFormat($format, $service["BookingTime"]);
            $service["BookingTime"] = $date->format("YmdHis"); //!!!!!
        } catch (Exception $e) {
            $service["BookingTime"] = "";
        }

        try {
            $service["ConfirmTime"] = $json["ConfirmTime"];
            $format = "d.m.Y H:i:s";
            $date = DateTime::createFromFormat($format, $service["ConfirmTime"]);
            $service["ConfirmTime"] = $date->format("YmdHis"); //!!!!!
        } catch (Exception $e) {
            $service["ConfirmTime"] = "";
        }

        try {
            $service["ConfirmTimeLimit"] = $json["ConfirmTimeLimit"];
            $format = "d.m.Y H:i:s";
            $date = DateTime::createFromFormat($format, $service["ConfirmTimeLimit"]);
            $service["ConfirmTimeLimit"] = $date->format("YmdHis"); //!!!!!
        } catch (Exception $e) {
            $service["ConfirmTimeLimit"] = "";
        }

        $service["CarNum"] = $json["CarNum"];
        $service["CarType"] = $json["CarType"];
        try {
            $service["DepartTime"] = $json["DepartTime"];
            $format = "d.m.Y H:i:s";
            $date = DateTime::createFromFormat($format, $service["DepartTime"]);
            $service["DepartTime"] = $date->format("YmdHis"); //!!!!!
        } catch (Exception $e) {
            $service["DepartTime"] = "";
        }

        $service["Email"] = $json["Email"];
        $service["ServiceClass"] = $json["ServiceClass"];
        $service["StationFrom"] = $json["StationFrom"];
        $service["StationTo"] = $json["StationTo"];
        $service["StationFromCode"] = $StationFromCode;
        $service["StationToCode"] = $StationToCode;
        try {
            $service["ArrivalTime"] = $json["ArrivalTime"];
            $format = "d.m.Y H:i:s";
            $date = DateTime::createFromFormat($format, $service["ArrivalTime"]);
            $service["ArrivalTime"] = $date->format("YmdHis"); //!!!!!
        } catch (Exception $e) {
            $service["ArrivalTime"] = "";
        }

        $service["GenderClass"] = $json["GenderClass"];
        $service["Carrier"] = $json["Carrier"];
        $service["CarrierInn"] = $json["CarrierInn"];
        $service["TimeDescription"] = $json["TimeDescription"];
        $service["GroupDirection"] = $json["GroupDirection"];
        if (isset($json["Terminal"])) {
            $service["Terminal"] = $json["Terminal"];
        }

        $service["ExpierSetEr"] = "";
        if (isset($json["ExpierSetEr"]) && is_string($json["ExpierSetEr"])) {
            $service["ExpierSetEr"] = $json["ExpierSetEr"];
            $format = "d.m.Y H:i:s";
            $date = DateTime::createFromFormat($format, $service["ExpierSetEr"]);
            $service["ExpierSetEr"] = $date->format("YmdHis"); //!!!!!
        }


        $service["TravelTime"] = $json["TripDuration"];

        $service["Domain"] = $json["Domain"];
        $service["PayTypeID"] = $json["PayTypeID"];
        $service["IsInternational"] = $json["IsInternational"];

        if (isset($json["Blank"]["@attributes"])) {
            $blank = [];
            $blank[] = $json["Blank"];
        } else {
            $blank = $json["Blank"];
        }

        if (isset($json["Passenger"]["@attributes"])) {
            $Passenger = [];
            $Passenger[] = $json["Passenger"];
        } else {
            $Passenger = $json["Passenger"];
        }

        $services = [];

        foreach ($blank as $value) {
            $serviceblank = $service;


            $serviceblank["RegTime"] = $value["RegTime"];
            if (isset($value["RegTime"]) && is_string($value["RegTime"])) {
                $serviceblank["RegTime"] = $value["RegTime"];
                $format = "d.m.Y H:i:s";
                $date = DateTime::createFromFormat($format, $serviceblank["RegTime"]);
                $serviceblank["RegTime"] = $date->format("YmdHis"); //!!!!!
            }

            $serviceblank["ID"] = $value["@attributes"]["ID"];
            $serviceblank["RetFlag"] = $value["RetFlag"];
            $serviceblank["Amount"] = $value["Amount"];
            if ($value["ServiceRateNds"] > 0) {
                $serviceblank["AmountWhithNDS"] = round($value["AmountNDS"] / $value["ServiceRateNds"] * (100 + $value["ServiceRateNds"]), 0);
                $serviceblank["ServiceWhithNDS"] = round($value["ServiceNDS"] / $value["ServiceRateNds"] * (100 + $value["ServiceRateNds"]), 0);
            } else {
                $serviceblank["AmountWhithNDS"] = 0;
                $serviceblank["ServiceWhithNDS"] = 0;
            }
            $serviceblank["AmountNDS"] = $value["AmountNDS"];
            $serviceblank["ServiceNDS"] = $value["ServiceNDS"];
            $serviceblank["ReservedSeatAmount"] = $value["ReservedSeatAmount"];
            $serviceblank["TariffRateNds"] = $value["TariffRateNds"];
            $serviceblank["ServiceRateNds"] = $value["ServiceRateNds"];
            $serviceblank["ServiceRateNds"] = $value["ServiceRateNds"];
            $serviceblank["CommissionFeeRateNds"] = $value["CommissionFeeRateNds"];
            $serviceblank["Profit"] = $json["UfsProfit"] / count($blank);

            $serviceblank["ReclamationCollectRateNds"] = $value["ReclamationCollectRateNds"];
            $serviceblank["TariffReturnNds"] = $value["TariffReturnNds"];
            $serviceblank["ServiceReturnNds"] = $value["ServiceReturnNds"];
            $serviceblank["CommissionFeeReturnNds"] = $value["CommissionFeeReturnNds"];
            $serviceblank["ReclamationCollectReturnNds"] = $value["ReclamationCollectReturnNds"];
            $serviceblank["TicketReturnAmount"] = $value["TicketReturnAmount"];
            $serviceblank["ReservedSeatReturnAmount"] = $value["ReservedSeatReturnAmount"];
            $serviceblank["ServiceReturnAmount"] = $value["ServiceReturnAmount"];
            $serviceblank["ReclamationCollectReturnAmount"] = $value["ReclamationCollectReturnAmount"];
            $serviceblank["TariffType"] = $value["TariffType"];
            $serviceblank["PassengerCard"] = $value["PassengerCard"];
            $serviceblank["TicketNum"] = $value["TicketNum"];
            $serviceblank["RemoteCheckIn"] = $value["RemoteCheckIn"];
            $serviceblank["PrintFlag"] = $value["PrintFlag"];
            $serviceblank["RzhdStatus"] = $value["RzhdStatus"];
            $serviceblank["TicketToken"] = $value["TicketToken"];

            foreach ($serviceblank as $key => $valuesb) {
                if (is_array($value) && (count($valuesb) == 0)) {
                    $serviceblank[$key] = "";
                }
            }

            //Passenger
            $TravelPeaples = [];
            foreach ($Passenger as $valuep) {
                if ($valuep["@attributes"]["BlankID"] == $serviceblank["ID"]) {
                    //
                    $TravelPeaple = [];

                    $TravelPeaple["Type"] = $valuep["Type"];
                    $TravelPeaple["DocType"] = $valuep["DocType"];
                    $TravelPeaple["DocumentNumber"] = $valuep["DocNum"];
                    $TravelPeaple["Name"] = $valuep["Name"];
                    if ($valuep["R"] == "МУЖ") {
                        $TravelPeaple["Sex"] = "M";
                    } else {
                        $TravelPeaple["Sex"] = "F";
                    }
                    if (isset($valuep["BirthDay"])) {
                        $TravelPeaple["BirthDay"] = $valuep["BirthDay"];
                    } else {
                        $TravelPeaple["BirthDay"] = "";
                    }

                    $serviceblank["Place"] = $valuep["Place"];
                    $serviceblank["Placetier"] = $valuep["PlaceTier"];

                    $TravelPeaples[] = $TravelPeaple;
                }
            }

            $serviceblank["TravelPeoples"] = $TravelPeaples;

            $services[] = $serviceblank;
        }

        $res["services"] = $services;

        //print_r($res);
        return $res;
    }

    private function v3addonservice($jsonv3sevice)
    {
        $services = [];

        if ($jsonv3sevice["AmountWithVAT18"] > 0) {
            //добавим услугу на 18%
            $service = $jsonv3sevice;

            $service["nomenclature"] = "СервисныеУслугиПеревозчика";
            $service["ownerservice"] = $jsonv3sevice["Synh"];
            $service["ApplicationService"] = $jsonv3sevice["Synh"];
            $service["Synh"] = "";
            $service["TicketNumber"] = "";
            $service["TypeOfTicket"] = "";

            $service["pricecustomer"] = $jsonv3sevice["AmountWithVAT18"];
            $service["amountclient"] = $jsonv3sevice["AmountWithVAT18"];
            $service["amountVATcustomer"] = $jsonv3sevice["VATAmount18"];
            $service["VATratecustomer"] = 18;

            $service["price"] = $jsonv3sevice["AmountWithVAT18"];
            $service["amount"] = $jsonv3sevice["AmountWithVAT18"];
            $service["amountVAT"] = $jsonv3sevice["VATAmount18"];
            $service["VATrate"] = 18;

            $service["AmountExcludingVAT"] = 0;
            $service["VATAmount10"] = 0;
            $service["VATAmount18"] = 0;
            $service["AmountWithVAT10"] = 0;
            $service["AmountWithVAT18"] = 0;
            $service["AmountServices"] = 0;
            $service["AmountOfPenalty"] = 0;
            $service["VendorFeeAmount"] = 0;

            $services[] = $service;
        }

        if ($jsonv3sevice["AmountWithVAT10"] > 0) {
            //добавим услугу на 10%
            $service = $jsonv3sevice;

            $service["nomenclature"] = "СервисныеУслугиПеревозчика";
            $service["ownerservice"] = $jsonv3sevice["Synh"];
            $service["ApplicationService"] = $jsonv3sevice["Synh"];
            $service["Synh"] = "";
            $service["TicketNumber"] = "";
            $service["TypeOfTicket"] = "";

            $service["pricecustomer"] = $jsonv3sevice["AmountWithVAT10"];
            $service["amountclient"] = $jsonv3sevice["AmountWithVAT10"];
            $service["amountVATcustomer"] = $jsonv3sevice["VATAmount10"];
            $service["VATratecustomer"] = 10;

            $service["price"] = $jsonv3sevice["AmountWithVAT10"];
            $service["amount"] = $jsonv3sevice["AmountWithVAT10"];
            $service["amountVAT"] = $jsonv3sevice["VATAmount10"];
            $service["VATrate"] = 10;

            $service["AmountExcludingVAT"] = 0;
            $service["VATAmount10"] = 0;
            $service["VATAmount18"] = 0;
            $service["AmountWithVAT10"] = 0;
            $service["AmountWithVAT18"] = 0;
            $service["AmountServices"] = 0;
            $service["AmountOfPenalty"] = 0;
            $service["VendorFeeAmount"] = 0;

            $services[] = $service;
        }

        if ($jsonv3sevice["VendorFeeAmount"] > 0) {
            //добавим услугу сбор поставщика
            $service = $jsonv3sevice;

            $service["nomenclature"] = "СборПоставщика";
            $service["ownerservice"] = $jsonv3sevice["Synh"];
            $service["ApplicationService"] = $jsonv3sevice["Synh"];
            $service["Synh"] = "";
            $service["TicketNumber"] = "";
            $service["TypeOfTicket"] = "";

            $service["pricecustomer"] = $jsonv3sevice["VendorFeeAmount"];
            $service["amountclient"] = $jsonv3sevice["VendorFeeAmount"];
            $service["VATratecustomer"] = 18;
            $service["amountVATcustomer"] = round($service["pricecustomer"] / (100 + $service["VATratecustomer"]) * $service["VATratecustomer"], 2);

            $service["price"] = $jsonv3sevice["VendorFeeAmount"];
            $service["amount"] = $jsonv3sevice["VendorFeeAmount"];
            $service["VATrate"] = 18;
            $service["amountVAT"] = round($service["price"] / (100 + $service["VATrate"]) * $service["VATrate"], 2);

            $service["AmountExcludingVAT"] = 0;
            $service["VATAmount10"] = 0;
            $service["VATAmount18"] = 0;
            $service["AmountWithVAT10"] = 0;
            $service["AmountWithVAT18"] = 0;
            $service["AmountServices"] = 0;
            $service["AmountOfPenalty"] = 0;
            $service["VendorFeeAmount"] = 0;

            $services[] = $service;

        }

        return $services;
    }

    private function v3($jsonv2)
    {
        $services = [];

        foreach ($jsonv2["services"] as $inservice) {
            $service = $this->get_empty_v3();
            //
            foreach ($service as $key => $value) {
                if (isset($inservice[$key])) {
                    $service[$key] = $inservice[$key];
                }
            }


            if ($inservice["Type"] == "1") {
                $service["TypeOfTicket"] = "S";
            } else {
                $service["TypeOfTicket"] = "R";
            }

            $service["Synh"] = $service["TypeOfTicket"] . $inservice["TicketNum"];

            $service["TicketNumber"] = $inservice["TicketNum"];

            $service["date"] = $inservice["CreateTime"];
            $service["manager"] = $inservice["Terminal"];

            $service["nomenclature"] = "ЖДБилет";

            $service["ServiceStartDate"] = $inservice["DepartTime"];
            $service["ServiceEndDate"] = $inservice["ArrivalTime"];

            $service["Depart"] = $service["ServiceStartDate"];
            $service["Arrival"] = $service["ServiceEndDate"];


//            $DepartureGeoData = $this->GetStationFromText(["", "railway", "станция " . $inservice["StationFrom"]]);
//            $ArrivalGeoData = $this->GetStationFromText(["", "railway", "станция " . $inservice["StationTo"]]);

//            $service["PlaceDeparture"] = [
//                "Place" => $DepartureGeoData["station"],
//                "City" => $DepartureGeoData["city"],
//                "Country" => $DepartureGeoData["country"]
//            ];
//            $service["PlaceArrival"] = [
//                "Place" => $ArrivalGeoData["station"],
//                "City" => $ArrivalGeoData["city"],
//                "Country" => $ArrivalGeoData["country"]
//            ];

//            $service["CityDeparture"] = $DepartureGeoData["city"];
//            $service["CityArrival"] = $ArrivalGeoData["city"];


//            $service["Latitude"] = $ArrivalGeoData["latitude"];
//            $service["Longitude"] = $ArrivalGeoData["longitude"];
//            $service["LatitudeDeparture"] = $DepartureGeoData["latitude"];
//            $service["LongitudeDeparture"] = $DepartureGeoData["longitude"];


            $service["DepartureCode"] = $inservice["StationFromCode"];
            $service["ArrivalCode"] = $inservice["StationToCode"];

            $service["Route"] = $service["CityDeparture"] . " - " . $service["CityArrival"];

            $service["supplier"] = ["INN" => "7708510731", "KPP" => "770401001", "Name" => "УФС"];
            $service["Supplier"] = $service["supplier"];


            $service["TrainNumber"] = $inservice["TrainNum"];

            $service["price"] = (float)$inservice["Amount"];
            $service["amount"] = (float)$service["price"];


            /*
            "AmountExcludingVAT": 0,
            "VATAmount10": 0,
            "VATAmount18": 0,
            "AmountWithVAT10": 0,
            "AmountWithVAT18": 0,
            "AmountServices": 0,
            "AmountOfPenalty": 0,
            VendorFeeAmount: 0
             */

            $service["AmountWithVAT18"] = (float)$inservice["AmountWhithNDS"];
            $service["VATAmount18"] = (float)$inservice["AmountNDS"];

            $service["AmountServices"] = (float)$service["amount"] - (float)$service["AmountWithVAT18"];

            $service["pricecustomer"] = $service["AmountServices"];
            //$service["VATratecustomer"] = (int)$inservice["ServiceRateNds"];
            //$service["amountVATcustomer"] = 0;
            $service["amountclient"] = $service["AmountServices"];

            $service["TravelTime"] = (int)$inservice["TravelTime"];
            $service["VendorFeeAmount"] = (int)$inservice["Profit"];


            $service["Place"] = trim($inservice["Place"]);
            $service["Wagon"] = trim($inservice["CarNum"]);

            $Secondeds = $this->DTV($inservice, "TravelPeoples");
            foreach ($Secondeds as $valueS) {
                $Seconded = [];

                $Name = $this->mb_ucwords(mb_strtolower($this->DTV($valueS, ["Name"])));
                $ars = explode(" ", $Name);

                $Seconded["FirstName"] = $this->DTV($ars, [1]);
                $Seconded["LastName"] = $this->DTV($ars, [0]);
                $Seconded["SurName"] = $this->DTV($ars, [2]);
                $Seconded["FirstNameLatin"] = "";
                $Seconded["LastNameLatin"] = "";
                $Seconded["SurNameLatin"] = "";


                $Seconded["DocumentNumber"] = $valueS["DocumentNumber"];

                try {
                    $Seconded["BirthDay"] = $valueS["BirthDay"];
                    $format = "d.m.Y";
                    $date = DateTime::createFromFormat($format, $Seconded["BirthDay"]);
                    $Seconded["BirthDay"] = $date->format("Ymd000000");
                } catch (Exception $e) {
                    $Seconded["BirthDay"] = "";
                }
                $Seconded["DocType"] = $valueS["DocType"];

                $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
                $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);

                $service["Seconded"][] = $Seconded;
            }

            $services[] = $service;

            $addservices = [];
            $addservices = $this->v3addonservice($service);
            $services = array_merge($services, $addservices);
        }

        $jsonv3["services"] = $services;

        return $jsonv3;
    }


}
