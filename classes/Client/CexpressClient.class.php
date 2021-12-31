<?php

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 21/02/2019
 * Time: 15:09
 *
 * Вход в тестовую среду ЦЭ
 *
 */
class CexpressClient extends ex_component
{
    private $metod;
    private $Auth;


    public function __construct($metod = "")
    {
        parent::__construct($metod);
        $this->metod = $metod;

        $this->Auth = new Auth();
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

            $res = $this->http_c_post("https://btrip.ru/Parser/Cexpress/", $post, $exparam);

            if (isset($res["content"])) {
                $result = json_decode($res["content"], true);
            }

        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }

}
