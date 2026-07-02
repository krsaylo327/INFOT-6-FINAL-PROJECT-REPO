<?php

header('Content-Type: application/json');

// Load .env file
$env = parse_ini_file(__DIR__ . '/../.env');

$apiKey = $env['WEATHER_API_KEY'];
$city = $env['CITY'];

$url = "https://api.openweathermap.org/data/2.5/weather?q=" .
        urlencode($city) .
        "&appid=$apiKey&units=metric";

$response = file_get_contents($url);

echo $response;

?>