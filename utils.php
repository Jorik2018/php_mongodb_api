<?php


$manager = new MongoDB\Driver\Manager($MONGO_URL);

function str_replace_first($search, $replace, $subject){
    $search = '/'.preg_quote($search, '/').'/';
    return preg_replace($search, $replace, $subject, 1);
}

function clog($msg){
    $fh = fopen("log.txt", 'a') or die("can't open file");
    fwrite($fh, "$msg\n");
    fclose($fh);
}

function getCurrentUser($token){
    $user=$token?(array)json_decode(base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $token)[1]))),true):array();
    /*if(microtime(true)>$user['exp']){
        header('Content-type: application/json');
        http_response_code(401);
		die(json_encode(['message' => 'Unauthorized']));
    }*/
    return $user;
}

function getAuthorizationHeader(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    }
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

function getBearerToken() {
    $headers = getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function dashesToCamelCase($string, $capitalizeFirstCharacter = false){
    $str = str_replace('-', '', ucwords($string, '-'));
    if (!$capitalizeFirstCharacter) {
        $str = lcfirst($str);
    }
    return $str;
}

$url=$_SERVER['REQUEST_URI']=str_replace_first('/api/minsa', '', $_SERVER['REQUEST_URI']);

$url=explode( '/', $url); 

clog(json_encode($url));