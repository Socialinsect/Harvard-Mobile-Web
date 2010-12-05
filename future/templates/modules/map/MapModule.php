<?php

require_once realpath(LIB_DIR.'/Module.php');
//require_once realpath(LIB_DIR.'/feeds/ArcGISServer.php');
//require_once realpath(LIB_DIR.'/feeds/MapSearch.php');
//require_once realpath(LIB_DIR.'/feeds/WMSServer.php');
require_once realpath(LIB_DIR.'/MapLayerDataController.php');
require_once realpath(LIB_DIR.'/StaticMapImageController.php');

// detail-basic: $imageUrl $imageWidth $imageHeight $scrollNorth $scrollSouth $scrollEast $scrollWest $zoomInUrl $zoomOutUrl $photoUrl $photoWidth
// detail: $hasMap $mapPane $photoPane $detailPane $imageWidth $imageHeight $imageUrl $name $details
// search: $places

define('ZOOM_FACTOR', 2);
define('MOVE_FACTOR', 0.40);
define('MIN_MAP_CONTEXT', 250); // enforce a minimum range in feet (their units) for map context

class MapModule extends Module {
  protected $id = 'map';
  protected $feeds;

  private function bboxArr2Str($bbox) {
    return implode(',', array_values($bbox));
  }
  
  private function bboxStr2Arr($bboxStr) {
    $values = explode(',', $bboxStr);
    return array(
      'xmin' => $values[0],
      'ymin' => $values[1],
      'xmax' => $values[2],
      'ymax' => $values[3],
    );
  }

  /*
  // all args can be -1, 0, or 1
  private function shiftBBox($bbox, $east, $south, $in) {
    $xrange = $bbox['xmax'] - $bbox['xmin'];
    $yrange = $bbox['ymax'] - $bbox['ymin'];
    if ($east != 0) {
      $bbox['xmin'] += $east * $xrange * MOVE_FACTOR;
      $bbox['xmax'] += $east * $xrange * MOVE_FACTOR;
    }
    if ($south != 0) {
      $bbox['ymin'] += $south * $yrange * MOVE_FACTOR;
      $bbox['ymax'] += $south * $yrange * MOVE_FACTOR;
    }
    if ($in != 0) {
      if ($in == 1)
        $inset = (ZOOM_FACTOR - 1) / ZOOM_FACTOR;
      else
        $inset = -(ZOOM_FACTOR - 1);
  
      $bbox['xmin'] += ($xrange / 2) * $inset;
      $bbox['ymin'] += ($yrange / 2) * $inset;
      $bbox['xmax'] -= ($xrange / 2) * $inset;
      $bbox['ymax'] -= ($yrange / 2) * $inset;
    }
  
    return $bbox;
  }
  */

