<?php

class BronevikClient extends ex_component
{
    private $metod;
    private $gds;
    private $Auth;

    public function __construct($metod = "")
    {
        parent::__construct($metod);
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

        $this->Auth = new Auth();
    }

    public function getsettings()
    {
        $settings = [
            "userId" => "",
            "userPass" => "",
            "clientKey" => "",
            "serveraddr" => "https://hotels-api.bronevik.com/v2.1.0/api.php"
        ];

        return json_encode($settings);
    }

    public function getinfo($params)
    {
        $result = ["result" => false];

        if ($this->Auth->userauth()) {
            $result["result"] = true;

            $text = $this->phpinput;

            $userinfo = $this->Auth->getusersessioninfo();
            $basicauth = [
                "username" => $userinfo["login"],
                "password" => $userinfo["basicpassword"]
            ];

            $exparam = [
                "basicauth" => $basicauth
            ];

            $this->gds["connector"] = 1;
            $post = $this->gds;
            $post["fileinfo"] = $text;

            $res = $this->http_c_post("https://btrip.ru/Parser/Bronevik/", $post, $exparam);

            if (isset($res["content"])) {
                $result = json_decode($res["content"], true);
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