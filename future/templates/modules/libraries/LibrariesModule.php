<?php

require_once realpath(LIB_DIR.'/Module.php');
require_once realpath(LIB_DIR.'/feeds/LibrariesInfo.php');

define('LIBRARY_LOCATIONS_COOKIE', 'libraryLocations');
define('LIBRARY_ITEMS_COOKIE',     'libraryItems');


class LibrariesModule extends Module {
  protected $id = 'libraries';
  private static $typeToCookie = array(
    'item'    => LIBRARY_ITEMS_COOKIE,
    'library' => LIBRARY_LOCATIONS_COOKIE,
    'archive' => LIBRARY_LOCATIONS_COOKIE,
  );
  private $bookmarks = array(
    'item'    => null,
    'library' => null,
    'archive' => null,
  );
    
  private function getBookmarks($type) {
    if (isset(self::$typeToCookie[$type])) {
      if (!isset($this->bookmarks[$type])) {
        $this->bookmarks[$type] = array();
        if (isset($_COOKIE[self::$typeToCookie[$type]])) {
          $this->bookmarks[$type] = array_unique(explode(',', $_COOKIE[self::$typeToCookie[$type]]));
        }
      }
      return $this->bookmarks[$type];
    }
    error_log(__FUNCTION__."(): Warning unknown cookie type '$type'");
    return array();
  }
  
  private function setBookmarks($type, $bookmarks) {
    if (isset(self::$typeToCookie[$type])) {
      setcookie(self::$typeToCookie[$type], implode(',', array_unique($bookmarks)), 0, COOKIE_PATH);
      $this->bookmarks[$type] = $bookmarks;
    } else {
      error_log(__FUNCTION__."(): Warning unknown cookie type '$type'");
    }
  }

  private function isBookmarked($type, $id) {
    return in_array($id, $this->getBookmarks($type));
  }
  
  private function checkToggleBookmark($type, $id) {
    if ($this->getArg('toggleBookmark')) {
      if (isset(self::$typeToCookie[$type])) {
        $bookmarks = array_fill_keys($this->getBookmarks($type), true);
    
        if (isset($bookmarks[$id])) {
          unset($bookmarks[$id]);
        } else {
          $bookmarks[$id] = true;
        }
        $this->setBookmarks($type, array_keys($bookmarks));
        
        $args = $this->args;
        unset($args['toggleBookmark']);
        $this->redirectTo($this->page, $args);
      }
    }
  }
  
  private function initLibraryDetails($type, $id, $name) {
    $data = array();
    
    if ($type == 'library') {
      $data = Libraries::getLibraryDetails($id, $name);
      //error_log(print_r($data, true));
      
    } else if ($type == 'archive') {
      $data = Libraries::getArchiveDetails($id, $name);
      //error_log(print_r($data, true));
    
    } else {
      $this->redirectTo('index');
    }
    
    $this->checkToggleBookmark($type, $id);
    
    return $data;
  }

  private function translateFormat($formatDetail) {
    switch (strtolower($formatDetail)) {
      case 'book':
        return 'book';
      
      case 'computer file':
        return 'computerfile';
        
      case 'recording':
        return 'soundrecording';
        
      case 'image':
        return 'image';
        
      case 'map':
        return 'map';
      
      case 'archives / manuscripts':
      case 'journal / serial':
        return 'journal';
        
      case 'sheet music':
        return 'musicscore';

      case 'movie':
        return 'video';
        
      default:
        error_log("Warning unknown library item format '$format'"); 
        return 'book';
    }
  }
  
  private function formatDetail($data, $key='', $default='') {
    if (is_array($data)) {
      $detail = self::argVal($data, $key, $default);
    } else {
      $detail = $data;
    }
    
    // no phone numbers in item details:
    $detail = str_replace('-', '-&shy;', $detail);
    
    // no email addresses
    $detail = str_replace('@', '@&shy;', $detail);
    
    return $detail;
  }
  
