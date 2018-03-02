<?php

require __DIR__ . '/Stemmer.php';

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

/* FUNCTION FOR MYSTEM LAUNCH */
function mystem($q) {
  $result = exec('echo "'.$q.'" | ./mystem -l -d');
  return $result;
}

/* MAIN PART (EXTRACT INFORMATION FROM WEBSITE) */
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

/* GENERATE JSON FILE */

$json = json_encode($root, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo $json;

file_put_contents('/Users/denis/Documents/Search Class/myfile.json', $json);

?>