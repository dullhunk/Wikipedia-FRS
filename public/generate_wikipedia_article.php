<?php

require_once __DIR__ . '/common.php';
require_once 'fetch_math_geneaology.php';
require_once 'fetch_election_certificate.php';

$name = urldecode($_GET['name']);
$year = urldecode($_GET['year']);

$dataDir = realpath(__DIR__ . '/../data/royalsociety');

$fellows = json_decode(file_get_contents($dataDir . '/fellows.json'), true);

$theFellow = false;

$name = str_replace('_', ' ', $name);

$extraData = array();

foreach($fellows as $no => $fellow){
    if($fellow['ElectedYear'] !== $year){
        continue;
    }       
    
    $firstLastName = trim($fellow['FirstName'] . ' ' . $fellow['LastName']);
    $firstMiddleLastName = trim($fellow['FirstName'] . ($fellow['MiddleName']?(' ' . $fellow['MiddleName']  . ' '):' ') . $fellow['LastName']);
    
    if($name === $firstLastName || $name === $firstMiddleLastName){
        $theFellow = $fellow;
        break;
    }
}

$genResults = mathGeneaology\getSearchResults($fellow['FirstName'], $fellow['MiddleName'], $fellow['LastName']);

$rsCollectionsResults = rsCollections\getSearchResults($fellow['FirstName'], $fellow['MiddleName'], $fellow['LastName'], $fellow['ElectedYear']);

if(isset($_GET['mathgen']) && $_GET['mathgen']){
    $extraData	= array_merge($extraData, mathGeneaology\getResult($_GET['mathgen']));
}

$certificate = '';

if(isset($_GET['rs_collections']) && $_GET['rs_collections']){
    $extraData	= array_merge($extraData, rsCollections\getResult($_GET['rs_collections']));
    
    $certificate .= 'His certificate of election reads: ';
    
    $citUrl = 'https://collections.royalsociety.org/DServe.exe?dsqIni=Dserve.ini&dsqApp=Archive&dsqCmd=Show.tcl&dsqDb=Catalog&dsqPos=0&dsqSearch=%28RefNo%3D%27' . urlencode($extraData['citationRef']) . '%27%29';
    $certificate .= '{{centered pull quote|' . $extraData['frsCitation'] . '&lt;ref name="frsCitation"&gt;{{cite web |url=' . $citUrl . ' |title = ' . $extraData['citationRef'] . ': ' . $extraData['citationTitle'] . '|publisher=[[The Royal Society]]|accessdate=' . date('j F Y') . '}}&lt;/ref&gt;}}';
}

if(!$theFellow){
    die('not found');
}

$honors = str_replace(' ', '|', $theFellow['Honours']);

if(strpos($honors, 'ForMemRS') !== false){
    $foreign = true;
    $awards = "{{Plainlist|
* [[Foreign Member of the Royal Society|ForMemRS]] {{small|({$theFellow['ElectedYear']})}}
}}";
    $award = 'Foreign Member of the Royal Society (ForMemRS)';
    $category = 'Foreign Members of the Royal Society';
} else if(strpos($honors, 'FRS') !== false){
    $foreign = false;
    $awards = "{{Plainlist|
* [[Fellow of the Royal Society|FRS]] {{small|({$theFellow['ElectedYear']})}}
}}";    
    $award = 'Fellow of the Royal Society (FRS)';
    $category = 'Fellows of the Royal Society';
} else {
    die('neither foreign nor frs');
}

echo '<pre style="white-space: pre-wrap;">';

$position = 'He is ...';

if($theFellow['Position'] && $theFellow['InstitutionName']){
    $position = "He is (a/an/the) {$theFellow['Position']} at the {$theFellow['InstitutionName']}.&lt;ref name=\"FRS\" /&gt;";
}

$workplaces = '';

if($theFellow['InstitutionName']){
    $workplaces = "{{Plainlist|
* {$theFellow['InstitutionName']}
}}";
}

$almamater = '';
$education = '';
$thesisTitle = '';
$thesisYear = '';
if(isset($extraData['degree_school']) && $extraData['degree_school']){
	$almamater = '[[' . $extraData['degree_school'] . ']]';
	
	if(isset($extraData['degree']) && $extraData['degree']){
		$almamater .= ' (' . $extraData['degree'] . ')';		
	} else {
		$extraData['degree'] = 'PhD';		
	}
	
	$almamater .= "&lt;ref name=\"GNP\"&gt;{{mathgenealogy|id={$extraData['mathgenid']}|name={$fellow['FirstName']} {$fellow['LastName']}}}&lt;/ref&gt;";
	
	$education = "\n==Education==\n{$fellow['LastName']} earned his {$extraData['degree']} from the [[{$extraData['degree_school']}]]";
	
	if(isset($extraData['degree_year']) && $extraData['degree_year']){
		$education .= ' in ' . $extraData['degree_year'];
	}
	
	if(isset($extraData['advisors']) && $extraData['advisors']){
		$education .= ', under the supervision of ' . implode(' and ', $extraData['advisors']);
	}
	
	$education .= ".";
	
	if(isset($extraData['thesis']) && $extraData['thesis']){
		$thesisTitle = "\n|thesis_title  = " . $extraData['thesis'];
		if(isset($extraData['degree_year']) && $extraData['degree_year']){
			$thesisYear = "\n|thesis_year  = {$extraData['degree_year']}";
		}
		
		$education .= ' His thesis was entitled \'\'' . $extraData['thesis'] . '\'\'.';
	}
	
	$education .= '&lt;ref name="GNP" /&gt;';
	
	$education .= "\n";
}

