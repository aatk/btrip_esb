<?php

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 19/11/2019
 * Time: 22:55
 */
class Sabre extends ex_classlite
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

        $this->supplier = ["INN" => "", "KPP" => "", "Name" => "Sabre"];
    }


    //***************  V1  ***************

    private function v1($textfile) {

        $airfile = $lines = preg_split('/\\r\\n?|\\n/', $textfile);

        $footerb = false;
        $header = [];
        $body = [];
        $footer = [];

        $otherIndex = 0;

        $passIndex = 0;
        $passMaxIndex = 0;
        $keySabre = "000";
        $passSabre = "1";

        foreach ($airfile as $key => $value) {
            if ($key < 16) {
                $header["r".$key] = $value;
            } elseif ($footerb) {
                if (trim($value) != "") {
                    $keySabre = substr($value, 0, 2);
                    $passSabre = substr($value, 2, 2);
                    $footer[$keySabre][$passSabre] = $value;
                }

            } else {
                $nextvalue = $airfile[$key+1];
                $nextkeySabre = substr($nextvalue, 0, 2);
                if (($value == "00000000000") && ($nextkeySabre == "M3")) {
                    $footerb = true;
                }

                //Для пассажиров
                if ($passIndex == 0) {
                    $keySabre = substr($value, 0, 2);
                    if (($keySabre == "M1")) {
                        $passSabre = substr($value, 2, 2);
                        $passIndex = 6;
                        $passMaxIndex = 7;
                    }

                    if (($keySabre == "M2")) {
                        $passSabre = substr($value, 2, 2);
                        $passIndex = 12;
                        $passMaxIndex = 13;
                    }

                }

                if ($passIndex > 0) {
                    $body[$keySabre][$passSabre]["r".($passMaxIndex-$passIndex)] = $value;
                    $passIndex -= 1;
                } else {
                    //Если это не пассажиры
                    $otherIndex += 1;
                    $body["other"]["r".$otherIndex] = $value;
                }

            }
        }

        $resairfile = [
            "header" => $header,
            "body" => $body,
            "footer" => $footer
        ];

        return $resairfile;
    }


    //***************  V2  ***************

    private function lineexplode($line, $masive) {

        $ex = [];
        $maxkaret = mb_strlen($line);
        $karet = 0;
        foreach ($masive as $key => $indexinline) {
            $indexinlineTrue = $indexinline-1;
            $length = $indexinlineTrue-$karet;

            $substr = mb_substr($line, $karet, $length);
            $ex["c".(string)($karet+1)] = rtrim($substr);
            $karet = $indexinlineTrue;
        }

        $substr = mb_substr($line, $karet, $maxkaret);
        $ex["c".(string)($karet+1)] = rtrim($substr);

        return $ex;
    }

    private function MaskedAirFile() {

        $Mask = [];
        $Mask["header"] = [
            "r0"  => [1,3,8,17,32,33,37,46,52,54,62,88,93,96,105,117,122,131,142,147,150,167,170,187,221,226],
            "r15"  => [1,2,3]
        ];
        $Mask["body"] = [
            "M1" => [
                "r1" => [1,5]
            ]
        ];
        $Mask["footer"] = [
            "M3" => [1,6,10,15,19,22,39,42,59,63,68,73,79,83,90,93,113,115,122,130,160,190,200,233],
            "M4" => [1,5,10,15,20],
            "M9" => [
                "OSR" => [1,4,9,12,15,19,20,25,26,39]
            ]
        ];

        return $Mask;
    }

    private function M8Masked($M8) {

        $newM8 = [];
        $vats = [];
        foreach ($M8 as $value) {
            if (mb_strpos($value, "X*")) {
                $exval = explode(" ", $value);
                $vatval = $exval[count($exval)-1];

                $exval = explode("/", $vatval);

                $procent = (int)($exval[1]);
                $index = (string)$procent;
                $vats[$index]["vat"] = (float)$exval[0];
                $vats[$index]["summ"] = intval($exval[0]/$procent*(100+$procent));
                $newM8["VATS"] = $vats;

            } elseif (mb_strpos($value, "XVAT*")) {
                $exval = explode("*", $value);

                $newM8["FULLNUM"] = $exval[1];
                $newM8["Supplier"] = substr($newM8["FULLNUM"],0,3);
            } else {
                $newM8[] = $value;
            }
        }
        return $newM8;
    }

    private function M9Masked($M9) {

        $mask = $this->MaskedAirFile();

        $newM9 = [];
        foreach ($M9 as $value) {
            if (mb_strpos($value, "PT-SSR DOCS")) {

                $value = substr($value, 15);
                $exval = explode("/", $value);

                $newM9["DOCS"] = $exval;
            } elseif (mb_strpos($value, "PT-OSI")) {

                $newM9["OSI"] = $value;
            } elseif (mb_strpos($value, "PT-SSR TKNE")) {
                $value = substr($value, 15);
                $exname = $this->lineexplode($value, $mask["footer"]["M9"]["OSR"]);

                $newM9["SSR"][] = $exname;
            } else {
                $newM9[] = $value;
            }
        }

        return $newM9;
    }

    private function v2($airfile) {

        $mask = $this->MaskedAirFile();

        $result = [];
        $passagers = $this->DTV($airfile, ["body", "M1"]);
        foreach ($passagers as $passager) {

            $r3 = (int)$this->DTV($passager, ["r3"]);
            //$r2 = (int)$this->DTV($passager, ["r2"]);
            $r2 = 1;
            $id = $r3.".".$r2;

            $name = $this->DTV($passager, ["r1"]);
            $exname = $this->lineexplode($name, [1, 5, 69]);
            $appendfio = [" MSTR", " MRS", " MR"];
            $fio = $exname["c5"];
            foreach ($appendfio as $item) {
                $fio = str_replace($item, "", $fio);
            }
            $fio = str_replace("/"," ", $fio);
            $fioex = explode(" ", trim($fio));

            $NewTiket = [];

            $NewTiket["findids"] = [
                "fio" => $fio,
                "fioex" => $fioex,
                "id" => $id
            ];


            //************ HEADER ************
            $NewTiket["header"] = [];
            foreach ($airfile["header"] as $key => $value) {
                if (trim($value) != "") {
                    if (isset($mask["header"][$key])) {
                        $exvalue = $this->lineexplode($value, $mask["header"][$key]);
                        $NewTiket["header"][$key] = $exvalue;
                    } else {
                        $NewTiket["header"][$key] = $value;
                    }
                }
            };



            //************ BODY ************
            $NewTiket["body"] = $airfile["body"];
            $NewTiket["body"]["M1"] = $passager;

            $M2 = $this->DTV($airfile, ["body", "M2"]);
            foreach ($M2 as $taxline) {
                $tax = preg_match_all("/\s[0-9]+[A-Z]{2}\s/", $taxline["r1"], $matches);
                foreach ($matches[0] as $match) {
                    $match = trim($match);
                    $taxname = mb_substr($match, -2, 2);
                    $taxcost = mb_substr($match, 0, -2);

                    $NewTiket["taxs"][$taxname] = $taxcost;
                }
            }

            //************ FOOTER ************
            $newfooter = $airfile["footer"];


            //************ M3 ************
            $M3 = $this->DTV($newfooter, ["M3"]);
            foreach ($M3 as $key => $value) {
                $exvalue = $this->lineexplode($value, $mask["footer"]["M3"]);
                $M3[$key] = $exvalue;
            }
            $newfooter["M3"] = $M3;


            //************ M4 ************
            $M4 = $this->DTV($newfooter, ["M4"]);
            foreach ($M4 as $key => $value) {
                $exvalue = $this->lineexplode($value, $mask["footer"]["M4"]);
                $M4[$key] = $exvalue;
            }
            $newfooter["M4"] = $M4;


            //************ M5 ************
            $newM5 = [];
            $M5index = 0;
            $M5 = $this->DTV($newfooter, ["M5"]);
            $fiosearch = $fioex[0]." ".$fioex[1];
            foreach ($M5 as $key => $value) {
                if (mb_strpos($value, $fiosearch) !== false) {
                    $M5index += 1;

                    $exname = $this->lineexplode($value, [1, 7, 11, 12, 22]);
                    $exvalue = explode("/", $exname["c22"]);
                    $newexvalue = [];
                    foreach ($exvalue as $nkey => $itemv) {
                        if ($nkey == 6) {
                            $itemv = $this->lineexplode($itemv, [1, 3, 7]);
                            $newexvalue["c".$nkey] = $itemv;
                        } else {
                            $newexvalue["c".$nkey] = trim($itemv);
                        }
                    }
                    $exname["c22"] = $newexvalue;
                    $newM5[(string)$M5index] = $exname;

                    $ticketnum = $exname["c12"];
                    $NewTiket["findids"]["ticketnum"] = $ticketnum;
                }
            }
            $newfooter["M5"] = $newM5;


            //************ M8 ************
            $newM8 = [];
            $M8 = $this->DTV($newfooter, ["M8"]);
            $idsearch = "-".$NewTiket["findids"]["id"];
            foreach ($M8 as $key => $value) {
                if (mb_strpos($value, $idsearch) !== false) {
                    $newM8[$key] = $value;
                }
            }
            $newM8 = $this->M8Masked($newM8);
            $newfooter["M8"] = $newM8;


            //************ M9 ************
            $newM9 = [];
            $M9 = $this->DTV($newfooter, ["M9"]);
            $fiosearch = $fioex[0]."/".$fioex[1];
            $ticketnum = $NewTiket["findids"]["ticketnum"];
            $passport = "XXXXXXXXXXXXXXXXXXXXXXXXXXX";
            foreach ($M9 as $key => $value) {
                if (mb_strpos($value, $fiosearch) !== false) {
                    $newM9[$key] = $value;

                    $Qvalue = substr($value, 15);
                    $exval = explode("/", $Qvalue);
                    $passport = $exval[3];
                    $NewTiket["findids"]["passport"] = $passport;
                }

                if (mb_strpos($value, $passport) !== false) {
                    $newM9[$key] = $value;
                }

                if (mb_strpos($value, $ticketnum) !== false) {
                    $newM9[$key] = $value;
                }
            }
            $newM9 = $this->M9Masked($newM9);
            $newfooter["M9"] = $newM9;


            $NewTiket["footer"] = $newfooter;


            $result[] = $NewTiket;
        }

        return $result;
    }


    //***************  V3  ***************

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
        //$innerjson = [ { place_start,  place_end, date_start, time_start, time_end } ]
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

    private function v3($airfiles) {
        $result = [];
        $services = [];

        foreach ($airfiles as $airfile) {
            $service = $this->get_empty_v3();

            $service["nomenclature"] = "Авиабилет";
            $service["TypeOfTicket"] = "S";

            $service["TicketNumber"] = $this->DTV($airfile, ["footer", "M9", "SSR", 0, "c26"]);
            $carrier_code = trim(substr($service["TicketNumber"], 0, 3));
            $service["TicketNumber"] = $carrier_code."-".substr($service["TicketNumber"], 3);
            $service["Synh"] = $service["TypeOfTicket"] . $service["TicketNumber"];

            $service["date"] = $this->DTV($airfile, ["header", "r0", "c3"], "", "dM");

            $service["supplier"] = $this->supplier;
            $service["Supplier"] = $this->supplier;

            $service["Carrier"] = trim($this->DTV($airfile, ["footer", "M5", "1", "c7"]));
            $service["CarrierContractor"] = $carrier_code;

            //$service["ReservationNumber"] = $location;

            //Почему тариф берется отсюда!? Потому что в описании такс и тарифа, если последний пасссажир ребенок, но тариф и таксы указываются для ребенка (меньше чем на взрослого)
            $service["TariffAmount"] = (float)$this->DTV($airfile, ["footer", "M5", "1", "c22", "c2"]);
            $TaxAmount = (float)$this->DTV($airfile, ["footer", "M5", "1", "c22", "c3"]);

            $price = $service["TariffAmount"] + $TaxAmount;


            $vats = $this->DTV($airfile, ["footer", "M8", "VATS"]);


            $service["price"] = $price;
            $service["amountVAT"] = 0;
            $service["VATrate"] = -2;
            $service["amount"] = $service["price"];

            $service["pricecustomer"] = $service["price"];
            $service["amountVATcustomer"] = 0;
            $service["VATratecustomer"] = -2;
            $service["amountclient"] = $service["pricecustomer"];

            $service["AmountServices"] = $service["amountclient"];

            if (isset($vats["10"])) {
                $price = $this->DTV($vats, ["10", "summ"]);
                $vat = $this->DTV($vats, ["10", "vat"]);

                $service["VATrate"] = 110;
                $service["VATratecustomer"] = 110;

                $service["amountVAT"] = $vat;
                $service["amountVATcustomer"] = $vat;
            }


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
            $fee = $this->CreateTaxs($services, $service, $airfile);
            $service["Fees"] = $fee["Fees"];

            //Сегменты
            $innerjson = [];
            $Segments = $this->DTV($airfile, ["footer", "M3"]);
            foreach ($Segments as $segment) {
                $inner = [];
                $inner["place_start"] = $this->DTV($segment, ["c19"]);
                $inner["place_end"] = $this->DTV($segment, ["c39"]);
                $inner["date_start"] = $this->DTV($segment, ["c10"]);
                $inner["time_start"] = $this->DTV($segment, ["c68"]);
                $inner["time_end"] = $this->DTV($segment, ["c73"]);
                $innerjson[] = $inner;
            };

            $Segments = $this->CreateSegments($services, $service, ["segments" => $innerjson]);
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


            $Seconded = [];
            $Seconded["LastNameLatin"] = trim(ucfirst(strtolower($this->DTV($airfile, ["findids", "fioex", 0]))));
            $Seconded["FirstNameLatin"] = trim(ucfirst(strtolower($this->DTV($airfile, ["findids", "fioex", 1]))));
            $Seconded["SurNameLatin"] = trim(ucfirst(strtolower($this->DTV($airfile, ["findids", "fioex", 2]))));

            $Seconded["FirstName"] = $Seconded["FirstNameLatin"];
            $Seconded["LastName"] = $Seconded["LastNameLatin"];
            $Seconded["SurName"] = $Seconded["SurNameLatin"];

            $Seconded["DocumentNumber"] = $this->DTV($airfile, ["findids", "passport"]);
            $Seconded["DocType"] = "";
            $Seconded["BirthDay"] = "";
            $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
            $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);

            $service["TuristName"] = mb_strtoupper($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"]);

            $service["Seconded"][] = $Seconded;


            if ($service["TypeOfTicket"] == "V") {
                $service["TicketSales"] = $service["TicketNumber"];
                $service["methods"] = ["afterload" => "ОтменитьУслугиПродажиИОтмены"];
            }

            $service["jsonv2"] = $airfile;

            $service["SegmentCount"] = count($service["Segments"]);

            $services[] = $service;

            unset($service["methods"]);

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

            if ($service["TypeOfTicket"] == "R") {
                $Penalty = (int) trim($this->DTV($innerjson, ["header_ex", "Penalty"]));
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

        if (true) {
            if ($params[1] == "v3") {

                $result["result"] = true;

                $text = trim($this->phpinput);
                $Parser = new Parser($this->metod);
                $Parser->SetUseParser("Sabre", md5($text), $this->Auth->getuserid());

                $result["json"] = $this->v1($text);
                $jsonv2 = $this->v2($result["json"]);
                $result["jsonv3"] = $this->v3($jsonv2);
            } else {
                $result["error"] = "Not for this version";
            }
        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }

}