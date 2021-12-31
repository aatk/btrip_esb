<?php
/*
 * ver: 2.0.1
 *
 * */
class ex_class extends db_connect
{
    public $GET = null;
    public $FILES = null;
    public $POST = null;
    public $REQUEST = null;
    public $SERVER = null;
    public $URI = null;
    public $phpinput = null;
    public $agent;
    public $gid;

    public $debugclass;

    private $cache;
    private $curlheader;

    public function SetGlobalID($gid)
    {
        $this->gid = $gid;
    }

    public function trueexit($status = 0)
    {
        unset($_SESSION["db_connect"]);
        exit($status);
    }


    public function __construct($connectionInfo = null, $debug = false)
    {
        $this->SetGlobalID($_SESSION["auth"]["globalid"]);

        $this->debugclass = $debug;

        if ($connectionInfo == null) {
            $connectionInfo = $_SESSION["i4b"]["connectionInfo"];
        }

        parent::__construct($connectionInfo, $this->debugclass);

        $this->GET = $_GET;
        $this->POST = $_POST;
        $this->REQUEST = $_REQUEST;
        $this->SERVER = $_SERVER;
        $this->FILES = $_FILES;
        $this->URI = explode("/",$_SERVER["REQUEST_URI"]);
        $this->phpinput = file_get_contents("php://input");

        $this->agent = $_SESSION["i4b"]["agent"];

        /* Cache */
        $metod = $_SERVER["REQUEST_METHOD"];

        $params = [];
        $params["GET"] = $this->GET;
        $params["POST"] = $this->POST;
        $params["REQUEST"] = $this->REQUEST;
        $params["SERVER"] = $this->SERVER;
        $params["phpinput"] = $this->phpinput;
        $params["type"] = "run";
        $this->cache = new Cache($metod, $connectionInfo, $params);
    }

    public function WriteToSession($type, $data)
    {
        $QUERY_STRING = $_SERVER["REQUEST_URI"];
        $_SESSION["i4b"][$QUERY_STRING][$type] = $data;
        return true;
    }

    public function ReadFromSession($type)
    {
        $QUERY_STRING = $_SERVER["REQUEST_URI"];
        $data = $_SESSION["i4b"][$QUERY_STRING][$type];
        return $data;
    }

    public function baseurl() {
        $purl = parse_url($_SERVER["REQUEST_URI"]);
        if (!isset($purl["host"])) {
        $purl["host"] = $_SERVER["SERVER_NAME"];
        }
        if (!isset($purl["scheme"])) {
            if ($_SERVER["SERVER_PORT"] == 80) {
                $purl["scheme"] = "http";
            } else {
                $purl["scheme"] = "https";
            }
        }
        return $purl["scheme"]."://".$purl["host"];
    }

    public function DTV($jsonarray, $inattr, $defresult = "", $dateformatfrom = "", $dateformatto = "YmdHis") {

        $finds = [];
        if (is_array($inattr)) {
            $finds = $inattr;
        } else {
            $finds[] = $inattr;
        }

        $result = "";
        $rr = $jsonarray;
        foreach ($finds as $value) {
            if (isset($rr[$value])) {
                $rr = $rr[$value];
                if (!is_array($rr)) {
                    $result = $rr;
                } elseif (is_array($rr) && (count($rr)==0)) {
                    $result = "";
                } else {
                    $result = $rr;
                }
            } else {
                $result = "";
                break;
            }
        }

        if ($result == "") {
            $result = $defresult;
        }

        if ($dateformatfrom != "") {
            $dateDepart = DateTime::createFromFormat($dateformatfrom, $result);
            if ($dateDepart !== false) {
                $result = $dateDepart->format($dateformatto);
            } else {
                $result = "";
            }
        }

        return $result;
    }

    public function toint($param)
    {
        $result = 0;
        if (preg_match_all("([\d]+.[\d]+)", $param, $out)) {
            $result = $out[0][0];
        }
        return (float)$result;
    }

    public function normalizejson($injson)
    {

        $result = "";

        if (!is_array($injson)) {
            $result = $injson;

        } elseif (is_array($injson) && (count($injson) == 0)) {
            $result = "";

        } elseif (is_array($injson)) {
            if (is_string($result)) $result = [];
            foreach ($injson as $key => $value) {
                $result[$key] = $this->normalizejson($value);
            }
        }

        return $result;
    }

    private function json_prepare_xml($domNode)
    {
        foreach ($domNode->childNodes as $node) {
            if ($node->hasChildNodes()) {
                json_prepare_xml($node);
            } else {
                if ($domNode->hasAttributes() && strlen($domNode->nodeValue)) {
                    $domNode->setAttribute("nodeValue", $node->textContent);
                    $node->nodeValue = "";
                }
            }
        }
    }

    public function xml2json($xmlnode)
    {

        $sxml = simplexml_load_string($xmlnode);//$dom->saveXML() );
        $json = json_decode(json_encode($sxml), true);
        $json = $this->normalizejson($json);

        return $json;
    }

    private function header_callback($ch, $header_line)
    {
        $this->curlheader .= $header_line;
        return strlen($header_line);
    }

