<?php

require __DIR__ . '/Stemmer.php';

/***************** OUTPUT TERMS ********************/

/* CLASSES FOR OUTPUT TERMS ARRAY */
class Term {
    public $name;
    public $count;
    public $index;
}

/* FUNCTIONS FOR OUTPUT TERMS ARRAY */
function get_num_of_words($string) {
    // $string = preg_replace('/\s+/', ' ', trim($string));
    $words = explode(" ", $string);

    return count($words);
}

function get_num_of_article($key, $documents) {
	$documentNumber = 1;
	foreach ($documents as $document) {
		$wordsAmount = $wordsAmount + get_num_of_words($document);
		if ($wordsAmount >= $key) {
			break;
		}
		else {
			$documentNumber++;
		}
	}

	return $documentNumber;
}

/***************** OUTPUT ARTICLES ********************/

/* CLASSES FOR OUTPUT FILE */
class Article {
    public $link;
    public $title;
    public $abstract;
    public $keywords;
}

class ChildStructure {
	public $original;
	public $porter;
	public $mystem;
}

class Root {
    public $year;
    public $url;
    public $articles = array();
}

/* FUNCTIONS FOR DATA OBTAINING */
function getTitleByLink($doc) {
	$xpath = new DOMXpath($doc);
	$element = $xpath->query("// td[contains(@valign, 'top')] / span[contains(@class, 'red')] / font");

	return $element[0]->nodeValue;
}

function getAbstractByLink($doc) {
	$xpath = new DOMXpath($doc);
	$element = $xpath->query("// b[contains(.,'Аннотация:')] / following-sibling::node()[following-sibling::br[count(// b[contains(.,'Аннотация:')] / following-sibling::br)]]");

	$str = "";
	foreach ($element as $el) {
		$str = $str . $el->nodeValue;
	}

	$str = preg_replace('/\t/', '', $str);
	$str = preg_replace('/\n/', '', $str);

	return $str;
}

function getKeywordsByLink($doc) {
	$xpath = new DOMXpath($doc);
	$element = $xpath->query("// b[contains(.,'Ключевые слова:')] / following-sibling::i");
	return $element[0]->nodeValue;
}

function mystem($q) {
  $result = exec('echo "'.$q.'" | ./mystem -l -d');
  return $result;
}


/************** MAIN PART (EXTRACT INFORMATION FROM WEBSITE) ***************/

$url = 'http://www.mathnet.ru/php/archive.phtml?jrnid=uzku&wshow=issue&bshow=contents&series=0&year=2014&volume=156&issue=1&option_lang=rus&bookID=1517';

libxml_use_internal_errors(true); 
$doc = new DOMDocument();
$doc->loadHTMLFile($url);

$xpath = new DOMXpath($doc);

$elements = $xpath->query("// td[@colspan='2'] / a[contains(@class, 'SLink')]");
$index = 0;

$root = new Root();
$root->year = "2014";
$root->url = $url;

