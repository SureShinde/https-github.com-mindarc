<?php

$username = "19968251-851b-42c4-88ed-698d0bfbffb7";

$password = "Newiurytiusfhj276874";

$account  = "0007429013";                                                             


echo "<pre>";

/*$ch = curl_init('https://digitalapi.auspost.com.au/shipping/v1/accounts/' . $account); 

curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                     

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                                                                

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      

curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Account-Number: ' . $account));

$result = curl_exec($ch);

$data = json_decode($result);

print_r($data);*/


$username = "19968251-851b-42c4-88ed-698d0bfbffb7";

$password = "Newiurytiusfhj276874";

$account  = "0007429013";

$request_body = '{"from":{"postcode":"2168"},"to":{"postcode":"77550"},"items":[{"length":10,"width":10,"height":10,"weight":1.5}]}';
//$request_body = '{"from":{"postcode":"2168"},"to":{"postcode":"77550"},"items":[{"length":13.5,"width":27,"height":37,"weight":2.5}]}';

//$request_body = array('to'=>array('postcode'=>4215),'items'=>array(array('length'=>10,'width'=>10,'height'=>10,'weight'=>20),array('length'=>10,'width'=>10,'height'=>10,'weight'=>10)));
//$request_body = json_encode($request_body);
//exit;

$ch = curl_init('https://digitalapi.auspost.com.au/shipping/v1/prices/items'); 

curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);

curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                                                                

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      

curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Account-Number: ' . $account));

$result = curl_exec($ch);

$data = json_decode($result);

echo "<pre>";

print_r($data);

