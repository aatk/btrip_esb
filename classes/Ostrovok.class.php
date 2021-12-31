<?php

class Ostrovok extends ex_classlite
{

    private $metod;
    private $gds;
    private $classname;

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

//    private function get_order($id)
//    {
//
//        $data = '{"format":"json","partner_order_id":"' . $id . '","lang":"ru"}';
//        $url = $this->gds["serveraddr"] . "api/b2b/v2/order/info?data=" . urlencode($data);
//        $res = $this->http_c_post($url, $data, [
//            "get",
//            "basicauth" => [
//                "username" => $this->gds["key_id"],
//                "password" => $this->gds["key"]
//            ]
//        ]);
//
//        return $res["content"];
//    }

//    public function loadorders($param1)
//    {
//        $result = ["result" => false];
//        if ($this->Auth->userauth()) {
//            //if (true) {
//
//            $serverid = $param1[1];
//            if ($serverid != "") {
//                $nowdate = date("Y-m-d H:i:s", time());
//
//                $maxdateorders = $this->max("returndoc", "lastdate", ["typedoc" => $this->classname]);
//                if ($maxdateorders == "") {
//                    $maxdateorders = date("Y-m-d", time() - (7 * 24 * 60 * 60));
//                    $this->insert("returndoc", ["lastdate" => $maxdateorders, "sever_id" => $serverid, "typedoc" => $this->classname]);
//                } else {
//                    $date = new DateTime($maxdateorders);
//                    $maxdateorders = $date->format('Y-m-d');
//                }
//
//                $data = '{"format":"json","created_from":"' . $maxdateorders . '","lang":"ru"}';
//                $url = $this->gds["serveraddr"] . "api/b2b/v2/order/list?data=" . urlencode($data);
//
//                $res = $this->http_c_post($url, $data, [
//                    "get",
//                    "basicauth" => [
//                        "username" => $this->gds["key_id"],
//                        "password" => $this->gds["key"]
//                    ]
//                ]);
//
//                $js = json_decode($res["content"], true);
//
//                $ordersid = [];
//                $orders = $this->DTV($js, ["result", "orders"]);
//                if (is_array($orders)) {
//                    foreach ($orders as $val) {
//                        $ordersid[] = $val["partner_order_id"];
//                    }
//                }
//
//                foreach ($ordersid as $id) {
//                    $order = $this->get_order($id);
//                    $this->phpinput = $order;
//                    $this->savetiket([], 1);
//
//                    //запишем транзакцию в лог
//                    $this->settransaction($id);
//                }
//
//
//                $this->update("returndoc", ["lastdate" => $nowdate], ["AND" => ["sever_id" => $serverid, "typedoc" => $this->classname]]);
//                $result = ["result" => true, "orders" => $ordersid];
//
//            } else {
//                $result["error"] = "Server id fail";
//            }
//
//        } else {
//            $result["error"] = "Authorization fail";
//        }
//
//        return $result;
//    }



