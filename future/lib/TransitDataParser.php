<?php

require_once('Polyline.php');

class TransitConfig {
  private $parsers = array();
  
  function __construct($feedConfig) {
    // Loads an array from an ini file
    // See config/feeds/transit.ini for more details on the structure of the ini

    foreach ($feedConfig as $id => $config) {
      $liveParserClass = null;
      if (isset($config['live_class']) && $config['live_class']) {
        $liveParserClass = $config['live_class'];
      }
      unset($config['live_class']);

      $staticParserClass = null;
      if (isset($config['static_class']) && $config['static_class']) {
        $staticParserClass = $config['static_class'];
      }
      unset($config['static_class']);

      $this->addParser($id, $liveParserClass, $staticParserClass);
      
      if (isset($config['route_whitelist']) && count($config['route_whitelist'])) {
        $this->setRouteWhitelist($id, $config['route_whitelist']);
      }
      unset($config['route_whitelist']);
      
      foreach ($config as $configKey => $configValue) {
        $parts = explode('_', $configKey);
        
        $type = $parts[0];
        $field = $parts[1];
        $keyOrVal = end($parts);
        
        // skip values so we don't add twice
        if ($keyOrVal == 'vals') { continue; }  
        
        if ($type != 'live' && $type != 'static') {
          error_log("Warning: unknown transit configuration type '$type'");
          continue;
        }
        
        $configValueKey = implode('_', array_slice($parts, 0, -1)).'_vals';
        if (!isset($config[$configValueKey])) {
          error_log("Warning: transit configuration file missing value '$configValueKey' for key '$configKey'");
          continue;
        }
        
        $fieldKeys = $configValue;
        $fieldValues = $config[$configValueKey];
        
        switch ($field) {
          case 'argument': 
            foreach ($fieldKeys as $i => $fieldKey) {
              $this->setArgument($id, $type, $fieldKey, $fieldValues[$i]);
            }
            break;
            
          case 'override':
            if (count($parts) == 5) {
              $object = $parts[2];
              $field = $parts[3];
              
              foreach ($fieldKeys as $i => $fieldKey) {
                $this->setFieldOverride($id, $type, $object, $field, $fieldKey, $fieldValues[$i]);
              }
            }
            break;
          
          default:
            error_log("Warning: unknown transit configuration key '$configKey'");
            break;
        }
      }
    }
  }
  
  public function addParser($id, $liveParserClass=null, $staticParserClass=null) {
    if (isset($liveParserClass) || isset($staticParserClass)) {
      $this->parsers[$id] = array();
    }
    if (isset($liveParserClass) && $liveParserClass) {
      $this->parsers[$id]['live'] = array(
        'class'     => $liveParserClass,
        'arguments' => array(),
        'overrides' => array(),
      );
    }
    if (isset($staticParserClass) && $staticParserClass) {
      $this->parsers[$id]['static'] = array(
        'class'     => $staticParserClass,
        'arguments' => array(),
        'overrides' => array(),
      );
    }
  }
  
  private function setArgument($id, $type, $key, $value) {
    if (isset($this->parsers[$id], $this->parsers[$id][$type])) {
      $this->parsers[$id][$type]['arguments'][$key] = $value;
    }
  }
  
  private function setFieldOverride($id, $type, $object, $field, $key, $value) {
    if (isset($this->parsers[$id], $this->parsers[$id][$type])) {
      if (!isset($this->parsers[$id][$type]['overrides'][$object])) {
        $this->parsers[$id][$type]['overrides'][$object] = array();
      }
      if (!isset($this->parsers[$id][$type]['overrides'][$object][$field])) {
        $this->parsers[$id][$type]['overrides'][$object][$field] = array();
      }
      $this->parsers[$id][$type]['overrides'][$object][$field][$key] = $value;
    }
  }

  private function setRouteWhitelist($id, $routes) {
    if (isset($this->parsers[$id])) {
      $this->parsers[$id]['routes'] = $routes;
    }
  }
  
  //
  // Query
  //
  
  private function getParserValueForKey($id, $type, $key, $default) {
    if (isset($this->parsers[$id], 
              $this->parsers[$id][$type], 
              $this->parsers[$id][$type][$key])) {
              
      return $this->parsers[$id][$type][$key];
    } else {
      return $default;
    }    
  }
  
  public function getParserIDs() {
    return array_keys($this->parsers);
  }
  
  public function hasLiveParser($id) {
    return isset($this->parsers[$id], $this->parsers[$id]['live']);
  }
  public function hasStaticParser($id) {
    return isset($this->parsers[$id], $this->parsers[$id]['static']);
  }
  
  public function getLiveParserClass($id) {
    return $this->getParserValueForKey($id, 'live', 'class', false);
  }
  public function getStaticParserClass($id) {
    return $this->getParserValueForKey($id, 'static', 'class', false);
  }
  
  public function getLiveParserRouteWhitelist($id) {
    return $this->getParserValueForKey($id, 'live', 'routes', array());
  }
  public function getStaticParserRouteWhitelist($id) {
    return $this->getParserValueForKey($id, 'static', 'routes', array());
  }
  
  public function getLiveParserArgs($id) {
    return $this->getParserValueForKey($id, 'live', 'arguments', array());
  }
  public function getStaticParserArgs($id) {
    return $this->getParserValueForKey($id, 'static', 'arguments', array());
  }
  
  public function getLiveParserOverrides($id) {
    return $this->getParserValueForKey($id, 'live', 'overrides', array());
  }
  public function getStaticParserOverrides($id) {
    return $this->getParserValueForKey($id, 'static', 'overrides', array());
  }
}

class TransitDataView {
  private $config = array();
  private $parsers = array();
  private static $routesCache = null;
  
  private static function getRoutesCache() {
    if (!isset(self::$routesCache)) {
      self::$routesCache = new DiskCache(
        $GLOBALS['siteConfig']->getVar('TRANSIT_CACHE_DIR'),
        $GLOBALS['siteConfig']->getVar('TRANSIT_ROUTE_LIST_CACHE_TIMEOUT'), TRUE);
      self::$routesCache->preserveFormat();
      self::$routesCache->setSuffix(".json");
    } 
    return self::$routesCache;
  }
  
  function __construct($transitConfig) {
    $this->config = $transitConfig;
    
    foreach ($this->config->getParserIDs() as $parserID) {
    
      if ($this->config->hasLiveParser($parserID)) {
      
        $parser['live'] = TransitDataParser::factory(
          $this->config->getLiveParserClass($parserID), 
          $this->config->getLiveParserArgs($parserID),
          $this->config->getLiveParserOverrides($parserID),
          $this->config->getLiveParserRouteWhitelist($parserID)
        );
      } else {
        $parser['live'] = false;
      }
      if ($this->config->hasStaticParser($parserID)) {
      
        $parser['static'] = TransitDataParser::factory(
          $this->config->getStaticParserClass($parserID), 
          $this->config->getStaticParserArgs($parserID),
          $this->config->getStaticParserOverrides($parserID),
          $this->config->getStaticParserRouteWhitelist($parserID)
        );
      } else {
        $parser['static'] = false;
      }
      $this->parsers[$parserID] = $parser;
    }
  }
  
  public function refreshLiveParsers() {
    foreach ($this->config->getParserIDs() as $parserID) {
      if ($this->config->hasLiveParser($parserID)) {
      
        unset($this->parsers[$parserID]['live']);
        $this->parsers[$parserID]['live'] = TransitDataParser::factory(
          $this->config->getLiveParserClass($parserID), 
          $this->config->getLiveParserArgs($parserID),
          $this->config->getLiveParserOverrides($parserID),
          $this->config->getLiveParserRouteWhitelist($parserID)
        );
      }
    }
  }
  
