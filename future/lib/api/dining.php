<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

require_once realpath(LIB_DIR.'/api.php');
require_once realpath(SITE_LIB_DIR.'/HarvardDining.php');
require_once realpath(SITE_LIB_DIR.'/HarvardDiningHalls.php');

switch (apiGetArg('command')) {
  case 'breakfast':
    $mealTime = 'BRK';
    $day = apiGetArg('date', date('Y-m-d', time()));
    echo json_encode(DiningData::getDiningData($day, $mealTime, false));
    break;
  
  case 'lunch':
    $mealTime = 'LUN';
    $day = apiGetArg('date', date('Y-m-d', time()));
    echo json_encode(DiningData::getDiningData($day, $mealTime, false));
    break;
  
  case 'dinner':
    $mealTime = 'DIN';
    $day = apiGetArg('date', date('Y-m-d', time()));
    echo json_encode(DiningData::getDiningData($day, $mealTime, false));
    break;
    
  case 'hours':
    echo json_encode(DiningHalls::getDiningHallHours());
    break;

}
