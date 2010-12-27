<?php

class ArcGISDataController extends MapLayerDataController
{
    protected $DEFAULT_PARSER_CLASS = 'ArcGISParser';
    protected $parserClass = 'ArcGISParser';
    protected $filters = array('f' => 'json');

    protected function cacheFileSuffix()
    {
        return '.js'; // json
    }
    
    protected function cacheFolder()
    {
        return $GLOBALS['siteConfig']->getVar('ARCGIS_CACHE');
    }
    
    public function projectsFeatures() {
        return true;
    }
    
    public function getProjection() {
        return $this->parser->getProjection();
    }

    public function getSubLayerNames() {
        return $this->parser->getSubLayerNames();
    }

    public function getItem($name)
    {
        $theItem = null;
        $items = $this->getFeatureList();
        if (isset($items[$name])) {
            $theItem = $items[$name];
            if (!$this->returnsGeometry || $theItem->getGeometry() == null) {
                $featureInfo = $this->queryFeatureServer($theItem);
                $theItem->setGeometryType($featureInfo['geometryType']);
                $theItem->readGeometry($featureInfo['geometry']);
            }

            // TODO fragile way of getting photos
            if ($theItem->getField('Photo') === null) {
                if (!isset($featureInfo))
                    $featureInfo = $this->queryFeatureServer($theItem);

                $photoFields = array('PHOTO_FILE', 'Photo', 'Photo File');
                foreach ($photoFields as $field) {
                    if (isset($featureInfo['attributes'][$field])) {
                        $theItem->setField('Photo', $featureInfo['attributes'][$field]);
                        break;
                    }
                }
            }
        }
        return $theItem;
    }
    
    // TODO this way of getting supplementary geometry is particular to Harvard's setup
    public function queryFeatureServer($feature) {
        $featureCache = new DiskCache($GLOBALS['siteConfig']->getVar('ARCGIS_FEATURE_CACHE'), 86400*7, true);
        $searchFieldCandidates = array('Building Number', 'Building Name', 'Building');
        foreach ($searchFieldCandidates as $field) {
            $searchField = $field;
            $bldgId = $feature->getField($field);
            if ($bldgId) {
                break;
            }
        }
        if (!$featureCache->isFresh($bldgId)) {
            if (!$this->returnsGeometry) {
                $queryBase = $GLOBALS['siteConfig']->getVar('ARCGIS_FEATURE_SERVER');
            } else {
                $queryBase = $this->baseURL;
            }

            $query = http_build_query(array(
                'searchText'     => $bldgId,
                'searchFields'   => $searchField,
                'contains'       => 'false',
                'sr'             => '',
                'layers'         => 0,
                'returnGeometry' => 'true',
                'f'              => 'json',
                ));

            $json = file_get_contents($queryBase . '/find?' . $query);
            $jsonObj = json_decode($json, true);
        
            if (isset($jsonObj['results']) && count($jsonObj['results'])) {
                $result = $jsonObj['results'][0];
                $featureCache->write($result, $bldgId);
            } else {
                error_log("could not find building $bldgId", 0);
            }
        }

        $result = $featureCache->read($bldgId);
        return $result;
    }
    
    public function selectSubLayer($layerId) {
        $this->parser->selectSubLayer($layerId);
    }

    public function getTitle() {
        if (!$this->parser->isPopulated()) {
            $data = $this->getData();
            $this->items = $this->parseData($data);
        }
        return $this->parser->getMapName();
    }
    
    public function items() {
        if (!$this->parser->isPopulated()) {
            $data = $this->getData();
            $this->parseData($data);
        }
        if (!$this->parser->selectedLayerIsInitialized()) {
            // set this directly so we don't interfere with cache
            $oldBaseURL = $this->baseURL;
            $this->parser->setBaseURL($oldBaseURL);
            $this->baseURL = $this->parser->getURLForSelectedLayer();
            $data = $this->getData();
            $this->parseData($data);
            $this->baseURL = $oldBaseURL;
        }
        if (!$this->parser->selectedLayerIsPopulated()) {
            $oldBaseURL = $this->baseURL;
            $this->parser->setBaseURL($oldBaseURL);
            $this->baseURL = $this->parser->getURLForLayerFeatures();
            $oldFilters = $this->filters;
            $this->filters = $this->parser->getFiltersForLayer();
            $data = $this->getData();
            $this->parseData($data);
            $this->filters = $oldFilters;
            $this->baseURL = $oldBaseURL;
        }
        return $this->parser->getFeatureList();
    }
    
    public static function parserFactory($baseURL) {
        $throwawayController = new ArcGISDataController();
        $throwawayController->init(array('BASE_URL' => $baseURL));
        $data = $throwawayController->getData();
        $throwawayController->parseData($data);
        return $throwawayController->parser;
    }
    
}