    /*
  private function initializeMap($name, $details) {
    $wms = new WMSServer();
    $bbox = isset($this->args['bbox']) ? $this->bboxStr2Arr($this->args['bbox']) : NULL;
  
    switch ($this->pagetype) {
     case 'compliant':
       $imageWidth = 290; $imageHeight = 190;
       break;
       
     case 'basic':
       if ($GLOBALS['deviceClassifier']->getPlatform() == 'bbplus') {
         $imageWidth = 410; $imageHeight = 260;
       } else {
         $imageWidth = 200; $imageHeight = 200;
       }
       break;
    }
    $this->assign('imageHeight', $imageHeight);
    $this->assign('imageWidth',  $imageWidth);
  
    if (!isset($bbox)) {
      if (strpos($name, ',') !== FALSE) {
        $nameparts = explode(',', $name);
        $name = $nameparts[0];
      }
      $name = str_replace('.', '', $name);
  
      // merge search results with category info if they came from a category
      $searchResults = ArcGISServer::search($name);
      if (isset($this->args['category'])) {
        $secondaryResults = $searchResults;
        $searchResults = ArcGISServer::search($name, $this->args['category']);
        if (!$searchResults || !$searchResults->results) {
          $searchResults = $secondaryResults;
          unset($secondaryResults);
        }
      }
      if ($searchResults && $searchResults->results) {
        $result = $searchResults->results[0];
        foreach ($result->attributes as $field => $value) {
          $details[$field] = $value;
        }
  
        if (isset($secondaryResults, $secondaryResults->results[0])) {
          foreach ($secondaryResults->results[0]->attributes as $field => $value) {
            $details[$field] = $value;
          }
        }
        switch ($result->geometryType) {
          case 'esriGeometryPolygon':
            $rings = $result->geometry->rings;
            $xmin = PHP_INT_MAX;
            $xmax = 0;
            $ymin = PHP_INT_MAX;
            $ymax = 0;
            foreach ($rings[0] as $point) {
              if ($xmin > $point[0]) $xmin = $point[0];
              if ($xmax < $point[0]) $xmax = $point[0];
              if ($ymin > $point[1]) $ymin = $point[1];
              if ($ymax < $point[1]) $ymax = $point[1];
            }
              
            $xrange = $xmax - $xmin;
            if ($xrange < MIN_MAP_CONTEXT) {
              $xmax += (MIN_MAP_CONTEXT - $xrange) / 2;
              $xmin -= (MIN_MAP_CONTEXT - $xrange) / 2;
            }
            $yrange = $ymax - $ymin;
            if ($yrange < 200) {
              $ymax += (MIN_MAP_CONTEXT - $yrange) / 2;
              $ymin -= (MIN_MAP_CONTEXT - $yrange) / 2;
            }
  
            break;
          case 'esriGeometryPoint':
          default:
            $pointBuffer = MIN_MAP_CONTEXT / 2;
            $xmin = $result->geometry->x - $pointBuffer;
            $xmax = $result->geometry->x + $pointBuffer;
            $ymin = $result->geometry->y - $pointBuffer;
            $ymax = $result->geometry->y + $pointBuffer;
             break;
        }
      
        $minBBox = array(
          'xmin' => $xmin,
          'ymin' => $ymin,
          'xmax' => $xmax,
          'ymax' => $ymax,
        );
    
        $bbox = $wms->calculateBBox($imageWidth, $imageHeight, $minBBox);
  
      } else { // no search results
        $imageUrl = 'images/map_not_found_placeholder.jpg';
      }
    }
  
    if (isset($bbox)) {
      $imageUrl = $wms->getMap($imageWidth, $imageHeight, 'EPSG:2249', $bbox);
  
      // build urls for panning/zooming
      $params = $this->args;
      
      $scrollNorth = $this->detailUrlForBBox($this->bboxArr2Str($this->shiftBBox($bbox,  0, -1,  0)));
      $scrollSouth = $this->detailUrlForBBox($this->bboxArr2Str($this->shiftBBox($bbox,  0,  1,  0)));
      $scrollEast  = $this->detailUrlForBBox($this->bboxArr2Str($this->shiftBBox($bbox,  1,  0,  0)));
      $scrollWest  = $this->detailUrlForBBox($this->bboxArr2Str($this->shiftBBox($bbox, -1,  0,  0)));
      $zoomInUrl   = $this->detailUrlForBBox($this->bboxArr2Str($this->shiftBBox($bbox,  0,  0,  1)));
      $zoomOutUrl  = $this->detailUrlForBBox($this->bboxArr2Str($this->shiftBBox($bbox,  0,  0, -1)));
    }
  
    $this->assign('imageUrl', $imageUrl);
  
    // the following are only used by webkit version
    $mapInitURL = $wms->getMapBaseURL(); // js variable
    $urlParts = parse_url($mapInitURL);
    parse_str($urlParts['query'], $queryParts);
    $mapLayers = $queryParts['layers'];
  
    unset($queryParts['layers']);
    unset($queryParts['styles']);
    $urlParts['query'] = http_build_query($queryParts);
  
    $mapBaseURL = $urlParts['scheme'] . '://'
                . $urlParts['host']
                . $urlParts['path'] . '?'
                . $urlParts['query']; // js variable
  
    $detailBaseUrl     = $this->detailUrlForBBox(null);
    $fullscreenBaseURL = $this->fullscreenUrlForBBox(null);
  
    $mapOptions = '&' . http_build_query(array(
      'crs' => 'EPSG:2249',
    ));
    
    $hasMap = $bbox != null;
    
    if (!isset($bbox)) {
      $bbox = array(
        'xmin' => 0,
        'ymax' => 0,
        'ymin' => 0,
        'xmax' => 0,
      );
    }  
    
    $selectbbox = $this->shiftBBox($bbox, 0, 0, 1);
    
    $script = <<<JS
      var mapSelect     = '$name';
      var initMapBoxW   = {$bbox['xmin']};
      var initMapBoxN   = {$bbox['ymax']};
      var initMapBoxS   = {$bbox['ymin']};
      var initMapBoxE   = {$bbox['xmax']};
      var selectMapBoxW = {$selectbbox['xmin']};
      var selectMapBoxN = {$selectbbox['ymax']};
      var selectMapBoxS = {$selectbbox['ymin']};
      var selectMapBoxE = {$selectbbox['xmax']};
      var mapBaseURL    = '{$mapBaseURL}';
      var mapOptions    = '{$mapOptions}';
      var mapLayers     = '{$mapLayers}';
      var detailBaseURL = '{$detailBaseUrl}';
      var fullscreenBaseURL = '{$fullscreenBaseURL}';
JS;

    $footerScript = <<<JS
      mapW = {$imageWidth};
      mapH = {$imageHeight};
      checkIfMoved();
JS;

    $this->addInlineJavascript($script);
    $this->addInlineJavascriptFooter($footerScript);

    $this->addOnLoad("loadImage(getMapURL(mapBaseURL),'mapimage');");

    return $hasMap;
  }
    */
  
