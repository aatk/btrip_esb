<?php

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 25/07/2019
 * Time: 13:10
 */
class Galileo extends ex_classlite
{
    private $metod;

    private $Auth;

    private $supplier;

    private $alltaxs;
    private $allsegments;


    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        parent::__construct();
        $this->metod = $metod;

        $this->debugclass = $debug;
        $this->Auth = new Auth();

        $this->supplier = ["INN" => "", "KPP" => "", "Name" => "Галилео"];
    }

    private function v1($textfile)
    {
        $result = [];

        //\T[0-9]\:\s+[0-9]+[A-Z]{2}
        $matches = [];
        $tax = preg_match_all("/\T[0-9]\:+[\s,0-9]{8}+[A-Z,0-9]{2}/", $textfile, $matches);
        //$tax = preg_match_all("/\T[0-9]\:\s+[0-9]+[A-Z,0-9]{2}/", $textfile, $matches);
        //print_r($tax);
        //print_r($matches);

        $airfile = $lines = preg_split('/\\r\\n?|\\n/', $textfile);
        $indexline = 0;
        $nowteg = "";

        $A = [];
        $B = [];
        $P = [];
        $R = [];

        $nowheader = true;
        $headerfile = [];
        $nowArray = $A;

        if (isset($airfile)) {
            foreach ($airfile as $line_num => $line) {

                if (trim($line) == "") {
                    //Следующий блок
                    $nowheader = false;
                    continue;
                }

                if ($nowheader) {
                    $headerfile[] = $line;
                    continue;
                }

                if (substr($line, 0, 1) == "A") {
                    //Это начало полезной информации
                    $teg = substr($line, 0, 3);

                    $saveline = substr($line, 3);
                    $A[$teg][] = $saveline;

                    $nowteg = $teg;
                    $indexline = count($A[$teg]) - 1;

                    $nowArray = $A;
                } elseif (substr($line, 0, 1) == "R") {
                    //Это начало полезной информации
                    $teg = substr($line, 0, 3);

                    $saveline = substr($line, 3);
                    $R[$teg][] = $saveline;

                    $nowteg = $teg;
                    $indexline = count($R[$teg]) - 1;

                    $nowArray = $R;
                } elseif (substr($line, 0, 1) == "P") {
                    //Это начало полезной информации
                    $teg = substr($line, 0, 3);

                    $saveline = substr($line, 3);
                    $P[$teg][] = $saveline;

                    $nowteg = $teg;
                    $indexline = count($P[$teg]) - 1;

                    $nowArray = $P;
                } elseif (substr($line, 0, 1) == "B") {
                    //Это начало полезной информации
                    $teg = substr($line, 0, 3);

                    $saveline = substr($line, 3);
                    $B[$teg][] = $saveline;

                    $nowteg = $teg;
                    $indexline = count($B[$teg]) - 1;

                    $nowArray = $P;
                } else {
                    $A[$nowteg][$indexline] = $A[$nowteg][$indexline] . $line;
                }

                //echo $line."\r\n";
            }
        }


        $result["header"] = $headerfile;
        $result["A"] = $A;
        $result["B"] = $B;
        $result["P"] = $P;
        $result["R"] = $R;
        $result["taxs"] = $matches;

        return $result;
    }

    private function v2($innerjson)
    {
        $result = [];
        $headerfile = $innerjson["header"];

        $header_explode = [];
        $lineh1 = $headerfile[0];
        $header_explode["type"] = substr($lineh1, 8, 2);
        $header_explode["date_create"] = substr($lineh1, 20, 11);
        $header_explode["carrier"] = substr($lineh1, 32, 2);
        $header_explode["carrier_code"] = trim(substr($lineh1, 34, 3));
        $header_explode["carrier_name"] = trim(substr($lineh1, 37, 24));
        $header_explode["date_start"] = substr($lineh1, 61, 7);

        //54N 54N92222826 C7ZHC0         084730N30AG23JUL1900023JUL19010
        $lineh1 = $headerfile[1];
        $header_explode["rss"] = substr($lineh1, 4, 4);
        $header_explode["validator"] = substr($lineh1, 8, 8);
        $header_explode["location"] = substr($lineh1, 16, 7);
        $header_explode["agentcode"] = substr($lineh1, 23, 13);
        $header_explode["agentcode"] = $header_explode["agentcode"].substr($lineh1, 39, 2);

        $A27 = $this->DTV($innerjson, ["A", "A27", 0]);
        $header_explode["CommissionAmount"] = substr($A27, 2, 17);

        $Penalty = $this->DTV($innerjson, ["P", "PF:", 0], 0);
        $header_explode["Penalty"] = (float) trim($Penalty);

        $A10 = $this->DTV($innerjson, ["A", "A10", 0]);
        $header_explode["ExchangeTiket"] = trim(substr($A10, 80, 13));

        $lineh4 = $headerfile[3];
        $header_explode["TypeTiket"] = trim(substr($lineh4, 11, 1));

        $result["header_ex"] = $header_explode;


        //--------------------------------------------------------------------------------------------------------------
        $A8 = $this->DTV($innerjson, ["A", "A08"]);
        //0101NCL     0000000002MAR2002MAR20      F:NCL            E:PSPT46087001103 INCL VAT 9   E:1.00RUB                      B:1PCEF:PSPT46087001103 INCL VAT 9/1.00RUB
        $FBs = [];
        foreach ($A8 as $key => $datepax) {
            $FB = [];
            $FB["FareBases"] = substr($datepax, 42, 3);
            $FBs[] = $FB;
        }

        if ($this->debugclass) {
            print_r($FBs);
        }
        $result["FareBases"] = $FBs;
        //--------------------------------------------------------------------------------------------------------------

        //Вытаскиваем ФИО
        //VLASOVA/ANNAPAVLOVNAMRS          204903305009479482645101000000002ADT   01  NSI:GEN:VLASOVA/ANNAPAVLOVNAMRS                                TL:23JUL19C35:NTD:23JUL19
        $paxs = [];
        $A2 = $this->DTV($innerjson, ["A", "A02"]);
        foreach ($A2 as $key => $datepax) {
            $pax = [];
            $pax["name"] = substr($datepax, 0, 33);
            $pax["type_doc"] = substr($datepax, 33, 2);
            $pax["number_doc"] = substr($datepax, 35, 10);
            $pax["tiket_number"] = substr($datepax, 45, 10);
            $paxs[] = $pax;
        }

        $result["paxs"] = $paxs;

        //Таксы
        $taxsumm = 0;
        $taxs = [];
        $intaxs = $this->DTV($innerjson, ["taxs", 0]);
        foreach ($intaxs as $taxinfo) {
            $lt = explode(":", $taxinfo);
            $name = substr($lt[1], -2, 2);
            $cost = (float)substr($lt[1], 0, count($lt[1]) - 3);
            $taxs[$name] = $cost;
            if ($name != "CP") {
                $taxsumm += $cost;
            }
        }
        $result["taxs"] = $taxs;


        if (($result["header_ex"]["ExchangeTiket"] != "") && ($result["header_ex"]["Penalty"] == 0)) {
            //Обмен, но штрафа нет. Возможно штраф в таксах CP
            $taxs = [];
            foreach ($result["taxs"] as $name => $cost) {
                if ($name == "CP") {
                    //Это штраф
                    $result["header_ex"]["Penalty"] = (float) $cost;
                } else {
                    $taxs[$name] = $cost;
                }
            }
            $result["taxs"] = $taxs;
        }


        //Вытаскиваем сегменты
        //01S7421SIBERIA AIRL  47S HK26JUL0730 0905 2DMEMOSCOW/DOMODELEDST PETERSBURGDNS   O0   319    00399F TK:YJT:01.35ANL:SIBERIA AIRLINES        DDL:26JUL19
        //01SU555AEROFLOT RUS1483N   24SEP1110      2KJAKRASNOYARSK  SVOMOSCOW/SHEREMDNL   O0   32B T1 02087F TK:YANL:AEROFLOT RUSSIAN AIRLINEDDL:24SEP19"
        $segments = [];
        $A4 = $this->DTV($innerjson, ["A", "A04"]);
        foreach ($A4 as $key => $datesegment) {
            $segment = [];
            $segment["carrier"] = substr($datesegment, 2, 2);
            $segment["carrier_code"] = substr($datesegment, 4, 3);
            $segment["carrier_name"] = substr($datesegment, 7, 12);
            $segment["reis"] = substr($datesegment, 19, 5);
            $segment["date_start"] = substr($datesegment, 27, 5);
            $segment["time_start"] = substr($datesegment, 32, 4);
            $segment["time_end"] = substr($datesegment, 37, 4);
            $segment["place_start"] = substr($datesegment, 43, 3);
            $segment["place_end"] = substr($datesegment, 59, 3);
            $segments[] = $segment;
        }
        $result["segments"] = $segments;


        //Стоимость
        //01RUB        6350RUB       10800               RUBT1:     430RIT2:    1420YQT3:    2600YR
        //01USD     1550.00RUB      112725RUB       99200RUBT1:    1002RIT2:     320EFT3:   12203XTIT:    1920GN     333FR    1374QX     896YQ    7680YR



        //       0   1983200000000       0RUB   19832
        $RA = $this->DTV($innerjson, ["R", "RA:", 0]);
        $RepeatSumm = (float) mb_substr($RA, 35);

        $ExchangeSumm = 0;
        $A11 = $this->DTV($innerjson, ["A", "A11"]);
        foreach ($A11 as $key => $datecost) {
            $price = substr($datecost, 0, 14);
            $ExchangeSumm = $this->toint($price);
            if ($ExchangeSumm == $result["header_ex"]["Penalty"]) {
                $ExchangeSumm = 0;

//                if ($this->debugclass) {
//                    print_r("ExchangeSumm"."\r\n");
//                    var_dump($ExchangeSumm);
//                    var_dump($result["header_ex"]["Penalty"]);
//                }
            }
        }


        $costs = [];
        $A7 = $this->DTV($innerjson, ["A", "A07"]);
        foreach ($A7 as $key => $datecost) {
            $cost = [];
            $cost["price_cur"] = substr($datecost, 17, 3);
            if ($RepeatSumm != 0) {
                $cost["price"] = $RepeatSumm + $result["header_ex"]["Penalty"];
            } else {
                $cost["price"] = substr($datecost, 20, 12);
            }

            $cost["tarif_cur"] = substr($datecost, 2, 3);
            $cost["tarif"] = (float) substr($datecost, 5, 12);
            if ($cost["tarif_cur"] != "RUB") {
                $cost["tarif_cur"] = substr($datecost, 32, 3);
                $cost["tarif"] = (float) substr($datecost, 35, 12);
            }
            $costs[] = $cost;
        }

        if ($this->debugclass) {
            print_r($costs);
            print_r($RepeatSumm);
        }
        //EX           0N                                     P:01"

        if ($RepeatSumm == 0) {
            $costs[0]["price"] = $costs[0]["tarif"] + $taxsumm;
        }

        if (($result["header_ex"]["ExchangeTiket"] != "")){ //&& ($ExchangeSumm !=0)) {
//            if ($this->debugclass) {
//                print_r("ExchangeSumm" . "\r\n");
//            }
                $costs[0]["price"] = $ExchangeSumm;
        }


        $result["costs"] = $costs;

        if ($this->debugclass) {
            print_r($costs);
        }


        //Расчет НДС
        $times = [];
        $vats = [];
        $A15 = $this->DTV($innerjson, ["A", "A15"]);
        foreach ($A15 as $key => $datevat) {
            $vat = [];
            $exline = explode(":", $datevat);
            $time = $exline[1];
            if (!in_array($time, $times)) {
                $times[] = $time;
            }

            $info = explode(" ", $exline[3]);

            $vatinfo["type"] = trim($exline[2]);
            $vatinfo["summ"] = (float)$info[0];
            $vatinfo["rate"] = "".$info[1];
            $vatinfo["vat"] = (float)$info[2];

            if (($vatinfo["type"] == "T") && ($vatinfo["summ"] == 0)) {
                $vatinfo["summ"] = (float)$this->DTV($costs, [0, "tarif"]);
            }


            if (($vatinfo["vat"] == 0) && ($vatinfo["rate"] != "NV") && ($vatinfo["rate"] != "0")) {
                $vatinfo["rate"] = "0";
            }


            $vats[$time][] = $vatinfo;
        }

        $maxtime = max($times);

        $result["header_ex"]["maxtime"] = $maxtime;
        $result["allvats"] = $vats;

        $vats = $vats[$maxtime];

        $allsumm = 0;
        foreach ($vats as $vatinfo) {
            $allsumm += $vatinfo["summ"];
        }


        if (is_null($vats)) {
            $vats = [];
            $vats["0"] = [
                "summ" => $allsumm,
                "vat" => 0,
                "rate" => "0"
            ];
        }


        if ($allsumm < $costs[0]["price"]) {
            $price = $costs[0]["price"];

            $vat = $vats[0];
            $vat["summ"] = $vat["summ"] + ($price - $allsumm);
            $vats[0] = $vat;
        } elseif ($allsumm > $costs[0]["price"]) {
            $price = $costs[0]["price"];

            $vat = $vats[0];
            $vat["summ"] = $vat["summ"] - ($allsumm - $price);
            $vats[0] = $vat;
        }

        $allsummvats = 0;
        $allvats = 0;
        $rates = [];
        foreach ($vats as $vatinfo) {
            $rate = (string)$vatinfo["rate"];
            if (isset($rates[$rate])) {
                //+
                $rates["".$rate]["summ"] = $rates[$rate]["summ"] + $vatinfo["summ"];
                $rates["".$rate]["vat"] = $rates[$rate]["vat"] + $vatinfo["vat"];
            } else {
                //0
                $rates["".$rate]["summ"] = $vatinfo["summ"];
                $rates["".$rate]["vat"] = $vatinfo["vat"];
            }
            $allvats = $allvats + $vatinfo["vat"];
            $allsummvats = $allsummvats + $vatinfo["summ"];
        }


        //Penalty

//        $Penalty = (int) trim($this->DTV($innerjson, ["header_ex", "Penalty"]));
//        $Cost = (int) trim($this->DTV($innerjson, ["costs", 0, "price"]));
//        if ($Penalty == $Cost) {
//            //$this->DTV($innerjson, ["costs", 0, "price"])
//        }

        $result["vats"] = $rates;
        $result["header_ex"]["allsummvats"] = $allsummvats;
        $result["header_ex"]["allvats"] = $allvats;

        return $result;
    }

    private function CreateTaxs(&$services, $inservice, $innerjson)
    {
        $fees = [];

        $Taxs = $this->DTV($innerjson, ["taxs"]);
        foreach ($Taxs as $NameTax => $Tax) {
            $service = $this->get_empty_v3();

            $service["nomenclature"] = "ТаксыАвиабилета";

            $service["NameFees"] = $NameTax;

            $service["price"] = $Tax;
            $service["amount"] = $Tax;
            $service["amountVAT"] = 0;
            $service["VATrate"] = -1;

            $service["pricecustomer"] = $service["price"];
            $service["amountclient"] = $service["price"];
            $service["VATratecustomer"] = $service["VATrate"];
            $service["amountVATcustomer"] = $service["amountVAT"];


            $service["supplier"] = $this->supplier;
            $service["Supplier"] = $this->supplier;

            $service["Synh"] = md5(json_encode($service, JSON_UNESCAPED_UNICODE));
            $service["MD5SourceFile"] = $service["Synh"];
            $service["date"] = date("YmdHis", time());

            if (!in_array($service["Synh"], $this->alltaxs)) {
                $this->alltaxs[] = $service["Synh"];
                $services[] = $service;
            }

            $fees[] = $service["Synh"]; //Это для создания списка такс
        }

        return ["Fees" => $fees];
    }

    private function CreateSegments(&$services, $inservice, $innerjson)
    {
        $segmentsservice = [];
        $segmentsarray = [];

        $Segments = $this->DTV($innerjson, ["segments"]);
        foreach ($Segments as $segment) {
            $service = $this->get_empty_v3();

            $service["nomenclature"] = "СегментАвиабилета";

            $DepartureCode = $this->DTV($segment, ["place_start"]);
            $service["DepartureCode"] = $DepartureCode;

            $ArrivalCode = $this->DTV($segment, ["place_end"]);
            $service["ArrivalCode"] = $ArrivalCode;

            $service["PlaceDeparture"] = $DepartureCode;
            $service["CityDeparture"] = $DepartureCode;
//            $res = $this->Catalogs->getCatalogItems("Propertys", ["value" => $DepartureCode]);
//            if ($res) {
//                $idplace = $res[0]["place"];
//                $res = $this->Catalogs->getCatalogItems("Places", ["id" => $idplace]);
//                if ($res) {
//                    $service["PlaceDeparture"] = $res[0]["place"];
//
//                    $findCity = true;
//                    do {
//                        if ($res[0]["type"] == "Город") $service["CityDeparture"] = $res[0]["place"];
//                        if (($res[0]["parent"] == 0) || ($res[0]["type"] == "Город")) $findCity = false;
//                        $res = $this->Catalogs->getCatalogItems("Places", ["id" => $res[0]["parent"]]);
//                    } while ($findCity);
//                }
//            }

            $service["PlaceArrival"] = $ArrivalCode;
            $service["CityArrival"] = $ArrivalCode;
//            $res = $this->Catalogs->getCatalogItems("Propertys", ["value" => $ArrivalCode]);
//            if ($res) {
//                $idplace = $res[0]["place"];
//                $res = $this->Catalogs->getCatalogItems("Places", ["id" => $idplace]);
//                if ($res) {
//                    $service["PlaceArrival"] = $res[0]["place"];
//
//                    $findCity = true;
//                    do {
//                        if ($res[0]["type"] == "Город") $service["CityArrival"] = $res[0]["place"];
//                        if (($res[0]["parent"] == 0) || ($res[0]["type"] == "Город")) $findCity = false;
//                        $res = $this->Catalogs->getCatalogItems("Places", ["id" => $res[0]["parent"]]);
//                    } while ($findCity);
//                }
//            }


            $date_start = $this->DTV($segment, ["date_start"]);
            $time_start = $this->DTV($segment, ["time_start"]);
            $time_end = trim($this->DTV($segment, ["time_end"]));
            if ($time_end == "") {
                $time_end = $time_start;
            }

            $Depart = $this->DTV([$date_start . $time_start], [0], "", "dMHi");
            $Arrival = $this->DTV([$date_start . $time_end], [0], "", "dMHi");

            $NowDate = date("YmdHis", strtotime("-1 month"));

            if ($NowDate > $Depart) {
                $Depart = strtotime("+1 years", strtotime($Depart));
                $Depart = date("YmdHis", $Depart);
            }
            if ($NowDate > $Arrival) {
                $Arrival = strtotime("+1 years", strtotime($Arrival));
                $Arrival = date("YmdHis", $Arrival);
            }

            $service["Depart"] = $Depart;
            $service["Arrival"] = $Arrival;
            $service["ServiceStartDate"] = $Depart;
            $service["ServiceEndDate"] = $Arrival;

            $diff = strtotime($service["ServiceEndDate"]) - strtotime($service["ServiceStartDate"]);
            $service["TravelTime"] = round(abs($diff) / 60);


            $service["supplier"] = $this->supplier;
            $service["Supplier"] = $this->supplier;

            $service["Synh"] = md5(json_encode($service, JSON_UNESCAPED_UNICODE));
            $service["MD5SourceFile"] = $service["Synh"];
            $service["date"] = $service["ServiceStartDate"];

            $segmentsservice[] = $service;

            if (!in_array($service["Synh"], $this->allsegments)) {
                $this->allsegments[] = $service["Synh"];
                $services[] = $service;
            }
            $segmentsarray[] = $service["Synh"];

        }

        return ["Segments" => $segmentsarray, "SegmentsService" => $segmentsservice];
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

    private function GetMinMaxInfo($SegmentsService)
    {

        $min_date = strtotime("+5 years", time());
        $max_date = 0;

        $min_port = "";
        $max_port = "";

        $min_place = "";
        $max_place = "";

        $min_city = "";
        $max_city = "";

        foreach ($SegmentsService as $Segment) {

            if ($min_date > strtotime($Segment["Depart"])) {
                $min_date = strtotime($Segment["Depart"]);
                $min_port = $Segment["DepartureCode"];
                $min_place = $Segment["PlaceDeparture"];
                $min_city = $Segment["CityDeparture"];
            }

            if ($max_date < strtotime($Segment["Arrival"])) {
                $max_date = strtotime($Segment["Arrival"]);
                $max_port = $Segment["ArrivalCode"];
                $max_place = $Segment["PlaceArrival"];
                $max_city = $Segment["CityArrival"];
            }

        }

        return [
            "Arrival" => ["date" => date("YmdHis", $max_date), "port" => $max_port, "place" => $max_place, "city" => $max_city],
            "Depart" => ["date" => date("YmdHis", $min_date), "port" => $min_port, "place" => $min_place, "city" => $min_city]];
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

    private function v3($innerjson)
    {
        $result = [];
        $services = [];

        $paxs = $this->DTV($innerjson, ["paxs"]);
        foreach ($paxs as $pax) {
            $service = $this->get_empty_v3();

            $location = trim($this->DTV($innerjson, ["header_ex", "location"]));

            $segmentscount = count($innerjson["segments"]); //$this->DTV($innerjson, ["segments"]));

            $service["nomenclature"] = "Авиабилет";
            //вывод базовых тарифов
            $FareBases = $this->DTV($innerjson, ["FareBases"]);
            $service["FareBases"] = "";
            foreach ($FareBases as $FareBase) {
                $service["FareBases"] = $service["FareBases"]."/".$FareBase["FareBases"];
            }
            $service["FareBases"] = substr($service["FareBases"], 1);

            $service["TypeOfTicket"] = "S";

            $ExchangeTiket = trim($this->DTV($innerjson, ["header_ex", "ExchangeTiket"]));
            if ($ExchangeTiket != "") {
                $service["nomenclature"] = "ОбменАвиабилета";
                $service["TypeOfTicket"] = "E";

            }

            if ($location == "ZZZZZZ") {
                if ($segmentscount > 0) {
                    $service["nomenclature"] = "ВозвратАвиабилета";
                    $service["TypeOfTicket"] = "R";

                } else {
                    $service["nomenclature"] = "ОтменаАвиабилета";
                    $service["TypeOfTicket"] = "V";
                }
            }

            $TypeTiket = trim($this->DTV($innerjson, ["header_ex", "TypeTiket"]));
            if ($TypeTiket == "V") {
                $service["nomenclature"] = "ОтменаАвиабилета";
                $service["TypeOfTicket"] = "V";
            } elseif ($TypeTiket == "R"){
                $service["nomenclature"] = "ВозвратАвиабилета";
                $service["TypeOfTicket"] = "R";
            }


            $service["TicketNumber"] = $this->DTV($innerjson, ["header_ex", "carrier_code"]) . "-" . $this->DTV($pax, ["tiket_number"]);
            $service["Synh"] = $service["TypeOfTicket"] . $service["TicketNumber"];

            $service["date"] = $this->DTV($innerjson, ["header_ex", "date_create"], "", "dMy");
            if ($service["date"] == "") {
                $service["date"] = $this->DTV($innerjson, ["header_ex", "date_create"], "", "dMyHi");
            }
            //var_dump($this->DTV($innerjson, ["header_ex", "date_start"]));

            $service["supplier"] = $this->supplier;
            $service["Supplier"] = $this->supplier;

            $service["manager"] = trim($this->DTV($innerjson, ["header_ex", "agentcode"]));
            $service["CommissionAmount"] = (float)trim($this->DTV($innerjson, ["header_ex", "CommissionAmount"]));


            $service["Carrier"] = $this->DTV($innerjson, ["header_ex", "carrier"]);
            $service["CarrierContractor"] = $this->DTV($innerjson, ["header_ex", "carrier_code"]);

            $service["ReservationNumber"] = $location;

            $service["TariffAmount"] = (float)$this->DTV($innerjson, ["costs", 0, "tarif"]);


            $lowprice = $this->DTV($innerjson, ["costs", 0, "lowprice"], 0);
            $Penalty  = $this->DTV($innerjson, ["header_ex", "Penalty"], 0);
            if (($lowprice != 0) && ($Penalty != 0)) {
                $service["price"] = $lowprice + $Penalty;
            } else {
                $service["price"] = (float)$this->DTV($innerjson, ["costs", 0, "price"]);
            }

            if ($service["price"] != 0) {
                $service["amountVAT"] = (float)$this->DTV($innerjson, ["header_ex", "allvats"]);
            }
            $service["VATrate"] = -1;
            $service["amount"] = $service["price"];


            $vats = $this->DTV($innerjson, ["vats"]);

            if ($this->debugclass) {
                print_r($vats);
            }

            if (isset($vats["10"])) {
                $service["pricecustomer"] = $this->DTV($vats, ["10", "summ"]);
                $service["amountVATcustomer"] = $this->DTV($vats, ["10", "vat"]);
                $service["VATratecustomer"] = 110;
                $service["amountclient"] = $service["pricecustomer"];

                $service["AmountWithVAT10"] = $this->DTV($vats, ["10", "summ"]);
                $service["VATAmount10"] = $this->DTV($vats, ["10", "vat"]);

            } elseif (isset($vats["0"])) {
                $service["pricecustomer"] = $this->DTV($vats, ["0", "summ"]);
                $service["amountVATcustomer"] = 0;
                $service["VATratecustomer"] = -2;
                $service["amountclient"] = $service["pricecustomer"];
            }

            if (isset($vats["20"])) {
                //Сбор авиакомпании
                $service["AmountWithVAT18"] = $this->DTV($vats, ["20", "summ"]);
                $service["VATAmount18"] = $this->DTV($vats, ["20", "vat"]);
            }

            if (isset($vats["NV"])) {
                $service["AmountExcludingVAT"] = $vats["NV"]["summ"];
            }

            if ($service["amountclient"] == $service["amount"]) {
                $service["VATrate"] = $service["VATratecustomer"];
            }

            $service["AmountServices"] = $service["amountclient"];


            if ($service["TypeOfTicket"] == "R") {
                $service["price"] = -1*$service["price"];
                $service["amountVAT"] = -1*$service["amountVAT"];
                $service["amount"] = -1*$service["amount"];

                $service["pricecustomer"] = -1*$service["pricecustomer"];
                $service["amountVATcustomer"] = -1*$service["amountVATcustomer"];
                $service["amountclient"] = -1*$service["amountclient"];

                $service["AmountServices"] = -1*$service["AmountServices"];
            }

            //Таксы
            $fee = $this->CreateTaxs($services, $service, $innerjson);
            $service["Fees"] = $fee["Fees"];

            //Сегменты
            $Segments = $this->CreateSegments($services, $service, $innerjson);
            $service["Segments"] = $Segments["Segments"];

            $SegmentsService = $Segments["SegmentsService"];
            $SegmentsInfo = $this->GetMinMaxInfo($SegmentsService);

            $service["ArrivalCode"] = $SegmentsInfo["Arrival"]["port"];
            $service["DepartureCode"] = $SegmentsInfo["Depart"]["port"];

            $service["PlaceArrival"] = $SegmentsInfo["Arrival"]["place"];
            $service["PlaceDeparture"] = $SegmentsInfo["Depart"]["place"];

            $service["CityArrival"] = $SegmentsInfo["Arrival"]["city"];
            $service["CityDeparture"] = $SegmentsInfo["Depart"]["city"];

            $service["Depart"] = $SegmentsInfo["Depart"]["date"];
            $service["Arrival"] = $SegmentsInfo["Arrival"]["date"];
            $service["ServiceStartDate"] = $SegmentsInfo["Depart"]["date"];
            $service["ServiceEndDate"] = $SegmentsInfo["Arrival"]["date"];

            $diff = strtotime($service["ServiceEndDate"]) - strtotime($service["ServiceStartDate"]);
            $service["TravelTime"] = round(abs($diff) / 60 / 60);

            $RouteInfo = $this->BuilRouteStr($SegmentsService);
            $service["Route"] = $RouteInfo["Route"];
            $service["RouteShortened"] = $RouteInfo["RouteShortened"];


            $name = explode("/", $pax["name"]);

            $Seconded = [];
            $Seconded["LastNameLatin"] = trim(ucfirst(strtolower($this->DTV($name, [0]))));
            $Seconded["FirstNameLatin"] = trim(ucfirst(strtolower($this->DTV($name, [1]))));
            $Seconded["SurNameLatin"] = "";

            if (substr($Seconded["FirstNameLatin"],-2,2) == "mr") {
                $Seconded["FirstNameLatin"] = substr($Seconded["FirstNameLatin"], 0, -2);
            }

            $Seconded["FirstName"] = $Seconded["FirstNameLatin"];
            $Seconded["LastName"] = $Seconded["LastNameLatin"];
            $Seconded["SurName"] = $Seconded["SurNameLatin"];

            $Seconded["DocumentNumber"] = $pax["number_doc"];
            $Seconded["DocType"] = "";
            $Seconded["BirthDay"] = "";
            $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
            $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);

            $service["TuristName"] = mb_strtoupper($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"]);



            $service["Seconded"][] = $Seconded;

            if ($service["TypeOfTicket"] == "R") {
                $service["TicketSales"] = $service["TicketNumber"];
                //Закомментировал из-за того, что иногда возвращается не весь билет
                //$service["methods"] = ["afterload" => "СкопироватьБилетПродажи"];

                $service["AmountServices"] = 0;
            }

            if ($service["TypeOfTicket"] == "V") {
                $service["TicketSales"] = $service["TicketNumber"];
                $service["methods"] = ["afterload" => "ОтменитьУслугиПродажиИОтмены"];
            }



            if ($service["TypeOfTicket"] == "E") {
                $service["TicketSales"] = substr($ExchangeTiket,0,3)."-".substr($ExchangeTiket,3,15);

                $EPrice =  $this->DTV($innerjson, ["header_ex", "Penalty"]);

                $MabyPrice = $service["price"] - $EPrice;
                if ($MabyPrice < 0) {
                    $MabyPrice = 0;
                }

                $service["pricecustomer"] = $MabyPrice;
                $service["amountclient"] = $MabyPrice;

                if ($service["pricecustomer"] == 0) {
                    $service["amountVATcustomer"] = 0;
                    $service["VATratecustomer"] = -1;
                }
                //$service["amountVATcustomer"] = $service["amountVAT"];
                //$service["VATratecustomer"] = $service["VATrate"];


                $service["AmountServices"] = $MabyPrice;
            }


            if ($service["TypeOfTicket"] == "S") {
                if ((float)$service["pricecustomer"] == 0) {

                    if (
                        ((float)$service["AmountServices"] == 0) &&
                        ((float)$service["amountVAT"] == 0)
                    ) {

                        $service["pricecustomer"] = $service["price"];
                        $service["amountclient"] = $service["price"];
                        $service["amountVATcustomer"] = 0;
                        $service["VATratecustomer"] = -2;
                        $service["AmountServices"] = $service["price"];

                    } else {
                        $service["pricecustomer"] = $service["TariffAmount"];
                        $service["amountclient"] = $service["TariffAmount"];
                        $service["amountVATcustomer"] = 0;
                        $service["VATratecustomer"] = -2;
                        $service["AmountServices"] = $service["TariffAmount"];
                    }
                }
            }

            $service["SegmentCount"] = count($service["Segments"]);

            $services[] = $service;

            unset($service["methods"]);

            if ($service["TypeOfTicket"] != "E") {
                //При обменах Галилео возвращает рассчитаные НДС по всему билету, по этому не нужно формировать доп.услуги на обмен
                if (isset($vats["NV"])) {
                    //Сбор ГВА
                    $price = $vats["NV"]["summ"];
                    $seviceGVA = $this->CreateGVA($service, ["price" => $price, "amountVAT" => 0, "amount" => $price]);
                    $services[] = $seviceGVA;
                }

                if (isset($vats["20"])) {
                    //Сбор авиакомпании
                    $price = $this->DTV($vats, ["20", "summ"]);
                    $vat = $this->DTV($vats, ["20", "vat"]);
                    $seviceSA = $this->CreateSA($service, ["price" => $price, "amountVAT" => $vat, "amount" => $price]);
                    $services[] = $seviceSA;
                }
            }

            if (($service["TypeOfTicket"] == "R") || ($service["TypeOfTicket"] == "E")) {
                $Penalty = (int) trim($this->DTV($innerjson, ["header_ex", "Penalty"]));
//                $Cost = (int) trim($this->DTV($innerjson, ["costs", 0, "price"]));
                if ($Penalty > 0) {
                    $sevicePenalty = $this->CreatePenalty($service, ["price" => $Penalty, "amountVAT" => 0, "amount" => $Penalty]);
                    $services[] = $sevicePenalty;
                }
            }


        }

        $result["services"] = $services;

        return $result;
    }

    public function getinfo($params)
    {
        $result = ["result" => false];

        if ($this->debugclass) {
            echo "DEBUG!!!" . "\r\n";
        }

        if (true) {
            if ($params[1] == "v3") {

                $result["result"] = true;

                $text = $this->phpinput;
                $Parser = new Parser($this->metod);
                $Parser->SetUseParser("Galileo", md5($text), $this->Auth->getuserid());

                $json = $this->v1($text);
                $jsonv2 = $this->v2($json);

                $result["json"] = $json;
                $result["jsonv2"] = $jsonv2;
                $result["jsonv3"] = $this->v3($jsonv2); //$this->v3($text, $result["json"]);

            } else {
                $result["error"] = "Not for this version";
            }
        } else {
            $result["error"] = "Authorization fail";
        }


        return $result;
    }

}