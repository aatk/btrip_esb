<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 27.01.2018
 * Time: 1:31
 */

class ex_classlite extends ex_class
{

    public function __construct($connectionInfo = null, $debug = false)
    {
        parent::__construct($connectionInfo, $debug);
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
