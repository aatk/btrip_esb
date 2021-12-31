<?php

class Cloud extends ex_class
{

    private $connectionInfo;
    private $metod;
    private $dirname;

    public function __construct($metod = "", $debug = false)
    {
        $this->debugclass = $debug;
        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем настройки подключения к БД
        parent::__construct($this->connectionInfo, $debug);

        $this->metod = $metod;
        $this->dirname = "cloudfiles";
    }

    public function SetDirname ($dirname) {
        $this->dirname = $dirname;//"democloud";
    }

    public function CreateDB()
    {
        /* Описание таблиц для работы с пользователями*/
        $info["cloudfiles"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "md5" => array('type' => 'varchar(150)', 'null' => 'NOT NULL')
        );
        $info["clouds_vdisk"] = array(
            "id" => array('type' => 'int(15)', 'null' => 'NOT NULL', 'inc' => true),
            "dirmd5"  => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "isdir" => array('type' => 'int(1)', 'null' => 'NOT NULL'),
            "name" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "ext" => array('type' => 'varchar(15)', 'null' => 'NOT NULL'),
            "createdate"  => array('type' => 'datetime', 'null' => 'NOT NULL'),
            "md5"  => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "serverid" => array('type' => 'int(15)', 'null' => 'NOT NULL')
        );

        $info["clouds_vservers"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "patch" => array('type' => 'varchar(150)', 'null' => 'NOT NULL')
        );


        $info["clouds_vpaths"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "parent" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "patch" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "md5"  => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "parentmd5"  => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "serverid" => array('type' => 'int(15)', 'null' => 'NOT NULL')
        );

        $res = $this->create($this->connectionInfo['database_type'], $info);
    }

    /**
     * @param $fullfilename
     * @param null $in_md5
     * @param int $serverid
     * @return array|mixed
     */
    public function add_to_vdisk($fullfilename, $in_md5 = null, $serverid = 0) {

        $fileinfo = pathinfo($fullfilename);

        $dirname = mb_strtolower($fileinfo['dirname']);
        $dirmd5 = md5($dirname);

        $isdir = 1;
        $md5 = "";
        $ext = $fileinfo['extension'];
        $name = $fileinfo['filename'];
        $create = date("Y-m-d H:i:s");
        if (isset($in_md5)) {
            $isdir = 0;
            $md5 = $in_md5;
        }


        $sqlparams = ["dirmd5"  => $dirmd5, "isdir" => $isdir, "name" => $name, "ext" => $ext, "serverid" => $serverid];
        $oldinfo = $this->get("clouds_vdisk", ["id" , "md5"], ["AND" => $sqlparams]);
        if ($oldinfo) {
            //print_r($oldinfo);
            $sqlparams = ["dirmd5"  => $dirmd5,"isdir" => $isdir, "name" => $name, "ext" => $ext, "createdate"  => $create, "md5"  => $md5, "serverid" => $serverid];
            $whot = ["md5" => $md5];
            $this->update("clouds_vdisk", $whot, ["AND" => $sqlparams]);
            $this->del($oldinfo["md5"]);
            $res = $oldinfo["id"];
        } else {

            $sqlparams = [
                "dirmd5" => $dirmd5,
                "isdir" => $isdir,
                "name" => $name,
                "ext" => $ext,
                "createdate" => $create,
                "md5" => $md5,
                "serverid" => $serverid
            ];
            $res = $this->insert("clouds_vdisk", $sqlparams);
        }


        return $res;
    }

    public function add_to_vpatchs($fullfilename, $in_md5 = null, $serverid = 0) {

        $fileinfo = pathinfo($fullfilename);
        $dirname = mb_strtolower($fileinfo['dirname']);
        $dirmd5new = md5($dirname);


        $exd = explode("/", $dirname);
        while (count($exd)>0) {

            $dirname = mb_strtolower(implode("/", $exd));
            $dirmd5 = md5($dirname);

            if (count($exd)>0) {
                unset($exd[count($exd)-1]);
            }
            $parent = mb_strtolower(implode("/",$exd));
            $parentmd5 = md5($parent);

            $sqlparams = ["md5"  => $dirmd5];
            $oldinfo = $this->get("clouds_vpaths", ["id" , "md5"], ["AND" => $sqlparams]);
            if ($oldinfo) {
                break;
            } else {
                $sqlparams = ["parent" => $parent, "patch" => $dirname, "md5" => $dirmd5, "parentmd5" => $parentmd5, "serverid" => $serverid];
                $res = $this->insert("clouds_vpaths", $sqlparams);
            }

        }

        return $dirmd5new;
    }


