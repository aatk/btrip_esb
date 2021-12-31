<?php

class S7 extends ex_classlite
{

    private $metod;
    //private $convert;
    //private $gds;
    //private $connectionInfo;

    private $Auth;
    private $Catalogs;//$getCatalogItems

    private $supplier;

    private $alltaxs;
    private $allsegments;

    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        parent::__construct();
        $this->metod = $metod;

        $this->debugclass = $debug;
        $this->Auth = new Auth();
        $this->Catalogs = new Catalogs();

        $this->supplier = ["INN" => "5448100656", "KPP" => "997650001", "Name" => "S7"];
    }

    private function v1($textfile)
    {
        $result = [];
        $airfile = $lines = preg_split('/\\r\\n?|\\n/', $textfile);
        $indexline = 0;

        //$itsline = true; //Флаг обработки сегментов

        $segments = []; //Массив с сегментами
        $passager = [];
        $taxes = [];
        $passport = [];
        $dates = [];
        $MUC = [];
        $ATC = [];
        $A = [];
        $B = [];
        $C = [];
        $G = [];
        $K = [];
        $L = [];
        $M = [];
        $N = [];
        $O = [];
        $Q = [];
        $U = [];
        $FOI = [];
        $FO = [];

        $T = [];
        $Y = [];
        $FE = [];
        $FH = [];
        $FM = [];
        $FP = [];
        $FV = [];
        $FT = [];

        $TK = [];
        $RM = [];
        $RII = [];
        $RIZ = [];
        $KRF = [];
        $KFTF = [];
        $KFTI = [];

        $RFD = [];
        $TMCD = [];

        if (isset($airfile)) {
            foreach ($airfile as $line_num => $line) {
                //Обрабатываем строку

                $linearray = preg_split("/[;]/", $line);

                $leninline = [];
                $pos = 0;
                foreach ($linearray as $key => $value) {
                    $leninline[$key] = $pos;
                    $pos = $pos + strlen($value) + 1;
                }

                $nlinearray = array_filter($linearray, 'strlen');

                $resultline = [];
                foreach ($nlinearray as $key => $value) {
                    $resultline['c' . $key] = trim($value);
                }


                if (substr($linearray[0], 0, 2) == "A-") {
                    $resultline["c0"] = str_replace("A-", "", $resultline["c0"]);
                    $A[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 3) == "MUC") {
                    //$resultline["c0"] = str_replace("", "", $resultline["c0"]);
                    $MUC[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 4) == "ATC-") {
                    $resultline["c0"] = str_replace("ATC-", "", $resultline["c0"]);
                    $ATC[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "B-") {
                    $resultline["c0"] = str_replace("B-", "", $resultline["c0"]);
                    $B[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "C-") {
                    $resultline["c0"] = str_replace("C-", "", $resultline["c0"]);
                    $C[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "D-") {
                    $resultline["c0"] = str_replace("D-", "", $resultline["c0"]);
                    $dates[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "G-") {
                    $resultline["c0"] = str_replace("G-", "", $resultline["c0"]);
                    $G[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "H-") {
                    $resultline["c0"] = str_replace("H-", "", $resultline["c0"]);
                    $segments[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 3) == "EMD") {
                    $resultline["c0"] = str_replace("", "", $resultline["c0"]);
                    $segments[] = $resultline;
                    $itsline = false;
                    //EMD

                } elseif (substr($linearray[0], 0, 2) == "K-") {
                    $resultline["c0"] = str_replace("K-", "", $resultline["c0"]);
                    $K[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 4) == "TAX-") {
                    $resultline["c0"] = str_replace("TAX-", "", $resultline["c0"]);
                    $taxes[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "L-") {
                    $resultline["c0"] = str_replace("L-", "", $resultline["c0"]);
                    $L[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "M-") {
                    $resultline["c0"] = str_replace("M-", "", $resultline["c0"]);
                    $M[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "N-") {
                    $resultline["c0"] = str_replace("N-", "", $resultline["c0"]);
                    $N[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "O-") {
                    $resultline["c0"] = str_replace("O-", "", $resultline["c0"]);
                    $O[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "Q-") {
                    $resultline["c0"] = str_replace("Q-", "", $resultline["c0"]);
                    $Q[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "Y-") {
                    $resultline["c0"] = str_replace("Y-", "", $resultline["c0"]);
                    $Y[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "I-") {
                    $resultline["c0"] = str_replace("I-", "", $resultline["c0"]);
                    $passager[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 3) == "FOI") {
                    $resultline["c0"] = str_replace("FOI", "", $resultline["c0"]);
                    $FOI[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "FO") {
                    $resultline["c0"] = str_replace("FO", "", $resultline["c0"]);
                    $FO[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "FT") {
                    $resultline["c0"] = str_replace("FT", "", $resultline["c0"]);
                    $FT[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 3) == "SSR") {
                    $passport[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "T-") {
                    $resultline["c0"] = str_replace("T-", "", $resultline["c0"]);
                    $T[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "FE") {
                    $resultline["c0"] = str_replace("FE", "", $resultline["c0"]);
                    $FE[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "FH") {
                    $resultline["c0"] = str_replace("FH", "", $resultline["c0"]);
                    $FH[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "FM") {
                    $resultline["c0"] = str_replace("FM", "", $resultline["c0"]);
                    $FM[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "FP") {
                    $resultline["c0"] = str_replace("FP", "", $resultline["c0"]);
                    $FP[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "FV") {
                    $resultline["c0"] = str_replace("FV", "", $resultline["c0"]);
                    $FV[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "TK") {
                    $resultline["c0"] = str_replace("TK", "", $resultline["c0"]);
                    $TK[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 2) == "RM") {
                    $resultline["c0"] = str_replace("RM", "", $resultline["c0"]);
                    $RM[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 3) == "RII") {
                    $resultline["c0"] = str_replace("RII", "", $resultline["c0"]);
                    $RII[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 3) == "KRF") {
                    $resultline["c0"] = str_replace("KRF", "", $resultline["c0"]);
                    $KRF[] = $resultline;
                    $itsline = false;


                } elseif (substr($linearray[0], 0, 2) == "U-") {
                    //$resultline["c0"] = str_replace("EMD", "", $resultline["c0"]);
                    $U[] = $resultline;
                    $itsline = false;


                } elseif (substr($linearray[0], 0, 4) == "KFTF") {
                    $resultline["c0"] = str_replace("KFTF", "", $resultline["c0"]);
                    $KFTF[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 3) == "RIZ") {
                    $resultline["c0"] = str_replace("RIZ", "", $resultline["c0"]);
                    $RIZ[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 4) == "TMCD") {
                    $resultline["c0"] = str_replace("TMCD", "", $resultline["c0"]);
                    $TMCD[] = $resultline;
                    $itsline = false;

                } elseif (substr($linearray[0], 0, 3) == "TMC") {
                    $resultline["c0"] = str_replace("TMC", "", $resultline["c0"]);
                    $TMC[] = $resultline;

                } elseif (substr($linearray[0], 0, 3) == "RFD") {
                    $resultline["c0"] = str_replace("RFD", "", $resultline["c0"]);
                    $RFD = $resultline;

                    $itsline = true;
                    //$itsline = false; //чтоб не сдвигались строки
                } elseif (substr($linearray[0], 0, 4) == "KFTI") {
                    $resultlinefull["c0"] = str_replace("KFTI", "", $resultline["c0"]);
                    $KFTI[] = $resultlinefull;

                    $itsline = true;
                } else {
                    $itsline = true;
                    $indexline++;
                }

                if ($itsline) {
                    $result['r' . $indexline] = $resultline;
                }
            }

            $result['dates'] = $dates;

            $result['MUC'] = $MUC;
            //$result['EMD'] = $EMD;
            $result['ATC'] = $ATC;

            $result['A'] = $A;
            $result['B'] = $B;
            $result['C'] = $C;
            $result['G'] = $G;
            $result['K'] = $K;
            $result['L'] = $L;
            $result['M'] = $M;
            $result['N'] = $N;

            $result['O'] = $O;
            $result['Q'] = $Q;
            $result['T'] = $T;
            $result['Y'] = $Y;
            $result['U'] = $U;

            $result['FE'] = $FE;
            $result['FH'] = $FH;
            $result['FO'] = $FO;
            $result['FOI'] = $FOI;

            $result['FM'] = $FM;
            $result['FP'] = $FP;
            $result['FV'] = $FV;
            $result['FT'] = $FT;
            $result['TK'] = $TK;
            $result['RM'] = $RM;
            $result['RII'] = $RII;
            $result['RIZ'] = $RIZ;
            $result['KRF'] = $KRF;
            $result['KFTF'] = $KFTF;

            //TMCD235-2903207010
            $result['TMCD'] = $TMCD;
            $result['RFD'] = $RFD;
            $result['KFTI'] = $KFTI;


            $result['segments'] = $segments;
            $result['passager'] = $passager;
            $result['passport'] = $passport;
            $result['taxes'] = $taxes;

        }

        $result = ["result" => true, "json" => $result];

        return $result;
    }

    private function BuildRoute(&$Route, $Whot)
    {
        $lastWhot = $Route[count($Route) - 1];
        if ($lastWhot != $Whot) {
            $Route[] = $Whot;
        }
    }

    private function GetFeeInfo($tiket, &$services)
    {
        $fees = [];

        $vat_rate = 0;
        $vat_amount = 0;
        $vat_price = 0;
        $vat_amount_full = 0;

        foreach ($tiket["taxes"] as $tax) {
            foreach ($tax as $key => $taxjson) {
                $service = $this->get_empty_v3();

                $line = preg_split("/[ ]+/", $taxjson);

                $servicetax = [];
                $servicetax["nomenclature"] = "ТаксыАвиабилета";
                $servicetax["NameFees"] = trim($line[1]);


                $servicetax["price"] = (float)substr($line[0], 3);
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

                $fees[] = $service["Synh"]; //Это для создания списка такс
            }
        }

        if ($vat_amount != 0) {
            $services[count($services) - 1]["amountVAT"] += $vat_amount;
            $services[count($services) - 1]["amountVAT"] += $vat_amount;
        }

        return ["Fees" => $fees, "vatinfo" => ["Rate" => $vat_rate, "Amount" => $vat_amount_full, "Price" => $vat_price]];
    }

    private function GetSegmentsInfo($tiket, &$services)
    {
        $segments = [];

        $min_date = strtotime("+5 years", time());
        $max_date = 0;

        $min_port = "";
        $max_port = "";

        $min_place = "";
        $max_place = "";

        $RouteShortened = [];
        $Route = [];

        foreach ($tiket["segments"] as $segmentjson) {

            if ($this->debugclass) {
                //var_dump(strpos($segmentjson["c0"], "EMD"));
            }

            if (($segmentjson["c5"] == "VOID") || (is_int(strpos($segmentjson["c0"], "EMD")))) {
                //
            } else {

                $service = $this->get_empty_v3();

                $servicesegment = [];
                $servicesegment["nomenclature"] = "СегментАвиабилета";

                $DepartureCode = mb_substr($segmentjson["c1"], 4);
                $servicesegment["DepartureCode"] = $DepartureCode;
                $this->BuildRoute($RouteShortened, $servicesegment["DepartureCode"]);

                $ArrivalCode = $segmentjson["c3"];
                $servicesegment["ArrivalCode"] = $ArrivalCode;
                $this->BuildRoute($RouteShortened, $servicesegment["ArrivalCode"]);

                $servicesegment["PlaceDeparture"] = $DepartureCode;
                $servicesegment["CityDeparture"] = $DepartureCode;
//                $res = $this->Catalogs->getCatalogItems("Propertys", ["value" => $DepartureCode]);
//                if ($res) {
//                    $idplace = $res[0]["place"];
//                    $res = $this->Catalogs->getCatalogItems("Places", ["id" => $idplace]);
//                    if ($res) {
//                        $servicesegment["PlaceDeparture"] = $res[0]["place"];
//
//                        $findCity = true;
//                        do {
//                            if ($res[0]["type"] == "Город") $servicesegment["CityDeparture"] = $res[0]["place"];
//                            if (($res[0]["parent"] == 0) || ($res[0]["type"] == "Город")) $findCity = false;
//                            $res = $this->Catalogs->getCatalogItems("Places", ["id" => $res[0]["parent"]]);
//                        } while ($findCity);
//                    }
//                }
                $this->BuildRoute($Route, $servicesegment["CityDeparture"]);

                $servicesegment["PlaceArrival"] = $ArrivalCode;
                $servicesegment["CityArrival"] = $ArrivalCode;
//                $res = $this->Catalogs->getCatalogItems("Propertys", ["value" => $ArrivalCode]);
//                if ($res) {
//                    $idplace = $res[0]["place"];
//                    $res = $this->Catalogs->getCatalogItems("Places", ["id" => $idplace]);
//                    if ($res) {
//                        $servicesegment["PlaceArrival"] = $res[0]["place"];
//
//                        $findCity = true;
//                        do {
//                            if ($res[0]["type"] == "Город") $servicesegment["CityArrival"] = $res[0]["place"];
//                            if (($res[0]["parent"] == 0) || ($res[0]["type"] == "Город")) $findCity = false;
//                            $res = $this->Catalogs->getCatalogItems("Places", ["id" => $res[0]["parent"]]);
//                        } while ($findCity);
//                    }
//                }
                $this->BuildRoute($Route, $servicesegment["CityArrival"]);


                if ($this->debugclass) {
                    //print_r($segmentjson);
                }

                $tt = explode(" ", $segmentjson["c5"]);

                try {
                    $servicesegment["Depart"] = $tt[7];
                    $format = "dMHi";
                    $dateDepart = DateTime::createFromFormat($format, $servicesegment["Depart"]);
                    $servicesegment["Depart"] = $dateDepart->format("YmdHis"); //!!!!!
                } catch (Exception $e) {
                    $servicesegment["Depart"] = "";
                }

                if ($min_date > strtotime($servicesegment["Depart"])) {
                    $min_date = strtotime($servicesegment["Depart"]);
                    $min_port = $DepartureCode;
                    $min_place = $servicesegment["CityDeparture"];
                }


                try {
                    $servicesegment["Arrival"] = $tt[9] . $tt[8];
                    $format = "dMHi";
                    $dateArrival = DateTime::createFromFormat($format, $servicesegment["Arrival"]);
                    $servicesegment["Arrival"] = $dateArrival->format("YmdHis"); //!!!!!
                } catch (Exception $e) {
                    $servicesegment["Arrival"] = "";
                }

                if ($max_date < strtotime($servicesegment["Arrival"])) {
                    $max_date = strtotime($servicesegment["Arrival"]);
                    $max_port = $ArrivalCode;
                    $max_place = $servicesegment["CityArrival"];
                }

                $servicesegment["ServiceStartDate"] = $servicesegment["Depart"];
                $servicesegment["ServiceEndDate"] = $servicesegment["Arrival"];

                $diff = strtotime($servicesegment["ServiceEndDate"]) - strtotime($servicesegment["ServiceStartDate"]);
                $servicesegment["TravelTime"] = round(abs($diff) / 60);

                $servicesegment["supplier"] = $this->supplier;
                $servicesegment["Supplier"] = $this->supplier;

                $service = array_merge($service, $servicesegment);

                $service["Synh"] = md5(json_encode($servicesegment, JSON_UNESCAPED_UNICODE));
                $service["MD5SourceFile"] = $service["Synh"];
                $service["date"] = $servicesegment["ServiceStartDate"];

                if (!in_array($service["Synh"], $this->allsegments)) {
                    $this->allsegments[] = $service["Synh"];
                    $services[] = $service;
                }
                $segments[] = $service["Synh"]; //Это для создания списка такс
            }
        }

        $RouteStr = implode(" - ", $Route);
        $RouteShortenedStr = implode(" - ", $RouteShortened);

        return ["Segments" => $segments, "Route" => $RouteStr, "RouteShortened" => $RouteShortenedStr, "Depart" => ["date" => $max_date, "port" => $max_port, "place" => $max_place], "Arrival" => ["date" => $min_date, "port" => $min_port, "place" => $min_place]];
    }


    private function integer($param)
    {
        $result = 0;
        if (preg_match_all("([\d]+.[\d]+)", $param, $out)) {
            $result = $out[0][0];
        }
        return (float)$result;
    }

    private function VATS($param)
    {
        $result = [];
        if (preg_match_all('/VAT([\d]+)%=([\d]+\.[\d]+)/', $param, $out)) {
            foreach ($out[1] as $key => $value) {
                $result[$value] = (float)$out[2][$key];
            }
        }
        return $result;
    }

    private function FEVATS($param)
    {
        $result = [];
        if (preg_match_all('/VAT([\d]+\.[\d]+)/', $param, $out)) {
            foreach ($out[1] as $key => $value) {
                $result["0"] = (float)$value;
            }
        }
        //print_r($result);

        return $result;
    }

    private function collectTaxes($tiket, $services)
    {
        $vatsummtax = 0;
        if ($tiket["Carrier"] == "SU") {
            foreach ($services as $service) {
                if ($service["nomenclature"] == "ТаксыАвиабилета") {
                    if (($service["NameFees"] == "RI") || ($service["NameFees"] == "XT") || ($service["NameFees"] == "YR")) {
                        foreach ($tiket["Fees"] as $fee) {
                            if ($fee == $service["Synh"]) {
                                $vatsummtax += $service["price"];
                            }
                        }
                    }
                }
            }
        }
        return $vatsummtax;
    }

    private function createVATS($tiket, $service, &$services, $VATinfo)
    {
        //СборАвиаперевозчика 18%
        //СборГражданскойВоздушнойАвиации БезНДС
        $serviceblank = $service;
        $serviceblank["Synh"] = "";

        foreach ($VATinfo as $key => $value) {
            if ($value["Amount"] > 0) {
                $serviceblank["price"] = $value["Amount"];
                $serviceblank["VATrate"] = (int)$key + 100;
                $serviceblank["amountVAT"] = $value["VATAmount"];
                $serviceblank["amount"] = $value["Amount"];

                $serviceblank["pricecustomer"] = $value["Amount"];
                $serviceblank["VATratecustomer"] = (int)$key + 100;
                $serviceblank["amountVATcustomer"] = $value["VATAmount"];
                $serviceblank["amountclient"] = $value["Amount"];

                $serviceblank["ApplicationService"] = $service["Synh"];
                if ($key == "20") {
                    if (($service["TypeOfTicket"] == "R") || ($service["TypeOfTicket"] == "V")) {
                        $serviceblank["nomenclature"] = "ВозвратСборАвиаперевозчика";
                    } else {
                        $serviceblank["nomenclature"] = "СборАвиаперевозчика";
                    }
                } elseif ($key == "-1") {
                    if (($service["TypeOfTicket"] == "R") || ($service["TypeOfTicket"] == "V")) {
                        $serviceblank["nomenclature"] = "ВозвратСбораГражданскойВоздушнойАвиации";
                    } else {
                        $serviceblank["nomenclature"] = "СборГражданскойВоздушнойАвиации";
                    }
                }
                $serviceblank["Synh"] = $serviceblank["nomenclature"] . " " . $service["Synh"];

                $serviceblank["ownerservice"] = $service["Synh"];
                $serviceblank["attachedto"] = $service["Synh"];
                $serviceblank["TypeThisService"] = "Загруженная";

                $services[] = $serviceblank;
            }
        }
    }


    private function PenaltiService($tiket, $service, &$services)
    {
        $serviceblank = $service;
        if ($service["TypeOfTicket"] == "R") {
            $serviceblank["nomenclature"] = "ШтрафЗаВозвратАвиабилета";
        } elseif ($service["TypeOfTicket"] == "E") {
            $serviceblank["nomenclature"] = "ШтрафЗаОбменАвиабилета";
        }
        $serviceblank["Synh"] = $serviceblank["nomenclature"] . " " . $service["Synh"];

        $serviceblank["price"] = $service["AmountOfPenalty"];
        $serviceblank["VATrate"] = -1;
        $serviceblank["amountVAT"] = 0;
        $serviceblank["amount"] = $service["AmountOfPenalty"];

        $serviceblank["pricecustomer"] = $service["AmountOfPenalty"];
        $serviceblank["VATratecustomer"] = -1;
        $serviceblank["amountVATcustomer"] = 0;
        $serviceblank["amountclient"] = $service["AmountOfPenalty"];

        $serviceblank["ownerservice"] = $service["Synh"];
        $serviceblank["ApplicationService"] = $service["Synh"];
        $serviceblank["TypeThisService"] = "Загруженная";

        $services[] = $serviceblank;
    }

    private function v3($text, $v1)
    {
        $jsonv1 = $v1["json"];
        if ($this->debugclass) {
            //print_r($jsonv1);
        }

        $typesource = "tiket";
        if ($this->DTV($jsonv1, ["r1", "c1"]) == "7D") {
            $typesource = "mco";
        }

        $services = [];
        $serviceblank = $this->get_empty_v3();

        $CTiket = $this->DTV($jsonv1, ["C", 0, "c0"]);
        $CTiketA = explode("/", $CTiket);
        $CTiketA = explode("-", $CTiketA[1]);
        $CTiket = $CTiketA[0];
        $serviceblank["manager"] = trim($CTiket);

        $serviceblank["supplier"] = $this->supplier;
        $serviceblank["Supplier"] = $this->supplier;

        $AIRLINES = $this->DTV($jsonv1, ["A", 0, "c1"]);
        $AIRLINESar = explode(" ", $AIRLINES);
        $serviceblank["Carrier"] = $this->DTV($AIRLINESar, [0]);
        $serviceblank["CarrierContractor"] = $serviceblank["Carrier"];

        $ReservationNumber = "";
        $c0 =$this->DTV($jsonv1, ["MUC", 0, "c0"]);
        $exp = explode(" ", $c0);
        if (isset($exp[1])) {
            $ReservationNumber = substr($exp[1], 0, 6);
        }

        $serviceblank["ReservationNumber"] = $ReservationNumber;

        //$serviceblank["ReservationNumber"] = $this->DTV($jsonv1, ["MUC", 0, "c31"]);

        if ($typesource == "tiket") {
            /* СЕГМЕНТЫ */

            $Segments = $this->GetSegmentsInfo($jsonv1, $services);

            $serviceblank["Segments"] = $Segments["Segments"]; //Сегменты

            $serviceblank["Depart"] = date("YmdHis", $Segments["Arrival"]["date"]);
            $serviceblank["Arrival"] = date("YmdHis", $Segments["Depart"]["date"]);

            $serviceblank["ArrivalCode"] = $Segments["Arrival"]["port"];
            $serviceblank["DepartureCode"] = $Segments["Depart"]["port"];

            $serviceblank["PlaceArrival"] = $Segments["Arrival"]["place"];
            $serviceblank["PlaceDeparture"] = $Segments["Depart"]["place"];

            $serviceblank["CityArrival"] = $Segments["Arrival"]["place"];
            $serviceblank["CityDeparture"] = $Segments["Depart"]["place"];

            $serviceblank["ServiceStartDate"] = $serviceblank["Depart"];
            $serviceblank["ServiceEndDate"] = $serviceblank["Arrival"];

            $diff = strtotime($serviceblank["ServiceEndDate"]) - strtotime($serviceblank["ServiceStartDate"]);
            $serviceblank["TravelTime"] = round(abs($diff) / 60 / 60);


            $serviceblank["Route"] = $Segments["Route"];
            $serviceblank["RouteShortened"] = $Segments["RouteShortened"];
        }

        /* ТАКСЫ */
        $fee = $this->GetFeeInfo($jsonv1, $services);
        $serviceblank["Fees"] = $fee["Fees"];


        $serviceblank["nomenclature"] = "Авиабилет";

        $RTiket = $this->DTV($jsonv1, ["r1", "c1"]);

        $ETitek = $this->DTV($jsonv1, ["FH", 0, "c0"]);
        $ETitek2 = $this->DTV($jsonv1, ["FO", 0, "c0"]);
        $TypeOfTicket = $this->DTV($jsonv1, ["r2", "c2"]);

        if ($TypeOfTicket == "" && $ETitek == "" && $ETitek2 == "") {
            $serviceblank["TypeOfTicket"] = "S";
        } elseif (mb_substr($TypeOfTicket, 0, 4) == "VOID") {
            $serviceblank["TypeOfTicket"] = "V";
            $serviceblank["nomenclature"] = "ОтменаАвиабилета";

        } elseif (($ETitek != "") || ($ETitek2 != "")) {
            $serviceblank["TypeOfTicket"] = "E";
            $serviceblank["nomenclature"] = "ОбменАвиабилета";

        }

        if ($RTiket == "RF") {
            $serviceblank["TypeOfTicket"] = "R";
            $serviceblank["nomenclature"] = "ВозвратАвиабилета";

        }
        if ($RTiket == "7D") {
            $serviceblank["TypeOfTicket"] = "S";
            $serviceblank["nomenclature"] = "ШтрафЗаОбменАвиабилета";
        }

        $price = 0;
        if ($serviceblank["TypeOfTicket"] == "R") {
            //Сумма штрафа
            $AmountOfPenalty = $this->DTV($jsonv1, ["RFD", "c8"]);
            $serviceblank["AmountOfPenalty"] = (float)$AmountOfPenalty;

            $summref = (float)$this->DTV($jsonv1, ["RFD", "c12"]);
            $summam = (float)$this->DTV($jsonv1, ["RFD", "c8"]);

            $price = -1 * ($summref + $summam);

        } elseif (($serviceblank["TypeOfTicket"] == "S") || ($serviceblank["TypeOfTicket"] == "E")) {

            $price = $this->DTV($jsonv1, ["K", 0, "c12"]);
            if ($price == "") {
                $price = 0;
            }
            $price = $this->integer($price);

            if (isset($jsonv1["ATC"])) {

                //$Eprice = $this->DTV($jsonv1, ["ATC", 0, "c2"]);
                $Eprice = $this->DTV($jsonv1, ["ATC", 0, "c8"]);
                if ($this->debugclass){
                    print_r($this->DTV($jsonv1, ["ATC"]));
                }

                $Eprice = $this->integer($Eprice);
                if ($Eprice > 0) {
                    //$AmountOfPenalty = $price - $Eprice;
                    $serviceblank["AmountOfPenalty"] = $Eprice;
                }
            }
        }

        if ($price - $serviceblank["AmountOfPenalty"] < 0) {
            $pricecustomer = $price;
        } else {
            $pricecustomer = $price - $serviceblank["AmountOfPenalty"];
        }

        $serviceblank["RIZ"] = $this->DTV($jsonv1, ["RIZ"]);

        if ($typesource == "tiket") {
            foreach ($jsonv1["T"] as $keytiket => $ntiket) {
                $service = $serviceblank;

                $service["TicketNumber"] = mb_substr($ntiket["c0"], 1);
                $service["Synh"] = $service["TypeOfTicket"] . $service["TicketNumber"];

                if ($service["TypeOfTicket"] != "S") {
                    $service["TicketSales"] = $service["TicketNumber"];

                    if ($service["TypeOfTicket"] == "E") {
                        $ETitek = $this->DTV($jsonv1, ["FH", 0, "c0"]);
                        if ($ETitek == "") {
                            $ETitek = $this->DTV($jsonv1, ["FO", 0, "c0"]);
                            $service["TicketSales"] = substr($ETitek, 0, 14);
                        } else {
                            //Нет примера
                        }
                    }
                }


                if ($this->debugclass) {
                    print_r($jsonv1["dates"]);
                }

                try {
                    $service["date"] = $this->DTV($jsonv1, ["dates", 0, "c2"], "", "ymd"); //$segmentjson[""].$segmentjson["ARRTIME"]."00";
                } catch (Exception $e) {
                    $service["date"] = "";
                }

                $service["price"] = $price;
                $service["amount"] = $service["price"];
                $service["VATrate"] = -2; //0% ставка НДС 0 -не загружается

                $service["pricecustomer"] = $pricecustomer;
                $service["amountclient"] = $service["pricecustomer"];
                $service["VATratecustomer"] = -2; //0% ставка НДС




                $tarif = $this->DTV($jsonv1, ["K", 0, "c1"]);
                if ($tarif == "") {
                    $tarif = $this->DTV($jsonv1, ["K", 0, "c0"]);
                }
                $service["TariffAmount"] = $this->integer($tarif);

                $service["TourCode"] = $this->DTV($jsonv1, ["FT", 0, "c0"]);


                /* РАСЧЕТ НДС */
                $vatsumm = 0;
                $vatsinfo = "";
                $RIZ = $this->DTV($jsonv1, ["RIZ"]);
                for ($ind = 0; $ind < count($RIZ); $ind++) {
                    $RIZ1 = $this->DTV($jsonv1, ["RIZ", $ind, "c1"], false);
                    if ($RIZ1 === false) {

                    } else {
                        $vatsinfo = $this->DTV($jsonv1, ["RIZ", $ind, "c0"]);
                        break;
                    }
                }
                $res = $this->VATS($vatsinfo);
                foreach ($res as $key => $value) {
                    if ($key == "10") {
                        $service["VATAmount10"] = $value;
                        $service["AmountWithVAT10"] = round(($value / (int)$key) * (100 + (int)$key));
                        //$vatsumm += $service["AmountWithVAT10"];
                    } elseif ($key == "20") {
                        $service["VATAmount18"] = $value;
                        $service["AmountWithVAT18"] = round(($value / (int)$key) * (100 + (int)$key));
                        $vatsumm += $service["AmountWithVAT18"];
                    }
                }


                $incvatsumm = 0;
                $vatsinfo = $this->DTV($jsonv1, ["FE", 0, "c0"]);
                $res = $this->FEVATS($vatsinfo);
                foreach ($res as $key => $value) {

                    if ($service["VATAmount18"] > 0) {
                        $incvatsumm += $service["VATAmount18"];
                    }
                    if ($service["VATAmount10"] > 0) {
                        $incvatsumm += $service["VATAmount10"];
                    }

                    if (($incvatsumm < $value) && ($service["VATAmount18"] == 0)) {
                        $service["VATAmount18"] = $value;
                        $service["AmountWithVAT18"] = round(($value / (int)$key) * (100 + (int)$key));
                        $incvatsumm += $service["VATAmount18"];
                    }

                    if ($service["VATAmount10"] > 0) {
                        $incvatsumm += $service["VATAmount10"];
                    }
                    if (($incvatsumm < $value) && ($service["VATAmount10"] == 0)) {
                        $service["VATAmount10"] = $value;
                        $service["AmountWithVAT10"] = round(($value / (int)$key) * (100 + (int)$key));
                        $incvatsumm += $service["VATAmount10"];
                    }
                }


                $AmountExcludingVAT = $this->collectTaxes($service, $services);
                $service["AmountExcludingVAT"] = $AmountExcludingVAT;
                $vatsumm += $service["AmountExcludingVAT"];


                /* НДС */
                if ($service["VATAmount10"] > 0) {
                    $service["VATrate"] = 110;
                    $service["amountVAT"] = $service["VATAmount10"];

                    $service["VATratecustomer"] = 110;
                    $service["amountVATcustomer"] = $service["VATAmount10"];

                    $service["pricecustomer"] = $service["AmountWithVAT10"];
                    $service["amountclient"] = $service["AmountWithVAT10"];
                } else {

                    if ($vatsumm > 0) {
                        $service["pricecustomer"] = $service["pricecustomer"] - $vatsumm;
                        $service["amountclient"] = $service["pricecustomer"];

                    } elseif (($service["VATAmount10"] > 0) && ($service["TypeOfTicket"] != "E")) {
                        $service["pricecustomer"] = $service["AmountWithVAT10"];
                        $service["amountclient"] = $service["AmountWithVAT10"];
                    }
                }

                $service["AmountServices"] = $service["amountclient"];

                if ((($service["price"] - $service["AmountOfPenalty"]) == 0) && ($service["AmountOfPenalty"] == $service["price"])) {
                    //Вся сумма билета это штраф
                    $service["price"] = 0;
                    $service["amount"] = $service["price"];
                }

                if ($service["amount"] == 0) {
                    $service["amountVAT"] = 0;
                    //Обнулим всё у клиента, если сумма поставщика = 0
                    $service["pricecustomer"] = 0;
                    $service["amountclient"] = 0;
                    $service["amountVATcustomer"] = 0;

                    $service["VATAmount10"] = 0;
                    $service["AmountWithVAT10"] = 0;

                    $service["AmountServices"] = 0;

                }


                /* КОМАНДИРУЕМЫЕ */
                $passagers = $jsonv1["passager"];
                $passager = $passagers[$keytiket];

                $pasars = explode(" ", $this->DTV($passager, ["c1"]));
                $passagerinfo = $pasars[0];
                $ars = explode("/", $passagerinfo);

                $Seconded = [];
                $Seconded["FirstNameLatin"] = ucfirst(strtolower($this->DTV($ars, [1])));
                $Seconded["LastNameLatin"] = $this->DTV($ars, [0]);
                $Seconded["SurNameLatin"] = "";
                preg_match('/\D+/', $Seconded["LastNameLatin"], $matches, PREG_OFFSET_CAPTURE);
                $Seconded["LastNameLatin"] = ucfirst(strtolower($matches[0][0]));

                $Seconded["FirstName"] = $Seconded["FirstNameLatin"];
                $Seconded["LastName"] = $Seconded["LastNameLatin"];
                $Seconded["SurName"] = $Seconded["SurNameLatin"];

                $Seconded["DocumentNumber"] = "";
                $Seconded["DocType"] = "";
                $Seconded["BirthDay"] = "";
                $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
                $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);
                $service["TuristName"] = mb_strtoupper($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"]);
                $service["Seconded"][] = $Seconded;




                if ($service["TypeOfTicket"] == "V") {
                    $service["methods"] = ["beforeload" => "ОтменитьУслугиПродажиИОтмены"];
                } elseif ($service["TypeOfTicket"] == "R") {
                    $service["methods"] = ["afterload" => "СкопироватьБилетПродажи"];
                }

                $service["SegmentCount"] = count($service["Segments"]);

                $services[] = $service;



                /* ШТРАФЫ */
                if ($this->integer($service["AmountOfPenalty"]) != 0) {
                    $this->PenaltiService($jsonv1, $service, $services);
                }

                $this->createVATS($jsonv1, $service, $services, [
                    "-1" => [
                        "Amount" => $service["AmountExcludingVAT"],
                        "VATAmount" => 0
                    ], "20" => [
                        "Amount" => $service["AmountWithVAT18"],
                        "VATAmount" => $service["VATAmount18"]
                    ]]);
            }
        }
        else {

            foreach ($jsonv1["TMCD"] as $keytiket => $ntiket) {
                $service = $serviceblank;

                $service["TicketNumber"] = $ntiket["c0"];//mb_substr(, 0);
                $service["Synh"] = $service["TypeOfTicket"] . $service["TicketNumber"];

                if ($service["nomenclature"] == "ШтрафЗаОбменАвиабилета") {

                    $typemco = $this->DTV($jsonv1, ["U", 0, "c17"], "");
                    if ($typemco == "PRE RESERVED SEAT ASSIGN") {
                        //Это Сбор за бронирование места в салоне самолета
                        $service["nomenclature"] = "ДополнительныеУслугиКАвиабилету";
                        $service["TypeEMD"] = "Сбор за бронирование места в салоне самолета";
                        $service["TicketSales"] = "S" . mb_substr($ntiket["c1"], 0, 3) . "-" . mb_substr($ntiket["c1"], 3);
                    } else {
                        $service["TicketSales"] = "E" . mb_substr($ntiket["c1"], 0, 3) . "-" . mb_substr($ntiket["c1"], 3);
                    }

                    $service["ownerservice"] = $service["TicketSales"];
                    $service["ApplicationService"] = $service["TicketSales"];

                    $service["TypeThisService"] = "Загруженная";


                }


                try {
                    $service["date"] = $this->DTV($jsonv1, ["dates", 0, "c0"]) . "000000"; //$segmentjson[""].$segmentjson["ARRTIME"]."00";
                    $format = "ymdHis";
                    $dateArrival = DateTime::createFromFormat($format, $service["date"]);
                    $service["date"] = $dateArrival->format("YmdHis");
                } catch (Exception $e) {
                    $service["date"] = "";
                }


                $service["price"] = $price;
                $service["amount"] = $service["price"];
                $service["VATrate"] = -1; //0% ставка НДС 0 -не загружается

                if ($this->debugclass) {
                    print_r($pricecustomer . "\r\n");
                }

                $service["pricecustomer"] = $pricecustomer;
                $service["amountclient"] = $service["pricecustomer"];
                $service["VATratecustomer"] = -1; //0% ставка НДС

                $tarif = $this->DTV($jsonv1, ["K", 0, "c1"]);
                if ($tarif == "") {
                    $tarif = $this->DTV($jsonv1, ["K", 0, "c0"]);
                }
                $service["TariffAmount"] = $this->integer($tarif);

                $vatsumm = 0;
                $vatsinfo = $this->DTV($jsonv1, ["RIZ", 0, "c0"]);
                $res = $this->VATS($vatsinfo);
                foreach ($res as $key => $value) {
                    if ($key == "10") {
                        $service["VATAmount10"] = $value;
                        $service["AmountWithVAT10"] = round(($value / (int)$key) * (100 + (int)$key));
                        //$vatsumm += $service["AmountWithVAT10"];
                    } elseif ($key == "20") {
                        $service["VATAmount18"] = $value;
                        $service["AmountWithVAT18"] = round(($value / (int)$key) * (100 + (int)$key));
                        $vatsumm += $service["AmountWithVAT18"];
                    }
                }

                $vatsumm = 0;
                $vatsinfo = $this->DTV($jsonv1, ["FE", 0, "c0"]);
                $res = $this->FEVATS($vatsinfo);
                foreach ($res as $key => $value) {
                    if ($key == "10") {
                        $service["VATAmount10"] = $value;
                        $service["AmountWithVAT10"] = round(($value / (int)$key) * (100 + (int)$key));
                        //$vatsumm += $service["AmountWithVAT10"];
                    } elseif ($key == "20") {
                        $service["VATAmount18"] = $value;
                        $service["AmountWithVAT18"] = round(($value / (int)$key) * (100 + (int)$key));
                        $vatsumm += $service["AmountWithVAT18"];
                    }
                }

                /* КОМАНДИРУЕМЫЕ */
                $passagers = $jsonv1["passager"];
                $passager = $passagers[$keytiket];

                $pasars = explode(" ", $this->DTV($passager, ["c1"]));
                $passagerinfo = $pasars[0];
                $ars = explode("/", $passagerinfo);

                $Seconded = [];
                $Seconded["FirstNameLatin"] = ucfirst(strtolower($this->DTV($ars, [1])));
                $Seconded["LastNameLatin"] = $this->DTV($ars, [0]);
                $Seconded["SurNameLatin"] = "";
                preg_match('/\D+/', $Seconded["LastNameLatin"], $matches, PREG_OFFSET_CAPTURE);
                $Seconded["LastNameLatin"] = ucfirst(strtolower($matches[0][0]));

                $Seconded["FirstName"] = $Seconded["FirstNameLatin"];
                $Seconded["LastName"] = $Seconded["LastNameLatin"];
                $Seconded["SurName"] = $Seconded["SurNameLatin"];

                $Seconded["DocumentNumber"] = "";
                $Seconded["DocType"] = "";
                $Seconded["BirthDay"] = "";
                $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
                $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);
                //$Seconded["NameRus"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);

                $service["TuristName"] = mb_strtoupper($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"]);
                $service["Seconded"][] = $Seconded;


                $AmountExcludingVAT = $this->collectTaxes($service, $services);
                $service["AmountExcludingVAT"] = $AmountExcludingVAT;
                $vatsumm += $service["AmountExcludingVAT"];

                /* НДС */
                if ($service["VATAmount10"] > 0) {
                    $service["VATrate"] = 110;
                    $service["amountVAT"] = $service["VATAmount10"];

                    $service["VATratecustomer"] = 110;
                    $service["amountVATcustomer"] = $service["VATAmount10"];
                }

                if ($this->debugclass) {
                    print_r($service["pricecustomer"] . "\r\n");
                }

                if ($vatsumm > 0) {
                    $service["pricecustomer"] = $service["pricecustomer"] - $vatsumm;
                    $service["amountclient"] = $service["pricecustomer"];


                } elseif ($service["VATAmount10"] > 0) {
                    $service["pricecustomer"] = $service["AmountWithVAT10"];
                    $service["amountclient"] = $service["AmountWithVAT10"];
                }
                if ($this->debugclass) {
                    print_r($service["pricecustomer"] . "\r\n");
                }

                $service["AmountServices"] = $service["amountclient"];

                if ($service["TypeOfTicket"] == "V") {
                    $service["methods"] = ["beforeload" => "ОтменитьУслугиПродажиИОтмены"];
                } elseif ($service["TypeOfTicket"] == "R") {
                    $service["methods"] = ["afterload" => "СкопироватьБилетПродажи"];
                }

                $services[] = $service;

                /* ШТРАФЫ */
                if ($this->integer($service["AmountOfPenalty"]) != 0) {
                    $this->PenaltiService($jsonv1, $service, $services);
                }

                $this->createVATS($jsonv1, $service, $services, [
                    "-1" => [
                        "Amount" => $service["AmountExcludingVAT"],
                        "VATAmount" => 0
                    ], "20" => [
                        "Amount" => $service["AmountWithVAT18"],
                        "VATAmount" => $service["VATAmount18"]
                    ]]);
            }

        }


        $jsonv3["services"] = $services;

        return $jsonv3;
    }


    public function getinfo($params)
    {
        $result = ["result" => false];

        if ($this->debugclass) {
            echo "DEBUG!!!" . "\r\n";
        }

        if ($this->Auth->userauth()) {
        //if (true) {
            if ($params[1] == "v3") {
                $result["result"] = true;

                $text = $this->phpinput;
                $Parser = new Parser($this->metod);
                $Parser->SetUseParser("S7", md5($text), $this->Auth->getuserid());

                $result["json"] = $this->v1($text);
                $result["jsonv3"] = $this->v3($text, $result["json"]);

            } else {
                $result["error"] = "Not for this version";
            }
        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }


}
