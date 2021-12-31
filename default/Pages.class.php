<?php
session_start();

class Pages extends ex_class
{

    private $Patch;
    private $Auth;

    private $connectionInfo;
    private $metod;

    private $listscript = ["php", "html", "htm", "css", "js"];
    private $listfiles = ["svg", "jpg", "png"];
    public $str = "/pages/";
    //содержит полный путь до папки с которой мы будем работать (включительно), задается в конктрукторе
    public $full_str;

    /* Конструктор
    На входе
      $connectionInfo - описание подключения к БД
      $metod - метод вызова класса GET, POST, PATCH и т.д.
    */
    public function __construct($metod = "")
    {
        $this->full_str = getcwd() . $this->str;
        $this->Patch = $_SERVER["DOCUMENT_ROOT"] . "/";
        parent::__construct($_SESSION["i4b"]["connectionInfo"]);   //на тот случай если мы будем наследовать от класса

        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем настройки подключения к БД
        $this->metod = $metod;

        $this->Auth = new Auth();
    }

    public function InstallModule()
    {
        $dirlist = ["pages"];
        foreach ($dirlist as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir);
            }
        }
    }


    /* Функция для установки нужны таблиц для класса */
    public function CreateDB()
    {
        /* Описание таблиц для работы с пользователями*/
        /*
          "id"            - id страницы
          "name"          - название страницы
          "text"          - содержание страницы
          "path"          - путь к странице
          "typep"         - тип страницы 0/1 (внешняя/из БД)
          "authorization" - требуется авторизация пользователя или нет (да/нет)
        */
        $info["pages"] = array(
            "id" => array('type' => 'int(15)', 'null' => 'NOT NULL', 'inc' => true),
            "name" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "path" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "text" => array('type' => 'text', 'null' => 'NOT NULL'),
            "typep" => array('type' => 'int(1)', 'null' => 'NOT NULL'),
            "authorization" => array('type' => 'int(1)', 'null' => 'NOT NULL')
        );

        /* Перелинковка страниц
          "id"      - id страницы перелинковки
          "name"    - имя страницы
          "idpage"  - id страницы из таблицы pages
        */
        $info["errorpages"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "name" => array('type' => 'varchar(150)', 'null' => 'NOT NULL'),
            "idpage" => array('type' => 'int(11)', 'null' => 'NOT NULL')
        );

        $connectionInfo = $_SESSION["i4b"]["connectionInfo"];
        $this->create($connectionInfo['database_type'], $info);
    }


    public function Savepage($Params)
    {
        $result = ["result" => false];
        if ($this->Auth->userauth()) {

            $POST = $this->POST;

            //если поля ИМЯ или ПУТЬ пустые, то выводим ошибку
            if (!(empty($POST["name"]) || empty($POST["path"]))) {

                //индикатор ошибок
                $err = false;

                $id = $POST["id"];
                $name = $POST["name"];
                $path_old = $path = $POST["path"];
                if (!empty($POST["path_old"])) {
                    $path_old = $POST["path_old"];
                }
                $typep = ($POST["typep"] == "on") ? true : false;
                $authorization = ($POST["authorization"] == "on") ? true : false;
                //$text = $POST["myCodeMirror_text"];
                $full_path = $this->full_str . $path;
                $full_path_old = $this->full_str . $path_old;

                $text = null;
                $datas = [
                    "name" => $name,
                    "path" => $path,
                    "typep" => $typep,
                    "authorization" => $authorization,
                ];

                $arrayEnd = explode(".", $full_path_old);
                $ext = end($arrayEnd);
                if (in_array($ext, $this->listscript)) {
                    $text = $POST["myCodeMirror_text"];
                    $datas["text"] = $text;
                } elseif (file_exists($full_path_old)) {
                    $text = file_get_contents($full_path_old);
                    $datas["text"] = $text;
                }

                //$result["errors"] = ["Поле Text пустое"];
                //if (!empty($text)) {

                //удаляем файл и старые папки (если они пустые)
                unlink($this->full_str . $path_old);
                while ($full_path_old !== $this->full_str) {
                    if (rmdir(dirname($full_path_old)) === false) {
                        break;
                    } else {
                        $full_path_old = dirname($full_path_old);
                    }
                }

                //$typep = false - создать папку и в ней файл
                if (!$typep) {
                    if (is_null($text)) {
                        $res = $this->get("pages", ["text"], ["id" => $id]);
                        $text = $res["text"];
                    }

                    //если папка не существует то надо ее создать
                    if (!file_exists(dirname($full_path))) {
                        if (!mkdir(dirname($full_path), 0777, true)) {
                            $result["errors"] = ["Ошибка создания каталогов"];
                            $err = true;
                        }
                    }

                    //если папка есть или успешно создалась, то сохраняем файл (если нет ошибок)
                    if (!$err) {
                        if (file_put_contents($full_path, $text) === False) {
                            $result["errors"] = ["Ошибка создания файла"];
                            $err = true;
                        } else {
                            $result["name_old"] = basename($path);
                            $result["path_old"] = $path;
                        }
                    }
                }

                //update/insert в БД (если нет ошибок)
                if (!$err) {
                    if ($id == 0 || $id == "File") {
                        $id = $this->insert("pages", $datas);
                    } else {
                        $this->update("pages", $datas, ["id" => $id]);
                    }
                    $result["id"] = $id;
                    $result["name"] = $name;
                    $result["result"] = true;
                }
            } else {
                $result["errors"] = ["Поля Name и Path не могут быть пустыми"];
            }
        } else {
            $result["errors"] = ["Error auth"];
        }
        return $result;
    }

    public function ListImages($Params)
    {
        return $this->listfiles;
    }

    //сохранить все файлы в БД
    public function AllInDBPage($Params)
    {
        $result = ["result" => false];
        if ($this->Auth->userauth()) {

            //получаем список всех файлов из папки
            $Pages_files = $this->GetPagesList_Files([$this->full_str, strlen($this->full_str)]);
            foreach ($Pages_files["data"] as $value) {

                //$id = $value["path"];
                $path = $value["path"];
                $text = file_get_contents($full_path = $this->full_str . $path);

                $datas = [
                    "name" => $value["name"],
                    "path" => $path,
                    "typep" => "1",
                    "authorization" => "0",
                    "text" => $text
                ];

                //если в БД уже есть файл с данным путем, то pdate, иначе insert
                $id = $this->get("pages", ["id"], ["path" => $path]);
                if (empty($id)) {
                    $this->insert("pages", $datas);
                } else {
                    $this->update("pages", $datas, ["id" => $id]);
                }

                //удаляем файл
                unlink($full_path);
                //удаляем  старые папки (если они пустые)
                $full_path = dirname($full_path);
                while ($full_path . "/" !== $this->full_str) {
                    if (rmdir($full_path) === false) {
                        break;
                    } else {
                        $full_path = dirname($full_path);
                    }
                }
            }

            $result["result"] = true;
            $result["mess"] = "Выполнено";
        } else {
            $result["errors"] = ["Error auth"];
        }
        return $result;
    }

    public function GetPageInfo($Params)
    {
        $result = ["result" => false];

        if ($this->Auth->userauth()) {
            $res = [];
            switch ($Params["typep"]) {
                case "1":
                    $res = $this->get("pages", ["id", "name", "path", "typep", "authorization", "text"], ["id" => $Params["id"]]);
                    break;
                case "":
                    $res = ["id" => "0", "name" => "", "path" => "", "typep" => "0", "authorization" => "0", "text" => ""];
                    break;
                case "0":
                    $text = file_get_contents($this->full_str . $Params["path"]);
                    $res = $this->get("pages", ["id", "name", "path", "typep", "authorization", "text"], ["id" => $Params["id"]]);
                    if (empty($res)) {
                        $res = ["id" => "File", "name" => basename($Params["path"]), "path" => $Params["path"], "typep" => "0", "authorization" => "0", "text" => $text];
                    }
                    $res["text"] = $text;
                    break;
            }
            $result["result"] = true;
            $result["data"] = $res;
        } else {
            $result["errors"] = ["Error auth"];
        }

        return $result;
    }

    public function GetPagesList($Params)
    {
        $result = ["result" => false];

        if ($this->Auth->userauth()) {
            $res = $this->select("pages", ["id", "name", "path", "typep", "authorization"]);
            $result["result"] = true;
            $result["data"] = $res;
        } else {
            $result["errors"] = ["Error auth"];
        }

        return $result;
    }

    public function GetPagesList_Files($Params)
    {
        $result = ["result" => false];
        $res = [];

        if ($this->Auth->userauth()) {
            $res = $this->GetPagesList_Files_Recursion($Params, $res);
            $result["result"] = true;
            $result["data"] = $res;
        } else {
            $result["errors"] = ["Error auth"];
        }

        return $result;
    }

    public function GetPagesList_Files_Recursion($Params, $re)
    {
        $files = scandir($Params[0]);
        $res = $re;
        foreach ($files as $value) {
            if (is_file($Params[0] . $value)) {
                $res[] = ["id" => "File", "name" => $value, "path" => substr($Params[0], $Params[1]) . $value, "typep" => "0", "authorization" => "0"];
            } else {
                if ($value != "." && $value != "..") {
                    $res = $this->GetPagesList_Files_Recursion([$Params[0] . $value . "/", $Params[1]], $res);
                }
            }
        }
        return $res;
    }

    /* Основная функция для вызова функций класса
    На Входе
      $param - массив
      [0] - имя функции или идентификатор процесса
      [1] - параметры для принимающей функции
    На выходе
      Результат работы функции либо
      $result["result"] = false;                  - ошибка вызова функции
      $result["error"]  = "Нет такой обработки";  - описание ошибки
    */
    public function Init($param, $Params = null)
    {

        if (!is_null($Params)) {
            $this->Params = $Params;
        }

        if (method_exists($this, $param[0])) {
            $method = $param[0];
            $result = $this->$method($param);
        } else {
            $result = [];
            $result["result"] = false;
            $result["error"] = "Нет такой обработки";
            $result['data'] = "";

            $filename = implode("/", $param);
            $result = $this->page($filename, $Params);

            if (isset($result['data'])) {
                $result = $result['data'];
            } else {
                $result = ["result" => false];
            }
        }

        return $result;
    }

    public function Write($param, $Params = null)
    {
        $content = $this->Init($param, $Params);
        echo $content;
    }

    /* Функция которая возвращает страницу сайта
      На входе
        $namepage - имя страницы
      На выходе
        $result["result"] = true / false  (есть / нет страницы)
        $result["data"] = "текст html страницы";
    */
    private function page($innamepage, $Params)
    {
        //var_dump($innamepage);
        $result = [];
        $namepage = $innamepage;
        if (trim($namepage) == "") {
            $namepage = "index";
        }

        $fullnamepage = $namepage;


        $indexdot = strrpos($namepage, ".");
        if ($indexdot === false) {

            $pageimp = [];
            $pageex = explode("/", $namepage);
            foreach ($pageex as $page_path) {
                $pageimp[] = $page_path;
                $newpage = implode("/", $pageimp);
                $existlocal = file_exists($this->Patch . "pages/$newpage.php");
                if ($existlocal) {
                    $namepage = $newpage;
                    break;
                }
            }

            $pagefiz = file_exists($this->Patch . "pages/$namepage.php");
            $fullnamepage = $namepage . ".php";
        } else {
            $param = explode(".", $namepage);
            if ($param[count($param) - 1] == "css") {
                header('Content-Type: text/css; charset=utf-8');
            } elseif ($param[count($param) - 1] == "svg") {
                header('Content-Type: image/svg+xml');
            } elseif ($param[count($param) - 1] == "jpg") {
                header('Content-Type: image/jpeg');
            } elseif ($param[count($param) - 1] == "png") {
                header('Content-Type: image/png');
            } elseif ($param[count($param) - 1] == "js") {
                header('Content-Type: application/javascript');
            }
            $pagefiz = file_exists($this->Patch . "pages/$namepage");
        }

        $page = $this->get("pages", "*", ["path" => $fullnamepage]);

        if (!isset($page["authorization"]) && (!$pagefiz)) {
            //Нет такой страницы
            $errorpage = $this->get("errorpages", "*", ["name" => "404"]);
            if (isset($errorpage["idpage"])) {
                // откроем страницу с ошибкой
                $page = $this->get("pages", "*", ["id" => "idpage"]);
                $result = $this->getpage($page["path"], $Params);
            } else {
                // откроем страницу index
                $result = $this->getpage("index", $Params);
            }
        } elseif ($page["authorization"] == 1) {
            //Страница с авторизацией
            if ($this->Auth->userauth()) {
                //Стриница с авторизацией и пользователь авторизован
                $result = $this->getpage($page["path"], $Params);
            } else {
                $errorpage = $this->get("errorpages", "*", ["name" => "403"]);
                if (isset($errorpage["idpage"])) {
                    // откроем страницу с ошибкой
                    $page = $this->get("pages", "*", ["id" => "idpage"]);
                    $result = $this->getpage($page["path"], $Params);
                } else {
                    // откроем страницу index
                    $result = $this->getpage("index", $Params);
                }
            }
        } else {
            //Обычная страница
            $result = $this->getpage($fullnamepage, $Params);
        }
        return $result;
    }

    private function createtmppage($innamepage)
    {
        $expage = explode("/", $innamepage);
        $namepage = $expage[count($expage) - 1];

        $filename = false;

        $tmpdir = $_SERVER["DOCUMENT_ROOT"] . "/tmp";
        $pagestmp = "pagestmp";
        $datedir = date("Ymd");
        $phpsession = session_id();

        $filedir = $tmpdir . "/" . $pagestmp . "/" . $datedir . "/" . $phpsession;
        $filename = $filedir . "/" . $namepage;


        if (!file_exists($tmpdir)) {
            mkdir($tmpdir);
        }

        if (!file_exists($tmpdir . "/" . $pagestmp)) {
            mkdir($tmpdir . "/" . $pagestmp);
        }

        if (!file_exists($tmpdir . "/" . $pagestmp . "/" . $datedir)) {
            mkdir($tmpdir . "/" . $pagestmp . "/" . $datedir);
        }

        if (!file_exists($filedir)) {
            mkdir($filedir);
        }

        return $filename;
    }

    /* Функция возвращает текст страницы в html
    На входе
      $namepage - имя страницы
    На выходе
      $result["result"] = true/false - сформировалась/несформировалась страница
      $result["data"] = "<html>Текст страницы</html>"
      $result["type"] = "page"
    */

    public function getpath($line)
    {
        $result = [];

        preg_match_all("/(^.+?)\[/", $line, $out, PREG_PATTERN_ORDER);
        $key = $out[1][0];
        if (count($out[1]) > 0) {
            $result[] = $key;
            $indexopen = mb_strpos($line, "[");
            $indexclose = mb_strlen(mb_strrchr($line, "]", true)) - $indexopen - 1; //strrchr  - 1
            $innerline = mb_substr($line, $indexopen + 1, $indexclose);

            if (mb_strpos($innerline, "[") > 0) {
                //Есть еще вложения
                $arrayPath = $this->getpath($innerline);
                foreach ($arrayPath as $value) $result[] = $value;
            } else {
                $result[] = $innerline;
            }
        } else {
            $result[] = $line;
        }

        return $result;
    }


    private function removeBomUtf8($s)
    {
        if (substr($s, 0, 3) == chr(hexdec('EF')) . chr(hexdec('BB')) . chr(hexdec('BF'))) {
            return substr($s, 3);
        } else {
            return $s;
        }
    }


    // {{\s?foreach\s?\(([a-zA-Z,\d,.]+)\s+as\s+([a-zA-Z,\d]+)\s?\)}}  - foreach
    // {{\s?endforeach\s?}}
    // {{\s?if\s?\(([a-zA-Z,\d,.]+)\s([!,=,>,<]+)\s([\w,",']+)\)}} - if
    // {{\s?endif\s?}}
    // {{\s?([$]{1}\s?[a-zA-Z,\d,.]+)\s?([(]{1}[a-zA-Z,\d,.]+[)]{1})\s?}}
    private function getparam($data)
    {
        $result = "";
        $dataarray = explode(".", $data);
        if (is_numeric($data)) {
            $result = $data;
        } elseif ($data == "null") {
            $result = "null";
        } elseif ($data == "true") {
            $result = "true";
        } elseif ($data == "false") {
            $result = "false";
        } elseif (is_int(substr($data, 1))) {
            //Ошибка
        } elseif ((substr($data, 0, 1) == "\"") || (substr($data, 0, 1) == "\'")) {
            //Ошибка
            $result = $data;
        } elseif (count($dataarray) > 1) {
            $arrayparam = $dataarray[0];
            unset($dataarray[0]);
            $pp = "'" . implode("' , '", $dataarray) . "'";
            $result = '$this->DTV($' . $arrayparam . ', [' . $pp . '])';
        } elseif (count($dataarray) == 1) {
            $result = '$' . $dataarray[0];
        }

        return $result;
    }

    private function getpagewithforandif($content)
    {
        $comment = '{{\*}}(.*)?{{\*}}';
        $snipplet = '{{\s?[$]{1}\s?([a-zA-Z,\d,.]+)\s?[(]{1}([a-zA-Z,\d,\s,.]+|)[)]{1}\s?}}';

        $foreach = '{{\s?foreach\s?\(([a-zA-Z,\d,.]+)\s+as\s+([a-zA-Z,\d]+)\s?\)}}';
        $endforeach = '{{\s?endforeach\s?}}';
        $if = '{{\s?if\s?\(([a-zA-Z,\d,.]+)\s?([!,=,>,<]+)\s?([a-zA-Z,\d,\w,.,",\']+)\s?\)\s?}}';
        $elseif = '{{\s?elseif\s?}}';
        $endif = '{{\s?endif\s?}}';
        $param = '{{\s?([a-zA-Z,.,_]+)\s?}}';


        $comments = [];
        preg_match_all("/" . $comment . "/", $content, $out, PREG_SET_ORDER);
        foreach ($out as $match) {
            $comments[] = $match[1];
            $temp_line = '/*{{'.count($comments).'}}*/';
            $content = str_replace($match[0], $temp_line, $content);
        }

        preg_match_all("/" . $foreach . "/", $content, $out, PREG_SET_ORDER);
        foreach ($out as $match) {
            $foreach_php = '<? foreach ({0} as {1}) { $Params["{2}"] = {1}; ?>';

            $match1 = $this->getparam($match[1]);
            $foreach_php = str_replace("{0}", $match1, $foreach_php);
            $match2 = $this->getparam($match[2]);
            $foreach_php = str_replace("{1}", $match2, $foreach_php);

            $foreach_php = str_replace("{2}", $match[2], $foreach_php);

            $content = str_replace($match[0], $foreach_php, $content);
        }

        preg_match_all("/" . $endforeach . "/", $content, $out, PREG_SET_ORDER);
        foreach ($out as $match) {
            $foreach_php = '<? }; ?>';
            $content = str_replace($match[0], $foreach_php, $content);
        }

        preg_match_all("/" . $if . "/", $content, $out, PREG_SET_ORDER);
        foreach ($out as $match) {
            $if_php = '<? if ({0} {1} {2}) { ?>';

            $match1 = $this->getparam($match[1]);
            $if_php = str_replace("{0}", $match1, $if_php);

            $if_php = str_replace("{1}", $match[2], $if_php);

            $match3 = $this->getparam($match[3]);
            $if_php = str_replace("{2}", $match3, $if_php);

            $content = str_replace($match[0], $if_php, $content);
        }

        preg_match_all("/" . $elseif . "/", $content, $out, PREG_SET_ORDER);
        foreach ($out as $match) {
            $elseif_php = '<?php } else { ?>';
            $content = str_replace($match[0], $elseif_php, $content);
        }

        preg_match_all("/" . $endif . "/", $content, $out, PREG_SET_ORDER);
        foreach ($out as $match) {
            $endif_php = '<?php }; ?>';
            $content = str_replace($match[0], $endif_php, $content);
        }

        preg_match_all("/" . $param . "/", $content, $out, PREG_SET_ORDER);
        foreach ($out as $match) {
            $param_php = '<?php echo ' . $this->getparam($match[1]) . '; ?>';
            $content = str_replace($match[0], $param_php, $content);
        }

        preg_match_all("/" . $snipplet . "/", $content, $out, PREG_SET_ORDER);
        foreach ($out as $match) {
            $param_php = '<?php ';
            $param_php .= '$snippletdata = [{1}];';
            $param_php .= '$this->Write([{0}], $snippletdata);';
            $param_php .= '?>';

            $path = explode(".", $match[1]);
            $newpath = [];
            foreach ($path as $pin) {
                $newpath[] = '"' . $pin . '"';
            }
            $path = implode(",", $newpath);

            $snippletparams = "";
            if (trim($match[2]) != "") {
                $snippletparams = explode(",", $match[2]);
                $newparams = [];
                foreach ($snippletparams as $pin) {
                    $pin = trim($pin);
                    $newparams[] = '$' . $pin;
                }
                $snippletparams = implode(",", $newparams);
            }


            $param_php = str_replace("{1}", $snippletparams, $param_php);
            $param_php = str_replace("{0}", $path, $param_php);

            $content = str_replace($match[0], $param_php, $content);
        }

        foreach ($comments as $key => $commentline) {
            $temp_line = '/*{{'.($key+1).'}}*/';
            $content = str_replace($temp_line, $commentline, $content);
        }



        return $content;
    }

    private function createpage($filetmppage, $filecontent, $Params, $inner)
    {

        $ext = explode(".", $filetmppage);
        $ext = $ext[count($ext) - 1];

        if (in_array($ext, $this->listscript)) {
            $filecontent = $this->getpagewithforandif($filecontent);

//            preg_match_all("/{{(.+?)}}/", $filecontent, $out, PREG_PATTERN_ORDER);
//
//            $keys = array_flip($out[1]);
//            $pageparams = array_keys($keys);
//            //print_r($pageparams);
//            foreach ($pageparams as $key) {
//                $path = $this->getpath($key);
//                $value = $this->DTV($Params, $path, NULL);
//
//                if (!is_null($value)) {
//                    $filecontent = str_replace("{{" . $key . "}}", $value, $filecontent);
//                } elseif ($inner) {
//                    $res = $this->getpage($key, $Params, false);
//                    $filecontent = str_replace("{{" . $key . "}}", $res["data"], $filecontent);
//                }
//            }
        }

        //print_r($filecontent);

        file_put_contents($filetmppage, $filecontent);


        if (in_array($ext, $this->listscript)) {
            ob_start();
            include($filetmppage);
            $content = ob_get_contents();
            ob_end_clean();
        } else {
            //header("content-length: ".filesize($filetmppage));
            $content = $filecontent;
        }

        unlink($filetmppage);

        $result["result"] = true;
        $result["data"] = $content;
        $result["type"] = "page";

        return $result;
    }

    /**
     * @param $namepage
     * @param bool $inner
     * @return array
     */
    private function getpage($namepage, $Params, $inner = true)
    {
        /*
        Если запись в таблице не присутствует, то ищем файл в папке по умолчанию "/pages/$namepage.php"
        Если запись есть и в поле "typep" таблицы указанно 0, то читаем путь до страницы из поля "path"
        Если запись есть и в поле "typep" таблицы указанно 1, то читаем текст страницы из поля "text"
        */
        $result = [];

        //Надо доработать
        if (file_exists($this->Patch . "pages/" . $namepage)) {
            $filetmppage = $this->createtmppage($namepage);
            $filecontent = file_get_contents($this->Patch . "pages/" . $namepage);

            if ($filetmppage) {
                $result = $this->createpage($filetmppage, $filecontent, $Params, $inner);
            }

        } else {
            $page = $this->get("pages", "*", ["path" => $namepage]);
            if ($page !== false) {
                $filetmppage = $this->createtmppage($namepage);
                $filecontent = $page["text"];

                if ($filetmppage) {
                    $result = $this->createpage($filetmppage, $filecontent, $Params, $inner);
                }
            }
        }

        return $result;
    }

    private function getfile($namepage)
    {
        /*
        Если запись в таблице не присутствует, то ищем файл в папке по умолчанию "/pages/$namepage.php"
        Если запись есть и в поле "typep" таблицы указанно 0, то читаем путь до страницы из поля "path"
        Если запись есть и в поле "typep" таблицы указанно 1, то читаем текст страницы из поля "text"
        */
        $result = [];

        $content = file_get_contents($namepage);

        $result["result"] = true;
        $result["data"] = $content;
        $result["type"] = "page";

        return $result;
    }

}
