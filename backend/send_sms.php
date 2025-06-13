<?php
// send_sms.php

function sendSMS($phoneNumber, $message) {
    $username = "sandbox";
    $apiKey   = "atsk_5ff3399535d75c850c2524270e7a5650a31901655a420699d1fad1914fe99cec32e9e681";

    $url = "https://api.africastalking.com/version1/messaging";

    $data = http_build_query([
        'username' => $username,
        'to' => $phoneNumber,
        'message' => $message,
    ]);

    $headers = [
        "apikey: $apiKey",
        "Content-Type: application/x-www-form-urlencoded",
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
?>
