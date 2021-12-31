<?php

class MyagentLite extends ex_class
{
    private $metod;
    private $Auth;

    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        $this->metod = $metod;
        parent::__construct($connectionInfo, $debug);

        $this->Auth = new Auth();
    }


    public function getinfo($Params)
    {
        $result = ["result" => false];

        if ($this->debugclass) {
            print_r("DEBUG MyagentLite");
        }

        if (class_exists("Myagent")) {

            $Model = loader("Myagent", $this->metod, $this->debugclass);
            $Model->POST = $this->POST;
            $Model->phpinput = $this->phpinput;
            $Params[1] = "v3";
            if ($this->debugclass) {
                $Params[2] = "debug";
            }

            $result = $Model->getinfo($Params);

            if ($result["result"]) {

                if ($this->Auth->haveuserrole("Parser-Btrip-FullParse")) {
                    //Пока не трогаем
                } else {
                    unset($result["json"]);
                    unset($result["jsonv2"]);

                    $jsonv3 = $result["jsonv3"];
                    $newservices = [];
                    foreach ($jsonv3["services"] as $service) {
                        $copy = true;
                        if (
                            ($service["nomenclature"] == "СборГражданскойВоздушнойАвиации") ||
                            ($service["nomenclature"] == "СборАвиаперевозчика") ||
                            ($service["nomenclature"] == "ВозвратСбораГражданскойВоздушнойАвиации") ||
                            ($service["nomenclature"] == "ВозвратСборАвиаперевозчика")
                        ) {
                            $copy = false;
                        }

                        //price	VATrate	amountVAT	amount
                        //pricecustomer	VATratecustomer	amountVATcustomer	amountclient
                        $service["pricecustomer"] = $service["price"];
                        $service["VATratecustomer"] = $service["VATrate"];
                        $service["amountVATcustomer"] = $service["amountVAT"];
                        $service["amountclient"] = $service["amount"];

                        if ($copy) {
                            $newservices[] = $service;
                        }
                    }

                    $jsonv3["services"] = $newservices;
                    $result["jsonv3"] = $jsonv3;
                }
            } else {
                if (isset($result["error"])) {
                    $result["errors"][] = $result["error"];
                    unset($result["error"]);
                }
            }

        } else {
            $result["errors"] = ["not found Myagent parser"];
        }

        if ($this->debugclass) {
            //print_r($result);
            //json_encode($result);
            //print_r(json_last_error_msg());
        }

        return $result;
    }


}