<?php

require_once __DIR__ . '/common.php';

$dataDir = realpath(__DIR__ . '/../data/royalsociety');
$dataDirWiki = realpath(__DIR__ . '/../data/wikipedia');

$fellows = json_decode(file_get_contents($dataDir . '/fellows.json'), true);

function analyzeWikipediaArticle($title)
{
    global $dataDirWiki;
    global $nameTranslations;
    
    if(isset($nameTranslations[$title])){
        $title = $nameTranslations[$title];
    }    
    
    $wikiTitle = str_replace(' ', '_', str_replace('  ', ' ',$title));    
    $wikiFile  = $dataDirWiki . '/' . $wikiTitle;
    
    if(!is_file($wikiFile)){
        echo 'ERROR: ' . $wikiFile . "\n";
        return;
        die('ERROR'); // should always exist
    }
    
    $content = file_get_contents($wikiFile);
    
    $result = array(
        'title' => $wikiTitle
    );
    
    if($content === 'DOES NOT EXIST'){
        $result['exists'] = false;
        return $result;
    } else {
        $result['exists'] = true;
    }
    
    $result['size'] = round(strlen($content)/1000,1).'k';
    
    if(stripos($content, '#redirect') !== false){        
        preg_match('/\#redirect(.*)\[\[(.*)\]\]/iSU', $content, $match);
                
        $result['redirect'] = trim($match[2]);
    } else {
        $result['redirect'] = false;
    }
    
    $result['infobox'] = stripos($content, '{{Infobox') !== false;
    $result['disamb'] = stripos($content, '{{disamb') !== false;
    $result['infobox_scientist'] = stripos($content, '{{Infobox scientist') !== false;
    $result['frs_mentioned'] = stripos($content, 'Fellow of the Royal Society') !== false || stripos($content, 'Foreign Member of the Royal Society') !== false;
    $result['cat_fellows'] = stripos($content, 'Category:Fellows of the Royal Society') !== false
                            || stripos($content, 'Category:Foreign Members of the Royal Society') !== false;
    
    return $result;
}

/**
 * Print row of the wikitable. resultMiddle is printed only if corresponding article exists.
 * 
 * @param array $fellow Fellow data as fetched from royalsociety.org
 * @param array $result Result of analyzeWikipediaArticle() (middle name not included)
 * @param type $resultMiddle Result of analyzeWikipediaArticle() (middle name included)
 */
function addRow($fellow, $result, $resultMiddle = null)
{    
    $colCount = 5;
    
    echo "|-\n";
    echo '|' . '[[' . $result['title'] . '|' . str_replace('_',' ',$result['title']) . ']]' . "\n";
    
    foreach(array('exists', 'size', 'cat_fellows','infobox_scientist', 'redirect') as $key){            
        echo '|';
        
        if($result['exists'] || $key === 'exists'){
            switch($key){
                case 'size':
                    echo $result[$key];
                    break;
                case 'redirect':
                    echo $result[$key] ? '[[' . $result[$key] . ']]' : '';
                    break;
                case 'infobox_scientist':
                    if($result[$key]){
                        echo '{{aye}}';
                    } else if($result['infobox']){
                        echo '{{bang}}';
                    } else {
                        echo '{{nay}}';
                    }                    
                    break;
                default:
                    echo ($result[$key] ? '{{aye}}': '{{nay}}');
            };           
        }
        
        echo "\n";
    }
    
    echo '|' . $fellow['ElectedYear'] . "\n";
    echo '|' . $fellow['Honours'] . "\n";
    echo '|' . $fellow['Position'] . "\n";
    echo '|' . $fellow['InstitutionName'] . "\n";
    echo '|' . '[https://royalsociety.org' . $fellow['FellowProfileUrl'] . ' profile]' . "\n";
    echo '|' . '[http://176.58.102.28/generate_wikipedia_article.php?name=' . urlencode($result['title']) . '&year=' . $fellow['ElectedYear'] . ' sketch]' . "\n";
    
    if($resultMiddle && $resultMiddle['exists']){
        addRow($fellow, $resultMiddle);
    }
}

echo '{| class="wikitable sortable"' . "\n";
echo "|-\n";
echo "! Name\n";
echo "! Page\n";
echo "! Size\n";
echo "! Category\n";
echo "! Infobox\n";
echo "! Redirect\n";
echo "! Year\n";
echo "! Honours\n";
echo "! Position\n";
echo "! Institution\n";
echo "! Bio\n";
echo "! Sketch\n";

foreach($fellows as $no => $fellow){                
    $fellow['FirstName'] = str_replace(array('(',')','FRS'), '', $fellow['FirstName']);    
    $fellow['MiddleName'] = str_replace(array('(',')','FRS'), '', $fellow['MiddleName']);
    $fellow['LastName'] = str_replace(array('(',')','FRS'), '', $fellow['LastName']);
    
    $firstLastName = trim($fellow['FirstName'] . ' ' . $fellow['LastName']);
    $firstMiddleLastName = trim($fellow['FirstName'] . ($fellow['MiddleName']?(' ' . $fellow['MiddleName']  . ' '):' ') . $fellow['LastName']);
   
    $result = analyzeWikipediaArticle($firstLastName);
    
    $middle = false;
    
    if($firstMiddleLastName != $firstLastName){
        $middle = analyzeWikipediaArticle($firstMiddleLastName);
    }
    
    addRow($fellow, $result, $middle);
}

echo '|}';
