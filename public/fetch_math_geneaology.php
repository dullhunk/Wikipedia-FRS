<?php

namespace mathGeneaology;

require_once __DIR__ . '/common.php';

function getSearchResults($firstName, $middleName, $lastName)
{
    $dataDir = realpath(__DIR__ . '/../data/geneaology');
    $key = md5($firstName . $middleName . $lastName);

    if(!is_file($dataDir . '/' . $key)){
            $url = 'http://www.genealogy.ams.org/query-prep.php';

            $data = array(
                    'given_name' => $firstName,
                    'family_name' => $lastName
            );

            $options = array(
                    'http' => array(
                            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                            'method'  => 'POST',
                            'content' => http_build_query($data),
                    ),
            );

            $context  = stream_context_create($options);
            $html = file_get_contents($url, false, $context);		

            file_put_contents($dataDir . '/' . $key, $html);
    }

    $html = file_get_contents($dataDir . '/' . $key);
    preg_match_all('#\<tr\>\<td\>\<a href\=\"id\.php\?id\=(.*)"\>(.+)</a></td>\s*\<td\>(.*)\<\/td\>\s*\<td\>(.*)\<\/td\>\<\/tr\>#iU', $html, $matches);

    $result = array();

    for($i=0;$i<count($matches[0]);$i++){
            $result[] = array(
                    'id' => $matches[1][$i],
                    'name' => $matches[2][$i],
                    'school' => $matches[3][$i],
                    'year' => $matches[4][$i]
            );
    }

    return $result;
}

function getResult($id)
{
    $dataDir = realpath(__DIR__ . '/../data/geneaology');
    $file = $dataDir . '/' . $id;

    if(!is_file($file)){
        file_put_contents($file, file_get_contents('http://www.genealogy.ams.org/id.php?id=' . $id));
    }

    $html = file_get_contents($file);
    preg_match('@<span style="margin-right: 0.5em">(.*) <span style="color:\s*#006633; margin-left: 0.5em">(.*)</span> (.*)</span>@isU', $html, $matches);

    $data = array(
            'degree' => trim($matches[1]),
            'degree_school' => trim($matches[2]),
            'degree_year' => trim($matches[3]),
    );

    preg_match('@<span style="font-style:italic" id="thesisTitle">(.*)</span>@isU', $html, $matches);	
    $data['thesis'] = trim($matches[1]);

    preg_match_all('#Advisor(.*): <a href="id\.php\?id=(.*)">(.*)</a>#isU', $html, $matches);	

    $advisors = array();
    for($i=0;$i<count($matches[0]);$i++){
        if(trim($matches[3][$i])){
                $advisors[] = trim($matches[3][$i]);
        }
    }

    $data['mathgenid'] = $id;
    $data['advisors'] = $advisors;

    return $data;
}