<?php

class WMSStaticMap extends StaticMapImageController {

    const LIFE_SIZE_METERS_PER_PIXEL = 0.00028; // standard definition at 1:1 scale
    const NO_PROJECTION = -1;
    // how much context to provide around a building relative to its size
    const OBJECT_PADDING = 1.0;

    protected $canAddAnnotations = false;
    protected $canaddPaths = false;
    protected $canAddLayers = true;
    protected $supportsProjections = true;

    protected $availableLayers = null;
    private $wmsParser;
    private $diskCache;
    private $projection = null;
    private $defaultProjection = 'CRS:84';
    private $unitsPerMeter = null;

    public function __construct($baseURL) {
        $this->baseURL = $baseURL;
        $this->diskCache = new DiskCache($GLOBALS['siteConfig']->getVar('WMS_CACHE'), 86400 * 7, true);
        $this->diskCache->preserveFormat();
        $filename = md5($this->baseURL);
        $metafile = $filename.'-meta.txt';
        
        if (!$this->diskCache->isFresh($filename)) {
            $params = array(
                'request' => 'GetCapabilities',
                'service' => 'WMS',
                );
            $query = $this->baseURL.'?'.http_build_query($params);
            file_put_contents($this->diskCache->getFullPath($metafile), $query);
            $contents = file_get_contents($query);
            $this->diskCache->write($contents, $filename);
        } else {
            $contents = $this->diskCache->read($filename);
        }
        $this->wmsParser = new WMSDataParser();
        $this->wmsParser->parseData($contents);
        $this->enableAllLayers();
        $this->setProjection(null);
    }

    public function getHorizontalRange()
    {
        return $this->bbox['xmax'] - $this->bbox['xmin'];
    }

    public function getVerticalRange()
    {
        return $this->bbox['ymax'] - $this->bbox['ymin'];
    }

    // http://wiki.openstreetmap.org/wiki/MinScaleDenominator
    private function getCurrentScale()
    {
        if ($this->unitsPerMeter === null) {
            $projCache = new DiskCache($GLOBALS['siteConfig']->getVar('PROJ_CACHE'), null, true);
            $projCache->preserveFormat();
            $filename = $this->projection;
            if (!$projCache->isFresh($filename)) {
                // mapfile is the easiest to parse of all formats offered at this website
                $url = 'http://spatialreference.org/ref/epsg/'.$this->projection.'/mapfile/';
                $contents = file_get_contents($url);
                $projCache->write($contents, $filename);
            } else {
                $contents = $projCache->read($filename);
            }

            if (preg_match('/"to_meter=([\d\.]+)"/', $contents, $matches)) {
                $this->unitsPerMeter = $matches[1];
            } else {
                $this->unitsPerMeter = self::NO_PROJECTION;
            }
        }
        if ($this->unitsPerMeter != self::NO_PROJECTION) {
            $metersPerPixel = $this->getHorizontalRange() / $this->imageWidth / $this->unitsPerMeter;
            return $metersPerPixel / self::LIFE_SIZE_METERS_PER_PIXEL;
        } else {
            // TODO this isn't quite right, this won't allow us to use
            // maxScaleDenom and minScaleDenom in any layers
            return self::NO_PROJECTION;
        }
    }
    
    private function zoomLevelForScale($scale)
    {
        // not sure if ceil is the right rounding in both cases
        if ($scale == self::NO_PROJECTION) {
            $range = $this->getHorizontalRange();
            return ceil(log(360 / $range, 2));
        } else {
            // http://wiki.openstreetmap.org/wiki/MinScaleDenominator
            return ceil(log(559082264 / $scale, 2));
        }
    }
    
    // currently the map will recenter as a side effect if projection is reset
    public function setProjection($proj)
    {
        $this->projection = $proj;
        $this->unitsPerMeter = null;

        // arbitrarily set initial bounding box to the center (1/10)^2 of the containing map
        $bbox = $this->wmsParser->getBBoxForProjection($this->projection);
        $xrange = $bbox['xmax'] - $bbox['xmin'];
        $yrange = $bbox['ymax'] - $bbox['ymin'];
        $bbox['xmin'] += 0.4 * $xrange;
        $bbox['xmax'] -= 0.4 * $xrange;
        $bbox['ymin'] += 0.4 * $yrange;
        $bbox['ymax'] -= 0.4 * $yrange;
        $this->initialBBox = $bbox;
        $this->bbox = $bbox;
        $this->zoomLevel = $this->zoomLevelForScale($this->getCurrentScale());
        $this->center = array(
            'lat' => ($this->bbox['ymin'] + $this->bbox['ymax']) / 2,
            'lon' => ($this->bbox['xmin'] + $this->bbox['xmax']) / 2,
            );
    }
    