foreach ($elements as $element) {
	$root->articles[$index] = new Article();
	$root->articles[$index]->link = "http://www.mathnet.ru" . $element->getAttribute("href");

	$pageDoc = new DOMDocument();
	$pageDoc->loadHTMLFile($root->articles[$index]->link);
	$root->articles[$index]->title = new ChildStructure();
	$root->articles[$index]->title->original = getTitleByLink($pageDoc);
	$root->articles[$index]->abstract = new ChildStructure();
	$root->articles[$index]->abstract->original = getAbstractByLink($pageDoc);

	/* Title stemmer */

	/* USING YANDEX MYSTEM */
	$mystemText = mystem($root->articles[$index]->title->original);
	$mystemText = str_replace("}{", " ", $mystemText);
	$mystemText = str_replace("{", "", $mystemText);
	$mystemText = str_replace("}", "", $mystemText);
	$root->articles[$index]->title->mystem = $mystemText;

	/* USING PORTER STEMMER */
	$stemmer = new \NXP\Stemmer();
	$stemmed = [];
	foreach (explode(' ', $root->articles[$index]->title->original) as $word) {
	    $stemmed[] = $stemmer->getWordBase($word);
	}
	$porterResult = implode(' ', $stemmed);
	$root->articles[$index]->title->porter = $porterResult;


	$textForStemmer = preg_replace('/\$[^$]*\$/', '', $root->articles[$index]->abstract->original); // Remove text between $ because of stemmer error

	/* USING YANDEX MYSTEM */
	$mystemText = mystem($textForStemmer);
	$mystemText = str_replace("}{", " ", $mystemText);
	$mystemText = str_replace("{", "", $mystemText);
	$mystemText = str_replace("}", "", $mystemText);
	$root->articles[$index]->abstract->mystem = $mystemText;

	/* USING PORTER STEMMER */
	$stemmer = new \NXP\Stemmer();
	$stemmed = [];
	foreach (explode(' ', $textForStemmer) as $word) {
	    $stemmed[] = $stemmer->getWordBase($word);
	}
	$porterResult = implode(' ', $stemmed);
	$root->articles[$index]->abstract->porter = $porterResult;

	if (getKeywordsByLink($pageDoc)==null) $root->articles[$index]->keywords = "";
	else $root->articles[$index]->keywords = getKeywordsByLink($pageDoc);

	$index++;
}

/* GENERATE ARTICLES JSON FILE */

