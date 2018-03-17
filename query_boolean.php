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
$words = explode(" ", $mystem);

/* Searching for documents(articles) with certain words */
$documents = array();
foreach ($words as $word) {
	$number = array_search($word, array_column($mergedTerms, 'name'));
	if ($number > 0) array_push($documents, $mergedTerms[$number]['index']);
}

/* Getting intersect array of documents (Where all words from the query are contained) */
$intersectArray = array();
if (count($documents) > 0) $intersectArray = $documents[0];
for ($i=1;$i<count($documents);$i++) {
	$intersectArray = array_uintersect($intersectArray, $documents[$i], "strcasecmp");
}

/* Printing documents numbers */
// print_r($intersectArray);


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

/* Getting intersect array of documents (Where all words from the query are contained) */
$minusIntersectArray = array();
if (count($minusDocuments) > 0) $minusIntersectArray = $minusDocuments[0];
for ($i=1;$i<count($minusDocuments);$i++) {
	$minusIntersectArray = array_uintersect($minusIntersectArray, $minusDocuments[$i], "strcasecmp");
}

/* Printing documents numbers */
// print_r($minusIntersectArray);


/********** SUBTRACTING ARRAY OF MINUSWORDS ***********/

$resultArray = array_diff($intersectArray, $minusIntersectArray);

/* Printing documents numbers */
foreach ($resultArray as $documentNumber) {
	echo $documentNumber . "\n";
}
if (count($resultArray) == 0) echo "No documents\n";

?>