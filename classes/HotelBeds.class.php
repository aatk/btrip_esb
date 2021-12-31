<?php

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 16.10.2018
 * Time: 13:56
 */
class HotelBeds extends ex_classlite
{
    private $metod;
    private $classname;
    private $gds;

    private $Auth;

    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        parent::__construct();
        $this->metod = $metod;
        if (isset($this->POST["connector"])) {
            $this->gds = $this->POST;
        } else {
            $this->gds = $_SESSION["i4b"][$this->classname];
        }

        $this->debugclass = $debug;
        $this->Auth = new Auth();
    }

    //Список заказов

//    public function load_orders_list($maxdateorders)
//    {
//
//        $nowdate = date("Y-m-d", time());
//        $url = $this->gds["serveraddr"] . 'booking/bookings?start=' . $maxdateorders . '&end=' . $nowdate . '&filterType=CREATION&includeCancelled=true'; //'http://test.appibol.ru/api/'.
//        $time = time();
//
//        $header["Api-Key"] = $this->gds["ApiKey"];
//        $header["Content-Type"] = 'application/json';
//        $header["Accept"] = 'application/json';
//        //$header["Accept-Encoding"] = 'gzip';
//        $header["X-Signature"] = hash("sha256", $this->gds["ApiKey"] . $this->gds["Secret"] . $time);
//        $header["X-Time"] = $time;
//
//        $res = $this->http_c_post($url, "", ["get" => 1, "headers" => $header]); //
//
//        $jso = json_decode($res["content"], true);
//        $bookings = $this->DTV($jso, ["bookings"]);
//
//        $ordersid = [];
//        foreach ($bookings as $order) {
//
//            $id = $this->DTV($order, ["number"]);
//            $ordersid[] = $id;
//
//            $orderjson = json_encode($order, JSON_UNESCAPED_UNICODE);
//            $this->phpinput = $orderjson;
//            $this->savetiket([], 1);
//
//            //запишем транзакцию в лог
//            $this->settransaction($id);
//        }
//
//        $result = $ordersid;
//
//        return $result;
//    }
//
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
//                $ordersid = $this->load_orders_list($maxdateorders);
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


    //Информация по заказу

    private function order_info($id)
    {
        $url = $this->gds["serveraddr"] . 'booking/bookings/' . $id;
        $time = time();

        $header["Api-Key"] = $this->gds["ApiKey"];
        $header["Content-Type"] = 'application/json';
        $header["Accept"] = 'application/json';
        $header["X-Signature"] = hash("sha256", $this->gds["ApiKey"] . $this->gds["Secret"] . $time);
        $header["X-Time"] = $time;

        $res = $this->http_c_post($url, "", ["get" => 1, "headers" => $header]);

        $jso = json_decode($res["content"], true);

        return $jso;
    }

    private function jv3($jsonv)
    {
        $services = [];

        $hotels = $this->DTV($jsonv, ["hotels"]);
        foreach ($hotels as $hotel) {

            $rooms = $this->DTV($hotel, ["hotel", "rooms"]);
            foreach ($rooms as $room) {

                $service = $this->get_empty_v3();

                $status = $this->DTV($hotel, ["status"]);
                if ($status != "TRASH") {
                    //Есть данные по заказу
                    $service["manager"] = $this->DTV($jsonv, ["booking", "clientReference"]);//$hotel["holder"]["name"]." ".$hotel["holder"]["surname"];
                    $service["Synh"] = "hbs_" . $this->DTV($hotel, ["reference"]); //$service["partner_order_id"];

                    $hotelitem = $this->DTV($hotel, ["hotel"]);

                    $dd = $this->DTV($hotelitem, ["checkIn"]); //"checkout_at": "2018-09-05",
                    try {
                        $format = "Y-m-d";
                        $date = DateTime::createFromFormat($format, $dd);
                        $service["ServiceStartDate"] = $date->format("YmdHis"); //!!!!!
                    } catch (Exception $e) {
                        $service["ServiceStartDate"] = "";
                    }

                    $dd = $this->DTV($hotelitem, ["checkOut"]); //"checkout_at": "2018-09-05",
                    try {
                        $format = "Y-m-d";
                        $date = DateTime::createFromFormat($format, $dd);
                        $service["ServiceEndDate"] = $date->format("YmdHis"); //!!!!!
                    } catch (Exception $e) {
                        $service["ServiceEndDate"] = "";
                    }
                    $service["date"] = $service["ServiceEndDate"];

                    $diff = strtotime($service["ServiceEndDate"]) - strtotime($service["ServiceStartDate"]);
                    $service["Night"] = round(abs($diff) / 60 / 60 / 24);


                    $service["nomenclature"] = "Проживание";
                    if ($status == "CONFIRMED") {
                        $service["TypeOfTicket"] = "S";
                    } else {
                        $service["TypeOfTicket"] = "V";
                    }

                    $service["HotelName"] = $this->DTV($hotelitem, ["name"]);
                    $service["HotelCategory"] = $this->DTV($hotelitem, ["categoryName"]);


                    $service["CityArrival"] = $this->DTV($hotelitem, ["destinationName"]);
                    $service["PlaceArrival"] = $this->DTV($hotelitem, ["destinationName"]);
                    $service["Arrival"] = $this->DTV($hotelitem, ["destinationName"]);
                    $service["ArrivalCode"] = $this->DTV($hotelitem, ["destinationCode"]);

                    $service["NumberTypeName"] = $this->DTV($room, ["name"]);
                    $service["TypeOfFood"] = $this->DTV($room, ["rates", 0, "boardName"]);


                    $service["Latitude"] = $this->DTV($hotelitem, ["latitude"]);
                    $service["Longitude"] = $this->DTV($hotelitem, ["longitude"]);

                    //Клиент
                    $gross = (float)$this->DTV($room, ["rates", 0, "gross"]);
                    $ndsPayer = (float)$this->DTV($room, ["rates", 0, "ndsPayer"]);
                    $service["amountclient"] = $gross;//(float)$this->DTV($hotelitem, ["totalNet"]);
                    $service["pricecustomer"] = $service["amountclient"];
                    $service["amountVATcustomer"] = $ndsPayer;
                    $service["VATratecustomer"] = -1;
                    if ($service["amountVATcustomer"] != 0){
                        $service["VATratecustomer"] = round($service["amountVAT"] / $service["amount"] * 100);
                    }
                    $service["AmountServices"] = $service["amountclient"];

                    //Поставщик
                    $net = (float)$this->DTV($room, ["rates", 0, "net"]);
                    $service["price"] = $net; //(float)$this->DTV($hotelitem, ["totalSellingRate"]);
                    $service["amount"] = (float)$service["price"];
                    $service["amountVAT"] = 0;//(float)$this->DTV($hotelitem, ["nds"]);
                    $service["VATrate"] = $service["VATratecustomer"];
                    if ($service["VATrate"] != -1) {
                        $service["amountVAT"] = round($service["price"] / (100+$service["VATrate"]) * $service["VATrate"]);
                    }


                    $service["supplier"] = ["INN" => "7730633954", "KPP" => "771401001", "Name" => "SVOY TT"];
                    $service["Supplier"] = $service["supplier"];

                }

                $services[] = $service;
            }
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
                $Parser->SetUseParser("HotelBeds", md5($text), $this->Auth->getuserid());

                $textjson = $this->phpinput;
                $json = json_decode($textjson, true);

                $id = $this->DTV($json, ["number"]);
                $jsonv = $this->order_info($id);

                $result["json"] = $json;
                $result["jsonv2"] = $jsonv;
                $result["jsonv3"] = $this->jv3($json);

            } else {
                $result["error"] = "Not for this version";
            }
        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }


    public function test($params)
    {
        //$res = $this->loadorders($params);

        return $params;
    }

}