<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

require_once realpath(LIB_DIR.'/api.php');

$feedData = null;

$feedConfig = $GLOBALS['siteConfig']->loadFeedData('people');
if (!$feedConfig || !isset($feedConfig['people'])) {
  $result = array('error' => 'Nothing Found');
  $content = json_encode($result);
  header('Content-Length: ' . strlen($content));
  echo $content;
  exit;  
}
$feedData = $feedConfig['people'];

$displayFields = $GLOBALS['siteConfig']->getAPIVar(apiGetArg('module'), 'displayFields');

switch (apiGetArg('command')) {
  case 'details':
    $uid = apiGetArg('uid');
    
    if ($uid) {
      $PeopleController = PeopleController::factory($feedData);
      $PeopleController->setAttributes(array_keys($displayFields));
      if ($person = $PeopleController->lookupUser($uid)) {
        $result = array(
          'uid' => $person->getId()
        );
        foreach ($displayFields as $field=>$display) {
          if ($value = $person->getField($field)) {
            $result[$field] = $value;
          }
        }
        $content = json_encode($result);      
      } else {
        $result = array('error' => $ldap->gerError());
        $content = json_encode($result);
      }

    }
    break;
  case 'search':
    $searchText = apiGetArg('q');
    
    if ($searchText) {
      $PeopleController = PeopleController::factory($feedData);
      $PeopleController->setAttributes(array_keys($displayFields));
      
      $people = $PeopleController->search($searchText);
      if (!is_array($people)) {
        $result = array('error' => 'Nothing Found');
        $content = json_encode($result);
        
      } elseif ($PeopleController->getError()) {
        $result = array('error' => $PeopleController->getError());
        $content = json_encode($result);
        
      } elseif (count($people)==0) {
          $result = array('error' => 'Nothing Found');
          $content = json_encode($result);
          
      } else {
        $results = array();
        foreach ($people as $person) {
          $result = array(
            'uid' => $person->getId()
          );
          foreach ($displayFields as $field=>$display) {
            if ($value = $person->getField($field)) {
              $result[$field] = $value;
            }
          }
          
          $results[] = $result;
        }
        
        $content = json_encode($results);
      }
    }
    break;
    
  case 'displayFields':
    $content = json_encode($displayFields);
    break;
    
  default:
    break;
}

header('Content-Length: ' . strlen($content));
echo $content;
