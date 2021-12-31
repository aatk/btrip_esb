<?php

class CMSmodel extends ex_class
{
    private $metod;
    private $connectionInfo;

    public function __construct($metod = "")
    {
        parent::__construct($_SESSION["i4b"]["connectionInfo"]);   //на тот случай если мы будем наследовать от класса

        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем настройки подключения к БД
        $this->metod = $metod;
    }

    public function CreateDB()
    {

        $info["CMS_Menu_type"] = [
            "id" => ['type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true],
            "name" => ['type' => 'varchar(100)', 'null' => 'NOT NULL'],
        ];

        $info["CMS_Menu"] = [
            "id" => ['type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true],
            "parent" => ['type' => 'int(11)', 'null' => 'NOT NULL'],
            "name" => ['type' => 'varchar(50)', 'null' => 'NOT NULL'],
            "icon" => ['type' => 'varchar(50)', 'null' => 'NOT NULL'],
            "url" => ['type' => 'varchar(150)', 'null' => 'NOT NULL'],

            "Menu_type" => ['type' => 'int(11)', 'null' => 'NOT NULL'],
        ];

        $info["CMS_Menu_Rule"] = [
            "id" => ['type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true],
            "Menu_id" => ['type' => 'int(11)', 'null' => 'NOT NULL'],
            "Rule_name" => ['type' => 'varchar(100)', 'null' => 'NOT NULL'],
        ];


        $connectionInfo = $this->connectionInfo;
        $this->create($connectionInfo['database_type'], $info);
    }

    private function explodeTypes($Types)
    {
        $TypesAr = explode(",", $Types);

        $newTypes = [];
        foreach ($TypesAr as $Type) {
            $newTypes[] = trim($Type);
        }

        return $newTypes;
    }

    private function CreateTypes($Types)
    {
        $idsType = [];
        foreach ($Types as $Type) {
            $idtype = $this->get("CMS_Menu_type", ["id"], ["name" => $Type]);
            if (!isset($idtype["id"])) {
                $id = $this->insert("CMS_Menu_type", ["name" => trim($Type)]);
            } else {
                $id = $idtype["id"];
            }
            $idsType[$Type] = $id;
        }

        return $idsType;
    }

    private function AddRuleMenu($idMenu, $RuleName)
    {
        $info = $this->get("CMS_Menu_Rule", ["id"], ["Menu_id" => $idMenu, "Rule_name" => $RuleName]);
        if (!isset($info["id"])) {
            $this->insert("CMS_Menu_Rule", ["Menu_id" => $idMenu, "Rule_name" => $RuleName]);
        }
    }

    private function createMenu($element, $parent=0, $Menu_type=0)
    {
        $item = [
            "name"      => $element["name"],
            "icon"      => $element["icon"],
            "url"       => $element["url"],

            "parent"    => $element["parent"],
            "Menu_type" => $element["url"],
        ];

        $idparent = $this->insert("CMS_Menu", $item);

        if (isset($element["rule"])) {
            $this->AddRuleMenu($idparent, $element["rule"]);
        }

        if (isset($element["elements"])) {
            foreach ($element["elements"] as $innerelement) {
                $this->createMenu($innerelement, $idparent, $Menu_type);
            }
        }
    }

    public function AddMenu($Menu)
    {
//        $userui =
//            [
//                "GlobalMenu" => [
//                    [
//                        'name' => 'Users Ui',
//                        'icon' => 'pe-7s-id',
//                        'rule' => 'pe-7s-id',
//                        'elements' => [
//                            ["url" => "UsersUI/groups", "name" => "Groups", 'icon' => 'pe-7s-id'],
//                            ["url" => "UsersUI/users", "name" => "Users"],
//                            ["url" => "UsersUI/object/globalids", "name" => "Global IDs"],
//                            ["url" => "UsersUI/updatesystem", "name" => "System Update"],
//                        ]
//                    ],
//                    [
//                        'name' => 'Users Ui',
//                        'icon' => 'pe-7s-id',
//                        'elements' => [
//                            ["url" => "UsersUI/groups", "name" => "Groups", 'icon' => 'pe-7s-id'],
//                            ["url" => "UsersUI/users", "name" => "Users"],
//                            ["url" => "UsersUI/object/globalids", "name" => "Global IDs"],
//                            ["url" => "UsersUI/updatesystem", "name" => "System Update"],
//                        ]
//                    ],
//                ]
//            ];


        foreach ($Menu as $key => $MenuValue) {
            //$key - CMS_Menu_type  type1,type2,type3

            $Types = $this->explodeTypes($key);
            $idsTypes = $this->CreateTypes($Types);

            foreach ($idsTypes as $keyType => $idsType) {
                foreach ($MenuValue as $element) {
                    $this->createMenu($element, 0, $idsType);
                }
            }

        }

    }


}