  public function getStopInfoForRoute($routeID, $stopID) {
    $stopInfo = array();
    $cacheName = "stopInfoForRoute.$routeID.$stopID";
    $cache = self::getRoutesCache();
    
    if ($cache->isFresh($cacheName)) {
      $stopInfo = json_decode($cache->read($cacheName), true);
      
    } else {
      $parser = $this->parserForRoute($routeID);
      
      if ($parser['live']) {
        $stopInfo = $parser['live']->getStopInfoForRoute($routeID, $stopID);
      }
      
      if ($parser['static']) {
        $staticStopInfo = $parser['static']->getStopInfoForRoute($routeID, $stopID);
      }
      
      if (!$stopInfo) {
        $stopInfo = $staticStopInfo;
      }
      
      if ($stopInfo) {
        if (!isset($stopInfo['arrives']) || $staticStopInfo['arrives'] < $stopInfo['arrives']) {
            $stopInfo['arrives'] = $staticStopInfo['arrives'];
        }
        if (!isset($stopInfo['predictions'])) {
          $stopInfo['predictions'] = $staticStopInfo['predictions'];
          
        } else if (count($staticStopInfo['predictions'])) {
          $stopInfo['predictions'] = array_merge($stopInfo['predictions'], $staticStopInfo['predictions']);
          
          $stopInfo['predictions'] = array_unique($stopInfo['predictions']);
          sort($stopInfo['predictions']);
        }
      }
      $cache->write(json_encode($stopInfo), $cacheName);
    }
    
    return $stopInfo;
  }
  
  public function getStopInfo($stopID) {
    $stopInfo = array();
    $cacheName = "stopInfo.$stopID";
    $cache = self::getRoutesCache();
    
    if ($cache->isFresh($cacheName)) {
      $stopInfo = json_decode($cache->read($cacheName), true);
      
    } else {
      foreach ($this->parsersForStop($stopID) as $parser) {
        $parserInfo = false;
        
        if ($parser['live']) {
          $parserInfo = $parser['live']->getStopInfo($stopID);
        }
        
        if ($parser['static']) {
          $staticParserInfo = $parser['static']->getStopInfo($stopID);
        }
        
        if (!$parserInfo) {
          $parserInfo = $staticParserInfo;
        } else if (isset($staticParserInfo['routes'])) {
          foreach ($parserInfo['routes'] as $routeID => $stopTimes) {
            if (!isset($stopTimes['arrives']) && isset($staticParserInfo['routes'][$routeID])) {
              $parserInfo['routes'][$routeID] = $staticParserInfo['routes'][$routeID];
            }
          }
        } else {
          foreach ($parserInfo['routes'] as $routeID => $stopTimes) {
            if (!isset($stopTimes['arrives'])) {
              $parserInfo['routes'][$routeID]['arrives'] = 0;
            }
          }
        }
        
        if ($parserInfo) {
          if (!count($stopInfo)) {
            $stopInfo = $parserInfo;
          } else {
            foreach ($parserInfo['routes'] as $routeID => $stopTimes) {
              if (!isset($stopInfo['routes'][$routeID])) {
                $stopInfo['routes'][$routeID] = $stopTimes;
              } else {
                if (!isset($stopTimes['arrives']) || $stopTimes['arrives'] < $stopInfo['routes'][$routeID]['arrives']) {
                  $stopInfo['routes'][$routeID]['arrives'] = $stopTimes['arrives'];
                }
                if (!isset($stopTimes['predictions'])) {
                  $stopInfo['routes'][$routeID]['predictions'] = $stopTimes['predictions'];
                  
                } else if (count($stopTimes['predictions'])) {
                  $stopInfo['routes'][$routeID]['predictions'] = array_merge(
                    $stopInfo['routes'][$routeID]['predictions'], $stopTimes['predictions']);
                  
                  $stopInfo['routes'][$routeID]['predictions'] = array_unique($stopInfo['routes'][$routeID]['predictions']);
                  sort($stopInfo['routes'][$routeID]['predictions']);
                }
              }
            }
          }
        }
      }
      $cache->write(json_encode($stopInfo), $cacheName);
    }
    return $stopInfo;
  }

  public function getMapImageForStop($stopID, $width=270, $height=270) {
    $image = false;
    $parser = reset($this->parsersForStop($stopID));
    
    if ($parser['live']) {
      $image = $parser['live']->getMapImageForStop($stopID, $width, $height);
    }
    
    if (!$image && $parser['static']) {
      $image = $parser['static']->getMapImageForStop($stopID, $width, $height);
    }
    
    return $image;
  }

  public function getMapImageForRoute($routeID, $width=270, $height=270) {
    $image = false;
    $parser = $this->parserForRoute($routeID);
    
    if ($parser['live']) {
      $image = $parser['live']->getMapImageForRoute($routeID, $width, $height);
    }
    
    if (!$image && $parser['static']) {
      $image = $parser['static']->getMapImageForRoute($routeID, $width, $height);
    }
    
    return $image;
  }
  
  public function getRouteInfo($routeID, $time=null) {
    $routeInfo = array();
    $cacheName = "routeInfo.$routeID";
    $cache = self::getRoutesCache();
    
    if ($cache->isFresh($cacheName) && $time == null) {
      $routeInfo = json_decode($cache->read($cacheName), true);
      
    } else {
      $parser = $this->parserForRoute($routeID);
      
      if ($parser['live']) {
        $routeInfo = $parser['live']->getRouteInfo($routeID, $time);
        if (count($routeInfo)) {
          $routeInfo['live'] = true;
        }
      }
      
      if ($parser['static']) {
        $staticRouteInfo = $parser['static']->getRouteInfo($routeID, $time);
        
        if (!count($routeInfo)) {
          $routeInfo = $staticRouteInfo;
        
        } else if (count($staticRouteInfo)) {
          if (strlen($staticRouteInfo['name'])) {
            // static name is better
            $routeInfo['name'] = $staticRouteInfo['name'];
          }
          if (strlen($staticRouteInfo['description'])) {
            // static description is better
            $routeInfo['description'] = $staticRouteInfo['description'];
          }
          if ($staticRouteInfo['frequency'] != 0) { // prefer static
            $routeInfo['frequency'] = $staticRouteInfo['frequency'];
          }
          if (!count($routeInfo['stops'])) {
            $routeInfo['stops'] = $staticRouteInfo['stops'];
          
          } else {
            // Use the static first stop, not the prediction first stop
            // Use static stop names if available
            $firstStop = reset(array_keys($staticRouteInfo['stops']));
            $foundFirstStop = false;
            $moveToEnd = array();
            foreach ($routeInfo['stops'] as $stopID => &$stop) {
              if (!isset($staticRouteInfo['stops'][$stopID])) {
                // NextBus sometimes has _ar suffixes on it.  Try stripping them
                $parts = explode('_', $stopID);
                if (isset($staticRouteInfo['stops'][$parts[0]])) {
                  //error_log("Warning: static route does not have live stop id $stopID, using {$parts[0]}");
                  $stopID = $parts[0];
                }
              }
              
              if (isset($staticRouteInfo['stops'][$stopID])) {
                $stop['name'] = $staticRouteInfo['stops'][$stopID]['name'];
  
                if (!$stop['hasTiming'] && $staticRouteInfo['stops'][$stopID]['hasTiming']) {
                  $stop['arrives'] = $staticRouteInfo['stops'][$stopID]['arrives'];
                  if (isset($staticRouteInfo['stops'][$stopID]['predictions'])) {
                    $stop['predictions'] = $staticRouteInfo['stops'][$stopID]['predictions'];
                  } else {
                    unset($stop['predictions']);
                  }
                }
              } else {
                error_log("Warning: static route info does not have live stop id $stopID");
              }
              
              if ($foundFirstStop || TransitDataParser::isSameStop($stopID, $firstStop)) {
                $foundFirstStop = true;
              } else {
                $moveToEnd[$stopID] = $stop;
                unset($routeInfo['stops'][$stopID]);
              }
            }
            $routeInfo['stops'] += $moveToEnd;
          }
        }
      }
      
      if (count($routeInfo)) {
        $now = time();
        
        // Walk the stops to figure out which is upcoming
        $stopIDs     = array_keys($routeInfo['stops']);
        $firstStopID = reset($stopIDs);
        
        $firstStopPrevID  = end($stopIDs);
        if (TransitDataParser::isSameStop($firstStopID, $firstStopPrevID)) {
          $firstStopPrevID = prev($stopIDs);
        }
        
        foreach ($stopIDs as $index => $stopID) {
          if (!isset($routeInfo['stops'][$stopID]['upcoming'])) {
            $arrives = $routeInfo['stops'][$stopID]['arrives'];
      
            if ($stopID == $firstStopID) {
              $prevArrives = $routeInfo['stops'][$firstStopPrevID]['arrives'];
            } else {
              $prevArrives = $routeInfo['stops'][$stopIDs[$index-1]]['arrives'];
            }
      
            // Suppress any soonest stops which are more than 2 hours from now
            $routeInfo['stops'][$stopID]['upcoming'] = 
                (abs($arrives - $now) < $GLOBALS['siteConfig']->getVar('TRANSIT_MAX_ARRIVAL_DELAY')) && 
                $arrives <= $prevArrives;
          }
        }
        
        $routeInfo['lastupdate'] = $now;
      }
      if ($time == null) {
        $cache->write(json_encode($routeInfo), $cacheName);
      }
    }
    
    return $routeInfo;    
  }
  
