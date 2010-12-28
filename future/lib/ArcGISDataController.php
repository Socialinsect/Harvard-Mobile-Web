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
    
    public function selectSubLayer($layerId) {
        $this->parser->selectSubLayer($layerId);
    }

    public function getTitle() {
        $this->initializeParser();
        return $this->parser->getMapName();
    }
    
    public function items() {
        $this->initializeParser();
        $this->initializeLayers();
        $this->initializeFeatures();
        return $this->parser->getFeatureList();
    }
    
    private function initializeParser() {
        if (!$this->parser->isPopulated()) {
            $data = $this->getData();
            $this->parseData($data);
        }
    }
    
    private function initializeFeatures() {
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
    }
    
    private function initializeLayers() {
        if (!$this->parser->selectedLayerIsInitialized()) {
            // set this directly so we don't interfere with cache
            $oldBaseURL = $this->baseURL;
            $this->parser->setBaseURL($oldBaseURL);
            $this->baseURL = $this->parser->getURLForSelectedLayer();
            $data = $this->getData();
            $this->parseData($data);
            $this->baseURL = $oldBaseURL;
        }
    }
    
    public static function parserFactory($baseURL) {
        $throwawayController = new ArcGISDataController();
        $throwawayController->init(array('BASE_URL' => $baseURL));
        $data = $throwawayController->getData();
        $throwawayController->parseData($data);
        return $throwawayController->parser;
    }
    
    // TODO in the following functions
    // this way of getting supplementary geometry is particular to Harvard's setup
    
    private static function getSupplementaryFeatureData($bldgId, $searchField, $queryBase) {
        // TODO don't use a shared cache file if queryBase isn't the default
        $featureCache = new DiskCache($GLOBALS['siteConfig']->getVar('ARCGIS_FEATURE_CACHE'), 86400*7, true);
        if (!$featureCache->isFresh($bldgId)) {
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
    
    public function getFeatureByField($searchField, $value) {
        if (!$this->returnsGeometry) {
            $queryBase = $GLOBALS['siteConfig']->getVar('ARCGIS_FEATURE_SERVER');
        } else {
            $queryBase = $this->baseURL;
        }
        $this->initializeParser();
        $this->initializeLayers();
        $featureInfo = self::getSupplementaryFeatureData($value, $searchField, $queryBase);
        $feature = $this->parser->featureFromJSON($featureInfo);
        return $feature;
    }

    // TODO move searchField strings from the following two
    // functions into config
    private function queryFeatureServer($feature) {
        if (!$this->returnsGeometry) {
            $queryBase = $GLOBALS['siteConfig']->getVar('ARCGIS_FEATURE_SERVER');
        } else {
            $queryBase = $this->baseURL;
        }
        
        $searchFieldCandidates = array('Building Number', 'Building Name', 'Building');
        foreach ($searchFieldCandidates as $field) {
            $searchField = $field;
            $bldgId = $feature->getField($field);
            if ($bldgId) {
                break;
            }
        }
        return self::getSupplementaryFeatureData($bldgId, $searchField, $queryBase);
    }
    
    public static function getBldgByNumber($bldgId) {
        $queryBase = $GLOBALS['siteConfig']->getVar('ARCGIS_FEATURE_SERVER');
        return ArcGISDataController::getSupplementaryFeatureData($bldgId, 'Building Number', $queryBase);
    }
    
}

