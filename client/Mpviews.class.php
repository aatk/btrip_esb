<?php

class Mpviews extends ex_component {

    private $connectionInfo;
    private $metod;
    private $Marketplace;

    public function __construct($metod = "")
    {
        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"];
        parent::__construct($metod, $this->connectionInfo);

        $this->metod = $metod;

        $this->Marketplace = new Marketplace();
    }

    public function downloadcomponent($params) {
        $result = '<p>Ошибка выполнения в системе!</p>';
        $res = $this->Marketplace->addcomponent($params);
        if ($res["result"]) {
            //Всё хорошо, покажем БД в которые будут установлены компоненты
            $result = "";
            foreach ($res["dbs"] as $db) {
                $result .= "<p>$db</p>";
            }
        } elseif(isset($res["message"])) {
            $result = '<p>'.$res["message"].'</p>';
        }

        return $result;
    }

    public function passwordreset($params) {
        $result = '<p>Ошибка выполнения в системе!</p>';
        $result = $this->Marketplace->resetpassword($_REQUEST["login"]);

        if ($result["result"]) {
            //Всё хорошо, покажем БД в которые будут установлены компоненты
            $result = '<p>'.$result["message"].'</p>';
        } elseif (isset($res["message"])) {
            $result = '<p>'.$result["message"].'</p>';
        }
        return $result;
    }

    public function resetpassword($params) {
        $result = '<h3>'.$params["message"].'</h3>';
        return $result;
    }



}