  public function getRoutePaths($routeID) {
    $paths = array();
    
    $parser = $this->parserForRoute($routeID);
    
    if ($parser['live']) {
      $paths = $parser['live']->getRoutePaths($routeID);
    } else if ($parser['static']) {
      $paths = $parser['static']->getRoutePaths($routeID);
    }
    
    return $paths;
  }
  
  public function getRouteVehicles($routeID) {
    $vehicles = array();
    
    $parser = $this->parserForRoute($routeID);
    
    if ($parser['live']) {
      $vehicles = $parser['live']->getRouteVehicles($routeID);
    } else if ($parser['static']) {
      $vehicles = $parser['static']->getRouteVehicles($routeID);
    }
    
    return $vehicles;
  }
  
  public function getNews() {
    $allNews = array();
    
    foreach ($this->parsers as $parser) {
      $news = array();

      if ($parser['live']) {
        $news = $parser['live']->getNews();
      }
      
      if ($parser['static']) {
        $staticNews = $parser['static']->getNews();
        if (!count($news)) {
          $news = $staticNews;
        
        } else if (count($staticNews)) {
          $news = $news + $staticNews;
        }
      }
      $allNews += $news;
    }
    
    return $allNews;
  }

  private function getAllRoutes($time=null) {
    $allRoutes = array();
    $cacheName = 'allRoutes';
    $cache = self::getRoutesCache();
    
    if ($cache->isFresh($cacheName) && $time == null) {
      $allRoutes = json_decode($cache->read($cacheName), true);
      
    } else {
      foreach ($this->parsers as $parser) {
        $routes = array();
        
        if ($parser['live']) {
          $routes = $parser['live']->getRoutes($time);
        }
        
        if ($parser['static']) {
          $staticRoutes = $parser['static']->getRoutes($time);
          if (!count($routes)) {
            $routes = $staticRoutes;
          } else {
            foreach ($routes as $routeID => $routeInfo) {
              if (isset($staticRoutes[$routeID])) {
                if (!$routeInfo['running']) {
                  $routes[$routeID] = $staticRoutes[$routeID];
                } else {
                  // static name is better
                  $routes[$routeID]['name'] = $staticRoutes[$routeID]['name'];
                  $routes[$routeID]['description'] = $staticRoutes[$routeID]['description'];
                  
                  if ($staticRoutes[$routeID]['frequency'] != 0) {
                    $routes[$routeID]['frequency'] = $staticRoutes[$routeID]['frequency'];
                  }
                }
              }
            }
            // Pull in static routes with no live data
            foreach ($staticRoutes as $routeID => $staticRouteInfo) {
              if (!isset($routes[$routeID])) {
                $routes[$routeID] = $staticRouteInfo;
              }
            }
          }
        }
        $allRoutes += $routes;
      }
      if ($time == null) {
        $cache->write(json_encode($allRoutes), $cacheName);
      }
    }
    
    return $allRoutes;
  }
 
  public function getRoutes($time=null) {
    $routes = $this->getAllRoutes($time);

    // Remove routes that are not in service
    foreach ($routes as $routeID => $routeInfo) {
      if (!$routeInfo['inService']) {
        unset($routes[$routeID]);
      }
    }
    
    return $routes;
  }
  
  public function getInactiveRoutes($time=null) {
    $routes = $this->getAllRoutes($time);

    // Remove routes that are in service
    foreach ($routes as $routeID => $routeInfo) {
      if ($routeInfo['inService']) {
        unset($routes[$routeID]);
      }
    }
    
    return $routes;
  }

  private function parserForRoute($routeID) {
    foreach ($this->parsers as $parser) {
      if ($parser['live'] && $parser['live']->hasRoute($routeID)) {
        return $parser;
      }
      if ($parser['static'] && $parser['static']->hasRoute($routeID)) {
        return $parser;
      }
    }
    return array('live' => false, 'static' => false);
  }
  
  private function parsersForStop($stopID) {
    $parsers = array();
  
    foreach ($this->parsers as $parser) {
      if (($parser['live'] && $parser['live']->hasStop($stopID)) ||
          ($parser['static'] && $parser['static']->hasStop($stopID))) {
        $parsers[] = $parser;
      }
    }
    return $parsers;
  }
}


abstract class TransitDataParser {
  protected $args = array();
  protected $whitelist = false;
  
  private $routes    = array();
  private $stops     = array();
  private $overrides = array();
  
  static private $arrows = array(
    '1' => 'n',
    '2' => 'ne',
    '3' => 'e',
    '4' => 'se',
    '5' => 's',
    '6' => 'sw',
    '7' => 'w',
    '8' => 'nw',
  );
    
  public static function factory($class, $args, $overrides, $whitelist) {
    $parser = null;
    $parserClassFile = realpath_exists(LIB_DIR."/$class.php");
    if ($parserClassFile) {
      require_once $parserClassFile;
      $parser = new $class($args, $overrides, $whitelist);
    }
    return $parser;
  }
  
  function __construct($args, $overrides, $whitelist) {
    $this->args = $args;
    $this->overrides = $overrides;
    $this->whitelist = $whitelist ? $whitelist : false;
    
    $this->loadData();
  }
  
  protected function updatePredictionData($routeID) {
    // override if you want to break out loading of prediction data
  }
    
  public function getRouteVehicles($routeID) {
    // override if the parser has vehicle locations
    return array();
  }
  
  public function getNewsForRoutes() {
    // override if the parser can get news items
    return array();
  }
  
  abstract protected function loadData();
  
  abstract protected function isLive();

  //
  // Routes
  //

  protected function addRoute($route) {
    $id = $route->getID();

    if (isset($this->routes[$id])) {
      error_log(__FUNCTION__."(): Warning duplicate route '$id'");
      return;
    }
    $this->routes[$id] = $route;
  }
    
  protected function getRoute($id) {
    if (!isset($this->routes[$id])) {
      error_log(__FUNCTION__."(): Warning no such route '$id'");
      return false;
    }

    return $this->routes[$id];
  }
  
