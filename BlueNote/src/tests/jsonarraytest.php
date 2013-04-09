<?php

$testobj1 = array(
	"Apfel" => "Apfelsaft",
	"Birne" => "Birnensaft",
	"Coca" => "Kaffee"
);

$testobj2 = array(
	"Obst" => array("Apfel", "Birne", "Banane"),
	"Gemuese" => array("Knoblauch", "Paprika", "Gurke")
);

$testobj3 = array(
	0 => array(
		"name" => "Apfel",
		"groesse" => 2
	),
	5 => array (
		"name" => "Banane",
		"groesse" => 4
	)
);

$res1 = json_encode($testobj1);
$res2 = json_encode($testobj2);
$res3 = json_encode($testobj3);

echo $res1 . "\n";
echo $res2 . "\n";
echo $res3 . "\n";


$arr1 = json_decode($res1);
$arr2 = json_decode($res2);
$arr3 = json_decode($res3);

echo "<pre>"; print_r($arr1); echo "</pre>\n";
echo "<pre>"; print_r($arr2); echo "</pre>\n";
echo "<pre>"; print_r($arr3); echo "</pre>\n";

?>