echo str_replace('Ph.D.','PhD', trim("
    
{{Infobox scientist
| name              = $firstLastName
| image             = 
| image_size        = 
| caption           = 
| birth_date        = {{birth date and age|df=yes|YYYY|MM|DD}}<ref name=\"whoswho\" />
| birth_place       = 
| death_date        = 
| death_place       = 
| known_for = 
| nationality       = 
| fields            =   {{plainlist |
* (fill in)
  }}
| workplaces        = $workplaces
|alma_mater  = $almamater $thesisTitle $thesisYear
|awards =  $awards
| doctoral_advisor" . ((isset($extraData['advisors'])&&count($extraData['advisors'])>1)?'s':'') . "  = " . ((isset($extraData['advisors'])&&$extraData['advisors'])?'{{Plainlist|'."\n* ".implode("\n* ",$extraData['advisors'])."\n}}":'') . "
| website = 
}}

'''$firstMiddleLastName''' {{post-nominals|size=100%|country=GBR|$honors}} is a British(check) (scientist). $position
$education
== Research ==
(if suitable, use the biography from https://royalsociety.org{$theFellow['FellowProfileUrl']})&lt;ref name=\"FRS\" /&gt;

== Awards and honours ==
{$theFellow['LastName']} was elected a [[List of Fellows of the Royal Society elected in {$theFellow['ElectedYear']}|$award in {$theFellow['ElectedYear']}]].&lt;ref name=\"FRS\"&gt;{{cite web|url=https://royalsociety.org{$theFellow['FellowProfileUrl']}|title=$firstLastName|publisher=[[Royal Society]]|location=London}} One or more of the preceding sentences may incorporate text from the royalsociety.org website where \"all text published under the heading 'Biography' on Fellow profile pages is available under [[Creative Commons license|Creative Commons Attribution 4.0 International License]].\" {{Wayback|url=https://royalsociety.org/about-us/terms-conditions-policies/|title=Royal Society Terms, conditions and policies|date=20160220093712}}&lt;/ref&gt; $certificate

== References ==
{{reflist}}

{{Authority control" . ((isset($extraData['mathgenid'])&&$extraData['mathgenid'])?'|MGP='.$extraData['mathgenid']:'') . "}}
{{DEFAULTSORT:{$theFellow['LastName']}, {$theFellow['FirstName']}}}
[[Category:Living people]]
[[Category:$category]]

{{Scientist-stub}}

"));

function getUrl($params)
{
    $params = array_merge(array(
        'name' => $_GET['name'],
        'year' => $_GET['year'],
        'mathgen' => isset($_GET['mathgen'])?$_GET['mathgen']:'',
        'rs_collections' => isset($_GET['rs_collections'])?$_GET['rs_collections']:''
    ), $params);

    $str = '?';
    
    foreach($params as $name => $value){
        $str .= $name . '=' . $value .= '&';
    }
    
    return $str;
}

echo '</pre>';

echo '<hr/>';
echo '<div style="-webkit-touch-callout: none;-webkit-user-select: none;-khtml-user-select: none;-moz-user-select: none;-ms-user-select: none; user-select: none;">';

echo '<h2>FRS Election Certificates Search</h2>';
echo '<table style="width:600px;text-align:left">';
echo '<thead>';
echo '<tr>';
echo '<th>Select</th>';
echo '<th>Details</th>';
echo '<th>Ref No</th>';
echo '<th>Date</th>';
echo '</tr>';	
echo '</thead>';

foreach($rsCollectionsResults as $result){
	echo '<tr>';
	echo '<td><a href="' . getUrl(array('rs_collections' => urlencode($result['url']))) . '">' . 'Select' . '</a></td>';
	echo '<td><a target="_blank" href="' . $result['url'] . '">' . $result['title'] . '</a></td>';
	echo '<td>' . $result['ref'] . '</td>';
        echo '<td>' . $result['date'] . '</td>';
	echo '</tr>';
}
echo '</table>';

echo '<h2>Math Geneaology Search</h2>';
echo '<table style="width:600px;text-align:left">';
echo '<thead>';
echo '<tr>';
echo '<th>Select</th>';
echo '<th>See profile</th>';
echo '<th>PhD School</th>';
echo '<th>PhD Year</th>';
echo '</tr>';	
echo '</thead>';

foreach($genResults as $result){
	echo '<tr>';
	echo '<td><a href="' . getUrl(array('mathgen' => $result['id'])) . '">Select</a></td>';
	echo '<td><a target="_blank" href="http://www.genealogy.ams.org/id.php?id=' . $result['id'] . '">' . $result['name'] . '</a></td>';
	echo '<td>' . $result['school'] . '</td>';
	echo '<td>' . $result['year'] . '</td>';
	echo '</tr>';
}

echo '</table>';
echo '</div>';

echo '<h3>Who\'s Who reference</h3>';
echo '<pre>';
echo '&lt;ref name="whoswho"&gt;{{Who\'s Who | surname = ' . strtoupper($fellow['LastName']) . ' | othernames =  | id =  | volume = 2016 | edition = online [[Oxford University Press]]|location=Oxford}} {{subscription required}}&lt;/ref&gt;';
echo '</pre>';

echo '<h3>Edit description</h3>';
echo '<pre>The article incorporates [[WP:COMPLIC|free content]] from royalsociety.org.</pre>';