  private function getItemDetails($data) {
    $details = array(
      'id'         => $data['itemId'],
      'title'      => $this->formatDetail($data, 'title', 'Unknown title'),
      'creator'    => $this->formatDetail($data, 'creator'),
      'publisher'  => $this->formatDetail($data, 'publisher'),
      'date'       => $this->formatDetail($data, 'date'),
      'format'     => $this->translateFormat(self::argVal($data['format'], 'formatDetail', 'Book')),
      'formatDesc' => $this->formatDetail($data['format'], 'formatDetail'),
      'type'       => $this->formatDetail($data['format'], 'typeDetail'),
      'url'        => $this->detailURL($data['itemId']),
      'bookmarked' => $this->isBookmarked('item', $data['itemId']),
      'cookie'     => LIBRARY_ITEMS_COOKIE,
    );
    
    if (isset($data['identifier'])) {
      foreach ($data['identifier'] as $identifier) {
        if (isset($identifier['type']) && strtolower($identifier['type']) == 'net') {
          $details['isOnline']  = true;
          $details['onlineUrl'] = $identifier['typeDetail'];
        }
      }
    }
    
    if (strtolower($details['format']) == 'image') {
      $imageInfo = Libraries::getImageThumbnail($data['itemId']);

      $details['isOnline']     = true;
      $details['onlineUrl']    = $imageInfo['cataloglink'];
      $details['thumbnail']    = $imageInfo['thumbnail'];
      $details['fullImageUrl'] = $imageInfo['fullimagelink'];
      $details['workType']     = $imageInfo['worktype'];
      $details['imageCount']   = $imageInfo['numberofimages'];
    }
    
    return $details;
  }
  
  private function formatItemAvailabilityInfo($entry) {
    $results = array();
    
    foreach ($entry['collection'] as $collection) {
      $items = array();
      
      $collectionCallNumber = self::argVal($collection, 'collectionCallNumber');
      
      foreach ($collection['itemsByStat'] as $statItems) {
        $item = array(
          'type'           => self::argVal($statItems, 'statMain'),
          'available'      => 0,
          'requestable'    => 0,
          'total'          => 0,
          'types'          => array(),
        );
        
        if (!$item['type']) {
          $item['type'] = 'collection';
        }
        
        $countMapping = array(
          'available'   => 'availCount',
          'requestable' => 'checkedOutCount',
          'unavailable' => 'unavailCount',
          'collection'  => 'collectionOnlyCount',
        );
        
        $typeMapping = array(
          'available'   => 'availableItems',
          'requestable' => 'checkedOutItems',
          'unavailable' => 'unavailableItems',
          'collection'  => 'collectionOnlyItems',
        );
        
        foreach ($typeMapping as $typeKey => $statKey) {
          $count = count($statItems[$statKey]);
          
          if ($count > 0) {
            $item['total'] += $count;
            if ($typeKey == 'available') {
              $item['available'] += $count;
            }
            if ($typeKey == 'requestable') {
              $item['requestable'] += $count;
            }
            
            $itemType = array(
              'count' => $count,
              'status' => $typeKey,
            );
            foreach ($statItems[$statKey] as $statItem) {
              if (self::argVal($statItem, 'requestUrl')) {
                $itemType['url'] = $this->formatDetail($statItem, 'requestUrl');
              }
              if (self::argVal($statItem, 'statSecondary')) {
                $itemType['status'] = $this->formatDetail($statItem, 'statSecondary');
              }
              if (self::argVal($statItem, 'callNumber')) {
                $itemType['callNumber'] = $this->formatDetail($statItem, 'callNumber');
                if (!$collectionCallNumber) {
                  $collectionCallNumber = $this->formatDetail($statItem, 'callNumber');
                }
              }
              if (self::argVal($statItem, 'collectionCallNumber')) {
                $itemType['callNumber'] = $this->formatDetail($statItem, 'collectionCallNumber');
                if (!$collectionCallNumber) {
                  $collectionCallNumber = $this->formatDetail($statItem, 'collectionCallNumber');
                }
              }
            }
            $item['types'][$typeKey] = $itemType;
          }
        }
        $items[] = $item;
      }
      
      if (count($items)) {
        $results[] = array(
          'name'       => $this->formatDetail($collection, 'collectionName'),
          'callNumber' => $collectionCallNumber,
          'items'      => $items,
        );
      }
    }
    
    return $results;
  }

  private function detailURL($id, $toggleBookmark=false, $addBreadcrumb=true) {
    $args = array(
      'id' => $id,
    );
    if ($toggleBookmark) {
      $args['toggleBookmark'] = 1;
    }
    return $this->buildBreadcrumbURL('detail', $args, !$toggleBookmark && $addBreadcrumb);
  }
  
  private function availabilityURL($itemID, $type, $id) {
    return $this->buildBreadcrumbURL('availability', array(
      'itemID' => $itemID,
      'type'   => $type,
      'id'     => $id,
    ));
  }
  