  private function initializeFullscreenMap() {
    $selectvalue = $this->args['selectvalues'];
    /*
    $bbox = explode(',', $this->args['bbox']);
    $minx = $bbox[0];
    $miny = $bbox[1];
    $maxx = $bbox[2];
    $maxy = $bbox[3];
    
    $bbox = explode(',', $this->args['bboxSelect']);
    $minxSelect = $bbox[0];
    $minySelect = $bbox[1];
    $maxxSelect = $bbox[2];
    $maxySelect = $bbox[3];
    
    $field  = isset($this->args['selectfield']) ? $this->args['selectfield'] : null;
    $layer  = isset($this->args['selectlayer']) ? $this->args['selectlayer'] : null;
    $layers = isset($this->args['layers'])      ? $this->args['layers']      : null;
    
    $wms = new WMSServer();
    
    $mapInitURL = $wms->getMapBaseURL(); // js variable
    $urlParts = parse_url($mapInitURL);
    parse_str($urlParts['query'], $queryParts);
    $mapLayers = $queryParts['layers'];
    
    $wms->disableAllLayers();
    //$mapBaseURL = $wms->getMapBaseUrl();
    //$wms->enableAllLayers();
    
    // extract url components and remove the 'layers' param
    $mapInitURL = $wms->getMapBaseUrl();
    $urlParts = parse_url($mapInitURL);
    parse_str($urlParts['query'], $queryParts);
    $baseLayers = $queryParts['layers'];
    $layers = explode(',', $mapLayers);
    $titles = $wms->getLayerTitles(); // to be encoded into a js var
    
    $labels = array();
    foreach ($titles as $title => $layerNames) {
      $labels[] = array(
        'id'    => 'chk'.str_replace(' ', '_', $title),
        'value' => implode(',', $layerNames),
        'title' => $title,
      );
    }
    $this->assign('labels', $labels);
    
    unset($queryParts['layers']);
    unset($queryParts['styles']);
    $urlParts['query'] = http_build_query($queryParts);
    
    $mapBaseURL = $urlParts['scheme'] . '://'
                . $urlParts['host']
                . $urlParts['path'] . '?'
                . $urlParts['query']; // js variable
    
    $detailBaseUrl     = $this->detailUrlForBBox(null);
    $fullscreenBaseURL = $this->fullscreenUrlForBBox(null);
    
    $mapOptions = '&' . http_build_query(array(
      'crs' => 'EPSG:2249',
    ));

    $layerTitles = json_encode($titles);
    
    $script = <<<JS
      var mapSelect = "$selectvalue";
      var initMapBoxW = $minx;
      var initMapBoxN = $maxy;
      var initMapBoxS = $miny;
      var initMapBoxE = $maxx;
      var selectMapBoxW = $minxSelect;
      var selectMapBoxN = $maxySelect;
      var selectMapBoxS = $minySelect;
      var selectMapBoxE = $maxxSelect;
      var mapLayers = "$mapLayers";
      var mapBaseURL = "$mapBaseURL";
      var mapOptions = "$mapOptions";
      var layerTitles = $layerTitles;
      var detailBaseURL = '{$detailBaseUrl}';
      var fullscreenBaseURL = '{$fullscreenBaseURL}';
      
      // from http://www.w3schools.com/jsref/jsref_sort.asp
      function sortNumber(a,b) {
          return a - b;
      }
      
      function saveOptions(strFormID) {
      // Applies full-screen map-option changes and hides the form
          var newLayers = "$baseLayers";
JS;

    foreach ($titles as $title => $layerNames) {
      $chkTitle = 'chk'.str_replace(' ', '_', $title);
      $script .= 
  "      if (document.mapform.$chkTitle.checked) {\n".
  "          newLayers = newLayers + ',' + document.mapform.$chkTitle.value;\n".
  "      }";
    }
      
    $script .= <<<JS
          var layerArr = newLayers.split(",");
          layerArr.sort(sortNumber);
          newLayers = layerArr.join(",");
      
          // Only load a new map image if the user actually changed some options
          if(newLayers!=mapLayers) {
              mapLayers = newLayers;
              loadImage(getMapURL(mapBaseURL),'mapimage'); 
          }
      
          hide("options");
      }
JS;

    $this->addInlineJavascript($script);
    
    $resizeScript = "scrollTo(0,1); rotateScreen(); setTimeout('rotateMap()',500)";
    
    $this->addOnLoad($resizeScript);
    $this->addOnOrientationChange($resizeScript);
    */
  }

