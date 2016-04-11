<?php

require_once __DIR__ . '/common.php';

$dataDir = realpath(__DIR__ . '/../data/royalsociety');
$dataDirWiki = realpath(__DIR__ . '/../data/wikipedia');

function fetchWikipediaArticle($title)
{
    global $dataDirWiki;
    global $nameTranslations;

    if(isset($nameTranslations[$title])){
        $title = $nameTranslations[$title];
    }
    
    $wikiTitle = str_replace(' ', '_', str_replace(['  ', '(', ')'], [' '],$title));    
    $wikiFile  = $dataDirWiki . '/' . $wikiTitle;       
    
    if(!is_file($wikiFile) || !filesize($wikiFile)){
        $url = 'https://en.wikipedia.org/w/api.php?format=json&action=query&prop=revisions&rvprop=content&titles=' . $wikiTitle;
        $response = json_decode(file_get_contents($url), true);
        
        $pages = $response['query']['pages'];
        
        if(count($pages) !== 1){
            die('invalid pages' . print_r($pages, true));
        }
        
        foreach($pages as $key => $p){
            if($key == '-1'){
                $content = 'DOES NOT EXIST';
            } else {
                $content = $p['revisions'][0]['*'];
            }
        }

        file_put_contents($wikiFile, $content);
        sleep(1);                
    }            
    
    return file_get_contents($wikiFile);
}

$fellows = json_decode(file_get_contents($dataDir . '/fellows.json'), true);

foreach($fellows as $no => $fellow){
    $firstLastName = trim($fellow['FirstName'] . ' ' . $fellow['LastName']);
    $firstMiddleLastName = trim($fellow['FirstName'] . ($fellow['MiddleName']?(' ' . $fellow['MiddleName']  . ' '):' ') . $fellow['LastName']);
   
    fetchWikipediaArticle($firstLastName);
    fetchWikipediaArticle($firstMiddleLastName);
}

echo 'FINISHED';

