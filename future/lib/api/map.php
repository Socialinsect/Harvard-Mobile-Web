<?php

/****************************************************************
*
*  Copyright 2010 The President and Fellows of Harvard College
*  Copyright 2010 Modo Labs Inc.
*
*****************************************************************/

require_once realpath(LIB_DIR.'/api.php');
require_once realpath(LIB_DIR .'/feeds/ArcGISServer.php');

$content = "";

switch (apiGetArg('command')) {

  case 'capabilities':
    $results = ArcGISServer::getCapabilities();
    $content = json_encode($results);
    break;
  
  case 'proj4specs':
    $wkid = apiGetArg('wkid');
    $results = ArcGISServer::getWkidProperties($wkid);
    $content = json_encode($results);
    break;
  
  case 'tilesupdated':
    $date = file_get_contents($GLOBALS['siteConfig']->getVar('MAP_TILE_CACHE_DATE'));
    $data = array("last_updated" => trim($date));
    $content = json_encode($data);
    break;
    
  case 'categorytitles':
    $collections = ArcGISServer::getLayers();
    $result = array();
    foreach ($collections as $id => $name) {
      $result[] = array(
        'categoryName' => $name, 
        'categoryId' => $id,
      );
    }
    $content = json_encode($result);
    break;
  
  case 'search':
    $searchTerms = apiGetArg('q');
    $category = apiGetArg('category');
    $loc = apiGetArg('loc');
    
    if ($searchTerms) {
      if ($loc) {
        $url = $GLOBALS['siteConfig']->getVar('MAP_SEARCH_URL').'?'.http_build_query(array(
          'loc' => $loc,
          'str' => $searchTerms,
        ));
        $json = file_get_contents($url);
        $jsonObj = json_decode($json, true);
        if (isset($jsonObj['items'])) {
            $jsonObj['results'] = $jsonObj['items'];
            unset($jsonObj['items']);
        }
        $content = json_encode($jsonObj);

      } else {
        if ($category) {
          $results = ArcGISServer::search($searchTerms, $category);
          if ($results === FALSE) {
            $results = array();
            
          } else if (count($results) <= 1) {
            // if we're looking at a single result,
            // see if we can get more comprehensive info from the main search
            $moreResults = ArcGISServer::search($searchTerms);
            if (count($moreResults->results) == 1) {
              $result = $moreResults->results[0];
              if (count($results)) {
                $attributes = $results->results[0]->attributes;
                foreach ($attributes as $name => $value) {
                  $result->attributes->{$name} = $value;
                }
              }
              $results = $moreResults;
            }
          }
        } else {
          require_once realpath(LIB_DIR.'/feeds/MapSearch.php');
          $results = searchCampusMap($searchTerms);
        }
        $content = json_encode($results);
      }
      
    } elseif ($category) {
      $results = array();
      $layer = ArcGISServer::getLayer($category);
      if ($layer) {
        $featurelist = $layer->getFeatureList();
        foreach ($featurelist as $featureId => $attributes) {
          $results[] = array_merge($attributes,
            array('displayName' => $featureId));
        }
      }
      $content = json_encode($results);
    }
    break;
}

header('Content-Length: ' . strlen($content));
echo $content;