$json = json_encode($root, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo $json;

file_put_contents('/Users/denis/Documents/Search Class/myfile.json', $json);


/************** MAIN PART (OUTPUT TERMS INDEXES) ***************/

/* PORTER TITLE */

$strings = array();
foreach ($root->articles as $article) {
	array_push($strings, $article->title->porter);
}

$commonString = " ";
foreach ($strings as $key => $string) {
	$strings[$key] = mb_strtolower($strings[$key]);
	$strings[$key] = preg_replace('/\$[^$]*\$/', '', $strings[$key]);
	$strings[$key] = str_replace('”', '', $strings[$key]);
	$strings[$key] = str_replace('“', '', $strings[$key]);
	$strings[$key] = str_replace(':', '', $strings[$key]);
	$strings[$key] = str_replace('.', '', $strings[$key]);
	$strings[$key] = str_replace(',', '', $strings[$key]);
	$strings[$key] = str_replace(';', '', $strings[$key]);
	$strings[$key] = str_replace('|', '', $strings[$key]);
	$strings[$key] = str_replace('[', '', $strings[$key]);
	$strings[$key] = str_replace(']', '', $strings[$key]);
	$strings[$key] = str_replace('(', '', $strings[$key]);
	$strings[$key] = str_replace(')', '', $strings[$key]);
	$strings[$key] = str_replace(' ', ' ', $strings[$key]);

	$commonString = $commonString . $strings[$key] . " ";
}

$words = explode(" ", $commonString);

for ($index=0;$index<count($words);$index++) {
	$word = $words[$index];
	if ($word != "") {
		$amountOfWord = substr_count($commonString, " " . $word . " ");
	
		$titleResult->words[$index]->text = $word;
		$indexes = array();

		for ($i=0;$i<$amountOfWord;$i++) {
			$key = array_search($word, $words);
			$words[$key] = "";
			array_push($indexes, get_num_of_article($key, $strings));
		}

		$indexes = array_unique($indexes);
		$newIndexes = array_values($indexes);

		$titleResult->words[$index]->count = count($newIndexes);
		$titleResult->words[$index]->index = $newIndexes;
	}

}

usort($titleResult->words, function($a, $b) { // Sort the array using a user defined function
    return $a->text < $b->text ? -1 : 1; //Compare the scores
});   


/* PORTER ABSTRACT */

$strings = array();
foreach ($root->articles as $article) {
	array_push($strings, $article->abstract->porter);
}

$commonString = " ";
foreach ($strings as $key => $string) {
	$strings[$key] = mb_strtolower($strings[$key]);
	$strings[$key] = preg_replace('/\$[^$]*\$/', '', $strings[$key]);
	$strings[$key] = str_replace('”', '', $strings[$key]);
	$strings[$key] = str_replace('“', '', $strings[$key]);
	$strings[$key] = str_replace(':', '', $strings[$key]);
	$strings[$key] = str_replace('.', '', $strings[$key]);
	$strings[$key] = str_replace(',', '', $strings[$key]);
	$strings[$key] = str_replace(';', '', $strings[$key]);
	$strings[$key] = str_replace('|', '', $strings[$key]);
	$strings[$key] = str_replace('[', '', $strings[$key]);
	$strings[$key] = str_replace(']', '', $strings[$key]);
	$strings[$key] = str_replace('(', '', $strings[$key]);
	$strings[$key] = str_replace(')', '', $strings[$key]);
	$strings[$key] = str_replace(' ', ' ', $strings[$key]);

	$commonString = $commonString . $strings[$key] . " ";
}

$words = explode(" ", $commonString);
$result = "";

for ($index=0;$index<count($words);$index++) {
	$word = $words[$index];
	if ($word != "") {
		$amountOfWord = substr_count($commonString, " " . $word . " ");
	
		$abstractResult->words[$index]->text = $word;
		$indexes = array();

		for ($i=0;$i<$amountOfWord;$i++) {
			$key = array_search($word, $words);
			$words[$key] = "";
			array_push($indexes, get_num_of_article($key, $strings));
		}

		$indexes = array_unique($indexes);
		$newIndexes = array_values($indexes);

		$abstractResult->words[$index]->count = count($newIndexes);
		$abstractResult->words[$index]->index = $newIndexes;
	}

}

usort($abstractResult->words, function($a, $b) { // Sort the array using a user defined function
    return $a->text < $b->text ? -1 : 1; //Compare the scores
});  

/* MYSTEM TITLE */

$titleResult = "";

$strings = array();
foreach ($root->articles as $article) {
	array_push($strings, $article->title->mystem);
}

$commonString = " ";
foreach ($strings as $key => $string) {
	$strings[$key] = mb_strtolower($strings[$key]);
	$strings[$key] = preg_replace('/\$[^$]*\$/', '', $strings[$key]);
	$strings[$key] = str_replace('”', '', $strings[$key]);
	$strings[$key] = str_replace('“', '', $strings[$key]);
	$strings[$key] = str_replace(':', '', $strings[$key]);
	$strings[$key] = str_replace('.', '', $strings[$key]);
	$strings[$key] = str_replace(',', '', $strings[$key]);
	$strings[$key] = str_replace(';', '', $strings[$key]);
	$strings[$key] = str_replace('|', '', $strings[$key]);
	$strings[$key] = str_replace('[', '', $strings[$key]);
	$strings[$key] = str_replace(']', '', $strings[$key]);
	$strings[$key] = str_replace('(', '', $strings[$key]);
	$strings[$key] = str_replace(')', '', $strings[$key]);
	$strings[$key] = str_replace(' ', ' ', $strings[$key]);

	$commonString = $commonString . $strings[$key] . " ";
}

$words = explode(" ", $commonString);
$result = "";

for ($index=0;$index<count($words);$index++) {
	$word = $words[$index];
	if ($word != "") {
		$amountOfWord = substr_count($commonString, " " . $word . " ");
	
		$mystemTitleResult->words[$index]->text = $word;
		$indexes = array();

		for ($i=0;$i<$amountOfWord;$i++) {
			$key = array_search($word, $words);
			$words[$key] = "";
			array_push($indexes, get_num_of_article($key, $strings));
		}

		$indexes = array_unique($indexes);
		$newIndexes = array_values($indexes);

		$mystemTitleResult->words[$index]->count = count($newIndexes);
		$mystemTitleResult->words[$index]->index = $newIndexes;
	}

}

usort($mystemTitleResult->words, function($a, $b) { // Sort the array using a user defined function
    return $a->text < $b->text ? -1 : 1; //Compare the scores
});    

/* MYSTEM ABSTRACT */

$strings = array();
foreach ($root->articles as $article) {
	array_push($strings, $article->abstract->mystem);
}

$commonString = " ";
foreach ($strings as $key => $string) {
	$strings[$key] = mb_strtolower($strings[$key]);
	$strings[$key] = preg_replace('/\$[^$]*\$/', '', $strings[$key]);
	$strings[$key] = str_replace('”', '', $strings[$key]);
	$strings[$key] = str_replace('“', '', $strings[$key]);
	$strings[$key] = str_replace(':', '', $strings[$key]);
	$strings[$key] = str_replace('.', '', $strings[$key]);
	$strings[$key] = str_replace(',', '', $strings[$key]);
	$strings[$key] = str_replace(';', '', $strings[$key]);
	$strings[$key] = str_replace('|', '', $strings[$key]);
	$strings[$key] = str_replace('[', '', $strings[$key]);
	$strings[$key] = str_replace(']', '', $strings[$key]);
	$strings[$key] = str_replace('(', '', $strings[$key]);
	$strings[$key] = str_replace(')', '', $strings[$key]);
	$strings[$key] = str_replace(' ', ' ', $strings[$key]);

	$commonString = $commonString . $strings[$key] . " ";
}

$words = explode(" ", $commonString);
$mystemAbstractResult = "";

for ($index=0;$index<count($words);$index++) {
	$word = $words[$index];
	if ($word != "") {
		$amountOfWord = substr_count($commonString, " " . $word . " ");
	
		$mystemAbstractResult->words[$index]->text = $word;
		$indexes = array();

		for ($i=0;$i<$amountOfWord;$i++) {
			$key = array_search($word, $words);
			$words[$key] = "";
			array_push($indexes, get_num_of_article($key, $strings));
		}

		$indexes = array_unique($indexes);
		$newIndexes = array_values($indexes);

		$mystemAbstractResult->words[$index]->count = count($newIndexes);
		$mystemAbstractResult->words[$index]->index = $newIndexes;
	}

}

usort($mystemAbstractResult->words, function($a, $b) { // Sort the array using a user defined function
    return $a->text < $b->text ? -1 : 1; //Compare the scores
});   


$index = 0;
foreach ($titleResult->words as $key => $term) {
	$terms->porter->title->terms[$index] = new Term();
	$terms->porter->title->terms[$index]->name = $term->text;
	$terms->porter->title->terms[$index]->count = $term->count;
	$terms->porter->title->terms[$index]->index = $term->index;
	$index++;
}

$index = 0;
foreach ($abstractResult->words as $key => $term) {
	$terms->porter->abstract->terms[$index] = new Term();
	$terms->porter->abstract->terms[$index]->name = $term->text;
	$terms->porter->abstract->terms[$index]->count = $term->count;
	$terms->porter->abstract->terms[$index]->index = $term->index;
	$index++;
}

$index = 0;
foreach ($mystemTitleResult->words as $key => $term) {
	$terms->mystem->title->terms[$index] = new Term();
	$terms->mystem->title->terms[$index]->name = $term->text;
	$terms->mystem->title->terms[$index]->count = $term->count;
	$terms->mystem->title->terms[$index]->index = $term->index;
	$index++;
}

$index = 0;
foreach ($mystemAbstractResult->words as $key => $term) {
	$terms->mystem->abstract->terms[$index] = new Term();
	$terms->mystem->abstract->terms[$index]->name = $term->text;
	$terms->mystem->abstract->terms[$index]->count = $term->count;
	$terms->mystem->abstract->terms[$index]->index = $term->index;
	$index++;
}

$listJson = json_encode($terms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo $listJson;

file_put_contents('/Users/denis/Documents/Search Class/myTermsList.json', $listJson);


?>