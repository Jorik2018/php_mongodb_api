<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept,Authorization');

$MONGO_URL='mongodb+srv://admin:A1_mongodb@cluster0.go2xt.mongodb.net/?retryWrites=true&w=majority';
$host="http://localhost:5000/api/oauth";
$OAUTH_CLIENT_ID="HuGZ7Yqh2WV1PJvBxodx6ary";
$client_secret="bBvCIddRyj42LeXHD8zG7IxgaVS65TC0aJ1jiiBi48kqAX1g";