    public function vscandir($dir) {
        if (substr($dir, -1) == "/") {
            $dir = substr($dir, 0, -1);
        };
        $md5dir = md5(mb_strtolower($dir));

        $result = [];
        $oldinfo = $this->select("clouds_vdisk", ["name", "ext", "md5"], ["dirmd5" => $md5dir]);
        if ($oldinfo) {
            foreach ($oldinfo as $value) {
                $result[] = $value["name"].".".$value["ext"];
            }
        }

        return $result;
    }

    public function vfile_exists($fullfilename) {

        $result = false;

        $fileinfo = pathinfo($fullfilename);
        $dirname = mb_strtolower($fileinfo['dirname']);
        $dirmd5 = md5($dirname);

        $ext = $fileinfo['extension'];
        $name = $fileinfo['filename'];
        $filename = $name.".".$ext;

        $AND = ["dirmd5" => $dirmd5, "name" => $name, "ext" => $ext];

        $oldinfo = $this->get("clouds_vdisk", ["name", "ext"], ["AND" => $AND]);
        if ($oldinfo) {
            $result = true;
        }

        return $result;
    }

    public function vfile_put_contents($fullfilename, $content) {

    }

    public function vfile_info($fullfilename) {

        $result = "";
        $fileinfo = pathinfo($fullfilename);

        $dirname = mb_strtolower($fileinfo['dirname']);
        $dirmd5 = md5($dirname);

        $ext = $fileinfo['extension'];
        $name = $fileinfo['filename'];

        $AND = ["dirmd5" => $dirmd5, "name" => $name, "ext" => $ext];

        $result = $this->get("clouds_vdisk", "md5", ["AND" => $AND]);
        //if ($oldinfo) {
        //    $result = $this->getcontent($oldinfo, $name.".".$ext); //$this->download($oldinfo, $name.".".$ext);
        //}

        return $result;
    }

    public function vfile_get_contents($fullfilename) {

        $result = "";
        $fileinfo = pathinfo($fullfilename);

        $dirname = mb_strtolower($fileinfo['dirname']);
        $dirmd5 = md5($dirname);

        $ext = $fileinfo['extension'];
        $name = $fileinfo['filename'];

        $AND = ["dirmd5" => $dirmd5, "name" => $name, "ext" => $ext];

        $oldinfo = $this->get("clouds_vdisk", "md5", ["AND" => $AND]);
        if ($oldinfo) {
            $result = $this->getcontent($oldinfo, $name.".".$ext); //$this->download($oldinfo, $name.".".$ext);
        }

        return $result;
    }

    public function Init($param)
    {
        $result = array();

        if (($this->metod == "POST") && (isset($param[0])) && (isset($param[1]))) {
            if ($param[0] == "upload") {
                $result = $this->upload($param);

            }
        } elseif (($this->metod == "GET")) {
            if ($param[0] == "download") {
                $result = $this->download($param[1], $param[2]);

            } elseif ($param[0] == "test") {
                $result = $this->test($param[1]);

            }
        } elseif (($this->metod == "DELETE")) {

            if ($param[0] == "delete") {
                $result = $this->del($param[1]);

            }
        }

        return $result;
    }

