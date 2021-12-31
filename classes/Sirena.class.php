<?php

class Sirena extends ex_classlite
{

    private $metod;
    private $convert;
    private $gds;
    private $classname;
    private $connectionInfo;

    private $Auth;
    //private $Catalogs;

    private $supplier;


    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        parent::__construct();
        $this->metod = $metod;
        $this->gds = $_SESSION["i4b"][$this->classname];

        $this->convert = new Conversion("INNER");
        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем

        $this->debugclass = $debug;
        $this->Auth = new Auth();
        //$this->Catalogs = new Catalogs();

        $this->supplier = ["INN" => "7714017443", "KPP" => "771001001", "Name" => "ТКП"];
    }

    private function BuildRoute(&$Route, $Whot)
    {
        $lastWhot = $Route[count($Route) - 1];
        if ($lastWhot != $Whot) {
            $Route[] = $Whot;
        }
    }

    private function BuilRouteStr($SegmentsService)
    {
        $Route = [];
        $RouteShortened = [];

        foreach ($SegmentsService as $Segment) {
            $this->BuildRoute($RouteShortened, $Segment["DepartureCode"]);
            $this->BuildRoute($RouteShortened, $Segment["ArrivalCode"]);

            $this->BuildRoute($Route, $Segment["CityDeparture"]);
            $this->BuildRoute($Route, $Segment["CityArrival"]);
        }

        $RouteStr = implode(" - ", $Route);
        $RouteShortenedStr = implode(" - ", $RouteShortened);

        return ["Route" => $RouteStr, "RouteShortened" => $RouteShortenedStr];
    }

    private function GetFeeInfo($tiket, &$services)
    {
        $fees = [];

        $vat_amount_full = 0;

        $vat_rate = -1;
        $vat_amount = 0;
        $vat_price = 0;
        $Taxs = [];

        foreach ($tiket->TAXES->TAX as $tax) {
            $taxjson = $this->object2array($tax);

            $vat_rate = -1;
            $vat_amount = 0;
            $vat_price = 0;

            if (isset($taxjson["@attributes"])) {
                $vat_rate = (float)$this->DTV($taxjson, ["@attributes", "vat_rate"]);
                $vat_amount = (float)$this->DTV($taxjson, ["@attributes", "vat_amount"]);
                $vat_amount_full += (float)$taxjson["AMOUNT"];
            } else {
                $vat_amount_full += (float)$taxjson["AMOUNT"];
            }

            $CODE = $taxjson["CODE"];
            if (isset($Taxs[$CODE])) {
                $Tax = $Taxs[$CODE];
            } else {
                $Tax = [];
                $Tax["vat_rate"] = $vat_rate;
            }
            $Tax["vat_amount"] += $vat_amount;
            $Tax["amount"] += (float)$taxjson["AMOUNT"];

            $service = $this->get_empty_v3();

            $servicetax = [];
            $servicetax["nomenclature"] = "ТаксыАвиабилета";
            $servicetax["date"] = date("YmdHis");
            $servicetax["NameFees"] = $taxjson["CODE"];

            $servicetax["price"] = (float)$taxjson["AMOUNT"];
            $servicetax["amount"] = (float)$taxjson["AMOUNT"];
            $servicetax["amountVAT"] = round($servicetax["price"] / 120 * 20, 2); //
            $servicetax["VATrate"] = (float)$vat_rate;

            $servicetax["pricecustomer"] = (float)$taxjson["AMOUNT"];
            $servicetax["amountclient"] = (float)$taxjson["AMOUNT"];
            $servicetax["VATratecustomer"] = (float)$servicetax["VATrate"];
            $servicetax["amountVATcustomer"] = (float)$servicetax["amountVAT"];

            $servicetax["supplier"] = $this->supplier;
            $servicetax["Supplier"] = $this->supplier;

            $vat_price += $servicetax["price"];

            $service = array_merge($service, $servicetax);


            $service["Synh"] = md5(json_encode($servicetax, JSON_UNESCAPED_UNICODE));
            $service["MD5SourceFile"] = $service["Synh"];

            $vat_amount -= $service["amountVAT"];

            $services[] = $service;

            $fees[] = $service["Synh"]; //Это для создания списка такс

            $Taxs[$CODE] = $Tax;
        }

        if ($vat_amount != 0) {
            $services[count($services) - 1]["amountVAT"] += $vat_amount;
            //$services[count($services) - 1]["amountVAT"] += $vat_amount;
        }

        $vatinfo = ["Rate" => $vat_rate, "Amount" => $vat_amount_full, "Price" => $vat_price];
        $ZZ = $Taxs["ZZ"];
        if (isset($Taxs["ZZ"])) {
            $vatinfo = ["Rate" => $Taxs["ZZ"]["vat_rate"], "Amount" => $Taxs["ZZ"]["amount"], "Price" => $Taxs["ZZ"]["vat_amount"]];
        }

        return ["Fees" => $fees, "TaxsAmount" => $vat_amount_full, "vatinfo" => $vatinfo];
    }

    private function GetSegmentsInfo($tiket, &$services)
    {
        $segments = [];

        $min_date = strtotime("+5 years", time());
        $max_date = 0;

        $min_port = "";
        $max_port = "";

        $RouteShortened = [];
        $Route = [];

        foreach ($tiket->SEGMENTS->SEGMENT as $segment) {
            $segmentjson = $this->object2array($segment);

            $service = $this->get_empty_v3();

            $servicesegment = [];
            $servicesegment["nomenclature"] = "СегментАвиабилета";

            $servicesegment["price"] = (float)$segmentjson["NFARE"];
            $servicesegment["amount"] = (float)$segmentjson["NFARE"];
            $servicesegment["amountVAT"] = 0; //
            $servicesegment["VATrate"] = -1;

            $servicesegment["pricecustomer"] = $servicesegment["price"];
            $servicesegment["amountclient"] = $servicesegment["amount"];
            $servicesegment["amountVATcustomer"] = $servicesegment["amountVAT"];
            $servicesegment["VATratecustomer"] = $servicesegment["VATrate"];

            $DepartureCode = $segmentjson["CITY1CODE"];
            $ArrivalCode = $segmentjson["CITY2CODE"];
            if ($this->DTV($segmentjson, ["PORT1CODE"]) != "") {
                $DepartureCode = $this->DTV($segmentjson, ["PORT1CODE"]);
            }
            if ($this->DTV($segmentjson, ["PORT2CODE"]) != "") {
                $ArrivalCode = $this->DTV($segmentjson, ["PORT2CODE"]);
            }
            $servicesegment["DepartureCode"] = $DepartureCode;
            $servicesegment["ArrivalCode"] = $ArrivalCode;

            $this->BuildRoute($RouteShortened, $servicesegment["DepartureCode"]);
            $this->BuildRoute($RouteShortened, $servicesegment["ArrivalCode"]);

            //Подключить КЕШ для IATA кодов и сокращенного маршрута

            $servicesegment["PlaceDeparture"] = $servicesegment["DepartureCode"];
            $servicesegment["CityDeparture"] = $servicesegment["DepartureCode"];
//            $res = $this->Catalogs->getCatalogItems("Propertys", ["value" => $servicesegment["DepartureCode"]]);
//            if ($res) {
//                $idplace = $res[0]["place"];
//                $res = $this->Catalogs->getCatalogItems("Places", ["id" => $idplace]);
//                if ($res) {
//                    $servicesegment["PlaceDeparture"] = $res[0]["place"];
//                    $findCity = true;
//                    do {
//                        if ($res[0]["type"] == "Город") $servicesegment["CityDeparture"] = $res[0]["place"];
//                        if (($res[0]["parent"] == 0) || ($res[0]["type"] == "Город")) $findCity = false;
//                        $res = $this->Catalogs->getCatalogItems("Places", ["id" => $res[0]["parent"]]);
//                    } while ($findCity);
//                }
//            }

            $servicesegment["PlaceArrival"] = $servicesegment["ArrivalCode"];
            $servicesegment["CityArrival"] = $servicesegment["ArrivalCode"];
//            $res = $this->Catalogs->getCatalogItems("Propertys", ["value" => $servicesegment["ArrivalCode"]]);
//            if ($res) {
//                $idplace = $res[0]["place"];
//                $res = $this->Catalogs->getCatalogItems("Places", ["id" => $idplace]);
//                if ($res) {
//                    $servicesegment["PlaceArrival"] = $res[0]["place"];
//                    $findCity = true;
//                    do {
//                        if ($res[0]["type"] == "Город") $servicesegment["CityArrival"] = $res[0]["place"];
//                        if (($res[0]["parent"] == 0) || ($res[0]["type"] == "Город")) $findCity = false;
//                        $res = $this->Catalogs->getCatalogItems("Places", ["id" => $res[0]["parent"]]);
//                    } while ($findCity);
//                }
//            }

            $this->BuildRoute($Route, $servicesegment["CityDeparture"]);
            $this->BuildRoute($Route, $servicesegment["CityArrival"]);

            //RouteShortened

            $servicesegment["TerminalDepartures"] = $this->DTV($segmentjson, ["TERM1"]);
            $servicesegment["TerminalArrivals"] = $this->DTV($segmentjson, ["TERM2"]);

            $servicesegment["SegmentFlight"] = $this->DTV($segmentjson, ["REIS"]);
            $servicesegment["Carrier"] = $this->DTV($segmentjson, ["CARRIER"]);


            try {
                $servicesegment["Depart"] = $segmentjson["FLYDATE"] . $segmentjson["FLYTIME"] . "00";
                $format = "dmYHis";
                $dateDepart = DateTime::createFromFormat($format, $servicesegment["Depart"]);
                $servicesegment["Depart"] = $dateDepart->format("YmdHis"); //!!!!!
            } catch (Exception $e) {
                $servicesegment["Depart"] = "";
            }

            if ($min_date > strtotime($servicesegment["Depart"])) {
                $min_date = strtotime($servicesegment["Depart"]);
                $min_port = $DepartureCode;
            }

            try {
                $servicesegment["Arrival"] = $segmentjson["ARRDATE"] . $segmentjson["ARRTIME"] . "00";
                $format = "dmYHis";
                $dateArrival = DateTime::createFromFormat($format, $servicesegment["Arrival"]);
                $servicesegment["Arrival"] = $dateArrival->format("YmdHis"); //!!!!!
            } catch (Exception $e) {
                $servicesegment["Arrival"] = "";
            }

            if ($max_date < strtotime($servicesegment["Arrival"])) {
                $max_date = strtotime($servicesegment["Arrival"]);
                $max_port = $ArrivalCode;
            }

            $servicesegment["ServiceStartDate"] = $servicesegment["Depart"];
            $servicesegment["ServiceEndDate"] = $servicesegment["Arrival"];

            $servicesegment["date"] = $servicesegment["Depart"];

            $diff = strtotime($servicesegment["ServiceEndDate"]) - strtotime($servicesegment["ServiceStartDate"]);
            $servicesegment["TravelTime"] = round(abs($diff) / 60);


            $servicesegment["supplier"] = $this->supplier;
            $servicesegment["Supplier"] = $this->supplier;

            $servicesegment["TicketClass"] = $this->DTV($segmentjson, ["CLASS"]);

            $service = array_merge($service, $servicesegment);

            $service["Synh"] = md5(json_encode($servicesegment, JSON_UNESCAPED_UNICODE));
            $service["MD5SourceFile"] = $service["Synh"];

            $services[] = $service;

            $segments[] = $service["Synh"]; //Это для создания списка такс
        }

        $RouteStr = implode(" - ", $Route);
        $RouteShortenedStr = implode(" - ", $RouteShortened);

        return ["Segments" => $segments, "Route" => $RouteStr, "RouteShortened" => $RouteShortenedStr, "Depart" => ["date" => $max_date, "port" => $max_port], "Arrival" => ["date" => $min_date, "port" => $min_port]];
    }


    private function CreateGVA($service, $info)
    {
        unset($service["Fees"]);
        unset($service["Segments"]);

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
        unset($service["Fees"]);
        unset($service["Segments"]);

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


    private function v3air($xml)
    {
        $services = [];

        foreach ($xml->TICKET as $tiket) {
            $injson = $this->object2array($tiket);

            $service = $this->get_empty_v3();

            //DEALDATE 11072019
            $date = $this->DTV($injson, ["DEALDATE"], "", "dmY");
            $service["date"] = $date;

            //Вытащим самое сложное
            $attvat = $this->object2array($tiket->FARE->attributes());
            $vat = $this->DTV($attvat, ["@attributes", "vat_amount"]);
            $service["amountVAT"] = (float)$vat;
            $service["amountVATcustomer"] = (float)$vat;

            $service["supplier"] = $this->supplier;
            $service["Supplier"] = $this->supplier;

            $service["nomenclature"] = "Авиабилет";
            $TicketNumber = $injson["BSONUM"];
            $service["TicketNumber"] = substr($TicketNumber, 0, 3) . "-" . substr($TicketNumber, 3);

            $service["TypeOfTicket"] = "V";
            if ($injson["OPTYPE"] == "SALE") {
                $service["TypeOfTicket"] = "S";
                $service["nomenclature"] = "Авиабилет";
            } elseif (substr($injson["OPTYPE"], 0, 1) == "E") {
                $service["TypeOfTicket"] = "E";
                $service["nomenclature"] = "ОбменАвиабилета";
            } elseif (substr($injson["OPTYPE"], 0, 1) == "R") {
                $service["TypeOfTicket"] = "R";
                $service["nomenclature"] = "ВозвратАвиабилета";
            } else {
                $service["nomenclature"] = "ОтменаАвиабилета";
            }
            $service["Synh"] = $service["TypeOfTicket"] . substr($TicketNumber, 0, 3) . "-" . substr($TicketNumber, 3);
            $service["manager"] = $this->DTV($attvat, ["DEAL", "@attributes", "disp"]);
            $service["TariffAmount"] = (float)$injson["FARE"];
    
            
            if ($service["amountVATcustomer"] != 0) {
                $service["VATratecustomer"] = 110;
            }

            $fullprice = $this->DTV($injson, ["FOPS", "FOP", "AMOUNT"]);
            $service["price"] = (float)$fullprice;
            $service["amount"] = (float)$fullprice;
            $service["amountVAT"] = $service["amountVATcustomer"];
            $service["VATrate"] = $service["VATratecustomer"];

            
            /* СЕГМЕНТЫ */
            $Segments = $this->GetSegmentsInfo($tiket, $services);
            $service["Segments"] = $Segments["Segments"]; //Сегменты
            $service["RouteShortened"] = $Segments["RouteShortened"]; //Сегменты
            $service["Route"] = $Segments["Route"]; //Сегменты

            $service["Depart"] = date("YmdHis", $Segments["Depart"]["date"]);
            $service["Arrival"] = date("YmdHis", $Segments["Arrival"]["date"]);

            $service["ArrivalCode"] = $Segments["Arrival"]["port"];
            $service["DepartureCode"] = $Segments["Depart"]["port"];

            $service["ServiceStartDate"] = $service["Arrival"];
            $service["ServiceEndDate"] = $service["Depart"];

            $service["PlaceArrival"] = $service["ArrivalCode"];
            $service["PlaceDeparture"] = $service["DepartureCode"];

            $diff = strtotime($service["ServiceEndDate"]) - strtotime($service["ServiceStartDate"]);
            $service["TravelTime"] = round(abs($diff) / 60 / 60);

            //GENERAL_CARRIER
            $CARRIER = $this->DTV($injson, ["GENERAL_CARRIER"]);
            $service["Carrier"] = $CARRIER;

            //ReservationNumber
            $ReservationNumber = $this->DTV($injson, ["PNR_LAT"]);
            $service["ReservationNumber"] = $ReservationNumber;


            //Подключить КЕШ для IATA кодов и сокращенного маршрута
//            $res = $this->Catalogs->getCatalogItems("Propertys", ["value" => $service["DepartureCode"]]);
//            if ($res) {
//                $idplace = $res[0]["place"];
//                $res = $this->Catalogs->getCatalogItems("Places", ["id" => $idplace]);
//                if ($res) {
//                    $service["PlaceDeparture"] = $res[0]["place"];
//
//                    $findCity = true;
//                    do {
//                        if ($res[0]["type"] == "Город") {
//                            $findCity = false;
//                            $service["CityDeparture"] = $res[0]["place"];
//                        }
//
//                        if ($res[0]["parent"] == 0) {
//                            $findCity = false;
//                        }
//
//                        $res = $this->Catalogs->getCatalogItems("Places", ["id" => $res[0]["parent"]]);
//                    } while ($findCity);
//                }
//            }
//            $res = $this->Catalogs->getCatalogItems("Propertys", ["value" => $service["ArrivalCode"]]);
//            if ($res) {
//                $idplace = $res[0]["place"];
//                $res = $this->Catalogs->getCatalogItems("Places", ["id" => $idplace]);
//                if ($res) {
//                    $service["PlaceArrival"] = $res[0]["place"];
//
//                    $findCity = true;
//                    do {
//                        if ($res[0]["type"] == "Город") {
//                            $findCity = false;
//                            $service["CityArrival"] = $res[0]["place"];
//                        }
//
//                        if ($res[0]["parent"] == 0) {
//                            $findCity = false;
//                        }
//
//                        $res = $this->Catalogs->getCatalogItems("Places", ["id" => $res[0]["parent"]]);
//                    } while ($findCity);
//
//                }
//            }
//            RouteShortened
//
//            $RouteInfo = $this->BuilRouteStr($SegmentsService);
//            $service["Route"] = $RouteInfo["Route"];
//            $service["RouteShortened"] = $RouteInfo["RouteShortened"];
//
//            Подключить геолокацию Яндекс, для получения нормальных адресов


            /* ТАКСЫ */
            $fee = $this->GetFeeInfo($tiket, $services);
            $service["Fees"] = $fee["Fees"];
            
            //$service["pricecustomer"] += $fee["TaxsAmount"];
            //$service["amountclient"] += $fee["TaxsAmount"];

            if ($fee["vatinfo"]["Rate"] == 20) {
                $service["VATAmount18"] = $fee["vatinfo"]["Price"];
                $service["AmountWithVAT18"] = $fee["vatinfo"]["Amount"];
            }
    
    
            $pricecustomer = $fullprice - $service["AmountWithVAT18"];
            $service["pricecustomer"] = $pricecustomer;
            $service["amountclient"] = $pricecustomer;
    
            if ($service["amountVATcustomer"] > 0) {
                //Есть НДС
                $fullPriceFromVat = round($service["amountVATcustomer"] / 10 * 110, 0);
                if (( $fullPriceFromVat > $pricecustomer-5 ) && ( $fullPriceFromVat < $pricecustomer+5 )) {
                    //$fullPriceFromVat в пределах +-5 рублей, скорее всего вся сумма НДС целиком на билет
                } else {
                    //Сумма с НДС и Без НДС различны, есть ГВА
                    $service["pricecustomer"] = $fullPriceFromVat;
                    $service["amountclient"] = $fullPriceFromVat;
                }
            }
    
    
    
            $service["info"] = $this->DTV($injson, ["ENDORS_RESTR"]);

            $Seconded = [];
            $ars = explode(" ", $this->DTV($injson, ["NAME"]));
            $Seconded["FirstName"] = $this->DTV($ars, [0]);
            $Seconded["LastName"] = $this->DTV($injson, ["SURNAME"]);
            $Seconded["SurName"] = $this->DTV($ars, [1]);
            $Seconded["FirstNameLatin"] = "";
            $Seconded["LastNameLatin"] = "";
            $Seconded["SurNameLatin"] = "";
            try {
                $Seconded["BirthDay"] = $this->DTV($injson, ["BIRTH_DATE"]) . "000000"; //$segmentjson[""].$segmentjson["ARRTIME"]."00";
                $format = "dmYHis";
                $dateArrival = DateTime::createFromFormat($format, $Seconded["BirthDay"]);
                $Seconded["BirthDay"] = $dateArrival->format("YmdHis");
            } catch (Exception $e) {
                $Seconded["BirthDay"] = "";
            }
            $Seconded["DocumentNumber"] = mb_substr($this->DTV($injson, ["PASS"]), 2);
            $Seconded["DocType"] = mb_substr($this->DTV($injson, ["PASS"]), 0, 2);
            $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
            $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);

            $service["Seconded"][] = $Seconded;

            $service["AmountServices"] = $service["pricecustomer"];

            $service["SegmentCount"] = count($service["Segments"]);
    
            if ($service["TypeOfTicket"] == "R") {
                //
                $service["price"] = -1*$service["price"];
                $service["amount"] = -1*$service["amount"];
                $service["amountVAT"] = -1*$service["amountVAT"];
                
                $service["pricecustomer"] = -1*$service["pricecustomer"];
                $service["amountclient"] = -1*$service["amountclient"];
                $service["amountVATcustomer"] = -1*$service["amountVATcustomer"];
    
                $service["VATAmount18"] = -1*$service["VATAmount18"];
                $service["AmountWithVAT18"] = -1*$service["AmountWithVAT18"];
                $service["AmountServices"] = -1*$service["AmountServices"];
            }
            
            $services[] = $service;

            unset($service["methods"]);
    
            
            $diff_summ = $service["amount"] - ($service["amountclient"] + $service["AmountWithVAT18"]);
            if ($diff_summ > 0) {
                //Сбор ГВА
                $price = $diff_summ;
                $seviceGVA = $this->CreateGVA($service, ["price" => $price, "amountVAT" => 0, "amount" => $price]);
                $services[] = $seviceGVA;
            }

            if ($service["AmountWithVAT18"] > 0) {
                //Сбор авиакомпании
                $price = $this->DTV($service, ["AmountWithVAT18"]);
                $vat = $this->DTV($service, ["VATAmount18"]);
                $seviceGVA = $this->CreateSA($service, ["price" => $price, "amountVAT" => $vat, "amount" => $price]);
                $services[] = $seviceGVA;
            }

            //000003519
        }


        $jsonv3["services"] = $services;

        return $jsonv3;
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
            $service["attachedto"] = $jsonv3sevice["Synh"];
            
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
            
            $services[] = $service;
        }
        
        if ($jsonv3sevice["AmountWithVAT18"] < 0) {
            //добавим услугу на 18%
            $service = $jsonv3sevice;
            
            $service["nomenclature"] = "СервисныеУслугиПеревозчикаВозврат";
            $service["ownerservice"] = $jsonv3sevice["Synh"];
            $service["ApplicationService"] = $jsonv3sevice["Synh"];
            $service["attachedto"] = $jsonv3sevice["Synh"];
            
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
            
            $services[] = $service;
        }
        
        
        if ($jsonv3sevice["AmountOfPenalty"] > 0) {
            //добавим услугу на 18%
            $service = $jsonv3sevice;
            
            $service["nomenclature"] = "СборПеревозчикаЗаВозврат";
            $service["ownerservice"] = $jsonv3sevice["Synh"];
            $service["ApplicationService"] = $jsonv3sevice["Synh"];
            //$service["attachedto"] = $jsonv3sevice["Synh"];
            
            $service["Synh"] = $service["nomenclature"] . $jsonv3sevice["Synh"];
            $service["TicketNumber"] = "";
            $service["TypeOfTicket"] = "";
            $service["TypeThisService"] = "Загруженная";
            
            $service["pricecustomer"] = $jsonv3sevice["AmountOfPenalty"];
            $service["amountclient"] = $jsonv3sevice["AmountOfPenalty"];
            $service["amountVATcustomer"] = $jsonv3sevice["AmountVATOfPenalty"];
            $service["VATratecustomer"] = 20;
            
            $service["price"] = $jsonv3sevice["AmountOfPenalty"];
            $service["amount"] = $jsonv3sevice["AmountOfPenalty"];
            $service["amountVAT"] = $jsonv3sevice["AmountVATOfPenalty"];
            $service["VATrate"] = 20;
            
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
            $service["attachedto"] = $jsonv3sevice["Synh"];
            
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
            
            $services[] = $service;
        }
        
        if ($jsonv3sevice["VendorFeeAmount"] > 0) {
            //добавим услугу сбор поставщика
            $service = $jsonv3sevice;
            
            $service["nomenclature"] = "СборПоставщика";
            $service["ownerservice"] = $jsonv3sevice["Synh"];
            $service["ApplicationService"] = $jsonv3sevice["Synh"];
            $service["attachedto"] = $jsonv3sevice["Synh"];
            
            $service["Synh"] = $service["nomenclature"] . $jsonv3sevice["Synh"];
            $service["TicketNumber"] = "";
            $service["TypeOfTicket"] = "";
            $service["TypeThisService"] = "Загруженная";
            
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
    
    private function v3train($xml)
    {
        $jsonv2 = $this->v2train($xml);
        
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
            
            $Type = 1;
            if ($inservice["TypeService"] == "Bus") {
                $Type = 2;
                $service["nomenclature"] = "БилетНаАвтобус";
                if ($service["TypeOfTicket"] == "R") {
                    $service["nomenclature"] = "ВозвратБилетаНаАвтобус";
                }
            } elseif ($inservice["TypeService"] == "Avia") {
                $Type = 2;
                $service["nomenclature"] = "Авиабилет";
                $service["Synh"] = $service["TypeOfTicket"] . substr($inservice["TicketNum"], 0, 3) . "-" . substr($inservice["TicketNum"], 3);
                if ($service["TypeOfTicket"] == "R") {
                    $service["nomenclature"] = "ВозвратАвиабилета";
                    $service["Synh"] = $service["TypeOfTicket"] . $inservice["TicketNum"];
                }
            } else {
                $service["nomenclature"] = "ЖДБилет";
                if ($service["TypeOfTicket"] == "R") {
                    $service["nomenclature"] = "ВозвратЖДБилета";
                }
            }
            
            
            $service["TicketNumber"] = $inservice["TicketNum"];
    
            $service["date"] = $inservice["CreateTime"];
            $service["manager"] = $inservice["Terminal"];
            
            
            $service["ServiceStartDate"] = $inservice["DepartTime"];
            $service["ServiceEndDate"] = $inservice["ArrivalTime"];
            
            $service["Depart"] = $service["ServiceStartDate"];
            $service["Arrival"] = $service["ServiceEndDate"];
    
            $service["AddressDeparture"] = $inservice["StationFrom"];
            $service["AddressDestination"] = $inservice["StationTo"];
            
            $service["PlaceDeparture"] = $inservice["StationFromCode"];
            $service["PlaceArrival"] = $inservice["StationToCode"];
            
            $serviceblank["ReservationNumber"] = $inservice["ReservationNumber"];
            
            if ($Type == 1) {
                $service["Route"] = $service["CityDeparture"] . " - " . $service["CityArrival"];
            } else {
                $service["Route"] = $service["AddressDeparture"] . " - " . $service["AddressDestination"];
            }
            
            
            $service["DepartureCode"] = $inservice["StationFromCode"];
            $service["ArrivalCode"] = $inservice["StationToCode"];
            
            
            $service["supplier"] = $this->supplier;//["INN" => "9717045555", "KPP" => "771401001", "Name" => "ИМ"];
            $service["Supplier"] = $service["supplier"];
            
            
            $service["TrainNumber"] = $inservice["TrainNum"];
            
            $service["price"] = (float)$inservice["Amount"];
            $service["amount"] = (float)$service["price"];
            
            $service["AmountWithVAT18"] = (float)$inservice["AmountWhithNDS"];
            $service["VATAmount18"] = (float)$inservice["AmountNDS"];
            
            $service["AmountOfPenalty"] = (float)$inservice["AmountOfPenalty"];
            $service["AmountVATOfPenalty"] = (float)$inservice["AmountVATOfPenalty"];
            
            $service["AmountServices"] = (float)$service["amount"] - (float)$service["AmountWithVAT18"];
            
            $service["pricecustomer"] = $service["AmountServices"];
            $service["amountclient"] = $service["AmountServices"];
            
            if ($service["TypeOfTicket"] == "R") {
                
                $service["price"] = -1 * ($service["price"] + $service["AmountOfPenalty"]);
                $service["amount"] = -1 * ($service["amount"] + $service["AmountOfPenalty"]);
                
                $service["AmountWithVAT18"] = -1 * $service["AmountWithVAT18"];
                $service["VATAmount18"] = -1 * $service["VATAmount18"];
                
                $service["AmountServices"] = -1 * ($service["AmountServices"] + $service["AmountOfPenalty"]);
                
                $service["pricecustomer"] = -1 * ($service["pricecustomer"] + $service["AmountOfPenalty"]);
                $service["amountclient"] = -1 * ($service["amountclient"] + $service["AmountOfPenalty"]);
            }
            
            $service["TravelTime"] = (int)$inservice["TravelTime"];
            $service["VendorFeeAmount"] = (int)$inservice["Profit"];
            
            
            $service["Place"] = trim($inservice["Place"]);
            $service["Wagon"] = trim($inservice["CarNum"]);
            
            $Secondeds = $this->DTV($inservice, "TravelPeoples"); //TravelPeaples
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
                
                
                $Seconded["DocumentNumber"] = $valueS["DocNum"];
                $Seconded["BirthDay"] = $this->DTV($valueS, ["BirthDay"], "", "Y-m-d\TH:i:s");
                $Seconded["DocType"] = $valueS["DocType"];
                
                $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
                $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);
                
                $service["Seconded"][] = $Seconded;
            }
            
            if ($service["TypeOfTicket"] == "R") {
                $service["TicketSales"] = $inservice["TicketNum"];
            }
            
            $services[] = $service;
            
            $addservices = [];
            $addservices = $this->v3addonservice($service);
            $services = array_merge($services, $addservices);
        }
        
        $jsonv3["services"] = $services;
    
        return $jsonv3;
    }
    

    private function GetSegmentsAEInfo($tiket, $services)
    {
        $segments = [];

        $min_date = strtotime("+5 years", time());
        $max_date = 0;

        $min_port = "";
        $max_port = "";

        $RouteShortened = [];
        $Route = [];

        $service = $this->get_empty_v3();

        foreach ($tiket->SEGMENTS->SEGMENT as $segment) {
            $segmentjson = $this->object2array($segment);

            $service = $this->get_empty_v3();

            $servicesegment = [];
            $servicesegment["nomenclature"] = "СегментАэроэкспресса";

            $servicesegment["price"] = (float)$segmentjson["FARE"];
            $servicesegment["amount"] = (float)$segmentjson["FARE"];
            $servicesegment["amountVAT"] = -1; //
            $servicesegment["VATrate"] = 0;

            $servicesegment["pricecustomer"] = $servicesegment["price"];
            $servicesegment["amountclient"] = $servicesegment["amount"];
            $servicesegment["amountVATcustomer"] = $servicesegment["amountVAT"];
            $servicesegment["VATratecustomer"] = $servicesegment["VATrate"];

            $DepartureCode = $segmentjson["DEPPORT"];
            $ArrivalCode = $segmentjson["ARRPORT"];

            $servicesegment["DepartureCode"] = $DepartureCode;
            $servicesegment["ArrivalCode"] = $ArrivalCode;

            $this->BuildRoute($RouteShortened, $servicesegment["DepartureCode"]);
            $this->BuildRoute($RouteShortened, $servicesegment["ArrivalCode"]);

            //Подключить КЕШ для IATA кодов и сокращенного маршрута
            $servicesegment["PlaceDeparture"] = trim($servicesegment["DepartureCode"]);
            $servicesegment["CityDeparture"] = trim($servicesegment["DepartureCode"]);
//            $res = $this->Catalogs->getCatalogItems("Propertys", ["value" => trim($servicesegment["DepartureCode"])]);
//            if ($res) {
//                $idplace = $res[0]["place"];
//                $res = $this->Catalogs->getCatalogItems("Places", ["id" => $idplace]);
//                if ($res) {
//                    $servicesegment["PlaceDeparture"] = $res[0]["place"];
//                    $findCity = true;
//                    do {
//                        if ($res[0]["type"] == "Город") $servicesegment["CityDeparture"] = $res[0]["place"];
//                        if (($res[0]["parent"] == 0) || ($res[0]["type"] == "Город")) $findCity = false;
//                        $res = $this->Catalogs->getCatalogItems("Places", ["id" => $res[0]["parent"]]);
//                    } while ($findCity);
//                }
//            }

            $servicesegment["PlaceArrival"] = trim($servicesegment["ArrivalCode"]);
            $servicesegment["CityArrival"] = trim($servicesegment["ArrivalCode"]);
//            $res = $this->Catalogs->getCatalogItems("Propertys", ["value" => trim($servicesegment["ArrivalCode"])]);
//            if ($res) {
//                $idplace = $res[0]["place"];
//                $res = $this->Catalogs->getCatalogItems("Places", ["id" => $idplace]);
//                if ($res) {
//                    $servicesegment["PlaceArrival"] = $res[0]["place"];
//                    $findCity = true;
//                    do {
//                        if ($res[0]["type"] == "Город") $servicesegment["CityArrival"] = $res[0]["place"];
//                        if (($res[0]["parent"] == 0) || ($res[0]["type"] == "Город")) $findCity = false;
//                        $res = $this->Catalogs->getCatalogItems("Places", ["id" => $res[0]["parent"]]);
//                    } while ($findCity);
//                }
//            }

            $this->BuildRoute($Route, $servicesegment["PlaceDeparture"]);
            $this->BuildRoute($Route, $servicesegment["PlaceArrival"]);

            //RouteShortened
            $servicesegment["TerminalDepartures"] = $this->DTV($segmentjson, ["TERM1"]);
            $servicesegment["TerminalArrivals"] = $this->DTV($segmentjson, ["TERM2"]);

            $servicesegment["SegmentFlight"] = $this->DTV($segmentjson, ["REIS"]);
            $servicesegment["Carrier"] = $this->DTV($segmentjson, ["CARRIER"]);


            $DEPDATE = $this->DTV($segmentjson, ["DEPDATE"]);
            $DEPTIME = $this->DTV($segmentjson, ["DEPTIME"], "0000");
            try {
                $servicesegment["Depart"] = $DEPDATE . $DEPTIME . "00";
                $format = "dmYHis";
                $dateDepart = DateTime::createFromFormat($format, $servicesegment["Depart"]);
                $servicesegment["Depart"] = $dateDepart->format("YmdHis");
            } catch (Exception $e) {
                $servicesegment["Depart"] = "";
            }

            if ($min_date > strtotime($servicesegment["Depart"])) {
                $min_date = strtotime($servicesegment["Depart"]);
                $max_date = $min_date;
                $min_port = $DepartureCode;
                $max_port = $ArrivalCode;
            }


            $servicesegment["ServiceStartDate"] = $servicesegment["Depart"];
            $servicesegment["ServiceEndDate"] = $servicesegment["Depart"];

            //$diff = strtotime($servicesegment["ServiceEndDate"]) - strtotime($servicesegment["ServiceStartDate"]);
            //$servicesegment["TravelTime"] = round(abs($diff) / 60);


            $servicesegment["supplier"] = $this->supplier;
            $servicesegment["Supplier"] = $this->supplier;

            $servicesegment["TicketClass"] = $this->DTV($segmentjson, ["CLASS"]);

            $service = array_merge($service, $servicesegment);

            $service["Synh"] = md5(json_encode($servicesegment, JSON_UNESCAPED_UNICODE));
            $service["MD5SourceFile"] = $service["Synh"];

            $segments[] = $service;
            //$segments[] = $service["Synh"]; //Это для создания списка такс
        }

        //print_r($service);
        $RouteStr = implode(" - ", $Route);
        $RouteShortenedStr = implode(" - ", $RouteShortened);

        return ["Segments" => $service, "Route" => $RouteStr, "RouteShortened" => $RouteShortenedStr, "Depart" => ["date" => $max_date, "port" => $max_port], "Arrival" => ["date" => $min_date, "port" => $min_port]];
    }

    private function v3aeroexpress($xml)
    {
        $services = [];

        foreach ($xml->TICKET as $tiket) {
            $injson = $this->object2array($tiket);
            $service = $this->get_empty_v3();

            $service["nomenclature"] = "Аэроэкспресс";
            $service["TicketNumber"] = $injson["TICKNUM"];

            $service["TypeOfTicket"] = "V";
            if ($injson["OPTYPE"] == "SALE") {
                $service["TypeOfTicket"] = "S";
            } elseif (substr($injson["OPTYPE"], 0, 1) == "E") {
                $service["TypeOfTicket"] = "E";
            } elseif (substr($injson["OPTYPE"], 0, 1) == "R") {
                $service["TypeOfTicket"] = "R";
            }
            $service["Synh"] = $service["TypeOfTicket"] . $service["TicketNumber"];

            /* СЕГМЕНТЫ */
            $Segments = $this->GetSegmentsAEInfo($tiket, $services);
            //print_r($Segments);
            //$service["Segments"] = $Segments["Segments"]; //Сегменты
            $service["RouteShortened"] = $Segments["RouteShortened"]; //Сегменты
            $service["Route"] = $Segments["Route"]; //Сегменты

            $service["Depart"] = date("YmdHis", $Segments["Arrival"]["date"]);
            $service["Arrival"] = date("YmdHis", $Segments["Depart"]["date"]);

            $date = $this->DTV($injson, ["DEALDATE"], "", "dmY");
            $service["date"] = $date;

            $service["ArrivalCode"] = $Segments["Arrival"]["port"];
            $service["DepartureCode"] = $Segments["Depart"]["port"];

            $service["ServiceStartDate"] = $service["Depart"];
            $service["ServiceEndDate"] = $service["Arrival"];


            $service["CityDeparture"] = $this->DTV($Segments, ["Segments", "CityDeparture"]);
            $service["CityArrival"] = $this->DTV($Segments, ["Segments", "CityArrival"]);

            $service["PlaceDeparture"] = $this->DTV($Segments, ["Segments", "PlaceDeparture"]);
            $service["PlaceArrival"] = $this->DTV($Segments, ["Segments", "PlaceArrival"]);

            $service["supplier"] = $this->supplier;
            $service["Supplier"] = $this->supplier;


            $fullprice = (float)$this->DTV($injson, ["FARE"]);
            $service["pricecustomer"] = $fullprice;
            $service["amountclient"] = $fullprice;
            if ($service["amountVATcustomer"] != 0) {
                $service["VATratecustomer"] = ($service["amountclient"] / $service["amountVAT"] * 10) - 100;
            }


            $service["price"] = $fullprice;
            $service["amount"] = $fullprice;
            $service["amountVAT"] = $service["amountVATcustomer"];
            $service["VATrate"] = $service["VATratecustomer"];

//            $Seconded = [];
//            $ars = explode(" ", $this->DTV($injson, ["NAME"]));
//            $Seconded["FirstName"] = $this->DTV($ars, [0]);
//            $Seconded["LastName"] = $this->DTV($injson, ["SURNAME"]);
//            $Seconded["SurName"] = $this->DTV($ars, [1]);
//            $Seconded["FirstNameLatin"] = "";
//            $Seconded["LastNameLatin"] = "";
//            $Seconded["SurNameLatin"] = "";
//            try {
//                $Seconded["BirthDay"] = $this->DTV($injson, ["BIRTH_DATE"]) . "000000"; //$segmentjson[""].$segmentjson["ARRTIME"]."00";
//                $format = "dmYHis";
//                $dateArrival = DateTime::createFromFormat($format, $Seconded["BirthDay"]);
//                $Seconded["BirthDay"] = $dateArrival->format("YmdHis");
//            } catch (Exception $e) {
//                $Seconded["BirthDay"] = "";
//            }
//            $Seconded["DocumentNumber"] = mb_substr($this->DTV($injson, ["PASS"]), 2);
//            $Seconded["DocType"] = mb_substr($this->DTV($injson, ["PASS"]), 0, 2);
//            $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
//            $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);
//
//            $service["Seconded"][] = $Seconded;

            $services[] = $service;
        }
        $jsonv3["services"] = $services;

        return $jsonv3;
    }

    private function v3emd($xml)
    {
        $services = [];

        foreach ($xml->TICKET as $tiket) {
            $injson = $this->object2array($tiket);

            $MCO_TYPE = $this->DTV($injson, ["MCO_TYPE"]);

            if ($MCO_TYPE != "") {
                $service = $this->get_empty_v3();

                $TicketNumber = $injson["BSONUM"];
                $service["TicketNumber"] = substr($TicketNumber, 0, 3) . "-" . substr($TicketNumber, 3);

                $date = $this->DTV($injson, ["DEALDATE"], "", "dmY");
                $service["date"] = $date;

                $service["supplier"] = $this->supplier;
                $service["Supplier"] = $this->supplier;

                $OPTYPE = $this->DTV($injson, ["OPTYPE"]);
                $service["TypeOfTicket"] = "V";
                if ($OPTYPE == "SALE") {
                    $service["TypeOfTicket"] = "S";
                } elseif (substr($OPTYPE, 0, 1) == "E") {
                    $service["TypeOfTicket"] = "E";
                } elseif (substr($OPTYPE, 0, 1) == "R") {
                    $service["TypeOfTicket"] = "R";
                }
                $service["Synh"] = $service["TypeOfTicket"] . substr($TicketNumber, 0, 3) . "-" . substr($TicketNumber, 3);

                $TRANS_TYPE = $this->DTV($injson, ["TRANS_TYPE"]);
                $TypeOfTicket = $service["TypeOfTicket"];
                if ($MCO_TYPE == "EXC_BAGG") {
                    $service["nomenclature"] = "ДополнительныйБагаж";
                } elseif (($MCO_TYPE == "PENALTY") && ($TRANS_TYPE == "EXCHANGE")) {
                    $service["nomenclature"] = "ШтрафЗаОбменАвиабилета";
                    $TypeOfTicket = "E";
                } elseif (($MCO_TYPE == "PENALTY") && ($TRANS_TYPE == "REFUND")) {
                    $service["nomenclature"] = "ШтрафЗаВозвратАвиабилета";
                    $TypeOfTicket = "R";
                } else {
                    $service["nomenclature"] = "ДополнительныеУслугиКАвиабилету";

                }

                $REASON = $this->DTV($injson, ["EMDCOUPONS", "EMDCOUPON", "REASON"]);
                $service["AdditionalDescription"] = $REASON;

                $fullprice = (float)$this->DTV($injson, ["FARE"]);
                $service["pricecustomer"] = $fullprice;
                $service["amountclient"] = $fullprice;
                if ($service["amountVATcustomer"] != 0) {
                    $service["VATratecustomer"] = ($service["amountclient"] / $service["amountVAT"] * 10) - 100;
                }


                $service["price"] = $fullprice;
                $service["amount"] = $fullprice;
                $service["amountVAT"] = $service["amountVATcustomer"];
                $service["VATrate"] = $service["VATratecustomer"];

                $Seconded = [];
                $ars = explode(" ", $this->DTV($injson, ["NAME"]));
                $Seconded["FirstName"] = $this->DTV($ars, [0]);
                $Seconded["LastName"] = $this->DTV($injson, ["SURNAME"]);
                $Seconded["SurName"] = $this->DTV($ars, [1]);
                $Seconded["FirstNameLatin"] = "";
                $Seconded["LastNameLatin"] = "";
                $Seconded["SurNameLatin"] = "";
                try {
                    $Seconded["BirthDay"] = $this->DTV($injson, ["BIRTH_DATE"]) . "000000"; //$segmentjson[""].$segmentjson["ARRTIME"]."00";
                    $format = "dmYHis";
                    $dateArrival = DateTime::createFromFormat($format, $Seconded["BirthDay"]);
                    $Seconded["BirthDay"] = $dateArrival->format("YmdHis");
                } catch (Exception $e) {
                    $Seconded["BirthDay"] = "";
                }
                $Seconded["DocumentNumber"] = mb_substr($this->DTV($injson, ["PASS"]), 2);
                $Seconded["DocType"] = mb_substr($this->DTV($injson, ["PASS"]), 0, 2);
                $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
                $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);

                $service["Seconded"][] = $Seconded;

                $TO_BSONUM = $this->DTV($injson, ["TO_BSONUM"]);
                if ($TO_BSONUM != "") {
                    $ApplicationService = $TypeOfTicket . substr($TO_BSONUM, 0, 3) . "-" . substr($TO_BSONUM, 3);
                    $service["ownerservice"] = $ApplicationService;
                    $service["ApplicationService"] = $ApplicationService;
                    $service["TypeThisService"] = "Загруженная";
                }

                $services[] = $service;
            }

        }

        $jsonv3["services"] = $services;

        return $jsonv3;
    }

    private function v3car($xml)
    {
        $services = [];

        foreach ($xml->TICKET as $tiket) {
            $injson = $this->object2array($tiket);

            $service = $this->get_empty_v3();

            $TicketNumber = $injson["BSONUM"];
            $service["TicketNumber"] = substr($TicketNumber, 0, 3) . "-" . substr($TicketNumber, 3);

            $date = $this->DTV($injson, ["DEALDATE"], "", "dmY");
            $service["date"] = $date;

            $service["supplier"] = $this->supplier;
            $service["Supplier"] = $this->supplier;

            $OPTYPE = $this->DTV($injson, ["OPTYPE"]);
            $service["TypeOfTicket"] = "V";
            if ($OPTYPE == "SALE") {
                $service["TypeOfTicket"] = "S";
            } elseif (substr($OPTYPE, 0, 1) == "E") {
                $service["TypeOfTicket"] = "E";
            } elseif (substr($OPTYPE, 0, 1) == "R") {
                $service["TypeOfTicket"] = "R";
            }
            $service["Synh"] = $service["TypeOfTicket"] . substr($TicketNumber, 0, 3) . "-" . substr($TicketNumber, 3);

            $TRANS_TYPE = $this->DTV($injson, ["TRANS_TYPE"]);
            $TypeOfTicket = $service["TypeOfTicket"];

            $service["nomenclature"] = "ПодачаАвтомобиля";

            $fullprice = (float)$this->DTV($injson, ["AMOUNT"]);
            $service["pricecustomer"] = $fullprice;
            $service["amountclient"] = $fullprice;
            if ($service["amountVATcustomer"] != 0) {
                $service["VATratecustomer"] = 110;
            }


            $service["price"] = $fullprice;
            $service["amount"] = $fullprice;
            $service["amountVAT"] = $service["amountVATcustomer"];
            $service["VATrate"] = $service["VATratecustomer"];

            $Seconded = [];
            $ars = explode(" ", $this->DTV($injson, ["FIO"]));
            $Seconded["FirstName"] = $this->DTV($ars, [1]);
            $Seconded["LastName"] = $this->DTV($ars, [0]);
            $Seconded["SurName"] = $this->DTV($ars, [2]);
            $Seconded["FirstNameLatin"] = "";
            $Seconded["LastNameLatin"] = "";
            $Seconded["SurNameLatin"] = "";
            try {
                $Seconded["BirthDay"] = $this->DTV($injson, ["BIRTH_DATE"]) . "000000"; //$segmentjson[""].$segmentjson["ARRTIME"]."00";
                $format = "dmYHis";
                $dateArrival = DateTime::createFromFormat($format, $Seconded["BirthDay"]);
                $Seconded["BirthDay"] = $dateArrival->format("YmdHis");
            } catch (Exception $e) {
                $Seconded["BirthDay"] = "";
            }
            $Seconded["DocumentNumber"] = mb_substr($this->DTV($injson, ["PASS"]), 2);
            $Seconded["DocType"] = mb_substr($this->DTV($injson, ["PASS"]), 0, 2);
            $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
            $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);

            $service["Seconded"][] = $Seconded;

            $TO_BSONUM = $this->DTV($injson, ["TO_BSONUM"]);
            if ($TO_BSONUM != "") {
                $ApplicationService = $TypeOfTicket . substr($TO_BSONUM, 0, 3) . "-" . substr($TO_BSONUM, 3);
                $service["ownerservice"] = $ApplicationService;
                $service["ApplicationService"] = $ApplicationService;
                $service["TypeThisService"] = "Загруженная";
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


                if ($this->debugclass) {
                    //print_r($this->phpinput);
                } else {
                    $Parser = new Parser($this->metod);
                    $Parser->SetUseParser("Sirena", md5($text), $this->Auth->getuserid());
                }


                $textxml = $this->phpinput;
                $xml = simplexml_load_string($textxml);
                $json = $this->object2array($xml);

                $v3 = [];

                $type = $this->DTV($json, ["TICKET", "TYPE"]);

                if ($type == "ETICKET") {
                    if ($this->debugclass) {
                        //print_r("ETICKET");
                    }
                    $v3 = $this->v3air($xml);
                } elseif ($type == "TRAIN_TICKET") {
                    $v3 = $this->v3train($xml);
                } elseif ($type == "AE_TICKET") {
                    $v3 = $this->v3aeroexpress($xml);
                } elseif ($type == "EMD") {
                    $v3 = $this->v3emd($xml);
                } elseif ($type == "EINSURCAR") {
                    $v3 = $this->v3car($xml);
                }

                $result = ["result" => true, "json" => $json, "jsonv3" => $v3];
            } else {
                $result["error"] = "Not for this version";
            }
        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }

    private function v2train($xml)
    {

        //$xml = simplexml_load_string($content);
        //var_dump($xml);
        
        $jsonfull = $this->object2array($xml);


        //ГГГГММДДччммсс

        $json = $jsonfull["TICKET"];
        $service = [];

        //
        $service["Synh"] = "";
        if ($json["TRANS_TYPE"] == "SALE") {
            $service["Type"] = "1";
            $service["RStatus"] = 0;
        } else {
            $service["Type"] = "0";
            $service["RStatus"] = 1;
        }

        $service["TrainNum"] = "";
        $service["CreateTime"] = $this->DTV([$json["DEALDATE"]." ".$json["DEALTIME"]],[0],"","dmY hi");
        
        $service["BookingTime"] = $json["BookingTime"];
        $service["ConfirmTime"] = $json["ConfirmTime"];
        $service["ConfirmTimeLimit"] = $json["ConfirmTimeLimit"];

        $service["CarNum"] = "";
        $service["CarType"] = "";
        $service["DepartTime"] = "";
        $service["Email"] = $json["Email"];
        $service["ServiceClass"] = "";
        $service["StationFrom"] = "";
        $service["StationTo"] = "";
        $service["StationFromCode"] = "";//$StationFromCode;
        $service["StationToCode"] = "";//$StationToCode;


        $service["ArrivalTime"] = $json["ArrivalTime"];
        $service["GenderClass"] = $json["GenderClass"];


        $service["Carrier"] = "";//$json["Carrier"];
        $service["CarrierInn"] = $json["CARRIER_VAT_ID"];
        $service["TimeDescription"] = ""; //ПРЕДВАРИТЕЛЬНЫЙ ДОСМОТР НА ВОКЗАЛЕ. ВРЕМЯ ОТПР И ПРИБ МОСКОВСКОЕ
        $service["GroupDirection"] = 0;//$json["GroupDirection"];
        $service["Terminal"] = $json["DEAL"]["@attributes"]["disp"];
        $service["ExpierSetEr"] = "";//$json["ExpierSetEr"];
        $service["Domain"] = "";//$json["Domain"];
        $service["PayTypeID"] = "";//CASH
        $service["IsInternational"] = 0;//0
        $service["EmdNum"] = $json["EMD_NUM"];//0


        if (isset($json["SEGMENTS"]["SEGMENT"]["SEGNO"])) {
            $blank = [];
            $blank[] = $json["SEGMENTS"]["SEGMENT"];
        } else {
            $blank = $json["SEGMENTS"]["SEGMENT"];
        }

        if (isset($json["PASSENGERS"]["PASSENGER"]["PASSNO"])) {
            $Passenger = [];
            $Passenger[] = $json["PASSENGERS"]["PASSENGER"];
        } else {
            $Passenger = $json["PASSENGERS"]["PASSENGER"];
        }

        $services = [];

        foreach ($blank as $value) {
            $serviceblank = $service;

            $serviceblank["TrainNum"] = $value["TRAIN"];
            $serviceblank["CarNum"] = $value["COACH"]["@attributes"]["num"];
            $serviceblank["CarType"] = $value["COACH"]["@attributes"]["type"];
            $serviceblank["ServiceClass"] = $value["CLASS"];
            $serviceblank["StationFrom"] = $value["DEPPORT"];
            $serviceblank["StationTo"] = $value["ARRPORT"];
            $serviceblank["StationFromCode"] = "";//$StationFromCode;
            $serviceblank["StationToCode"] = "";//$StationToCode;

            $serviceblank["DepartTime"] = $value["DEPDATE"] . $value["DEPTIME"] . "00"; //!!!!
            $serviceblank["ArrivalTime"] = $value["ARRDATE"] . $value["ARRTIME"] . "00"; //!!!!!

            $format = "dmYHis";
            $date = DateTime::createFromFormat($format, $serviceblank["ArrivalTime"]);
            $serviceblank["ArrivalTime"] = $date->format("YmdHis"); //!!!!!

            $date = DateTime::createFromFormat($format, $serviceblank["DepartTime"]);
            $serviceblank["DepartTime"] = $date->format("YmdHis"); //!!!!

            $serviceblank["RegTime"] = $value["RegTime"];

            $serviceblank["ID"] = $value["@attributes"]["ID"];
            $serviceblank["RetFlag"] = $value["RetFlag"];


            $SERVICES = $this->object2array($xml->TICKET->SERVICES->attributes());
            $vat_amount = $SERVICES["@attributes"]["vat_amount"];

            $serviceblank["Amount"] = $json["TOTAL"];


            $serviceblank["AmountWhithNDS"] = round($vat_amount / 18 * 118, 0);
            $serviceblank["ServiceWhithNDS"] = round($vat_amount / 18 * 118, 0);

            $serviceblank["AmountNDS"] = $vat_amount;
            $serviceblank["ServiceNDS"] = $vat_amount;
            $serviceblank["ReservedSeatAmount"] = $value["ReservedSeatAmount"];
            $serviceblank["TariffRateNds"] = $value["TariffRateNds"];
            $serviceblank["ServiceRateNds"] = 18;
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
            $serviceblank["TariffType"] = $json["FARETYPE"];
            $serviceblank["PassengerCard"] = $value["PassengerCard"];
            $serviceblank["TicketNum"] = $json["TICKNUM"];
            $serviceblank["RemoteCheckIn"] = $value["RemoteCheckIn"];
            $serviceblank["PrintFlag"] = $value["PrintFlag"];
            $serviceblank["RzhdStatus"] = $value["RzhdStatus"];

            $serviceblank["TicketToken"] = $json["ORDER_NUM"];

            $serviceblank["Place"] = $value["SEATS"]["SEAT"];

            $SEAT = $this->object2array($xml->TICKET->SEGMENTS->SEGMENT->SEATS->SEAT->attributes());
            $Placetier = $SEAT["@attributes"]["tier"];
            $serviceblank["Placetier"] = $Placetier;

            $serviceblank["Synh"] = "R";
            if ($service["RStatus"] == 0) {
                $serviceblank["Synh"] = "S";
            }
            $serviceblank["Synh"] .= substr($serviceblank["TicketNum"], 0, 3) . "-" . substr($serviceblank["TicketNum"], 3);

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
                    $TravelPeaple["DocNum"] = $valuep["PASS"];
                    $TravelPeaple["Name"] = $valuep["SURNAME"] . " " . $valuep["NAME"];
                    if ($valuep["GENDER"] == "M") {
                        $TravelPeaple["Sex"] = "M";
                    } else {
                        $TravelPeaple["Sex"] = "F";
                    }
                    $TravelPeaple["BirthDay"] = $valuep["BirthDay"];
                    $TravelPeaples[] = $TravelPeaple;
                }
            }

            $serviceblank["TravelPeoples"] = $TravelPeaples;

            $services[] = $serviceblank;
        }

        $res["services"] = $services;

        return $res;
    }

}
