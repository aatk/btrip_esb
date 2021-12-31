<?php

class Im extends ex_classlite
{
    private $metod;

    private $Auth;


    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        parent::__construct();
        $this->metod = $metod;

        $this->debugclass = $debug;
        $this->Auth = new Auth();
    }


    public function getinfo($params)
    {

        if ($this->Auth->userauth()) {
            if ($params[1] == "v3") {

                $text = $this->phpinput;
                $Parser = new Parser($this->metod);
                $Parser->SetUseParser("Im", md5($text), $this->Auth->getuserid());

                $textxml = $this->phpinput;
                //$xml = simplexml_load_string("<xml>".$textxml."</xml>");
                //$json = $this->object2array($xml);
                $xml = simplexml_load_string($textxml);
                $json = $this->object2array($xml);


                $v2 = $this->v2($textxml);
                $v3 = $this->v3($v2);

                $result = ["result" => true, "json" => $json, "jsonv2" => $v2, "jsonv3" => $v3];

            } else {
                $result["error"] = "Not for this version";
            }
        } else {
            $result["error"] = "Authorization fail";
        }

        return $result;
    }

    private function v3_AE($json)
    {

        $services = [];

        if ($this->debugclass) {
            print_r($json);
        }


        if (isset($json["Ticket"]["Id"])) {
            $blank = [];
            $blank[] = $json["Ticket"];
        } else {
            $blank = $json["Ticket"];
        }

        $DepartDate = $this->DTV($json, ["DepartDate"], "", "d.m.Y H:i:s"); //25.05.2019 00:00:00
        $CurTime = $this->DTV($json, ["CurTime"], "", "d.m.Y H:i:s"); //25.05.2019 00:00:00
        $Airport = $this->DTV($json, ["Airport"]);
        $Direction = $this->DTV($json, ["Direction"]);
        $Type = $this->DTV($json, ["Type"]);

        foreach ($blank as $Ticket) {
            $service = $this->get_empty_v3();


            if ($this->debugclass) {
                print_r($CurTime);
            }

            $service["nomenclature"] = "Аэроэкспресс";
            $service["date"] = $CurTime;
            $service["Depart"] = $DepartDate;

            $service["supplier"] = ["INN" => "7708510731", "KPP" => "770401001", "Name" => "УФС"];
            $service["Supplier"] = $service["supplier"];

            $Id = $this->DTV($Ticket, ["Id"]);
            $Price = $this->DTV($Ticket, ["Price"]);
            $PassengerFio = $this->DTV($Ticket, ["PassengerFio"]);


            $service["TicketNumber"] = $Id;
            $service["TypeOfTicket"] = "S";
            $pre = 1;
            if ($Type == "14") {
                $service["TypeOfTicket"] = "R";
                $pre = -1;
            }

            $service["Synh"] = $service["TypeOfTicket"] . $service["TicketNumber"];


            $service["ServiceStartDate"] = $DepartDate;


            $service["price"] = $pre * (float)$Price;
            $service["amount"] = $pre * (float)$Price;
            $service["pricecustomer"] = $pre * (float)$Price;
            $service["amountclient"] = $pre * (float)$Price;

            $Seconded = [];

            $Name = $this->mb_ucwords(mb_strtolower($PassengerFio));
            $ars = explode(" ", $Name);

            $Seconded["FirstName"] = $this->DTV($ars, [1]);
            $Seconded["LastName"] = $this->DTV($ars, [0]);
            $Seconded["SurName"] = $this->DTV($ars, [2]);
            $Seconded["FirstNameLatin"] = "";
            $Seconded["LastNameLatin"] = "";
            $Seconded["SurNameLatin"] = "";

            $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
            $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);

            $service["Seconded"][] = $Seconded;

            $services[] = $service;
        }


        $jsonv3["services"] = $services;

        return $jsonv3;
    }

    private function v2($content)
    {

        $xml = simplexml_load_string($content);
        $gjson = $this->object2array($xml);

        $json = $this->DTV($gjson, ["OrderItem"]);

        $StationFromCode = $this->DTV($json, ["OriginLocationCode"]);
        $StationToCode = $this->DTV($json, ["DestinationLocationCode"]);

        $service = [];
        $service["Terminal"] = $this->DTV($gjson, ["EmployeeName"]);
        $service["TrainNum"] = $json["TrainNumber"];
        $service["Type"] = "1";//$json["ServiceType"];
        if ($json["OperationType"] == "Return") {
            $service["Type"] = "13";//$json["ServiceType"];
        }

        $service["CreateTime"] = $this->DTV($json, ["CreateDateTime"], "", "Y-m-d\TH:i:s");
        $service["BookingTime"] = $this->DTV($json, ["CreateDateTime"], "", "Y-m-d\TH:i:s");
        $service["ConfirmTime"] = $this->DTV($json, ["ConfirmDateTime"], "", "Y-m-d\TH:i:s");
        $service["ConfirmTimeLimit"] = $this->DTV($json, ["ConfirmTimeLimit"], "", "Y-m-d\TH:i:s");


        $service["CarNum"] = $json["CarNumber"];
        $service["CarType"] = $json["CarType"];

        //<DepartureDateTime>2019-10-16T08:15:00</DepartureDateTime>
        //<ArrivalDateTime>  2019-10-16T11:35:00</ArrivalDateTime>

        $service["DepartTime"] = $this->DTV($json, ["LocalDepartureDateTime"], "", "Y-m-d\TH:i:s");
        $service["ArrivalTime"] = $this->DTV($json, ["LocalArrivalDateTime"], "", "Y-m-d\TH:i:s");

        if ($service["DepartTime"] == "") {
            $service["DepartTime"] = $this->DTV($json, ["DepartureDateTime"], "", "Y-m-d\TH:i:s");
        }

        if ($service["ArrivalTime"] == "") {
            $service["ArrivalTime"] = $this->DTV($json, ["ArrivalDateTime"], "", "Y-m-d\TH:i:s");
        }

        $service["StationFrom"] = $json["OriginLocationName"];
        $service["StationTo"] = $json["DestinationLocationName"];
        $service["StationFromCode"] = $StationFromCode;
        $service["StationToCode"] = $StationToCode;

        $service["Carrier"] = $json["Carrier"];
        $service["CarrierInn"] = $json["CarrierTin"];

        $service["ExpierSetEr"] = $this->DTV($json, ["ElectronicRegistrationExpirationDateTime"], "", "Y-m-d\TH:i:s");

        $diff = strtotime($service["ArrivalTime"]) - strtotime($service["DepartTime"]);
        $service["TravelTime"] = round(abs($diff) / 60);

        $service["IsInternational"] = $json["IsInterstate"];


        if (isset($json["OrderItemBlanks"]["@attributes"])) {
            $blank = [];
            $blank[] = $json["OrderItemBlanks"];
        } else {
            $blank = $json["OrderItemBlanks"];
        }

        if (isset($gjson["OrderCustomers"]["OrderCustomerId"])) {
            $Passenger = [];
            $Passenger[] = $gjson["OrderCustomers"];
        } else {
            $Passenger = $gjson["OrderCustomers"];
        }

        $OrderItemCustomers = $OrderItemCustomer = $this->DTV($json, ["OrderItemCustomers"]);
        if (isset($OrderItemCustomers["@attributes"])) {
            $OrderItemCustomers = [];
            $OrderItemCustomers[] = $OrderItemCustomer;
        }

        $services = [];

        foreach ($blank as $value) {
            $serviceblank = $service;


            $Type = $this->DTV($value, ["@attributes", "type"]);
            $extype = explode(",", $Type);
            $Type = explode(".", $extype[0])[5];
            $serviceblank["TypeService"] = $Type;

            $serviceblank["RegTime"] = $this->DTV($value, ["ElectronicRegistrationSetDateTime"], "", "Y-m-d\TH:i:s");
            $serviceblank["ID"] = $value["OrderItemBlankId"];
            $serviceblank["TicketNum"] = $value["BlankNumber"];

            //Розобрать

            $serviceblank["RetFlag"] = $value["RetFlag"];
            $serviceblank["Amount"] = $value["Amount"];


            $serviceblank["AmountNDS"] = 0;
            $serviceblank["ServiceNDS"] = 0;


            $Components = $Component = $this->DTV($value, ["FareInfo", "Components"]);
            if (isset($Components["ComponentName"])) {
                $Components = [];
                $Components[] = $Component;
            }

            foreach ($Components as $Component) {
                //
                $ComponentName = $this->DTV($Component, ["ComponentName"]);
                if ($ComponentName == "СБОР ЗА ВОЗВРАТ") {
                    //Штраф - СборПеревозчикаЗаВозврат
                    $serviceblank["AmountOfPenalty"] = $this->DTV($Component, ["ComponentCost"]);
                    $serviceblank["AmountVATOfPenalty"] = $this->DTV($Component, ["Vat"]);
                } elseif ($ComponentName == "СЕРВИС") {
                    $serviceblank["AmountWhithNDS"] = $this->DTV($Component, ["ComponentCost"]);
                    $serviceblank["ServiceWhithNDS"] = $this->DTV($Component, ["ComponentCost"]);

                    $serviceblank["ServiceNDS"] = $this->DTV($Component, ["Vat"]);
                    $serviceblank["AmountNDS"] = $this->DTV($Component, ["Vat"]);
                }
            }

            //$serviceblank["AmountWhithNDS"] = $value["ServicePrice"];
            //$serviceblank["ServiceWhithNDS"] = $value["ServicePrice"];

            $VatRateValues = $this->DTV($value, ["VatRateValues"]);
            if (isset($VatRateValues["Rate"])) {
                $VatRateValues = [];
                $VatRateValues[] = $this->DTV($value, ["VatRateValues"]);
            }

            /*
            foreach ($VatRateValues as $VatRateValue) {
                //print_r($VatRateValue);
                $RateVat = $this->DTV($VatRateValue, ["Rate"], 0);
                if ($RateVat > 0) {
                    $serviceblank["AmountNDS"] = $serviceblank["AmountNDS"] + (float)$this->DTV($VatRateValue, ["Value"], 0);
                    $serviceblank["ServiceNDS"] = $serviceblank["AmountNDS"];
                }
            }
            */
            //print_r($value["AmountNDS"]);

            $serviceblank["Profit"] = $this->DTV($value, ["AgentFeeCalculation", "Profit"]);

            $serviceblank["ReservationNumber"] = $this->DTV($json, ["ReservationNumber"]);

            $serviceblank["ReservedSeatAmount"] = $value["ReservedSeatAmount"];
            $serviceblank["TariffRateNds"] = $value["TariffRateNds"];
            $serviceblank["ServiceRateNds"] = $value["ServiceRateNds"];
            $serviceblank["ServiceRateNds"] = $value["ServiceRateNds"];
            $serviceblank["CommissionFeeRateNds"] = $value["CommissionFeeRateNds"];
            $serviceblank["ReclamationCollectRateNds"] = $value["ReclamationCollectRateNds"];
            $serviceblank["TariffReturnNds"] = $value["TariffReturnNds"];
            $serviceblank["ServiceReturnNds"] = $value["ServiceReturnNds"];
            $serviceblank["CommissionFeeReturnNds"] = $value["CommissionFeeReturnNds"];
            $serviceblank["ReclamationCollectReturnNds"] = $value["ReclamationCollectReturnNds"];
            $serviceblank["TicketReturnAmount"] = $value["TicketReturnAmount"];
            $serviceblank["ReservedSeatReturnAmount"] = $value["ReservedSeatReturnAmount"];
            $serviceblank["ServiceReturnAmount"] = $value["ServiceReturnAmount"];
            $serviceblank["ReclamationCollectReturnAmount"] = $value["ReclamationCollectReturnAmount"];
            $serviceblank["TariffType"] = $value["TariffType"];
            $serviceblank["PassengerCard"] = $value["PassengerCard"];
            $serviceblank["RemoteCheckIn"] = $value["RemoteCheckIn"];
            $serviceblank["PrintFlag"] = $value["PrintFlag"];
            $serviceblank["RzhdStatus"] = $value["RzhdStatus"];

            $serviceblank["TicketToken"] = $value["TicketToken"];


            foreach ($serviceblank as $key => $valuesb) {
                if (is_array($value) && (count($valuesb) == 0)) {
                    $serviceblank[$key] = "";
                }
            }

            //Passenger
            $TravelPeaples = [];

            if ($this->debugclass) {
                print_r($OrderItemCustomers);
            }

            foreach ($OrderItemCustomers as $OrderItemCustomer) {
                $id_costumer = $OrderItemCustomer["OrderCustomerId"];

                if ($this->debugclass) {
                    print_r($OrderItemCustomer);
                }

                if (isset($OrderItemCustomer["OrderItemBlankId"])) {
                    $OrderItemBlankId = $OrderItemCustomer["OrderItemBlankId"];
                } else {
                    $OrderItemBlankId = $OrderItemCustomer["OrderItemBlankIds"];
                }
                if ($OrderItemBlankId == $value["OrderItemBlankId"]) {
                    $serviceblank["Place"] = $OrderItemCustomer["Places"];
                    $serviceblank["Placetier"] = $OrderItemCustomer["Places"];

                    foreach ($Passenger as $valuep) {
                        //print_r($valuep["OrderCustomerId"] ." : ". $id_costumer);
                        if ($valuep["OrderCustomerId"] == $id_costumer) {
                            //
                            //print_r($valuep);
                            $TravelPeaple = [];

                            $TravelPeaple["Type"] = $valuep["Type"];
                            $TravelPeaple["DocType"] = $valuep["DocumentType"];
                            $TravelPeaple["DocumentNumber"] = $valuep["DocumentNumber"];
                            $name = $valuep["LastName"] . " " . $valuep["FirstName"] . " " . $valuep["MiddleName"];
                            $TravelPeaple["Name"] = $name;
                            if ($valuep["Sex"] == "Male") {
                                $TravelPeaple["Sex"] = "M";
                            } else {
                                $TravelPeaple["Sex"] = "F";
                            }
                            if (isset($valuep["BirthDay"])) {
                                $TravelPeaple["BirthDay"] = $valuep["BirthDate"];
                            } else {
                                $TravelPeaple["BirthDay"] = "";
                            }


                            $TravelPeaples[] = $TravelPeaple;
                        }
                    }
                }
            }

            $serviceblank["TravelPeoples"] = $TravelPeaples;

            $services[] = $serviceblank;
        }

        $res["services"] = $services;

        //print_r($res);
        return $res;
    }

    private function v3addonservice($jsonv3sevice)
    {
        $services = [];

        if ($jsonv3sevice["AmountWithVAT18"] > 0) {
            //добавим услугу на 18%
            $service = $jsonv3sevice;

            $service["nomenclature"] = "СервисныеУслугиПеревозчика";
            $service["ownerservice"] = $jsonv3sevice["Synh"];
            $service["ApplicationService"] = $jsonv3sevice["Synh"];
            $service["attachedto"] = $jsonv3sevice["Synh"];

            $service["Synh"] = $service["nomenclature"] . $jsonv3sevice["Synh"];
            $service["TicketNumber"] = "";
            $service["TypeOfTicket"] = "";
            $service["TypeThisService"] = "Загруженная";

            $service["pricecustomer"] = $jsonv3sevice["AmountWithVAT18"];
            $service["amountclient"] = $jsonv3sevice["AmountWithVAT18"];
            $service["amountVATcustomer"] = $jsonv3sevice["VATAmount18"];
            $service["VATratecustomer"] = 20;

            $service["price"] = $jsonv3sevice["AmountWithVAT18"];
            $service["amount"] = $jsonv3sevice["AmountWithVAT18"];
            $service["amountVAT"] = $jsonv3sevice["VATAmount18"];
            $service["VATrate"] = 20;

            $service["AmountExcludingVAT"] = 0;
            $service["VATAmount10"] = 0;
            $service["VATAmount18"] = 0;
            $service["AmountWithVAT10"] = 0;
            $service["AmountWithVAT18"] = 0;
            $service["AmountServices"] = 0;
            $service["AmountOfPenalty"] = 0;
            $service["VendorFeeAmount"] = 0;

            $services[] = $service;
        }

        if ($jsonv3sevice["AmountWithVAT18"] < 0) {
            //добавим услугу на 18%
            $service = $jsonv3sevice;

            $service["nomenclature"] = "СервисныеУслугиПеревозчикаВозврат";
            $service["ownerservice"] = $jsonv3sevice["Synh"];
            $service["ApplicationService"] = $jsonv3sevice["Synh"];
            $service["attachedto"] = $jsonv3sevice["Synh"];

            $service["Synh"] = $service["nomenclature"] . $jsonv3sevice["Synh"];
            $service["TicketNumber"] = "";
            $service["TypeOfTicket"] = "";
            $service["TypeThisService"] = "Загруженная";

            $service["pricecustomer"] = $jsonv3sevice["AmountWithVAT18"];
            $service["amountclient"] = $jsonv3sevice["AmountWithVAT18"];
            $service["amountVATcustomer"] = $jsonv3sevice["VATAmount18"];
            $service["VATratecustomer"] = 20;

            $service["price"] = $jsonv3sevice["AmountWithVAT18"];
            $service["amount"] = $jsonv3sevice["AmountWithVAT18"];
            $service["amountVAT"] = $jsonv3sevice["VATAmount18"];
            $service["VATrate"] = 20;

            $service["AmountExcludingVAT"] = 0;
            $service["VATAmount10"] = 0;
            $service["VATAmount18"] = 0;
            $service["AmountWithVAT10"] = 0;
            $service["AmountWithVAT18"] = 0;
            $service["AmountServices"] = 0;
            $service["AmountOfPenalty"] = 0;
            $service["VendorFeeAmount"] = 0;

            $services[] = $service;
        }


        if ($jsonv3sevice["AmountOfPenalty"] > 0) {
            //добавим услугу на 18%
            $service = $jsonv3sevice;

            $service["nomenclature"] = "СборПеревозчикаЗаВозврат";
            $service["ownerservice"] = $jsonv3sevice["Synh"];
            $service["ApplicationService"] = $jsonv3sevice["Synh"];
            //$service["attachedto"] = $jsonv3sevice["Synh"];

            $service["Synh"] = $service["nomenclature"] . $jsonv3sevice["Synh"];
            $service["TicketNumber"] = "";
            $service["TypeOfTicket"] = "";
            $service["TypeThisService"] = "Загруженная";

            $service["pricecustomer"] = $jsonv3sevice["AmountOfPenalty"];
            $service["amountclient"] = $jsonv3sevice["AmountOfPenalty"];
            $service["amountVATcustomer"] = $jsonv3sevice["AmountVATOfPenalty"];
            $service["VATratecustomer"] = 20;

            $service["price"] = $jsonv3sevice["AmountOfPenalty"];
            $service["amount"] = $jsonv3sevice["AmountOfPenalty"];
            $service["amountVAT"] = $jsonv3sevice["AmountVATOfPenalty"];
            $service["VATrate"] = 20;

            $service["AmountExcludingVAT"] = 0;
            $service["VATAmount10"] = 0;
            $service["VATAmount18"] = 0;
            $service["AmountWithVAT10"] = 0;
            $service["AmountWithVAT18"] = 0;
            $service["AmountServices"] = 0;
            $service["AmountOfPenalty"] = 0;
            $service["VendorFeeAmount"] = 0;

            $services[] = $service;
        }

        if ($jsonv3sevice["AmountWithVAT10"] > 0) {
            //добавим услугу на 10%
            $service = $jsonv3sevice;

            $service["nomenclature"] = "СервисныеУслугиПеревозчика";
            $service["ownerservice"] = $jsonv3sevice["Synh"];
            $service["ApplicationService"] = $jsonv3sevice["Synh"];
            $service["attachedto"] = $jsonv3sevice["Synh"];

            $service["Synh"] = $service["nomenclature"] . $jsonv3sevice["Synh"];
            $service["TicketNumber"] = "";
            $service["TypeOfTicket"] = "";
            $service["TypeThisService"] = "Загруженная";

            $service["pricecustomer"] = $jsonv3sevice["AmountWithVAT10"];
            $service["amountclient"] = $jsonv3sevice["AmountWithVAT10"];
            $service["amountVATcustomer"] = $jsonv3sevice["VATAmount10"];
            $service["VATratecustomer"] = 10;

            $service["price"] = $jsonv3sevice["AmountWithVAT10"];
            $service["amount"] = $jsonv3sevice["AmountWithVAT10"];
            $service["amountVAT"] = $jsonv3sevice["VATAmount10"];
            $service["VATrate"] = 10;

            $service["AmountExcludingVAT"] = 0;
            $service["VATAmount10"] = 0;
            $service["VATAmount18"] = 0;
            $service["AmountWithVAT10"] = 0;
            $service["AmountWithVAT18"] = 0;
            $service["AmountServices"] = 0;
            $service["AmountOfPenalty"] = 0;
            $service["VendorFeeAmount"] = 0;

            $services[] = $service;
        }

        if ($jsonv3sevice["VendorFeeAmount"] > 0) {
            //добавим услугу сбор поставщика
            $service = $jsonv3sevice;

            $service["nomenclature"] = "СборПоставщика";
            $service["ownerservice"] = $jsonv3sevice["Synh"];
            $service["ApplicationService"] = $jsonv3sevice["Synh"];
            $service["attachedto"] = $jsonv3sevice["Synh"];

            $service["Synh"] = $service["nomenclature"] . $jsonv3sevice["Synh"];
            $service["TicketNumber"] = "";
            $service["TypeOfTicket"] = "";
            $service["TypeThisService"] = "Загруженная";

            $service["pricecustomer"] = $jsonv3sevice["VendorFeeAmount"];
            $service["amountclient"] = $jsonv3sevice["VendorFeeAmount"];
            $service["VATratecustomer"] = 18;
            $service["amountVATcustomer"] = round($service["pricecustomer"] / (100 + $service["VATratecustomer"]) * $service["VATratecustomer"], 2);

            $service["price"] = $jsonv3sevice["VendorFeeAmount"];
            $service["amount"] = $jsonv3sevice["VendorFeeAmount"];
            $service["VATrate"] = 18;
            $service["amountVAT"] = round($service["price"] / (100 + $service["VATrate"]) * $service["VATrate"], 2);

            $service["AmountExcludingVAT"] = 0;
            $service["VATAmount10"] = 0;
            $service["VATAmount18"] = 0;
            $service["AmountWithVAT10"] = 0;
            $service["AmountWithVAT18"] = 0;
            $service["AmountServices"] = 0;
            $service["AmountOfPenalty"] = 0;
            $service["VendorFeeAmount"] = 0;

            $services[] = $service;

        }

        return $services;
    }

    private function v3($jsonv2)
    {
        $services = [];

        foreach ($jsonv2["services"] as $inservice) {
            $service = $this->get_empty_v3();
            //
            foreach ($service as $key => $value) {
                if (isset($inservice[$key])) {
                    $service[$key] = $inservice[$key];
                }
            }


            if ($inservice["Type"] == "1") {
                $service["TypeOfTicket"] = "S";
            } else {
                $service["TypeOfTicket"] = "R";
            }

            $service["Synh"] = $service["TypeOfTicket"] . $inservice["TicketNum"];

            $Type = 1;
            if ($inservice["TypeService"] == "Bus") {
                $Type = 2;
                $service["nomenclature"] = "БилетНаАвтобус";
                if ($service["TypeOfTicket"] == "R") {
                    $service["nomenclature"] = "ВозвратБилетаНаАвтобус";
                }
            } elseif ($inservice["TypeService"] == "Avia") {
                $Type = 2;
                $service["nomenclature"] = "Авиабилет";
                $service["Synh"] = $service["TypeOfTicket"] . substr($inservice["TicketNum"], 0, 3) . "-" . substr($inservice["TicketNum"], 3);
                if ($service["TypeOfTicket"] == "R") {
                    $service["nomenclature"] = "ВозвратАвиабилета";
                    $service["Synh"] = $service["TypeOfTicket"] . $inservice["TicketNum"];
                }
            } else {
                $service["nomenclature"] = "ЖДБилет";
                if ($service["TypeOfTicket"] == "R") {
                    $service["nomenclature"] = "ВозвратЖДБилета";
                }
            }


            $service["TicketNumber"] = $inservice["TicketNum"];

            $service["date"] = $inservice["CreateTime"];
            $service["manager"] = $inservice["Terminal"];


            $service["ServiceStartDate"] = $inservice["DepartTime"];
            $service["ServiceEndDate"] = $inservice["ArrivalTime"];

            $service["Depart"] = $service["ServiceStartDate"];
            $service["Arrival"] = $service["ServiceEndDate"];

            $service["AddressDeparture"] = $inservice["StationFrom"];
            $service["AddressDestination"] = $inservice["StationTo"];

            $service["PlaceDeparture"] = $inservice["StationFromCode"];
            $service["PlaceArrival"] = $inservice["StationToCode"];

            $serviceblank["ReservationNumber"] = $inservice["ReservationNumber"];

            if ($Type == 1) {
//                $DepartureGeoData = $this->GetStationFromText(["", "railway", "станция ".$inservice["StationFrom"] ]);
//                $ArrivalGeoData = $this->GetStationFromText(["", "railway", "станция ".$inservice["StationTo"]]);
//
//                $service["PlaceDeparture"] = [
//                    "Place" => $DepartureGeoData["station"],
//                    "City" => $DepartureGeoData["city"],
//                    "Country" => $DepartureGeoData["country"]
//                ];
//                $service["PlaceArrival"] = [
//                    "Place" => $ArrivalGeoData["station"],
//                    "City" => $ArrivalGeoData["city"],
//                    "Country" => $ArrivalGeoData["country"]
//                ];
//
//                $service["CityDeparture"] = $DepartureGeoData["city"];
//                $service["CityArrival"] = $ArrivalGeoData["city"];
//
//
//                $service["Latitude"] = $ArrivalGeoData["latitude"];
//                $service["Longitude"] = $ArrivalGeoData["longitude"];
//                $service["LatitudeDeparture"] = $DepartureGeoData["latitude"];
//                $service["LongitudeDeparture"] = $DepartureGeoData["longitude"];

                $service["Route"] = $service["CityDeparture"] . " - " . $service["CityArrival"];
            } else {
                $service["Route"] = $service["AddressDeparture"] . " - " . $service["AddressDestination"];
            }


            $service["DepartureCode"] = $inservice["StationFromCode"];
            $service["ArrivalCode"] = $inservice["StationToCode"];


            $service["supplier"] = ["INN" => "9717045555", "KPP" => "771401001", "Name" => "ИМ"];
            $service["Supplier"] = $service["supplier"];


            $service["TrainNumber"] = $inservice["TrainNum"];

            $service["price"] = (float)$inservice["Amount"];
            $service["amount"] = (float)$service["price"];

            $service["AmountWithVAT18"] = (float)$inservice["AmountWhithNDS"];
            $service["VATAmount18"] = (float)$inservice["AmountNDS"];

            $service["AmountOfPenalty"] = (float)$inservice["AmountOfPenalty"];
            $service["AmountVATOfPenalty"] = (float)$inservice["AmountVATOfPenalty"];

            $service["AmountServices"] = (float)$service["amount"] - (float)$service["AmountWithVAT18"];

            $service["pricecustomer"] = $service["AmountServices"];
            $service["amountclient"] = $service["AmountServices"];

            if ($service["TypeOfTicket"] == "R") {

                $service["price"] = -1 * ($service["price"] + $service["AmountOfPenalty"]);
                $service["amount"] = -1 * ($service["amount"] + $service["AmountOfPenalty"]);

                $service["AmountWithVAT18"] = -1 * $service["AmountWithVAT18"];
                $service["VATAmount18"] = -1 * $service["VATAmount18"];

                $service["AmountServices"] = -1 * ($service["AmountServices"] + $service["AmountOfPenalty"]);

                $service["pricecustomer"] = -1 * ($service["pricecustomer"] + $service["AmountOfPenalty"]);
                $service["amountclient"] = -1 * ($service["amountclient"] + $service["AmountOfPenalty"]);
            }

            $service["TravelTime"] = (int)$inservice["TravelTime"];
            $service["VendorFeeAmount"] = (int)$inservice["Profit"];


            $service["Place"] = trim($inservice["Place"]);
            $service["Wagon"] = trim($inservice["CarNum"]);

            $Secondeds = $this->DTV($inservice, "TravelPeoples");
            foreach ($Secondeds as $valueS) {
                $Seconded = [];

                $Name = $this->mb_ucwords(mb_strtolower($this->DTV($valueS, ["Name"])));
                $ars = explode(" ", $Name);

                $Seconded["FirstName"] = $this->DTV($ars, [1]);
                $Seconded["LastName"] = $this->DTV($ars, [0]);
                $Seconded["SurName"] = $this->DTV($ars, [2]);
                $Seconded["FirstNameLatin"] = "";
                $Seconded["LastNameLatin"] = "";
                $Seconded["SurNameLatin"] = "";


                $Seconded["DocumentNumber"] = $valueS["DocumentNumber"];
                $Seconded["BirthDay"] = $this->DTV($valueS, ["BirthDay"], "", "Y-m-d\TH:i:s");
                $Seconded["DocType"] = $valueS["DocType"];

                $Seconded["Name"] = trim($Seconded["LastNameLatin"] . " " . $Seconded["FirstNameLatin"] . " " . $Seconded["SurNameLatin"]);
                $Seconded["NameRus"] = trim($Seconded["LastName"] . " " . $Seconded["FirstName"] . " " . $Seconded["SurName"]);

                $service["Seconded"][] = $Seconded;
            }

            if ($service["TypeOfTicket"] == "R") {
                $service["TicketSales"] = $inservice["TicketNum"];
            }

            $services[] = $service;

            $addservices = [];
            $addservices = $this->v3addonservice($service);
            $services = array_merge($services, $addservices);
        }

        $jsonv3["services"] = $services;

        return $jsonv3;
    }

}
