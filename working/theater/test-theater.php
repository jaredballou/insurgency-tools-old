<?php
$theaterpath = '../../data/theaters/1.8.4.3/';
require_once "kvreader2.php";
$reader = new KVReader();
$data = $reader->readFile("{$theaterpath}/default_checkpoint.theater");
var_dump($reader->write($data));
