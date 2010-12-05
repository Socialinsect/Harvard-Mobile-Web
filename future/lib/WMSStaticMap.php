<?php

class WMSStaticMap extends StaticMapImageController {

    const METERS_PER_PIXEL = 0.00028; // WMS standard definition
    // how much context to provide around a building relative to its size
    const OBJECT_PADDING = 1.0;

    protected $canAddAnnotations = false;
    protected $canaddPaths = false;
    protected $canAddLayers = true;

    protected $availableLayers = null;
    private $wmsParser;

    public function __construct($args) {
        
    }
    
    public function getImageURL() {
    }
    
    public function getAvailableLayers() {
        if ($this->availableLayers === null) {
            if (!$this->wmsParser->getLayers()) {
                $this->wmsParser
            }
            foreach ($this->wmsParser->
        }
        return $this->availableLayers;
    }

    public function __construct($parser) {
        $this->wmsParser = $parser;
        $this->enableAllLayers();
    }
}

