<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept,Authorization');

$file_handle = fopen('.env', 'r');
function get_all_lines($file_handle) { 
    while (!feof($file_handle)) {
        yield fgets($file_handle);
    }
}
foreach (get_all_lines($file_handle) as $line) {
    $v=explode('=',$line,2);
    ${trim($v[0])}=trim($v[1]);
}
fclose($file_handle);