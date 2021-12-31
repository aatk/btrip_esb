<?php

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 21/02/2019
 * Time: 15:10
 */
class Aviacenter extends ex_component
{
    private $metod;
    private $gds;
    private $connectionInfo;
    private $supplier;

    private $alltaxs;
    private $allsegments;

    private $Auth;
    private $token;


    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        parent::__construct($metod);
        $this->metod = $metod;
        $this->gds = $_SESSION["i4b"][$this->classname];

        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем

        $this->debugclass = $debug;
        $this->Auth = new Auth();
        $this->supplier = ["INN" => "", "KPP" => "", "Name" => "Aviacenter"];
    }


    private function getservicestandart(&$service, $book, $ticket, $passenger)
    {

        $status = $this->DTV($ticket, ["status_code"]);

//        Booked Забронировано
//        Ticketed Выписано
//        Paid  Оплачен
//        Cancelled Отменен
//        Refunded Возвращено
//        RefundAuthorized Возврат денег разрешен
//        PartiallyRefunded Частично возвращено

        if ($status == "Booked") {
            $service["TypeOfTicket"] = "B";
            $service["nomenclature"] = "БронированиеАвиабилета";
        }

        if (($status == "Ticketed") || ($status == "Paid")) {
            $service["TypeOfTicket"] = "S";
            $service["nomenclature"] = "Авиабилет";
        }

        if ($status == "Cancelled") {
            $service["TypeOfTicket"] = "V";
            $service["nomenclature"] = "ОтменаАвиабилета";
        }

        if (($status == "Refunded") || ($status == "RefundAuthorized") || ($status == "PartiallyRefunded")) {
            $service["TypeOfTicket"] = "R";
            $service["nomenclature"] = "ВозвратАвиабилета";
        }
        //Ticketed

        $number = $this->DTV($ticket, ["locator"]) . $this->DTV($passenger, ["id"]);
        if ($service["TypeOfTicket"] == "S") {
            $n = $this->DTV($passenger, ["ticker_number"]);
            if ($n != "") {
                $number = $n;
                if (strpos($n, "-") === false) {
                    $number = substr($number, 0, 3) . "-" . substr($number, 3); //S201-5963569871
                }
            } else {
                $service["TypeOfTicket"] = "B";
                $service["nomenclature"] = "БронированиеАвиабилета";
            }
        }

        $service["Synh"] = $service["TypeOfTicket"] . $number;
        $service["TicketNumber"] = $number;

        $service["TariffAmount"] = $this->DTV($ticket, ["amounts", "tariff"]);

        $service["supplier"] = $this->supplier;
        $service["Supplier"] = $this->supplier;

    }

    private function AddPassenger(&$service, $book, $ticket, $passenger)
    {

        /* КОМАНДИРУЕМЫЕ */
        $Seconded = [];
        $Seconded["FirstNameLatin"] = ucfirst(strtolower($this->DTV($passenger, ["first_name"])));
        $Seconded["LastNameLatin"] = ucfirst(strtolower($this->DTV($passenger, ["last_name"])));
        $Seconded["SurNameLatin"] = ucfirst(strtolower($this->DTV($passenger, ["middle_name"])));

        $Seconded["FirstName"] = $Seconded["FirstNameLatin"];
        $Seconded["LastName"] = $Seconded["LastNameLatin"];
        $Seconded["SurName"] = $Seconded["SurNameLatin"];

        $Seconded["DocumentNumber"] = $this->DTV($passenger, ["document_number"]);
        $Seconded["DocType"] = $this->DTV($passenger, ["document", "type"]);
        $Seconded["BirthDay"] = $this->DTV($passenger, ["birthday"], "", "Y-m-d");

        $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
        $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);
        $service["seconded"][] = $Seconded;
    }


    private function BuildRoute(&$Route, $Whot)
    {
        $lastWhot = $Route[count($Route) - 1];
        if ($lastWhot != $Whot) {
            $Route[] = $Whot;
        }
    }

    private function AddSegments(&$services, &$service, $book)
    {

        $segmentout = [];
        $RouteShortened = [];
        $Route = [];


        $segmentservice = [];
        $segments = $this->DTV($book, ["segments"]);
        foreach ($segments as $wheresegments) {
            foreach ($wheresegments as $segment) {
                $segmentservice = $this->get_empty_v3();

                $segmentservice["nomenclature"] = "СегментАвиабилета";
                $segmentservice["supplier"] = $this->supplier;
                $segmentservice["Supplier"] = $this->supplier;

                $service["Carrier"] = $this->DTV($segment, ["carrier_code"]);
                $service["CarrierContractor"] = $service["Carrier"];

                $DepartureCode = $this->DTV($segment, ["airport_departure"]);
                $segmentservice["DepartureCode"] = $DepartureCode;

                $ArrivalCode = $this->DTV($segment, ["airport_arrival"]);
                $segmentservice["ArrivalCode"] = $ArrivalCode;

                $segmentservice["CityDeparture"] = $this->DTV($segment, ["city_departure"]);
                $segmentservice["CityArrival"] = $this->DTV($segment, ["city_arrival"]);

                $segmentservice["PlaceDeparture"] = $DepartureCode;
                $segmentservice["PlaceArrival"] = $ArrivalCode;

                $segmentservice["Depart"] = $this->DTV($segment, ["date_departure"], "", "Y-m-d H:i:s");
                $segmentservice["Arrival"] = $this->DTV($segment, ["date_departure"], "", "Y-m-d H:i:s");

                $segmentservice["ServiceStartDate"] = $segmentservice["Depart"];
                $segmentservice["ServiceEndDate"] = $segmentservice["Arrival"];

                $diff = strtotime($segmentservice["ServiceEndDate"]) - strtotime($segmentservice["ServiceStartDate"]);
                $segmentservice["TravelTime"] = round(abs($diff) / 60);

                $service["FareBases"] = $this->DTV($segment, ["fare_code"]);

                $segmentservice["Synh"] = md5(json_encode($segmentservice, JSON_UNESCAPED_UNICODE));
                $segmentservice["MD5SourceFile"] = $segmentservice["Synh"];

                $segmentservice["date"] = $segmentservice["Depart"];

                $segmentout[] = $segmentservice["Synh"];

                if (!in_array($segmentservice["Synh"], $this->allsegments)) {
                    $this->allsegments[] = $segmentservice["Synh"];
                    $services[] = $segmentservice;
                }
                //$services[] = $segmentservice;

                $Whot = $segmentservice["DepartureCode"];
                $this->BuildRoute($RouteShortened, $Whot);
                $Whot = $segmentservice["ArrivalCode"];
                $this->BuildRoute($RouteShortened, $Whot);

                $Whot = $segmentservice["CityDeparture"];
                $this->BuildRoute($Route, $Whot);
                $Whot = $segmentservice["CityArrival"];
                $this->BuildRoute($Route, $Whot);

                if ($service["Depart"] == "") {
                    $service["Depart"] = $segmentservice["Depart"];
                }
                if ($service["DepartureCode"] == "") {
                    $service["DepartureCode"] = $segmentservice["DepartureCode"];
                }

                if ($service["CityDeparture"] == "") {
                    $service["CityDeparture"] = $segmentservice["CityDeparture"];
                }
                if ($service["PlaceDeparture"] == "") {
                    $service["PlaceDeparture"] = $segmentservice["PlaceDeparture"];
                }
            }
        }

        if ($service["Arrival"] == "") {
            $service["Arrival"] = $this->DTV($segmentservice, ["Arrival"]);
        }
        if ($service["ArrivalCode"] == "") {
            $service["ArrivalCode"] = $this->DTV($segmentservice, ["ArrivalCode"]);
        }
        if ($service["CityArrival"] == "") {
            $service["CityArrival"] = $this->DTV($segmentservice, ["CityArrival"]);
        }
        if ($service["PlaceArrival"] == "") {
            $service["PlaceArrival"] = $this->DTV($segmentservice, ["PlaceArrival"]);
        }

        $service["ServiceStartDate"] = $service["Depart"];
        $service["ServiceEndDate"] = $service["Arrival"];

        $service["Route"] = implode(" - ", $Route);
        $service["RouteShortened"] = implode(" - ", $RouteShortened);

        return $segmentout;
    }

    private function AddTaxs(&$services, &$inservice, $tiket)
    {
        $fees = [];

        $vat_amount_full = 0;

        $vat_rate = -1;
        $vat_amount = 0;
        $vat_price = 0;
        $Taxs = [];

        $TAXES = $this->DTV($tiket, ["fiscal_data", "taxes"]);

        foreach ($TAXES as $taxjson) {

            $service = $this->get_empty_v3();

            $servicetax = [];
            $servicetax["nomenclature"] = "ТаксыАвиабилета";
            $servicetax["date"] = date("YmdHis");
            $servicetax["NameFees"] = $this->DTV($taxjson, ["code"]);

            $servicetax["price"] = (float)$taxjson["amount"];
            $servicetax["amount"] = (float)$taxjson["amount"];

            $percent = (float)$taxjson["percent"];
            if ($percent == 0) {
                $vat_rate = -1;
            } else {
                $vat_rate = 100+$percent;
            }
            $servicetax["amountVAT"] = round($servicetax["price"] / (100 + $percent) * $percent, 2); //
            $servicetax["VATrate"] = $vat_rate;

            $servicetax["pricecustomer"] = $servicetax["price"];
            $servicetax["amountclient"] = $servicetax["price"];
            $servicetax["VATratecustomer"] = $servicetax["VATrate"];
            $servicetax["amountVATcustomer"] = $servicetax["amountVAT"];

            $servicetax["supplier"] = $this->supplier;
            $servicetax["Supplier"] = $this->supplier;


            $service = array_merge($service, $servicetax);


            $service["Synh"] = md5(json_encode($servicetax, JSON_UNESCAPED_UNICODE));
            $service["MD5SourceFile"] = $service["Synh"];

            if (!in_array($service["Synh"], $this->alltaxs)) {
                $this->alltaxs[] = $service["Synh"];
                $services[] = $service;
            }
            //$services[] = $service;

            $fees[] = $service["Synh"]; //Это для создания списка такс
        }


        return ["Fees" => $fees, "TaxsAmount" => $vat_amount_full];
    }


    private function CreateVendirFee(&$services, $inservice)
    {
        if ($inservice["VendorFeeAmount"] > 0) {
            $service = $inservice;
            $service["nomenclature"] = "СборПоставщика";
            $service["Synh"] = $service["nomenclature"] . $inservice["Synh"];

            $service["ownerservice"] = $inservice["Synh"];
            $service["ApplicationService"] = $inservice["Synh"];
            $service["attachedto"] = $inservice["Synh"];

            $service["TicketNumber"] = "";
            $service["TypeOfTicket"] = "";
            $service["TypeThisService"] = "Загруженная";

            $service["pricecustomer"] = $inservice["VendorFeeAmount"];
            $service["amountclient"] = $inservice["VendorFeeAmount"];
            $service["amountVATcustomer"] = 0;
            $service["VATratecustomer"] = -1;

            $service["price"] = $inservice["VendorFeeAmount"];
            $service["amount"] = $inservice["VendorFeeAmount"];
            $service["amountVAT"] = 0;
            $service["VATrate"] = -1;

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
    }


    private function CreateGVA($service, $info)
    {
        //Услуга БезНДС
        if (($service["TypeOfTicket"] == "R") || ($service["TypeOfTicket"] == "V")) {
            $service["nomenclature"] = "ВозвратСбораГражданскойВоздушнойАвиации";
        } else {
            $service["nomenclature"] = "СборГражданскойВоздушнойАвиации";
        }

        $service["ownerservice"] = $service["Synh"];
        $service["attachedto"] = $service["Synh"];
        $service["TypeThisService"] = "Загруженная";

        $service["Synh"] = $service["nomenclature"] . " " . $service["Synh"];

        $service["price"] = $info["price"];
        $service["amountVAT"] = $info["amountVAT"];
        $service["VATrate"] = -1;
        $service["amount"] = $info["amount"];

        $service["pricecustomer"] = $service["price"];
        $service["amountVATcustomer"] = $service["amountVAT"];
        $service["VATratecustomer"] = -1;
        $service["amountclient"] = $service["amount"];

        return $service;
    }

    private function CreateSA($service, $info)
    {
        //Услуга БезНДС
        if (($service["TypeOfTicket"] == "R") || ($service["TypeOfTicket"] == "V")) {
            $service["nomenclature"] = "ВозвратСборАвиаперевозчика";
        } else {
            $service["nomenclature"] = "СборАвиаперевозчика";
        }

        $service["ownerservice"] = $service["Synh"];
        $service["attachedto"] = $service["Synh"];
        $service["TypeThisService"] = "Загруженная";

        $service["Synh"] = $service["nomenclature"] . " " . $service["Synh"];

        $service["price"] = $info["price"];
        $service["amountVAT"] = $info["amountVAT"];
        $service["VATrate"] = 120;
        $service["amount"] = $info["amount"];

        $service["pricecustomer"] = $service["price"];
        $service["amountVATcustomer"] = $service["amountVAT"];
        $service["VATratecustomer"] = 120;
        $service["amountclient"] = $service["amount"];

        return $service;
    }

    private function CreatePenalty($service, $info)
    {
        //Услуга БезНДС
        if (($service["TypeOfTicket"] == "R") || ($service["TypeOfTicket"] == "V")) {
            $service["nomenclature"] = "ШтрафЗаВозвратАвиабилета";
        } else {
            $service["nomenclature"] = "ШтрафЗаОбменАвиабилета";
        }


        $service["ApplicationService"] = $service["Synh"];
        $service["ownerservice"] = $service["Synh"];
        //$service["attachedto"] = $service["Synh"];
        $service["TypeThisService"] = "Загруженная";

        $service["Synh"] = $service["nomenclature"] . " " . $service["Synh"];

        $service["price"] = $info["price"];
        $service["amountVAT"] = $info["amountVAT"];
        $service["VATrate"] = -1;
        $service["amount"] = $info["amount"];

        $service["pricecustomer"] = $service["price"];
        $service["amountVATcustomer"] = $service["amountVAT"];
        $service["VATratecustomer"] = -1;
        $service["amountclient"] = $service["amount"];

        return $service;
    }


    private function v3avia($innerjson)
    {
        $services = [];

        $book = $this->DTV($innerjson, ["data", "passengers"], "");

        if ($book == "") {
            $book = $this->DTV($innerjson, ["data", "records"]);
        }
        foreach ($book as $ticket) {


            $passenger = $ticket["passenger_data"];
            //foreach ($ticket["passenger_data"] as $passenger) {
            $service = $this->get_empty_v3();

            $this->getservicestandart($service, $ticket, $ticket, $passenger);

            $this->AddPassenger($service, $book, $ticket, $passenger);

            $service["Segments"] = $this->AddSegments($services, $service, $ticket);

            $Fees = $this->AddTaxs($services, $service, $ticket);
            $service["Fees"] = $Fees["Fees"];

            $service["date"] = $this->DTV($ticket, ["book_date"], "", "Y-m-d H:i:s");

            $indexvalue = 1;
            if (($service["TypeOfTicket"] == "V") || ($service["TypeOfTicket"] == "R")) {
                $indexvalue = -1;
            }

            $VendorFeeAmount = (float)$this->DTV($ticket, ["amounts", "partner_fee"]);
            $service["VendorFeeAmount"] = $VendorFeeAmount;


//            $pricecustomer = (float)$this->DTV($book, ["order", "price", "RUB", "amount"]);
//            $pricecustomer = $pricecustomer - $VendorFeeAmount;
//            $amountclient = $pricecustomer;

            //----

            $vats = [];
            $fiscal_data = $this->DTV($ticket, ["fiscal_data"]);

            $fiscal_tariff = $this->DTV($fiscal_data, ["tariff"]);
            $procent = (int)$this->DTV($fiscal_tariff, ["percent"]);
            $key = "_".$procent;

            $vats[$key]["amount"] = $this->DTV($fiscal_tariff, ["amount"]);
            $vats[$key]["amountVAT"] = $this->DTV($fiscal_tariff, ["amount"])/(100+$procent)*$procent;

            $fiscal_taxes = $this->DTV($fiscal_data, ["taxes"]);
            foreach ($fiscal_taxes as $tax) {
                $procent = (int)$this->DTV($tax, ["vat_percent"]);
                $key = "_".$procent;
                $vats[$key]["amount"] = $vats[$key]["amount"] + $this->DTV($tax, ["tax_amount"]);
                $vats[$key]["amountVAT"] += $this->DTV($tax, ["vat_amount"]);///(100+$procent)*$procent;
                $vats[$key]["price"] = $vats[$key]["amount"];
                $vats[$key]["procent"] = $procent;
            }

            $amount = 0;
            $vatsamount = 0;
            foreach ($vats as $vat) {
                $vatsamount += $vat["amountVAT"];
                if ($vat["amountVAT"] > 0) {
                    $amount += $vat["amount"];
                }
            }
            //print_r($vats);

            $ServiceFeeAmount = $this->DTV($ticket, ["amounts", "agent_fee"]);
            $service["ServiceFeeAmount"] = $ServiceFeeAmount;

            $TariffAmount = $service["TariffAmount"];
            $TaxAmount = $this->DTV($ticket, ["amounts", "tax_amount"]);


            $service["price"] = $indexvalue * ($TariffAmount + $TaxAmount);
            $service["amount"] = $indexvalue * ($TariffAmount + $TaxAmount);
            $service["amountVAT"] = $indexvalue * (float)$vatsamount;

            $price = $TariffAmount + $TaxAmount;

            if ($this->debugclass) {
                print_r($vats);
            }

            //$amount != $prices
            if (isset($vats["_10"])) {
                //Есть суммы с НДС 10
                $amount = $vats["_10"]["amount"];
                $service["pricecustomer"] = $amount;
                $service["amountclient"] = $amount;
                $service["VATratecustomer"] = 110;
                $service["amountVATcustomer"] = $vats["_10"]["amountVAT"];
            } elseif (isset($vats["_0"])) {
                $amount = $vats["_0"]["amount"];
                $service["pricecustomer"] = $amount;
                $service["amountclient"] = $amount;
                $service["VATratecustomer"] = -1;
                $service["amountVATcustomer"] = 0;
            }

            $service["pricecustomer"] = $indexvalue * ($service["pricecustomer"]);
            $service["amountclient"] = $indexvalue * ($service["amountclient"]);
            $service["amountVATcustomer"] = $indexvalue * ($service["amountVATcustomer"]);

            $service["AmountServices"] = $service["pricecustomer"];

            $service["SegmentCount"] = count($service["Segments"]);

            $services[] = $service;

            //print_r($vats);
            if (isset($vats["_0"])) {
                $serviceGVA = $this->CreateGVA($service, $vats["_0"]);
                $services[] = $serviceGVA;
            }

            if (isset($vats["_20"])) {
                $serviceSA = $this->CreateSA($service, $vats["_20"]);
                $services[] = $serviceSA;
            }

            $this->CreateVendirFee($services, $service);

        }

        return ["services" => $services];
    }


    public function getinfo($params)
    {
        $result = ["result" => false];
        $textjson = $this->phpinput;

        $json = json_decode($textjson, true);

        if (true) {

            $jsonv3 = $this->v3avia($json);

            $result = ["result" => true, "json" => $json, "jsonv3" => $jsonv3];
        } else {
            $result["message"] = "Error auth";
        }

        return $result;
    }

}