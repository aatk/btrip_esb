<?php

class AriadnaClient extends ex_component
{

    private $metod;
    private $gds;
    private $Auth;


    public function __construct($metod = "")
    {
        parent::__construct(null);
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
            "token" => "",
            "serveraddr" => "http://gate.aanda.ru/agency/getxml.php"
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

//            $this->gds["connector"] = 1;
//            $post = $this->gds;
            $post["fileinfo"] = $text;

            $res = $this->http_c_post("https://btrip.ru/Parser/Ariadna/", $post, $exparam);

            if (isset($res["content"])) {
                $result = json_decode($res["content"], true);
            }

        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }


    private function get_order($id)
    {
        $request = "";
        $url = $this->gds["serveraddr"] . "?token=" . $this->gds["token"] . "&id=" . $id;
        $res = $this->http_c_post($url, $request);

        return $res["content"];
    }

    private function get_orders($date1)
    {
        $date = new DateTime($date1);
        $date1 = $date->format('Y-m-d');
        $date2 = date("Y-m-d", time());

        $request = "";
        $url = $this->gds["serveraddr"] . "?token=" . $this->gds["token"] . "&date1=" . $date1 . "&date2=" . $date2;
        $res = $this->http_c_post($url, $request);

        return $res["content"];
    }

    public function loadorders($param1)
    {

        //!!!! Нужно передать идентификатор сервера

        $result = ["result" => false];

        //if (true) {
        if ($this->Auth->userauth()) {

            $serverid = $param1[1];
            if ($serverid != "") {
                $nowdate = date("Y-m-d H:i:s", time());

                $classname = get_class($this);
                $maxdateorders = $this->max("returndoc", "lastdate", ["typedoc" => $classname]);
                if ($maxdateorders == "") {
                    $maxdateorders = date("Y-m-d", time() - (3 * 24 * 60 * 60));
                    $this->insert("returndoc", ["lastdate" => $maxdateorders, "sever_id" => $serverid, "typedoc" => $this->classname]);
                } else {
                    $date = new DateTime($maxdateorders);
                    $maxdateorders = $date->format('Y-m-d');
                }
                $content = $this->get_orders($maxdateorders);

                $xml = simplexml_load_string($content);
                $json = $this->object2array($xml);


                $Orders = $json["Orders"]["Order"];
                if (isset($json["Orders"]["Order"]["@attributes"])) {
                    $Orders = [];
                    $Orders[] = $json["Orders"]["Order"];
                }

                $ordersid = [];
                foreach ($Orders as $value) {
                    $ordersid[] = $value["@attributes"]["Id"];
                }

                foreach ($ordersid as $id) {
                    $order = $this->get_order($id);
                    $this->phpinput = $order;
                    $this->savetiket([], 1);

                    //запишем транзакцию в лог
                    $this->settransaction($id);
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
