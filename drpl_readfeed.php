<?php

/* 
 * The copyright to this code belongs to Northumbria Blood Bikes
 * This code package will have an included LICENSE that you should view
 *  .
 * drpl_readfeed.php         8/7/2016     MT     First version
 */

$url = 'http://localhost/drupal/testfeed.xml';
echo $url."<br />";
// read feed into SimpleXML object
$sxml = simplexml_load_file($url);

// then you can do
var_dump($sxml);
echo '<hr>';
print_r($sxml);
echo '<hr>';
$xml = new SimpleXMLElement(file_get_contents($url));

// pre tags to format nicely
echo '<pre>';
print_r($xml);
echo '</pre>';




foreach($xml->channel->item as $row){
  echo $row->title . "<br>";
}