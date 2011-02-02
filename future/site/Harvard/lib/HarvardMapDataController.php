<?php

class HarvardMapDataController extends ArcGISDataController
{
    protected $returnsGeometry = true;
    protected $isSearchLayer = false;

    protected function init($args)
    {
        parent::init($args);

        if (isset($args['DYNAMIC_ZOOM_LEVEL']))
            $this->dynamicZoomLevel = $args['DYNAMIC_ZOOM_LEVEL'];
        
        if (isset($args['ARCGIS_LAYER_ID']))
            $this->parser->setDefaultLayer($args['ARCGIS_LAYER_ID']);

        if (isset($args['RETURNS_GEOMETRY']))
            $this->returnsGeometry = $args['RETURNS_GEOMETRY'];
    }
    
    public function getItem($name)
    {
        $theItem = null;
        
        if ($this->searchable) {
            $this->initializeParser();
            $this->initializeLayers();
            $featureInfo = self::getBldgDataByNumber($name);
            if ($featureInfo) {
                $theItem = $this->parser->featureFromJSON($featureInfo);
                // cheating here as i'm not sure when fields get
                // returned as column ids or aliases
                $theItem->setTitleField("Building Name");
            }

        } else {
            $items = $this->items();
            if (isset($items[$name])) {
                $theItem = $items[$name];
                if (!$this->returnsGeometry || $theItem->getGeometry() == null) {
                    $featureInfo = $this->queryFeatureServer($theItem);
                    $theItem->setGeometryType($featureInfo['geometryType']);
                    $theItem->readGeometry($featureInfo['geometry']);
                }

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
        }
        return $theItem;
    }
    
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
    
    public static function getBldgDataByNumber($bldgId) {
        $queryBase = $GLOBALS['siteConfig']->getVar('ARCGIS_FEATURE_SERVER');
        return self::getSupplementaryFeatureData($bldgId, 'Building Number', $queryBase);
    }
    
    public function setIsSearchLayer($isSearchLayer) {
        $this->isSearchLayer = $isSearchlayer;
    }

}