  // used to avoid warnings when looking for the right parser for a route
  public function hasRoute($id) {
    return isset($this->routes[$id]);
  }

  //
  // Stops
  //

  protected function addStop($stop) {
    $id = $stop->getID();

    if (isset($this->stops[$id])) {
      // This case seems to happen fairly often
      //error_log(__FUNCTION__."(): Warning duplicate stop '$id'");
      return;
    }
    $this->stops[$id] = $stop;
  }
    
  protected function getStop($id) {
    if (!isset($this->stops[$id])) {
      error_log(__FUNCTION__."(): Warning no such stop '$id'");
      return false;
    }

    return $this->stops[$id];
  }
  
  // used to avoid warnings when looking at the wrong agency
  public function hasStop($id) {
    return isset($this->stops[$id]);
  }
 
  protected function getMapIconUrlForRouteStop($routeID) {
    if($_SERVER['SERVER_NAME'] != 'localhost') {
      $iconURL = "http://".SERVER_HOST."/modules/transit/images/shuttle_stop_dot.png";
    } else {
      return $GLOBALS['siteConfig']->getVar('GOOGLE_CHART_API_URL').http_build_query(array(
        'chst' => 'd_simple_text_icon_left',
        'chld' => '|9|000|glyphish_target|12|'.$this->getRouteColor($routeID).'|FFF',
      ));
    }
  }
 
  protected function getMapIconUrlForStopPin($stopID) {
    if($_SERVER['SERVER_NAME'] != 'localhost') {
      $iconURL = "http://".SERVER_HOST."/modules/transit/images/shuttle_stop_pin.png";
    } else {
      return $GLOBALS['siteConfig']->getVar('GOOGLE_CHART_API_URL').http_build_query(array(
        'chst' => 'd_map_pin_icon',
        'chld' => 'bus|'.$GLOBALS['siteConfig']->getVar('TRANSIT_DEFAULT_ROUTE_COLOR'),
      ));
    }
  }
 
  protected function getMapIconUrlForRouteVehicle($routeID, $vehicle=null) {
    // same icon for every vehicle by default
    return $GLOBALS['siteConfig']->getVar('GOOGLE_CHART_API_URL').http_build_query(array(
      'chst' => 'd_map_pin_icon',
      'chld' => 'bus|'.$this->getRouteColor($routeID),
    ));
  }
 
  protected function getMapMarkersForVehicles($vehicles) {
    $query = '';
    
    if (count($vehicles)) {
      $firstVehicle = reset($vehicles);
    
      $markers = "icon:".$this->getMapIconUrlForRouteVehicle($firstVehicle['routeID']);
      foreach ($vehicles as $vehicle) {
        $markers .= "|{$vehicle['lat']},{$vehicle['lon']}";
      }
      $query .= '&'.http_build_query(array(
        'markers' => $markers,
      ));
    }
    
    return $query;
  }  
  
  protected function getDirectionForHeading($heading) {
    $arrowIndex = ($heading / 45) + 1.5;
    if ($arrowIndex > 8) { $arrowIndex = 8; }
    if ($arrowIndex < 0) { $arrowIndex = 0; }
    $arrowIndex = floor($arrowIndex);
    
    return self::$arrows[$arrowIndex];
}
  
  protected function getRouteColor($routeID) {
    return $GLOBALS['siteConfig']->getVar('TRANSIT_DEFAULT_ROUTE_COLOR');
  }

  //
  // Query functions
  // 
  
  public function getStopInfoForRoute($routeID, $stopID) {
    if (!isset($this->routes[$routeID])) {
      error_log(__FUNCTION__."(): Warning no such route '$routeID'");
      return array();
    }
  
    $this->updatePredictionData($routeID);
    
    $stopInfo = array();

    $now = TransitTime::getCurrentTime();
    $predictions = $this->routes[$routeID]->getPredictionsForStop($stopID, $now); 
    $stopInfo = array(
      'name'        => $this->stops[$stopID]->getName(),
      'description' => $this->stops[$stopID]->getDescription(),
      'coordinates' => $this->stops[$stopID]->getCoordinates(),
      'predictions' => $predictions['predictions'],
      'arrives'     => $predictions['arrives'],
      'live'        => $this->isLive(),
    );
    
    $this->applyStopInfoOverrides($stopID, $stopInfo);
    
    return $stopInfo;
  }
  
  public function getStopInfo($stopID) {
    if (!isset($this->stops[$stopID])) {
      error_log(__FUNCTION__."(): Warning no such stop '$stopID'");
      return array();
    }
  
    $now = TransitTime::getCurrentTime();

    $routePredictions = array();
    foreach ($this->routes as $routeID => $route) {
      if ($route->routeContainsStop($stopID)) {
        $this->updatePredictionData($route->getID());
        
        $routePredictions[$routeID] = $route->getPredictionsForStop($stopID, $now);
        $routePredictions[$routeID]['name'] = $this->getRoute($routeID)->getName();
        $routePredictions[$routeID]['live'] = $this->isLive();
      }
    }
    
    $stopInfo = array(
      'name'        => $this->stops[$stopID]->getName(),
      'description' => $this->stops[$stopID]->getDescription(),
      'coordinates' => $this->stops[$stopID]->getCoordinates(),
      'routes'      => $routePredictions,
    );
    
    $this->applyStopInfoOverrides($stopID, $stopInfo);

    return $stopInfo;
  }
  
  public function getMapImageForStop($id, $width=270, $height=270) {
    if (!isset($this->stops[$id])) {
      error_log(__FUNCTION__."(): Warning no such stop '$id'");
      return false;
    }
    
    $stop = $this->stops[$id];
    $coords = $stop->getCoordinates();
    $iconURL = $this->getMapIconUrlForStopPin($id);
    
    $query = http_build_query(array(
      'sensor'  => 'false',
      'size'    => "{$width}x{$height}",
      'markers' => "icon:$iconURL|{$coords['lat']},{$coords['lon']}",
    ));
    
    return $GLOBALS['siteConfig']->getVar('GOOGLE_STATIC_MAPS_URL').$query;
  }

  public function getMapImageForRoute($id, $width=270, $height=270) {
    if (!isset($this->routes[$id])) {
      error_log(__FUNCTION__."(): Warning no such route '$id'");
      return false;
    }
    
    $route = $this->routes[$id];
    $paths = $route->getPaths();
    $color = $this->getRouteColor($id);
    
    if (!count($paths)) {
      error_log(__FUNCTION__."(): Warning no path for route '$id'");
      return false;
    }
    
    $query = http_build_query(array(
      'sensor' => 'false',
      'size'   => "{$width}x{$height}",
    ));
  
    $now = TransitTime::getCurrentTime();
    if ($route->isRunning($now)) {
      $vehicles = $this->getRouteVehicles($id);
      $query .= $this->getMapMarkersForVehicles($vehicles);
    }
    
    foreach ($paths as $points) {
      foreach ($points as &$point) {
        $point = array_values($point);
      }
      $query .= '&'.http_build_query(array(
        'path' => 'weight:3|color:0x'.$color.'C0|enc:'.Polyline::encodeFromArray($points)
      ), 0, '&amp;');
    }
    
    return $GLOBALS['siteConfig']->getVar('GOOGLE_STATIC_MAPS_URL').$query;
  }

  public function routeIsRunning($routeID, $time=null) {
    if (!isset($this->routes[$routeID])) {
      error_log(__FUNCTION__."(): Warning no such route '$routeID'");
      return false;
    }
    
    $this->updatePredictionData($routeID);

    if (!isset($time)) {
      $time = TransitTime::getCurrentTime();
    }
    return $this->routes[$routeID]->isRunning($time);
  }
  
