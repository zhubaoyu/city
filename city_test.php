<?php
require 'autoload.php';

use \City\City;
use \City\City\Mapper;

$city = new City(1,'maincity',70,80);
var_dump($city);
//Mapper::create($city);
//var_dump($city);
//var_dump(Mapper::findById(1));
//$city = new City(-1,'maincity',70,80);
//$city = new City(0,'maincity',70,80);
//$city = new City(2,'',70,80);
//$city = new City(2,'maincity',0,0);
//$city = new City(2,'maincity',-1,80);
//$city = new City(2,'maincity',70,-1);
//$city = new City(2,'maincity',100,80);
//$city = new City(2,'maincity',70,100);
//$city = new City(2,'maincity',70,80,City::CAPITAL);
//Mapper::create($city);
//var_dump($city);
//$city = new City(2,'maincity',70,80,2);
//$city = new City(2,'maincity',70,80,3);
//$city = new City(2,'maincity',70,80,1,0);
//$city = new City(2,'maincity',70,80,1,-0.5);
//$city = new City(2,'maincity',70,80,1,0.2,10);
//$city = new City(2,'maincity',70,80,1,0.2,-1);
//$city = new City(2,'maincity',70,80,1,0.2,4,5);
//$city = new City(2,'maincity',70,80,1,0.2,4,-1);
//$city = new City(2,'maincity',70,80,1,0.2,4,5,0);
//$city = new City(2,'maincity',70,80,1,0.2,4,5,-1);
//$city = new City(2,'largecity',70,80,1,0.2,4,5,1000);
//Mapper::create($city);
//var_dump($city);
//$city = new City(2,'maincity',70,80,1,0.2,4,5,1001);
//$city = new City(2,'maincity',70,80,1,0.2,4,5,0,null,null,null,1);
//$city = new City(2,'maincity',70,80,1,0.2,4,5,0,null,null,null,0);
//Mapper::create($city);

//$city = Mapper::findById(3);
//var_dump($city);
//$city->develop();
//$city->setType(City::CAPITAL);
//$d = Mapper::update($city);
//var_dump($city,$d);
//var_dump(Mapper::findByPlayerId(1));
//var_dump(Mapper::findByPlayerId(2));

