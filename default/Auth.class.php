<?php

class Auth extends ex_class
{

    private $metod;
    private $connectionInfo;

    public function __construct($metod = "")
    {
        parent::__construct($_SESSION["i4b"]["connectionInfo"]);   //на тот случай если мы будем наследовать от класса

        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем настройки подключения к БД
        $this->metod = $metod;

        //$this->SetGlobalID($_SESSION["auth"]["globalid"]);

        if (isset($_SESSION["auth"]["globalid"])) {
            $this->SetGlobalID($_SESSION["auth"]["globalid"]);
        } else {
            $this->SetGlobalID(0);
        }
    }

    /* Функция для установки нужны таблиц для класса */
    public function CreateDB()
    {
        /* Описание таблиц для работы с пользователями*/
        $info["users"] = array(
            "id" => ['type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true],
            "login" => ['type' => 'varchar(150)', 'null' => 'NOT NULL'],
            "password" => ['type' => 'varchar(150)', 'null' => 'NOT NULL'],
            "id_userinfo" => ['type' => 'int(15)', 'null' => 'NOT NULL'],
            "su" => ['type' => 'bool'],
            "globalid" => ['type' => 'int(15)', 'null' => 'NOT NULL'],
            "markdel" => ['type' => 'bool', 'null' => 'NULL']
        );

        $info["Auth_Groups"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "name" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "info" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "globalid" => ['type' => 'int(15)', 'null' => 'NOT NULL']
        );
        $info["Auth_GroupRoles"] = array(
            "id" => ['type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true],
            "Auth_Groups_id" => ['type' => 'int(11)', 'null' => 'NOT NULL'],
            "Rules_id" => ['type' => 'int(11)', 'null' => 'NOT NULL']
        );

        $info["Auth_UserGroups"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "users_id" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "Auth_Groups_id" => array('type' => 'int(15)', 'null' => 'NOT NULL')
        );

        $info["roles"] = array(
            "id" => ['type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true],
            "name" => ['type' => 'varchar(150)', 'null' => 'NOT NULL'],
            "info" => ['type' => 'text'],
            "disabled" => ['type' => 'bool', 'null' => 'NOT NULL']
        );

        $info["userroles"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "userid" => array('type' => 'int(11)', 'null' => 'NOT NULL'),
            "roleid" => array('type' => 'int(11)', 'null' => 'NOT NULL')
        );

        $info["Auth_RulesEnabled"] = [
            "id" => ['type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true],
            "rule_id" => ['type' => 'int(11)', 'null' => 'NOT NULL'],
            "user_id" => ['type' => 'int(11)', 'null' => 'NOT NULL'],
        ];

        $info["userrestorepsw"] = array(
            "id" => array('type' => 'int(15)', 'null' => 'NOT NULL', 'inc' => true),
            "userid" => array('type' => 'int(11)', 'null' => 'NOT NULL'),
            "login" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "dateendlive" => array('type' => 'datetime', 'null' => 'NOT NULL'),
            "newtoken" => array('type' => 'varchar(36)', 'null' => 'NOT NULL')
        );
        $info["oauth"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "access_token" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),

            "device_id" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "device_name" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "token_type" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),

            "starttime" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "expires_in" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "endtime" => array('type' => 'int(15)', 'null' => 'NOT NULL'),

            "iduser" => array('type' => 'int(15)', 'null' => 'NOT NULL'),
            "tel_number" => array('type' => 'varchar(15)', 'null' => 'NOT NULL')
        );
        $info["clients"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "client_id" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "client_secret" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "redirect_uri" => array('type' => 'varchar(1500)', 'null' => 'NULL')
        );
        $info["codes"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "client_id" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "tel_number" => array('type' => 'varchar(15)', 'null' => 'NOT NULL'),
            "tel_code" => array('type' => 'varchar(15)', 'null' => 'NOT NULL'),
            "endtime" => array('type' => 'int(15)', 'null' => 'NOT NULL')
        );
        $info["stokens"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "system" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "token" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "stoken" => array('type' => 'varchar(50)', 'null' => 'NOT NULL')
        );
        $info["tokens"] = [
            "id" => ['type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true],
            "userid" => ['type' => 'varchar(50)', 'null' => 'NOT NULL'],
            "stoken" => ['type' => 'varchar(50)', 'null' => 'NOT NULL'],
            "token" => ['type' => 'varchar(50)', 'null' => 'NOT NULL'],

            "username" => ['type' => 'varchar(50)', 'null' => 'NOT NULL'],
            "userhash" => ['type' => 'varchar(50)', 'null' => 'NOT NULL'],

            "dateendlive" => ['type' => 'datetime', 'null' => 'NOT NULL'],
            "ssoparams" => ['type' => 'text', 'null' => 'NOT NULL']
        ];

        $info["usersinfo"] = [
            "id" => ['type' => 'int(15)', 'null' => 'NOT NULL', 'inc' => true],
            "name" => ['type' => 'varchar(150)', 'null' => 'NULL'],
            "mail" => ['type' => 'varchar(150)', 'null' => 'NULL'],
            "avatar" => ['type' => 'longblob', 'null' => 'NULL']
        ];

        $info["auth_globalids"] = [
            "id" => ['type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true],
            "name" => ['type' => 'varchar(50)', 'null' => 'NOT NULL']
        ];

        $connectionInfo = $_SESSION["i4b"]["connectionInfo"];
        $this->create($connectionInfo['database_type'], $info);
    }

    public function InstallModule()
    {
        $this->setrule("Auth-Btrip-UserAdmin", "Auth-Btrip-UserAdmin", "Administrator of the user group", true);

        $globalids = ["id" => 1, "name" => "default"];

        if (!$this->has("auth_globalids", ["id" => $globalids["id"]])) {
            $this->insert("auth_globalids", ["id" => $globalids["id"], "name" => $globalids["name"]]);
        }

        $res = $this->select("users", ["id", "globalid"]);
        foreach ($res as $user) {
            if ($user["globalid"] == 0) {
                $this->update("users", ["globalid" => 1], ["id" => $user["id"]]);
            }
        }
    }


    public function Init($param)
    {
        $result = array();
        $result["result"] = false;
        $result["error"] = "Error function call";

        if (($this->metod == "POST") && (isset($param[0])) && (isset($param[1]))) {
            if ($param[0] == "tel") {
                $result = $this->send_code($param[1]);
            } elseif ($param[0] == "code") {
                $result = $this->set_code($param[1]);
//            } elseif ($param[0] == "refresh") {
//                $result = $this->refreshtoken($param[1]);
            } elseif ($param[0] == "forseadduser") {
                $result = $this->forseadduser($param);
            } elseif ($param[0] == "createtoken") {
                $result = $this->createtoken($param);
            }

        } elseif (($this->metod == "PATCH") && (isset($param[0])) && (isset($param[1]))) {
//            if ($param[0] == "user") {
//                $result = $this->setuserinfo($param[1]);
//            }

        } elseif (($this->metod == "GET") && (isset($param[0])) && (isset($param[1]))) {
            if ($param[0] == "info") {
                $params_token = array(
                    "access_token" => $_REQUEST["access_token"],
                    "device_id" => $_REQUEST["device_id"],
                    "device_name" => $_REQUEST["device_name"]
                );
                $result = $this->userinfo($params_token);
            } elseif ($param[0] == "token") {
                $result = $this->auth($param[1]);
//            } elseif ($param[0] == "user") {
//                $result = $this->getuserinfo($param[1]);
            } elseif ($param[0] == "haveuser") {
                $result = $this->haveuser($param[1], $param[2]);
            } elseif ($param[0] == "unlogin") {
                $result["result"] = true;
                $this->unlogin();
            }
        }

        return $result;

    }


    private function generate_code()
    {
        $random_string = ("" . rand(1, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9));
        return $random_string;
    }

    private function generate_password($number)
    {
        $arr = array('a', 'b', 'c', 'd', 'e', 'f',
            'g', 'h', 'i', 'j', 'k', 'l',
            'm', 'n', 'o', 'p', 'r', 's',
            't', 'u', 'v', 'x', 'y', 'z',
            'A', 'B', 'C', 'D', 'E', 'F',
            'G', 'H', 'I', 'J', 'K', 'L',
            'M', 'N', 'O', 'P', 'R', 'S',
            'T', 'U', 'V', 'X', 'Y', 'Z',
            '1', '2', '3', '4', '5', '6',
            '7', '8', '9', '0', '.', ',',
            '(', ')', '[', ']', '!', '?',
            '&', '^', '%', '@', '*', '$',
            '<', '>', '/', '|', '+', '-',
            '{', '}', '`', '~');
        // Генерируем пароль
        $pass = "";
        for ($i = 0; $i < $number; $i++) {
            // Вычисляем случайный индекс массива
            $index = rand(0, count($arr) - 1);
            $pass .= $arr[$index];
        }
        return $pass;
    }

    private function check_client($client_id, $client_secret = null)
    {
        $result = false;
        $where = array("client_id" => $client_id);
        if ($client_secret != null) {
            $where["client_secret"] = $client_secret;
            $where = array("AND" => $where);
        }
        $res = $this->get("clients", "id", $where);
        if (isset($res["id"])) {
            $result = true;
        }
        return $result;
    }

    private function send_code($to)
    {
        $client_id = $_REQUEST["client_id"];
        if ($this->check_client($client_id)) {
            $random_string = $this->generate_code();

            $message = $_SESSION["wm"]["sms_message"];
            $message = str_replace("%code%", $random_string, $message);
            $id = $_SESSION["wm"]["sms_id"];

            //Сохраним значения в сессии
            $sms = new sms($id);
            $body = $sms->Init(array("send", $to, $message));//send($to, $message);

            $body_p = substr($body, 0, 3);//explode(" ", $body);
            if ($body_p == "100") {
                $insert_param = array(
                    "client_id" => $client_id,
                    "tel_number" => $to,
                    "tel_code" => $random_string,
                    "endtime" => time() + 300        //5 минут на ввод кода из СМС
                );
                $ids = $this->insert("codes", $insert_param);
                //$result["result"] = true;
                //$ids = $this->insert("codes", $insert_param);
                if ($ids) {
                    $result["result"] = true;
                } else {
                    $result["result"] = false;
                    $result["message"] = $this->error();
                }
            } else {
                $result["result"] = false;
            }
            $result["body"] = $body;
        } else {
            $result["result"] = false;
            $result["message"] = "Error client";
        }

        return $result;
    }

    private function set_code($tel_number)
    {
        $result = array();

        $client_id = $_REQUEST["client_id"];
        $client_secret = $_REQUEST["client_secret"];

        if ($this->check_client($client_id, $client_secret)) {
            $_SESSION["wm"]["tel_number"] = $tel_number;

            $where = array(
                "ORDER" => "id",
                "AND" => array(
                    "client_id" => "" . $_REQUEST["client_id"],
                    "tel_number" => "" . $tel_number,
                    "endtime[>]" => time()
                )
            );

            $res = $this->get("codes", array("tel_code"), $where);
            $tel_code = $res["tel_code"];
            $code = $_REQUEST["code"];

            if (isset($tel_code) && isset($code) && ($code == $tel_code)) {
                $cms_user = new cms($_SESSION["wm"]["cms_system"]);
                $userinfo = $cms_user->Init(array("createuser", $tel_number, $tel_code)); //создадим нового пользователя

                if ($userinfo["result"]) {

                    $_SESSION["wm"]["iduser"] = $userinfo["result"];
                    $params_token = array(
                        "grant_type" => "authorization_code",
                        "code" => $code,
                        "client_id" => $_REQUEST["client_id"],
                        "client_secret" => $_REQUEST["client_secret"],
                        "device_id" => $_REQUEST["device_id"],
                        "device_name" => $_REQUEST["device_name"]
                    );
                    $token = $this->gettoken($params_token);

                    $result = $token;
                } else {
                    $result = $userinfo;
                }

            } else {
                $result["result"] = false;
                $result["message"] = "Codes do not match";
            }
        } else {
            $result["result"] = false;
            $result["message"] = "Error client";
        }
        return $result;
    }


    private function get_new_token($params_token)
    {

        $key = md5(
            $params_token["code"] . ":" .
            $params_token["client_id"] . ":" .
            $params_token["grant_type"] . ":" .
            $params_token["device_id"] . ":" .
            $params_token["device_name"] . ":" .
            $params_token["secret_code"] . ":" .
            time()
        );
        $date = 3600;

        $token = array(
            "access_token" => $key,
            "token_type" => "bearer",
            "expires_in" => $date
        );

        return $token;
    }

    public function gettoken($params_token)
    {
        //Генерируем новый токен для доступа к данным
        //grant_type=authorization_code&client_id=464119&client_secret=deadbeef&code=DoRieb0y&redirect_uri=http%3A%2F%2Fexample.com%2Fcb%2F123

        //  grant_type=authorization_code
        //  & code=<код подтверждения>
        // [& client_id=<идентификатор приложения>]
        // [& client_secret=<пароль приложения>]
        // [& device_id=<идентификатор устройства>]
        // [& device_name=<имя устройства>]

        $token = $this->get_new_token($params_token);

        $insert_param = $token;
        $insert_param["device_id"] = $params_token["device_id"];
        $insert_param["device_name"] = $params_token["device_name"];

        $insert_param["starttime"] = time();
        $insert_param["endtime"] = time() + $insert_param["expires_in"];

        $insert_param["iduser"] = $_SESSION["wm"]["iduser"];
        $insert_param["tel_number"] = $_SESSION["wm"]["tel_number"];
        $idoauth = $this->insert("oauth", $insert_param);

        if ($idoauth == 0) {
            //error
            $result["result"] = false;
            $result["message"] = $this->error();
        } else {
            $result = $token;
        }

        return $result;
    }

//    public function refreshtoken($params_token)
//    {
//        //Генерируем новый токен на основе старого
//
//        //  grant_type=authorization_code
//        //  & access_token=<старый токен>
//        // [& client_id=<идентификатор приложения>]
//        // [& client_secret=<пароль приложения>]
//        // [& device_id=<идентификатор устройства>]
//        // [& device_name=<имя устройства>]
//        $client_id = $_REQUEST["client_id"];
//        $client_secret = $_REQUEST["client_secret"];
//
//        if ($this->check_client($client_id, $client_secret)) {
//            $params_token = array(
//                "access_token" => $_REQUEST["access_token"],
//                "client_id" => $_REQUEST["client_id"],
//                "client_secret" => $_REQUEST["client_secret"],
//                "device_id" => $_REQUEST["device_id"],
//                "device_name" => $_REQUEST["device_name"]
//            );
//
//            $tokeninfo = $this->userinfo($params_token, false); //Ищем включая просроченные токены
//            if ($tokeninfo["result"]) {
//                if ($tokeninfo["result"]["endtime"] > time()) {
//                    //Токен просрочен
//                    $result["result"] = false;
//                    $result["message"] = "You have working token";
//                } else {
//                    $newcode = $this->generate_code();
//                    $params_token["code"] = $newcode;
//                    $_SESSION["wm"]["iduser"] = $tokeninfo["result"]["iduser"];
//                    $_SESSION["wm"]["tel_number"] = $tokeninfo["result"]["tel_number"];
//
//                    $res = $this->gettoken($params_token);
//                    $result["result"] = true;
//                    $result["message"] = $res;
//                }
//            } else {
//                $result["result"] = false;
//                $result["message"] = "Error access token";
//            }
//        } else {
//            $result["result"] = false;
//            $result["message"] = "Error client";
//        }
//        return $result;
//
//    }


//    private function auth($params = "")
//    {
//        //Авторизация по токену
//        //  & access_token=<старый токен>
//        // [& device_id=<идентификатор устройства>]
//        // [& device_name=<имя устройства>]
//        $params_token = array(
//            "access_token" => $_REQUEST["access_token"],
//            "device_id" => $_REQUEST["device_id"],
//            "device_name" => $_REQUEST["device_name"]
//        );
//
//        $tokeninfo = $this->userinfo($params_token);
//        if ($tokeninfo["result"]) {
//            //попробуем авторизоваться в CMS
//            $tokeninfo = $tokeninfo["result"];
//            $iduser = $tokeninfo["iduser"];
//
//            $cms_user = new cms($_SESSION["wm"]["cms_system"]);
//            $userinfo = $cms_user->Init(array("authuser", $tokeninfo["iduser"])); //создадим нового пользователя
//
//            $_SESSION["wm"]["iduser"] = $tokeninfo["iduser"];
//            $_SESSION["wm"]["tel_number"] = $tokeninfo["tel_number"];
//
//            $result["result"] = true;
//            $result["message"] = "Authorization complete";
//        } else {
//            $result["result"] = false;
//            $result["message"] = "Authorization error";
//        }
//        return $result;
//    }

//    private function userinfo($params_token, $now = true)
//    {
//        //Отзываем токен для доступа к данным
//
//        $where = array(
//            "ORDER" => "id",
//            "AND" => array(
//                "access_token" => $params_token["access_token"],
//                "device_id" => $params_token["device_id"],
//                "device_name" => $params_token["device_name"],
//                "token_type" => "bearer"
//            )
//        );
//        if ($now) {
//            $where["AND"]["endtime[>]"] = time();
//        }
//
//        $tokeninfo = $this->get("oauth", "*", $where);
//        if ($tokeninfo === false) {
//            $result["result"] = false;
//            $result["message"] = "I don't know this token";
//        } else {
//            $result["result"] = $tokeninfo;
//        }
//
//        return $result;
//    }
//
//    public function revoketoken($token)
//    {
//        //Отзываем токен для доступа к данным
//    }
//

    /**                 **/

    public function unlogin()
    {
        $this->setsession("");
    }

    public function createtoken($params)
    {
        if (isset($_POST["login"])) {
            $login = $_POST["login"];
            $token = $_POST["token"];
            $hash = $_POST["hash"];

            $UserName = $_POST["UserName"];
            $UserHash = $_POST["UserHash"];
        } else {
            $inputparams = json_decode($this->phpinput, true);

            $login = $this->DTV($inputparams, ["login"]);
            $token = $this->DTV($inputparams, ["token"]);
            $hash = $this->DTV($inputparams, ["hash"]);

            $UserName = $this->DTV($inputparams, ["UserName"]);
            $UserHash = $this->DTV($inputparams, ["UserHash"]);
        }

        $inputparams = json_decode($this->phpinput, true);

        //$token = md5($login.":".$stoken);

        /*
        $url = "https://reports.btrip.ru/api/auth/createtoken/";
        $stoken = "e0a4c3972c879e1ebd67ec50afc5682e";

        $login = "login";
        $password = md5("password");
        $token = "a56823972c879e1ebd0a4c67ec50afcd";
        $hash = md5($login.":".$password.":".$stoken);

        $POST = [
            "login" => $login,
            "token" => $token,
            "hash" => $hash
        ];


        $postdata = http_build_query($POST);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'content' => $postdata,
            ],
        ]);
        $json = file_get_contents($url, false, $context);
        */

        $result = ["result" => false];
        $resultuserid = $this->havelogin($login);
        if ($resultuserid["result"]) {
            $id = $resultuserid["guid"];
            $info = $this->infouser($id);
            if ($info["result"]) {
                $md5password = $info["info"]["password"];
                $infotoken = $this->getstoken($token);
                if ($infotoken["result"]) {
                    $stoken = $infotoken["info"]["stoken"];
                    $chash = md5($login . ":" . $md5password . ":" . $stoken);
                    if ($chash == $hash) {


                        //контрольная сумма соответствует
                        $dateendlive = date("Y-m-d H:i:s", strtotime("+1 day"));
                        $chash = md5($login . ":" . $md5password . ":" . $stoken . ":" . $dateendlive);
                        $item = [
                            "token" => $chash,
                            "dateendlive" => $dateendlive
                        ];
                        $result = ["result" => true, "info" => $item];

                        $item["userid"] = $id;
                        $item["stoken"] = $stoken;
                        $item["username"] = $UserName;
                        $item["userhash"] = $UserHash;
                        $item["ssoparams"] = json_encode($this->DTV($inputparams, ["Params"], ""), JSON_UNESCAPED_UNICODE);


                        $idtoken = $this->get("tokens", ["id"], ["AND" => ["userid" => $id, "username" => $UserName, "stoken" => $stoken]]);
                        //var_dump($idtoken);
                        if ($idtoken === false) {
                            $this->insert("tokens", $item);
                        } else {
                            $this->update("tokens", $item, ["id" => $idtoken["id"]]);
                        }

                    } else {
                        $result["error"] = "Error hash";
                    }
                } else {
                    $result["error"] = "Error token";
                }

            } else {
                $result["error"] = "Error user info";
            }
        } else {
            $result["error"] = "Error user found";
        }

        return $result;
    }

    private function getstoken($token)
    {
        $result = ["result" => false];
        $id = $this->get("stokens", "*", ["token" => $token]);
        if ($id) {
            $result = ["result" => true, "info" => $id];
        }
        return $result;
    }


    public function havelogin($login)
    {
        $result = ["result" => false];
        $id = $this->get("users", "id", ["login" => $login]);
        if ($id) {
            $result = ["result" => true, "guid" => $id];
        }
        return $result;
    }

    public function infouser($id)
    {
        $result = ["result" => false];
        //$id = $this->get("users, userinfo", "*", ["users.id" => $id, "userinfo.id" => "users.id_userinfo"]);

        $field = [
            "users.id",
            "users.login",
            "users.password",
            "users.globalid",
            "users.su",
            "usersinfo.name",
            "usersinfo.mail",
        ];

        $id = $this->get("users", ["[>]usersinfo" => ["id_userinfo" => "id"]], $field, ["users.id" => $id]);
        if ($id) {
            $result = ["result" => true, "info" => $id];
        }
        return $result;
    }


    private function geturlavatar($iduser)
    {
        $result = "";
        $users = $this->get("users", ["id_userinfo"], ["id" => $iduser]);
        if ($users) {
            $userinfo = $this->get("usersinfo", ["avatar"], ["id" => $users["id_userinfo"]]);

            $path = $_SERVER["DOCUMENT_ROOT"] . "/tmp/imgusers";
            if (!file_exists($path)) {
                mkdir($path);
            }
            $filespath = "/tmp/imgusers/" . $iduser . ".jpg";

            file_put_contents($_SERVER["DOCUMENT_ROOT"] . $filespath, $userinfo["avatar"]);
            $result = $filespath;
        }

        return $result;
    }

    public function getuserinfo($guid)
    {
        $auth = [];
        if ($this->userauth()) {
            $aroles = $this->getalluserrules($guid);
            $infouser = $this->infouser($guid);
            $avatarurl = $this->geturlavatar($guid);

            $auth["idauth"] = $guid;
            $auth["enable"] = true;
            $auth["globalid"] = $infouser["info"]["globalid"];
            $auth["sudo"] = $infouser["info"]["su"];

            $auth["roles"] = $aroles;

            $auth["login"] = $infouser["info"]["login"];
            $auth["password"] = $infouser["info"]["password"];

            $auth["name"] = $infouser["info"]["name"];
            $auth["mail"] = $infouser["info"]["mail"];
            $auth["avatar"] = $avatarurl;
        }

        return $auth;
    }

    public function setsession($guid = "")
    {
        $auth = [];
        if (isset($_SESSION["auth"])) {
            $auth = $_SESSION["auth"];
        }

        if ($guid == "") {
            $_SESSION["auth"] = [];

            $auth["idauth"] = false;
            $auth["enable"] = false;
            $auth["roles"] = [];

        } else {

            $aroles = $this->getalluserrules($guid);
            $infouser = $this->infouser($guid);
            $avatarurl = $this->geturlavatar($guid);

            $auth["idauth"] = $guid;
            $auth["enable"] = true;
            $auth["globalid"] = $infouser["info"]["globalid"];
            $auth["sudo"] = $infouser["info"]["su"];

            $auth["roles"] = $aroles;

            $auth["login"] = $infouser["info"]["login"];
            $auth["password"] = $infouser["info"]["password"];

            $auth["name"] = $infouser["info"]["name"];
            $auth["mail"] = $infouser["info"]["mail"];
            $auth["avatar"] = $avatarurl;
        }

        $_SESSION["auth"] = $auth;
    }

    public function addusersessioninfo($key, $value)
    {
        $_SESSION["auth"][$key] = $value;
    }

    public function getusersessioninfo()
    {
        return $_SESSION["auth"];
    }

    /** USERS */

    public function getusers($id = 0)
    {

        $filters = [];
        if (!$this->is_su()) {
            $filters["AND"]["globalid"] = $this->gid;
        }

        if ($id == 0) {
            $filters["AND"]["markdel"] = false;
            $result = $this->select("users", "*", $filters);
        } else {
            $filters["AND"]["id"] = $id;
            $result = $this->get("users", "*", $filters);
        }
        return $result;

    }

    public function is_su()
    {
        $result = false;
        if ($_SESSION["auth"]["sudo"] == true) {
            $result = true;
        }
        return $result;
    }

    public function forseadduser($inparams)
    {
        $result = ["result" => false];

        if ($this->userauth()) {
            $login = $inparams[1];
            $passwordmd5 = $inparams[2];

            $id = false;
            $res = $this->havelogin($login);
            if ($res["result"]) {
                $id = $res["guid"];
                $this->moduser($id, ["login" => $login, "password" => $passwordmd5]);
            } else {
                $id = $this->insert("users", ["login" => $login, "password" => $passwordmd5]);
            }

            if ($id) {
                $result = ["result" => true, "guid" => $id];
            }
        }
        return $result;
    }

    public function haveuser($login, $password)
    {

        $result = ["result" => false];
        $id = $this->get("users", "id", ["AND" => ["login" => $login, "password" => md5($password)]]);
        if ($id) {
            $result = ["result" => true, "guid" => $id];
        }
        return $result;
    }

    public function adduser($login, $password, $su = false, $gid = 0)
    {
        $result = ["result" => false];

        $setusersuirule = false;

        $globalid = $this->gid;
        if ($su) {
            $globalid = $gid;
        }

        if ($globalid == 0) {
            //Нет gid - надо назначть пользователю группу
            $parts = explode("@", $login);
            $globalidname = $parts[1];

            $res = $this->getglobalid($globalidname);
            if ($res["result"]) {
                //Есть такой gid
                $globalid = $res["id"];
            } else {
                //Нет такого gid - делаем пользователя его администратором
                $globalid = $this->setglobalids($globalidname);

                $setusersuirule = true;
            }
        }

        $users = [
            "login" => $login,
            "password" => md5($password),
            "id_userinfo" => 0,
            "su" => 0,
            "globalid" => $globalid,
            "markdel" => 0
        ];

        $id = $this->insert("users", $users);
        if ($id) {
            if ($setusersuirule) {
                $idrule = $this->getrulebyname("Auth-Btrip-UserAdmin");
                $this->setuserrule($id, $idrule);
            }

            $result = ["result" => true, "guid" => $id, "setusersuirule" => $setusersuirule];
        }

        return $result;

    }

    public function moduser($guid, $params)
    {
        $result = ["result" => false];
        $id = $this->update("users", $params, ["id" => $guid]);
        if ($id) {
            $result = ["result" => true];
        }
        return $result;
    }

    public function deluser($guid)
    {
        $result = ["result" => false];

        $this->update("users", ["markdel" => true], ["id" => $guid]);
        $result = ["result" => true];

        return $result;
    }

    public function adduserrule($guid, $ruleid, $do)
    {
        $result = ["result" => false];
        if ($do == 0) {
            $res = $this->getrule($ruleid);
            if (isset($res["Rule"])) {

                $delete = true;
                if ($res["Rule"][0]["disabled"]) {
                    //Заблокировано
                    $usernowid = $this->getuserid();
                    if (!$this->RuleEnabled($usernowid, $ruleid)) {
                        $delete = false;
                    }
                }

                if ($delete) {
                    $this->delete("userroles", ["userid" => $guid, "roleid" => $ruleid]);
                    $result = ["result" => true];
                } else {
                    $result["errors"] = ["Rule is bloked for you"];
                }
            }
        } elseif ($do == 1) {

            $res = $this->getrule($ruleid);
            if (isset($res["Rule"])) {

                $insert = true;
                if ($res["Rule"][0]["disabled"]) {
                    //Заблокировано
                    $usernowid = $this->getuserid();
                    if (!$this->RuleEnabled($usernowid, $ruleid)) {
                        $insert = false;
                    }
                }

                if ($insert) {
                    if (!$this->has("userroles", ["userid" => $guid, "roleid" => $ruleid])) {
                        $this->insert("userroles", ["userid" => $guid, "roleid" => $ruleid]);
                        $result = ["result" => true];
                    }
                } else {
                    $result["errors"] = ["Rule is bloked for you"];
                }
            }
        }

        return $result;
    }

    public function setusergroup($guid, $group, $do)
    {

        if ($do == 0) {
            $this->delete("Auth_UserGroups", ["users_id" => $guid, "Auth_Groups_id" => $group]);
        } elseif ($do == 1) {
            if (!$this->has("Auth_UserGroups", ["users_id" => $guid, "Auth_Groups_id" => $group])) {
                $this->insert("Auth_UserGroups", ["users_id" => $guid, "Auth_Groups_id" => $group]);
            }
        }

        return ["result" => true];
    }

    /**    GLOBAL IDS         **/
    public function setglobalids($newname, $id = 0)
    {
        if (empty($id) || ($id == 0)) {
            $id = $this->insert("auth_globalids", ["name" => $newname]);
        } else {
            $this->update("auth_globalids", ["name" => $newname], ["id" => $id]);
        }

        return $id;
    }

    public function deleteglobalids($id = 0)
    {
        $this->delete("auth_globalids", ["id" => $id]);
        return ["result" => true];
    }

    public function getglobalids($id = 0)
    {
        if ($id == 0) {
            $result = $this->select("auth_globalids", "*");
        } else {
            $result = $this->get("auth_globalids", ["id", "name"], ["id" => $id]);
        }
        return $result;
    }

    public function getglobalid($name = "")
    {
        $result = ["result" => false];
        if (trim($name) != "") {
            $result = $this->get("auth_globalids", ["id", "name"], ["name" => trim($name)]);
            if (isset($result["id"])) {
                $result["result"] = true;
            }
        }
        return $result;
    }


    /**    GROUPS             **/

    public function setgroups($newname, $info, $id = 0)
    {
        $result = ["result" => false];
        if ($this->userauth()) {

            $oldrole = [];
            if ($id != 0) {
                $oldrole = $this->get("Auth_Groups", "*", ["id" => $id, "globalid" => $this->gid]);
            }

            $idroles = 0;
            if (isset($oldrole["id"])) {
                $roles = $this->update("Auth_Groups", ["name" => $newname, "info" => $info], ["id" => $oldrole["id"]]);
                $idroles = $oldrole["id"];
            } else {
                $idroles = $this->insert("Auth_Groups", ["name" => $newname, "info" => $info, "globalid" => $this->gid]);
            }

            $result["result"] = true;
            $result["id"] = $idroles;
        }

        return $result;
    }

    public function setrulesgroups($id, $rules)
    {
        $result = ["result" => false];
        if ($this->userauth()) {

            $this->delete("Auth_GroupRoles", ["Auth_Groups_id" => $id]);
            foreach ($rules as $rule) {

                $res = $this->getrule($ruleid);
                if (isset($result["Rule"])) {

                    $insert = true;
                    if ($result["Rule"]["disabled"]) {
                        //Заблокировано
                        $guid = $this->getuserid();
                        if (!$this->RuleEnabled($guid, $ruleid)) {
                            $insert = false;
                        }
                    }

                    if ($insert) {
                        $this->insert("Auth_GroupRoles", ["Auth_Groups_id" => $id, "Rules_id" => $rule]);
                    } else {
                        $result["errors"][] = ["Rules ID: " . $ruleid . " is bloked for you"];
                    }
                }
            }

            $result["result"] = true;
        }

        return $result;
    }

    public function getgroups()
    {
        $result = ["result" => false];
        if ($this->userauth()) {
            $roles = $this->select("Auth_Groups", "*", ["globalid" => $this->gid]);

            $result["result"] = true;
            $result["Groups"] = $roles;
        }

        return $result;
    }

    public function getgroup($groupid)
    {
        $result = ["result" => false];
        if ($this->userauth()) {
            $roles = $this->get("Auth_Groups", "*", ["id" => $groupid, "globalid" => $this->gid]);

            $result["result"] = true;
            $result["info"] = $roles;
        }

        return $result;
    }

    public function getusergroups($guid)
    {
        $result = ["result" => false];
        if ($this->userauth()) {
            $usergroups = $this->select("Auth_UserGroups", ["[>]Auth_Groups" => ["Auth_Groups_id" => "id"]], ["Auth_Groups.id", "Auth_Groups.name"], ["users_id" => $guid]);

            $result["result"] = true;
            $result["Groups"] = $usergroups;
        }

        return $result;
    }

    public function getgrouproles($guid)
    {
        $aroles = [];
        $roles = $this->select("Auth_GroupRoles", ["[>]roles" => ["Rules_id" => "id"]], ["Auth_GroupRoles.Rules_id", "roles.name"], ["Auth_Groups_id" => $guid]);
        foreach ($roles as $vrole) {
            $aroles[$vrole["Rules_id"]] = $vrole["name"];
        }

        return $aroles;
    }

    public function deletegroups($id = 0)
    {
        $result = ["result" => false];
        if ($this->has("Auth_Groups", ["id" => $id, "globalid" => $this->gid])) {

            $this->delete("Auth_Groups", ["id" => $id, "globalid" => $this->gid]);
            $this->delete("Auth_GroupRoles", ["Auth_Groups_id" => $id]);
            $this->delete("Auth_UserGroups", ["Auth_Groups_id" => $id]);

            $result = ["result" => true];
        }
        return $result;
    }

    /** RULES  */

    public function setrule($newname, $oldname = "", $info = "", $disabled = false)
    {
        $result = ["result" => false];

        $oldrole = [];
        if ($oldname != "") {
            $oldrole = $this->get("roles", "*", ["name" => $oldname]);
        }

        $idroles = 0;
        if (isset($oldrole["id"])) {
            $roles = $this->update("roles", ["name" => $newname, "info" => $info, "disabled" => $disabled], ["id" => $oldrole["id"]]);
            $idroles = $oldrole["id"];
        } else {
            $oldrole = $this->get("roles", "*", ["name" => $newname]);
            if (!isset($oldrole["id"])) {
                $idroles = $this->insert("roles", ["name" => $newname, "info" => $info, "disabled" => $disabled]);
            }
        }

        $result["result"] = true;
        $result["id"] = $idroles;

        return $result;
    }

    public function getroles()
    {
        $result = ["result" => false];
        if ($this->userauth()) {
            $roles = $this->select("roles", "*");

            $result["result"] = true;
            $result["Rules"] = $roles;
        }

        return $result;
    }

    public function getrule($id)
    {
        $result = ["result" => false];
        if ($this->userauth()) {
            $rules = $this->select("roles", "*", ["id" => $id]);

            $result["result"] = true;
            $result["Rule"] = $rules;
        }

        return $result;
    }

    private function getrulebyname($name)
    {
        $result = $this->get("roles", ["id"], ["name" => trim($name)]);
        return $result["id"];
    }

    private function setuserrule($user, $rule)
    {
        $result = false;
        if (!$this->has("userroles", ["userid" => $user, "roleid" => $rule] )) {
            $result = $this->insert("userroles", ["userid" => $user, "roleid" => $rule]);
        }
        return $result;
    }

    public function RuleEnabled($userid, $ruleid)
    {
        $result = $this->has("Auth_RulesEnabled", ["user_id" => $userid, "rule_id" => $ruleid]);
        return $result;
    }

    public function getuserroles($guid)
    {
        $aroles = [];
        $roles = $this->select("userroles", ["[>]roles" => ["roleid" => "id"]], ["roles.name"], ["userid" => $guid]);
        foreach ($roles as $vrole) {
            $aroles[] = $vrole["name"];
        }

        return $aroles;
    }

    public function getalluserrules($userid, $returntype = 0)
    {

        $fullrules = [];

        $aroles = [];
        $roles = $this->select("userroles", ["[>]roles" => ["roleid" => "id"]], ["roles.id", "roles.info", "roles.name"], ["userid" => $userid]);
        foreach ($roles as $vrole) {
            $aroles[] = $vrole["name"];
            $fullrules[$vrole["name"]][] = ["id" => $vrole["id"], "info" => $vrole["info"], "group" => ""];
        }

        $roles = $this->select("Auth_UserGroups",
            [
                "[>]Auth_GroupRoles" => ["Auth_UserGroups.Auth_Groups_id" => "Auth_Groups_id"],
                "[>]Auth_Groups" => ["Auth_UserGroups.Auth_Groups_id" => "id"],
                "[>]roles" => ["Auth_GroupRoles.Rules_id" => "id"]
            ], [
                "roles.id",
                "roles.name",
                "roles.info",
                "Auth_Groups.id(groupid)",
                "Auth_Groups.name(groupname)"
            ], [
                "Auth_UserGroups.users_id" => $userid
            ]);

        foreach ($roles as $vrole) {
            $aroles[] = $vrole["name"];
            $fullrules[$vrole["name"]][] = ["id" => $vrole["id"], "info" => $vrole["info"], "group" => $vrole["groupname"], "groupid" => $vrole["groupid"]];
        }

        $result = $aroles;
        if ($returntype == 1) {
            $result = $fullrules;
        }
        return $result;
    }

    public function haveuserrole($namerole = "")
    {
        $result = false;
        $auth = [];
        if (isset($_SESSION["auth"])) {
            $auth = $_SESSION["auth"];
        }
        if (($namerole != "") && (in_array($namerole, $auth["roles"]))) {
            $result = true;
        }

        if ($auth["sudo"] === true) {
            $result = true;
        }

        return $result;
    }


    public function getuserid($guid = "")
    {
        $auth["idauth"] = false;
        if (isset($_SESSION["auth"])) {
            $auth = $_SESSION["auth"];
        }
        return $auth["idauth"];
    }

    public function basicauth()
    {
        $resut = false;
        if (isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])) {
            $login = $_SERVER["PHP_AUTH_USER"];
            $password = $_SERVER["PHP_AUTH_PW"];
            $res = $this->haveuser($login, $password);
            if ($res["result"]) {
                $this->setsession($res["guid"]);

                $this->addusersessioninfo("basicpassword", $_SERVER["PHP_AUTH_PW"]);
                $resut = true;
            }
        }
        return $resut;
    }

    public function PHPSESSIDauth()
    {
        $resut = false;
        if (isset($_GET["PHPSESSID"])) {
            $PHPSESSID = $_GET["PHPSESSID"];

            $i4b = $_SESSION["i4b"];
            unset($i4b["auth"]);
            unset($i4b["db_connect"]);

            session_unset();
            session_destroy();
            session_id($PHPSESSID);
            session_start();

            $_SESSION["i4b"] = $i4b;
        }
        return $resut;
    }

    public function GetSSOParams()
    {
        return $_SESSION["SSO"]["Params"];
    }

    public function SetSSOParams($Params)
    {
        $_SESSION["SSO"]["Params"] = $Params;
    }

    public function oauth()
    {

        $result = false;
        if (isset($_GET["token"])) {
            $token = $_GET["token"];

            $dateendlive = date("Y-m-d H:i:s");
            $res = $this->get("tokens", ["id", "userid", "stoken", "token", "dateendlive", "ssoparams", "username", "userhash"], ["AND" => ["token" => $token, "dateendlive[>]" => $dateendlive]]);
            if ($res !== false) {
                $userid = $this->havelogin($res["username"]);
                if ($userid["result"]) {
                    $this->setsession($userid["guid"]);
                } else {
                    $this->setsession($res["userid"]);
                    $this->addusersessioninfo("login", $res["username"]);
                    $this->addusersessioninfo("password", $res["userhash"]);
                }
                $this->SetSSOParams(json_decode($res["ssoparams"], true));
                $result = true;
            }
        }

        return $result;
    }

    public function userauth()
    {
        $result = false;
        if (isset($_SESSION["auth"]) && ($_SESSION["auth"]["enable"])) {
            $result = true;
        }

        if ($this->basicauth()) {
            $result = true;
        }

        if ($this->PHPSESSIDauth()) {
            $result = true;
        }

        if ($this->oauth()) {
            $result = true;
        }
        return $result;
    }

    public function setpageafterlogin($page = "")
    {
        if (isset($_SESSION["auth"])) {
            $auth = $_SESSION["auth"];
        } else {
            $auth = [];
        }
        if ($page == "") {
            if (isset($auth["pageafterlogin"])) {
                unset($auth["pageafterlogin"]);
            }
        } else {
            $auth["pageafterlogin"] = $page;
        }
        $_SESSION["auth"] = $auth;
    }

    public function gotopageafterlogin()
    {
        if (isset($_SESSION["auth"])) {
            $auth = $_SESSION["auth"];
        } else {
            $auth = [];
        }
        if (isset($auth["pageafterlogin"])) {
            $this->setpageafterlogin();
            header('Location:' . $auth["pageafterlogin"]); //componentlist'."?PHPSESSID=".session_id()); // . $auth["pageafterlogin"]);
            unset($_SESSION["db_connect"]);
            exit(0);
        }
    }

    public function getpageafterlogin()
    {
        $result = "";
        if (isset($_SESSION["auth"])) {
            $auth = $_SESSION["auth"];
        } else {
            $auth = [];
        }
        if (isset($auth["pageafterlogin"])) {
            $result = $auth["pageafterlogin"];
        }
        return $result;
    }


    //Фукции ниже надо переделать, тексты не должны быть в открытом виде
    //Почту нужно отправлять через свой класс, на случай если поменяется скрипт отправки

    public function sendpass($email, $urlconfirm, $subject, $messagetemplate)
    {
        $this->delete("userrestorepsw", ["dateendlive[<=]" => date("Y-m-d H:i:s")]);

        $userid = 0;
        $rid = $this->havelogin($email);
        if ($rid["result"]) {
            $userid = $rid["guid"];
        }

        $code = md5($this->generate_code());
        $dates = [
            "userid" => $userid,
            "login" => $email,
            "dateendlive" => date("Y-m-d H:i:s", strtotime("+1 hours")),
            "newtoken" => $code
        ];
        $id = $this->insert("userrestorepsw", $dates);

        $href = $this->baseurl() . "/" . $urlconfirm . "?token=" . $code;

        if (class_exists("Pages")) {
            //$messagetemplate = "x.y.z";
            $messagetemplatear = explode(".", $messagetemplate);
            $Params = [
                $href, $email, $userid
            ];
            $Pages = new  Pages();
            $message = $Pages->Init($messagetemplatear, $Params);
        } else {
            $message = $messagetemplate;
        }

        $mail = new Mail();
        $Address = ["Address" => [$email]];
        $res = $mail->SendMail($Address, $subject, $message);
        if ($res["result"]) {
            $result = ["result" => true, "message" => 'Message has been sent'];
        } else {
            $result = ["result" => false, "message" => 'Error: ' . $res["message"]];
        }

        return $result;
    }

    public function sendpasstomail($token, $subject, $messagetemplate)
    {
        $result = ["result" => false, "message" => "Unexpected error"];

        $this->delete("userrestorepsw", ["dateendlive[<=]" => date("Y-m-d H:i:s")]);
        $restoreinfo = $this->get("userrestorepsw", ["id", "userid", "login"], ["newtoken" => $token]);
        if ($restoreinfo) {

            $email = $restoreinfo["login"];
            $userid = $restoreinfo["userid"];
            $newpass = $this->generate_password(10);

            $this->delete("userrestorepsw", ["id" => $restoreinfo["id"]]);

            if ($userid == 0) {
                //Создадим нового
                $res = $this->adduser($email, $newpass);
                $userid = $res["guid"];
            } else {
                $this->moduser($userid, ["password" => md5($newpass)]);
            }

            if (class_exists("Pages")) {
                //$messagetemplate = "x.y.z";
                $messagetemplatear = explode(".", $messagetemplate);
                $Params = [
                    $newpass, $email, $userid
                ];
                $Pages = new  Pages();
                $message = $Pages->Init($messagetemplatear, $Params);
            } else {
                $message = $messagetemplate;
            }

            $mail = new Mail();
            $res = $mail->SendMail(["Address" => [$email]], $subject, $message);
            if ($res["result"]) {
                $result = ["result" => true, "message" => 'Password sent to your email'];
            } else {
                $result = ["result" => false, "message" => 'The password was NOT sent. Error sending message: ' . $res["message"]];
            }

        } else {
            $result = ["result" => false, "message" => "The token is not current"];
        }

        return $result;
    }

}