  public function getRoutePaths($routeID) {
    if (!isset($this->routes[$routeID])) {
      error_log(__FUNCTION__."(): Warning no such route '$routeID'");
      return array();
    }

    $route = $this->routes[$routeID];
    return $route->getPaths();
  }
  
  public function getRouteInfo($routeID, $time=null) {
    if (!isset($this->routes[$routeID])) {
      error_log(__FUNCTION__."(): Warning no such route '$routeID'");
      return array();
    }
    $this->updatePredictionData($routeID);

    if (!isset($time)) {
      $time = TransitTime::getCurrentTime();
    }
    $route = $this->routes[$routeID];

    $routeInfo = array(
      'agency'         => $route->getAgencyID(),
      'name'           => $route->getName(),
      'description'    => $route->getDescription(),
      'color'          => $this->getRouteColor($routeID),
      'live'           => $this->isLive(),
      'frequency'      => $route->getServiceFrequency($time),
      'running'        => $route->isRunning($time, $inService),
      'inService'      => $inService,
      'stopIconUrl'    => $this->getMapIconUrlForRouteStop($routeID),
      'vehicleIconUrl' => $this->getMapIconUrlForRouteVehicle($routeID),
      'stops'          => array(),
    );

    // Check if there are a valid services and segments
    // Add a minute to the time checking so we don't tell people about buses 
    // that are leaving
    
    $seenDirections = array();
    $directions = array();
    foreach ($route->getDirections() as $direction) {
      $directionNames = array();
      $directionStops = array();

      foreach ($route->getSegmentsForDirection($direction) as $segment) {
        if (!$segment->getService()->isRunning($time)) {
          continue;
        }
        
        $segmentName = $segment->getName();
        if (isset($segmentName)) {
          $directionNames[$segment->getID()] = $segmentName;
        }

        foreach ($segment->getStops() as $stopIndex => $stopInfo) {
          $stopID = $stopInfo['stopID'];
          
          $arrivalTime = null;
          if ($stopInfo['hasTiming']) {
            $arrivalTime = $segment->getNextArrivalTime($time, $stopIndex);
          }
          
          if (!isset($directionStops[$stopID])) {
            $directionStops[$stopID] = array(
              'name'      => $this->stops[$stopID]->getName(),
              'arrives'   => $arrivalTime,
              'hasTiming' => $stopInfo['hasTiming'],
            );
            if (isset($this->stops[$stopID])) {
              $directionStops[$stopID]['coordinates'] = $this->stops[$stopID]->getCoordinates();
            }
            if (isset($stopInfo['predictions'])) {
              $directionStops[$stopID]['predictions'] = $stopInfo['predictions'];
            }
            //error_log('Setting stop time to '.strftime("%H:%M:%S %Y/%m/%d", $arrives).' for '.$this->stops[$stopID]->getName());
          } else {
            $oldArrivalTime = $directionStops[$stopID]['arrives'];
            if ($arrivalTime > $time && ($arrivalTime < $oldArrivalTime || $oldArrivalTime < $time)) {
              $directionStops[$stopID]['arrives'] = $arrivalTime;
              //error_log('Replacing stop time '.strftime("%H:%M:%S %Y/%m/%d", $oldArrivalTime).' with '.strftime("%H:%M:%S %Y/%m/%d", $arrivalTime)." (".strftime("%H:%M:%S %Y/%m/%d", $time).') for stop '.$this->stops[$stopID]['name']);
            }
          }
        }
        
        $directions[$direction] = array(
          'names' => array_unique($directionNames),
          'stops' => $directionStops,
        );
      }
    }

    // Check if we can merge the directions together into one big loop
    if (count($directions) > 1) {
      $newDirections = array();
      $handled = array();
      foreach ($directions as $direction => &$info) {
        $directionStops = array_keys($info['stops']);
        $first = reset($directionStops);
        $last = end($directionStops);
        foreach ($directions as $testDirection => &$testInfo) {
          if ($direction != $testDirection && 
              !in_array($direction, $handled) && !in_array($testDirection, $handled)) {
            //error_log("Looking at directions '$direction' and '$testDirection'");
            $testDirectionStops = array_keys($testInfo['stops']);
            $testFirst = reset($testDirectionStops);
            $testLast = end($testDirectionStops);
            $stops = $info['stops'];
            $testStops = $testInfo['stops'];
            
            if (TransitDataParser::isSameStop($last, $testFirst)) {
              if ($last['arrives'] > $testFirst['arrives']) {
                TransitDataParser::removeLastStop($stops);
              } else {
                TransitDataParser::removeFirstStop($testStops);
              }
              //error_log("Collapsing '$direction' and '$testDirection'");
              $newDirections["$direction-$testDirection"] = array(
                'names' => array_unique($info['names'] + $testInfo['names']),
                'stops' => $stops + $testStops,
              );
              $handled[] = $testDirection;
              $handled[] = $direction;
              break;
              
            } else if (TransitDataParser::isSameStop($testLast, $first)) {
              if ($testLast['arrives'] > $first['arrives']) {
                TransitDataParser::removeLastStop($testStops);
              } else {
                TransitDataParser::removeFirstStop($stops);
              }
              //error_log("Collapsing '$testDirection' and '$direction'");
              $newDirections["$testDirection-$direction"] = array(
                'names' => array_unique($testInfo['names'] + $info['names']),
                'stops' => $testStops + $stops,
              );
              $handled[] = $testDirection;
              $handled[] = $direction;
              break;
              
            }
          }
        }
        if (!in_array($direction, $handled) && count($directions[$direction]['stops'])) {
          $newDirections[$direction] = $info;
        }
      }
      //error_log('NEW DIRECTIONS: '.print_r($newDirections, true));
      $directions = $newDirections;
    }

    $names = array();
    foreach ($directions as $direction => $info) {
      $routeInfo['stops'] += $info['stops'];
      $names = array_merge($names, $info['names']);
    }
    
    $routeInfo['frequency'] = round($routeInfo['frequency'] / 60, 0);
    //error_log(print_r($routeInfo, true));
    
    $this->applyRouteInfoOverrides($routeID, $routeInfo);
    
    return $routeInfo;
  }

  public function getRoutes($time=null) {
    if (!isset($time)) {
      $time = TransitTime::getCurrentTime();
    }

    $routes = array();
    $inService = false;
    foreach ($this->routes as $routeID => $route) {
      $this->updatePredictionData($routeID);
          
      $routes[$routeID] = array(
        'name'        => $route->getName(),
        'description' => $route->getDescription(),
        'color'       => $this->getRouteColor($routeID),
        'frequency'   => round($route->getServiceFrequency($time) / 60),
        'agency'      => $route->getAgencyID(),
        'live'        => $this->isLive(),
      );
      $routes[$routeID]['running'] = $route->isRunning($time, $inService);
      $routes[$routeID]['inService'] = $inService;
      
      $this->applyRouteInfoOverrides($routeID, $routes[$routeID]);
    }

    return $routes;
  }
  
  private function applyRouteInfoOverrides($routeID, &$routeInfo) {
    if (isset($this->overrides['route'])) {
      foreach ($this->overrides['route'] as $field => $overrides) {
        if (isset($overrides[$routeID])) {
          $routeInfo[$field] = $overrides[$routeID];
        }
      }
    }
  }
  
  private function applyStopInfoOverrides($stopID, &$stopInfo) {
    if (isset($this->overrides['stop'])) {
      foreach ($this->overrides['stop'] as $field => $overrides) {
        if (isset($overrides[$stopID])) {
          $stopInfo[$field] = $overrides[$stopID];
        }
      }
    }
  }
  
