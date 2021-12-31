<?php

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 04.10.2018
 * Time: 14:33
 */
class Hotelbook extends ex_classlite
{
    private $metod;
    private $classname;
    private $gds;
    private $connectionInfo;

    private $Auth;


    public function CreateDB()
    {
        $info["hotelbook_orderload"] = array(
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
        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем
        if (isset($this->POST["connector"])) {
            $this->gds = $this->POST;
        } else {
            $this->gds = $_SESSION["i4b"][$this->classname];
        }

        $this->debugclass = $debug;
        $this->Auth = new Auth();
    }

    private function gettime()
    {
        $url = $this->gds["serveraddr"] . "/unix_time";
        $res = $this->http_c_post($url, "");
        return $res["content"];
    }

    //Список заказов

//    private function order_list($time, $datefrom)
//    {
//        /*
//            <xml version="1.0" encoding="utf-8">
//            <OrderListRequest>
//                [<CheckInFrom>...</CheckInFrom>]
//                [<CheckInTo>...</CheckInTo>]
//                [<CreatedFrom>...</CreatedFrom>]
//                [<CreatedTo>...</CreatedTo>]
//                [<ChangedFrom>...</ChangedFrom>]
//                [<ChangedTo>...</ChangedTo>]
//                [<MessagesOnlineFrom>...</MessagesOnlineFrom>]
//                [<MessagesOnlineTo>...</MessagesOnlineTo>]
//                [<OnlyNotReadMessages>...</OnlyNotReadMessages>]
//                [<Agents>
//                  <Agent>...<Agent>
//                </Agents>]
//            </OrderListRequest>
//         */
//        $data = [
//            'request' => '<xml version="1.0" encoding="utf-8"><OrderListRequest><CreatedFrom>' . $datefrom . '</CreatedFrom><ChangedFrom>' . $datefrom . '</ChangedFrom></OrderListRequest>'
//        ];
//
//        $login = $this->gds["userId"];
//        $password = $this->gds["userPass"];
//
//        $url = $this->gds["serveraddr"] . "/order_list?login=" . $login . "&time=" . $time . "&checksum=" . md5(md5($password) . $time);
//        $res = $this->http_c_post($url, $data, ["gzip"]);
//
//        return $res;
//    }
//
//    private function load_orders_list($content)
//    {
//        /*
//         <xml version="1.0" encoding="utf-8">
//        <OrderListResponse>
//         [<OrderListRequest>...</OrderListRequest>] - Request for this response
//         [<Errors>
//            <Error code="..." description="..."> - ошибки
//          </Errors>]
//          <OrderList>
//            <Orders agent="..."> - список заказов (может быть много)
//              <Order id="..." state="..." via_xml_gate="true|false" tag="..."> - список элементов заказа (может быть несколько)
//                <HotelItem id="..." state="...">
//                  <HotelId>...</HotelId>
//                  [<HotelName>...</HotelName>]
//                  [<CheckIn>...</CheckIn>]
//                  [<Duration>...</Duration>]
//                  [<Created>...</Created>]
//                  [<Price></Price>]
//                  [<Currency></Currency>]
//                  [<Currency></Currency>]
//                  [<Rooms>
//                    <Room roomSizeId=".." roomTypeId=".." roomViewId=".." cots="...">
//                      <Paxes>
//                        <Pax>
//                          [<Title>...</Title>]
//                          [<FirstName>...</FirstName>]
//                          [<LastName>...</LastName>]
//                        </Pax>
//                      </Paxes>
//                    </Room>
//                  </Rooms>]
//                  [<Logs></Logs>]
//                </HotelItem>
//                <MessagesOnline> - блок сообщений
//                  <Message> - сообщение
//                    <Id>...</Id> - ID сообщения
//                    <Message>...</Message> - текст сообщения
//                    <Direction>...</Direction> - Собственное сообщение, либо нет
//                    <isRead>...</isRead> - Прочитано ли сообщение
//                    <Date>...</Date> - Дата добавления сообщения
//                    <User> - Пользователь отправивший сообщение
//                      <Name>...</Name> - Имя пользователя
//                    </User>
//                  </Message>
//                </MessagesOnline>
//              </Order>
//            </Orders>
//          </OrderList>
//        </OrderListResponse>
//         */
//
//        $result = ["result" => false, "content" => $content];
//
//        $xml = simplexml_load_string($content);
//
//        $ordersid = [];
//        $ordersxml = $xml->OrderList->Orders;
//        foreach ($ordersxml as $orders) {
//            //цикл по агентам
//            foreach ($orders->Order as $order) {
//                $json = $this->object2array($order);
//                $id = $this->DTV($json, ["@attributes", "id"]);
//                $ordersid[] = $id;
//
//                $orderxml = $order->asXML();
//
//                $md5source = md5($orderxml);
//                $ids = $this->get("hotelbook_orderload", ["id", "md5"], ["AND" => ["md5" => $md5source, "datecreate[<>]" => [date("Y-m-d"), date("Y-m-d")]]]);
//                if ($ids) {
//                    //Такой заказ уже загружался
//                } else {
//                    $this->phpinput = $orderxml;
//                    $this->savetiket([], 1);
//
//                    $this->insert("hotelbook_orderload", ["md5" => $md5source, "datecreate" => date("Y-m-d")]); //ЗАпишем данные о том что заказ загружался
//                }
//
//                //запишем транзакцию в лог
//                $this->settransaction($id);
//            }
//        }
//
//
//        return $ordersid;
//    }
//
//    public function loadorders($param1)
//    {
//
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
//                $time = (string)time(); //$this->gettime();
//                $res = $this->order_list($time, $maxdateorders);
//                $ordersid = $this->load_orders_list($res["content"]);
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
        /*
        $data = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
            <OrderListRequest>
                <CreatedFrom>$datefrom</CreatedFrom>
                <ChangedFrom>$datefrom</ChangedFrom>
            </OrderListRequest>";
        */
        $time = (string)time();
        $login = $this->gds["userId"];
        $password = $this->gds["userPass"];

        //curl_setopt($rCurl, CURLOPT_HTTPHEADER, array('Accept-Encoding: gzip,deflate'));

        $url = $this->gds["serveraddr"] . "/order_info?login=" . $login . "&time=" . $time . "&checksum=" . md5(md5($password) . $time) . "&order_id=" . $id;
        //print_r($url);
        $res = $this->http_c_post($url, "", ["get", "gzip"]);

        return $res;

    }