    public function getgetarray($data)
    {
        $encoded = "";
        foreach($data as $name => $value) {
            $encoded .= rawurlencode($name).'='.rawurlencode($value).'&';
        }
        $encoded = substr($encoded, 0, strlen($encoded)-1);

        return $encoded;
    }

    public function http_c_post($url, $data, $exparam = [])
    {
        $out = false;
        $this->curlheader = "";

        $encoded = "";
        if (is_array($data)) {
            foreach($data as $name => $value) {
                $encoded .= rawurlencode($name).'='.rawurlencode($value).'&';
            }
            $encoded = substr($encoded, 0, strlen($encoded)-1);
        } else {
            $encoded .= $data;
        }

        if( $ch = curl_init() ) {

            $cert = 0;

            curl_setopt($ch, CURLOPT_URL, $url);
            if (isset($exparam["basicauth"])) {
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, $exparam["basicauth"]["username"].":".$exparam["basicauth"]["password"]);
            }


            curl_setopt($ch, CURLOPT_HEADER, 0);


            if ((isset($exparam["get"])) || (isset($exparam["GET"]))) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                curl_setopt($ch, CURLOPT_POST, 0);
            } elseif (isset($exparam["PUT"])) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POST, 0);
            } elseif (isset($exparam["DELETE"])) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POST, 0);
            } else {
                curl_setopt($ch, CURLOPT_POST, 1);
            }

            if (isset($exparam["gzip"])) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept-Encoding: gzip,deflate']);
            }

            if (isset($exparam["headers"])) {
                $CURLOPT_HTTPHEADER = [];
                foreach ($exparam["headers"] as $key => $val) {
                    $CURLOPT_HTTPHEADER[] = $key.": ".$val;
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'header_callback'));

            if ($cert) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); //for solving certificate issue
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
            } else {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //for solving certificate issue
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS,  $encoded);

            $out = curl_exec($ch);
            curl_close($ch);

            $out = ['content'=>$out, 'headers'=>$this->curlheader];
        }

        return $out;
    }

    public function GetCache() {
        return $this->cache;
    }

    public function jsDate($datestr) {
        $format = 'Y-m-d H:i:s';
        $mdate = date_parse_from_format($format, $datestr);
        $jsDate = $mdate['year'] . "," . ($mdate['month'] - 1) . "," . $mdate['day'] . "," . $mdate['hour'] . "," . $mdate['minute'] . "," . $mdate['second'];
        return $jsDate;
    }

    public function mastdie($code, $error = "") {
        $this->echo_log("ОШИБКА: ".$error);
        throw new MyException($code.":".$error);
    }

    public function echo_log($e) { //, $suffix = ""
        ob_start();
        if ((is_array($e)) || (is_object($e))) {
            print_r($e);
        } else {
            echo $e."\r\n";
        }
        $String = ob_get_contents();
        ob_end_clean();

        $dir = $_SERVER["DOCUMENT_ROOT"];
        if (!file_exists($dir."/log")) {
            mkdir($dir."/log");
        }
        $dates = date("Ymd"); //'.$suffix.'
        file_put_contents($dir.'/log/dumplog_'.$dates.'.txt', $String, FILE_APPEND);
    }

    public function getlogfile($dates) {
        $dir = $_SERVER["DOCUMENT_ROOT"];
        $res = file_get_contents($dir.'/log/dumplog_'.$dates.'.txt');
        return $res;
    }

    public function pcgbasename($param, $suffix=null) {
        if ( $suffix ) {
            $tmpstr = ltrim(substr($param, strrpos($param, DIRECTORY_SEPARATOR) ), DIRECTORY_SEPARATOR);
            if ( (strpos($param, $suffix)+strlen($suffix) )  ==  strlen($param) ) {
                return str_ireplace( $suffix, '', $tmpstr);
            } else {
                return ltrim(substr($param, strrpos($param, DIRECTORY_SEPARATOR) ), DIRECTORY_SEPARATOR);
            }
        } else {
            return ltrim(substr($param, strrpos($param, DIRECTORY_SEPARATOR) ), DIRECTORY_SEPARATOR);
        }
    }

    public function object2array($object) { return @json_decode(@json_encode($object),1); }

    public function queue($metod, $param) {

    }


    public function array_to_xml ($data, $xml_data = null) {
        if (!isset($xml_data)) $xml_data = new SimpleXMLElement('<?xml version="1.0"?><xml></xml>');

        foreach( $data as $key => $value ) {
            if( is_array($value) ) {
                if( !is_numeric($key) ) {
                    $subnode = $xml_data->addChild("item_".$key);
                    $this->array_to_xml($value, $subnode);
                } else {
                    $this->array_to_xml($value, $xml_data);
                }
            } else {
                if(is_numeric($key) ) {
                    $key = "items_".$key;
                }
                $xml_data->addChild("$key",htmlspecialchars("$value"));
            }
        }

        return $xml_data;
    }

    public function mb_ucwords($string)
    {
        $ars = explode(" ", $string);
        $newars = [];
        foreach ($ars as $word) {
            $newars[] = mb_strtoupper(mb_substr($word,0,1), "utf-8").mb_substr($word,1);
        }
        return implode(" ", $newars);
    }

}
