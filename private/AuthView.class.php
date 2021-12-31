<?php

class AuthView extends ex_class
{
    private $metod;
    private $connectionInfo;
    private $Auth;

    public function CreateDB()
    {
        /* Описание таблиц для работы с пользователями*/

        $connectionInfo = $_SESSION["i4b"]["connectionInfo"];
        //$this->create($connectionInfo['database_type'], $info);
    }

    public function __construct($metod = "")
    {
        parent::__construct($_SESSION["i4b"]["connectionInfo"]);   //на тот случай если мы будем наследовать от класса

        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем настройки подключения к БД
        $this->metod = $metod;

        $this->Auth = new Auth();
    }

    public function Init($param)
    {
        $result = array();
        $result["result"] = false;
        $result["error"] = "Error function call";

        if (($this->metod == "POST") && (isset($param[0]))) {
            if ($param[0] == "SaveGroup") {
                $result = $this->SaveGroup($param[1]);
            } elseif ($param[0] == "DeleteGlobalID") {
                $result = $this->DeleteGlobalID($param[1]);
            } elseif ($param[0] == "SaveGlobalID") {
                $result = $this->SaveGlobalID($param[1]);
            } elseif ($param[0] == "DeleteGroup") {
                $result = $this->DeleteGroup($param[1]);
            } elseif ($param[0] == "DeleteUser") {
                $result = $this->DeleteUser($param[1]);
            } elseif ($param[0] == "AddUserRule") {
                $result = $this->AddUserRule($param[1]);
            } elseif ($param[0] == "DelUserRule") {
                $result = $this->DelUserRule($param[1]);
            } elseif ($param[0] == "SetUserGroup") {
                $result = $this->SetUserGroup($param[1]);
            }
        }

        if (($this->metod == "GET") && (isset($param[0]))) {
            if ($param[0] == "GetSnipplet") {
                $result = $this->GetSnipplet($param);
            }
        }

        return $result;

    }




    public function DeleteGlobalID($Params)
    {
        $result = ["result" => false];
        if ($this->Auth->userauth()) {
            $userinfo = $this->Auth->infouser($this->Auth->getuserid());
            if ($userinfo["info"]["su"]) {
                $id = $this->POST["id"];
                if (!empty($id)) {
                    $result = $this->Auth->deleteglobalids($id);
                }
            }
        }

        return $result;
    }

    public function SaveGlobalID($Params)
    {
        $result = ["result" => false];
        if ($this->Auth->userauth()) {
            $userinfo = $this->Auth->infouser($this->Auth->getuserid());
            if ($userinfo["info"]["su"]) {

                $id = $this->POST["id"];
                $name = $this->POST["name"];

                if (!empty($name)) {
                    $id = $this->Auth->setglobalids($name, $id);
                    if ($id != 0) {
                        $result = ["result" => true];
                    }
                }
            }
        }

        return $result;
    }



    public function DeleteGroup($Params)
    {
        $result = ["result" => false];
        if ($this->Auth->userauth()) {
            $id = $this->POST["id"];
            $result = $this->Auth->deletegroups($id);
        }

        return $result;
    }

    public function SaveGroup($Params)
    {
        $result = ["result" => false];
        $POST = $this->POST;

        if (trim($POST["name"]) != "") {
            $id = $POST["id"];
            $groupid = $this->Auth->setgroups(trim($POST["name"]), trim($POST["info"]), $id);
            $result = $this->Auth->setrulesgroups($groupid["id"], $POST["Rules"]);
        }

        return $result;
    }


    //сохранить профайл в БД
    public function SaveProFile($Params)
    {
        $result = ["result" => false];

        if ($this->Auth->userauth()) {

            $POST = $Params;

            $id_user = $POST["id"]; //$this->Auth->getuserid();
            $name = $POST["name"];

            $datas = [
                "name" => $name,
                "mail" => $POST["email"]
            ];

            if (isset($POST["avatar"])) {
                $datas["avatar"] = $POST["avatar"];
            }


            if (!empty($name)) {
                $res =$this->get("users", ["id_userinfo"], ["users.id" => $id_user]);
                $id_userinfo = $res["id_userinfo"];

                if (!empty($id_userinfo) || ($id_userinfo != 0)) {
                    $this->update("usersinfo", $datas, ["id" => $id_userinfo]);
                } else {
                    $id_userinfo = $this->insert("usersinfo", $datas);

                    $usersdatas = [
                        "id_userinfo" => $id_userinfo
                    ];
                    $this->update("users", $usersdatas, ["id" => $id_user]);
                }

                //$this->Auth->setsession($id_user);
                $result["result"] = true;

            }

        } else {
            $result["errors"] = ["Error auth"];
        }
        return $result;
    }

