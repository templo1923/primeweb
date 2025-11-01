<?php

include "session.php";
include "header.php";
include "config.php";
echo "<div><div>\n\t\t\t\t<div >\n\t\t\t\t\t";
echo "\t\t\t\t</div>\n\t\t\t</div>\\t\n\t\t\t\t\t\t<div class=\"owl-carousel owl-theme\" style=\"opacity: 1; display: block;\">\n\t";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Combined Webpage</title>
    <style>
        body {
            background-color: black;
            color: white;
        }
        table {
            width: 100%;
        }
        td {
            text-align: left;
            vertical-align: top;
            width: 20%;
            color: white;
            text-shadow: 1px 1px 2px black;
        }
        img {
            border-radius: 10px;
            max-width: 70%;
        }
    </style>
</head>
<body>
<?php
function fetchAndProcessContent($target_url) {
    $content = file_get_contents($target_url);
    $text_replacements = array(
        "../" => "https://www.thesportsdb.com/",
        "/images/icons/flags/" => "https://www.thesportsdb.com/images/icons/flags/",
        "/images/icons/calendar.png" => "https://www.thesportsdb.com/images/icons/calendar.png",
        "/images/icons/time.png" => "https://www.thesportsdb.com/images/icons/time.png"
    );
    $content = str_replace(array_keys($text_replacements), array_values($text_replacements), $content);
    $content = preg_replace('/(<img[^>]+)(>)/i', '$1 style="border-radius: 10px; max-width: 70%;"$2', $content);
    
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($content);
    libxml_use_internal_errors(false);
    
    $xpath = new DOMXPath($dom);
    
    $linkElements = $xpath->query('//a');
    foreach ($linkElements as $linkElement) {
        $linkElement->removeAttribute('href');
    }
    
    $tdElements = $xpath->query('//td');
    foreach ($tdElements as $tdElement) {
        $tdElement->setAttribute('style', 'text-align: left; vertical-align: top; width: 20%; color: white; text-shadow: 2px 2px 2px black;');
    }
    
    $htmlElement = $xpath->query('//html')->item(0);
    if ($htmlElement) {
        $htmlElement->setAttribute('style', 'background-color: red;');
    }
    
    return $dom;
}

// Process the first URL
$dom1 = fetchAndProcessContent("https://www.thesportsdb.com/browse_tv");
$xpath1 = new DOMXPath($dom1);
$table1 = $xpath1->query('//table')->item(0);

if ($table1) {
    echo $dom1->saveXML($table1);
} else {
    echo "Table not found in the first URL.<br>";
}

echo "<br><hr><br>";

// Process the second URL
$dom2 = fetchAndProcessContent("https://www.thesportsdb.com/");
$xpath2 = new DOMXPath($dom2);
$table2 = $xpath2->query('//table')->item(4);

if ($table2) {
    echo $dom2->saveXML($table2);
} else {
    echo "Table not found in the second URL.";
}
?>
</body>
</html>