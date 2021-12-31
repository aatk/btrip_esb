<?php

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 29/03/2019
 * Time: 20:23
 */
class Corteoscb extends ex_classlite
{
    private $metod;
    private $classname;
    private $gds;
    private $connectionInfo;
    private $Auth;

    public function CreateDB()
    {
        $info["corteos_token"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "login" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "token" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "datecreate" => array('type' => 'datetime', 'null' => 'NOT NULL'),
            "datevalid" => array('type' => 'datetime', 'null' => 'NOT NULL')
        );

        $this->create($this->connectionInfo['database_type'], $info);
    }


    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        parent::__construct($connectionInfo, $debug);

        $this->metod = $metod;
        if (isset($this->POST["connector"])) {
            $this->gds = $this->POST;
        } else {
            $this->gds = $_SESSION["i4b"][mb_strtolower($this->classname)];
        }
        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем
        $this->debugclass = $debug;
        $this->Auth = new Auth();
    }

    private function GetToken()
    {
        $result = $this->get("corteos_token", ["login", "token", "datecreate"], ["datevalid[>]" => date("Y-m-d")]);

        if ($this->debugclass) {
            var_dump($result);
        }

        if ($result === false) {
            $result = $this->GetNewToken();
        } else {
            $result = ["result" => true, "content" => $result["token"]];
        }
        return $result;
    }

    private function SetToken($token, $datevalid)
    {
        $result = ["result" => false];
        $login = $this->gds["email"];
        $data = $this->get("corteos_token", ["id"], ["login" => $login]);

        $item = [
            "login" => $login,
            "token" => $token,
            "datecreate" => date("Y-m-d"),
            "datevalid" => $datevalid
        ];

        if ($data !== false) {
            $this->update("corteos_token", $item, ["id" => $data["id"]]);
            $result = ["result" => true, "type" => "update"];
        } else {
            $this->insert("corteos_token", $item);
            $result = ["result" => false, "type" => "insert"];
        }

        return $result;
    }

    private function GetNewToken()
    {

        $clientaddress = $this->gds["url"];
        $Email = $this->gds["email"];
        $Password = $this->gds["password"];
        $validityDate = date("Y-m-d", strtotime("+7 days"));

        $exparam["headers"] = [
            "Content-Type" => "text/xml"
        ];

        $url = 'https://' . $clientaddress . '/XmlGate/V3/Authorization.asmx';
        $xml = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cor="http://corteos.ru">
           <soapenv:Header/>
           <soapenv:Body>
              <cor:Login>
                 <cor:Email>' . $Email . '</cor:Email>
                 <cor:Password>' . $Password . '</cor:Password>
                 <cor:validityDate>' . $validityDate . '</cor:validityDate>
              </cor:Login>
           </soapenv:Body>
        </soapenv:Envelope>';

        $result = false;
        $content = "";
        $resultxml = $this->http_c_post($url, $xml, $exparam);

        if ((strpos($resultxml["headers"], "HTTP/1.1 200 OK") !== false) || (strpos($resultxml["headers"], "HTTP/2 200") !== false)) {

            if ($this->debugclass) {
                print_r($resultxml);
            }
            $result = true;

            $content = $resultxml["content"];

            $sxml = simplexml_load_string($content);
            $Body = $sxml->children('soap', true)->Body->children('http://corteos.ru');
            $jsonv = $this->object2array($Body);

            $TokenValue = $this->DTV($jsonv, ["LoginResponse", "LoginResult", "AuthToken", "TokenValue"]);
            $content = $TokenValue;

            $this->SetToken($content, $validityDate);
        } else {
            print_r($resultxml);
            $content = $resultxml["content"];
        }

        return ["result" => $result, "content" => $content];
    }

    private function getorder($token, $orderid)
    {

        $clientaddress = $this->gds["url"];
        $url = 'https://' . $clientaddress . '/XmlGate/V3/OrderManagement/OrdersAPI.asmx';

        $exparam["headers"] = [
            "Content-Type" => "text/xml"
        ];

        $xml = '
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cor="http://corteos.ru">
               <soapenv:Header>
                  <cor:AuthHeader>
                     <cor:Token>' . $token . '</cor:Token>
                  </cor:AuthHeader>
               </soapenv:Header>
               <soapenv:Body>
                  <cor:GetSimpleReserveById>
                     <cor:id_Reserve>' . $orderid . '</cor:id_Reserve>
                  </cor:GetSimpleReserveById>
               </soapenv:Body>
            </soapenv:Envelope>';

        $result = false;
        $content = "";
        $resultxml = $this->http_c_post($url, $xml, $exparam);


        if ((strpos($resultxml["headers"], "HTTP/1.1 200 OK") !== false) || (strpos($resultxml["headers"], "HTTP/2 200") !== false)) {
        //if (strpos($resultxml["headers"], "HTTP/1.1 200 OK") !== false) {
            $result = true;

            $content = $resultxml["content"];

            file_put_contents("tmp/corteos_" . $orderid . ".xml", $content);

            $sxml = simplexml_load_string($content);
            $Body = $sxml->children('soap', true)->Body->children('http://corteos.ru');
            $xmld = $Body->asXML();

            $Body = simplexml_load_string($xmld);
            $content = $this->object2array($Body);
        }

        return ["result" => $result, "content" => $content];


    }


    ////////////////////////////////////////////////////////////////////////////////////////////////
    //
    //                  ОБЩИЕ ФУНКЦИИ
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////
    private function getSupplier($supplier)
    {
        $result = ["INN" => "", "KPP" => "", "Name" => ""];
        if ($supplier == "3") {
            $result = ["INN" => "9909118687", "KPP" => "774751001", "Name" => "БСП"];
        } elseif ($supplier == "2") {
            $result = ["INN" => "7714017443", "KPP" => "771001001", "Name" => "ТКП"];
        } elseif ($supplier == "17") {
            $result = ["INN" => "7714352628", "KPP" => "773001001", "Name" => "МойАгент"];

        } elseif ($supplier == "10" . $supplier) {
            $result = ["INN" => "7708510731", "KPP" => "770401001", "Name" => "УФС"];

        } elseif ($supplier == "h46") {
            $result = ["INN" => "7703403951", "KPP" => "770301001", "Name" => "Островок"];
        } elseif ($supplier == "-1") {
            $result = ["INN" => "7730633954", "KPP" => "773001001", "Name" => "Свой Тур и Трэвел"];
        } elseif ($supplier == "h3") {
            $result = ["INN" => "5024053441", "KPP" => "502401001", "Name" => "Академсервис"];
        } elseif ($supplier == "h35") {
            $result = ["INN" => "7709878038", "KPP" => "770901001", "Name" => "Хотелбук"];
        } elseif ($supplier == "h72") {
            $result = ["INN" => "6673216075", "KPP" => "667301001", "Name" => "Броневик"];
        } elseif ($supplier == "h33") {
            $result = ["INN" => "7730557534", "KPP" => "773001001", "Name" => "Эй энд Эй"];

        } elseif ($supplier == "-1") {
            $result = ["INN" => "5407479940", "KPP" => "540701001", "Name" => "iway"];
        }

        return $result;
    }


    private function getFiles($CBR)
    {
        $Files = [];
        $StableFiles = $StableFile = $this->DTV($CBR, ["Files", "StableFile"]);
        if (isset($StableFile["id_File"])) {
            $StableFiles = [];
            $StableFiles[] = $StableFile;
        }

        foreach ($StableFiles as $StableFile) {
            $File = [];
            $File["Name"] = $StableFile["Caption"];
            $File["Url"] = $StableFile["Url"];
            $File["SearchID"] = $StableFile["TicketNumber"];
            $Files[$StableFile["TicketNumber"]][] = $File;
        }

        return $Files;
    }

    private function getCodes($CBR)
    {
        $Codes = [];
        $StableCodes = $StableCode = $this->DTV($CBR, ["Codes", "StableCode"]);
        if (isset($StableCode["id_MetaCode"])) {
            $StableCodes = [];
            $StableCodes[] = $StableCode;
        }

        foreach ($StableCodes as $StableCode) {
            $Code = [];
            $Code["Name"] = $StableCode["DictionaryName"];
            $Code["Value"] = $StableCode["CodeName"];
            $Codes[] = $Code;
        }

        return $Codes;
    }

    private function getPassager($jsonv, &$Codes, $FindKey = 0)
    {
        $Secondeds = [];

        $passagers = $passager = $this->DTV($jsonv, ["Passengers", "StablePassenger"]);
        if (isset($passager["id_Passenger"])) {
            $passagers = [];
            $passagers[] = $passager;
        }

        foreach ($passagers as $passager) {

            $id_Passenger = $this->DTV($passager, ["id_Passenger"]);
            if (($FindKey == 0) || (($FindKey != 0) && ((int)$id_Passenger == (int)$FindKey))) {

                $Seconded["FirstName"] = $this->DTV($passager, ["FirstName"]);
                $Seconded["LastName"] = $this->DTV($passager, ["LastName"]);
                $Seconded["SurName"] = $this->DTV($passager, ["SurName"]);
                $Seconded["FirstNameLatin"] = $this->DTV($passager, ["FirstNameLatin"]);
                $Seconded["LastNameLatin"] = $this->DTV($passager, ["LastNameLatin"]);
                $Seconded["SurNameLatin"] = "";//$this->DTV($passager, ["SurNameLatin"]);

                $Seconded["DocumentNumber"] = $this->DTV($passager, ["DocumentNumber"]);
                $Seconded["DocType"] = $this->DTV($passager, ["DocumentType"]);

                $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
                $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);

                $Secondeds[] = $Seconded;

                $passagerCodes = $this->getCodes($passager);
                $Codes = array_merge($passagerCodes, $Codes);
            }
        }

        return $Secondeds;
    }


    ////////////////////////////////////////////////////////////////////////////////////////////////
    //
    //                  AVIA
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////

    private function getTikets($Tickets)
    {
        $service = [];

        $Ticket = $Tickets;
        if (isset($Tickets["@attributes"])) {
            $Tickets = [];
            $Tickets[] = $Ticket;
        }
        foreach ($Tickets as $ticket) {
            $MaskKey = $this->DTV($ticket, ["@attributes", "MaskKey"]);
            $TicketNumber = $OrignalTicketNumber = $this->DTV($ticket, ["@attributes", "TicketNumber"]);
            if (substr($TicketNumber, 3, 1) != "-") {
                $TicketNumber = substr($TicketNumber, 0, 3) . "-" . substr($TicketNumber, 3);
            }
            $PassengerKey = $this->DTV($ticket, ["@attributes", "PassengerKey"]);

            $service[$MaskKey] = [
                "OrignalTicketNumber" => $OrignalTicketNumber,
                "TicketNumber" => $TicketNumber,
                "PassengerKey" => $PassengerKey,
            ];
        }

        return $service;
    }

    private function getMasks($Masks)
    {
        $service = [];

        $Mask = $Masks;
        if (isset($Masks["@attributes"])) {
            $Masks = [];
            $Masks[] = $Mask;
        }
        foreach ($Masks as $Mask) {
            $MaskKey = $this->DTV($Mask, ["@attributes", "Key"]);

            $MyMask = [];
            $MyMask = $this->DTV($Mask, ["@attributes"]);
            $MyMask["VATRemark"] = $this->DTV($Mask, ["VATRemark"]);

            $MyMask["BaseFare"] = $this->DTV($Mask, ["BaseFare", "@attributes", "EquivValue"]);
            $MyMask["BaseFareCurrency"] = $this->DTV($Mask, ["BaseFare", "@attributes", "EquivCode"]);

            $MyMask["ServiceFee"] = $this->DTV($Mask, ["ServiceFee", "@attributes", "EquivValue"]);
            $MyMask["ServiceFeeCurrency"] = $this->DTV($Mask, ["ServiceFee", "@attributes", "EquivCode"]);

            $MyMask["HiddenFee"] = $this->DTV($Mask, ["HiddenFee", "@attributes", "EquivValue"]);
            $MyMask["HiddenFeeCurrency"] = $this->DTV($Mask, ["HiddenFee", "@attributes", "EquivCode"]);

            $MyMask["Commission"] = $this->DTV($Mask, ["Commission", "@attributes", "EquivValue"]);
            $MyMask["CommissionCurrency"] = $this->DTV($Mask, ["Commission", "@attributes", "EquivCode"]);

            $MyMask["Segments"] = [];
            $Legs = $Leg = $this->DTV($Mask, ["LegReferences", "Ref"]);
            if (isset($Legs["@attributes"])) {
                $Legs = [];
                $Legs[] = $Leg;
            }
            foreach ($Legs as $Leg) {
                $MyMask["Segments"][] = $this->DTV($Leg, ["@attributes", "Key"]);
            }
            //$MyMask["Legs"][] = $this->DTV($Mask, ["LegReferences", "Ref", "@attributes", "Key"]);
            $MyMask["Taxes"] = $this->DTV($Mask, ["Taxes"], []);

            $service[$MaskKey] = $MyMask;//$this->DTV($Mask, ["@attributes", "TicketNumber"]);
        }

        return $service;
    }

    private function getFlights($Flights)
    {
        $service = [];

        $Flight = $Flights;
        if (isset($Flights["@attributes"])) {
            $Flights = [];
            $Flights[] = $Flight;
        }
        foreach ($Flights as $Flight) {
            $FlightKey = $this->DTV($Flight, ["@attributes", "Key"]);
            $FlightInfo = $this->DTV($Flight, ["@attributes"]);

            $service[$FlightKey] = $FlightInfo;
        }

        return $service;
    }

    private function getLegs($Legs)
    {
        $service = [];

        $Leg = $Legs;
        if (isset($Legs["@attributes"])) {
            $Legs = [];
            $Legs[] = $Leg;
        }
        foreach ($Legs as $Leg) {
            $LegKey = $this->DTV($Leg, ["@attributes", "Key"]);

            $MyLeg = $this->DTV($Leg, ["@attributes"]);
            $MyLeg["gds"] = $this->DTV($Leg, ["MetaData", "gds"]);

            $Flights = $this->DTV($Leg, ["Flight"]);
            $FlightsInfo = $this->getFlights($Flights);
            $MyLeg["Flight"] = $FlightsInfo;

            $service[$LegKey] = $MyLeg;
        }

        return $service;
    }

    private function FligthToSegments(&$Segments, $LegsInfo)
    {
        //$Segments = [];
        $BlankSegment = $LegsInfo;
        unset($BlankSegment["Flight"]);

        foreach ($LegsInfo["Flight"] as $Flight) {
            //print_r($Flight);

            $Segment = array_merge($BlankSegment, $Flight);
            $Segments[] = $Segment;
        }

        //print_r($Segments);
    }

    private function AddSegmentC($MyTicket, $LegsInfo)
    {
        $NewSegments = [];
        $Segments = $MyTicket["Segments"];
        foreach ($Segments as $Segment) {
            if (isset($LegsInfo[$Segment])) {
                $this->FligthToSegments($NewSegments, $LegsInfo[$Segment]);

//                if ($this->debugclass) {
//                    print_r($LegsInfo[$Segment]);
//                }
            }
        }

        $MyTicket["Segments"] = $NewSegments;

        return $MyTicket;
    }

    private function getPassengers($Passengers)
    {
        $service = [];

        $Passenger = $Passengers;
        if (isset($Passengers["@attributes"])) {
            $Passengers = [];
            $Passengers[] = $Passenger;
        }
        foreach ($Passengers as $Passenger) {
            $Key = $this->DTV($Passenger, ["@attributes", "Key"]);
            $id_Passenger = $this->DTV($Passenger, ["@attributes", "id_Passenger"]);

            $service[$Key] = $id_Passenger;
        }

        return $service;
    }


    private function getBiletinfo(&$serviceblank, &$servises, $OrderSpecificData, $CBR)
    {

        $MyTickets = [];
        $MultiItinerary = $this->DTV($OrderSpecificData, ["BLMultiItinerary"]);


        $LegsInfo = [];
        //Itinerary - билет
        $Itinerarys = $Itinerary = $this->DTV($MultiItinerary, ["Itinerary"]);
        if (isset($Itinerary["@attributes"])) {
            $Itinerarys = [];
            $Itinerarys[] = $Itinerary;
        }
        foreach ($Itinerarys as $Itinerary) {
            $ValidatingCarrier = $this->DTV($Itinerary, ["@attributes", "ValidatingCarrier"]);
            $service["Carrier"] = $ValidatingCarrier;

            //Tickets
            $Tickets = $this->DTV($Itinerary, ["Tickets", "Ticket"]);
            $TicketsInfo = $this->getTikets($Tickets);

            //Masks
            $Masks = $this->DTV($Itinerary, ["Masks", "Mask"]);
            $MasksInfo = $this->getMasks($Masks);

            //Legs
            $Legs = $this->DTV($Itinerary, ["Legs", "Leg"]);
            $LegsInfo = $this->getLegs($Legs);

            //Passengers
            $Passengers = $this->DTV($Itinerary, ["Passengers", "Passenger"]);
            $PassengersInfo = $this->getPassengers($Passengers);

            $Codes = $this->getCodes($CBR);
            $Files = $this->getFiles($CBR);

            foreach ($TicketsInfo as $key => $TicketInfo) {

                //print_r($key);
                //print_r($MasksInfo[$key]);

                $MyTicket = [];
                if (isset($MasksInfo[$key])) {
                    $MyTicket = $MasksInfo[$key];
                    $MyTicket["TicketNumber"] = $TicketInfo["TicketNumber"];

                    //print_r($MyTicket["TicketNumber"]);
                    $PassengerKey = $TicketInfo["PassengerKey"];
                    $seconded = $this->getPassager($CBR, $Codes, $PassengersInfo[$PassengerKey]);
                    $MyTicket["seconded"] = $seconded;

                    $MyTicket["Codes"] = $Codes;
                }

                if (isset($Files[$TicketInfo["OrignalTicketNumber"]])) {
                    $MyTicket["Files"] = $Files[$TicketInfo["OrignalTicketNumber"]];
                }
                $MyTicket["ReservationNumber"] = $this->DTV($Itinerary, ["@attributes", "Locator"]);

                $MyTicket = $this->AddSegmentC($MyTicket, $LegsInfo);

                $MyTickets[] = $MyTicket;
            }

        }


        return $MyTickets;
    }

    private function CreateSegment($Segment, $Ticket)
    {
        //print_r($Segment);
        $service = $this->get_empty_v3();

        $service["nomenclature"] = "СегментАвиабилета";
        $service["supplier"] = $Ticket["supplier"];
        $service["Supplier"] = $Ticket["supplier"];

        $service["Carrier"] = $this->DTV($Segment, ["Airline"]);
        $service["CarrierContractor"] = $service["Carrier"];

        $DepartureCode = $this->DTV($Segment, ["Origin"]);
        $ArrivalCode = $this->DTV($Segment, ["Destination"]);

        $service["DepartureCode"] = $DepartureCode;
        $service["ArrivalCode"] = $ArrivalCode;
        $service["PlaceDeparture"] = $DepartureCode;
        $service["PlaceArrival"] = $ArrivalCode;

        $service["CityDeparture"] = $this->DTV($Segment, ["OriginCityName"]);
        $service["CityArrival"] = $this->DTV($Segment, ["DestinationCityName"]);

        $service["Depart"] = $this->DTV($Segment, ["DepartureDate"], "", "d.m.Y H:i:s");
        $service["Arrival"] = $this->DTV($Segment, ["ArrivalDate"], "", "d.m.Y H:i:s");


        $service["TerminalDepartures"] = $this->DTV($Segment, ["OriginTerminal"]);
        $service["TerminalArrivals"] = $this->DTV($Segment, ["DestinationTerminal"]);

        $service["ServiceStartDate"] = $service["Depart"];
        $service["ServiceEndDate"] = $service["Arrival"];

        $diff = strtotime($service["ServiceEndDate"]) - strtotime($service["ServiceStartDate"]);
        $service["TravelTime"] = round(abs($diff) / 60);

        $service["FareBases"] = $this->DTV($Segment, ["FareName"]);

        $service["Synh"] = md5(json_encode($service, JSON_UNESCAPED_UNICODE));
        $service["MD5SourceFile"] = $service["Synh"];

        $service["date"] = $service["Depart"];

        return $service;
    }

    private function BuildRoute(&$Route, $Whot)
    {
        $lastWhot = $Route[count($Route) - 1];
        if ($lastWhot != $Whot) {
            $Route[] = $Whot;
        }
    }

    private function CreateSegmentsService($serviceblank, &$servises, &$BiletsInfo)
    {

        $NewBiletsInfo = []; //Собираем здесь все переформированные билеты
        $NewSegments = []; //Собирем здесь все услуги с сегментами

        foreach ($BiletsInfo as $Bilet) {

            $Route = [];
            $RouteShortened = [];
            $segmentservice = []; //Заглушка чтоб не развалилось после выхода из цикла

            $NewSegmentsItem = []; //Собираем здесь все md5 сегментов
            $Segments = $Bilet["Segments"];
            foreach ($Segments as $Segment) {
                //Обрабатываем каждый сегмент

                $segmentservice = $this->CreateSegment($Segment, $Bilet);
                $id = $segmentservice["Synh"];
                if (!isset($NewSegments[$id])) {
                    $NewSegments[$id] = $segmentservice;
                }
                $NewSegmentsItem[] = $id;

                $Whot = $segmentservice["DepartureCode"];
                $this->BuildRoute($RouteShortened, $Whot);
                $Whot = $segmentservice["ArrivalCode"];
                $this->BuildRoute($RouteShortened, $Whot);

                $Whot = $segmentservice["CityDeparture"];
                $this->BuildRoute($Route, $Whot);
                $Whot = $segmentservice["CityArrival"];
                $this->BuildRoute($Route, $Whot);

                if ($serviceblank["Depart"] == "") {
                    $serviceblank["Depart"] = $segmentservice["Depart"];
                }
                if ($serviceblank["DepartureCode"] == "") {
                    $serviceblank["DepartureCode"] = $segmentservice["DepartureCode"];
                }

                if ($serviceblank["CityDeparture"] == "") {
                    $serviceblank["CityDeparture"] = $segmentservice["CityDeparture"];
                }
                if ($serviceblank["PlaceDeparture"] == "") {
                    $serviceblank["PlaceDeparture"] = $segmentservice["PlaceDeparture"];
                }
            }


            $ProviderFee = 0;
            $Taxes = $Tax = $this->DTV($Bilet, ["Taxes", "Tax"]);
            if (isset($Tax["@attributes"])) {
                $Taxes = [];
                $Taxes[] = $Tax;
            }

            foreach ($Taxes as $Tax) {
                $TaxCode = $this->DTV($Tax, ["@attributes", "TaxCode"]);
                if ($TaxCode == "DP") {
                    // Это сбор поставщика
                    $ProviderFee = $this->DTV($Tax, ["@attributes", "EquivValue"]);
                }
            }


            if ($serviceblank["Arrival"] == "") {
                $serviceblank["Arrival"] = $this->DTV($segmentservice, ["Arrival"]);
            }
            if ($serviceblank["ArrivalCode"] == "") {
                $serviceblank["ArrivalCode"] = $this->DTV($segmentservice, ["ArrivalCode"]);
            }
            if ($serviceblank["CityArrival"] == "") {
                $serviceblank["CityArrival"] = $this->DTV($segmentservice, ["CityArrival"]);
            }
            if ($serviceblank["PlaceArrival"] == "") {
                $serviceblank["PlaceArrival"] = $this->DTV($segmentservice, ["PlaceArrival"]);
            }

            $diff = strtotime($serviceblank["ServiceEndDate"]) - strtotime($serviceblank["ServiceStartDate"]);
            $serviceblank["TravelTime"] = round(abs($diff) / 60);

            $serviceblank["Route"] = implode(" - ", $Route);
            $serviceblank["RouteShortened"] = implode(" - ", $RouteShortened);

            $serviceblank["Synh"] = $serviceblank["TypeOfTicket"] . $Bilet["TicketNumber"];
            $serviceblank["TicketNumber"] = $Bilet["TicketNumber"];
            $serviceblank["TicketNumber"] = $Bilet["TicketNumber"];

            $serviceblank["seconded"] = $Bilet["seconded"];

            if ($this->debugclass) {
                //print_r($Bilet);
            }
            //Рассчет сумм
            $serviceblank["price"] = (float)$Bilet["EquivTotal"];
            $serviceblank["amountVAT"] = (float)$Bilet["VAT"];
            $serviceblank["amount"] = (float)$Bilet["EquivTotal"];
            if ($serviceblank["amountVAT"] > 0) {
                $serviceblank["VATrate"] = 110;

                $serviceblank["VATAmount10"] = $serviceblank["amountVAT"];
                $serviceblank["AmountWithVAT10"] = $serviceblank["amountVAT"] / 10 * 110;
            }

            $serviceblank["pricecustomer"] = $serviceblank["price"];
            $serviceblank["amountVATcustomer"] = $serviceblank["amountVAT"];
            $serviceblank["amountclient"] = $serviceblank["amount"];
            $serviceblank["VATratecustomer"] = $serviceblank["VATrate"];

            $serviceblank["ProviderFee"] = (float)$ProviderFee;
            $serviceblank["ServiceFee"] = (float)$Bilet["ServiceFee"];
            $serviceblank["HiddenFee"] = (float)$Bilet["HiddenFee"];
            $serviceblank["CommissionFee"] = (float)$Bilet["CommissionFee"];

            //Отнимем сервисные сборы
            $serviceblank["amountclient"] = $serviceblank["amountclient"] - $serviceblank["ProviderFee"];
            $serviceblank["amountclient"] = $serviceblank["amountclient"] - $serviceblank["ServiceFee"];
            $serviceblank["amountclient"] = $serviceblank["amountclient"] - $serviceblank["HiddenFee"];
            $serviceblank["pricecustomer"] = $serviceblank["amountclient"];

            $serviceblank["AmountServices"] = $serviceblank["amountclient"];

            $serviceblank["date"] = $serviceblank["Depart"];
            //print_r($serviceblank);
            $serviceblank["ReservationNumber"] = $Bilet["ReservationNumber"];

            if (isset($Bilet["Codes"])) {
                $serviceblank["ExtraItemes"] = $Bilet["Codes"];
            }
            if (isset($Bilet["Files"])) {
                $serviceblank["Files"] = $Bilet["Files"];
            }

            $NewBiletInfo = $serviceblank;
            $NewBiletInfo["Segments"] = $NewSegmentsItem;


            $NewBiletsInfo[] = $NewBiletInfo;
        }

        foreach ($NewSegments as $NewSegment) {
            $servises[] = $NewSegment;
        }

        $BiletsInfo = $NewBiletsInfo;
    }


    private function v3avia($orderjson, &$servises)
    {
        $service = $this->get_empty_v3();

        $CBR = $this->DTV($orderjson, ["GetSimpleReserveByIdResult"]);

        $service["nomenclature"] = "БронированиеАвиабилета";
        $service["TypeOfTicket"] = "B";
        if ($this->DTV($CBR, ["@attributes", "id_ReserveState"]) == "5") {
            $service["nomenclature"] = "Авиабилет";
            $service["TypeOfTicket"] = "S";
        } elseif ($this->DTV($CBR, ["@attributes", "id_ReserveState"]) == "6") {
            $service["nomenclature"] = "ВозвратАвиабилета";
            $service["TypeOfTicket"] = "R";
        } elseif ($this->DTV($CBR, ["@attributes", "id_ReserveState"]) == "5") {
            //НЕТ такого статуса, сделано на всякий случай
            $service["nomenclature"] = "Авиабилет";
            $service["TypeOfTicket"] = "V";
        }
        $service["manager"] = $this->DTV($CBR, ["Creator", "@attributes", "Email"]);

        $Payer = [
            "Name" => "Неизвестно",
            "INN" => $this->DTV($CBR, ["Payer", "INN"]),
            "KPP" => $this->DTV($CBR, ["Payer", "KPP"])
        ];
        $service["Payer"] = $Payer;
        $service["partner"] = $Payer;
        $service["contractor"] = $Payer;

        $service["CorteosId"] = $this->DTV($CBR, ["@attributes", "id_Reserve"]);
        $service["ComplexReserve"] = $this->DTV($CBR, ["@attributes", "id_ComplexReserve"]);

        $service["methods"] = ["afterload" => [
            "НажатьКнопкуЗаполнитьСвязанныеУслуги",
            "НажатьКнопкуСформироватьКомиссииВУслуге",
            "НажатьКнопкуСформироватьНаименованиеУслуги"
        ]];


        $OrderSpecificData = $this->DTV($CBR, ["OrderSpecificData"]);
        $BiletInfo = $this->getBiletinfo($service, $servises, $OrderSpecificData, $CBR);

        $this->CreateSegmentsService($service, $servises, $BiletInfo);
        foreach ($BiletInfo as $serviceTicket) {
            $servises[] = $serviceTicket;
        }

        return $service;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    //
    //                  RAIL
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////

    private function AddRail($CBR, &$services, $blankservice, $dataRail)
    {
        $service = $blankservice;


        $service["TicketNumber"] = $this->DTV($dataRail, ["Blanks", "OrderBlank", "@attributes", "TicketNumber"]);
        $service["Synh"] = $service["TypeOfTicket"] . $service["TicketNumber"];
        $service["date"] = $this->DTV($dataRail, ["@attributes", "ReservationDate"], "", "Y-m-d\TH:i:s"); //2019-06-03T17:20:00


        if ($service["TypeOfTicket"] != "S") {
            $service["TicketSales"] = "S" . $service["TicketNumber"];
        }

        $idsupplier = "10" . $this->DTV($CBR, ["OrderSpecificData", "OrderContainer", "@attributes", "id_System"]);
        $supplier = $this->getSupplier($idsupplier);
        $service["supplier"] = $supplier;
        $service["Supplier"] = $service["supplier"];

        $service["TrainNumber"] = $this->DTV($dataRail, ["@attributes", "TrainNumberForCustomer"]);
        $service["Place"] = (int)$this->DTV($dataRail, ["Blanks", "OrderBlank", "@attributes", "PlaceString"]);
        $service["Wagon"] = trim($this->DTV($dataRail, ["@attributes", "CarNumber"]));

        //Даты выезда - заеда
        $service["ServiceStartDate"] = $this->DTV($dataRail, ["UfsDateContainer", "@attributes", "Date"], "", "Y-m-d\TH:i:s"); //2019-06-03T17:20:00
        $service["ServiceEndDate"] = $this->DTV($dataRail, ["UfsDateContainer", "@attributes", "ArrivalDate"], "", "Y-m-d\TH:i:s"); //2019-06-03T17:20:00
        $service["Depart"] = $service["ServiceStartDate"];
        $service["Arrival"] = $service["ServiceEndDate"];
        // UP
        $diff = strtotime($service["ServiceEndDate"]) - strtotime($service["ServiceStartDate"]);
        $service["TravelTime"] = round(abs($diff) / 60);

        //Маршрут
        $StationFrom = $this->DTV($dataRail, ["@attributes", "PassengerDepartureStationName"]);
        $StationTo = $this->DTV($dataRail, ["@attributes", "PassengerArrivalStationName"]);
        $service["AddressDeparture"] = $StationFrom;
        $service["AddressDestination"] = $StationTo;
        $service["Route"] = $service["AddressDeparture"] . " - " . $service["AddressDestination"];

//        $DepartureGeoData = $this->GetStationFromText(["", "railway", "станция " . $StationFrom]);
//        $ArrivalGeoData = $this->GetStationFromText(["", "railway", "станция " . $StationTo]);
//        $service["PlaceDeparture"] = [
//            "Place" => $DepartureGeoData["station"],
//            "City" => $DepartureGeoData["city"],
//            "Country" => $DepartureGeoData["country"]
//        ];
//        $service["PlaceArrival"] = [
//            "Place" => $ArrivalGeoData["station"],
//            "City" => $ArrivalGeoData["city"],
//            "Country" => $ArrivalGeoData["country"]
//        ];
//
//        $service["CityDeparture"] = $DepartureGeoData["city"];
//        $service["CityArrival"] = $ArrivalGeoData["city"];
//
//        $service["Latitude"] = $ArrivalGeoData["latitude"];
//        $service["Longitude"] = $ArrivalGeoData["longitude"];
//        $service["LatitudeDeparture"] = $DepartureGeoData["latitude"];
//        $service["LongitudeDeparture"] = $DepartureGeoData["longitude"];

        $service["DepartureCode"] = $this->DTV($dataRail, ["@attributes", "TrainDepartureStationCode"]);
        $service["ArrivalCode"] = $this->DTV($dataRail, ["@attributes", "TrainArrivalStationCode"]);


        $price = (float)$this->DTV($dataRail, ["Blanks", "OrderBlank", "@attributes", "Amount"]);
        if (($service["TypeOfTicket"] == "R") || ($service["TypeOfTicket"] == "V")) {
            //Цены поставщика
            $Rprice = (float)$this->DTV($dataRail, ["Blanks", "OrderBlank", "OrderBlankRefundInfo", "@attributes", "Amount"]);
            if ($Rprice == 0) {
                $Rprice = $price;
            }
            $service["price"] = -1 * $Rprice;
            $service["amount"] = -1 * $Rprice;

            //Цены клиента
            $service["pricecustomer"] = -1 * $Rprice;
            $service["amountclient"] = -1 * $Rprice;
        } else {
            //Цены поставщика
            $service["price"] = $price;
            $service["amount"] = $price;

            //Цены клиента
            $service["pricecustomer"] = $price;
            $service["amountclient"] = $price;
        }


        //Подготовка вторичных данных
        $Bilet = [];
        $Codes = $this->getCodes($CBR);
        $Files = $this->getFiles($CBR);
        $passager = $this->getPassager($CBR, $Codes);
        $service["seconded"] = $passager;
        if (count($Codes) > 0) {
            $Bilet["Codes"] = $Codes;
        }
        if (isset($Files[$service["TicketNumber"]])) {
            $Bilet["Files"] = $Files[$service["TicketNumber"]];
        }

        //Запись данных в json
        if (isset($Bilet["Codes"])) {
            $service["ExtraItemes"] = $Bilet["Codes"];
        }
        if (isset($Bilet["Files"])) {
            $service["Files"] = $Bilet["Files"];
        }

        $services[] = $service;
    }

    private function v3rail($orderjson, &$services)
    {
        $service = $this->get_empty_v3();

        $CBR = $this->DTV($orderjson, ["GetSimpleReserveByIdResult"]);

        $service["nomenclature"] = "БронированиеЖДБилета";
        $service["TypeOfTicket"] = "B";
        if ($this->DTV($CBR, ["@attributes", "id_ReserveState"]) == "5") {
            $service["nomenclature"] = "ЖДБилет";
            $service["TypeOfTicket"] = "S";
        } elseif ($this->DTV($CBR, ["@attributes", "id_ReserveState"]) == "6") {
            $service["nomenclature"] = "ВозвратЖДБилета";
            $service["TypeOfTicket"] = "R";
        } elseif ($this->DTV($CBR, ["@attributes", "id_ReserveState"]) == "5") {
            //НЕТ такого статуса, сделано на всякий случай
            $service["nomenclature"] = "ВозвратЖДБилета";
            $service["TypeOfTicket"] = "V";
        }
        $service["manager"] = $this->DTV($CBR, ["Creator", "@attributes", "Email"]);
        $service["CorteosId"] = $this->DTV($CBR, ["@attributes", "id_Reserve"]);
        $service["ComplexReserve"] = $this->DTV($CBR, ["@attributes", "id_ComplexReserve"]);

        $Payer = [
            "Name" => "Неизвестно",
            "INN" => $this->DTV($CBR, ["Payer", "INN"]),
            "KPP" => $this->DTV($CBR, ["Payer", "KPP"])
        ];
        $service["Payer"] = $Payer;
        $service["partner"] = $Payer;
        $service["contractor"] = $Payer;

        $service["methods"] = ["afterload" => [
            "НажатьКнопкуСформироватьНаименованиеУслуги",
            "НажатьКнопкуЗаполнитьСвязанныеУслуги",
            "НажатьКнопкуСформироватьКомиссииВУслуге"
        ]];

        $OrderSpecificDatas = $OrderSpecificData = $this->DTV($CBR, ["OrderSpecificData", "OrderContainer", "OrderItems", "OrderItem"]);
        if (isset($OrderSpecificDatas["@attributes"])) {
            $OrderSpecificDatas = [];
            $OrderSpecificDatas[] = $OrderSpecificData;
        }

        foreach ($OrderSpecificDatas as $dataRail) {
            $this->AddRail($CBR, $services, $service, $dataRail);
        }
        //print_r($OrderSpecificDatas);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    //
    //                  HOTEL
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////

    private function getprefix($supplier)
    {
        $result = "";
        if ($supplier["Name"] == "Эй энд Эй") {
            $result = "ada_";
        } elseif ($supplier["Name"] == "Академсервис") {
            $result = "acd_";
        } elseif ($supplier["Name"] == "Хотелбук") {
            $result = "htb_";
        } elseif ($supplier["Name"] == "Свой Тур и Трэвел") {
            $result = "hbs_";
        } elseif ($supplier["Name"] == "Островок") {
            $result = "ost_";
        } elseif ($supplier["Name"] == "Броневик") {
            $result = "brn_";
        } elseif ($supplier["Name"] == "iway") {
            $result = "iway_";
        }
        return $result;
    }

    private function getHotelinfo($CBR, $blankservice, &$services, $MultiItinerary)
    {
        $Locator = $this->DTV($CBR, ["@attributes", "Locator"]);
        $VendorLocator = $this->DTV($CBR, ["VendorLocator"]);

        //6582149

        $BLExtendedBookings = $BLExtendedBooking = $this->DTV($MultiItinerary, ["BLExtendedBooking"]);
        if (isset($BLExtendedBooking["@attributes"])) {
            $BLExtendedBookings = [];
            $BLExtendedBookings[] = $BLExtendedBooking;
        }

        foreach ($BLExtendedBookings as $BLExtendedBooking) {
            $service = $blankservice;

            $Codes = [];
            $service["seconded"] = $this->getPassager($CBR, $Codes);

            $idsupplier = "h" . $this->DTV($BLExtendedBooking, ["@attributes", "id_System"]);
            $supplier = $this->getSupplier($idsupplier);
            $service["supplier"] = $supplier;
            $service["Supplier"] = $service["supplier"];

            $id_Systems = $this->DTV($BLExtendedBooking, ["@attributes", "id_System"]);

            $service["TypeOfTicket"] = $this->getprefix($supplier);
            $service["TicketNumber"] = $Locator;
            $service["ReservationNumber"] = $Locator;
            $service["Synh"] = $service["TypeOfTicket"] . $service["TicketNumber"];

            $Latitude = $this->DTV($BLExtendedBooking, ["@attributes", "Latitude"]);
            $Longitude = $this->DTV($BLExtendedBooking, ["@attributes", "Longitude"]);
            $service["Longitude"] = $Longitude;
            $service["Latitude"] = $Latitude;

            //Описание номера отеля и другой информации
            $HotelName = $this->DTV($BLExtendedBooking, ["HotelName", "BS", "@attributes", "Rus"]);
            $service["HotelName"] = $HotelName;
            $service["HotelAddress"] = $this->DTV($BLExtendedBooking, ["HotelAddress", "BS", "@attributes", "Rus"]);


            $Rooms = $this->DTV($BLExtendedBooking, ["Rooms"]);
            $ExtendedRoomDescription = $this->DTV($Rooms, ["ExtendedRoomDescription"]);
            $BaseHotelRateAccessor = $this->DTV($ExtendedRoomDescription, ["BaseHotelRateAccessor"]);

            $service["NumberTypeName"] = $this->DTV($ExtendedRoomDescription, ["RoomName", "BS", "@attributes", "Rus"]);

            $CheckInTime = $this->DTV($BaseHotelRateAccessor, ["CheckInTime"]);
            $CheckOutTime = $this->DTV($BaseHotelRateAccessor, ["CheckOutTime"]);


            $Accessor = $this->DTV($BaseHotelRateAccessor, ["Accessor"]);
            $Checkin = $this->DTV($Accessor, ["Checkin"]);
            $Checkout = $this->DTV($Accessor, ["Checkout"]);


            if ($CheckInTime != "") {
                $Checkin = $Checkin . " " . $CheckInTime;
                $ServiceStartDate = $this->DTV([$Checkin], [0], "", "d.m.Y H:i");
                if ($ServiceStartDate == "") {
                    $ServiceStartDate = $this->DTV([$Checkin], [0], "", "d.m.Y H:i:s");
                }
            } else {
                $ServiceStartDate = $this->DTV([$Checkin], [0], "", "d.m.Y");
            }
            if ($CheckOutTime != "") {
                $Checkout = $Checkout . " " . $CheckOutTime;
                $ServiceEndDate = $this->DTV([$Checkout], [0], "", "d.m.Y H:i");
                if ($ServiceEndDate == "") {
                    $ServiceEndDate = $this->DTV([$Checkout], [0], "", "d.m.Y H:i:s");
                }
            } else {
                $ServiceEndDate = $this->DTV([$Checkout], [0], "", "d.m.Y");
            }
            
            $service["ServiceStartDate"] = $ServiceStartDate;
            $service["ServiceEndDate"] = $ServiceEndDate;
            $service["Depart"] = $service["ServiceStartDate"];
            $service["Arrival"] = $service["ServiceEndDate"];
            $service["date"] = $ServiceEndDate;

            $diff = strtotime($service["ServiceEndDate"]) - strtotime($service["ServiceStartDate"]);
            $service["Night"] = round(abs($diff) / 60 / 60 / 24);

            $city = $this->DTV($BLExtendedBooking, ["@attributes", "ruCityName"]);
            $cuntry = $this->DTV($BLExtendedBooking, ["@attributes", "ruCountryName"]);
            $service["CityArrival"] = $city;

//            $findPlace = ($cuntry . ", " . $city . ", " . $service["HotelAddress"]);
//            $ArrivalGeoData = $this->GetHotelFromText(["", "house", $findPlace]);
//            $service["PlaceArrival"] = [
//                "Place" => $service["HotelName"],
//                "City" => $ArrivalGeoData["city"],
//                "Country" => $ArrivalGeoData["country"]
//            ];
//            $service["AddressDestination"] = $ArrivalGeoData["name"];

            // Суммы
            $ExtendedRatePrice = $this->DTV($ExtendedRoomDescription, ["ExtendedRatePrice"]);
            $totalprice = (float)$this->DTV($ExtendedRatePrice, ["@attributes", "OriginalPrice"]);

            if ($this->debugclass) {
                print_r($ExtendedRatePrice);
            }

            if ((float)$this->DTV($ExtendedRatePrice, ["@attributes", "ComparisionOriginalPrice"]) != 0) {
                $price = (float)$this->DTV($ExtendedRatePrice, ["@attributes", "ComparisionOriginalPrice"]);
            } else {
                $price = (float)$this->DTV($ExtendedRatePrice, ["@attributes", "OriginalPrice"]);
            }

            $VAT = $this->DTV($ExtendedRatePrice, ["VAT"]);
            $VAT = $this->toint($VAT);

            $service["price"] = $price;
            if ($VAT > 0) {
                $service["amountVAT"] = $VAT;
                $service["VATrate"] = 120;
            }
            $service["amount"] = $price;

            $service["pricecustomer"] = $price;
            $service["amountVATcustomer"] = $service["amountVAT"];
            $service["VATratecustomer"] = $service["VATrate"];
            $service["amountclient"] = $service["amount"];

            $service["AmountServices"] = $service["amountclient"];

            if ((float)$this->DTV($ExtendedRatePrice, ["@attributes", "ComparisionServiceFee"]) != 0) {
                $ServiceFee = $this->DTV($ExtendedRatePrice, ["@attributes", "ComparisionServiceFee"]);
            } else {
                $ServiceFee = $this->DTV($ExtendedRatePrice, ["@attributes", "ComparisionServiceFee"]);
            }
            $service["ServiceFee"] = (float)$ServiceFee;

            if ((float)$this->DTV($ExtendedRatePrice, ["@attributes", "ComparisionHiddenServiceFee"]) != 0) {
                $HiddenServiceFee = $this->DTV($ExtendedRatePrice, ["@attributes", "ComparisionHiddenServiceFee"]);
            } else {
                $HiddenServiceFee = $this->DTV($ExtendedRatePrice, ["@attributes", "HiddenServiceFee"]);
            }
            $service["HiddenServiceFee"] = (float)$HiddenServiceFee;

            $CommissionDouble = $this->DTV($ExtendedRatePrice, ["CommissionDouble"]);
            $service["Commission"] = (float)$CommissionDouble;

            $services[] = $service;
        }
    }

    private function v3hotel($orderjson, &$services)
    {
        $service = $this->get_empty_v3();

        $CBR = $this->DTV($orderjson, ["GetSimpleReserveByIdResult"]);

        $service["nomenclature"] = "БронированиеПроживания";
        $service["TypeOfTicket"] = "B";
        if ($this->DTV($CBR, ["@attributes", "id_ReserveState"]) == "5") {
            $service["nomenclature"] = "Проживание";
            $service["TypeOfTicket"] = "S";
        } elseif ($this->DTV($CBR, ["@attributes", "id_ReserveState"]) == "6") {
            $service["nomenclature"] = "ОтменаПроживания";
            $service["TypeOfTicket"] = "V";
        }
        $service["manager"] = $this->DTV($CBR, ["Creator", "@attributes", "Email"]);
        $service["CorteosId"] = $this->DTV($CBR, ["@attributes", "id_Reserve"]);
        $service["ComplexReserve"] = $this->DTV($CBR, ["@attributes", "id_ComplexReserve"]);

        $Payer = [
            "Name" => "Неизвестно",
            "INN" => $this->DTV($CBR, ["Payer", "INN"]),
            "KPP" => $this->DTV($CBR, ["Payer", "KPP"])
        ];
        $service["Payer"] = $Payer;
        $service["partner"] = $Payer;
        $service["contractor"] = $Payer;

        $MultiItinerary = $this->DTV($CBR, ["OrderSpecificData"]);
        $this->getHotelinfo($CBR, $service, $services, $MultiItinerary);

        $service["methods"] = ["afterload" => [
            "НажатьКнопкуЗаполнитьСвязанныеУслуги",
            "НажатьКнопкуСформироватьКомиссииВУслуге",
            "НажатьКнопкуСформироватьНаименованиеУслуги"
        ]];

        return $service;

    }


    private function v3($orderjson)
    {
        $servises = [];

        $type = $this->DTV($orderjson, ["GetSimpleReserveByIdResult", "@attributes", "OrderTypeName"]);
        if ($type == "Avia") {
            $service = $this->v3avia($orderjson, $servises);
        } elseif ($type == "Rail") {
            $service = $this->v3rail($orderjson, $servises);
        } elseif ($type == "Hotel") {
            $service = $this->v3hotel($orderjson, $servises);
        }

        return ["services" => $servises];
    }

    public function getinfo($params)
    {

        $result = ["result" => false];

        if ($this->Auth->userauth()) {
            //if (true) {
            if ($params[1] == "v3") {

                $tokenjson = $this->GetToken();
                if ($tokenjson["result"]) {
                    $token = $tokenjson["content"];

                    $text = $this->phpinput;
                    $Parser = new Parser($this->metod);
                    $Parser->SetUseParser("Corteoscb", md5($text), $this->Auth->getuserid());

                    $input = $this->phpinput;
                    $xmlobject = simplexml_load_string($input);
                    $jsonv = $this->object2array($xmlobject);

                    $id_Reserve = $this->DTV($jsonv, ["id_Reserve"]);
                    $resultorder = $this->getorder($token, $id_Reserve);

                    if ($this->debugclass) {
                        echo "DEBUGGER!!!\r\n";
                    }

                    if ($resultorder["result"]) {

                        $jsonv3 = $this->v3($resultorder["content"]);
                        $result = ["result" => true, "json" => $resultorder["content"], "jsonv3" => $jsonv3];
                    } else {
                        $result["error"] = "Error getOrder";
                    }

                } else {
                    $result["error"] = "Error getToken";
                }
            } else {
                $result["error"] = "Not for this version";
            }
        } else {
            $result["error"] = "Authorization fail";
        }

        return $result;
    }


    public function getinfotest($params)
    {
        $result = ["result" => false];

        $orderid = $params[1];  //"2190772";
        $content = file_get_contents("tmp/corteos_" . $orderid . ".xml");
        $sxml = simplexml_load_string($content);
        $Body = $sxml->children('soap', true)->Body->children('http://corteos.ru');
        $xmld = $Body->asXML();
        $Body = simplexml_load_string($xmld);
        $content = $this->object2array($Body);
        $resultorder = ["result" => true, "content" => $content];

        if ($resultorder["result"]) {

            $jsonv3 = $this->v3($resultorder["content"]);
            $result = ["result" => true, "jsonv3" => $jsonv3, "json" => $resultorder["content"]];
        } else {
            $result["message"] = "Error getOrder";
        }

        return $result;
    }


}

/*

Перед созданием услуги Кортеос

Если СтруктураУслуги.Свойство("nomenclature") Тогда
	Если СтруктураУслуги.nomenclature = "Проживание" ИЛИ СтруктураУслуги.nomenclature = "ОтменаПроживания" Тогда
		ИДУслуги = СтруктураУслуги.Synh;

		Запрос = Новый Запрос;
		Запрос.УстановитьПараметр("Характеристика", ПланыВидовХарактеристик.НаборХарактеристикДляНоменклатуры.НомерБрони );
		Запрос.УстановитьПараметр("ЗначениеХарактеристики", ИДУслуги);
		Запрос.Текст = "ВЫБРАТЬ ПЕРВЫЕ 1
		               |	ИнформацияПоУслуге.Услуга КАК Услуга,
		               |	ИнформацияПоУслуге.Характеристика КАК Характеристика,
		               |	ИнформацияПоУслуге.ЗначениеХарактеристики КАК ЗначениеХарактеристики
		               |ИЗ
		               |	РегистрСведений.ИнформацияПоУслуге КАК ИнформацияПоУслуге
		               |ГДЕ
		               |	ИнформацияПоУслуге.Характеристика = &Характеристика
		               |	И ИнформацияПоУслуге.ЗначениеХарактеристики = &ЗначениеХарактеристики";
		Выборка = Запрос.Выполнить().Выбрать();
		Если Выборка.Следующий() Тогда
			ВхОбъект = Выборка.Услуга;

			Запрос = Новый Запрос;
			Запрос.УстановитьПараметр("Услуга", ВхОбъект);
			Запрос.УстановитьПараметр("Характеристика", ПланыВидовХарактеристик.НаборХарактеристикДляНоменклатуры.IDСинхронизации );
			Запрос.Текст = "ВЫБРАТЬ ПЕРВЫЕ 1
			               |	ИнформацияПоУслуге.Услуга КАК Услуга,
			               |	ИнформацияПоУслуге.Характеристика КАК Характеристика,
			               |	ИнформацияПоУслуге.ЗначениеХарактеристики КАК ЗначениеХарактеристики
			               |ИЗ
			               |	РегистрСведений.ИнформацияПоУслуге КАК ИнформацияПоУслуге
			               |ГДЕ
			               |	ИнформацияПоУслуге.Услуга = &Услуга
			               |	И ИнформацияПоУслуге.Характеристика = &Характеристика";
			Выборка = Запрос.Выполнить().Выбрать();
			Если Выборка.Следующий() Тогда
				IDСинхронизации = Выборка.ЗначениеХарактеристики;
				СтруктураУслуги.Synh = IDСинхронизации;
				СтруктураУслуги.ReservationNumber = ИДУслуги;
			КонецЕсли;

		КонецЕсли;
	КонецЕсли;
КонецЕсли;



 */