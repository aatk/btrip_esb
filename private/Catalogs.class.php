<?php

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 01.11.2018
 * Time: 18:53
 */
class Catalogs extends ex_component
{
    private $connectionInfo;

    private $Auth;

    public function CreateDB()
    {
        $info["catalog_Places"] = array(
            "id" => array('type' => 'int(15)', 'null' => 'NOT NULL', 'inc' => true),
            "place" => array('type' => 'varchar(100)', 'null' => 'NOT NULL'),
            "parent" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "type" => array('type' => 'varchar(25)', 'null' => 'NOT NULL'),
        );

        $info["catalog_Propertys"] = array(
            "id" => array('type' => 'int(15)', 'null' => 'NOT NULL', 'inc' => true),
            "place" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "property" => array('type' => 'varchar(100)', 'null' => 'NOT NULL'),
            "value" => array('type' => 'varchar(250)', 'null' => 'NOT NULL')
        );

        $this->create($this->connectionInfo['database_type'], $info);
    }

    public function __construct($metod = "")
    {
        parent::__construct(null);
        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем

        $this->Auth = new Auth();
    }

    public function getCatalogItems($name, $filter)
    {
        $result = $this->select("catalog_".$name, "*", ["AND" => $filter]);
        return $result;
    }

    public function loadjsondata()
    {

        $content = $this->phpinput;

        $json = json_decode($content, true);

        //$idplace = 0;
        //$idpropertys = 0;
        $places = [];
        $propertys = [];
        foreach ($json as $value) {
            if (key_exists((string)$value["kod"], $places)) {
                //$nowidplace = $places[(string)$value["kod"]]["id"];
            } else {
                //$idplace = $idplace + 1;
                //$nowidplace = $idplace;

                $place = [];
                $place["id"] = $value["kod"];
                $place["place"] = $value["place"];
                if ((int)$value["parentkod"] == 0){
                    $place["parent"] = 0;
                } else {
                    $place["parent"] = $places[$value["parentkod"]]["id"];
                }
                $place["type"] = $value["vid"];

                $places[(string)$value["kod"]] =$place;
            }

            //$idpropertys +=1;
            //$property["id"] = $idpropertys;
            $property["place"] = $value["kod"];
            $property["property"] = $value["har"];
            $property["value"] = $value["value"];

            $propertys[] = $property;
        }

        $places = array_values($places);
        foreach ($places as $key => $value) {
            print_r($value);
            if ( $this->has("catalog_Places", ["id" => $value["id"]]) ) {
                $this->delete("catalog_Places", ["id" => $value["id"]]);
                $this->delete("catalog_Propertys", ["place" => $value["id"]]);
            }
            $this->insert("catalog_Places", $value);
        }

        foreach ($propertys as $key => $value) {
            print_r($value);
            $this->insert("catalog_Propertys", $value);
        }

        return ["result" => true];
    }

    public function loadjson()
    {

        $droot = $_SERVER["DOCUMENT_ROOT"];
        $content = file_get_contents($droot . "/default/data/place.json");

        $json = json_decode($content, true);

        $idplace = 0;
        $idpropertys = 0;
        $places = [];
        $propertys = [];
        foreach ($json as $value) {
            if (key_exists((string)$value["kod"], $places)) {
                $nowidplace = $places[(string)$value["kod"]]["id"];
            } else {
                $idplace = $idplace + 1;
                $nowidplace = $idplace;
                $place = [];
                $place["id"] = $nowidplace;
                $place["place"] = $value["place"];
                if ((int)$value["parentkod"] == 0){
                    $place["parent"] = 0;
                } else {
                    $place["parent"] = $places[$value["parentkod"]]["id"];
                }
                $place["type"] = $value["vid"];

                $places[(string)$value["kod"]] =$place;
            }

            $idpropertys +=1;
            $property["id"] = $idpropertys;
            $property["place"] = $nowidplace;
            $property["property"] = $value["har"];
            $property["value"] = $value["value"];

            $propertys[] = $property;
        }

        $places = array_values($places);

        $res = $this->count("catalog_Places");
        $countjson = count($places);
        if ($res != $countjson) {
            print_r("Обнаружена недозагрузка данных Мест, загружаем с позиции ".$res);
            foreach ($places as $key => $value) {
                if ($key >= $res) $this->insert("catalog_Places", $value);
            }
        } else {
            print_r("Все места загрузилось");
        }

        $res = $this->count("catalog_Propertys");
        $countjson = count($propertys);
        if ($res != $countjson) {
            print_r("Обнаружена недозагрузка данных Характеристик мест, загружаем с позиции ".$res);
            foreach ($propertys as $key => $value) {
                if ($key >= $res) $this->insert("catalog_Propertys", $value);
            }
        } else {
            print_r("Все характеристики загрузилось");
        }

        return "";
    }


    public function updateitem($params)
    {
        ob_start();
        $this->loadjsondata();
        $String = ob_get_contents();
        ob_end_clean();

        $result = ["result" => true, "message" => $String];
        return $result;
    }



}