<?php

abstract class MapImageController
{
    const STYLE_LINE_WEIGHT = 'weight';
    const STYLE_LINE_ALPHA = 'alpha';
    const STYLE_LINE_COLOR = 'color';

    //protected $initialBoundingBox;
    //protected $boundingBox;

    //protected $baseURL;
    protected $center = null; // array('lat' => 0.0, 'lon' => 0.0), or address

    protected $zoomLevel = null;
    protected $maxZoomLevel;
    protected $minZoomLevel;

    protected $imageWidth = 300;
    protected $imageHeight = 300;

    //protected $imageFormat = 'png';
    //protected static $supportedImageFormats = array('png', 'jpg');

    // layers are sets of overlays that span the full range of the map
    // as opposed to a selection
    protected static $availableLayers = array(); // array of all map layers
    protected $enabledLayers = array(); // array of map layers to show
    protected $layerStyles = array(); // id => styleName

    // capabilities
    protected $canAddAnnotations = false;
    protected $canAddPaths = false;
    protected $canAddLayers = false;

    // final function that generates url for the img src argument
    //abstract public function getImageURL();

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

    //public function getHorizontalRange()
    //{
    //    return 0.01;
    //}

    //public function getVerticalRange()
    //{
    //    return 0.01;
    //}

    public function getLayerNames()
    {
        return self::$availableLayers;
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

    /*
    // n, s, e, w, ne, nw, se, sw
    public function getCenterForPanning($direction) {
        $vertical = null;
        $horizontal = null;

        if (preg_match('/[ns]/', $direction, $matches)) {
            $vertical = $matches[0];
        }
        if (preg_match('/[ew]/', $direction, $matches)) {
            $horizontal = $matches[0];
        }

        $center = $this->center;

        if ($horizontal == 'e') {
            $center['lon'] += $this->getHorizontalRange() / 2;
        } else if ($horizontal == 'w') {
            $center['lon'] -= $this->getHorizontalRange() / 2;
        }

        if ($vertical == 'n') {
            $center['lat'] += $this->getVerticalRange() / 2;
        } else if ($vertical == 's') {
            $center['lat'] -= $this->getVerticalRange() / 2;
        }

        return $center;
    }

    public function getLevelForZooming($direction) {
        $zoomLevel = $this->zoomLevel;
        if ($direction == 'in') {
            if ($zoomLevel < $this->maxZoomLevel)
                $zoomLevel += 1;
        } else if ($direction == 'out') {
            if ($zoomLevel > $this->minZoomLevel)
                $zoomLevel -= 1;
        }
        return $zoomLevel;
    }
    */

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
        $this->enabledLayers = self::$availableLayers;
    }

    public function disableAllLayers()
    {
        $this->enabledLayers = array();
    }

    protected function isEnabledLayer($layer) {
        return in_array($layer, $this->enabledLayers);
    }

    protected function isAvailableLayer($layer) {
        return in_array($layer, self::$availableLayers);
    }

    // setters
    //public function setImageFormat($format) {
    //    if (in_array($format, self::$supportedImageFormats)) {
    //        $this->imageFormat = $format;
    //    }
    //}

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


