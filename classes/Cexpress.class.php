<?php

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 21/02/2019
 * Time: 15:09
 *
 * Вход в тестовую среду ЦЭ
 *
 * https://cabinet-test.centrexpress.ru/PartnerPoint
 * kassaTEST2019
 * 1478914
 *
 */
class Cexpress extends ex_classlite
{
    private $metod;
    private $Auth;
    private $Supplier;


    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        parent::__construct();
        $this->metod = $metod;

        $this->debugclass = $debug;
        $this->Auth = new Auth();

        $this->Supplier = ["INN" => "7717648334", "KPP" => "771401001", "Name" => "ЦентрЭкспресс"];
    }


    public function getinfo($params)
    {
        $result = ["result" => false];

        if ($this->debugclass) {
            echo "DEBUG!!!" . "\r\n";
            print_r($params);
        }


        if ($this->Auth->userauth()) {
        //if (true) {
            if ($params[1] == "v3") {

                $text = $this->phpinput;
                $Parser = new Parser($this->metod);
                $Parser->SetUseParser("Cexpress", md5($text), $this->Auth->getuserid());

                $textxml = $this->phpinput;
                $xml = simplexml_load_string($textxml);
                $json = $this->object2array($xml);

                $v2 = $this->v2($textxml);
                $v3 = $this->v3($v2);

                $result = ["result" => true, "json"=> $json, "jsonv2" => $v2, "jsonv3" => $v3];
            } else {
                $result["error"] = "Not for this version";
            }
        } else {
            $result["error"] = "Authorization fail";
        }

        return $result;
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

        $service["CreateTime"] = $this->DTV($json, ["CreateTime"], "", "d.m.Y H:i:s");


        $service["BookingTime"] = $this->DTV($json, ["BookingTime"], "", "d.m.Y H:i:s");

        $service["ConfirmTime"] = $this->DTV($json, ["ConfirmTime"], "", "d.m.Y H:i:s");
        $service["ConfirmTimeLimit"] = $this->DTV($json, ["ConfirmTimeLimit"], "", "d.m.Y H:i:s");

        $service["CarNum"] = $json["CarNum"];
        $service["CarType"] = $json["CarType"];


        $service["ArrivalTime"] = $this->DTV($json, ["ArrivalTime"], "", "d.m.Y H:i:s");
        $service["DepartTime"] = $this->DTV($json, ["DepartTime"], "", "d.m.Y H:i:s");


        $service["Email"] = $json["Email"];
        $service["ServiceClass"] = $json["ServiceClass"];
        $service["StationFrom"] = $json["StationFrom"];
        $service["StationTo"] = $json["StationTo"];
        $service["StationFromCode"] = $StationFromCode;
        $service["StationToCode"] = $StationToCode;

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
            $service["ExpierSetEr"] = $this->DTV($json, ["ExpierSetEr"], "", "d.m.Y H:i:s");
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

                $serviceblank["RegTime"] = $this->DTV($json, ["RegTime"], "", "d.m.Y H:i:s");
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
            $serviceblank["Profit"] = $this->DTV($json, ["Fee"]) / count($blank);

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

        if ($jsonv3sevice["AmountWithVAT18"] != 0) {
            //добавим услугу на 20%
            $service = $jsonv3sevice;

            $service["nomenclature"] = "СервисныеУслугиПеревозчика";
            $service["ownerservice"] = $jsonv3sevice["Synh"];
            $service["ApplicationService"] = $jsonv3sevice["Synh"];
            $service["Synh"] = $service["nomenclature"] . $jsonv3sevice["Synh"];
            $service["TicketNumber"] = "";
            $service["TypeOfTicket"] = "";
            $service["TypeThisService"] = "Загруженная";

            $service["pricecustomer"] = $jsonv3sevice["AmountWithVAT18"];
            $service["amountclient"] = $jsonv3sevice["AmountWithVAT18"];
            $service["amountVATcustomer"] = $jsonv3sevice["VATAmount18"];
            $service["VATratecustomer"] = 20;

            $service["price"] = $jsonv3sevice["AmountWithVAT18"];
            $service["amount"] = $jsonv3sevice["AmountWithVAT18"];
            $service["amountVAT"] = $jsonv3sevice["VATAmount18"];
            $service["VATrate"] = 20;

            $service["AmountExcludingVAT"] = 0;
            $service["VATAmount10"] = 0;
            $service["VATAmount18"] = 0;
            $service["AmountWithVAT10"] = 0;
            $service["AmountWithVAT18"] = 0;
            $service["AmountServices"] = 0;
            $service["AmountOfPenalty"] = 0;
            $service["VendorFeeAmount"] = 0;

            $service["attachedto"] = $jsonv3sevice["Synh"];

            $services[] = $service;
        }

        if ($jsonv3sevice["AmountWithVAT10"] != 0) {
            //добавим услугу на 10%
            $service = $jsonv3sevice;

            $service["nomenclature"] = "СервисныеУслугиПеревозчика";
            $service["ownerservice"] = $jsonv3sevice["Synh"];
            $service["ApplicationService"] = $jsonv3sevice["Synh"];
            $service["Synh"] = $service["nomenclature"] . $jsonv3sevice["Synh"];
            $service["TicketNumber"] = "";
            $service["TypeOfTicket"] = "";
            $service["TypeThisService"] = "Загруженная";

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

            $service["attachedto"] = $jsonv3sevice["Synh"];

            $services[] = $service;
        }

        if ($jsonv3sevice["VendorFeeAmount"] != 0) {
            //добавим услугу сбор поставщика
            $service = $jsonv3sevice;

            $service["nomenclature"] = "СборПоставщика";
            $service["ownerservice"] = $jsonv3sevice["Synh"];
            $service["ApplicationService"] = $jsonv3sevice["Synh"];
            $service["Synh"] = $service["nomenclature"] . $jsonv3sevice["Synh"];
            $service["TicketNumber"] = "";
            $service["TypeOfTicket"] = "";
            $service["TypeThisService"] = "Загруженная";

            $service["pricecustomer"] = $jsonv3sevice["VendorFeeAmount"];
            $service["amountclient"] = $jsonv3sevice["VendorFeeAmount"];
            $service["VATratecustomer"] = 20;
            $service["amountVATcustomer"] = round($service["pricecustomer"] / (100 + $service["VATratecustomer"]) * $service["VATratecustomer"], 2);

            $service["price"] = $jsonv3sevice["VendorFeeAmount"];
            $service["amount"] = $jsonv3sevice["VendorFeeAmount"];
            $service["VATrate"] = 20;
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

            $service["nomenclature"] = "ЖДБилет";
            $service["TicketNumber"] = $inservice["TicketNum"];

            if ($inservice["Type"] == "1") {
                $service["TypeOfTicket"] = "S";
            } else {
                $service["nomenclature"] = "ВозвратЖДБилета";
                $service["TicketSales"] = $service["TicketNumber"];
                $service["TypeOfTicket"] = "R";
            }

            $service["Synh"] = $service["TypeOfTicket"] . $inservice["TicketNum"];


            $service["date"] = $inservice["CreateTime"];
            $service["manager"] = $inservice["Terminal"];


            $service["ServiceStartDate"] = $inservice["DepartTime"];
            $service["ServiceEndDate"] = $inservice["ArrivalTime"];

            $service["Depart"] = $service["ServiceStartDate"];
            $service["Arrival"] = $service["ServiceEndDate"];

            $service["AddressDestination"] = $inservice["StationTo"];
            $service["AddressDeparture"] = $inservice["StationFrom"];

//            $DepartureGeoData = $this->GetStationFromText(["", "railway", "станция " . $inservice["StationFrom"]]);
//            $ArrivalGeoData = $this->GetStationFromText(["", "railway", "станция " . $inservice["StationTo"]]);
//
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
//
//            $service["CityDeparture"] = $DepartureGeoData["city"];
//            $service["CityArrival"] = $ArrivalGeoData["city"];
//
//
//            $service["Latitude"] = $ArrivalGeoData["latitude"];
//            $service["Longitude"] = $ArrivalGeoData["longitude"];
//            $service["LatitudeDeparture"] = $DepartureGeoData["latitude"];
//            $service["LongitudeDeparture"] = $DepartureGeoData["longitude"];


            $service["DepartureCode"] = $inservice["StationFromCode"];
            $service["ArrivalCode"] = $inservice["StationToCode"];

            $service["Route"] = (($service["CityDeparture"] == "") ? $service["PlaceDeparture"]["Place"] : $service["CityDeparture"]) . " - " . (($service["CityArrival"] == "") ? $service["PlaceArrival"]["Place"] : $service["CityArrival"]);

            $service["supplier"] = $this->Supplier;//["INN" => "7717648334", "KPP" => "771401001", "Name" => "ЦентрЭкспресс"];
            $service["Supplier"] = $service["supplier"];


            $service["TrainNumber"] = $inservice["TrainNum"];

            $service["price"] = (float)$inservice["Amount"];
            $service["amount"] = (float)$service["price"];

            //$service["AmountWithVAT18"] = (float)$inservice["AmountWhithNDS"];
            //$service["VATAmount18"] = (float)$inservice["AmountNDS"];
            //ВОДОХОД Услуга 94 от 20.08.2019 НДС в 2 раза больше чем в билете, поэтому ServiceNDS - закомментили
            $service["AmountWithVAT18"] = (float)$inservice["AmountWhithNDS"]; //(float)$inservice["ServiceWhithNDS"] +
            $service["VATAmount18"] = (float)$inservice["AmountNDS"]; //(float)$inservice["ServiceNDS"] +


            $service["AmountServices"] = (float)$service["amount"] - (float)$service["AmountWithVAT18"];

            $service["pricecustomer"] = $service["AmountServices"];
            $service["amountclient"] = $service["AmountServices"];

            if ($service["TypeOfTicket"] == "R") {
                $service["price"] = -1 * $service["price"];
                $service["amount"] = -1 * $service["amount"];
                $service["AmountServices"] = -1 * $service["AmountServices"];
                $service["AmountWithVAT18"] = -1 * $service["AmountWithVAT18"];
                $service["VATAmount18"] = -1 * $service["VATAmount18"];

                $service["pricecustomer"] = -1 * $service["pricecustomer"];
                $service["amountclient"] = -1 * $service["amountclient"];
            }

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
                $Seconded["FirstNameLatin"] = $this->DTV($ars, [1]);;
                $Seconded["LastNameLatin"] = $this->DTV($ars, [0]);
                $Seconded["SurNameLatin"] = $this->DTV($ars, [2]);


                $Seconded["DocumentNumber"] = $valueS["DocumentNumber"];

                try {
                    $Seconded["BirthDay"] = $this->DTV($valueS, ["BirthDay"], "", "d.m.Y");
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
