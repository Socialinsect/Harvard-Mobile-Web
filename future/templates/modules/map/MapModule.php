<?php

require_once realpath(LIB_DIR.'/Module.php');
require_once realpath(LIB_DIR.'/MapLayerDataController.php');
require_once realpath(LIB_DIR.'/StaticMapImageController.php');

// detail-basic: $imageUrl $imageWidth $imageHeight $scrollNorth $scrollSouth $scrollEast $scrollWest $zoomInUrl $zoomOutUrl $photoUrl $photoWidth
// detail: $hasMap $mapPane $photoPane $detailPane $imageWidth $imageHeight $imageUrl $name $details
// search: $places

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
  
  private function initializeFullscreenMap() {
    $selectvalue = $this->args['selectvalues'];
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

  private function detailURL($name, $category, $info=null, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('detail', array(
      'selectvalues' => $name,
      'category'     => $category,
      'info'         => $info,
    ), $addBreadcrumb);
  }
  
  private function getTitleForSearchResult($result) {
    return $result->getTitle();
  }

  private function detailURLArgsForResult($title, $category) {
    return array(
      'selectvalues' => $title,
      'category' => $category,
    );
  }
  
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
        
        // Map Tab
        $tabKeys[] = 'map';

        // TODO all this should be moved to initializeMap() once its working

        //$hasMap = $this->initializeMap($name, $details);
        $hasMap = true;
        $this->assign('hasMap', $hasMap);

        if (!$this->feeds)
            $this->feeds = $this->loadFeedData();

        $layer = $this->getLayer($this->args['category']);

        $index = $this->args['selectvalues'];
        $feature = $layer->getFeature($index);
        $style['title'] = $feature->getTitle();
        $geometry = $feature->getGeometry();
        $style = $feature->getStyleAttribs();

        $this->assign('name', $feature->getTitle());
        $this->assign('address', $feature->getSubtitle());

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
            $zoomLevel = $layer->getDefaultZoomLevel();
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

            if ($mapController->supportsProjections()) {
                $mapController->setDataProjection($layer->getProjection());
            }
            
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
                case 'Polygon':
                    if ($mapController->canAddPolygons()) {
                        $mapController->addPolygon($geometry->getRings(), $style);
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

        $this->enableTabs($tabKeys, null, $tabJavascripts);
        break;
        
      case 'fullscreen':
        $this->initializeFullscreenMap();
        break;
    }
  }
}
