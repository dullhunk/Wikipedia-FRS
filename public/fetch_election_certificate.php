<?php

namespace rsCollections;

require_once __DIR__ . '/common.php';

function getSearchResults($firstName, $middleName, $lastName, $electionYear)
{
	$dataDir = realpath(__DIR__ . '/../data/rs_collections');	
	
        $result = array();

        $origYear = $electionYear;
        
        foreach(range($electionYear-1,$electionYear+2) as $electionYear){
                        
            $key = md5($firstName . $middleName . $lastName . (string)$electionYear);

            if(true||!is_file($dataDir . '/' . $key)){
                if($electionYear === $origYear + 2 && count($result) === 0){
                    $url = 'https://collections.royalsociety.org/?dsqIni=Dserve.ini&dsqApp=Archive&dsqDb=Catalog&dsqCmd=Overview.tcl&dsqSearch=((text)%3d%27' . urlencode($lastName) . '%27)and((text)%3d%27' . urlencode($firstName) . '%27)and(RefNo%3d%27EC*%27)';                   
                } else {
                    $url = 'https://collections.royalsociety.org/?dsqIni=Dserve.ini&dsqApp=Archive&dsqDb=Catalog&dsqCmd=Overview.tcl&dsqSearch=((text)%3d%27' . urlencode($lastName) . '%27)and(Date%3d%27' . $electionYear . '%27)and(RefNo%3d%27EC*%27)';
                }
                
                file_put_contents($dataDir . '/' . $key, file_get_contents($url));
            }

            $html = file_get_contents($dataDir . '/' . $key);

            preg_match_all('#\<td class\=\"OverviewKey\"\>(.*)<\/tr\>#isU', $html, $matches);

            for($i=0;$i<count($matches[0]);$i++){
                $html = $matches[0][$i];

                preg_match('#<a href="(.*)" class#iSU', $html, $url);
                preg_match('#<td class="OverviewCell OverviewCellRefNo">(.*)\n</td>#iSU', $html, $ref);
                preg_match('#<td class="OverviewCell OverviewCellTitle">(.*)\n</td>#iSU', $html, $title);
                preg_match('#<td class="OverviewCell OverviewCellDate">(.*)\n</td>#iSU', $html, $date);            

                $result[] = array(
                    'url' => 'https://collections.royalsociety.org' . trim($url[1]),
                    'ref' => trim(strip_tags(trim($ref[1]))),
                    'title' => trim(strip_tags(trim($title[1]))),
                    'date' => trim(strip_tags(trim($date[1])))
                );            
            }        
        }
	
	return $result;
}

function getResult($url)
{
    $dataDir = realpath(__DIR__ . '/../data/rs_collections');
    $file = $dataDir . '/' . md5($url);

    $url = str_replace('&amp;', '&', $url);

    if(!is_file($file)){
        file_put_contents($file, file_get_contents($url));
    }

    $html = file_get_contents($file);

    preg_match('#<td class="Citation">(.*)\n</td>#iSU', $html, $citation);
    preg_match('#<td class="RefNo">(.*)</td>#iSU', str_replace(array("\n","\r"),'',$html), $citationRef);
    preg_match('#<td class="Title">(.*)\n</td>#iSU', $html, $citationTitle);

    $data = array(
        'frsCitation' => trim($citation[1]),
        'citationRef' => trim(strip_tags(trim($citationRef[1]))),
        'citationTitle' => trim($citationTitle[1])
    );

    return $data;
}