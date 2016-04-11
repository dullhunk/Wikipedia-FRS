<?php

require_once __DIR__ . '/common.php';

$dataDir = realpath(__DIR__ . '/../data/royalsociety');

$fellows = json_decode(file_get_contents($dataDir . '/fellows.json'), true);

foreach($fellows as $no => $fellow){
    $profileUrl = 'https://royalsociety.org/' . $fellow['FellowProfileUrl'];        
    
    // not implemented
}