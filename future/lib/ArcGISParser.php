<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

// sort addresses using natsort
// but move numbers to the end first
function addresscmp($addr1, $addr2) {
  $addr1 = preg_replace('/^([\d\-\.]+)(\s*)(.+)/', '${3}${2}${1}', $addr1);
  $addr2 = preg_replace('/^([\d\-\.]+)(\s*)(.+)/', '${3}${2}${1}', $addr2);
  return strnatcmp($addr1, $addr2);
}

class ArcGISPoint implements MapGeometry
{
    private $x;
    private $y;

    public function __construct($geometry)
    {
        $this->x = $geometry['x'];
        $this->y = $geometry['y'];
    }
    
    public function getCenterCoordinate()
    {
        return array('lat' => $this->y, 'lon' => $this->x);
    }
    
    public function getType()
    {
        return 'Point';
    }
}

class ArcGISPolygon implements MapGeometry
{
    public function __construct($geometry)
    {
    }

    public function getCenterCoordinate()
    {
    }
    
    public function getType()
    {
        return 'Polygon';
    }
}

class ArcGISFeature implements MapFeature
{
    private $index;
    private $attributes;
    private $geometry;
    private $titleField;
    private $geometryType;
    
    // if we want to turn off display for certain fields
    // TODO put this in a more accessible place
    private $blackList;
    
    public function __construct($attributes, $geometry=null)
    {
        $this->attributes = $attributes;
        $this->geometry = $geometry;
    }
    
    public function setId($id) {
        $this->attributes['modolabs:_id'] = $id;
        $this->setIdField('modolabs:_id');
    }

    public function setIndex($index)
    {
        $this->index = $index;
    }

    public function getIndex()
    {
        return $this->index;
    }
    
    public function setGeometryType($geomType)
    {
        $this->geometryType = $geomType;
    }
    
    public function setTitleField($field)
    {
        $this->titleField = $field;
    }
    
    //////// MapFeature interface

    public function getTitle()
    {
        return $this->attributes[$this->titleField];
    }
    
    public function getGeometry()
    {
        $geometry = null;
        switch ($this->geometryType) {
        case 'esriGeometryPoint':
            $geometry = new ArcGISPoint($this->geometry);
            break;
        case 'esriGeometryPolygon':
            $geometry = new ArcGISPolygon($this->geometry);
            break;
        }
        return $geometry;
    }
    
    public function getDescription()
    {
        $description = '<ul>';
        foreach ($this->attributes as $name => $value) {
            if ($name != 'Shape' && $name != 'geometry' && $name != $this->titleField) {
                $description .= '<li><b>'.$name.':</b> '.$value.'</li>';
            }
        }
        $description .= '</ul>';
        return $description;
    }

    public function getStyleAttribs()
    {
        return null;
    }
}

class ArcGISParser extends DataParser
{
    public $singleFusedMapCache; // indicates whether we have map tiles
    public $initialExtent;
    public $fullExtent;
    public $serviceDescription;
    public $spatialRef;
    
    private $mapName;
    private $id;
    
    // sublayers are known to arcgis as layers
    // but we call them sublayers since we are known to our datacontroller as a layer
    private $subLayers = array();
    private $selectedLayer = null;
    private $isPopulated = false;

    public function parseData($contents)
    {
        if (!$this->isPopulated) { // initial parse
            $data = json_decode($contents, true);

            $this->serviceDescription = $data['serviceDescription'];
            $this->mapName = $data['mapName'];

            $this->spatialRef = $data['spatialReference']['wkid'];
            $this->initialExtent = $data['initialExtent'];

            $this->fullExtent = $data['fullExtent'];

            // assume these are always the same as the overall spatial ref
            unset($this->initialExtent['spatialReference']);
            unset($this->fullExtent['spatialReference']);

            $this->singleFusedMapCache = $data['singleFusedMapCache'];

            foreach ($data['layers'] as $layerData) {
                $id = $layerData['id'];
                $name = $layerData['name'];
                $this->subLayers[$id] = new ArcGISLayer($id, $name);
            }
            
            $this->selectDefaultLayer();
            $this->isPopulated = true;

        } else {
            $this->selectedLayer->parseData($contents);
        }
    }
    
    public function getProjection()
    {
        return $this->spatialRef;
    }
    
    public function isPopulated() {
        return $this->isPopulated;
    }

    public function getMapName() {
        return $this->mapName;
    }

    ////// functions dispatched to selected layer

    public function query($text='') {
        return $this->selectedLayer->query($text);
    }

