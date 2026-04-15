<?php

$curl = curl_init();

curl_setopt_array($curl, [
	CURLOPT_URL => "https://youtube-search-and-download.p.rapidapi.com/channel/search?id=UCXoJ8kY9zpLBEz-8saaT3ew&query=news&next=4qmFsgJ2EhhVQ2hQdlE4aGZyU1cxRUFidEJXamlzMGcaJkNCMFNCbk5sWVhKamFEQUJPQUZnQVdvQWVnTkhRalM0QVFBJTNEWgZhbmltYWyaAilicm93c2UtZmVlZFVDaFB2UThoZnJTVzFFQWJ0QldqaXMwZ3NlYXJjaA%253D%253D",
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => [
		"Content-Type: application/json",
		"x-rapidapi-host: youtube-search-and-download.p.rapidapi.com",
		"x-rapidapi-key: 69425703f2msh1a15f89df904226p10ebc6jsn8ae15575cab0"
	],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
	echo "cURL Error #:" . $err;
} else {
	echo $response;
}