  private function drillURL($drilldown, $name=NULL, $addBreadcrumb=true) {
    $args = array(
      'drilldown' => $drilldown,
    );
    if (isset($this->args['category'])) {
      $args['category'] = $this->args['category'];
    }
    if (isset($name)) {
      $args['desc'] = $name;
    }
    return $this->buildBreadcrumbURL('category', $args, $addBreadcrumb);
  }
  
  private function categoryURL($category=NULL, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('category', array(
      'category' => isset($category) ? $category : $_REQUEST['category'],
    ), $addBreadcrumb);
  }

  /*  
  private function detailURL($args, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('detail', array(
      'selectvalues' => $args['name'],
      'category'     => $args['category'],
      'center'       => $args['center'],
      'zoom'         => $args['zoom']
    ), $addBreadcrumb);
  }
  */

  private function detailURL($name, $category, $info=null, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('detail', array(
      'selectvalues' => $name,
      'category'     => $category,
      'info'         => $info,
    ), $addBreadcrumb);
  }
  
  private function getTitleForSearchResult($result) {
    return $result->getTitle();
    /*
    if (isset($result->attributes->{'Building Name'})) {
      return $result->attributes->{'Building Name'};
    } else {
      return $result->value;
    }
    */
  }

  /*  
  private function detailURLArgsForResult($result) {
    return array(
      'selectvalues' => $this->getTitleForSearchResult($result),
      'info'         => $result->attributes,
    );
  }
  */

  private function detailURLArgsForResult($title, $category) {
    return array(
      'selectvalues' => $title,
      'category' => $category,
      //'selectvalues' => $this->getTitleForSearchResult($result),
      //'info'         => $result->attributes,
    );
  }
  
  //private function detailURLForResult($result, $addBreadcrumb=true) {
  //  return $this->buildBreadcrumbURL('detail', 
  //    $this->detailURLArgsForResult($result), $addBreadcrumb);
  private function detailURLForResult($title, $category, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('detail', 
      $this->detailURLArgsForResult($title, $category), $addBreadcrumb);
  }

  private function detailUrlForPan($direction, $mapController) {
    $args = $this->args;
    $center = $mapController->getCenterForPanning($direction);
    $args['center'] = $center['lat'] .','. $center['lon'];
    return $this->buildBreadcrumbURL('detail', $args, false);
  }

  private function detailUrlForZoom($direction, $mapController) {
    $args = $this->args;
    $args['zoom'] = $mapController->getLevelForZooming($direction);
    return $this->buildBreadcrumbURL('detail', $args, false);
  }

  private function detailUrlForBBox($bbox=null) {
    $args = $this->args;
    if (isset($bbox)) {
      $args['bbox'] = $bbox;
    }
    return $this->buildBreadcrumbURL('detail', $args, false);
  }
  
  private function fullscreenUrlForBBox($bbox=null) {
    $args = $this->args;
    if (isset($bbox)) {
      $args['bbox'] = $bbox;
    }
    return $this->buildBreadcrumbURL('fullscreen', $args, false);
  }

  public function federatedSearch($searchTerms, $maxCount, &$results) {
    $searchResults = array_values(searchCampusMap($searchTerms)->results);
    
    $limit = min($maxCount, count($searchResults));
    for ($i = 0; $i < $limit; $i++) {
      $result = array(
        'title' => $this->getTitleForSearchResult($searchResults[$i]),
        'url'   => $this->buildBreadcrumbURL("/{$this->id}/detail", 
          $this->detailURLArgsForResult($searchResults[$i]), false),
      );
      $results[] = $result;
    }

    return count($searchResults);
  }

