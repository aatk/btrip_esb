<?php

class Portbilet extends ex_classlite
{
    private $metod;
    private $gds;
    private $classname;

    private $Auth;

    private $supplier;
    private $alltaxs;
    private $allsegments;



    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        parent::__construct();
        $this->metod = $metod;

        if (isset($this->POST["connector"])) {
            $this->gds = $this->POST;
        } else {
            $this->gds = $_SESSION["i4b"][mb_strtolower($this->classname)];
        }

        $this->debugclass = $debug;
        $this->Auth = new Auth();

        $this->supplier = ["INN" => "7731205077", "KPP" => "771601001", "Name" => "VIPSevice"];
    }


    /*
     *          Список заказов
     */

    public function loadorders($param1)
    {

        $result = ["result" => false];

        if ($this->Auth->userauth()) {
            //if (true) {

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


    /*
     *          Информация по заказу
     */

    private function AddPassanger($jsonv)
    {

        $Secondeds = [];

        $passagers = $passager = $this->DTV($jsonv, ["passengers", "passenger"]);
        if (isset($passager["@attributes"])) {
            $passagers = [];
            $passagers[] = $passager;
        }
        foreach ($passagers as $passager) {

            $psgr_id = $this->DTV($passager, ["@attributes", "psgr_id"]);

            $FirstName = ucfirst(strtolower($this->DTV($passager, ["@attributes", "name"])));
            $LastName = ucfirst(strtolower($this->DTV($passager, ["@attributes", "first_name"])));
            $SurName = ucfirst(strtolower($this->DTV($passager, ["@attributes", "middle_name"])));

            $Seconded = [];
            $Seconded["psgrid"] = $psgr_id;

            $Seconded["FirstName"] = "";
            $Seconded["LastName"] = "";
            $Seconded["SurName"] = "";
            $Seconded["FirstNameLatin"] = "";
            $Seconded["LastNameLatin"] = "";
            $Seconded["SurNameLatin"] = "";

            //if (($FirstName[0] >="А") && ($FirstName[0] <= "Я")) {
            //Русская раскладка
            $Seconded["FirstName"] = $FirstName;
            $Seconded["LastName"] = $LastName;
            $Seconded["SurName"] = $SurName;
            //} else {
            $Seconded["FirstNameLatin"] = $FirstName;
            $Seconded["LastNameLatin"] = $LastName;
            $Seconded["SurNameLatin"] = $SurName;
            //}

            $Seconded["DocumentNumber"] = $this->DTV($passager, ["@attributes", "doc_number"]);
            $Seconded["DocType"] = "";

            $Seconded["BirthDay"] = $this->DTV($passager, ["@attributes", "birth_date"], "", "Y-m-d");// . " 000000";

            $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
            $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);

            $Secondeds[] = $Seconded;
        }
        //print_r($Secondeds);

        return $Secondeds;
    }

    private function GetReservations($jsonv)
    {

        $result = [];

        $reservations = $reservation = $this->DTV($jsonv, ["reservations", "reservation"]);
        if (isset($reservation["@attributes"])) {
            $reservations = [];
            $reservations[] = $reservation;
        }
        foreach ($reservations as $reservation) {
            $id = $this->DTV($reservation, ["@attributes", "rsrv_id"]);

            $res = [];
            $res["ReservationNumber"] = $this->DTV($reservation, ["@attributes", "rloc"]);
            $res["System"] = $this->DTV($reservation, ["@attributes", "crs"]);

            $result[$id] = $res;
        }

        return $result;
    }

    private function GetTravelDoc($jsonv)
    {
        //travel_docs
        $result = [];

        $travel_docs = $travel_doc = $this->DTV($jsonv, ["travel_docs", "travel_doc"]);
        if (!isset($travel_doc[0])) {
            $travel_docs = [];
            $travel_docs[] = $travel_doc;
        }
        //print_r($travel_docs);
        foreach ($travel_docs as $intravel_docs) {
            foreach ($intravel_docs as $key => $travel_doc) {
                //print_r($travel_doc);
                if ($key == "air_ticket_doc") {
                    //Данные по Авиабилетам
                    $id = $this->DTV($travel_doc, ["@attributes", "prod_id"]);
                    $tkt_number = trim($this->DTV($travel_doc, ["@attributes", "tkt_number"]));
                    $tkt_number = str_replace(" ", "-", $tkt_number);

                    $exch_tkt = trim($this->DTV($travel_doc, ["@attributes", "exch_tkt"]));
                    $exch_tkt = str_replace(" ", "-", $exch_tkt);
                    //exch_tkt="555 2880960399"

                    $psgr_id = trim($this->DTV($travel_doc, ["@attributes", "psgr_id"]));
                    $tkt_oper = trim($this->DTV($travel_doc, ["@attributes", "tkt_oper"]));

                    $result[$id] = ["TicketNumber" => $tkt_number, "ExchengeTNumber" => $exch_tkt, "psgr_id" => $psgr_id, "tkt_oper" => $tkt_oper];

                } elseif ($key == "ral_ticket_doc") {
                    $id = $this->DTV($travel_doc, ["@attributes", "prod_id"]);
                    $tkt_number = trim($this->DTV($travel_doc, ["@attributes", "tkt_number"]));
                    $tkt_number = str_replace(" ", "-", $tkt_number);

                    $tkt_oper = trim($this->DTV($travel_doc, ["@attributes", "tkt_oper"]));

                    $tkt_date = trim($this->DTV($travel_doc, ["@attributes", "tkt_date"]));
                    if ($tkt_date != "") {
                        try {
                            $format = "Y-m-d H:i:s";
                            $tkt_date = DateTime::createFromFormat($format, $tkt_date);
                            $tkt_date = $tkt_date->format("YmdHis"); //!!!!!
                        } catch (Exception $e) {
                            $tkt_date = "";
                        }
                    }

                    $psgr_id = trim($this->DTV($travel_doc, ["@attributes", "psgr_id"]));

                    $result[$id] = ["TicketNumber" => $tkt_number, "tkt_oper" => $tkt_oper, "tkt_date" => $tkt_date, "psgr_id" => $psgr_id];
                }
            }
        }

        return $result;
    }


    /*
     *          Авиабилет
     */

    private function tax($segtax)
    {

        $service = $this->get_empty_v3();
        $service["nomenclature"] = "ТаксыАвиабилета";

        $vat_rate = 0;
        $vat_amount = 0;
        $vat_price = 0;
        $vat_amount_full = 0;

        $servicetax = [];
        $servicetax["nomenclature"] = "ТаксыАвиабилета";
        $servicetax["NameFees"] = trim($this->DTV($segtax, ["@attributes", "code"]));


        $servicetax["price"] = (float)$this->DTV($segtax, ["@attributes", "amount"]);
        $servicetax["amount"] = $servicetax["price"];
        $servicetax["amountVAT"] = 0;
        $servicetax["VATrate"] = -1;

        $servicetax["pricecustomer"] = $servicetax["price"];
        $servicetax["amountclient"] = $servicetax["price"];
        $servicetax["VATratecustomer"] = $servicetax["VATrate"];
        $servicetax["amountVATcustomer"] = $servicetax["amountVAT"];

        $servicetax["supplier"] = $this->supplier;
        $servicetax["Supplier"] = $this->supplier;

        $service = array_merge($service, $servicetax);


        $service["Synh"] = md5(json_encode($servicetax, JSON_UNESCAPED_UNICODE));
        $service["MD5SourceFile"] = $service["Synh"];
        $service["date"] = date("YmdHis", time());

        if (!in_array($service["Synh"], $this->alltaxs)) {
            $this->alltaxs[] = $service["Synh"];
            $services[] = $service;
        }

        //$fees[] = $service["Synh"]; //Это для создания списка такс

        return ["service" => $service, "vatinfo" => ["Rate" => $vat_rate, "Amount" => $vat_amount_full, "Price" => $vat_price]];
    }

    private function segmentv3($info)
    {
        $res = [];

        $taxs = [];

        $service = $this->get_empty_v3();
        $service["nomenclature"] = "СегментАвиабилета";
        $service["supplier"] = $this->supplier;
        $service["Supplier"] = $this->supplier;

        //print_r($info);
        $service["Carrier"] = $this->DTV($info, ["@attributes", "carrier"]);

        $ArrivalCode = $this->DTV($info, ["@attributes", "arrival_airport"]);
        $service["ArrivalCode"] = $ArrivalCode;

        $DepartureCode = $this->DTV($info, ["@attributes", "departure_airport"]);
        $service["DepartureCode"] = $DepartureCode;


        $service["PlaceDeparture"] = $DepartureCode;
        $service["CityDeparture"] = $DepartureCode;
//        $res = $this->Catalogs->getCatalogItems("Propertys", ["value" => $DepartureCode]);
//        if ($res) {
//            $idplace = $res[0]["place"];
//            $res = $this->Catalogs->getCatalogItems("Places", ["id" => $idplace]);
//            if ($res) {
//                $service["PlaceDeparture"] = $res[0]["place"];
//
//                $findCity = true;
//                do {
//                    if ($res[0]["type"] == "Город") $service["CityDeparture"] = $res[0]["place"];
//                    if (($res[0]["parent"] == 0) || ($res[0]["type"] == "Город")) $findCity = false;
//                    $res = $this->Catalogs->getCatalogItems("Places", ["id" => $res[0]["parent"]]);
//                } while ($findCity);
//            }
//        }


        $service["PlaceArrival"] = $ArrivalCode;
        $service["CityArrival"] = $ArrivalCode;
//        $res = $this->Catalogs->getCatalogItems("Propertys", ["value" => $ArrivalCode]);
//        if ($res) {
//            $idplace = $res[0]["place"];
//
//            $res = $this->Catalogs->getCatalogItems("Places", ["id" => $idplace]);
//            if ($res) {
//                $service["PlaceArrival"] = $res[0]["place"];
//
//                $findCity = true;
//                do {
//                    if ($res[0]["type"] == "Город") $service["CityArrival"] = $res[0]["place"];
//                    if (($res[0]["parent"] == 0) || ($res[0]["type"] == "Город")) $findCity = false;
//                    $res = $this->Catalogs->getCatalogItems("Places", ["id" => $res[0]["parent"]]);
//                } while ($findCity);
//            }
//        }

        $service["Depart"] = $this->DTV($info, ["@attributes", "departure_datetime"], "", "Y-m-d H:i:s");
        $service["Arrival"] = $this->DTV($info, ["@attributes", "arrival_datetime", "", "Y-m-d H:i:s"]);

        $service["ServiceStartDate"] = $service["Depart"];
        $service["ServiceEndDate"] = $service["Arrival"];

        $diff = strtotime($service["ServiceEndDate"]) - strtotime($service["ServiceStartDate"]);
        $service["TravelTime"] = round(abs($diff) / 60);

        $service["FareBases"] = $this->DTV($info, ["@attributes", "fare_basis"]);

        $service["Synh"] = md5(json_encode($service, JSON_UNESCAPED_UNICODE));
        $service["MD5SourceFile"] = $service["Synh"];

        $segments[] = $service["Synh"]; //Это для создания списка такс

        $service["date"] = $service["ServiceStartDate"];



        if (isset($info["air_tax"])) {
            $air_taxs = $air_tax = $this->DTV($info, ["air_tax"], []);
            if (isset($air_tax["@attributes"])) {
                $air_taxs = [];
                $air_taxs[] = $air_tax;
            }
            foreach ($air_taxs as $tax) {
                $taxinfo = $this->tax($tax);
                $taxs[] = $taxinfo["service"];
            }
        }

        return ["service" => $service, "taxs" => $taxs];
    }

    private function BuildRoute(&$Route, $Whot)
    {
        $lastWhot = $Route[count($Route) - 1];
        if ($lastWhot != $Whot) {
            $Route[] = $Whot;
        }
    }

    private function airv3($air, &$service, $traveldocs)
    {

        $services = [];

        $service["prodid"] = $this->DTV($air, ["@attributes", "prod_id"]);

        //$traveldocs
        $service["nomenclature"] = "Авиабилет";
        $service["TypeOfTicket"] = "S";

        if (isset($traveldocs[$service["prodid"]])) {
            $td = $traveldocs[$service["prodid"]];
            if ($td["tkt_oper"] == "REF") {

                //После 01/01/2020 портбилет начал возвращать возвраты с кодом перевозчика и все возвраты стали падать как Штрафы
                //поэтому поменяли условие с (strpos($td["TicketNumber"], "-") > 0) - Штраф наоборот на "=="
                //Ждем пример со штрафом

                //Либо штраф за обмен, либо возврат
                if (strpos($td["TicketNumber"], "-") == 0) {
                    //Это штраф
                    $service["nomenclature"] = "ШтрафЗаОбменАвиабилета";
                    $service["TypeOfTicket"] = $service["nomenclature"]."S";

                    $supplier = $this->DTV($air, ["@attributes", "supplier"]);
                    $service["TicketNumber"] = $td["TicketNumber"];

                    //Найдем обмененный билет
                    foreach ($traveldocs as $key => $traveldoc) {
                        if ($traveldoc["ExchengeTNumber"] == $td["TicketNumber"]) {
                            $service["ownerservice"] = "E".$supplier."-".$traveldoc["TicketNumber"];
                            $service["ApplicationService"] = "E".$supplier."-".$traveldoc["TicketNumber"];

                            $service["TypeThisService"] = "Загруженная";

                        }
                    }

                } else {
                    //Это возврат
                    $service["TypeOfTicket"] = "R";
                    $service["nomenclature"] = "ВозвратАвиабилета";

                    //$supplier = $this->DTV($air, ["@attributes", "supplier"]);
                    //$service["TicketNumber"] = $supplier."-".$td["TicketNumber"];

                    //Вот здесь еще допилили
                    $service["TicketNumber"] = $td["TicketNumber"];
                    $service["TicketSales"] = $td["TicketNumber"];  //$supplier."-".

                }
            } else {
                if ($td["ExchengeTNumber"] == "") {
                    //Просто билет
                    $service["TicketNumber"] = $this->DTV($td, ["TicketNumber"]);
                } else {
                    //Обмен
                    $service["TypeOfTicket"] = "E";
                    $service["nomenclature"] = "ОбменАвиабилета";
                    $supplier = $this->DTV($air, ["@attributes", "supplier"]);
                    $service["TicketNumber"] = $supplier."-".$td["TicketNumber"];
                    $service["TicketSales"] = $td["ExchengeTNumber"];
                }
            }
        }

        $service["Synh"] = $service["TypeOfTicket"] . $service["TicketNumber"];

        $service["supplier"] = $this->supplier;
        $service["Supplier"] = $this->supplier;

        $service["TariffAmount"] = (float)($this->DTV($air, ["@attributes", "fare"]));
        $service["TaxAmount"] = (float)$this->DTV($air, ["@attributes", "taxes"]);

        $service["price"] = $service["TariffAmount"] + $service["TaxAmount"];
        $service["amount"] = $service["price"];

        $service["pricecustomer"] = $service["price"];
        $service["VATratecustomer"] = $service["VATrate"];
        $service["amountVATcustomer"] = $service["VATAmount"];
        $service["amountclient"] = $service["amount"];

        $service["AmountServices"] = $service["pricecustomer"];

        if (($service["TypeOfTicket"] == "R") || ($service["nomenclature"] == "ШтрафЗаОбменАвиабилета")) {
            $service["price"] = -1*$service["price"];
            $service["amount"] = -1*$service["amount"];

            $service["pricecustomer"] = -1*$service["pricecustomer"];
            $service["amountVATcustomer"] = -1*$service["amountVATcustomer"];
            $service["amountclient"] = -1*$service["amountclient"];

            $service["AmountServices"] = -1*$service["AmountServices"];
        }

        $min_date = strtotime("+5 years", time());
        $max_date = 0;

        $min_port = "";
        $max_port = "";

        $min_place = "";
        $max_place = "";

        $RouteShortened = [];
        $Route = [];

        $Segments = [];
        $fee = [];

        $air_segs = $air_seg = $this->DTV($air, ["air_seg"]);
        if (isset($air_seg["@attributes"])) {
            $air_segs = [];
            $air_segs[] = $air_seg;
        }

        foreach ($air_segs as $value) {

            $seginfo = $this->segmentv3($value);

            $segmentservices = $seginfo["service"];
            $segmenttax = $seginfo["taxs"];

            $Segments[] = $segmentservices["Synh"];


            foreach ($segmenttax as $srfee) {
                $fee[] = $srfee["Synh"];
            }


            $Whot = $segmentservices["DepartureCode"];
            $this->BuildRoute($RouteShortened, $Whot);
            $Whot = $segmentservices["ArrivalCode"];
            $this->BuildRoute($RouteShortened, $Whot);

            $Whot = $segmentservices["CityDeparture"];
            $this->BuildRoute($Route, $Whot);
            $Whot = $segmentservices["CityArrival"];
            $this->BuildRoute($Route, $Whot);


            if ($segmentservices["Depart"] != "") {
                $nowtime = strtotime($segmentservices["Depart"]);
                if ($nowtime > $max_date) {
                    $max_date = $nowtime;

                    $max_port = $segmentservices["DepartureCode"];
                    $max_place = $segmentservices["CityDeparture"];
                }
                if ($nowtime < $min_date) {
                    $min_date = $nowtime;

                    $min_port = $segmentservices["DepartureCode"];
                    $min_place = $segmentservices["CityDeparture"];
                }
            }

            if ($segmentservices["Arrival"] != "") {
                $nowtime = strtotime($segmentservices["Arrival"]);
                if ($nowtime > $max_date) {
                    $max_date = $nowtime;

                    $max_port = $segmentservices["ArrivalCode"];
                    $max_place = $segmentservices["CityArrival"];
                }
                if ($nowtime < $min_date) {
                    $min_date = $nowtime;

                    $min_port = $segmentservices["ArrivalCode"];
                    $min_place = $segmentservices["CityArrival"];
                }
            }


            $services = array_merge($services, $segmenttax);
            $services[] = $segmentservices;
        }



        $service["Route"] = implode(" - ", $Route);
        $service["RouteShortened"] = implode(" - ", $RouteShortened);

        $service["ArrivalCode"] = $max_port;
        $service["DepartureCode"] = $min_port;

        $service["CityArrival"] = $max_place;
        $service["CityDeparture"] = $min_place;

        $service["ServiceStartDate"] = date("YmdHis", $min_date);
        $service["ServiceEndDate"] = date("YmdHis", $max_date);

        $diff = strtotime($service["ServiceEndDate"]) - strtotime($service["ServiceStartDate"]);
        $service["TravelTime"] = round(abs($diff) / 60 / 60);

        $service["Segments"] = $Segments; //Сегменты
        $service["Fees"] = $fee;

        //$services[] = $service;

        return $services;
    }


    /*
     *          Сбор поставщика
     */

    private function servicev3($sbor, &$service)
    {

        $service["prodid"] = $this->DTV($sbor, ["@attributes", "main_ticket_prod_id"]);

        $service["nomenclature"] = "СборПоставщика";
        $service["supplier"] = $this->supplier;
        $service["Supplier"] = $this->supplier;

        $service["price"] = (float)$this->DTV($sbor, ["@attributes", "price"]);
        $service["amount"] = $service["price"];

        $service["pricecustomer"] = $service["price"];
        $service["VATratecustomer"] = $service["VATrate"];
        $service["amountVATcustomer"] = $service["VATAmount"];
        $service["amountclient"] = $service["amount"];

        $service["AmountServices"] = $service["pricecustomer"];

        return $service;
    }


    /*
     *          ЖД Билет
     */

//    private function SendToCache($data)
//    {
//        $result = ["result" => false];
//        $res = $this->get("ufsv3_cache_geocode", ["id"], ["query" => $data["query"]]);
//        if (!$res) {
//            $data["date"] = date("Y-m-d H:i:s", time());
//            $this->insert("ufsv3_cache_geocode", $data);
//            $result = ["result" => true];
//        }
//
//        return $result;
//    }
//
//    private function GetFromCache($text)
//    {
//        $result = ["result" => false];
//        $res = $this->get("ufsv3_cache_geocode", "*", ["query" => mb_strtolower($text)]);
//        if ($res) {
//            $result = ["result" => true, "line" => $res];
//        }
//
//        return $result;
//    }

//    public function GetStationFromText($params)
//    {
//        $result = ["result" => false];
//
//        $geoaddress = "";
//        $station = "";
//        $description = "";
//        $name = "";
//        $country = "";
//
//        $kind = $params[1];
//        $address = $params[2];
//        $address = str_replace(".", "", $address);
//        $addressword = explode(" ", $address);
//
//        $yandexcache = $this->GetFromCache($address);
//        $result = $yandexcache["line"];
//        if (!$yandexcache["result"]) {
//            $json = $this->Yandex->GetYandexObject($address);
//
//            $featureMembers = $this->DTV($json, ["response", "GeoObjectCollection", "featureMember"]);
//
//            $findGeoObject = $this->DTV($featureMembers, [0, "GeoObject"]);
//            $findkind = $this->DTV($findGeoObject, ["metaDataProperty", "GeocoderMetaData", "kind"]);
//
//            $stationname = "";
//            $maxvesfinds = 0;
//            $maxwordsfinds = 0;
//            $maxfindObject = null;
//            $maxfindkind = "";
//            foreach ($featureMembers as $featureMember) {
//                $GeoObject = $this->DTV($featureMember, ["GeoObject"]);
//
//                $yandexkind = $this->DTV($GeoObject, ["metaDataProperty", "GeocoderMetaData", "kind"]);
//                if ($kind == $yandexkind) {
//
//
//                    $findGeoObject = $GeoObject;
//                    $findkind = $yandexkind;
//                    $station = $this->DTV($findGeoObject, ["name"]);
//
//                    $ves = 0;
//                    $findword = 0;
//                    foreach ($addressword as $word) {
//                        if (mb_strpos(mb_strtolower($station), mb_strtolower($word)) !== false) {
//                            $findword += 1;
//                            $ves += strlen($word);
//                        }
//                    }
//
//                    if ($maxvesfinds < $ves) {
//                        $maxfindObject = $findGeoObject;
//                        $maxvesfinds = $ves;
//                        $stationname = $this->DTV($findGeoObject, ["name"]);
//                    }
//
//                    if ($findword == count($addressword)) {
//                        break;
//                    }
//                }
//            }
//
//            //print_r($maxfindObject);
//            if ($maxfindObject != null) {
//                $findkind = $maxfindkind;
//
//
//                //Найдем населенный пунк по геолокации
//                $geoaddress = $this->DTV($maxfindObject, ["Point", "pos"]);
//                $localitykind = "locality";
//                $geojson = $this->Yandex->GetYandexObject($geoaddress);
//
//                $localityMembers = $this->DTV($geojson, ["response", "GeoObjectCollection", "featureMember"]);
//                $localityGeoObject = $this->DTV($localityMembers, [0, "GeoObject"]);
//
//                //print_r($localityGeoObject);
//
//                $country = $this->Yandex->GetConmonentName($localityGeoObject, "country");
//                $name = $this->Yandex->GetConmonentName($localityGeoObject, "locality");
//                $description = $this->DTV($localityGeoObject, ["description"]);
//            }
//
//
//            if ($findkind != $kind) {
//                //Вытащим только локацию
//                //print_r($maxfindObject);
//                $findGeoObject = $maxfindObject;
//                $description = $this->DTV($findGeoObject, ["description"]);
//
//                $country = $this->Yandex->GetConmonentName($findGeoObject, "country");
//                if ($name == "") {
//                    $name = $this->Yandex->GetConmonentName($findGeoObject, "locality");
//                }
//                $geoaddress = $this->DTV($findGeoObject, ["Point", "pos"]);
//            }
//
//            $result = [];
//            $result["query"] = mb_strtolower($address);
//            if ($station == "") {
//                $result["station"] = $description;
//            } else {
//                $result["station"] = $stationname;
//            }
//            $result["name"] = $description;
//            $result["country"] = $country;
//            $result["city"] = $name;
//            $result["position"] = $geoaddress;
//            $result["longitude"] = "";
//            $result["latitude"] = "";
//
//            $position = explode(" ", $geoaddress);
//            if (count($position) == 2) {
//                $result["longitude"] = $position[0];
//                $result["latitude"] = $position[1];
//            }
//
//            $cacheres = $this->SendToCache($result);
//            if (!$cacheres["result"]) {
//                print_r($cacheres);
//            }
//        } else {
//            $result = $yandexcache["line"];
//        }
//        return $result;
//    }


    private function gdv3($air, &$service)
    {

        $services = [];

        $service["prodid"] = $this->DTV($air, ["@attributes", "prod_id"]);

        $service["StationFromCode"] = "";
        $service["StationToCode"] = "";

        $service["nomenclature"] = "ЖДБилет";
        $service["supplier"] = $this->supplier;
        $service["Supplier"] = $this->supplier;

        $service["TariffAmount"] = (float)($this->DTV($air, ["@attributes", "fare"]));

        $service["price"] = $service["TariffAmount"];
        $service["amount"] = $service["price"];

        $service["pricecustomer"] = $service["price"];
        $service["VATratecustomer"] = $service["VATrate"];
        $service["amountVATcustomer"] = $service["VATAmount"];
        $service["amountclient"] = $service["amount"];

        $service["AmountServices"] = $service["pricecustomer"];

        $service["TypeOfTicket"] = "S";

        $service["Carrier"] = $this->DTV($air, ["@attributes", "carrier"]);
        $service["Wagon"] = $this->DTV($air, ["@attributes", "carnum"]);
        $service["Place"] = $this->DTV($air, ["@attributes", "seat"]);

        $service["VATAmount18"] = (float)$this->DTV($air, ["@attributes", "nds_20"]);
        $service["AmountWithVAT18"] = round($service["VATAmount18"] / 20 * 100, 1);

        $service["TrainNumber"] = $this->DTV($air, ["@attributes", "train_number"]);

        try {
            $service["Depart"] = $this->DTV($air, ["@attributes", "departure_datetime"]);
            $format = "Y-m-d H:i:s";
            $dateDepart = DateTime::createFromFormat($format, $service["Depart"]);
            $service["Depart"] = $dateDepart->format("YmdHis"); //!!!!!
        } catch (Exception $e) {
            $service["Depart"] = "";
        }

        try {
            $service["Arrival"] = $this->DTV($air, ["@attributes", "arrival_datetime"]);
            $format = "Y-m-d H:i:s";
            $dateDepart = DateTime::createFromFormat($format, $service["Arrival"]);
            $service["Arrival"] = $dateDepart->format("YmdHis"); //!!!!!
        } catch (Exception $e) {
            $service["Arrival"] = "";
        }
        $service["ServiceStartDate"] = $service["Depart"];
        $service["ServiceEndDate"] = $service["Arrival"];

        $diff = strtotime($service["ServiceEndDate"]) - strtotime($service["ServiceStartDate"]);
        $service["TravelTime"] = round(abs($diff) / 60 / 60);

        $StationFrom = $this->DTV($air, ["@attributes", "origin"]);
        $StationTo = $this->DTV($air, ["@attributes", "destination"]);

//        $DepartureGeoData = $this->GetStationFromText(["", "railway", "станция " . $StationFrom]);
//        $ArrivalGeoData = $this->GetStationFromText(["", "railway", "станция " . $StationTo]);
//
//        $service["PlaceDeparture"] = [
//            "Place" => $DepartureGeoData["station"],
//            "City" => $DepartureGeoData["city"],
//            "Country" => $DepartureGeoData["country"]
//        ];
//        $service["PlaceArrival"] = [
//            "Place" => $ArrivalGeoData["station"],
//            "City" => $ArrivalGeoData["city"],
//            "Country" => $ArrivalGeoData["country"]
//        ];
//
//        $service["CityDeparture"] = $DepartureGeoData["city"];
//        $service["CityArrival"] = $ArrivalGeoData["city"];
//
//        $service["Latitude"] = $ArrivalGeoData["latitude"];
//        $service["Longitude"] = $ArrivalGeoData["longitude"];
//        $service["LatitudeDeparture"] = $DepartureGeoData["latitude"];
//        $service["LongitudeDeparture"] = $DepartureGeoData["longitude"];

        $service["AddressDestination"] = $StationTo;
        $service["AddressDeparture"] = $StationFrom;

        $service["DepartureCode"] = $service["StationFromCode"];
        $service["ArrivalCode"] = $service["StationToCode"];

        $service["Route"] = $service["CityDeparture"] . " - " . $service["CityArrival"];

        return $services;
    }


    private function jv3($jsonv)
    {
        $services = [];

        $id = $this->DTV($jsonv, ["header", "@attributes", "ord_id"]);
        try {
            $date = $this->DTV($jsonv, ["header", "@attributes", "time"]);
            $format = "Y-m-d H:i:s";
            $dateDepart = DateTime::createFromFormat($format, $date);
            $date = $dateDepart->format("YmdHis");
        } catch (Exception $e) {
            $date = "";
        }

        $manager = $this->DTV($jsonv, ["manager", "@attributes", "login"]);

        $reservations = $this->GetReservations($jsonv);
        $traveldocs = $this->GetTravelDoc($jsonv);
        //print_r($traveldocs);

        $products = $product = $this->DTV($jsonv, ["products", "product"]);
        if (isset($product["air_ticket_prod"])) {
            $products = [];
            $products[] = $product;
        }

        foreach ($products as $value) {
            $service = $this->get_empty_v3();

            $service["manager"] = $manager;
            $service["Synh"] = "vip_" . $id;
            $service["date"] = $date;

            if (isset($value["air_ticket_prod"])) {
                //Авиабилет
                $res = $this->airv3($value["air_ticket_prod"], $service, $traveldocs);


                $Secondeds = $this->AddPassanger($jsonv);
                foreach ($Secondeds as $Seconded) {
                    $psgrid = $traveldocs[$service["prodid"]]["psgr_id"];
                    if ($Seconded["psgrid"] == $psgrid) {
                        $service["Seconded"][] = $Seconded;
                    }
                }

                $service["SegmentCount"] = count($service["Segments"]);

                if (isset($service["prodid"])) {
                    //
                    $prodid = $service["prodid"];
                    if (isset($reservations[$prodid])) {
                        $service["ReservationNumber"] = $this->DTV($reservations[$prodid], ["ReservationNumber"]);
                    }
//                    if (isset($traveldocs[$prodid])) {
//                        $ExNumber = $this->DTV($traveldocs[$prodid], ["ExchengeTNumber"]);
//                        if (trim($ExNumber) != "") {
//                          $service["nomenclature"] = "ОбменАвиабилета";
//                          $service["TypeOfTicket"] = "E";
//                          $service["TicketSales"] = $ExNumber;
//                        }
//                    }

                }


                $services = array_merge($services, $res);

            }
            elseif (isset($value["ral_ticket_prod"])) {
                //ЖД Билет
                $res = $this->gdv3($value["ral_ticket_prod"], $service);
                $service["Seconded"] = [];
                $Secondeds = $this->AddPassanger($jsonv);
                foreach ($Secondeds as $Seconded) {
                    $psgrid = $traveldocs[$service["prodid"]]["psgr_id"];
                    if ($Seconded["psgrid"] == $psgrid) {
                        $service["Seconded"][] = $Seconded;
                    }
                }

                if (isset($service["prodid"])) {
                    //
                    $prodid = $service["prodid"];
                    if (isset($reservations[$prodid])) {
                        $service["ReservationNumber"] = $this->DTV($reservations[$prodid], ["ReservationNumber"]);
                    }
                    if (isset($traveldocs[$prodid])) {
                        //print_r($service["prodid"]);
                        $service["TicketNumber"] = $this->DTV($traveldocs[$prodid], ["TicketNumber"]);

                        $tkt_oper = $this->DTV($traveldocs[$prodid], ["tkt_oper"]);
                        if ($tkt_oper == "REF") {
                            //Возврат
                            $service["nomenclature"] = "ВозвратЖДБилета";
                            $service["TypeOfTicket"] = "R";
                            $service["TicketSales"] = $service["TicketNumber"];

                            $service["price"] = -1*$service["price"];
                            $service["VATAmount"] = -1*$service["VATAmount"];
                            $service["amount"] = -1*$service["amount"];

                            $service["pricecustomer"] = -1*$service["pricecustomer"];
                            $service["amountVATcustomer"] = -1*$service["amountVATcustomer"];
                            $service["amountclient"] = -1*$service["amountclient"];

                            $service["AmountServices"] = -1*$service["AmountServices"];


                            $tkt_date = $this->DTV($traveldocs[$prodid], ["tkt_date"]);
                            $service["date"] = $tkt_date;
                        }

                        $service["Synh"] = $service["TypeOfTicket"] . $service["TicketNumber"];
                    }
                }

            }
            elseif (isset($value["service_prod"])) {
                //Сбор випсервиса
                $this->servicev3($value["service_prod"], $service);

                foreach ($services as $searchservice) {
                    if ($searchservice["prodid"] == $service["prodid"]) {
                        $service["ApplicationService"] = $searchservice["Synh"];

                        //Временная заглушка
                        //$service["attachedto"] = $searchservice["Synh"];

                        $service["ownerservice"] = $searchservice["Synh"];
                        $service["attachedto"] = $searchservice["Synh"];
                        $service["TypeThisService"] = "Загруженная";

                        $service["Synh"] = $service["Synh"] . "_" . $service["prodid"];
                    }
                }
                //$services = array_merge($services, $service);
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
                $Parser->SetUseParser("Portbilet", md5($text), $this->Auth->getuserid());

                $textxml = $this->phpinput;
                $xmlheader = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>';
                $textxml = str_replace($xmlheader, "" , $textxml);
                $textxml = str_replace('xmlns:', "" , $textxml);


                $xml = simplexml_load_string(trim($textxml));
                $jsonv = $this->object2array($xml);

                $id = $this->DTV($jsonv, ["header", "@attributes", "ord_id"]);

                $result["json"] = $jsonv;
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