    public function setCenter($center)
    {
        if (is_array($center)
            && isset($center['lat'])
            && isset($center['lon']))
        {
            $xrange = $this->getHorizontalRange();
            $yrange = $this->getVerticalRange();
            $this->center = $center;
            $this->bbox['xmin'] = $center['lon'] - $xrange / 2;
            $this->bbox['xmax'] = $center['lon'] + $xrange / 2;
            $this->bbox['ymin'] = $center['lat'] - $xrange / 2;
            $this->bbox['ymax'] = $center['lat'] + $xrange / 2;
        }
    }
    
    public function setZoomLevel($zoomLevel)
    {
        $dZoom = $zoomLevel - $this->zoomLevel;
        $this->zoomLevel = $zoomLevel;
        // dZoom > 0 means decrease range
        $newXRange = $this->getHorizontalRange() / pow(2, $dZoom);
        $newYRange = $this->getVerticalRange() / pow(2, $dZoom);
        $this->bbox['xmin'] = $this->center['lon'] - $newXRange / 2;
        $this->bbox['xmax'] = $this->center['lon'] + $newXRange / 2;
        $this->bbox['ymin'] = $this->center['lat'] - $newYRange / 2;
        $this->bbox['ymax'] = $this->center['lat'] + $newYRange / 2;
    }

    public function setImageWidth($width) {
        $ratio = $width / $this->imageWidth;
        $range = $this->getHorizontalRange();
        $this->imageWidth = $width;
        $newRange = $range * $ratio;
        $this->bbox['xmin'] = $this->center['lon'] - $newRange / 2;
        $this->bbox['xmax'] = $this->center['lon'] + $newRange / 2;
    }

    public function setImageHeight($height) {
        $ratio = $height / $this->imageHeight;
        $range = $this->getVerticalRange();
        $this->imageHeight = $height;
        $newRange = $range * $ratio;
        $this->bbox['ymin'] = $this->center['lat'] - $newRange / 2;
        $this->bbox['ymax'] = $this->center['lat'] + $newRange / 2;
    }
    
    public function getImageURL()
    {
        $bboxStr = $this->bbox['xmin'].','.$this->bbox['ymin'].','
                  .$this->bbox['xmax'].','.$this->bbox['ymax'];

        $layers = array();
        $styles = array();
        
        // TODO figure out if maxScale and minScale in the XMl feed
        // are based on meters or the feed's inherent units
        $currentScale = $this->getCurrentScale()*$this->unitsPerMeter;
        foreach ($this->enabledLayers as $layerName) {
            // exclude if out of bounds
            $aLayer = $this->wmsParser->getLayer($layerName);
            $bbox = $aLayer->getBBoxForProjection($this->projection);
            if ($bbox['xmin'] > $this->center['lon']
                || $bbox['xmax'] < $this->center['lon']
                || $bbox['ymin'] > $this->center['lat']
                || $bbox['ymax'] < $this->center['lat'])
                continue;

            if (!$aLayer->canDrawAtScale($currentScale) )
                continue;
            $layers[] = $aLayer->getLayerName();
            $styles[] = $aLayer->getDefaultStyle()->getStyleName();
        }

        $params = array(
            'request' => 'GetMap',
            'version' => '1.3.0',  // TODO allow config
            'format'  => 'png',    // TODO allow config
            'bbox' => $bboxStr,
            'width' => $this->imageWidth,
            'height' => $this->imageHeight,
            'crs' => $this->projection,
            'layers' => implode(',', $layers),
            'styles' => implode(',', $styles),
            );
            
        if (!isset($params['crs'])) $params['crs'] = $this->defaultProjection;

        return $this->baseURL.'?'.http_build_query($params);
    }
    
    public function getAvailableLayers() {
        if ($this->availableLayers === null) {
            $this->availableLayers = $this->wmsParser->getLayerNames();
        }
        return $this->availableLayers;
    }
}