  public static function isSameStop($stopID, $compareStopID) {
    if ($stopID == $compareStopID) {
      return true;
    }
    if ($stopID == $compareStopID.'_ar') {
      return true;
    }
    if ($stopID.'_ar' == $compareStopID) {
      return true;
    }
    return false;
  }
  
  public static function removeLastStop(&$stops) {
    end($stops);
    unset($stops[key($stops)]);
  }
  
  public static function removeFirstStop(&$stops) {
    reset($stops);
    unset($stops[key($stops)]);
  }
}

//
// TransitTime -- compact time to reduce memory footprint
//

define('HOUR_MULTIPLIER', 10000);
define('MINUTE_MULTIPLIER', 100);

class TransitTime {   
  static $localTimezone = null;
  static $gmtTimezone = null;
  
  private static function getLocalTimezone() {
    if (!isset(self::$localTimezone)) {
      self::$localTimezone = new DateTimeZone(LOCAL_TIMEZONE);
    }
    return self::$localTimezone;
  }

  private static function getGMTTimezone() {
    if (!isset(self::$gmtTimezone)) {
      self::$gmtTimezone = new DateTimeZone('GMT');
    }
    return self::$gmtTimezone;
  }
  
  public static function getLocalDatetimeFromTimestamp($timestamp) {
    $datetime = new DateTime('@'.$timestamp, self::getGMTTimezone());
    $datetime->setTimeZone(self::getLocalTimezone()); 
    return $datetime;
  }

  static public function getCurrentTime() {
    return time();
    //return strtotime("01:45:00 11/3/2010");
  }

  private static function getComponents($tt) {
    $hours = floor($tt/HOUR_MULTIPLIER);
    $minutes = floor(($tt - $hours*HOUR_MULTIPLIER)/MINUTE_MULTIPLIER); 
    $seconds = $tt - $minutes*MINUTE_MULTIPLIER - $hours*HOUR_MULTIPLIER;
    
    return array($hours, $minutes, $seconds);
  }
  
  private static function createFromComponents($hours, $minutes, $seconds) {
    if ($seconds > 59) {
      $addMinutes = floor($seconds/60);
      $minutes += $addMinutes;
      $seconds -= $addMinutes*60;
    }
    if ($minutes > 59) {
      $addHours = floor($minutes/60);
      $hours += $addHours;
      $minutes -= $addHours*60;
    }
    
    return $hours*HOUR_MULTIPLIER + $minutes*MINUTE_MULTIPLIER + $seconds;
  }
  
  public static function createFromString($timeString) {
    list($hours, $minutes, $seconds) = explode(':', $timeString);
    
    $hours = intval($hours);
    $minutes = intval($minutes);
    $seconds = intval($seconds);
    
    return self::createFromComponents($hours, $minutes, $seconds);
  }
  
  public static function getString($tt) {
    list($hours, $minutes, $seconds) = self::getComponents($tt);
    
    return 
      str_pad($hours,   2, '0', STR_PAD_LEFT).':'.
      str_pad($minutes, 2, '0', STR_PAD_LEFT).':'.
      str_pad($seconds, 2, '0', STR_PAD_LEFT);
  }
  
  public static function getTimestampOnDate($tt, $dateTimestamp) {
    $date = self::getLocalDatetimeFromTimestamp($dateTimestamp);

    list($hours, $minutes, $seconds) = explode(':', $date->format('G:i:s'));
    $dateTT = self::createFromComponents($hours, $minutes, $seconds);
  
    list($hours, $minutes, $seconds) = self::getComponents($tt);

    if ($hours > 23) {
      $date->modify('+1 day'); // will be for the next day
    }
    
    $date->setTime($hours, $minutes, $seconds);
    
    return $date->format('U');
  }
  
  public static function compare($tt1, $tt2) {
    //error_log("Comparing ".self::getString($tt1)." to ".self::getString($tt2));
    if ($tt1 == $tt2) {
      return 0;
    } else {
      return $tt1 < $tt2 ? -1 : 1;
    }
  }
  
  public static function addSeconds(&$tt, $addSeconds) {
    list($hours, $minutes, $seconds) = self::getComponents($tt);
    $tt = self::createFromComponents($hours, $minutes, $seconds+$addSeconds);
  }
  
  public function addMinutes(&$tt, $addMinutes) {
    list($hours, $minutes, $seconds) = self::getComponents($tt);
    $tt = self::createFromComponents($hours, $minutes+$addMinutes, $seconds);
  }
  
  public function addHours(&$tt, $addHours) {
    list($hours, $minutes, $seconds) = self::getComponents($tt);
    $tt = self::createFromComponents($hours+$addHours, $minutes, $seconds);
  }
  
  public function addTime(&$tt, $addTT) {
    list($hours,    $minutes,    $seconds)    = self::getComponents($tt);
    list($addHours, $addMinutes, $addSeconds) = self::getComponents($addTT);
    
    $tt = self::createFromComponents($hours+$addHours, $minutes+$addMinutes, $seconds+$addSeconds);
  }
  
  public static function isTimeInRange($timestamp, $fromTT, $toTT) {
    $time = self::getLocalDatetimeFromTimestamp($timestamp);
    
    $tt = TransitTime::createFromString($time->format('G:i:s'));
    
    $afterStart = TransitTime::compare($fromTT, $tt) <= 0;
    $beforeEnd  = TransitTime::compare($toTT, $tt) >= 0;
    $inRange = $afterStart && $beforeEnd;
    
    //error_log(TransitTime::getString($tt)." is ".($inRange ? '' : 'not ')."in range ".TransitTime::getString($fromTT).' - '.TransitTime::getString($toTT));
    return $inRange;
  }
}

//
// Routes
//

class TransitRoute {
  private $id = null;
  private $name = null;
  private $description = null;
  private $agencyID = null;
  private $directions = array();
  private $viewAsLoop = false;
  
  function __construct($id, $agencyID, $name, $description, $viewAsLoop=false) {
    $this->id = $id;
    $this->name = $name;
    $this->description = $description;
    $this->agencyID = $agencyID;
    $this->viewAsLoop = $viewAsLoop;
    $this->paths = array();
  }
  
  public function getID() {
    return $this->id;
  }
    
  public function getName() {
    return $this->name;
  }
    
  public function getDescription() {
    return $this->description;
  }
    
  public function getAgencyID() {
    return $this->agencyID;
  }
    
  public function addSegment(&$segment) {
    $direction = $segment->getDirection();
  
    if (!isset($this->directions[$direction])) {
      $this->directions[$direction] = array(
        'segments' => array(),
      );
    }
    
    $segmentID = $segment->getID();
    if (isset($this->directions[$direction]['segments'][$segmentID])) {
      error_log(__FUNCTION__."(): Warning duplicate segment '$segmentID' for route '{$this->name}'");
    }
    
    $this->directions[$direction]['segments'][$segmentID] = $segment;
  }
  
  public function getDirections() {
    if ($this->viewAsLoop) {
      return array('loop');
    } else {
      return array_keys($this->directions);
    }
  }
  
  public function getSegmentsForDirection($direction) {
    if ($this->viewAsLoop) {
      $segments = array();
      foreach ($this->directions as $directionID => $direction) {
        $segments += $direction['segments'];
      }
      return $segments;
    } else {
      return $this->directions[$direction]['segments'];
    }
  }
  
  public function setStopTimes($directionID, $stopID, $arrivesOffset, $departsOffset) {
    if (!isset($this->directions[$directionID])) {
      error_log("Warning no direction $directionID for route {$this->id}");
    }
    foreach ($this->directions[$directionID]['segments'] as &$segment) {
      $segment->setStopTimes($stopID, $predictions, $arrivesOffset, $departsOffset);
    }
  }
  
