<?php

require_once realpath(LIB_DIR.'/feeds/html2text.php');
require_once realpath(LIB_DIR.'/DiskCache.php');

define('HOURS_UNAVAILABLE_STRING', '');

class Libraries {
  private static $cache = null;
  private static $daysOfWeek = array(
    'Monday'    => 1,
    'Mon'       => 1,
    'Tuesday'   => 2,
    'Tue'       => 2,
    'Tues'      => 2,
    'Wednesday' => 3,
    'Weds'      => 3,
    'Thursday'  => 4,
    'Thu'       => 4,
    'Thurs'     => 4,
    'Friday'    => 5,
    'Fri'       => 5,
    'Saturday'  => 6,
    'Sat'       => 6,
    'Sunday'    => 7,
    'Sun'       => 7,
  );
  private static $timezone = null;
  
  private static function getTimezone() {
    if (!isset(self::$timezone)) {
      self::$timezone = new DateTimeZone($GLOBALS['siteConfig']->getVar('LOCAL_TIMEZONE'));
    }
    return self::$timezone;
  }

  private static function getLibraryCache() {
    if (!isset(self::$cache)) {
      self::$cache = new DiskCache(
        $GLOBALS['siteConfig']->getVar('LIB_CACHE_DIR'), 
        $GLOBALS['siteConfig']->getVar('LIB_DIR_CACHE_TIMEOUT'), TRUE);
      self::$cache->preserveFormat();
      self::$cache->setSuffix('.xml');
    }
    
    return self::$cache;
  }

  private static function parseXML($contents) {
    return simplexml_load_string($contents, "SimpleXMLElement", LIBXML_NOCDATA);
  }

  private static function query($cacheName, $urlPrefix, $urlSuffix='') {
    $cache = self::getLibraryCache();
    $xml = null;
    
    if ($cache->isFresh($cacheName)) {
      $xml = self::parseXML($cache->read($cacheName));
      
    } else {
      $url = $GLOBALS['siteConfig']->getVar($urlPrefix).$urlSuffix;
    
      $contents = file_get_contents($url);
      error_log("LIBRARIES DEBUG: " . $url);
      if ($contents == "") {
        error_log("Failed to read contents from $url, reading expired cache");
        $xml = self::parseXML($cache->read($cacheName));
        
      } else {
        $xml = self::parseXML($contents, "SimpleXMLElement", LIBXML_NOCDATA);
        if (!$xml) {
          error_log("Failed to parse contents from $url, reading expired cache");
          $xml = self::parseXML($cache->read($cacheName));
        
        } else {
          $cache->write($contents, $cacheName);
        }
      }
    }
    return $xml;
  }
  
  private static function stripHTML($field, $value) {
    if (!is_string($value)) { 
      error_log(__FUNCTION__."(): field value is not a string: ".print_r($value, true));
      return '';
    }
    
    // We accept two types of data:
    // 1: urls (starts with 'http')
    // 2: text without html links in it
    //
    // The reason for this is that our touch interfaces can't handle small tappable
    // targets, such as links in the middle of blocks of small text
    
    if (strpos($value, 'http://') === FALSE && strpos($value, 'https://') === FALSE) {
      $value = HTML2TEXT(preg_replace(';<br\s*/>\s*\n?;', " \n", trim($value))); // not a url
      
    } else if (strpos($value, '<a ') !== FALSE && strpos($value, 'href=') !== FALSE) {
      error_log("Warning: skipped HTML with links in field '$field'".
        (isset($GLOBALS['librariesDebugEntryName']) ? " in {$GLOBALS['librariesDebugEntryName']}" : ''));
      $value = '';  // skip fields which have html links 
    } 
    
    return $value;
  }
  