    private function jv3($jsonv)
    {
        $services = [];
        $service = $this->get_empty_v3();

        $hotel_data = $this->DTV($jsonv, ["result", "hotel_data"]);

//        if ($this->debugclass) {
//            print_r($hotel_data);
//        }

        $service["CityArrival"] = $hotel_data["city"];
        $service["PlaceArrival"] = $hotel_data["city"];

        //$service["Arrival"] = $hotel_data["city"];

        //$hotel_data["country"];
        //$hotel_data["country_code"];
        $service["Latitude"] = $hotel_data["latitude"];
        $service["Longitude"] = $hotel_data["longitude"];

        $service["HotelName"] = $hotel_data["name"];

        $order_data = $this->DTV($jsonv, ["result", "order_data"]);

        $dd = $this->DTV($order_data, ["checkin_at"]); //"checkin_at": "2018-09-04",
        $tt = $this->DTV($order_data, ["checkin_time"]); //"checkin_time": "14:00",
        if (strlen($tt) < 6) {
            $tt .= ":00";
        }
        $service["ServiceStartDate"] = $this->DTV([$dd.$tt], [0], date("YmdHis"), "Y-m-d H:i:s");

//        try {
//            $format = "Y-m-d H:i:s";
//            $date = DateTime::createFromFormat($format, $dd . " " . $tt . ":00");
//            $service["ServiceStartDate"] = $date->format("YmdHis"); //!!!!!
//        } catch (Exception $e) {
//            $service["ServiceStartDate"] = "";
//        }

        $dd = $this->DTV($order_data, ["checkout_at"]); //"checkout_at": "2018-09-05",
        $tt = $this->DTV($order_data, ["checkout_time"]); //"checkout_time": "12:00",
        if (strlen($tt) < 6) {
            $tt .= ":00";
        }
        $service["ServiceEndDate"] = $this->DTV([$dd.$tt], [0], date("YmdHis"), "Y-m-d H:i:s");
//        try {
//            $format = "Y-m-d H:i";
//            $date = DateTime::createFromFormat($format, $dd . " " . $tt);
//            $service["ServiceEndDate"] = $date->format("YmdHis"); //!!!!!
//        } catch (Exception $e) {
//            $service["ServiceEndDate"] = "";
//        }

        $service["date"] = $service["ServiceEndDate"];


        $dd = $this->DTV($order_data, ["created_at"]); //"created_at": "2018-09-03T07:00:35",
        $service["creationdate"] = $this->DTV([$dd], [0], date("YmdHis"), "Y-m-d\TH:i:s");
//        try {
//            $format = "Y-m-d\TH:i:s";
//            $date = DateTime::createFromFormat($format, $dd);
//            $service["creationdate"] = $date->format("YmdHis"); //!!!!!
//        } catch (Exception $e) {
//            $service["creationdate"] = "";
//        }

        $diff = strtotime($service["ServiceEndDate"]) - strtotime($service["ServiceStartDate"]);
        $service["Night"] = round(abs($diff) / 60 / 60 / 24);

        //rate_data
        $rate_data = $this->DTV($jsonv, ["result", "rate_data"]);

        $author_room = $this->DTV($rate_data, ["room_name"]);
        $service["NumberTypeName"] = $author_room;


        $service["nomenclature"] = "Проживание";
        if ($this->DTV($order_data, ["status"]) == "completed") {
            $service["TypeOfTicket"] = "S";
        } else {
            $service["TypeOfTicket"] = "V";
            $service["nomenclature"] = "ОтменаПроживания";
        }

        $service["price"] = (float)$this->DTV($order_data, ["amount_sell"]);
        $service["amount"] = (float)$service["price"];

        $service["VATrate"] = -1;
        $service["amountVAT"] = (float)$this->DTV($order_data, ["amount_payable_vat"]);
        //$service["amountVAT"] = $service["price"] / (100+$VATrate) * $VATrate;
        if ($service["amountVAT"] > 0) {
            $service["VATrate"] = 120;
        }

        $service["amountclient"] = (float)$this->DTV($order_data, ["amount_sell_b2b2c"]);
        $service["pricecustomer"] = $service["amountclient"];

        $service["amountVATcustomer"] = 0;
        $service["VATratecustomer"] = $service["VATrate"];
        //$service["amountVATcustomer"] = (float)$this->DTV($order_data, ["amount_payable_vat"]);

        if ($service["VATrate"] > 0) {
            $service["amountVATcustomer"] = $service["pricecustomer"] * ($service["VATrate"]-100) / $service["VATrate"];
        }

        $service["AmountServices"] = $service["amountclient"];

        //"amount_sell": "3322.00",
        //"amount_sell_b2b2c": "3654.20",
        $service["Synh"] = "ost_" . $this->DTV($order_data, ["order_id"]); //$service["partner_order_id"];

        $service["supplier"] = ["INN" => "7703403951", "KPP" => "770301001", "Name" => "Островок"];
        $service["Supplier"] = $service["supplier"];

        $Secondeds = $this->DTV($order_data, ["guests_names"]);
        foreach ($Secondeds as $valueS) {
            $Seconded = [];

            $Name = $valueS;
            $ars = explode(" ", $Name);

            $Seconded["FirstName"] = $this->DTV($ars, [0]);
            $Seconded["LastName"] = $this->DTV($ars, [1]);
            $Seconded["SurName"] = $this->DTV($ars, [2]);
            $Seconded["FirstNameLatin"] = "";
            $Seconded["LastNameLatin"] = "";
            $Seconded["SurNameLatin"] = "";

            $Seconded["DocumentNumber"] = "";
            $Seconded["BirthDay"] = "";
            $Seconded["DocType"] = "";

            $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
            $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);

            $service["Seconded"][] = $Seconded;
        }

        if ($service["nomenclature"] == "ОтменаПроживания") {
            $service["price"] = -1 * $service["price"];
            $service["amount"] = -1 * $service["amount"];
            $service["amountVAT"] = -1 * $service["amountVAT"];

            $service["pricecustomer"] = -1 * $service["pricecustomer"];
            $service["amountVATcustomer"] = -1 * $service["amountVATcustomer"];
            $service["amountclient"] = -1 * $service["amountclient"];

            $service["CommissionAmount"] = -1 * $service["CommissionAmount"];
            $service["CommissionVATAmount"] = -1 * $service["CommissionVATAmount"];
            $service["AmountServices"] = -1 * $service["AmountServices"];
        }

        $services[] = $service;
        $jsonv3["services"] = $services;

        if ($this->debugclass) {
            echo "END!!!\r\n";
            print_r($jsonv3);
        }

        return $jsonv3;
    }

    public function getinfo($params)
    {

        $result = ["result" => false];

        if ($this->Auth->userauth()) {
            //if (true) {
            if ($params[1] == "v3") {

                $text = $this->phpinput;
                $Parser = new Parser($this->metod);
                $Parser->SetUseParser("Ostrovok", md5($text), $this->Auth->getuserid());

                $result["result"] = true;

                $jsonv = $this->phpinput;


//                if ($this->debugclass) {
//                    echo "\r\n";
//                    print_r(json_last_error_msg());
//                    echo "\r\n";
//                    print_r($jsonv);
//                }

                $jsonv = json_decode($jsonv, true);

//                if ($this->debugclass) {
//                    echo "\r\n";
//                    print_r(json_last_error_msg());
//                    echo "\r\n";
//                    print_r($jsonv);
//                }

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
