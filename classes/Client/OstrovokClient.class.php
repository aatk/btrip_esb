<?php

class OstrovokClient extends ex_component
{

    private $metod;
    private $gds;
    private $Auth;

    public function __construct($metod = "")
    {
        parent::__construct($metod);
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

        $this->Auth = new Auth();
    }

    public function getsettings()
    {
        $settings = [
            "key" => "",
            "key_id" => "",
            "serveraddr" => "https://partner.ostrovok.ru/"
        ];

        return json_encode($settings);
    }

    private function get_order($id)
    {

        $data = '{"format":"json","partner_order_id":"' . $id . '","lang":"ru"}';
        $url = $this->gds["serveraddr"] . "api/b2b/v2/order/info?data=" . urlencode($data);
        $res = $this->http_c_post($url, $data, [
            "get",
            "basicauth" => [
                "username" => $this->gds["key_id"],
                "password" => $this->gds["key"]
            ]
        ]);

        return $res["content"];
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

                $data = '{"format":"json","created_from":"' . $maxdateorders . '","lang":"ru"}';
                $url = $this->gds["serveraddr"] . "api/b2b/v2/order/list?data=" . urlencode($data);

                $res = $this->http_c_post($url, $data, [
                    "get",
                    "basicauth" => [
                        "username" => $this->gds["key_id"],
                        "password" => $this->gds["key"]
                    ]
                ]);

                $js = json_decode($res["content"], true);

                $ordersid = [];
                $orders = $this->DTV($js, ["result", "orders"]);
                if (is_array($orders)) {
                    foreach ($orders as $val) {
                        $ordersid[] = $val["partner_order_id"];
                    }
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

            $res = $this->http_c_post("https://btrip.ru/Parser/Ostrovok/", $post, $exparam);

            if (isset($res["content"])) {
                $result = json_decode($res["content"], true);
            }

        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }

}
