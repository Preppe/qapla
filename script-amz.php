<?php
$trackingNumber = $_GET['trackingNumber'];

//check if tracking number is empty
if (empty($trackingNumber)) {
    $response = [
        'status' => 'error',
        'message' => 'Empty tracking number'
    ];
    echo json_encode($response);
    exit;
}

//get tracking info from amazon
$trackingInfo = getTrackingInfo($trackingNumber);
echo json_encode($trackingInfo);

function getTrackingInfo($trackingNumber){
    $url = 'https://'. 'track.amazon.it/api/'. 'tracker/'. $trackingNumber;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);

    $headers = [];
    $headers[] = 'Accept: application/json';

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);

    if(curl_errno($ch)){
        $error_msg = curl_error($ch);
    }

    curl_close($ch);

    //check if there is an error on curl
    if(isset($error_msg)){
        $response = [
            'status' => 'error',
            'message' => $error_msg
        ];
        return $response;
    }

    $result = json_decode($result, true);
    $progressTracker = json_decode($result['progressTracker'], true);
    
    //check if there is an error on tracking number
    if(empty($progressTracker['errors'])){
        $response = getJsonResponse($result);
    }else{
        $response = [
            'status' => 'error',
            'message' => 'Invalid tracking number'
        ];
    }

    return $response;
}

function getJsonResponse($result){
    $eventHistory = json_decode($result['eventHistory'], true);
    $eventHistory = $eventHistory['eventHistory'];
    $history = [];
    foreach($eventHistory as $event){
        $history[] = [
            'status' => mapStatusId($event['eventCode']),
            'statusId' => $event['eventCode'],
            'date' => $event['eventTime'],
            'location' => $event['location'],
            'description' => $event['eventMetadata']
        ];
    }
    $response = [
        'status' => 'success',
        'from' => [
            'name' => $result['shipperDetails']['shipperName'],
            'id' => $result['shipperDetails']['shipperId'],
        ],
        'history' => $history
    ];
    return $response;
}

function mapStatusId($statusId){
    switch ($statusId){
        case 'CreationConfirmed':
            return 'Etichetta creata';
        case 'PickupDone':
            return 'Presa in carico';
        case 'Received':
            return 'Arrivato al centro di smistamento';
        case 'Departed':
            return 'Partito dal centro di smistamento';
        case 'OutForDelivery':
            return 'Arrivato al centro di consegna';
        case 'Delivered':
            return 'Consegnato';
    }
}