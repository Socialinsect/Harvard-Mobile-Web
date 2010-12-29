<?php

require_once realpath(LIB_DIR.'/TransitDataParser.php');


$data = array();
$command = $_REQUEST['command'];

error_log("COMMAND {$_SERVER['REQUEST_URI']}");

switch ($command) {
  case 'about':
  case 'calendar':
    $feedConfigFile = realpath_exists(SITE_CONFIG_DIR."/feeds/transit-info.ini");
    if (!$feedConfigFile) {
      exitWithError("Missing configuration file '/feeds/transit-info.ini'");
    }
    
    $type = ($command == 'calendar') ? 'calendar' : $_REQUEST['agency'];

    $infoConfig = parse_ini_file($feedConfigFile, true);
    if (!isset($infoConfig[$type])) {
      exitWithError("unknown agency '{$_REQUEST['agency']}'");
    }
    
    $data['html'] = $infoConfig[$type];
    break;
  
  case 'stopInfo':
    $view = initTransitDataView();

    $stopID = $_REQUEST['id'];
    $time = time();
    
    $stopInfo = $view->getStopInfo($stopID);
    $stops = array();
    if (isset($stopInfo['routes'])) {
      foreach ($stopInfo['routes'] as $routeID => $stopTimes) {
        $stops[] = formatStopInfo($routeID, $stopID, $stopInfo, $stopTimes);
      }
      $data['stops'] = $stops;
      $data['now'] = $time;
    } else {
      $data['error'] = "could not perform $command";
    }
    break;
    
  case 'routes':
    $view = initTransitDataView();
    $routesInfo = $view->getRoutes();
    
    foreach ($routesInfo as $routeID => $routeInfo) {
      $entry = formatRouteInfo($routeID, $routeInfo);
      
      if (!isset($_REQUEST['compact'])) {
        $path = mergePaths($view->getRoutePaths($routeID));
        $fullRouteInfo = $view->getRouteInfo($routeID);
        
        $stops = array();
        foreach ($fullRouteInfo['stops'] as $stopID => $stopInfo) {
          $stops[] = formatStopForRouteInfo($stopID, $stopInfo);
        }
        $stops[0]['path'] = $path;
        $entry['stops'] = $stops;
        
        $data['path'] = $path;
      }
      
      $data[] = $entry;
    }
    break;
  
  case 'routeInfo':
    $routeID = $_REQUEST['id'];
    $addPath = isset($_REQUEST['full']) && $_REQUEST['full'] == 'true';
    
    $time = time();
    if ($routeID) {
      $view = initTransitDataView();

      $routeInfo = $view->getRouteInfo($routeID);      
      $data = formatRouteInfo($routeID, $routeInfo);

      $vehicles = $view->getRouteVehicles($routeID);
      $data['vehicleLocations'] = array_values($vehicles);

      $stops = array();
      foreach ($routeInfo['stops'] as $stopID => $stopInfo) {
        $stops[] = formatStopForRouteInfo($stopID, $stopInfo);
      }
      if ($addPath) {
        $path = mergePaths($view->getRoutePaths($routeID));
        $stops[0]['path'] = $path;
        $data['path'] = $path;
      }
      $data['stops'] = $stops;
      $data['now'] = $time;
  
    } else {
      $data = Array('error' => "no route parameter");
    }  
    break;
  
  case 'announcements':
    $view = initTransitDataView();
    $newsConfigs = $view->getNews();
    $data['success'] = 1;
    
    $agencies = array();
    foreach ($newsConfigs as $newsID => $newsConfig) {
      if (!isset($agencies[$newsConfig['agency']])) {
        $agencies[$newsConfig['agency']] = array(
          'name'          => $newsConfig['agency'],
          'announcements' => array(),
        );
      }
      $newsConfig['date'] = strftime('%Y/%m/%d', $newsConfig['date']);
      $agencies[$newsConfig['agency']]['announcements'][] = $newsConfig;
    }
    
    $data['agencies'] = array_values($agencies);
    break;
  
  case 'subscribe': 
  case 'unsubscribe':
    require_once realpath(LIB_DIR.'/push/apns_lib.php');
    
    $data = array('error' => "could not perform $command");
    
    if ($sub = APNSSubscriber::create()) {
      $routeID = $_REQUEST['route'];
      $stopID = $_REQUEST['stop'];
      $params = Array(
        'route_id' => $routeID,
        'stop_id'  => $stopID,
      );
      
      // Always unsubscribe any existing subscriptions
      $unsubscribed = $sub->unsubscribe("ShuttleSubscription", $params);
      
      if ($command == 'unsubscribe') {
        if ($unsubscribed) {
          $data = array('success' => $command);
        } else {
          exitWithError("failed to unsubscribe notification for route $routeID, stop $stopID");
        }
      } else {
        $requestTime = isset($_REQUEST['time']) ? intval($_REQUEST['time']) : time();
        $params['request_time'] = $requestTime;
        
        if ($sub->subscribe("ShuttleSubscription", $params)) {
          $view = initTransitDataView();
          $routeInfo = $view->getRouteInfo($routeID, $requestTime);
          
          $frequencySeconds = $routeInfo['frequency']*60;
          $padding = ceil($frequencySeconds / 2);
          
          $data = array(
            'success'     => $command,
            'start_time'  => $requestTime - $padding,
            'expire_time' => $requestTime + $padding,
          );
        
          //error_log("Requested shuttle notification for route $routeID, stop $stopID at ".strftime('%I:%M:%S %p', $requestTime)." from ".strftime('%I:%M:%S %p', $data['start_time'])." to ".strftime('%I:%M:%S %p', $data['expire_time']));
        } else {
          exitWithError("failed to subscribe notification for route $routeID, stop $stopID");
        }
      }
    } else {
      exitWithError("push notification library could not initialize");
    }
    break;


  case 'default':
    exitWithError("unknown transit command '$command'");
}

