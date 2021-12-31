<?php

class AcademserviceClient extends ex_component
{
    private $metod;
    private $gds;
    private $Auth;


    public function __construct($metod = "")
    {
        parent::__construct($metod);
        $this->metod = $metod;

        $this->classname = strtolower(get_class($this));
        $this->gds = $_SESSION["i4b"][$this->classname];
        if ($this->gds === null) {
            $this->gds = $_SESSION["i4b"][get_class($this)];
        }

        $this->Auth = new Auth();
    }

    public function getsettings()
    {
        $settings = [
            "companyId" => "",
            "userId" => "",
            "userPass" => "",
            "serveraddr" => "https://www.acase.ru/xml/form.jsp"
        ];

        return $settings;
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

            $res = $this->http_c_post("https://btrip.ru/Parser/Academservice/", $post, $exparam);

            if (isset($res["content"])) {
                $result = json_decode($res["content"], true);
            }

        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }


    private function load_orders_list($maxdateorders)
    {

        $RequestName = "OrderListRequest";
        $companyId = $this->gds["companyId"];
        $userId = $this->gds["userId"];
        $userPass = $this->gds["userPass"];
        $language = "ru";

        $data = $this->DTV([$maxdateorders], [0], "", "Y-m-d", "d.m.Y");
        $xml = "<?xml version='1.0' encoding='utf-8'?><OrderListRequest BuyerId='" . $companyId . "' UserId='" . $userId . "' Password='" . $userPass . "' Language='ru' ArrivalDateFrom='" . $data . "'/>"; // ArrivalDateTo='15.01.2018'  RegistrationDateFrom='09.01.2018' RegistrationDateTo='15.01.2018'/>";

        $request = [
            "RequestName" => $RequestName,
            "companyId" => $companyId,
            "userId" => $userId,
            "userPass" => $userPass,
            "language" => $language,
            "xml" => $xml
        ];

        //Id= 4930586
        $url = $this->gds["serveraddr"];
        $res = $this->http_c_post($url, $request);
        $content = $res["content"];

        $xml = simplexml_load_string($content);

        $ordersid = [];
        $ordersxml = $xml->Orders->Order;
        foreach ($ordersxml as $order) {
            $jso = $this->object2array($order);
            $id = $this->DTV($jso, ["@attributes", "Id"]);
            $ordersid[] = $id;

            $orderxml = $order->asXML();
            $this->phpinput = $orderxml;
            $this->savetiket([], 1);

            //запишем транзакцию в лог
            $this->settransaction($id);
        }

        $result = $ordersid;

        return $result;
    }

    public function loadorders($param1)
    {
        $result = ["result" => false];

        if ($this->Auth->userauth()) {
            $serverid = $param1[1];
            if ($serverid != "") {
                $nowdate = date("Y-m-d H:i:s", time());

                $maxdateorders = $this->max("returndoc", "lastdate", ["typedoc" => $this->classname]);
                if ($maxdateorders == "") {
                    $maxdateorders = date("Y-m-d", time() - (7 * 24 * 60 * 60));
                    $this->insert("returndoc", ["lastdate" => $maxdateorders, "sever_id" => $serverid, "typedoc" => $this->classname]);
                } else {
                    $date = new DateTime($maxdateorders);
                    $maxdateorders = $date->format('Y-m-d');
                }

                $ordersid = $this->load_orders_list($maxdateorders);

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
