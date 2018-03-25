<?php

/* Function for Mystem script execution */
function mystem($q) {
  $result = exec('echo "'.$q.'" | ./mystem -l -d');
  return $result;
}

/* Reading file and decoding into array */
$terms = json_decode(file_get_contents('myTermsList.json'), true);
$titleTerms = $terms['mystem']['title']['terms'];
$abstractTerms = $terms['mystem']['abstract']['terms'];

/* Creating common array of titles and abstracts */
$mergedTerms = $titleTerms;

/* Merging titles and abstract terms */
foreach ($abstractTerms as $abstractTerm) {
	$number = array_search($abstractTerm['name'], array_column($titleTerms, 'name'));
	if ($number > -1) {
		for ($i=0;$i<count($abstractTerm['index']);$i++) {
			$flag = false;
			foreach ($mergedTerms[$number]['index'] as $index) {
				if ($index == $abstractTerm['index'][$i]) $flag = true;
			}
			if ($flag == false) array_push($mergedTerms[$number]['index'], $abstractTerm['index'][$i]);
		}
	}
	else {
		array_push($mergedTerms, $abstractTerm);
	}
}

/* Getting query from file */
$query = file_get_contents('query_string.txt');

/* Transforming into words array */
$words = explode(" ", $query);

/* Extracting minus words */
$wordsString = "";
$minusWordsString = "";
foreach ($words as $key => $word) {
	if ($word[0] == "-") {
		$newWord = substr($word, 1, strlen($word));
		$minusWordsString = $minusWordsString . $newWord . " ";
	}
	else $wordsString = $wordsString . $word . " ";
}


/********** INTERSECTING ARRAY OF WORD ***********/

/* Using Mystem stemmer */
$mystem = mystem($wordsString);
$mystem = str_replace("}{", " ", $mystem);
$mystem = str_replace("{", "", $mystem);
$mystem = str_replace("}", "", $mystem);

/* Transforming into words array */
$queryWords = explode(" ", $mystem);

/* Searching for documents(articles) with certain words */
$documents = array();
foreach ($queryWords as $word) {
	$number = array_search($word, array_column($mergedTerms, 'name'));
	if ($number > 0) array_push($documents, $mergedTerms[$number]['index']);
}

/********** INTERSECTING ARRAY OF MINUSWORDS ***********/

/* Using Mystem stemmer */
$minusMystem = mystem($minusWordsString);
$minusMystem = str_replace("}{", " ", $minusMystem);
$minusMystem = str_replace("{", "", $minusMystem);
$minusMystem = str_replace("}", "", $minusMystem);

/* Transforming into words array */
$minusWords = explode(" ", $minusMystem);

/* Searching for documents(articles) with certain words */
$minusDocuments = array();
foreach ($minusWords as $minusWord) {
	$number = array_search($minusWord, array_column($mergedTerms, 'name'));
	if ($number > 0) array_push($minusDocuments, $mergedTerms[$number]['index']);
}


/********** SUBTRACTING ARRAY OF MINUSWORDS ***********/

$resultArray = array_diff($documents, $minusDocuments);

/********** TF IDF SCORE DOCUMENTS CALCULATING ***********/

/* Getting all documents which contain at least one of the words */
$relevantDocuments = array();
foreach($resultArray as $row => $innerArray){
  foreach($innerArray as $innerRow => $value){
    array_push($relevantDocuments, $value);
  }
}

$relevantDocuments = array_unique($relevantDocuments);
$relevantDocuments = array_values($relevantDocuments);

/* Reading file and decoding into array */
$articlesFile = json_decode(file_get_contents('myfile.json'), true);
$articles = $articlesFile['articles'];

/* Taking number of documents */
$numberOfDocuments = count($articles);

$titleDocuments = array();
foreach($relevantDocuments as $relevantDocument) {
	array_push($titleDocuments, $articles[$relevantDocument-1]['title']['mystem']);
}

$abstractDocuments = array();
foreach($relevantDocuments as $relevantDocument) {
	array_push($abstractDocuments, $articles[$relevantDocument-1]['abstract']['mystem']);
}