  private function locationAndHoursURL($type, $id, $name, $toggleBookmark=false) {
    $args = array(
      'type' => $type,
      'id'   => $id,
      'name' => $name,
    );
    if ($toggleBookmark) {
      $args['toggleBookmark'] = 1;
    }
    return $this->buildBreadcrumbURL('locationAndHours', $args, !$toggleBookmark);
  }
  
  private function mapURL($type, $id, $name) {
    return $this->buildBreadcrumbURL('map', array(
      'type' => $type,
      'id'   => $id,
      'name' => $name,
    ));
  }
  
  private function fullHoursURL($type, $id, $name) {
    return $this->buildBreadcrumbURL('fullHours', array(
      'type' => $type,
      'id'   => $id,
      'name' => $name,
    ));
  }

  private static function titleSort($a, $b) {
    return strcmp($a['title'], $b['title']);
  }
  
  protected function urlForSearch($searchTerms) {
    return $this->buildBreadcrumbURL("/{$this->id}/search", array(
      'keywords' => $searchTerms,
    ), false);
  }

  public function federatedSearch($searchTerms, $maxCount, &$results) {
    $count = 0;
    $results = array();
  
    $data = array_values(Libraries::searchItems($searchTerms, '', ''));
    $count = count($data);

    if ($count) {
      $limit = min($maxCount, $count);
      
      for ($i = 0; $i < $limit; $i++) {
        $subtitle = trim(self::argVal($data[$i], 'date', ''));
        $creator = self::argVal($data[$i], 'creator', '');
        if ($creator) {
          if ($subtitle && $creator) { $subtitle .= ' | '; }
          $subtitle .= $creator;
        }
      
        $results[] = array(
          'title'    => self::argVal($data[$i], 'title', 'Unknown title'),
          'subtitle' => $subtitle ? $subtitle : null,
          'url'      => $this->buildBreadcrumbURL("/{$this->id}/detail", array(
            'id' => $data[$i]['itemId'],
          ), false),
        );
      }
    }
    return $count;
  }
  
