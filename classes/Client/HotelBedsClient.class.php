<?php

class HotelBedsClient extends ex_component
{
    private $metod;
    private $gds;
    private $Auth;

    public function __construct($metod = "")
    {
        parent::__construct(null);
        $this->metod = $metod;
        if (isset($this->POST["connector"])) {
            $this->gds = $this->POST;
        } else {
            $this->gds = $_SESSION["i4b"][$this->classname];
        }

        $this->Auth = new Auth();
    }

    public function getsettings()
    {
        $settings = [
            "ApiKey" => "",
            "Secret" => "",
            "serveraddr" => ""
        ];

        return json_encode($settings);
    }

    //Список заказов

    public function load_orders_list($maxdateorders)
    {

        $nowdate = date("Y-m-d", time());
        $url = $this->gds["serveraddr"] . 'booking/bookings?start=' . $maxdateorders . '&end=' . $nowdate . '&filterType=CREATION&includeCancelled=true'; //'http://test.appibol.ru/api/'.
        $time = time();

        $header["Api-Key"] = $this->gds["ApiKey"];
        $header["Content-Type"] = 'application/json';
        $header["Accept"] = 'application/json';
        //$header["Accept-Encoding"] = 'gzip';
        $header["X-Signature"] = hash("sha256", $this->gds["ApiKey"] . $this->gds["Secret"] . $time);
        $header["X-Time"] = $time;

        $res = $this->http_c_post($url, "", ["get" => 1, "headers" => $header]); //

        $jso = json_decode($res["content"], true);
        $bookings = $this->DTV($jso, ["bookings"]);

        $ordersid = [];
        foreach ($bookings as $order) {

            $id = $this->DTV($order, ["number"]);
            $ordersid[] = $id;

            $orderjson = json_encode($order, JSON_UNESCAPED_UNICODE);
            $this->phpinput = $orderjson;
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

            $res = $this->http_c_post("https://btrip.ru/Parser/HotelBeds/", $post, $exparam);

            if (isset($res["content"])) {
                $result = json_decode($res["content"], true);
            }

        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }

}