  public function setStopPredictions($directionID, $stopID, $predictions) {
    if (!isset($this->directions[$directionID])) {
      error_log("Warning no direction $directionID for route {$this->id}");
    }
    foreach ($this->directions[$directionID]['segments'] as &$segment) {
      $segment->setStopPredictions($stopID, $predictions);
    }
  }
  
  public function getStops() {
    $stops = array();
    foreach ($this->directions as $directionID => $direction) {
      foreach ($direction['segments'] as $segment) {
        foreach ($segment->getStops() as $stopInfo) {
          $stops[] = $stopInfo;
        }
      }
    }
    return $stops;
  }
  
  public function routeContainsStop($stopID) {
    foreach ($this->directions as $directionID => $direction) {
      foreach ($direction['segments'] as $segment) {
        foreach ($segment->getStops() as $stopInfo) {
          if ($stopInfo['stopID'] == $stopID) {
            return true;
          }
        }
      }
    }
    return false;
  }
  
  public function getPredictionsForStop($stopID, $time) {
    $predictions = array(
      'running' => $this->isRunning($time),
    );
    foreach ($this->directions as $directionID => $direction) {
      foreach ($direction['segments'] as $segment) {
        foreach ($segment->getStops() as $stopIndex => $stopInfo) {
          if ($stopInfo['stopID'] == $stopID && $stopInfo['hasTiming']) {
            $arrivalTime = $segment->getNextArrivalTime($time, $stopIndex);
            
            if (!isset($predictions['arrives']) || 
                $arrivalTime < $predictions['arrives']) {
              $predictions['arrives'] = $arrivalTime;
            }
            if (isset($stopInfo['predictions'])) {
              if (!isset($predictions['predictions'])) {
                $predictions['predictions'] = array();
              }
              
              $predictions['predictions'] = array_merge(
                $predictions['predictions'], $stopInfo['predictions']);
                
              sort($predictions['predictions']);
            }
            break;
          }
        }
      }
    }

    return $predictions;
  }
  
  public function hasPredictions() {
    foreach ($this->directions as $direction) {
      foreach ($direction['segments'] as $segment) {
        if ($segment->hasPredictions()) {
          return true;
        }
      }
    }
    return false;
  }
  
  public function isRunning($time, &$inService=null, &$runningSegmentNames=null) {
    $isRunning = false;
    $inService = false;
    $runningSegmentNames = array();
    
    // Check if there is a valid segment
    $servicesForDate = null;
    
    //error_log(__FUNCTION__."(): Looking at route {$this->id} ({$this->name})");
    foreach ($this->directions as $direction) {
      foreach ($direction['segments'] as $segment) {
        //error_log("    Looking at segment $segment");
        if ($segment->getService()->isRunning($time)) {
          $inService = true;
          
          if ($segment->isRunning($time)) {
            $name = $segment->getName();
            if (isset($name) && !isset($runningSegmentNames[$name])) {
              //error_log("   Route {$this->name} has named running segment '$name' (direction '$direction')");
              $runningSegmentNames[$name] = $name;
            }
            $isRunning = true;
          }
        }
      }
    }
    
    $runningSegmentNames = array_values($runningSegmentNames);
    return $isRunning;
  }
  
  private function segmentsUseFrequencies() {
    foreach ($this->directions as $direction) {
      foreach ($direction['segments'] as $segment) {
        return $segment->hasFrequencies();
      }
    }
    return false;
  }
  
  private function getFirstStopIDAndDirection() {
    foreach ($this->directions as $directionID => $direction) {
      foreach ($direction['segments'] as $segment) {
        foreach ($segment->getStops() as $stopInfo) {
          return array($stopInfo['stopID'], $directionID);
        }
      }
    }
    return array(false, false);
  }
  
  public function getServiceFrequency($time) {
    // Time between shuttles at the same stop
    $frequency = 0;
    
    if ($this->segmentsUseFrequencies()) {
      foreach ($this->directions as $direction) {
        foreach ($direction['segments'] as $segment) {
          if ($segment->isRunning($time)) {
            $frequency = $segment->getFrequency($time);
            if ($frequency > 0) { break; }
          }
          if ($frequency > 0) { break; }
        }
        if ($frequency > 0) { break; }
      }
    } else {
      // grab the first stop and check how often vehicles arrive at it
      list($stopID, $directionID) = $this->getFirstStopIDAndDirection();
            
      if ($stopID) {
        $arrivalTimes = array();
        
        foreach ($this->directions[$directionID]['segments'] as $segment) {
          if ($segment->getService()->isRunning($time)) {
            $segmentArrivalTimes = $segment->getArrivalTimesForStop($stopID);
            $arrivalTimes = array_merge($arrivalTimes, $segmentArrivalTimes);
          }
        }
        $arrivalTimes = array_unique($arrivalTimes);
        sort($arrivalTimes);
      
        for ($i = 0; $i < count($arrivalTimes); $i++) {
          if ($arrivalTimes[$i] > $time) {
            if (isset($arrivalTimes[$i+1])) {
              $frequency = $arrivalTimes[$i+1] - $arrivalTimes[$i];
            } else if (isset($arrivalTimes[$i-1])) {
              $frequency = $arrivalTimes[$i] - $arrivalTimes[$i-1];
            }
          }
          if ($frequency > 0 && $frequency < MAX_ARRIVAL_DELAY) { break; }
        }
      }
      if ($frequency == 0) { $frequency = 60*60; } // default to 1 hour
    }
    return $frequency;
  }
  
  public function addPath($path) {
    $this->paths[] = $path;
  }
  
  public function getPaths() {
    $paths = array();
    foreach ($this->paths as $path) {
      $paths[$path->getID()] = $path->getPoints();
    }
    return $paths;
  }
}

//
// Services
//

class TransitService {
  private $id = null;
  private $dateRanges = array();
  private $exceptions = array();
  private $additions = array();
  
  private $live = false;
  
  function __construct($id, $live=false) {
    $this->id = $id;
    $this->live = $live;
  }
  
  public function getID() {
    return $this->id;
  }
  public function addDateRange($firstDate, $lastDate, $weekdays) {
    $this->dateRanges[] = array(
      'first'    => intval($firstDate),
      'last'     => intval($lastDate),
      'weekdays' => $weekdays,
    );
  }
  
  public function addExceptionDate($date) {
    $this->exceptions[] = intval($date);
  }
  
  public function addAdditionalDate($date) {
    $this->additions[] = intval($date);    
  }
  
  public function isRunning($time) {
    if ($this->live) { return true; }
  
    $datetime = TransitTime::getLocalDatetimeFromTimestamp($time);
    
    $hour = intval($datetime->format('H'));
    if ($hour < 5) {
      $datetime->modify('-1 day'); // before 5am is part of the previous day
    }
    
    $date = intval($datetime->format('Ymd'));
    $dayName = $datetime->format('l');
    
    $insideValidDateRange = false;
    foreach ($this->dateRanges as $dateRange) {
      $week  = $dateRange['weekdays'];
      
      if ($date >= $dateRange['first'] && $date <= $dateRange['last'] && $week[strtolower($dayName)]) {
        $insideValidDateRange = true;
        break;
      }
    }
    $isException  = in_array($date, $this->exceptions);
    $isAddition   = in_array($date, $this->additions);

    //error_log("service $service is ".($isAddition || ($inValidDateRange && !$isException) ? '' : 'not ').'running');
    return $isAddition || ($insideValidDateRange && !$isException);
  }
}

//
// Segments
//

class TransitSegment {
  private $id = null;
  private $name = null;
  private $service = null;
  private $direction = null;
  private $stops = array();
  private $stopsSorted = false;
  private $frequencies = null;
  
  private $hasPredictions = false;
  
