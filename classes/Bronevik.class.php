<?php

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 05.10.2018
 * Time: 11:59
 */
class Bronevik extends ex_classlite
{
    private $classname;
    private $metod;
    private $gds;
    private $Auth;

    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        parent::__construct();
        $this->metod = $metod;

        $this->classname = strtolower(get_class($this));

        if (isset($this->POST["connector"])) {
            $this->gds = $this->POST;
        } else {
            $this->gds = $_SESSION["i4b"][$this->classname];
            if ($this->gds === null) {
                $this->gds = $_SESSION["i4b"][get_class($this)];
            }
        }

        $this->debugclass = $debug;
        $this->Auth = new Auth();
    }


    private function jv3($injsonv)
    {
        $services = [];

        //$service["creationdate"] = $this->DTV($jsonv, ["services", "date"]);

        //в $jsonv["services"] может быть массив
        $brservices = $this->DTV($injsonv, ["services"]);
        if (isset($brservices["date"])) {
            $brservices = [];
            $brservices[] = $this->DTV($injsonv, ["services"]);
        }

        foreach ($brservices as $key => $jsonv) {

            $service = $this->get_empty_v3();
            $service["manager"] = $this->DTV($injsonv, ["contactEmail"]);

            $dd = $this->DTV($jsonv, ["date"]);
            //var_dump($dd);
            try {
                $format = "Y-m-d\TH:i:se";
                $date = DateTime::createFromFormat($format, $dd);
                $service["creationdate"] = $date->format("YmdHis"); //!!!!!
            } catch (Exception $e) {
                $service["creationdate"] = "";
            }

            $dd = $this->DTV($jsonv, ["checkout"]);
            try {
                $format = "Y-m-d\TH:i:se";
                $date = DateTime::createFromFormat($format, $dd);
                $service["EndDate"] = $date->format("YmdHis"); //!!!!!
            } catch (Exception $e) {
                $service["EndDate"] = "";
            }
            $service["ServiceEndDate"] = $service["EndDate"];

            $dd = $this->DTV($jsonv, ["checkin"]);
            try {
                $format = "Y-m-d\TH:i:se";
                $date = DateTime::createFromFormat($format, $dd);
                $service["ServiceStartDate"] = $date->format("YmdHis"); //!!!!!
            } catch (Exception $e) {
                $service["ServiceStartDate"] = "";
            }

            $diff = strtotime($service["ServiceEndDate"]) - strtotime($service["ServiceStartDate"]);
            $service["Night"] = round(abs($diff) / 60 / 60 / 24);

            $service["date"] = $service["ServiceEndDate"];


            $service["price"] = (float)$this->DTV($jsonv, ["priceDetails", "client", "net", "price"]);
            $service["amount"] = (float)$service["price"];
            $service["amountVAT"] = (float)$this->DTV($jsonv, ["priceDetails", "client", "net", "vatAmount"]);
            $service["VATrate"] = -1;
            if ($service["amountVAT"] > 0) {
                $service["VATrate"] = 120;
            }


            $service["pricecustomer"] = (float)$this->DTV($jsonv, ["priceDetails", "client", "gross", "price"]);
            $service["VATratecustomer"] = $service["VATrate"];
            $service["amountVATcustomer"] = (float)$this->DTV($jsonv, ["priceDetails", "client", "gross", "vatAmount"]);
            $service["amountclient"] = $service["pricecustomer"];


            $service["CommissionAmount"] = (float)$this->DTV($jsonv, ["priceDetails", "client", "commission", "price"]);
            $service["CommissionVATAmount"] = (float)$this->DTV($jsonv, ["priceDetails", "client", "commission", "vatAmount"]);


            $dailyPrices = $this->DTV($jsonv, ["dailyPrices", "prices"]);
            if (isset($dailyPrices["date"])) {
                $dailyPrices = [];
                $dailyPrices[] = $this->DTV($jsonv, ["dailyPrices", "prices"]);
            }
            //print_r($dailyPrices);
            $lateDeparture = 0;
            foreach ($dailyPrices as $valdailyPrices) {
                if (isset($valdailyPrices["lateDeparture"])) {
                    $lateDeparture = (float)$this->DTV($valdailyPrices, ["lateDeparture", "gross", "price"]);
                }
            }
            $service["LateCheckout"] = $lateDeparture;


            $service["AmountServices"] = $service["amountclient"] - $service["LateCheckout"];

            $service["nomenclature"] = "Проживание";


            $statusId = $this->DTV($jsonv, ["statusId"]);
            if ($statusId == 1) {
                $service["TypeOfTicket"] = "B"; //новая услуга
            } elseif ($statusId == 2) {
                $service["TypeOfTicket"] = "P"; //в обработке
            } elseif ($statusId == 3) {
                $service["TypeOfTicket"] = "AC"; //ожидает подтверждения
            } elseif ($statusId == 4) {
                $service["TypeOfTicket"] = "S"; //подтвержден
            } elseif ($statusId == 5) {
                $service["TypeOfTicket"] = "NC"; //не подтвержден
            } elseif ($statusId == 6) {
                $service["TypeOfTicket"] = "ACC"; //ожидает подтверждения
            } elseif ($statusId == 7) {
                $service["TypeOfTicket"] = "BV"; //заказана аннуляция
            } elseif ($statusId == 8) {
                $service["TypeOfTicket"] = "WV"; //ожидает аннуляции
            } elseif ($statusId == 9) {
                $service["TypeOfTicket"] = "V"; //аннулировано, без штрафа
                $service["nomenclature"] = "ОтменаПроживания";
            } elseif ($statusId == 10) {
                $service["TypeOfTicket"] = "F"; //аннулировано, штраф
                $service["nomenclature"] = "ОтменаПроживания";
            }

            $service["Synh"] =  "brnr_" . $this->DTV($jsonv, ["id"]); //

            $id = $this->DTV($injsonv, ["id"]);
            $service["ReservationNumber"] = "brn_" . $id;

            $City = $this->DTV($jsonv, ["cityName"]);
            $service["CityArrival"] = $City;

            $HotelName = $this->DTV($jsonv, ["hotelName"]);
            $service["HotelName"] = $HotelName;
            $service["PlaceArrival"] = $HotelName;

            $diff = strtotime($service["ServiceEndDate"]) - strtotime($service["ServiceStartDate"]);
            $service["TravelTime"] = round(abs($diff) / 60 / 60 / 24);

            $Product = $this->DTV($jsonv, ["offerName"]);
            $service["NumberTypeName"] = $Product;

            $vatIncluded = $this->DTV($jsonv, ["priceDetails", "hotel", "vatIncluded"]);
            if ($this->debugclass) {
                var_dump($vatIncluded);
            }

            if ($vatIncluded == "true") {
              $service["supplier"] = ["INN" => "6670412152", "KPP" => "667001001", "Name" => "БроневикОнлайн"];
            } else {
              $service["supplier"] = ["INN" => "6671151513", "KPP" => "667101001", "Name" => "Броневик"];
            }
            $service["Supplier"] = $service["supplier"];


            $sSecondeds = $this->DTV($jsonv, ["guests"]);
            $Secondeds = $sSecondeds;
            if (is_string($sSecondeds)) {
                $Secondeds = [];
                $Secondeds[] = $sSecondeds;
            }
            foreach ($Secondeds as $valueS) {
                $Seconded = [];
                $ars = explode(" ", $valueS);

                $Seconded["FirstName"] = $this->DTV($ars, [1]);
                $Seconded["LastName"] = $this->DTV($ars, [0]);
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

        if ($params[count($params)-1] == "debug") {
            $this->debugclass = true;
        }

        //if ($this->Auth->userauth()) {
        if (true) {

            if ($params[1] == "v3") {

                $result["result"] = true;

                $text = $this->phpinput;
                $Parser = new Parser($this->metod);
                $Parser->SetUseParser("Bronevik", md5($text), $this->Auth->getuserid());

                $textxml = $this->phpinput;
                $textxml = str_replace('xsi:type="OrderServiceAccommodation"', "", $textxml);
                $xml = simplexml_load_string($textxml);
                $jsonv = $this->object2array($xml);

                if ($this->debugclass) {
                    //print_r($jsonv);
                }

                $data = '<?xml version="1.0" encoding="utf-8"?>
                <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="soap.bronevik" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                   <SOAP-ENV:Body>
                       <ns1:SearchOrdersRequest>
                           <credentials>
                               <clientKey>' . $this->gds["clientKey"] . '</clientKey>
                               <login>' . $this->gds["userId"] . '</login>
                               <password>' . $this->gds["userPass"] . '</password>
                           </credentials>
                           <language>ru</language>
                           <searchCriteria xsi:type="ns1:SearchOrderCriterionOrderId">
                               <orderId>' . $jsonv["id"] . '</orderId>
                           </searchCriteria>
                       </ns1:SearchOrdersRequest>
                   </SOAP-ENV:Body>
                </SOAP-ENV:Envelope>';

                //
                $url = $this->gds["serveraddr"];
                $res = $this->http_c_post($url, $data);

                $content = $res["content"];
                $content = trim(str_replace('<?xml version="1.0" encoding="UTF-8"?>', "", $content));
                $content = trim(str_replace('SOAP-ENV:', "", $content));
                $content = trim(str_replace('ns1:', "", $content));
    
                $jsonv = [];
                $xml = simplexml_load_string($content);
                $ordersxml = $xml->Body->SearchOrdersResponse->orders;
                foreach ($ordersxml as $order) {
                    $orderxml = $order->asXML();

                    $textxml = str_replace('xsi:type="OrderServiceAccommodation"', "", $orderxml);
                    $xml = simplexml_load_string($textxml);
                    $jsonv = $this->object2array($xml);
                }


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


    public function loadorders($param1)
    {
        $result = ["result" => false];
        if ($this->Auth->userauth()) {
            //if (true) {

            $serverid = $param1[1];
            if ($serverid != "") {
                $nowdate = date("Y-m-d H:i:s", time());
                $nowd = date("Y-m-d", time() + (24 * 60 * 60));

                $maxdateorders = $this->max("returndoc", "lastdate", ["typedoc" => $this->classname]);
                if ($maxdateorders == "") {
                    $maxdateorders = date("Y-m-d", time() - (5 * 24 * 60 * 60));
                    $this->insert("returndoc", ["lastdate" => $maxdateorders, "sever_id" => $serverid, "typedoc" => $this->classname]);
                } else {
                    $date = new DateTime($maxdateorders);
                    $maxdateorders = $date->format('Y-m-d');
                }

                $data = '<?xml version="1.0" encoding="utf-8"?>
                    <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="soap.bronevik" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                       <SOAP-ENV:Body>
                           <ns1:SearchOrdersRequest>
                               <credentials>
                                   <clientKey>' . $this->gds["clientKey"] . '</clientKey>
                                   <login>' . $this->gds["userId"] . '</login>
                                   <password>' . $this->gds["userPass"] . '</password>
                               </credentials>
                               <language>ru</language>
                               <searchCriteria xsi:type="ns1:SearchOrderCriterionCreateDate">
                                   <dateStart>' . $maxdateorders . '</dateStart>
                                   <dateEnd>' . $nowd . '</dateEnd>
                               </searchCriteria>
                           </ns1:SearchOrdersRequest>
                       </SOAP-ENV:Body>
                    </SOAP-ENV:Envelope>';

                $url = $this->gds["serveraddr"];
                $res = $this->http_c_post($url, $data);

                $content = $res["content"];
                $content = trim(str_replace('<?xml version="1.0" encoding="UTF-8"?>', "", $content));
                $content = trim(str_replace('SOAP-ENV:', "", $content));
                $content = trim(str_replace('ns1:', "", $content));

                $xml = simplexml_load_string($content);

                $ordersid = [];
                $ordersxml = $xml->Body->SearchOrdersResponse->orders;
                foreach ($ordersxml as $order) {
                    $jso = $this->object2array($order);
                    $ordersid[] = $jso["id"];

                    $orderxml = $order->asXML();
                    $this->phpinput = $orderxml;
                    $this->savetiket([], 1);

                    //запишем транзакцию в лог
                    $this->settransaction($jso["id"]);
                }

                $this->update("returndoc", ["lastdate" => $nowdate], ["AND" => ["sever_id" => $serverid, "typedoc" => $this->classname]]);
                $result = ["result" => true, "orders" => $ordersid];

            } else {
                $result["error"] = "Server id fail";
            }

        } else {
            $result["error"] = "Authorization fail";
        }

        return $result;
    }

}


/*
Перед созданием услуги Броневик и Нотелбук

Если СтруктураУслуги.Свойство("ReservationNumber") Тогда
		НомерБрониУслуги = СтруктураУслуги.ReservationNumber;

		Запрос = Новый Запрос;
		Запрос.УстановитьПараметр("Характеристика", ПланыВидовХарактеристик.НаборХарактеристикДляНоменклатуры.IDСинхронизации );
		Запрос.УстановитьПараметр("ЗначениеХарактеристики", НомерБрониУслуги);
		Запрос.Текст = "ВЫБРАТЬ ПЕРВЫЕ 1
		               |	ИнформацияПоУслуге.Услуга КАК Услуга,
		               |	ИнформацияПоУслуге.Характеристика КАК Характеристика,
		               |	ИнформацияПоУслуге.ЗначениеХарактеристики КАК ЗначениеХарактеристики
		               |ИЗ
		               |	РегистрСведений.ИнформацияПоУслуге КАК ИнформацияПоУслуге
		               |ГДЕ
		               |	ИнформацияПоУслуге.Характеристика = &Характеристика
		               |	И ИнформацияПоУслуге.ЗначениеХарактеристики = &ЗначениеХарактеристики";
		Выборка = Запрос.Выполнить().Выбрать();
		Если Выборка.Следующий() Тогда
			ВхОбъект = Выборка.Услуга;    //Услуга кортеоса
		КонецЕсли;
КонецЕсли;


*/