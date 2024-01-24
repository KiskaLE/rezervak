<?php

// Function to find and print data by name property
function findDataByName($xml, $name) {
    foreach ($xml->TransactionList->Transaction as $transaction) {
        foreach ($transaction as $column) {
            $attributes = $column->attributes();
            if ((string)$attributes['name'] === $name) {
                echo (string)$column . PHP_EOL;
            }
        }
    }
}

$filePath = "./../temp/text.xml";
if (file_exists($filePath)) {
    $xml = simplexml_load_file($filePath) or die("Error: Cannot create object");
   //print_r($xml->Info[0]);
    print_r($xml->TransactionList[0]);

}