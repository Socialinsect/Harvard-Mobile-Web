<?php

// http://resources.esri.com/help/9.3/arcgisserver/apis/rest/index.html
class ArcGISStaticMap extends WMSStaticMap {
    
    private $parser;
    private $layerFilters = array();
    
    private $transparent = false;
    public function setTransparent($tranparent) {
        $this->transparent = ($transparent == true);
    }

    public function __construct($baseURL, $parser=null) {
        $this->baseURL = $baseURL;
        
        if ($parser === null) {
            // if our controlling class is not ArcGISDataController
            // there will not be a parser yet and we have to redo
            // everything that ArcGISDataController would do for us
            $this->diskCache = new DiskCache($GLOBALS['siteConfig']->getVar('ARCGIS_CACHE'), 86400 * 7, true);
            $this->diskCache->preserveFormat();
            $filename = md5($this->baseURL);
            $metafile = $filename.'-meta.txt';
        
            if (!$this->diskCache->isFresh($filename)) {
                $params = array('f' => 'json');
                $query = $this->baseURL.'?'.http_build_query($params);
                file_put_contents($this->diskCache->getFullPath($metafile), $query);
                $contents = file_get_contents($query);
                $this->diskCache->write($contents, $filename);
            } else {
                $contents = $this->diskCache->read($filename);
            }
            $this->parser = new ArcGISParser();
            $this->parser->parseData($contents);
        }
        
        // TODO support this in ArcGISParser
        //$this->supportedImageFormats = $parser->getSupportedImageFormats();
        $this->setProjection($this->parser->getProjection());
        $this->enableAllLayers();
    }
    
    // currently the map will recenter as a side effect if projection is reset
    public function setProjection($proj)
    {
        $this->projection = $proj;
        $this->unitsPerMeter = null;

        if ($proj == $this->parser->getProjection()) {
            $bbox = $this->parser->getInitialExtent();
        } else {
            // if they choose a non-geographic projection
            // we can't do anything self-contained
            $bbox = array('xmin' => 0, 'ymin' => 0, 'xmax' => 1, 'ymax' => 1);
        }

        $xrange = $bbox['xmax'] - $bbox['xmin'];
        $yrange = $bbox['ymax'] - $bbox['ymin'];
        $bbox['xmin'] += 0.4 * $xrange;
        $bbox['xmax'] -= 0.4 * $xrange;
        $bbox['ymin'] += 0.4 * $yrange;
        $bbox['ymax'] -= 0.4 * $yrange;
        $this->bbox = $bbox;
        $this->zoomLevel = $this->zoomLevelForScale($this->getCurrentScale());
        $this->center = array(
            'lat' => ($this->bbox['ymin'] + $this->bbox['ymax']) / 2,
            'lon' => ($this->bbox['xmin'] + $this->bbox['xmax']) / 2,
            );
    }

    ////////////// overlays ///////////////
    
    public function getAvailableLayers() {
        if ($this->availableLayers === null) {
            $this->availableLayers = $this->parser->getSubLayerIds();
        }
        return $this->availableLayers;
    }
    
    // $filter is something like "POP>1000000", "ID='51'"
    // where you know the name of the field and its range of values
    public function setLayerFilter($layer, $filter) {
        if ($this->isAvailableLayer($layer)) {
            $this->layerFilters[$layer] = $filter;
        }
    }

    /////////// query builders ////////////

    public function getImageURL() {
        $bboxStr = $this->bbox['xmin'].','.$this->bbox['ymin'].','
                  .$this->bbox['xmax'].','.$this->bbox['ymax'];
        
        $params = array(
            'f' => 'image',
            'bbox' => $bboxStr,
            'size' => $this->imageWidth.','.$this->imageHeight,
            'dpi' => null, // default 96
            'imageSR' => $this->projection,
            'bboxSR' => $this->projection,
            'format' => $this->imageFormat,
            'layerDefs' => $this->getLayerDefs(),
            'layers' => 'show:'.implode(',', $this->enabledLayers),
            'transparent' => $this->transparent ? 'true' : 'false',
            );

        $query = http_build_query($params);

        return $this->baseURL . '/export?' . $query;
    }
    
    private function getLayerDefs() {
        if (!$this->layerFilters)
            return null;

        $layerDefs = array();
        foreach ($this->layerFilters as $layer => $filter) {
            $layerDefs[] = $layer.':'.$filter;
        }
        return implode(';', $layerDefs);
    }
}