foreach ($titleDocuments as $key => $titleDocument) {
	$titleDocuments[$key] = preg_replace('/\$[^$]*\$/', '', $titleDocuments[$key]);
	$titleDocuments[$key] = str_replace('”', '', $titleDocuments[$key]);
	$titleDocuments[$key] = str_replace('“', '', $titleDocuments[$key]);
	$titleDocuments[$key] = str_replace(':', '', $titleDocuments[$key]);
	$titleDocuments[$key] = str_replace('.', '', $titleDocuments[$key]);
	$titleDocuments[$key] = str_replace(',', '', $titleDocuments[$key]);
	$titleDocuments[$key] = str_replace(';', '', $titleDocuments[$key]);
	$titleDocuments[$key] = str_replace('|', '', $titleDocuments[$key]);
	$titleDocuments[$key] = str_replace('[', '', $titleDocuments[$key]);
	$titleDocuments[$key] = str_replace(']', '', $titleDocuments[$key]);
	$titleDocuments[$key] = str_replace('(', '', $titleDocuments[$key]);
	$titleDocuments[$key] = str_replace(')', '', $titleDocuments[$key]);
	$titleDocuments[$key] = str_replace(' ', ' ', $titleDocuments[$key]);
	$titleDocuments[$key] = str_replace('ё', 'е', $titleDocuments[$key]);
	$titleDocuments[$key] = str_replace(' ', ' ', $titleDocuments[$key]);
	$titleDocuments[$key] = str_replace('!', '', $titleDocuments[$key]);
	$titleDocuments[$key] = str_replace('?', '', $titleDocuments[$key]);
}
foreach ($abstractDocuments as $key => $abstractDocument) {
	$abstractDocuments[$key] = preg_replace('/\$[^$]*\$/', '', $abstractDocuments[$key]);
	$abstractDocuments[$key] = str_replace('”', '', $abstractDocuments[$key]);
	$abstractDocuments[$key] = str_replace('“', '', $abstractDocuments[$key]);
	$abstractDocuments[$key] = str_replace(':', '', $abstractDocuments[$key]);
	$abstractDocuments[$key] = str_replace('.', '', $abstractDocuments[$key]);
	$abstractDocuments[$key] = str_replace(',', '', $abstractDocuments[$key]);
	$abstractDocuments[$key] = str_replace(';', '', $abstractDocuments[$key]);
	$abstractDocuments[$key] = str_replace('|', '', $abstractDocuments[$key]);
	$abstractDocuments[$key] = str_replace('[', '', $abstractDocuments[$key]);
	$abstractDocuments[$key] = str_replace(']', '', $abstractDocuments[$key]);
	$abstractDocuments[$key] = str_replace('(', '', $abstractDocuments[$key]);
	$abstractDocuments[$key] = str_replace(')', '', $abstractDocuments[$key]);
	$abstractDocuments[$key] = str_replace(' ', ' ', $abstractDocuments[$key]);
	$abstractDocuments[$key] = str_replace('ё', 'е', $abstractDocuments[$key]);
	$abstractDocuments[$key] = str_replace(' ', ' ', $abstractDocuments[$key]);
	$abstractDocuments[$key] = str_replace('!', '', $abstractDocuments[$key]);
	$abstractDocuments[$key] = str_replace('?', '', $abstractDocuments[$key]);
}

$scores = array();
foreach ($titleDocuments as $key => $titleDocument) {
	$score = 0;

	$titleDocumentWords = explode(" ", $titleDocument);
	$abstractDocumentWords = explode(" ", $abstractDocuments[$key]);

	/* Taking number of words in certain document */
	$numberOfTitleWords = count($titleDocumentWords);
	$numberOfAbstractWords = count($abstractDocumentWords);

	foreach ($queryWords as $wordKey => $word) {
		/* Taking number of documents containing certain word */
		$numberOfContainingDocuments = count($resultArray[$wordKey]);

		//echo array_count_values($titleDocumentWords)[$word];

		/* Taking number of matches in document for certain word */
		$numberOfMatchesInTitle = substr_count($titleDocument, " " . $word . " ");
		$numberOfMatchesInAbstract = substr_count($abstractDocuments[$key], " " . $word . " ");

		if ($numberOfContainingDocuments == 0) $idf = 0;
		else $idf = log($numberOfDocuments/$numberOfContainingDocuments);

		$score = $score + 0.6*($numberOfMatchesInTitle/$numberOfTitleWords)*$idf + 0.4*($numberOfMatchesInAbstract/$numberOfAbstractWords)*$idf;

	}
	$scores[$relevantDocuments[$key]] = $score;
}

arsort($scores);

foreach ($scores as $key => $score) {
	echo "Document " . $key . " score: " . $score . "\n";
}
if (count($scores) == 0) echo "No documents\n";

?>