  private static function formatField($field, $value) {
    switch ($field) {
      case 'hoursofoperation':
        $hours = array();
        $hoursStrings = array();
        foreach ($value as $entry) {
          if (isset($entry->dailyhours)) {
            foreach ($entry->dailyhours as $dayHours) {
              $date = $dayHours->date[0];
              $datetime = new DateTime(
                substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date, 6, 2), 
                self::getTimezone());

              $hours[] = array(
                'date'  => strval($date),
                'day'   => $datetime->format('l'),
                'hours' => strval($dayHours->hours[0]),
              );
            }            
          } else if (isset($entry[0]) && strval($entry[0])) {
            // a text description of the hours
            $hoursStrings[] = self::stripHTML($field, strval($entry[0]));
          }
        }
        
        if (count($hours)) {
          return $hours;
          
        } else if (count($hoursStrings)) {
          return $hoursStrings;
          
        } else {
          return array(HOURS_UNAVAILABLE_STRING);
        }
        break;
        
      case 'phonenumber':
        $value = trim($value);
        if (preg_match(';^[0-9]{3}-[0-9]{4}$;', $value)) {
          $value = $GLOBALS['siteConfig']->getVar('LOCAL_AREA_CODE').'-'.$value;
        }
        break;
    }
    return self::stripHTML($field, $value);
  }
  
  private static function getField($obj, $fields, $default='') {
    $lastField = is_array($fields) ? end($fields) : $fields;
    $exists = true;
    $value = $obj;
    
    if (!is_array($fields)) {
      $fields = array($fields);
    }
    
    foreach ($fields as $field) {
      if (isset($value->$field)) {
        $value = $value->$field;
        
      } else if (isset($value[$field])) {
        $value = $value[$field];
        
      } else {
        $exists = false;
        break;
      }
    }
  
    switch ($lastField) {
      case 'hoursofoperation':
        if ($exists) {
          return self::formatField($lastField, $value);
        } else {
          return array(HOURS_UNAVAILABLE_STRING);
        }
        break;
      
      case 'phonenumber':
        $phoneNumbers = array();
        if ($exists) {
          foreach ($value as $phonenumber) {
            if (isset($phonenumber['mobiledisplay']) && $phonenumber['mobiledisplay'] == 'false') { 
              continue;
            }
            
            $phoneNumbers[] = array(
              'description' => self::formatField('description', (string)$phonenumber->description[0]),
              'number'      => self::formatField($field,        (string)$phonenumber->number[0]),
            );
          }
        }
        return $phoneNumbers;
      
      case 'url':
        if ($exists) {
          return self::formatField($lastField, (string)$value);
        }
        break;
      
      case 'format':
        $formats = array();
        if ($exists) {
          foreach ($value as $format) {
            $attributes = $format->attributes();
            if (isset($attributes['type'], $attributes['type'][0])) {
              $formats['type']       = strval($attributes['type'][0]);
              $formats['typeDetail'] = strval($format);
            } else {
              $formats['formatDetail'] = strval($format);
            }
          }
        }
        return $formats;
      
      case 'identifier':
        $availability = array();
        if ($exists) {
          foreach ($value as $identifier) {
            $entry = array(
              'type' => '',
              'typeDetail' => strval($identifier[0]),
            );
            $attributes = $identifier->attributes();
            if (isset($attributes['type'], $attributes['type'][0])) {
              $entry['type'] = strval($attributes['type'][0]);
            }
            $availability[] = $entry;
          }
        }
        return $availability;
      
      case 'authorlink':
        if ($exists && isset($value[0])) {
          return urldecode((string)$value[0]);
        }
        break;
      
      default: 
        if ($exists && isset($value[0])) {
          return self::formatField($lastField, (string)$value[0]);
        }
        break;
    }
    return $default;
  }
  
  
  private static function getDateTimeForTimeString($timeString) {
    if ($timeString == '12' || $timeString == 'noon') {
      $timeString = '12pm';
    }
    if ($timeString == 'midnight') {
      $timeString = '12am';
    }
    //error_log($timeString);
    return new DateTime($timeString, self::getTimezone());
  }
  
  private static function isOpen($hours) {
    $now = time();
    
    $todaysHours = self::hoursOpenToday($hours);
    foreach ($todaysHours as $hoursString) {
      if (preg_match_all(';([0-9:]+(am|pm)?|noon|midnight)\s*-\s*([0-9:]+(am|pm)?|noon|midnight);', $hoursString, $matches)) {
        foreach ($matches[0] as $index => $match) {
          $start = self::getDateTimeForTimeString($matches[1][$index]);
          $end   = self::getDateTimeForTimeString($matches[3][$index]);
          
          if (intval($start->format('U')) <= $now && $now <= intval($end->format('U'))) {
            return true;
          }          
        }
      }
    }
    return false;
  }
  
  private static function hoursOpenToday($hours) {
    $now = new DateTime('now', self::getTimezone());
    $today = $now->format('Ymd');
    $dayOfWeek = intval($now->format('N'));

    $hoursOpenToday = array();

    foreach ($hours as $dateHours) {
      if (is_array($dateHours)) {
        if ($dateHours['date'] == $today) {
          $hoursOpenToday[] = $dateHours['hours'];
        }
      } else {
        $re = ';([A-Za-z]+).?\s*-\s*([A-Za-z]+).?\s+,?([0-9:]+(am|pm)?\s*-\s*[0-9:]+(am|pm)?);';
        if (preg_match($re, $dateHours, $matches)) {
          if (isset($matches[1], $matches[2])) {
            $first = strval($matches[1]);
            $last  = strval($matches[2]);
            if (isset(self::$daysOfWeek[$first], self::$daysOfWeek[$last]) && 
                self::$daysOfWeek[$first] <= $dayOfWeek && $dayOfWeek <= self::$daysOfWeek[$last]) {
              $hoursOpenToday[] = $matches[3];
            }
          }
        }
      }
    }
    if (!count($hoursOpenToday)) {
      $hoursOpenToday[] = 'closed';
    }
    return $hoursOpenToday;
  }
  
  private static function hoursOpenTodayString($hours) {
    return implode(', ', self::hoursOpenToday($hours));
  }
  
  private static function getInstitutionsByType($type=null) {
    $xml = self::query('librariesInfo', 'URL_LIBRARIES_INFO');
      
    $institutions = array();
    
    foreach ($xml->institution as $institution) {
      if (isset($type) && $type != self::getField($institution, 'type')) { continue; }
      //error_log(print_r($institution, true));

      $GLOBALS['librariesDebugEntryName'] = 
        self::getField($institution, 'type')." '".self::getField($institution, 'primaryname')."'";
    
      $hours = self::getField($institution, 'hoursofoperation');

      $institutions[] = array(
        'name'         => self::getField($institution, 'name'),
        'primaryname'  => self::getField($institution, 'primaryname'),
        'id'           => self::getField($institution, 'id'),
        'type'         => self::getField($institution, 'type'),
        'address'      => self::getField($institution, array('location', 'address')),
        'latitude'     => self::getField($institution, array('location', 'latitude')),
        'longitude'    => self::getField($institution, array('location', 'longitude')),
        'hrsOpenToday' => self::hoursOpenTodayString($hours),
        'isOpenNow'    => self::isOpen($hours),
      );
      //error_log(print_r(end($institutions), true));
    }
    return $institutions;
  }
  
  private static function getInstitutionShortInfo($type) {
    $xml = self::query('librariesInfo', 'URL_LIBRARIES_INFO');
      
    $institutions = array();
    
    foreach ($xml->institution as $institution) {
      if ($type != 'all' && $type != self::getField($institution, 'type')) { continue; }
      
      $GLOBALS['librariesDebugEntryName'] = 
        self::getField($institution, 'type')." '".self::getField($institution, 'primaryname')."'";
          
      $institutions[] = array(
        'name'         => self::getField($institution, 'name'),
        'id'           => self::getField($institution, 'id'),
        'type'         => $type,
        'isOpenNow'    => self::isOpen(self::getField($institution, 'hoursofoperation')),
      );
    }
    return $institutions;    
  }
  
  private static function getInstitutionDetails($type, $id, $preferredName) {
    $urlBase = ($type == 'library') ? 'URL_LIB_DETAIL_BASE' : 'URL_ARCHIVE_DETAIL_BASE';
  
    $xml = self::query("$type-{$id}", $urlBase, $id);
    
    $GLOBALS['librariesDebugEntryName'] = 
      self::getField($xml, 'type')." '".self::getField($xml, array('names', 'primaryname'))."'";
    
    //error_log(print_r($institution, true));
    $primaryName = self::getField($xml, array('names', 'primaryname'));
    if ($preferredName != $primaryName) {
      // Make sure name is valid
      $foundName = false;
      if (isset($xml->names->alternatename)) {
        foreach ($xml->names->alternatename as $name) {
          if (strval($name[0]) == $preferredName) {
            $foundName = true;
          }
        }
      }      
      if (!$foundName) {
        $preferredName = $primaryName;
      }
    }
    
    $hours = self::getField($xml, 'hoursofoperation');
    $hoursIsArray = count($hours) && is_array($hours[0]);
    
    return array(
      'name'                   => $preferredName,
      'primaryname'            => $primaryName,
      'id'                     => self::getField($xml, 'id'),
      'type'                   => self::getField($xml, 'type'),
      'address'                => self::getField($xml, array('location', 'address')),
      'directions'             => self::getField($xml, array('location', 'directions')),
      'longitude'              => self::getField($xml, array('location', 'longitude')),
      'latitude'               => self::getField($xml, array('location', 'latitude')),
      'website'                => self::getField($xml, 'url'),
      'email'                  => self::getField($xml, array('emailaddresses', 'email', 'emailaddress')),
      'phone'                  => self::getField($xml, array('phonenumbers', 'phonenumber')),
      'weeklyHours'            => $hoursIsArray ? $hours : array(), 
      'isOpenNow'              => self::isOpen($hours),
      'hrsOpenToday'           => self::hoursOpenTodayString($hours),
      'hoursOfOperationString' => !$hoursIsArray ? implode('; ', $hours) : '',
    );
  }

  private static function getItemState($item) {
    $state = 'unavailable';
  
    $statusString = strtolower(self::getField($item, 'stat'));
    if (strcasecmp($item->isavail, 'Y') == 0) {
      $state = 'available'; 
      
    } else if ((strpos($statusString, 'checked out') !== FALSE &&
                strpos($statusString, 'not checked out') === FALSE) || 
               (strpos($statusString, 'on hold') !== FALSE &&
                strpos($statusString, 'not on hold') === FALSE) || 
               (strpos($statusString, 'on order') !== FALSE &&
                strpos($statusString, 'not on order') === FALSE)) { 
      // haven't seen 'not on hold' or 'not on order' but better safe than sorry
      $state = 'requestable';
      
    } else if (strpos($statusString, 'lost') === FALSE && self::getField($item, 'req')) {
      foreach($item->req as $req) {
        if (self::getField($req, 'href')) {
          $state = 'requestable';
          break;
        }
      }
    }

    return $state;
  }

  private static function getItemAvailabilitySummaryForInstitution($branch) {
    // Brief collection information - grouped by item loan type
    
    $categories = array();
    
    foreach ($branch->collection as $collection) {
      $collectionItemCount = 0;
      
      // Walk over the items if there are any
      if (isset($collection->items->itemrecord)) {
        foreach ($collection->items->itemrecord as $item) {
          $statusString = strtolower(self::getField($item, 'stat'));
          $statusArray = explode(' | ', $statusString);
          $holdingStatus = $statusArray[0];
          
          $state = self::getItemState($item);
  
          if (!isset($categories[$holdingStatus])) {
            $categories[$holdingStatus] = array(
              'holdingStatus' => $holdingStatus,
              'available'     => 0,
              'requestable'   => 0,
              'unavailable'   => 0,
              'collection'    => 0,
              'total'         => 0,
            );
          }
          $categories[$holdingStatus]['total']++;
          if ($state == 'available') {
            $categories[$holdingStatus]['available']++;
            
          } else if ($state == 'requestable') {
            $categories[$holdingStatus]['requestable']++;
            
          } else if ($state == 'unavailable') {
            $categories[$holdingStatus]['unavailable']++;
          }
          $collectionItemCount++;
        }
      }
      
      // No items means it's a collection only item
      if (!$collectionItemCount && !self::getField($collection, 'online')) { 
        $descriptions = array();
        if (isset($collection->holdtag)) {
          foreach($collection->holdtag as $holdtag) {
            $descriptions[] = self::getField($holdtag, 'availval');
          }
        }
  
        $categories[] = array(
          'holdingStatus' => 'collection',
          'available'     => 0,
          'requestable'   => 0,
          'unavailable'   => 0,
          'collection'    => 1,
          'total'         => 1,
        );
      }
    }
    
    return array_values($categories);
  }

  private static function getItemAvailabilityForInstitution($branch) {
    // Detailed collection information - grouped by collection
    //
    // Within a collection items are grouped into set where all the following
    // fields are the same:
    // - callNumber
    // - state
    // - holding status
    // - secondary status
    // - description
    // - request url (if any)
    // - scan and deliver url (if any)
    //
    // Item keys:
    // 'state'             => [available|requestable|unavailable|may be available]
    // 'secondaryStatus'   => (optional) Informational message
    // 'callNumber'        => (optional) The item's call number
    // 'description'       => (optional) A description of the item
    // 'requestURL'        => (optional) A url to request the item be put on hold for the user
    // 'scanAndDeliverURL' => (optional) A url to request the item be scanned and delivered to the user
    // 'count'             => The number of items matching this category
    // (optional item keys may be missing or empty strings)

    $collections = array();
    
    foreach ($branch->collection as $collection) {
      if (self::getField($collection, 'online')) { continue; }
    
      $categories = array();
      
      if (isset($collection->items->itemrecord)) {
        foreach ($collection->items->itemrecord as $item) {
          $statusString = strtolower(self::getField($item, 'stat'));
          $statusArray = explode(' | ', $statusString);
          $holdingStatus = $statusArray[0];
  
          $itemDetails = array(
            'state'             => self::getItemState($item),
            'secondaryStatus'   => implode(' ', array_slice($statusArray, 1)),
            'callNumber'        => self::getField($item, 'call'),
            'description'       => self::getField($item, 'desc'),
            'requestURL'        => '',
            'scanAndDeliverURL' => '',
          );
          if (!strlen($itemDetails['callNumber'])) {
            $itemDetails['callNumber'] = self::getField($collection, 'callnumber');
          }
          
          if (isset($item->req)) {
            foreach($item->req as $req) {
              $url = self::getField($req, 'href');
              
              if (stripos($req, 'scan') !== FALSE) {
                $itemDetails['scanAndDeliverURL'] = $url;
              } else {
                $itemDetails['requestURL'] = $url;
              }
            }
          }
          
          if (!isset($categories[$holdingStatus])) {
            $categories[$holdingStatus] = array(
              'holdingStatus' => $holdingStatus,
              'items'         => array(),
            );
          }
          
          $key = implode('-', $itemDetails);
          if (!isset($categories[$holdingStatus]['items'][$key])) {
            $categories[$holdingStatus]['items'][$key] = $itemDetails;
            $categories[$holdingStatus]['items'][$key]['count'] = 0;
          }
          $categories[$holdingStatus]['items'][$key]['count']++;
        }
      }
  
      // No items means it's a collection only item
      if (!count($categories)) { 
        $descriptions = array();
        if (isset($collection->holdtag)) {
          foreach($collection->holdtag as $holdtag) {
            $descriptions[] = self::getField($holdtag, 'availval');
          }
        }
  
        $categories[] = array(
          'holdingStatus' => 'collection',
          'items' => array(
            array(
              'state'             => 'may be available',
              'secondaryStatus'   => self::getField($branch, 'noitems'),
              'callNumber'        => self::getField($collection, 'callnumber'),
              'description'       => implode("\n", $descriptions),
              'requestURL'        => '',
              'scanAndDeliverURL' => '',
              'count'             => 1,
            ),
          )
        );
      }
      
      foreach ($categories as $holdingStatus => $category) {
        $categories[$holdingStatus]['items'] = array_values($categories[$holdingStatus]['items']);
      }

      $collections[] = array(
        'name'       => self::getField($collection, 'collectionname'),
        'callNumber' => self::getField($collection, 'callnumber'),
        'categories' => array_values($categories),
      );
    }
    
    return $collections;
  }

  private static function getItemAvailabilityAtDetailLevel($itemId, $detailLevel) {
    $xml = self::query("avail-{$itemId}", 'URL_LIBRARIES_AVAILABILITY_BASE', $itemId);
    
    // Get the full list of known institution IDs for later use below
    $institutionIDs = array();
    foreach (self::getInstitutionsByType() as $institution) {
      $institutionIDs["{$institution['type']}_{$institution['id']}"] = true;
    }
    
    $results = array(
      'id'           => $itemId,
      'institutions' => array(),
    );
    
    if (isset($xml->branch)) {
      foreach ($xml->branch as $branch) {
        $institution = array(
          'id'   => self::getField($branch, array('repository', 'id')),
          'type' => self::getField($branch, array('repository', 'type')), 
          'name' => self::getField($branch, array('repository', 'name')),
        );

        // Make sure library is in the list of institutions:
        if (!isset($institutionIDs["{$institution['type']}_{$institution['id']}"])) { 
          continue; 
        }
        
        if ($detailLevel == 'brief') {
          if (!isset($institution['categories'])) {
            $institution['categories'] = array();
          }
          $institution['categories'] = self::getItemAvailabilitySummaryForInstitution($branch);
          if (!count($institution['categories'])) { continue; }
          
        } else {
          $institution['collections'] = self::getItemAvailabilityForInstitution($branch);
          if (!count($institution['collections'])) { continue; }
        }
        
        $results['institutions'][] = $institution;
      }
    }
    
    return $results;    
  }

  //
  // Public functions
  //
  
  public static function getLibrarySearchCodes() {
    $xml = self::query('librariesOpts', 'URL_LIBRARIES_OPTS');
    
    $codes = array();
    for ($i = 0; isset($xml->refinebylibrary->label[$i]); $i++) {
      $code = strval($xml->refinebylibrary->value[$i]);
      if ($code) {
        $codes[$code] = strval($xml->refinebylibrary->label[$i]);
      }
    }
    
    return $codes;
  }

  public static function getFormatSearchCodes() {
    $xml = self::query('librariesOpts', 'URL_LIBRARIES_OPTS');
    
    $codes = array();
    for ($i = 0; isset($xml->refinebyformat->label[$i]); $i++) {
      $code = strval($xml->refinebyformat->value[$i]);
      if ($code) {
        $codes[$code] = strval($xml->refinebyformat->label[$i]);
      }
    }
    
    return $codes;
  }
  
  public static function getPubDateSearchCodes() {
    $end = intval(strftime('%Y'));
    return array(
      ($end-3)."-$end"  => 'In the last 3 years',
      ($end-10)."-$end" => 'In the last 10 years', 
    );
  }
  
  public static function getLibraries() {
    return self::getInstitutionsByType('library');
  }

  public static function getArchives() {
    return self::getInstitutionsByType('archive');
  }

  public static function getOpenNow() {
    return self::getInstitutionShortInfo('library');
  }

  public static function getLibraryDetails($id, $preferredName) {
    return self::getInstitutionDetails('library', $id, $preferredName);
  }

  public static function getArchiveDetails($id, $preferredName) {
    return self::getInstitutionDetails('archive', $id, $preferredName);
  }

  public static function searchItems($searchParams=array(), $page=1) {
    $qParamMapping = array(
      'q'        => '',
      'keywords' => '',
      'title'    => 'ex-Everything-1.0',
      'author'   => 'author',
      'language' => 'language-id',
      'pubDate'  => 'ex-Everything-6.0',
    );
    $getParamMapping = array(
      'format'   => 'fmt', 
      'location' => 'lib', 
    );
  
    $queryTermArray = array();
    foreach ($qParamMapping as $searchParam => $qParam) {
      if (isset($searchParams[$searchParam]) && trim($searchParams[$searchParam])) {
        $value = trim($searchParams[$searchParam]);
        switch ($searchParam) {
          case 'q':
          case 'keywords':
            // don't quote keywords or full queries
            $queryTermArray[] = $value;
            break;
            
          default:
            if (strpos($value, ' ') !== FALSE) {
              $value = '"'.$value.'"'; 
            }
            $queryTermArray[] = ($qParam ? "{$qParam}:" : '').$value;
            break;
        }
      }      
    }
    
    $args = array(
      'q'       => implode(' ', $queryTermArray), 
      'curpage' => $page,
    );
    foreach ($getParamMapping as $searchParam => $getParam) {
      if (isset($searchParams[$searchParam]) && trim($searchParams[$searchParam])) {
        $args[$getParam] = trim($searchParams[$searchParam]);
      }
    }
    $urlSuffix = http_build_query($args);
  
    $xml = self::query("search-{$urlSuffix}", 'URL_LIBRARIES_SEARCH_BASE', $urlSuffix);
    
    $results = array(
      'q'        => $args['q'],
      'total'    => self::getField($xml, 'totalResults', 0),
      'start'    => 0,
      'end'      => 0,
      'pagesize' => self::getField($xml, 'itemsPerPage', 0),
      'items'    => array(),
    );
    
    if (isset($xml->resultSet, $xml->resultSet->item)) {
      foreach ($xml->resultSet->item as $item) {
        $namespaces = $item->getNameSpaces(true);
        $dc = $item->children($namespaces['dc']);

        $GLOBALS['librariesDebugEntryName'] = "item '".self::getField($dc, 'title')."'";

        $index = self::getField($item, 'position');
        if ($index > $results['end']) { $results['end'] = $index; }
        if ($results['start'] > $index || $results['start'] < 1) { $results['start'] = $index; }
        $results['items'][] = array(
          'index'           => $index,
          'itemId'          => self::getField($item, 'id'),
          'creator'         => self::getField($dc,   'creator'),
          // This is extracted as vernacularcreator in the detail version
          'nonLatinCreator' => self::getField($item, 'vernacularauthor'),
          'itemId'          => self::getField($item, 'id'),
          'title'           => self::getField($dc,   'title'),
          // The var casing is different from the same info in the detail (vernacularTitle)
          'nonLatinTitle'   => self::getField($item, 'vernaculartitle'),
          'date'            => self::getField($dc,   'date'),
          'format'          => self::getField($dc,   'format'),
          'edition'         => self::getField($item, 'edition'),
        );
      }
    }
    
    return $results;
  }

  public static function getItemAvailability($itemId) {
    return self::getItemAvailabilityAtDetailLevel($itemId, 'full');
  }

  public static function getItemAvailabilitySummary($itemId) {
    return self::getItemAvailabilityAtDetailLevel($itemId, 'brief');
  }

  public static function getFullAvailability($id) {
    $xml = self::query("avail-{$id}", 'URL_LIBRARIES_AVAILABILITY_BASE', $id);
    
    // Get the full list of known institution IDs for later use below
    $institutionIDs = array();
    foreach (self::getInstitutionsByType() as $institution) {
      $institutionIDs[$institution['id']] = true;
    }
    
    $results = array();
    
    if (isset($xml->branch)) {
      foreach ($xml->branch as $branch) {
        $library = array(
          'name' => strval($branch->repository->name[0]),
          'id'   => strval($branch->repository->id[0]),
          'type' => strval($branch->repository->type[0]), 
          'collection' => array(),
        );
        
        // Make sure library is in the list of institutions:
        if (!isset($institutionIDs[$library['id']])) { continue; }
        
        foreach ($branch->collection as $collection) {
          $collectionName = strval($collection->collectionname[0]);
          $collectionCallNumber = strval($collection->callnumber);
          
          $callNumbersMatch = true;
          $descriptionsMatch = true;
          $tempCallNumber = null;
          $tempDescription = null;
  
          $itemsByStat = array();
          $itemCount = 0;
          
          // Walk over the items if there are any
          if (isset($collection->items->itemrecord)) {
            foreach ($collection->items->itemrecord as $item) {
              $statString = strtolower($item->stat[0]);
              $stats = explode(' | ', $statString);
              $statMain = $stats[0];
              
              $checkedOutItem = 
                (strpos($statString, 'checked out') !== FALSE &&
                 strpos($statString, 'not checked out') === FALSE) || 
                (strpos($statString, 'on hold') !== FALSE &&
                 strpos($statString, 'not on hold') === FALSE);
              
              if (!isset($itemsByStat[$statMain])) {
                $itemsByStat[$statMain] = array(
                  'statMain'            => $statMain,
                  'availableItems'      => array(),
                  'checkedOutItems'     => array(),
                  'unavailableItems'    => array(),
                  'collectionOnlyItems' => array(),
                  'availCount'          => 0,
                  'unavailCount'        => 0,
                  'requestCount'        => 0,
                  'checkedOutCount'     => 0,
                  'scanAndDeliverCount' => 0,
                  'collectionOnlyCount' => 0,
                );
              }
               
              $itemByStat = array(
                'callNumber'           => strval($item->call),
                'description'          => strval($item->desc),
                'collectionName'       => $collectionName,
                'collectionCallNumber' => $collectionCallNumber,
                'available'            => strcasecmp($item->isavail, 'Y') == 0,
                'unavailable'          => strcasecmp($item->isavail, 'Y') != 0,
                'checkedOutItem'       => $checkedOutItem,
                'canRequest'           => false,
                'requestURL'           => '',
                'canScanAndDeliver'    => false,
                'scanAndDeliverURL'    => '',
                'statMain'             => $statMain,
                'statSecondary'        => implode(' ', array_slice($stats, 1)),
              );
              
              if (!strlen($itemByStat['callNumber'])) {
                $itemByStat['callNumber'] = $collectionCallNumber;
              }
              
              if (isset($item->req)) {
                foreach($item->req as $req) {
                  if (stripos($req, 'scan') !== FALSE) {
                    $itemByStat['scanAndDeliverURL'] = strval($req['href']);
                    $itemByStat['canScanAndDeliver'] = true;
                  } else {
                    $itemByStat['requestURL'] = strval($req['href']);
                    $itemByStat['canRequest'] = true;
                  }
                }
              }
              
              if ($itemByStat['available'] || $itemByStat['canScanAndDeliver']) {
                $itemsByStat[$statMain]['availableItems'][] = $itemByStat;
                $itemsByStat[$statMain]['availCount']++;
                
              } else if ($itemByStat['checkedOutItem'] || $itemByStat['canRequest']) {
                $itemsByStat[$statMain]['checkedOutItems'][] = $itemByStat;
                $itemsByStat[$statMain]['checkedOutCount']++;
                
              } else {
                $itemsByStat[$statMain]['unavailableItems'][] = $itemByStat;
                $itemsByStat[$statMain]['unavailCount']++;
              }
              
              if ($itemByStat['canRequest']) { 
                $itemsByStat[$statMain]['requestCount']++;
              }
              if ($itemByStat['canScanAndDeliver']) { 
                $itemsByStat[$statMain]['scanAndDeliverCount']++; 
              }
              
              $itemCount++;
              
              // Remember whether or not all items have the same call number and/or description            
              if (!isset($tempCallNumber)) {
                $tempCallNumber = $itemByStat['callNumber'];
                
              } else if ($tempCallNumber != $itemByStat['callNumber']) {
                $callNumbersMatch = false;
              }
              if (!isset($tempDescription)) {
                $tempDescription = $itemByStat['description'];
                
              } else if ($tempDescription != $itemByStat['description']) {
                $descriptionsMatch = false;
              }
            }
          }
          
          if ($itemCount == 0) { // No items means it's a collection only item
            $collectionAvail = array();
            if (isset($collection->holdtag)) {
              foreach($collection->holdtag as $holdtag) {
                $collectionAvail[] = strval($holdtag->availval[0]);
              }
            }
            
            $itemsByStat['collection'] = array(
              'statMain'            => 'collection',
              'availableItems'      => array(),
              'checkedOutItems'     => array(),
              'unavailableItems'    => array(),
              'collectionOnlyItems' => array(
                array(
                  'collectionName'       => $collectionName,
                  'collectionCallNumber' => $collectionCallNumber,
                  'collectionAvailVal'   => $collectionAvail,
                  'noItemMessage'        => strval($branch->noitems[0]),
                ),
              ),
              'availCount'          => 0,
              'unavailCount'        => 0,
              'requestCount'        => 0,
              'checkedOutCount'     => 0,
              'scanAndDeliverCount' => 0,
              'collectionOnlyCount' => 1,
            );
          }
  
          // Categorize by type        
          if (count($itemsByStat) <= 1) {
            if (isset($itemsByStat['collection'])) {
              $displayType = 'V';   // type 5: collection-only display
              
            } else if ($callNumbersMatch && $descriptionsMatch) {
              $displayType = 'I';   // type 1: everything same
            } else {
              $displayType = 'III'; // type 3: only diff call numbers or desc
            }
          } else {
            if ($callNumbersMatch && $descriptionsMatch) {
              $displayType = 'II';  // type 2: only diff holding stats within same collection
            } else {
              $displayType = 'IV';  // type 4: everything diff
            }
          }
          
          // Add the collection if it is valid
          if (strlen($collectionName)) {
            $library['collection'][] = array(
              'collectionName'       => $collectionName,
              'collectionCallNumber' => $collectionCallNumber,
              'displayType'          => $displayType,
              'itemsByStat'          => array_values($itemsByStat),
            );
          }
        }
        
        $results[] = $library;
      }
    }
    
    return $results;
  }
  
  public static function getItemRecord($id) {
    $item = self::query("item-{$id}", 'URL_LIBRARIES_ITEM_RECORD_BASE', $id);
  
    $namespaces = $item->getNameSpaces(true);
    $dc = $item->children($namespaces['dc']);
    
    $GLOBALS['librariesDebugEntryName'] = "item '".self::getField($dc, 'title')."'";

    return array(
      'itemId'          => $id,
      'title'           => self::getField($dc,   'title'),
      // UTF-8 encoded, non-Latin title (so Chinese, Japanese, Korean, etc.)
      // Yes, the casing of the variables really is different in the feed.
      'nonLatinTitle'   => self::getField($item, 'vernacularTitle'),
      'nonLatinCreator' => self::getField($item, 'vernacularcreator'),
      'creator'         => self::getField($dc,   'creator'),
      'creatorLink'     => self::getField($item, 'authorlink'),
      'publisher'       => self::getField($dc,   'publisher'),
      'date'            => self::getField($dc,   'date'),
      'format'          => self::getField($dc,   'format'),
      'edition'         => self::getField($item, 'edition'),
      'identifier'      => self::getField($dc,   'identifier'),
      'numberofimages'  => self::getField($item, 'numberofimages', 0),
      'worktype'        => self::getField($item, 'workType'),
      'thumbnail'       => self::getField($item, 'thumbnail'),
      'cataloglink'     => self::getField($item, 'cataloglink'),
      'fullimagelink'   => self::getField($item, 'fullimage'),
    );
  }
  
  public static function getImageThumbnail($id) {
    return self::getItemRecord($id);
  }
}
