<?php

class StaticMapImageController
{
    protected $initialBoundingBox;
    protected $boundingBox;

    protected $baseURL;
    protected $center = null; // array('lat' => 0.0, 'lon' => 0.0), or address
    protected $zoomLevel = null;
    protected $imageWidth = 300;
    protected $imageHeight = 300;

    protected $imageFormat = 'png';
    protected static $supportedImageFormats = array('png', 'jpg');

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
    public function getImageURL()
    {
        return null;
    }

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
            case 'GoogleStaticMap':
            default:
                require_once realpath(LIB_DIR.'/GoogleStaticMap.php');
                $controller = new GoogleStaticMap();
                break;
        }
        return $controller;
    }

    // query functions
    public function getCenter()
    {
        return $this->center;
    }

    public function getHorizontalRange()
    {
        return 0.01;
    }

    public function getVerticalRange()
    {
        return 0.01;
    }

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

    // overlays
    public function addAnnotation($latitude, $longitude, $style=null)
    {
    }

    public function addPath($points, $lineColor, $lineWeight)
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
    public function setImageFormat($format) {
        if (in_array($format, self::$supportedImageFormats)) {
            $this->imageFormat = $format;
        }
    }

    public function setCenter($latitude, $longitude) {
        $this->center = array(
            'lat' => $latitude,
            'lon' => $longitude,
            );
    }

    public function setImageWidth($width) {
        $this->imageWidth = $width;
    }

    public function setImageHeight($height) {
        $this->imageHeight = $height;
    }

    // n, s, e, w, ne, nw, se, sw
    public function pan($direction) {
        if (preg_match('[ns]', $direction, $matches)) {
            $vertical = $matches[0];
        }
        if (preg_match('[ew]', $direction, $matches)) {
            $horizontal = $matches[0];
        }

        if ($horizontal == 'e') {
            $this->center['lon'] += $this->getHorizontalRange();
        } else if ($horizontal == 'w') {
            $this->center['lon'] -= $this->getHorizontalRange();
        }

        if ($vertical == 'n') {
            $this->center['lat'] += $this->getVerticalRange();
        } else if ($vertical == 's') {
            $this->center['lat'] -= $this->getVerticalRange();
        }
    }

    public function setZoomLevel($zoomLevel)
    {
        $this->zoomLevel = $zoomLevel;
    }

    public function zoomIn()
    {
        $this->setZoomLevel($this->zoomLevel + 1);
    }

    public function zoomOut()
    {
        $this->setZoomLevel($this->zoomLevel - 1);
    }

    public function setBoundingBox($bbox)
    {
        $this->boundingBox = $bbox;
    }
}


