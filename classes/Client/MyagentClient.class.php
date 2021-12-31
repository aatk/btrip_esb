<?php


class MyagentClient extends ex_component
{
    private $metod;
    private $gds;
    private $connectionInfo;

    private $Auth;
    private $token;


    public function __construct($metod = "")
    {
        parent::__construct($metod);
        $this->metod = $metod;

        if (isset($this->POST["connector"])) {
            $this->gds = $this->POST;
        } else {
            $this->gds = $_SESSION["i4b"][$this->classname];
        }

        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем

        $this->Auth = new Auth();
    }

    public function getsettings()
    {
        $settings = [
            "login" => "",
            "password" => "",
            "serveraddr" => "https://api.myagent.online/api"
        ];

        return $settings;
    }

    private function AuthMyAgent()
    {
        $result = false;

        $url = $this->gds["serveraddr"] . "/user/login";
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
            $result = true;
        }

        return $result;
    }

    private function getavialistorder($date_from, $date_to)
    {
        $url = $this->gds["serveraddr"] . "/order/avia-list?";
        $query = [
            "lang" => "ru",
            "limit" => 100,
            "offset" => 0,
            "fast_filters" => "",
            "auth_key" => $this->token,
            "date_from" => $date_from,
            "date_to" => $date_to
        ];
        $encoded = "";
        if (is_array($query)) {
            foreach ($query as $name => $value) {
                $encoded .= rawurlencode($name) . '=' . rawurlencode($value) . '&';
            }
            $encoded = substr($encoded, 0, strlen($encoded) - 1);
        }
        $res = file_get_contents($url . $encoded);
        $res = json_decode($res, true);

        return $res;
    }

    public function loadorders($params)
    {
        $result = ["result" => false];

        if ($this->Auth->userauth()) {

            $serverid = $params[1];
            if ($serverid != "") {

                //Дата на входе
                $date = $this->DTV($params, [2], date("Y-m-d", time()), "Y-m-d", "Y-m-d");

                $auth = $this->AuthMyAgent();
                if ($auth) {

                    $listorders = $this->getavialistorder($date, $date);

                    if ($this->DTV($listorders, ["success"]) == true) {
                        $nowloadordertable = $this->select("myagent_tiketload", ["md5"], ["datecreate[<>]" => [$date, $date]]);
                        $nowloadorder = [];
                        if ($nowloadordertable === false) {
                            //
                        } else {
                            foreach ($nowloadordertable as $value) {
                                $nowloadorder[] = $value["md5"];
                            }
                        }
                        //var_dump($nowloadorder);

                        if ($listorders["data"]["count"] > 0) {
                            $ids = [];
                            foreach ($listorders["data"]["orders"] as $value) {
                                $orderdata = json_encode($value, JSON_UNESCAPED_UNICODE);
                                $md5order = md5($orderdata);
                                if (!in_array($md5order, $nowloadorder)) {

                                    //Этот заказ еще не сохранялся
                                    $this->phpinput = $orderdata;
                                    $this->savetiket([], 1);

                                    $id = $this->DTV($value, ["billing_number"]);
                                    //запишем транзакцию в лог
                                    $this->settransaction($id);

                                    $res = $this->insert("myagent_tiketload", ["md5" => $md5order, "datecreate" => $date]);
                                    $ids[] = $id;
                                }
                            }
                            $result = ["result" => true, "ids" => $ids];
                        }
                    }

                }

            } else {
                $result["error"] = "Server id fail";
            }

        } else {
            $result["error"] = "Authorization fail";
        }

        return $result;
    }

    public function savetiket($params, $name = 0)
    {
        $result = ["result" => false];
        $res = parent::savetiket($params, $name); // TODO: Change the autogenerated stub

        if ($res["result"]) {
            $result = [
                "status" => "success"
            ];
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

            $res = $this->http_c_post("https://btrip.ru/Parser/Myagent/", $post, $exparam);

            if (isset($res["content"])) {
                $result = json_decode($res["content"], true);
            }

        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }


}