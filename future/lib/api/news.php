<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

require_once realpath(LIB_DIR.'/api.php');

$feeds      = $GLOBALS['siteConfig']->loadFeedData('news');
$maxPerPage = $GLOBALS['siteConfig']->getVar('NEWS_MAX_RESULTS');
$limit = 10;

function getFeed($feeds, $index) {
  if (isset($feeds[$index])) {
    $feedData = $feeds[$index];
    $controller = RSSDataController::factory($feedData);
    $controller->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));
    return $controller;
  } else {
    error_log("Error getting news feed for index $index");
    return null;
  }
}

$content = "";

switch(apiGetArg('command')) {
  case 'channels':
    $feed_labels = array();
    foreach ($feeds as $feedData) {
      $feed_labels[] = $feedData['TITLE'];
    }
  
    $content = json_encode($feed_labels);
    break;
  
  case 'search':
    $searchTerms = apiGetArg('q');
    $feed = getFeed($feeds, apiGetArg('channel', 0));
    
    if ($searchTerms && $feed) {
      $feed->addFilter('search', $searchTerms);

      $index = 0;
      $lastStoryId = apiGetArg('storyId', null);
      if ($lastStoryId) {
        $feedIndex = $feed->getIndexForItem($lastStoryId);
        if (!is_null($feedIndex)) {
          $index = $feedIndex + 1;
        }
      }
      
      $content = $feed->getRSSItems($index, $limit);
    }       
    break;
    
  default:
    $feed = getFeed($feeds, apiGetArg('channel', 0));
    
    if ($feed) {
      $index = 0;
      $lastStoryId = apiGetArg('storyId', null);
      if ($lastStoryId) {
        $feedIndex = $feed->getIndexForItem($lastStoryId);
        if (!is_null($feedIndex)) {
          $index = $feedIndex + 1;
        }
      }
      
      $content = $feed->getRSSItems($index, $limit);
    }
    break;
}

header('Content-Length: ' . strlen($content));
echo $content;
