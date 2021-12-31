<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 27.01.2018
 * Time: 1:31
 */

class ex_component extends ex_class
{

    private $metod;
    private $connectionInfo;
    private $convert;

    public $cloud;
    public $debugclass;
    public $classname;
    public $client;

    public function CreateDB() {
        /* Описание таблиц для работы с пользователями*/
        $info["transactionlog"] = array(
            "id"        => array('type' => 'int(15)',       'null' => 'NOT NULL', 'inc' => true),
            "agent"     => array('type' => 'varchar(150)',  'null' => 'NOT NULL'),
            "gdstype"   => array('type' => 'varchar(150)',  'null' => 'NOT NULL'),
            "gdsid"     => array('type' => 'varchar(150)',  'null' => 'NOT NULL'),
            "date"      => array('type' => 'datetime',      'null' => 'NOT NULL')
        );
        $this->create($this->connectionInfo['database_type'], $info);

        $res = $this->error();
        if ((int)$res[0] == 0) {
            $result = ["result" => true];
        } else {
            $result = ["result" => false, "message" => $res];
        }

        return $result;
    }

    public function settransaction($idtransaction)
    {
        if (!$this->has("transactionlog", ["AND" => ["agent" => $this->client, "gdstype" => $this->classname, "gdsid" => $idtransaction]])) {
            $this->insert("transactionlog", [
                "agent" => $this->client,
                "gdstype" => $this->classname,
                "gdsid" => $idtransaction,
                "date" => date("Y-m-d H:i:s", time())
            ]);
        }
    }

    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        $this->debugclass = $debug;
        if ($connectionInfo == null) {
            $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем
        } else {
            $this->connectionInfo = $connectionInfo; //Прочитаем
        }
        parent::__construct($connectionInfo, $debug);
        $this->metod = $metod;
        $this->classname = strtolower(get_class($this));

        $this->convert = new Conversion("INNER");
        $this->cloud = new Cloud($metod);

        $this->client = $this->agent;
        $this->cloud->SetDirname("cloudfiles");

    }

    public function SetClient($client) {
        $this->client = $client;
    }
    public function SetDirnameCloud ($dirname) {
        $this->cloud->SetDirname($dirname);
    }

    public function Init($param)
    {
        $result = [];
        $this->queue($this->metod, $param);

        if ($param[count($param)-1] == "debug") {
            $this->debugclass = true;
            unset($param[count($param)-1]);
        }

        if (method_exists($this, $param[0])) {
            $method = $param[0];
            $result = $this->$method($param);
        };

        if (($this->metod == "POST") && (isset($param[0]))) {
            if (($param[0] == "anysave") || ($param[0] == "uploadfile")) {
                $result = $this->savetiket($param);
            } elseif ($param[0] == "save") {
                $result = $this->savefile($param);
            };
        }

        return $result;
    }

    public function savetiket($params, $name = 0)
    {
        $dirname = get_class($this);
        $clien = $this->client;
        $dates = date('Ymd');
        if ($name == 0) {
            $fname = $_SERVER["REQUEST_TIME_FLOAT"].".post";
        } else {
            usleep(250);
            $tt = (string)microtime(true);
            $fname = $tt.".post";
        }
        $this->cloud->phpinput = $this->phpinput;
        $this->convert->phpinput = $this->phpinput;

        $this->cloud->upload(["upload", $clien, $dirname, $dates, $fname]);
        $this->convert->addtolist($dirname, $fname);

        return ["result" => true, "dirname" => $dirname, "filename" => $fname];
    }

    public function savefile($params) {

        $dirname = get_class($this);
        $clien = $this->client;
        $dates = date('Ymd');
        $fname = $params[count($params)-1];

        $this->cloud->phpinput = $this->phpinput;
        $this->convert->phpinput = $this->phpinput;

        $md5 = md5($this->phpinput);

        $this->cloud->upload(["upload", $clien, $dirname, $dates, $fname]);
        $this->convert->addtolist($dirname, $fname, $md5);

        return ["result" => true];
    }

    public function getfile($filename) {

        $text = $this->cloud->vfile_get_contents($filename);
        return $text;
    }

    public function get_empty_v3() {

        $v3 = [
            "author" => "",
            "organization" => "",
            "manager" => "",
            "ownerservice" => "",
            "attachedto" => "",
            "nomenclature" => "",
            "orderfromcart" => "",
            "price" => 0,
            "VATrate" => -1,
            "amountVAT" => 0,
            "amount" => 0,
            "supplier" => "",
            "contractsupplier" => "",
            "creationdate" => "",
            "fullnameservice" => "",
            "partner" => "",
            "contractor" => "",
            "contract" => "",
            "pricecustomer" => 0,
            "VATratecustomer" => -1,
            "amountVATcustomer" => 0,
            "amountclient" => 0,
            "seconded" => "",
            "date" => "",
            "markdel" => "",
            "conducted" => "",
            "countclient" => 1
        ];

        $v3info = [
            "Synh" => "",

            "Project" => "",
            "Depart" => "",
            "Arrival" => "",
            "DepartureCode" => "",
            "ArrivalCode" => "",
            "SegmentFlight" => "",
            "NameFees" => "",
            "NonReturnable" => "",
            "Carrier" => "",
            "LineFeature" => "",
            "Segments" => "",
            "TariffAmount" => "",
            "Fees" => "",
            "TypeOfTicket" => "",
            "TypeOfVisa" => "",
            "Longitude" => "",
            "HotelCategory" => "",
            "HotelName" => "",
            "NumberTypeName" => "",
            "ReservationNumber" => "",
            "Night" => "",
            "LateCheckout" => "",
            "EarlyCheckin" => "",
            "TypeOfFood" => "",
            "Latitude" => "",
            "RoomEMD" => "",
            "TrainNumber" => "",
            "WithFood" => "",
            "TypeTrainTicket" => "",
            "TicketSales" => "",
            "RouteShortened" => "",
            "CityDeparture" => "",
            "CityArrival" => "",
            "ServiceStartDate" => "",
            "ServiceEndDate" => "",
            "Route" => "",
            "LineType" => "",
            "MD5SourceFile" => "",
            "PlaceDeparture" => "",
            "PlaceArrival" => "",
            "TicketNumber" => "",
            "Payer" => "",
            "Supplier" => "",
            "AmountExcludingVAT" => 0,
            "VATAmount10" => 0,
            "VATAmount18" => 0,
            "AmountWithVAT10" => 0,
            "AmountWithVAT18" => 0,
            "AmountServices" => 0,
            "AmountOfPenalty" => 0,
            "VendorFeeAmount" => 0,
            "CustomerPaymentType" => "",
            "SupplierPaymentType" => "",
            "ApplicationService" => "",

            "BC" => "",
            "CodeShape" => "",
            "FareBases" => "",
            "Siov" => false,
            "Baggage" => "",
            "TravelTime" => 0,
            "Smoking" => false,
            "Food" => "",
            "Seat" => "",
            "Status" => "",
            "TerminalDepartures" => "",
            "TerminalArrivals" => "",
            "Nevalidation" => false,
            "Compelled" => false,
            "CustomersFault" => false,
            "NumberBCO" => "",
            "ThirdPartyCashier" => false,
            "TicketClass" => "",

            "AddressDeparture" => "",
            "AddressDestination" => "",
            "CarClass" => "",

            "LatitudeDeparture" => "",
            "LongitudeDeparture" => "",
            "Place" => "",
            "Wagon" => ""
        ];

        $v3 = array_merge($v3, $v3info);

        return $v3;
    }

}
