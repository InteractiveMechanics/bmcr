<?php

$key    = 'BeQ2PIcGJgBnfrlP1ds8IlsWAGsR46MnS2BSD766x7V9NlCf9ygKmj4UVW2acFOpzNX6XwP3Gp9QsoSs';
$url    = 'http://www.worldcat.org/webservices/catalog/content/';
$oclc   = $_GET['oclc'];

$ch = curl_init( $url . $oclc . '?wskey=' . $key );
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$result = curl_exec($ch);
curl_close($ch);

print_r('<xmp>');
print_r($result);
print_r('</xmp>');
?>