//error_log(print_r($data, true));
echo json_encode($data);


function initTransitDataView() {
  $feedConfigFile = realpath_exists(SITE_CONFIG_DIR."/feeds/transit.ini");
  if (!$feedConfigFile) {
    exitWithError("cannot load transit module configuration");
  }
  
  $feedData = parse_ini_file($feedConfigFile, true);
  $transitConfig = new TransitConfig($feedData);
  
  return new TransitDataView($transitConfig);
}

function formatRouteInfo($routeID, $routeInfo) {
  $result = array(
    'route_id'       => "$routeID", // php really likes to make the #1 bus an integer
    'agency'         => $routeInfo['agency'],
    'color'          => $routeInfo['color'],
    'title'          => $routeInfo['name'],
    'interval'       => $routeInfo['frequency'],
    'isSafeRide'     => $routeInfo['agency'] == 'saferide' ? true : false,
    'isRunning'      => $routeInfo['running'] ? true : false,
    'summary'        => isset($routeInfo['description']) ? $routeInfo['description'] : '',
    'description'    => isset($routeInfo['summary']) ? $routeInfo['summary'] : '',
    'gpsActive'      => isset($routeInfo['live']) && $routeInfo['live'] ? true : false,
  );
  if (isset($routeInfo['stopIconUrl'])) {
    $result['stopMarkerUrl'] = $routeInfo['stopIconUrl'];
  }
  if (isset($routeInfo['genericIconUrl'])) {
    $result['vehicleIconUrl'] = $routeInfo['vehicleIconUrl'];
  }
  return $result;
}