  function __construct($id, $name, $service, $direction) {
    $this->id = $id;
    $this->name = $name;
    $this->service = $service;
    $this->direction = $direction;
  }
  
  public function getID() {
    return $this->id;
  }
  
  public function getName() {
    return $this->name;
  }

  public function getDirection() {
    return $this->direction;
  }

  public function getService() {
    return $this->service;
  }

  public function addFrequency($firstTT, $lastTT, $frequency) {
    if (!isset($this->frequencies)) {
      $this->frequencies = array();
    }
        
    $this->frequencies[] = array(
      'start'     => $firstTT,
      'end'       => $lastTT,
      'frequency' => intval($frequency),
    );
  }
  
  public function hasFrequencies() {
    return isset($this->frequencies);
  }
  
  public function getFrequency($time) {
    $frequency = false;
    
    if (isset($this->frequencies)) {
      foreach ($this->frequencies as $index => $frequencyInfo) {
        if (TransitTime::isTimeInRange($time, $frequencyInfo['start'], $frequencyInfo['end'])) {
          $frequency = $frequencyInfo['frequency'];
          break;
        } else if (!$frequency) {
          $frequency = $frequencyInfo['frequency'];
        }
      }
    }
    return $frequency;
  }
  
  public function addStop($stopID, $sequenceNumber) {
    $this->stops[] = array(
      'stopID'    => $stopID,
      'i'         => intval($sequenceNumber),
      'hasTiming' => false,
    );
    $this->stopsSorted = false;
  }
  
  private function getIndexForStop($stopID) { 
    foreach ($this->stops as $index => $stop) {
      if ($stopID == $stop['stopID']) {
        return $index;
      }
    }
    return false;
  }

  public function setStopTimes($stopID, $arrivesTT, $departsTT) {
    $index = $this->getIndexForStop($stopID);
    if ($index !== false) {
      $this->stops[$index]['arrives'] = $arrivesTT;
      $this->stops[$index]['departs'] = $departsTT;
      $this->stops[$index]['hasTiming'] = true;
    }
  }
  
  public function setStopPredictions($stopID, $predictions) {
    $index = $this->getIndexForStop($stopID);
    if ($index !== false) {
      if (!$this->hasPredictions && count($predictions)) {
        $this->hasPredictions = true;
      }
      $this->stops[$index]['predictions'] = $predictions;
      $this->stops[$index]['hasTiming'] = count($predictions) > 0;
    }
  }
  
  private static function sortStops($a, $b) {
    if ($a["i"] == $b["i"]) { 
      return 0; 
    }
    return ($a["i"] < $b["i"]) ? -1 : 1;
  }
  
  private function sortStopsIfNeeded() {
    if (!$this->stopsSorted) {
      usort($this->stops, array(get_class($this), 'sortStops'));
      $this->stopsSorted = true;
    }
  }
  
  public function getStops() {
    $this->sortStopsIfNeeded();
    return $this->stops;
  }
  
  public function hasPredictions() {
    return $this->hasPredictions;
  }
  
  public function isRunning($time) {
    $this->sortStopsIfNeeded();

    if ($this->hasPredictions) {
      return true; // live service with predictions
    
    } else if ($this->service->isRunning($time)) {
      if (isset($this->frequencies)) {
        foreach ($this->frequencies as $index => $frequencyInfo) {
          if (TransitTime::isTimeInRange($time, $frequencyInfo['start'], $frequencyInfo['end'])) {
            return true;
          }
        }
      } else {
        $firstStop = reset($this->stops);
        $lastStop  = end($this->stops);
        
        if (isset($firstStop['arrives'], $lastStop['departs'])) {
          if (TransitTime::isTimeInRange($time, $firstStop['arrives'], $lastStop['departs'])) {
            return true;
          }
        }
      }
    }
    
    return false;
  }
  
  public function getArrivalTimesForStop($stopID=null) {
    $arrivalTimes = array(); 
    $index = 0;
    if (isset($stopID)) {
      $index = $this->getIndexForStop($stopID);
    }
    
    if ($index !== false && isset($this->stops[$index])) {
      $now = TransitTime::getCurrentTime();
      $stop = $this->stops[$index];
      
      if (isset($stop['predictions']) && count($stop['predictions'])) {
        foreach ($stop['predictions'] as $prediction) {
          $arrivalTimes[] = $prediction + $now;
        }
      } else if (isset($stop['arrives'])) {
        $arrivalTimes[] = TransitTime::getTimestampOnDate($stop['arrives'], $now);
      }
    }
    return $arrivalTimes;
  }
  
  public function getNextArrivalTime($time, $stopIndex) {
    $this->sortStopsIfNeeded();

    $arrivalTime = 0; // noticeable error state

    $stop = $this->stops[$stopIndex];

    if (isset($this->frequencies)) {
      $firstFrequency = reset($this->frequencies);
      
      $firstLoopStopTime = $firstFrequency['start'];
      TransitTime::addTime($firstLoopStopTime, $stop['arrives']);
      
      $arrivalTime = TransitTime::getTimestampOnDate($firstLoopStopTime, $time);
      //error_log("Stop {$stop['stopID']} default arrival time will be ".$firstLoopStopTime->getString()." start is ".$firstFrequency['range']->getStart()->getString()." offset is ".$stop['arrives']->getString());

      $foundArrivalTime = false;
      foreach ($this->frequencies as $frequencyInfo) {
        $currentTT = $frequencyInfo['start']; // loop start
        TransitTime::addTime($currentTT, $stop['arrives']); // stop offset from loop start
        
        while (TransitTime::compare($currentTT, $frequencyInfo['end']) <= 0) {
          $testTime = TransitTime::getTimestampOnDate($currentTT, $time);
          //error_log("Looking at ".$currentTT->getString()." is ".($testTime > $time ? 'after now' : 'before now'));
          if ($testTime > $time && (!$foundArrivalTime || $testTime < $arrivalTime)) { 
            $arrivalTime = $testTime; 
            $foundArrivalTime = true;
            break;
          }
          TransitTime::addSeconds($currentTT, $frequencyInfo['frequency']);
        }
      }
      
    } else if ($this->hasPredictions && count($stop['predictions'])) {
      $now = TransitTime::getCurrentTime();
      
      foreach ($stop['predictions'] as $prediction) {
        $testTime = $now + $prediction;
        if ($testTime > $time) {
          $arrivalTime = $testTime;
          break;
        }
      }
    
    } else if (isset($stop['arrives'])) { 
      $arrivalTime = TransitTime::getTimestampOnDate($stop['arrives'], $time);
    }
    
    return $arrivalTime;
  }
}

//
// Stops
//

class TransitStop {
  private $id = null;
  private $name = null;
  private $description = null;
  private $latitude = null;
  private $longitude = null;
  
  function __construct($id, $name, $description, $latitude, $longitude) {
    $this->id = $id;
    $this->name = $name;
    $this->description = $description;
    $this->latitude = floatVal($latitude);
    $this->longitude = floatVal($longitude);
  }
  
  public function getID() {
    return $this->id;
  }
  
  public function getName() {
    return $this->name;
  }
  
  public function getDescription() {
    return $this->description;
  }
  
  public function getCoordinates() {
    return array(
      'lat' => $this->latitude, 
      'lon' => $this->longitude,
    );
  }
}


//
// Paths
//

class TransitPath {
  private $id = null;
  private $points = array();
  
  function __construct($id, $points) {
    $this->id = $id;
    
    $pathPoints = array();
    foreach ($points as &$point) {
      $pathPoints[] = array(
        'lat' => floatVal(reset($point)),
        'lon' => floatVal(end($point)),
      );
    }
    $this->points = $pathPoints;
  }
  
  public function getID() {
    return $this->id;
  }
  
  public function getPoints() {
    return $this->points;
  }
}
