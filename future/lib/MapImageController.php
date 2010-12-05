<?php

abstract class MapImageController
{
    const STYLE_LINE_WEIGHT = 'weight';
    const STYLE_LINE_ALPHA = 'alpha';
    const STYLE_LINE_COLOR = 'color';

    protected $center = null; // array('lat' => 0.0, 'lon' => 0.0), or address

    protected $zoomLevel = null;
    protected $maxZoomLevel;
    protected $minZoomLevel;

    protected $imageWidth = 300;
    protected $imageHeight = 300;

    // layers are sets of overlays that span the full range of the map
    // as opposed to a selection
    protected $enabledLayers = array(); // array of map layers to show
    protected $layerStyles = array(); // id => styleName

    // capabilities
    protected $canAddAnnotations = false;
    protected $canAddPaths = false;
    protected $canAddLayers = false;

    // TODO decide if we will use the factory function or get rid of it
    public static function factory($imageClass)
    {
        switch ($imageClass) {
            case 'WMSStaticMap':
                require_once realpath(LIB_DIR.'/WMSStaticMap.php');
                $controller = new WMSStaticMap();
                break;
            case 'ArcGISStaticMap':
                require_once realpath(LIB_DIR.'/ArcGISStaticMap.php');
                $controller = new ArcGISStaticMap();
                break;
            case 'GoogleJSMap':
                require_once realpath(LIB_DIR.'/GoogleJSMap.php');
                $controller = new GoogleJSMap();
                break;
            case 'GoogleStaticMap':
            default:
                require_once realpath(LIB_DIR.'/GoogleStaticMap.php');
                $controller = new GoogleStaticMap();
                break;
        }
        return $controller;
    }

    // query functions
    public function isStatic() {
        return false;
    }

    public function getCenter()
    {
        return $this->center;
    }

    public function getAvailableLayers()
    {
        return array();
    }

    public function canAddAnnotations()
    {
        return $this->canAddAnnotations;
    }

    public function canAddPaths()
    {
        return $this->canAddPaths;
    }

    public function canAddLayers()
    {
        return $this->canAddlayers;
    }

    // overlays
    public function addAnnotation($latitude, $longitude, $style=null)
    {
    }

    public function addPath($points, $style=null)
    {
    }

    public function enableLayer($layer)
    {
        if (!$this->isEnabledLayer($layer) && $this->isAvalableLayer($layer)) {
            $this->enabledLayers[] = $layer;
        }
    }

    public function disableLayer($layer)
    {
        $position = array_search($layer, $this->enabledLayers);
        if ($position !== false) {
            $this->enabledLayers = array_splice(
                $this->enabledLayers,
                $position,
                1);
        }
    }

    public function enableAllLayers()
    {
        $this->enabledLayers = $this->getAvailableLayers();
    }

    public function disableAllLayers()
    {
        $this->enabledLayers = array();
    }

    protected function isEnabledLayer($layer) {
        return in_array($layer, $this->enabledLayers);
    }

    protected function isAvailableLayer($layer) {
        return in_array($layer, $this->getAvailableLayers());
    }

    public function setCenter($center) {
        if (is_array($center)
            && isset($center['lat'])
            && isset($center['lon']))
        {
            $this->center = $center;
        }
    }

    public function setImageWidth($width) {
        $this->imageWidth = $width;
    }

    public function setImageHeight($height) {
        $this->imageHeight = $height;
    }

    public function setZoomLevel($zoomLevel)
    {
        $this->zoomLevel = $zoomLevel;
    }

    public function setBoundingBox($bbox)
    {
        $this->boundingBox = $bbox;
    }
}