    private function jv3($jsonv)
    {
        $services = [];

        $Order = $this->DTV($jsonv, ["Order"]);

        $HotelItem = $this->DTV($Order, ["Items", "HotelItem"]);
        if ($HotelItem == "") {
            $HotelItem = $this->DTV($Order, ["Items", "OfflineHotelItem"]);
        }

        $HotelItems = [];
        if (isset($HotelItem["@attributes"])) {
            //Это 1 к 1
            $HotelItems[] = $HotelItem;
        } else {
            $HotelItems = $HotelItem;//[count($HotelItem)-1];
        }
        //$service["HotelItem"] = $HotelItem;

        foreach ($HotelItems as $HotelItem) {

            $service = $this->get_empty_v3();
            $service["manager"] = $Order["Manager"];
            $service["ReservationNumber"] = "htb_" . $this->DTV($Order, ["Id"]); //$service["Synh"];

            $dd = $this->DTV($Order, ["CreationDate"]); //"CreationDate": "2018-10-09 16:07:18",
            try {
                $format = "Y-m-d H:i:s";
                $date = DateTime::createFromFormat($format, $dd);
                if ($date) {
                    $service["creationdate"] = $date->format("YmdHis");
                }
            } catch (Exception $e) {
                $service["creationdate"] = "";
            }

            $service["Synh"] = "htbr_" . $this->DTV($HotelItem, ["@attributes", "itemId"]); //$service["partner_order_id"];

            $dd = $this->DTV($HotelItem, ["CheckIn"]); //"checkout_at": "2018-09-05",
            $tt = $this->DTV($HotelItem, ["CheckInTime", "@attributes", "value"]); //"checkout_time": "12:00",


            if ($tt == "") {
                $format = "Y-m-d H:i:s";
                $tt = $this->DTV($HotelItem, ["CheckInTime", "@attributes", "default"]);
            }

            if ($tt != "") {
                $format = "Y-m-d H:i";
            } else {
                $format = "Y-m-d";
            }


            try {
                $date = DateTime::createFromFormat("Y-m-d H:i:s", $dd . " " . $tt);// + ((int)$service["Night"] * 24 * 60 * 60);
                if (!$date) {
                    $date = DateTime::createFromFormat("Y-m-d H:i", $dd . " " . $tt);// + ((int)$service["Night"] * 24 * 60 * 60);
                    if (!$date) {
                        $date = DateTime::createFromFormat("Y-m-d", trim($dd . " " . $tt));// + ((int)$service["Night"] * 24 * 60 * 60);
                    }
                }
                if ($date) {
                    $service["ServiceStartDate"] = $date->format("YmdHis"); //!!!!!
                }
            } catch (Exception $e) {
                $service["ServiceStartDate"] = "";
            }

            $service["Night"] = (int)$this->DTV($HotelItem, ["Duration"]);

            $dd = $this->DTV($HotelItem, ["CheckIn"]); //"checkout_at": "2018-09-05",
            $tt = $this->DTV($HotelItem, ["CheckOutTime", "@attributes", "value"]); //"checkout_time": "12:00",


            $format = "Y-m-d H:i";
            if ($tt == "") {
                $format = "Y-m-d H:i:s";
                $tt = $this->DTV($HotelItem, ["CheckOutTime", "@attributes", "default"]);

                if ($this->debugclass) {
                    var_dump($dd . " " . $tt);
                }
            }

            if ($tt != "") {
                $format = "Y-m-d H:i";
            } else {
                $format = "Y-m-d";
            }

            if ($this->debugclass) {
                var_dump($dd . " " . $tt);
                var_dump($format);
            }

            try {
                $date = DateTime::createFromFormat("Y-m-d H:i:s", $dd . " " . $tt);// + ((int)$service["Night"] * 24 * 60 * 60);
                if (!$date) {
                    $date = DateTime::createFromFormat("Y-m-d H:i", $dd . " " . $tt);// + ((int)$service["Night"] * 24 * 60 * 60);
                    if (!$date) {
                        $date = DateTime::createFromFormat("Y-m-d", trim($dd . " " . $tt));// + ((int)$service["Night"] * 24 * 60 * 60);
                    }
                }
                if ($date) {
                    $date->modify('+' . $service["Night"] . ' day');
                    $service["ServiceEndDate"] = $date->format("YmdHis"); //!!!!!
                }


            } catch (Exception $e) {
                $service["ServiceEndDate"] = "";
            }

            $service["date"] = $service["ServiceEndDate"];

            $service["nomenclature"] = "Проживание";
            if ($this->DTV($Order, ["State"]) == "COMMITED") {
                $service["TypeOfTicket"] = "S";
            } else {
                $service["nomenclature"] = "ОтменаПроживания";
                $service["TypeOfTicket"] = "V";
            }

            $service["HotelName"] = $this->DTV($HotelItem, ["Name"]);

            $Rooms = [];
            $Room = $this->DTV($HotelItem, ["Rooms", "Room"]);
            if (isset($Room["@attributes"])) {
                $Rooms[] = $Room;
            } else {
                $Rooms = $Room;
            }

            $paxid = -1;
            if (isset($Rooms[0])) {
                $Room = $Rooms[0];
                $service["NumberTypeName"] = $this->DTV($Room, ["@attributes", "roomName"]);
                $paxid = $this->DTV($Room, ["Paxes", "PaxId"]);
                if (!is_array($paxid)) {
                    $paxid = [];
                    $paxid[] = $this->DTV($Room, ["Paxes", "PaxId"]);
                }
            }


            //Поставщик
            $service["price"] = (float)$this->DTV($HotelItem, ["TotalPrice"]);
            $service["amount"] = (float)$service["price"];
            $service["amountVAT"] = 0; //(float)$this->DTV($HotelItem, ["amount_refunded"]);
            if ($service["amountVAT"] == 0) {
                $service["VATrate"] = -1;
            } else {
                $service["VATrate"] = round($service["amountVAT"] / $service["amount"] * 100);
            }
            //Клинт
            $service["amountclient"] = (float)$this->DTV($HotelItem, ["TotalPrice"]);
            $service["pricecustomer"] = $service["amountclient"];
            $service["amountVATcustomer"] = 0;//(float)$this->DTV($order_data, ["amount_payable_vat"]);
            $service["VATratecustomer"] = $service["VATrate"];
            $service["AmountServices"] = $service["amountclient"];

            $service["supplier"] = ["INN" => "7709878038", "KPP" => "770901001", "Name" => "Хотелбук-Сервис"];
            $service["Supplier"] = $service["supplier"];


            $sSecondeds = $this->DTV($Order, ["Paxes", "Pax"]);
            $Secondeds = $sSecondeds;
            if (isset($Secondeds["@attributes"])) {
                $Secondeds = [];
                $Secondeds[] = $sSecondeds;
            }


            foreach ($Secondeds as $valueS) {
                $paxid2 = $this->DTV($valueS, ["@attributes", "paxId"]);
                if (in_array($paxid2, $paxid)) {
                    //
                    if ($this->debugclass) {
                        print_r("paxid = ".$paxid);
                        print_r("paxid2 = ".$paxid2);
                    }

                    $Seconded = [];

                    $Seconded["FirstName"] = $this->DTV($valueS, ["FirstName"]);
                    $Seconded["LastName"] = $this->DTV($valueS, ["LastName"]);
                    $Seconded["SurName"] = "";
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
            }

            if ($service["nomenclature"] == "ОтменаПроживания") {
                $service["price"] = -1*$service["price"];
                $service["amount"] = -1*$service["amount"];
                $service["amountVAT"] = -1*$service["amountVAT"];

                $service["pricecustomer"] = -1*$service["pricecustomer"];
                $service["amountVATcustomer"] = -1*$service["amountVATcustomer"];
                $service["amountclient"] = -1*$service["amountclient"];

                $service["CommissionAmount"] = -1*$service["CommissionAmount"];
                $service["CommissionVATAmount"] = -1*$service["CommissionVATAmount"];
                $service["AmountServices"] = -1*$service["AmountServices"];
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
                $Parser->SetUseParser("Hotelbook", md5($text), $this->Auth->getuserid());


                $textxml = $this->phpinput;
                $xml = simplexml_load_string($textxml);
                $jsonv = $this->object2array($xml);

                $id = $this->DTV($jsonv, ["@attributes", "id"]);

                $orderxml = $this->order_info($id);
                $textxml = $orderxml["content"];
                $xml = simplexml_load_string($textxml); //Реальный заказ
                $jsonv = $this->object2array($xml);

                //print_r($textxml);
                $result["jsonv"] = $jsonv;
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