    public function DeleteUser($Params)
    {
        $result = ["result" => false];

        if ($this->Auth->userauth()) {

            $id = $this->POST["id"];
            $result = $this->Auth->deluser($id);
        }

        return $result;
    }

    public function DelUserRule($Params)
    {
        $result = ["result" => false];
        if ($this->Auth->userauth()) {
            $userid = $this->POST["id"];
            $ruleid = $this->POST["rule"];

            $result = $this->Auth->adduserrule($userid, $ruleid, 0);
        }

        return $result;
    }

    public function AddUserRule($Params)
    {
        $result = ["result" => false];
        if ($this->Auth->userauth()) {
            $userid = $this->POST["id"];
            $ruleid = $this->POST["rule"];

            $result = $this->Auth->adduserrule($userid, $ruleid, 1);
        }
        return $result;
    }

    public function SetUserGroup($Params)
    {
        $result = ["result" => false];
        if ($this->Auth->userauth()) {

            $userid = $this->POST["id"];
            $groups = $this->POST["groups"];

            $nowgroup = $this->Auth->getusergroups($userid);
            //print_r($nowgroup);

            $forrestruct = [];
            foreach ($nowgroup["Groups"] as $oldgroup) {
                $forrestruct[] = $oldgroup["id"];
            }

            $newgroup = array_diff($groups, $forrestruct);
            if (count($groups) == 0) {
                $delgroup = $forrestruct;
            } else {
                $delgroup = array_diff($forrestruct, $groups);
            }

//            print_r($newgroup);
//            print_r($delgroup);

            foreach ($newgroup as $group) {
                $this->Auth->setusergroup($userid, $group, 1);
            }
            foreach ($delgroup as $group) {
                $this->Auth->setusergroup($userid, $group, 0);
            }

            $result = ["result" => true];
        }

        return $result;
    }

    public function GetSnipplet($Params)
    {
        $Name = $Params[1];
        $iduser = $Params[2];

        $allrules = $this->GetAllRulesProfile($iduser);

        $Page = new Pages();
        $tampl = [
            $iduser,
            $allrules
        ];

        $content = $Page->Write(["UsersUI", "snipplets", $Name], $tampl);


        return $content;

    }

    public function GetAllGroupProfile($userid)
    {
        $AllGroups = $this->Auth->getgroups();
        $UserGroup = $this->Auth->getusergroups($userid);

        $SelectGroups = $UserGroup["Groups"];

        //print_r($UserGroup);

        $Result = [];
        foreach ($AllGroups["Groups"] as $Group) {
            foreach ($SelectGroups as $selectGroup){
                if ($selectGroup["id"] == $Group["id"]) {
                    $Group["selected"] = true;
                }
            }

            $Result[] = $Group;
        }

        return $Result;
    }

    public function GetAllRulesProfile($userid)
    {

        $AllRules = $this->Auth->getroles();
        $AllUserRules = $this->Auth->getalluserrules($userid, 1);

        $ViewRules = [];
        foreach ($AllRules["Rules"] as $Rule) {
            $Rule["checked"] = false;
            $Rule["disabled"] = false;
            $Rule["inGroup"] = [];

            if (array_key_exists($Rule["name"], $AllUserRules)) {
                $Rule["checked"] = true;

                foreach ($AllUserRules[$Rule["name"]] as $InGroup) {
                    if ($InGroup["group"] != "") {
                        $Rule["disabled"] = true;
                        $Rule["inGroup"][] = $InGroup["group"];
                    }
                }
            }

            $Rule["inGroup"] = implode(", ", $Rule["inGroup"]);

            $ViewRules[] = $Rule;
        }

        return $ViewRules;
    }

    public function GetAllRulesGroup($groupid)
    {

        $AllRules = $this->Auth->getroles();
        $AllUserRules = $this->Auth->getgrouproles($groupid);

        $ViewRules = [];
        foreach ($AllRules["Rules"] as $Rule) {
            $Rule["checked"] = false;
            if (array_key_exists($Rule["id"], $AllUserRules)) {
                $Rule["checked"] = true;
            }
            $ViewRules[] = $Rule;
        }

        return $ViewRules;
    }

    public function GetAllUsers()
    {
        $result = ["result" => false];
        if ($this->Auth->userauth()) {
            $users = $this->Auth->getusers();
            $result = ["result" => true, "Users" => $users];
        }

        return $result;
    }

}