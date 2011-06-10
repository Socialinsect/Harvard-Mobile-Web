<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

require_once realpath(LIB_DIR.'/api.php');
require_once realpath(LIB_DIR . '/ICalendar.php');

$data = array();

$feeds = $GLOBALS['siteConfig']->loadFeedData('calendar');
$timezone = new DateTimeZone($GLOBALS['siteConfig']->getVar('LOCAL_TIMEZONE'));
$suppressedCustomFields = $GLOBALS['siteConfig']->getAPIVar(apiGetArg('module'), 'suppressedCustomFields');

function getFeed($feeds, $index) {
  if (isset($feeds[$index])) {
    $feedData = $feeds[$index];
    $controller = CalendarDataController::factory($feedData);
    $controller->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));
    return $controller;
  } else {
    error_log("Error getting calendar feed for index $index");
    return null;
  }
}

switch (apiGetArg('command')) {
  case 'day':
    $type = strtolower(apiGetArg('type', 'events'));
    $time = apiGetArg('time', time());
    
    $feed = getFeed($feeds, $type);
    
    if ($feed) {
      $start = new DateTime(date('Y-m-d H:i:s', $time), $timezone);
      $start->setTime(0,0,0);
      $end = clone $start;
      $end->setTime(23,59,59);
  
      $feed->setStartDate($start);
      $feed->setEndDate($end);
      $iCalEvents = $feed->items();
              
      foreach($iCalEvents as $iCalEvent) {
        $data[] = $iCalEvent->apiArray($suppressedCustomFields);
      }
    }
    break;

  case 'search':
    $type = strtolower(apiGetArg('type', 'events'));
    $searchString = apiGetArg('q');
    
    $feed = getFeed($feeds, $type);
    
    if ($feed) {
      $start = new DateTime(null, $timezone);
      $start->setTime(0,0,0);
      $feed->setStartDate($start);
      $feed->setDuration(7,'day');
      $feed->addFilter('search', $searchString);
      $iCalEvents = $feed->items();
      
      foreach($iCalEvents as $iCalEvent) {
        $data[] = $iCalEvent->apiArray($suppressedCustomFields);
      }
      
      $data = array('events'=>$data);
    }
    break;

  case 'category':
    $type = strtolower(apiGetArg('type', 'events'));
    $id = apiGetArg('id');
    
    if ($id) {
      $start = apiGetArg('start', time());
      $end = apiGetArg('end', $start + 86400);

      $events = array();
      
      if (strlen($id) > 0) {
        $feed = getFeed($feeds, $type);
        
        if ($feed) {
          $start = new DateTime(date('Y-m-d H:i:s', $start), $timezone);
          $start->setTime(0,0,0);
          $end = clone $start;
          $end->setTime(23,59,59);
        
          $feed->setStartDate($start);
          $feed->setEndDate($end);
          $feed->addFilter('category', $id);
          $events = $feed->items();
          foreach ($events as $event) {
            $data[] = $event->apiArray($suppressedCustomFields);
          }
        }
      }
    }
   break;

  case 'categories':
    $type = strtolower(apiGetArg('type', 'events'));
    $feed = getFeed($feeds, $type);
    
    if ($feed) {
      $categoryObjects = $feed->getEventCategories();
  
      foreach ($categoryObjects as $categoryObject) {
        $name = ucwords($categoryObject->get_name());
        $catid = $categoryObject->get_cat_id();
        $url = $categoryObject->get_url();
  
        $catData = array(
          'name' => $name,
          'catid' => $catid,
          'url' => $url
        );
  
        $data[] = $catData;
      }
    }
    break;

  case 'academic':
    $year = intval(apiGetArg('year', date('Y')));

    $start = new DateTime( $year   ."0820", $timezone);
    $end   = new DateTime(($year+1)."0819", $timezone);
    
    $feed = getFeed($feeds, 'academic');
    
    if ($feed) {
      $feed->setStartDate($start);
      $feed->setEndDate($end);
      $iCalEvents = $feed->items();
  
      foreach($iCalEvents as $event) {
        $data[] = $event->apiArray($suppressedCustomFields);
      }
    }
    break;
}

echo json_encode($data);
