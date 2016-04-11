<?php

require_once __DIR__ . '/common.php';

$dataDir = realpath(__DIR__ . '/../data/royalsociety');

$fellows = array();

$pageSize = 100;
$page = 0;

do {    
    $page++;
    $offset = ($page-1)*$pageSize;
    
    sleep(1); 
    
    $url = 'https://royalsociety.org/api/Fellows/Search';
    $data = array('SearchType' => 'fellows', 'Sort' => 'data', 'StartIndex' => $offset, 'PageSize' => $pageSize);

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ),
    );

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $decoded = json_decode($result, true);

    if ($result === FALSE || !$decoded) { 
        die('ERROR');
    }
    
    $fellows = array_merge($fellows, $decoded['Results']);
} while(!empty($decoded['Results']));

file_put_contents($dataDir . '/fellows.json', json_encode($fellows));
echo 'Saved ' . count($fellows) . ' fellows.';
