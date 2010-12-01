<?php

// for KML, each KML file is a category
// for ArcGIS, each layer (or service instance?) is a category

interface MapFeature
{
    public function getTitle();
    public function getGeometry();
    public function getDescription();
}

class MapLayerDataController extends DataController
{
    protected $parser = null;
    // TODO make KMLDataParser and ArcGISServer subclasses of 
    // this so we don't need switch statements everywhere
    protected $parserClass = null;
    protected $DEFAULT_PARSER_CLASS = 'KMLDataParser';
    protected $items = null;

    protected function cacheFolder()
    {
        return CACHE_DIR . "/Maps";
    }

    protected function cacheLifespan()
    {
        // TODO add config so the following line works instead
        //return $GLOBALS['siteConfig']->getVar('MAP_CACHE_LIFESPAN');
        return 86400;
    }

    protected function cacheFileSuffix()
    {
        switch ($this->parserClass) {
            case 'ArcGISServer':
                return ''; // TODO determine what default suffix is
            case 'KMLDataParser':
            default:
                return '.kml';
        }
    }

    public function canSearch() {
        switch ($this->parserClass) {
            case 'KMLDataParser':
                return true;
            case 'ArcGISServer': // TODO implement
            default:
                return false;
        }
    }

    // not sure this is a good place for this function
    public function search($searchText) {
        if (!$this->items) {
            $data = $this->getData();
            $this->items = $this->parseData($data);
        }

        $results = array();
        if ($this->parserClass == 'KMLDataParser') {
            $results = $this->parser->searchByTitle($searchText);
        }

        return $results;
    }

    public function getFeatureList() {
        return $this->items();
    }

    public function getFeature($name) {
        return $this->getItem($name);
    }

    public function getItem($name)
    {
        if (!$name) {
            return null;
        }
        
        $items = $this->getFeatureList();
        foreach ($items as $item) {
            if ($item->getTitle() == $name) {
                return $item;
            }
        }

        return null;
    }

    public function getTitle() {
        if (!$this->items) {
            $data = $this->getData();
            $this->items = $this->parseData($data);
        }
        return $this->parser->getTitle();
    }

    public function items() {
        if (!$this->items) {
            $data = $this->getData();
            $this->items = $this->parseData($data);
        }
        return $this->items;
    }

    public function setParser($parserClass) {
        $this->parser = new $parserClass();
        $this->parserClass = $parserClass;
    }

    public function getMapControllerClass() {
        return $this->mapClass;
    }

    protected function init($args)
    {
        parent::init($args);

        $this->mapClass = isset($args['MAP_IMAGE_CLASS']) ? $args['MAP_IMAGE_CLASS'] : 'GoogleStaticMap';
    }


    public static function factory($args)
    {
        $parserClass = isset($args['PARSER_CLASS']) ? $args['PARSER_CLASS'] : $this->DEFAULT_PARSER_CLASS;
        switch ($parserClass) {
            case 'ArcGISServer':
                require_once realpath(LIB_DIR.'/ArcGISServer.php');
                break;
            case 'KMLDataParser':
                require_once realpath(LIB_DIR.'/KMLDataParser.php');
                break;
            default:
                require_once realpath(LIB_DIR.'/'.$DEFAULT_PARSER_CLASS.'.php');
                break;
        }
        $controller = new MapLayerDataController();
        $controller->init($args);
        $controller->setParser($parserClass);
        return $controller;
    }

}

