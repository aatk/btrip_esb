<?php

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 04.10.2018
 * Time: 14:33
 */
class HotelbookClient extends ex_component
{
    private $metod;
    private $gds;
    private $connectionInfo;

    private $Auth;


    public function __construct($metod = "")
    {
        parent::__construct($metod);

        $this->metod = $metod;
        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем
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
            "userId" => "",
            "userPass" => "",
            "serveraddr" => "https://www.hotelbook.pro/xml"
        ];

        return $settings;
    }

    //Список заказов

    private function order_list($time, $datefrom)
    {
        /*
            <?xml version="1.0" encoding="utf-8"?>
            <OrderListRequest>
                [<CheckInFrom>...</CheckInFrom>]
                [<CheckInTo>...</CheckInTo>]
                [<CreatedFrom>...</CreatedFrom>]
                [<CreatedTo>...</CreatedTo>]
                [<ChangedFrom>...</ChangedFrom>]
                [<ChangedTo>...</ChangedTo>]
                [<MessagesOnlineFrom>...</MessagesOnlineFrom>]
                [<MessagesOnlineTo>...</MessagesOnlineTo>]
                [<OnlyNotReadMessages>...</OnlyNotReadMessages>]
                [<Agents>
                  <Agent>...<Agent>
                </Agents>]
            </OrderListRequest>
         */
        $data = [
            'request' => '<?xml version="1.0" encoding="utf-8"?><OrderListRequest><CreatedFrom>' . $datefrom . '</CreatedFrom><ChangedFrom>' . $datefrom . '</ChangedFrom></OrderListRequest>'
        ];

        $login = $this->gds["userId"];
        $password = $this->gds["userPass"];

        $url = $this->gds["serveraddr"] . "/order_list?login=" . $login . "&time=" . $time . "&checksum=" . md5(md5($password) . $time);
        $res = $this->http_c_post($url, $data, ["gzip"]);

        return $res;
    }

    private function load_orders_list($content)
    {
        /*
         <?xml version="1.0" encoding="utf-8"?>
        <OrderListResponse>
         [<OrderListRequest>...</OrderListRequest>] - Request for this response
         [<Errors>
            <Error code="..." description="..."> - ошибки
          </Errors>]
          <OrderList>
            <Orders agent="..."> - список заказов (может быть много)
              <Order id="..." state="..." via_xml_gate="true|false" tag="..."> - список элементов заказа (может быть несколько)
                <HotelItem id="..." state="...">
                  <HotelId>...</HotelId>
                  [<HotelName>...</HotelName>]
                  [<CheckIn>...</CheckIn>]
                  [<Duration>...</Duration>]
                  [<Created>...</Created>]
                  [<Price></Price>]
                  [<Currency></Currency>]
                  [<Currency></Currency>]
                  [<Rooms>
                    <Room roomSizeId=".." roomTypeId=".." roomViewId=".." cots="...">
                      <Paxes>
                        <Pax>
                          [<Title>...</Title>]
                          [<FirstName>...</FirstName>]
                          [<LastName>...</LastName>]
                        </Pax>
                      </Paxes>
                    </Room>
                  </Rooms>]
                  [<Logs></Logs>]
                </HotelItem>
                <MessagesOnline> - блок сообщений
                  <Message> - сообщение
                    <Id>...</Id> - ID сообщения
                    <Message>...</Message> - текст сообщения
                    <Direction>...</Direction> - Собственное сообщение, либо нет
                    <isRead>...</isRead> - Прочитано ли сообщение
                    <Date>...</Date> - Дата добавления сообщения
                    <User> - Пользователь отправивший сообщение
                      <Name>...</Name> - Имя пользователя
                    </User>
                  </Message>
                </MessagesOnline>
              </Order>
            </Orders>
          </OrderList>
        </OrderListResponse>
         */

        $result = ["result" => false, "content" => $content];

        $xml = simplexml_load_string($content);

        $ordersid = [];
        $ordersxml = $xml->OrderList->Orders;
        foreach ($ordersxml as $orders) {
            //цикл по агентам
            foreach ($orders->Order as $order) {
                $json = $this->object2array($order);
                $id = $this->DTV($json, ["@attributes", "id"]);
                $ordersid[] = $id;

                $orderxml = $order->asXML();

                $md5source = md5($orderxml);
                $ids = $this->get("hotelbook_orderload", ["id", "md5"], ["AND" => ["md5" => $md5source, "datecreate[<>]" => [date("Y-m-d"), date("Y-m-d")]]]);
                if ($ids) {
                    //Такой заказ уже загружался
                } else {
                    $this->phpinput = $orderxml;
                    $this->savetiket([], 1);

                    $this->insert("hotelbook_orderload", ["md5" => $md5source, "datecreate" => date("Y-m-d")]); //ЗАпишем данные о том что заказ загружался
                }

                //запишем транзакцию в лог
                $this->settransaction($id);
            }
        }


        return $ordersid;
    }

    public function loadorders($param1)
    {

        $result = ["result" => false];
        if ($this->Auth->userauth()) {
            //if (true) {

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

                $time = (string)time(); //$this->gettime();
                $res = $this->order_list($time, $maxdateorders);
                $ordersid = $this->load_orders_list($res["content"]);

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

            $res = $this->http_c_post("https://btrip.ru/Parser/Hotelbook/", $post, $exparam);

            if (isset($res["content"])) {
                $result = json_decode($res["content"], true);
            }

        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }


}
