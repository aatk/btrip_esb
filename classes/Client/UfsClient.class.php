<?php

class UfsClient extends ex_component
{

    private $metod;
    private $gds;
    private $connectionInfo;
    private $Auth;



    public function __construct($metod = "")
    {
        parent::__construct(null);
        $this->metod = $metod;
        $this->gds = $_SESSION["i4b"][$this->classname];

        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем

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

            $res = $this->http_c_post("https://btrip.ru/Parser/Ufs/", $post, $exparam);

            if (isset($res["content"])) {
                $result = json_decode($res["content"], true);
            }

        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }


}