    public function getFeatureList() {
        return $this->selectedLayer->getFeatureList();
    }

    public function getDefaultSearchField() {
        return $this->selectedLayer->getDisplayField();
    }
    
    public function selectedLayerIsPopulated() {
        return $this->selectedLayer->isPopulated();
    }
    
    public function getURLForSelectedLayer($baseURL) {
        return $baseURL.'/'.$this->selectedLayer->getId();
    }
    
    public function selectedLayerIsInitialized() {
        return $this->selectedLayer && $this->selectedLayer->isInitialized();
    }
    
    public function getURLForLayerFeatures($baseURL) {
        return $baseURL.'/'.$this->selectedLayer->getId().'/query';
    }
    
    public function getFiltersForLayer() {
        return $this->selectedLayer->getFilters();
    }
    
    /////// sublayer functions
    
    public function selectDefaultLayer() {
        $this->selectSubLayer(0);
    }
    
    public function selectSubLayer($layerId) {
        if (isset($this->subLayers[$layerId])) {
            $this->selectedLayer = $this->getSubLayer($layerId);
        }
    }
    
    public function getSubLayerNames() {
        $result = array();
        foreach ($this->subLayers as $id => $sublayer) {
            $result[$id] = $sublayer->getTitle();
        }
        return $result;
    }

    private function getSubLayer($layerId) {
        if (isset($this->subLayers[$layerId])) {
            return $this->subLayers[$layerId];
        }
        return null;
    }

}

class ArcGISLayer {
    private $id;
    private $name;

    private $fieldNames;
    private $extent;
    private $minScale;
    private $maxScale;
    private $displayField;
    private $spatialRef;
    private $geometryType;
    private $isInitialized = false;
    
    private $features = array();
    private $isPopulated = false;
    
    public function __construct($id, $name) {
        $this->id = $id;
        $this->name = $name;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getTitle() {
        return $this->name;
    }
    
    public function isPopulated() {
        return $this->isPopulated;
    }
    
    public function isInitialized() {
        return $this->isInitialized;
    }
    
    public function parseData($contents) {
        if (!$this->isInitialized) {
            $data = json_decode($contents, true);
        
            $this->name = $data['name'];
            $this->minScale = $data['minScale'];
            $this->maxScale = $data['maxScale'];
            $this->displayField = $data['displayField'];
            $this->geometryType = $data['geometryType'];
            $this->extent = array(
                'xmin' => $data['extent']['xmin'],
                'xmax' => $data['extent']['xmax'],
                'ymin' => $data['extent']['ymin'],
                'ymax' => $data['extent']['ymax'],
            );
            $this->spatialRef = $data['extent']['spatialReference'];
        
            foreach ($data['fields'] as $fieldInfo) {
                $this->fieldNames[$fieldInfo['name']] = $fieldInfo['alias'];
            }
    
            $this->isInitialized = true;
        } else if (!$this->isPopulated) {
            $data = json_decode($contents, true);

            $result = array();
            foreach ($data['features'] as $featureInfo) {
                $attribs = $featureInfo['attributes'];
                $displayAttribs = array();
                // use human-readable field alias to construct feature details
                foreach ($attribs as $name => $value) {
                    $displayAttribs[$this->fieldNames[$name]] = $value;
                }
                $feature = new ArcGISFeature($displayAttribs, $featureInfo['geometry']);
                $feature->setIndex(count($result));
                $feature->setTitleField($this->fieldNames[$this->displayField]);
                $feature->setGeometryType($this->geometryType);
                $result[$feature->getIndex()] = $feature;
            }
            uksort($result, 'addresscmp');

            $this->features = $result;
            $this->isPopulated = true;
        }
    }

    public function getGeometryType() {
        return $this->geometryType;
    }

    public function getDisplayField() {
        return $this->displayField;
    }
    
    public function getFeatureList() {
        return $this->features;
    }
    
    public function getFilters() {
        $bbox = $this->extent['xmin'].','.$this->extent['ymin'].','
               .$this->extent['xmax'].','.$this->extent['ymax'];
        
        $filters = array(
            'text'           => '',
            'geometry'       => $bbox,
            'geometryType'   => 'esriGeometryEnvelope',
            'inSR'           => $this->spatialRef,
            'spatialRel'     => 'esriSpatialRelIntersects',
            'where'          => '',
            'returnGeometry' => 'true',
            'outSR'          => '',
            'outFields'      => implode(',', array_keys($this->fieldNames)),
            'f'              => 'json',
        );
        
        return $filters;
    }
}