    private function getLayer($index) {
        if (isset($this->feeds[$index])) {
            $feedData = $this->feeds[$index];
            $controller = MapLayerDataController::factory($feedData);
            $controller->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));
            return $controller;
        } else {
            throw new Exception("Error getting layer for index $index");
        }
    }

  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;
        
      case 'index':
        if (!$this->feeds)
            $this->feeds = $this->loadFeedData();

        $categories = array();
        foreach ($this->feeds as $id => $feed) {
            $categories[] = array(
                'title' => $feed['TITLE'],
                'url' => $this->categoryURL($id),
                );
        }

        // TODO show category description in cell subtitles
        $this->assign('categories', $categories);
        break;
        
      case 'search':
        if (isset($this->args['filter'])) {
          $searchTerms = $this->args['filter'];
          if (!$this->feeds)
              $this->feeds = $this->loadFeedData();

          $searchResults = array();
          $numResults = 0;
          foreach ($this->feeds as $id => $feed) {
              $layer = $this->getLayer($id);
              if ($layer->canSearch()) {
                  $results = $layer->search($searchTerms);
                  if (count($results)) {
                      $searchResults[$id] = $layer->search($searchTerms);
                      $numResults += count($searchResults[$id]);
                      $lastSearchedLayer = $id;
                  }
              }
          }

          if ($numResults == 1) {
            $title = $this->getTitleForSearchResult($searchResults[$lastSearchedLayer][0]);
            $this->redirectTo('detail', $this->detailURLArgsForResult($title, $lastSearchedLayer));
          } else {
            $places = array();
            foreach ($searchResults as $category => $results) {
              foreach ($results as $result) {
                $title = $this->getTitleForSearchResult($result);
                $place = array(
                  'title' => $title,
                  'url' => $this->detailURLForResult($result->getIndex(), $category),
                );
                $places[] = $place;
              }
            }
            
            $this->assign('searchTerms', $searchTerms);
            $this->assign('places',      $places);
          }
          
        } else {
          $this->redirectTo('index');
        }
        break;
        
      case 'category':
        if (isset($this->args['category'])) {
          $category = $this->args['category'];

          if (!$this->feeds)
              $this->feeds = $this->loadFeedData();

          $categories = array();
          foreach ($this->feeds as $id => $feed) {
              $categories[] = array(
                  'id' => $id,
                  'title' => $feed['TITLE'],
                  );
          }

          $layer = $this->getLayer($category);
          
          // TODO some categories have subcategories
          // they will return lists of categories instead of lists of features
          
          $features = $layer->getFeatureList();
          $places = array();
          foreach ($features as $feature) {
            $title = $feature->getTitle();
            $places[] = array(
              'title' => $title,
              'url'   => $this->detailURL($feature->getIndex(), $category),
            );
          }

          $this->assign('title',      $layer->getTitle());
          $this->assign('places',     $places);          
          $this->assign('categories', $categories);
          
        } else {
          $this->redirectTo('index');
        }
        break;
      
      case 'detail':
        $detailConfig = $this->loadWebAppConfigFile('map-detail', 'detailConfig');        
        $tabKeys = array();
        $tabJavascripts = array();
        
        
        $index    = $this->args['selectvalues'];
        //$details = $this->args['info'];
        
        // Map Tab
        $tabKeys[] = 'map';

        // TODO all this should be moved to initializeMap() once its working

        //$hasMap = $this->initializeMap($name, $details);
        $hasMap = true;
        $this->assign('hasMap', $hasMap);

        if (!$this->feeds)
            $this->feeds = $this->loadFeedData();

        $layer = $this->getLayer($this->args['category']);

        $feature = $layer->getFeature($index);
        $name = $feature->getTitle();
        $geometry = $feature->getGeometry();
        $style = $feature->getStyleAttribs();
        $style['title'] = $name;

        // center
        if (isset($this->args['center'])) {
            $latlon = explode(",", $this->args['center']);
            $center = array('lat' => $latlon[0], 'lon' => $latlon[1]);
        } else {
            $center = $geometry->getCenterCoordinate();
        }

        // zoom
        if (isset($this->args['zoom'])) {
            $zoomLevel = $this->args['zoom'];
        } else {
            // TODO get default zoom level based on static map class
            $zoomLevel = 14;
        }

        // image size
        switch ($this->pagetype) {
            case 'compliant':
                $imageWidth = 290; $imageHeight = 190;
                break;
       
            case 'basic':
                if ($GLOBALS['deviceClassifier']->getPlatform() == 'bbplus') {
                    $imageWidth = 410; $imageHeight = 260;
                } else {
                    $imageWidth = 200; $imageHeight = 200;
                }
                break;
        }
        $this->assign('imageHeight', $imageHeight);
        $this->assign('imageWidth',  $imageWidth);

        $mapControllers = array();
        $mapControllers[] = $layer->getStaticMapController();
        if ($this->pagetype == 'compliant' && $layer->supportsDynamicMap()) {
            $mapControllers[] = $layer->getDynamicMapController();
        }

        foreach ($mapControllers as $mapController) {

            $mapController->setCenter($center);
            $mapController->setZoomLevel($zoomLevel);

            switch ($geometry->getType()) {
                case 'Point':
                    if ($mapController->canAddAnnotations()) {
                        $mapController->addAnnotation($center['lat'], $center['lon'], $style);
                    }
                    break;
                case 'Polyline':
                    if ($mapController->canAddPaths()) {
                        $mapController->addPath($geometry->getPoints(), $style);
                    }
                    break;
                default:
                    break;
            }

            $mapController->setImageWidth($imageWidth);
            $mapController->setImageHeight($imageHeight);

            if ($mapController->isStatic()) {

                $this->assign('imageUrl', $mapController->getImageURL());

                $this->assign('scrollNorth', $this->detailUrlForPan('n', $mapController));
                $this->assign('scrollEast', $this->detailUrlForPan('e', $mapController));
                $this->assign('scrollSouth', $this->detailUrlForPan('s', $mapController));
                $this->assign('scrollWest', $this->detailUrlForPan('w', $mapController));

                $this->assign('zoomInUrl', $this->detailUrlForZoom('in', $mapController));
                $this->assign('zoomOutUrl', $this->detailUrlForZoom('out', $mapController));

            } else {
                $mapController->setMapElement('mapimage');
                $this->addExternalJavascript($mapController->getIncludeScript());
                $this->addInlineJavascript($mapController->getHeaderScript());
                $this->addInlineJavascriptFooter('hideMapTabChildren();');
                $this->addInlineJavascriptFooter($mapController->getFooterScript());
            }

        }

        /*
        // Photo Tab
        $photoFile = null;
        if (array_key_exists('PHOTO_FILE', $details)) {
          $photoFile = rawurlencode($details['PHOTO_FILE']);
          
        } elseif (array_key_exists('Photo', $details)) {
          $photoFile = rawurlencode($details['Photo']);
        }
        
        $photoUrl = '';
        if (isset($photoFile) && $photoFile != 'Null') {
          $tabKeys[] = 'photo';
          $tabJavascripts['photo'] = "loadPhoto(photoURL,'photo');";
          $photoUrl = $GLOBALS['siteConfig']->getVar('MAP_PHOTO_SERVER').$photoFile;
          $this->assign('photoUrl', $photoUrl);
        }
        $this->addInlineJavascript("var photoURL = '{$photoUrl}';");
        */        
        
        // Details Tab
        $tabKeys[] = 'detail';
        $this->assign('details', $feature->getDescription());
        // for ArcGIS data, which comes back in key/value pairs, 
        // construct a list or table in the $details html

        /*
        $displayDetails = array();
        foreach ($details as $field => $value) {
          $value = trim($value);
          if (strlen(trim($value))) {
            if (!in_array($field, $detailConfig['details']['suppress'])) {
              $detail = array(
                'label' => $field,
                'title' => $value,
              );
              // There is a bug in some versions of strtr where it can't handle hyphens in hostnames
              if (filter_var(strtr($value, '-', '_'), FILTER_VALIDATE_URL)) {
                $detail['url'] = $value;
              }
              $displayDetails[] = $detail;
            }
          }
        }
        $this->assign('name', $name);
        $this->assign('address', $details['Address']);
        $this->assign('details', $displayDetails);
        */
        $this->enableTabs($tabKeys, null, $tabJavascripts);
        break;
        
      case 'fullscreen':
        $this->initializeFullscreenMap();
        break;
    }
  }
}