    public function upload($params, $content = "")
    {
        //
        unset($params[0]);
        $dirnamesave = implode("/", $params);
        if ($content == "") {
            $content = $this->phpinput;
        }
        $md5 = md5($content);

        $dirname = $this->dirname;
        $patch1 = substr($md5, 0, 2);
        $patch2 = substr($md5, 2, 2);

        $dir = $_SERVER["DOCUMENT_ROOT"];
        $exdir = $dir."/".$dirname."/".$patch1.'/'.$patch2;
        $filename = $exdir."/".$md5;

        //$this->echo_log("ИМЯ ФАЙЛА - ".$filename);
        if (!file_exists($filename)) {

            if (!file_exists($dir . "/" . $dirname)) {
                mkdir($dir . "/" . $dirname);
            }
            if (!file_exists($dir . "/" . $dirname . "/" . $patch1)) {
                mkdir($dir . "/" . $dirname . "/" . $patch1);
            }
            if (!file_exists($exdir)) {
                mkdir($exdir);
            }

            $res = file_put_contents($filename.".file", $content);
            if ($res === false) {
                $this->echo_log("ОШИБКА ЗАПИСИ ФАЙЛА - ".$filename);
            }
        }

        $this->insert("cloudfiles", ["md5" => $md5]);
        //$res = $this->add_to_vdisk($dirnamesave, $md5);
        $res = $this->add_to_vpatchs($dirnamesave, $md5);
        $res = $this->add_to_vdisk($dirnamesave, $md5);

        return ["result" => true, "id" => $md5];
    }

    public function content_force_download($content, $infilename)
    {
        if (isset($content)) {
            // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
            // если этого не сделать файл будет читаться в память полностью!
            if (ob_get_level()) {
                ob_end_clean();
            }

            $filename = iconv("cp1251", "utf-8", basename($infilename));

            // заставляем браузер показать окно сохранения файла
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename*=UTF-8' . urlencode($filename));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . count($content));
            // читаем файл и отправляем его пользователю
            echo ($content);
//            if ($delfile === true) {
//                unlink($file);
//            };

            $this->trueexit(0);
        }
    }

    public function file_force_download($file, $infilename, $delfile = false)
    {
        if (file_exists($file)) {
            // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
            // если этого не сделать файл будет читаться в память полностью!
            if (ob_get_level()) {
                ob_end_clean();
            }

            $filename = iconv("cp1251", "utf-8", basename($infilename));

            // заставляем браузер показать окно сохранения файла
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename*=UTF-8' . urlencode($filename));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            // читаем файл и отправляем его пользователю
            readfile($file);
            if ($delfile === true) {
                unlink($file);
            };
            $this->trueexit(0);
        }
        return false;
    }

    public function download($md5, $filename)
    {
        $dirname = $this->dirname;
        $patch1 = substr($md5, 0, 2);
        $patch2 = substr($md5, 2, 2);

        $dir = $_SERVER["DOCUMENT_ROOT"];
        $exdir = $dir."/".$dirname."/".$patch1.'/'.$patch2;
        $file = $exdir."/".$md5;

        //print_r($filename);
        if ($this->file_force_download($file, $filename, false) === false) {
            $this->file_force_download($file.".file", $filename, false);
        }

        $this->trueexit(0);
    }

    public function getcontent($md5, $filename = "")
    {
        $dirname = $this->dirname;
        $patch1 = substr($md5, 0, 2);
        $patch2 = substr($md5, 2, 2);

        $dir = $_SERVER["DOCUMENT_ROOT"];
        $exdir = $dir."/".$dirname."/".$patch1.'/'.$patch2;
        $file = $exdir."/".$md5;

        $content = "";
        if (file_exists($file)) {
            $content = file_get_contents($file);
        } elseif (file_exists($file.".file")) {
            $content = file_get_contents($file.".file");
        } elseif (file_exists($file.".zip")) {
            $content = file_get_contents($file.".zip");
        }

        return $content;
    }

    public function del($md5) {

        $count = $this->count("cloudfiles", [
            "md5" => $md5
        ]);

        if ($count > 1) {
            $id = $this->get("cloudfiles", "id", ["md5" => $md5]);
            $this->delete("cloudfiles", ["id" => $id]);

        } else {
            $this->delete("cloudfiles", ["md5" => $md5]);

            $dirname = $this->dirname;
            $patch1 = substr($md5, 0, 2);
            $patch2 = substr($md5, 2, 2);

            $dir = $_SERVER["DOCUMENT_ROOT"];
            $exdir = $dir."/".$dirname."/".$patch1.'/'.$patch2;
            $filename = $exdir."/".$md5;

            unlink($filename);
        }

        return ["result" => true];
    }

    private function test($param1) {
        return $param1;
    }

}