  protected function initializeForPage() {
    switch ($this->page) {
      case 'index':
        $indexConfig = $this->loadWebAppConfigFile('libraries-index', 'indexConfig');
        
        $searchPlaceholder = '';
        $sections = array();
        foreach ($indexConfig as $sectionName => $config) {
          if ($sectionName == 'search') {
            $searchPlaceholder = $config['searchPlaceholder'];
            continue;
          }
          
          $section = array(
            'items' => array(),
          );
          foreach ($config['titles'] as $i => $title) {
            if ($sectionName == 'bookmarks') {
              $types = explode('|', $config['bookmarkType'][$i]);

              $hasBookmarkOfTypes = false;
              foreach ($types as $type) {
                if (count($this->getBookmarks($type))) {
                  $hasBookmarkOfTypes = true;
                  break;
                }
              }
              if (!$hasBookmarkOfTypes) { continue; }
            }
            $section['items'][] = array(
              'title' => $title,
              'url'   => $config['urls'][$i],
              'class' => $config['classes'][$i],
            );
          }
          if (count($section['items'])) {
            $sections[] = $section;
          }
        }
        
        $this->assign('searchPlaceholder', $searchPlaceholder);
        $this->assign('sections',          $sections);
        break;

      case 'links':
        $indexConfig = $this->loadWebAppConfigFile('libraries-links', 'indexConfig');
        
        $sections = array();
        foreach ($indexConfig as $sectionName => $config) {
          $section = array(
            'heading' => self::argVal($config, 'heading'),
            'items'   => array(),
          );
          foreach ($config['titles'] as $i => $title) {
            $section['items'][] = array(
              'title'    => $title,
              'subtitle' => $config['subtitles'][$i],
              'url'      => $config['urls'][$i],
              'class'    => $config['classes'][$i],
            );
          }
          if (count($section)) {
            $sections[] = $section;
          }
        }
        
        $this->assign('sections', $sections);
        break;
      
      case 'detail':
        $id = $this->getArg('id');
        if (!$id) {
          $this->redirectTo('index');
        }
        
        $this->checkToggleBookmark('item', $id);

        $data = Libraries::getItemRecord($id);
        $data['itemId'] = $id;
        
        $item = $this->getItemDetails($data);
        //error_log(print_r($data, true));
        
        $data = Libraries::getFullAvailability($id);
        //error_log(print_r($data, true));
        
        $locations = array();
        $locationCoords = array();
        foreach ($data as $entry) {
          $collections = $this->formatItemAvailabilityInfo($entry);
          if (!count($collections)) { continue; }
          
          $locations[] = array(
            'id'          => $entry['id'],
            'type'        => $entry['type'],
            'name'        => $this->formatDetail($entry, 'name'),
            'collections' => $collections,
            'url'         => $this->availabilityURL($id, $entry['type'], $entry['id']),
          );
          
          $ldata = null;
          if ($entry['type'] == 'library') {
            $ldata = Libraries::getLibraryDetails($entry['id'], $entry['name']);
            
          } else if ($entry['type'] == 'archive') {
            $ldata = Libraries::getArchiveDetails($entry['id'], $entry['name']);
          
          }
          
          if ($ldata) {
            $locationCoords[$entry['id']] = array(
              'lat' => floatVal($ldata['latitude']),
              'lon' => floatVal($ldata['longitude']),
            );
          }
        }
        
        $this->addInlineJavascript('var locationCoords = '.json_encode($locationCoords).';');
        $this->addOnLoad('setLocationDistances(locationCoords);');
        //error_log(print_r($item, true));
        
        $this->assign('locations',   $locations);
        $this->assign('item',        $item);
        $this->assign('bookmarkURL', $this->detailURL($id, true));
        break;
      
      case 'availability':
        $itemID = $this->getArg('itemID');
        $type   = $this->getArg('type');
        $id     = $this->getArg('id');
          
        if (!$itemID || !$id) {
          $this->redirectTo('index');
        }
        
        $items = array();
        $name = '';
        
        $data = Libraries::getFullAvailability($itemID);
        
        foreach ($data as $entry) {
          if ($entry['id'] != $id) { continue; }
          //error_log(print_r($entry, true));
          $name = $entry['name'];
          $collections = $this->formatItemAvailabilityInfo($entry);

          break; // assume libraries are not listed multiple times
        }

        if (!$name) {
          $this->redirectTo('index');
        }

        if ($type == 'library') {
          $data = Libraries::getLibraryDetails($id, $name);
          //error_log(print_r($data, true));
          
        } else if ($type == 'archive') {
          $data = Libraries::getArchiveDetails($id, $name);
          //error_log(print_r($data, true));
        
        } else {
          $this->redirectTo('index');
        }
        
        $location = array(
          'type'        => $data['type'],
          'name'        => $this->formatDetail($data, 'name'),
          'hours'       => $this->formatDetail($data, 'hrsOpenToday'),
          'collections' => $collections,
        );
        
        $this->assign('infoURL', $this->locationAndHoursURL($type, $id, $name));
        $this->assign('location', $location);
        break;
      
      case 'advanced':
        $searchConfig = $this->loadWebAppConfigFile('libraries-search', 'searchConfig');
        
        $locations = $searchConfig['locations'] + Libraries::getLibrarySearchCodes();
        $formats   = $searchConfig['formats']   + Libraries::getFormatSearchCodes();

        $this->assign('locations', $locations);
        $this->assign('formats',   $formats);
        break;

      case 'search':
        $searchConfig = $this->loadWebAppConfigFile('libraries-search', 'searchConfig');

        $locations = $searchConfig['locations'] + Libraries::getLibrarySearchCodes();
        $formats   = $searchConfig['formats']   + Libraries::getFormatSearchCodes();
        
        $keywords     = trim($this->getArg('keywords'));
        $title        = trim($this->getArg('title'));
        $author       = trim($this->getArg('author'));
        $locationTerm = $this->getArg('location');
        $formatTerm   = $this->getArg('format');

        $searchTerms = '';
        if ($keywords) {
          $searchTerms .= '"'.$keywords.'"';
        }
        if ($title) {
          $searchTerms .= 'title:"'.$title.'"';
        }
        if ($author) {
          $searchTerms .= 'author:"'.$author.'"';
        }
        if ($locationTerm == 'any' || $locationTerm == 'all') {
          $locationTerm = '';
        }
        if ($formatTerm == 'any' || $formatTerm == 'all') {
          $formatTerm = '';
        } 
        
        $results = array();
        if ($searchTerms) {
          $results = array();
          $data = Libraries::searchItems($searchTerms, $locationTerm, $formatTerm);
          //error_log(print_r($data, true));
        
          foreach ($data as $entry) {
            $results[] = $this->getItemDetails($entry);
          }
        }
        
        $this->assign('keywords',    $keywords);
        $this->assign('title',       $title);
        $this->assign('author',      $author);
        $this->assign('location',    $this->getArg('location'));
        $this->assign('format',      $this->getArg('format'));
        $this->assign('locations',   $locations);
        $this->assign('formats',     $formats);
        $this->assign('resultCount', count($results));
        $this->assign('results',     $results);
        break;

      case 'bookmarks':
        $types = explode('|', $this->getArg('type'));
        
        $libraries = null;
        $archives = null;
        
        $results = array();
        foreach ($types as $type) {
          $bookmarks = $this->getBookmarks($type);
          foreach ($bookmarks as $id) {
            if ($type == 'library') {
              if (!isset($libraries)) {
                $libraries = Libraries::getAllLibraries();
              }
              
              foreach ($libraries as $entry) {
                if ($entry['id'] == $id) {
                  $results[] = array(
                    'title' => $this->formatDetail($entry, 'primaryName'),
                    'url' => $this->locationAndHoursURL('library', $entry['id'], $entry['primaryName']),
                  );
                  break;
                }
              }
            } else if ($type == 'archive') {
              if (!isset($archives)) {
                $archives = Libraries::getAllArchives();
              }
              
              foreach ($archives as $entry) {
                if ($entry['id'] == $id) {
                  $results[] = array(
                    'title' => $this->formatDetail($entry, 'primaryName'),
                    'url' => $this->locationAndHoursURL('archive', $entry['id'], $entry['primaryName']),
                  );
                  break;
                }
              }

            } else {
              $data = Libraries::getItemRecord($id);
              $data['itemId'] = $id;
              $results[] = $this->getItemDetails($data);
            }
          }
        }
        
        $this->assign('bookmarkType', $type);
        $this->assign('results', $results);
        break;
        
      case 'libraries':
        $openOnly = $this->getArg('openOnly', false) ? true : false;
        $openNowToggleURL = $this->buildBreadcrumbURL($this->page, 
          $openOnly ? array() : array('openOnly' => 'true'), false);// toggle
        
        $data = Libraries::getOpenNow();
        //error_log(print_r($data, true));
        
        $libraries = array();
        foreach ($data as $entry) {
          if (!isset($libraries[$entry['name']]) && (!$openOnly || $entry['isOpenNow'] == 'YES')) {
            $libraries[$entry['name']] = array(
              'title' => $this->formatDetail($entry, 'name'),
              'url' => $this->locationAndHoursURL('library', $entry['id'], $entry['name']),
            );
          }
        }
        ksort($libraries);
        
        $this->assign('openOnly',         $openOnly);
        $this->assign('openNowToggleURL', $openNowToggleURL);
        $this->assign('libraries',        $libraries);
        break;
        
      case 'archives':
        $data = Libraries::getAllArchives();
        //error_log(print_r($data, true));
        
        $archives = array();
        foreach ($data as $entry) {
          if (!isset($archives[$entry['name']])) {
            $archives[$entry['name']] = array(
              'title' => $this->formatDetail($entry, 'name'),
              'url' => $this->locationAndHoursURL('archive', $entry['id'], $entry['name']),
            );
          }
        }
        ksort($archives);

        $this->assign('archives', $archives);
        break;
        
      case 'locationAndHours':        
        $type = $this->getArg('type');
        $id   = $this->getArg('id');
        $name = $this->getArg('name');
        
        $data = $this->initLibraryDetails($type, $id, $name);
        //error_log(print_r($data, true));
        
        $info['hours'] = array();
        if (count($data['weeklyHours'])) {
          $now = new DateTime();
          $today = intval($now->format('Ymd'));
          
          foreach ($data['weeklyHours'] as $entry) {
            if (intval($entry['date']) >= $today && count($info['hours']) < 3) {
              $info['hours'][$entry['date']] = array(
                'label' => $entry['day'],
                'title' => $this->formatDetail($entry, 'hours'),
              );
            }
          }
          $info['hours'][] = array(
            'label' => null,
            'title' => "Full week's schedule",
            'url'   => $this->fullHoursURL($type, $id, $name),            
          );
          $info['hours'] = array_values($info['hours']);
          
        } else if (strlen($data['hoursOfOperationString'])) {
          $info['hours'][] = array(
            'label' => 'Hours',
            'title' => $data['hoursOfOperationString'],
          );
        }
        if (!count($info['hours'])) {
          unset($info['hours']);
        }
        
        $info['directions'] = array();
        if ($data['address']) {
          $info['directions'][] = array(
            'label' => 'Location',
            'title' => $this->formatDetail($data, 'address'),
            'url'   => $this->mapURL($type, $id, $name),
            'class' => 'map',
          );
        }
        if ($data['directions']) {
          $directions = trim($data['directions']);
          
          $entry = array(
            'label' => 'Directions',
            'title' => $this->formatDetail($directions),
          );
          if (strpos($directions, 'http') === 0) {
            $entry['url'] = $directions;
            $entry['class'] = 'external';
          }
          
          $info['directions'][] = $entry;
        }
        if (!count($info['directions'])) {
          unset($info['directions']);
        }
        
        $info['contact'] = array();
        if ($data['website']) {
          if (strpos($data['website'], 'http') === 0) {
            $info['contact'][] = array(
              'label' => 'Website',
              'title' => $data['website'],
              'url'   => $data['website'],
              'class' => 'external',
            );
          } else {
            $info['contact'][] = array(
              'label' => 'Website',
              'title' => $this->formatDetail($data, 'website'),
            );
          }
        }
        if ($data['email']) {
          $info['contact'][] = array(
            'label' => 'Email',
            'title' => $this->formatDetail($data, 'email'),
            'url'   => "mailto:{$data['email']}",
            'class' => 'email',
          );
        }
        foreach ($data['phone'] as $phone) {
          $info['contact'][] = array(
            'label' => $phone['description'] ? $this->formatDetail($phone, 'description') : 'Phone',
            'title' => $this->formatDetail($phone, 'number'),
            'url'   => 'tel:'.strtr($phone['number'], '-', ''),
            'class' => 'phone',
          );
        }
        if (!count($info['contact'])) {
          unset($info['contact']);
        }
        
        $item = array(
          'id'           => $id,
          'name'         => $this->formatDetail($name),
          'fullName'     => $this->formatDetail($data, 'primaryname'),
          'type'         => $type,
          'bookmarked'   => $this->isBookmarked($type, $id),
          'cookie'       => LIBRARY_LOCATIONS_COOKIE,
          'infoSections' => $info,
        );
        
        $this->assign('item', $item);
        $this->assign('bookmarkURL',  $this->locationAndHoursURL($type, $id, $name, true));
        break;

      case 'fullHours':
        $type = $this->getArg('type');
        $id   = $this->getArg('id');
        $name = $this->getArg('name');
        
        $data = $this->initLibraryDetails($type, $id, $name);

        $hours = array();
        if (count($data['weeklyHours'])) {
          foreach ($data['weeklyHours'] as $entry) {
            $hours[$entry['date']] = array(
              'label' => $this->formatDetail($entry, 'day'),
              'title' => $this->formatDetail($entry, 'hours'),
            );
          }
          $hours = array_values($hours);
        }
        if (!count($hours)) {
          $this->redirectTo('locationAndHours', array(
            'type' => $type,
            'id'   => $id,
            'name' => $name,
          ));
        }
        
        $item = array(
          'name'       => $this->formatDetail($name),
          'fullName'   => $this->formatDetail($data, 'primaryname'),
          'type'       => $type,
          'bookmarked' => $this->isBookmarked($type, $id),
          'cookie'     => LIBRARY_LOCATIONS_COOKIE,
          'hours'      => $hours,
        );
        
        $this->assign('item', $item);
        break;

      case 'map':
        $type = $this->getArg('type');
        $id   = $this->getArg('id');
        $name = $this->getArg('name');
        
        $data = $this->initLibraryDetails($type, $id, $name);
        
        switch ($this->pagetype) {
          case 'compliant':
            $imageWidth = 290; $imageHeight = 300;
            break;
          
          case 'basic':
            if ($GLOBALS['deviceClassifier']->getPlatform() == 'bbplus') {
              $imageWidth = 410; $imageHeight = 360;
            } else {
              $imageWidth = 200; $imageHeight = 200;
            }
            break;
        }
        
        $this->assign('imageWidth',  $imageWidth);
        $this->assign('imageHeight', $imageHeight);
        
        $imgSrc = $GLOBALS['siteConfig']->getVar('GOOGLE_STATIC_MAPS_URL').http_build_query(array(
          'sensor'  => 'false',
          'size'    => "{$imageWidth}x{$imageHeight}",
          'markers' => "color:0xb12727|{$data['latitude']},{$data['longitude']}",
        ));

        
        $item = array(
          'name'       => $this->formatDetail($name),
          'fullName'   => $this->formatDetail($data, 'primaryname'),
          'type'       => $type,
          'bookmarked' => $this->isBookmarked($type, $id),
          'cookie'     => LIBRARY_LOCATIONS_COOKIE,
          'imgSrc'     => $imgSrc,
        );
        
        $this->assign('item', $item);
        break;
    }
  }
}
