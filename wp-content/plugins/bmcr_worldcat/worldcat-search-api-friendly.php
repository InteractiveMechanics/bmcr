<?php

$key    = 'fwG2m6dl6mBw63uBLRKvXNfPAfuIhKV5JFj3kF9iz2O8dYs2zGpiInIcdmQ3OeIoM33R1lghfinuIXho';
$url    = 'http://www.worldcat.org/webservices/catalog/content/isbn/';
$oclc   = $_GET['oclc'];

$ch = curl_init( $url . $oclc . '?wskey=' . $key );
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$result = curl_exec($ch);
curl_close($ch);

print_r('<xmp>');
print_r($result);
print_r('</xmp>');
?>