function formatStopForRouteInfo($stopID, $stopInfo) {
  $stop = array(
    'id'          => "$stopID",
    'title'       => $stopInfo['name'],
    'lat'         => $stopInfo['coordinates']['lat'],
    'lon'         => $stopInfo['coordinates']['lon'],
    'predictions' => array(),
    'next'        => $stopInfo['arrives'],
  );
  if ($stop['next'] == 0) {
    $stop['next'] = time();
  }
  if (isset($stopInfo['predictions']) && count($stopInfo['predictions']) > 1) {
    array_shift($stopInfo['predictions']); // remove prediction corresponding to $stop['next']
    $stop['predictions'] = $stopInfo['predictions'];
  }
  if ($stopInfo['upcoming']) {
    $stop['upcoming'] = true;
  }
  return $stop;
}

function formatStopInfo($routeID, $stopID, $stopInfo, $stopTimes) {
  $stop = array(
    'id'          => "$stopID",
    'route_id'    => "$routeID",
    'lat'         => $stopInfo['coordinates']['lat'],
    'lon'         => $stopInfo['coordinates']['lon'],
    'next'        => $stopTimes['arrives'],
    'gps'         => isset($routeInfo['live']) && $routeInfo['live'] ? true : false,
  );
  
  if (isset($stopTimes['predictions']) && count($stopTimes['predictions']) > 1) {
    array_shift($stopTimes['predictions']); // remove prediction corresponding to $stop['next']
    $stop['predictions'] = $stopTimes['predictions'];
  }
  return $stop;
}

function mergePaths($paths) {
  // the iPhone app does not understand paths which aren't in a loop.  Wheeee!
  $paths = array_values($paths);

  if (count($paths) > 1) {
    $foundPair = true;
    while ($foundPair) {
      $foundPair = false;
      for ($i = 0; $i < count($paths); $i++) {
        for ($j = 0; $j < count($paths); $j++) {
          if ($i == $j) { continue; }
          
          $path1 = array_values($paths[$i]);
          $path2 = array_values($paths[$j]);
          //error_log("Path 1 ($i): ".count($path1)." points");
          //error_log("Path 2 ($j): ".count($path2)." points");
          for ($x = 0; $x < count($path1)-1; $x++) {
            for ($y = 0; $y < count($path2)-1; $y++) {
              
              if ($path1[$x] == $path2[$y] && $path1[$x+1] == $path2[$y+1]) {
                // Found a place to attach the paths!
                $path1Segment1 = array_slice($path1, 0, $x+1);
                $path1Segment2 = array_slice($path1, $x);
                $path2Segment1 = array_slice($path2, 0, $y+1);
                $path2Segment2 = array_slice($path2, $y);
                
                unset($paths[$i]);
                unset($paths[$j]);
                $paths[] = mergeArrays(array(
                  $path1Segment1,
                  array_reverse($path2Segment1),
                  array_reverse($path2Segment2),
                  $path1Segment2,
                ));
                $foundPair = true;
                break;
              } else if ($path1[$x] == $path2[$y+1] && $path1[$x+1] == $path2[$y]) {
                // Found a place to attach the paths!
                $path1Segment1 = array_slice($path1, 0, $x+1);
                $path1Segment2 = array_slice($path1, $x);
                
                unset($paths[$i]);
                unset($paths[$j]);
                $paths[] = mergeArrays(array(
                  $path1Segment1,
                  $path2,
                  $path1Segment2,
                ));
                $foundPair = true;
              }
            }
            if ($foundPair) { break; }
          }
          if ($foundPair) { break; }
        }
        if ($foundPair) { break; }
      }
    }
  }

  if (count($paths) > 1) {
    error_log("Warning!  Multiple path segments after merge.");
  }  

  // Last ditch effort... if there is still more than one we will just
  // merge and live with the criss-crosses
  $mergedPath = array();
  foreach ($paths as $path) {
    $mergedPath = array_merge($mergedPath, $path);
  }
  
  return $mergedPath;
}

function mergeArrays($arrays) {
  $result = array();
  foreach ($arrays as $array) {
    $result = array_merge($result, $array);
  }
  return array_values($result);
}

function exitWithError($message) {
  echo json_encode(array('error' => $message));
  exit;
}
