<?php

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 21/02/2019
 * Time: 15:10
 */
class Myagent extends ex_classlite
{
    private $metod;
    private $gds;
    private $classname;
    private $connectionInfo;
    private $supplier;

    private $Auth;
    private $token;

    public function CreateDB()
    {
        $info["myagent_token"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "login" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "token" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "datecreate" => array('type' => 'datetime', 'null' => 'NOT NULL')
        );

        $info["myagent_tiketload"] = array(
            "id" => array('type' => 'int(15)', 'null' => 'NOT NULL', 'inc' => true),
            "md5" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "datecreate" => array('type' => 'datetime', 'null' => 'NOT NULL')
        );

        $this->create($this->connectionInfo['database_type'], $info);
    }


    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        parent::__construct();
        $this->metod = $metod;

        if (isset($this->POST["connector"])) {
            $this->gds = $this->POST;
        } else {
            $this->gds = $_SESSION["i4b"][$this->classname];
        }

        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем

        $this->debugclass = $debug;
        $this->Auth = new Auth();

        $this->supplier = ["INN" => "7714352628", "KPP" => "773001001", "Name" => "MyAgent"];
    }

    private function AuthMyAgent()
    {
        $result = false;

        $url = $this->gds["serveraddr"] . "/user/login"; //"https://api4-ma-dev-api.crpo.su/api/user/login"; //
        $login = $this->gds["login"];
        $password = $this->gds["password"];

        $insert = false;
        $tokenline = $this->get("myagent_token", ["id", "token", "datecreate"], ["login" => $login]);

        if ($tokenline == false) {
            $insert = true;
            $leftminuts = 90;
        } else {
            $this->token = $this->DTV($tokenline, ["token"]);

            $dateNow = date("YmdHis", time());
            $datecreate = $this->DTV($tokenline, ["datecreate"], "", "Y-m-d H:i:s");
            $diff = strtotime($dateNow) - strtotime($datecreate);
            $leftminuts = round(abs($diff) / 60);
        }

        if ($leftminuts > 59) {
            $res = $this->http_c_post($url, ["login" => $login, "password" => $password]);
            $contenttext = $res["content"];
            $content = json_decode($contenttext, true);

            if ($content["success"] == true) {
                $result = true;
                $this->token = $this->DTV($content, ["data", "auth_token"]);
                $data = [
                    "login" => $login,
                    "token" => $this->token,
                    "datecreate" => date("Y-m-d H:i:s", time())
                ];
                if ($insert) {
                    $this->insert("myagent_token", $data);
                } else {
                    $this->update("myagent_token", $data, ["login" => $login]);
                }
            } else {
                $this->delete("myagent_token", ["login" => $login]);
            }
        } else {
            //var_dump("old token");
            $result = true;
        }

        return $result;
    }


    private function getmaorder($billing_number, $sig)
    {
        $url = $this->gds["serveraddr"] . "/avia/book-info?";
        $query = [
            "lang" => "ru",
            "auth_key" => $this->token,
            "billing_number" => $billing_number
        ];
        $encoded = "";
        if (is_array($query)) {
            foreach ($query as $name => $value) {
                $encoded .= rawurlencode($name) . '=' . rawurlencode($value) . '&';
            }
            $encoded = substr($encoded, 0, strlen($encoded) - 1);
        }

        $postdata = http_build_query($query);
        $opts = array('https' =>
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );
        $context = stream_context_create($opts);
        $res = file_get_contents($url . $encoded, false, $context);

//        print_r("\r\n".$url . $encoded."\r\n");
//        print_r($postdata."\r\n");
//        print_r($res."\r\n");


        return $res;
    }

    private function getservicestandart(&$service, $book, $ticket, $passenger)
    {

        $status = $this->DTV($book, ["order", "status", "sign"]);

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

        $number = $this->DTV($ticket, ["locator"]) . $this->DTV($passenger, ["id"]);
        if ($service["TypeOfTicket"] == "S") {
            $n = $this->DTV($passenger, ["ticketData", "number"]);
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

        $service["supplier"] = $this->supplier;
        $service["Supplier"] = $this->supplier;

    }

    private function AddPassenger(&$service, $book, $ticket, $passenger)
    {

        /* КОМАНДИРУЕМЫЕ */
        $Seconded = [];
        $Seconded["FirstNameLatin"] = ucfirst(strtolower($this->DTV($passenger, ["name", "first"])));
        $Seconded["LastNameLatin"] = ucfirst(strtolower($this->DTV($passenger, ["name", "last"])));
        $Seconded["SurNameLatin"] = ucfirst(strtolower($this->DTV($passenger, ["name", "middle"])));

        $Seconded["FirstName"] = $Seconded["FirstNameLatin"];
        $Seconded["LastName"] = $Seconded["LastNameLatin"];
        $Seconded["SurName"] = $Seconded["SurNameLatin"];

        $Seconded["DocumentNumber"] = $this->DTV($passenger, ["document", "num"]);
        $Seconded["DocType"] = $this->DTV($passenger, ["document", "type"]);
        $Seconded["BirthDay"] = $this->DTV($passenger, ["birthdate"], "", "d.m.Y");

        $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
        $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);


        if (!is_array($service["seconded"])) {
            $service["seconded"] = [];
        };
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
        $segments = $this->DTV($book, ["flight", "segments"]);
        foreach ($segments as $segment) {
            $segmentservice = $this->get_empty_v3();

            $segmentservice["nomenclature"] = "СегментАвиабилета";
            $segmentservice["supplier"] = $this->supplier;
            $segmentservice["Supplier"] = $this->supplier;

            $service["Carrier"] = $this->DTV($segment, ["carrier", "code"]);
            $service["CarrierContractor"] = $service["Carrier"];

            $DepartureCode = $this->DTV($segment, ["dep", "airport", "code"]);
            $segmentservice["DepartureCode"] = $DepartureCode;

            $ArrivalCode = $this->DTV($segment, ["arr", "airport", "code"]);
            $segmentservice["ArrivalCode"] = $ArrivalCode;

            $segmentservice["CityDeparture"] = $this->DTV($segment, ["dep", "city", "title"]);
            $segmentservice["CityArrival"] = $this->DTV($segment, ["arr", "city", "title"]);

            $segmentservice["PlaceDeparture"] = $DepartureCode;
            $segmentservice["PlaceArrival"] = $ArrivalCode;

            $segmentservice["Depart"] = $this->DTV($segment, ["dep", "datetime"], "", "d.m.Y H:i:s");
            $segmentservice["Arrival"] = $this->DTV($segment, ["arr", "datetime"], "", "d.m.Y H:i:s");

            $segmentservice["ServiceStartDate"] = $segmentservice["Depart"];
            $segmentservice["ServiceEndDate"] = $segmentservice["Arrival"];

            $diff = strtotime($segmentservice["ServiceEndDate"]) - strtotime($segmentservice["ServiceStartDate"]);
            $segmentservice["TravelTime"] = round(abs($diff) / 60);

            $service["FareBases"] = $this->DTV($segment, ["fare_code"]);

            $segmentservice["Synh"] = md5(json_encode($segmentservice, JSON_UNESCAPED_UNICODE));
            $segmentservice["MD5SourceFile"] = $segmentservice["Synh"];

            $segmentservice["date"] = $segmentservice["Depart"];

            $segmentout[] = $segmentservice["Synh"];

            $services[] = $segmentservice;

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

    private function CreateAviaFee(&$services, $inservice)
    {

        if ($inservice["AmountWithVAT18"] > 0) {
            $service = $inservice;
            $service["nomenclature"] = "СборАвиаперевозчика";
            $service["Synh"] = $service["nomenclature"] . $inservice["Synh"];

            $service["ownerservice"] = $inservice["Synh"];
            $service["ApplicationService"] = $inservice["Synh"];
            $service["attachedto"] = $inservice["Synh"];

            $service["TicketNumber"] = "";
            $service["TypeOfTicket"] = "";
            $service["TypeThisService"] = "Загруженная";

            $service["pricecustomer"] = $inservice["AmountWithVAT18"];
            $service["amountclient"] = $inservice["AmountWithVAT18"];
            $service["amountVATcustomer"] = $inservice["VATAmount18"];;
            $service["VATratecustomer"] = 120;

            $service["price"] = $inservice["AmountWithVAT18"];
            $service["amount"] = $inservice["AmountWithVAT18"];
            $service["amountVAT"] = $inservice["VATAmount18"];;
            $service["VATrate"] = 120;

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


    private function v3avia($innerjson)
    {
        $services = [];

        $book = $this->DTV($innerjson, ["data", "book"]);
        foreach ($book["tickets"] as $ticket) {

            foreach ($ticket["passengers"] as $passenger) {
                $service = $this->get_empty_v3();

                $this->getservicestandart($service, $book, $ticket, $passenger);
                $this->AddPassenger($service, $book, $ticket, $passenger);

                $service["Segments"] = $this->AddSegments($services, $service, $book);

                $service["date"] = $this->DTV($book, ["order", "created"], "", "d.m.Y H:i:s");

                $indexvalue = 1;
                if (($service["TypeOfTicket"] == "V") || ($service["TypeOfTicket"] == "R")) {
                    $indexvalue = -1;
                }

                $count = count($ticket["passengers"]);
                $VendorFeeAmount = (float)$this->DTV($book, ["order", "price", "RUB", "fee"]) / $count;
                $service["VendorFeeAmount"] = $VendorFeeAmount;

                //$pricecustomer = (float) $this->DTV($book, ["order", "price", "RUB", "amount"]);
                //$pricecustomer = $pricecustomer - $VendorFeeAmount;
                //$amountclient = $pricecustomer;

                $passengerdetails = $this->DTV($book, ["passengers_price_details"]);

                foreach ($passengerdetails as $passengerdetail) {
                    if ($passengerdetail["key"] == $passenger["key"]) {
                        //Это цены именно этого пассажира

                        $service["price"] = $indexvalue * (int)$passengerdetail["ticket_price"];
                        $service["amount"] = $indexvalue * (int)$passengerdetail["ticket_price"];

                        if ($passengerdetail["taxes_amount"] > 0) {
                            $service["AmountWithVAT18"] = $indexvalue * (float)$passengerdetail["taxes_amount"];
                            $service["VATAmount18"] = $indexvalue * (float)$passengerdetail["vat"];
                        } else {
                            $service["amountVAT"] = $indexvalue * (float)$passengerdetail["vat"];
                            $service["VATratecustomer"] = -1;
                            if ($service["amountVAT"] > 0) {
                                $service["VATratecustomer"] = 100 + round((($service["amountclient"] - $service["amountVAT"]) / $service["amountVAT"]), 0);
                            }
                        }

                        $service["pricecustomer"] = $service["price"] - $VendorFeeAmount - $service["AmountWithVAT18"];
                        $service["amountclient"] = $service["amount"] - $VendorFeeAmount - $service["AmountWithVAT18"];
                        $service["amountVATcustomer"] = $service["amountVAT"];

                        $service["VATrate"] = $service["VATratecustomer"];

                        $service["AmountServices"] = $service["pricecustomer"];

                        $service["CommissionAmount"] = $passengerdetail["comsa"];
                    }
                }

                $services[] = $service;

                $this->CreateVendirFee($services, $service);
                $this->CreateAviaFee($services, $service);
            }
        }
        //print_r($services);
        return ["services" => $services];
    }


    public function getinfo($params)
    {
        $result = ["result" => false];

        if ($this->Auth->userauth()) {
            if ($params[1] == "v3") {

                $text = $this->phpinput;

                if ($this->debugclass) {
                    print_r("DEBUG MYAGET\r\n");
                }

                $Parser = new Parser($this->metod);
                $Parser->SetUseParser("Myagent", md5($text), $this->Auth->getuserid());

                $textjson = $this->phpinput;
                $json = json_decode($textjson, true);

                $billing_number = $json["billing_number"];
                $billing_signature = $json["billing_signature"];

                $auth = $this->AuthMyAgent();
                if ($auth) {

                    $resultorder = $this->getmaorder($billing_number, $billing_signature);

                    if ($this->debugclass) {
                        //print_r($resultorder);
                    }

                    $jsonorder = json_decode($resultorder, true);

                    $json = $this->v3avia($jsonorder);

                    $result = ["result" => true, "json" => $jsonorder, "jsonv3" => $json];


                } else {
                    $result["error"] = "Error auth myagent";
                }

            } else {
                $result["error"] = "Not for this version";
            }
        } else {
            $result["error"] = "Authorization fail";
        }

        return $result;
    }


//    public function savetiket($params, $name = 0)
//    {
//        $result = ["result" => false];
//        $res = parent::savetiket($params, $name); // TODO: Change the autogenerated stub
//
//        if ($res["result"]) {
//            $result = [
//                "status" => "success"
//            ];
//        }
//
//        return $result;
//    }
//

}