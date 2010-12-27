<?php

// for KML, each KML file is a category
// for ArcGIS, each layer (or service instance?) is a category

// TODO move these interfaces to a separate file if
// we create maps without using MapLayerDataController

interface MapFeature
{
    public function getTitle();
    public function getGeometry();
    public function getDescription();
    public function getStyleAttribs();
}

interface MapGeometry
{
    public function getCenterCoordinate();
    public function getType();
}

define('GEOGRAPHIC_PROJECTION', 4326);

class MapLayerDataController extends DataController
{
    protected $parser = null;
    protected $parserClass = null;
    protected $DEFAULT_PARSER_CLASS = 'KMLDataParser';
    protected $DEFAULT_MAP_CLASS = 'GoogleStaticMap';
    protected $items = null;
    protected $staticMapBaseURL = null;
    protected $dynamicMapBaseURL = null;
    protected $searchable = false;
    protected $defaultZoomLevel = 16;
    protected $returnsGeometry = true;
    
    // in theory all map images controllers should use the same
    // zoom level, but if certain image servers (e.g. Harvard ArcGIS)
    // have different definitions for zoom level, we need another
    // field to specify this
    protected $dynamicZoomLevel = null;
    
    const COMMON_WORDS = 'the of to and in is it you that he was for on are with as his they be at one have this from or had by hot but some what there we can out other were all your when up use word how said an each she which do their time if will way about many then them would write like so these her long make thing see him two has look more day could go come did my no most who over know than call first people may down been now find any new take get place made where after back only me our under';

    protected $staticMapClass;
    protected $dynamicMapClass = null;

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
        return null;
    }

    public function canSearch()
    {
        return $this->searchable;
    }

    // default search implementation loops through all relevant features
    public function search($searchText)
    {
        $results = array();
        
        if ($this->searchable) {
            $tokens = explode(' ', $searchText);
            $validTokens = array();
            foreach ($tokens as $token) {
                if (strlen($token) <= 1)
                    continue;
                $pattern = "/\b$token\b/i";
                if (!preg_match($pattern, self::COMMON_WORDS)) {
                    $validTokens[] = $pattern;
                }
            }
            if (count($validTokens)) {
                foreach ($this->items() as $item) {
                    $matched = true;
                    $title = $item->getTitle();
                    foreach ($validTokens as $token) {
                        if (!preg_match($token, $title)) {
                            $matched = false;
                        }
                    }
                    if ($matched) {
                        $results[] = $item;
                    }
                }
            }
        }
        return $results;
    }

    public function getFeatureList() {
        return $this->items();
    }

    public function getFeature($name) {
        return $this->getItem($name);
    }
    
    public function getProjection() {
        return GEOGRAPHIC_PROJECTION;
    }

    public function getItem($name)
    {
        $items = $this->getFeatureList();
        if (isset($items[$name]))
            return $items[$name];

        // TODO: get rid of anything that's getting items
        // by title, since titles are poor unique identifiers
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
    
    public function getDefaultZoomLevel() {
        return $this->defaultZoomLevel;
    }

    public function getStaticMapController() {
        $controller = MapImageController::factory($this->staticMapClass, $this->staticMapBaseURL);
        return $controller;
    }

    public function supportsDynamicMap() {
        //return false;
        return ($this->dynamicMapClass !== null);
    }

    public function getDynamicMapController() {
        $controller = MapImageController::factory($this->dynamicMapClass, $this->dynamicMapBaseURL);
        if ($this->dynamicMapClass == 'ArcGISJSMap' && $this->dynamicZoomLevel !== null) {
            $controller->setPermanentZoomLevel($this->dynamicZoomLevel);
        }
        return $controller;
    }

    protected function init($args)
    {
        parent::init($args);
        // static map support required; dynamic optional
        if (isset($args['STATIC_MAP_CLASS']))
            $this->staticMapClass = $args['STATIC_MAP_CLASS'];
        else
            $this->staticMapClass = $this->DEFAULT_MAP_CLASS;

        // other optional fields
        if (isset($args['JS_MAP_CLASS']))
            $this->dynamicMapClass = $args['JS_MAP_CLASS'];
        
        if (isset($args['STATIC_MAP_BASE_URL']))
            $this->staticMapBaseURL = $args['STATIC_MAP_BASE_URL'];
        
        if (isset($args['DYNAMIC_MAP_BASE_URL']))
            $this->dynamicMapBaseURL = $args['DYNAMIC_MAP_BASE_URL'];
        
        $this->searchable = isset($args['SEARCHABLE']) ? ($args['SEARCHABLE'] == 1) : false;

        if (isset($args['DEFAULT_ZOOM_LEVEL']))
            $this->defaultZoomLevel = $args['DEFAULT_ZOOM_LEVEL'];

        if (isset($args['DYNAMIC_ZOOM_LEVEL']))
            $this->dynamicZoomLevel = $args['DYNAMIC_ZOOM_LEVEL'];
        
        if ($this->parserClass == 'ArcGISParser' && isset($args['ARCGIS_LAYER_ID']))
            $this->parser->setDefaultLayer($args['ARCGIS_LAYER_ID']);

        if (isset($args['RETURNS_GEOMETRY']))
            $this->returnsGeometry = $args['RETURNS_GEOMETRY'];
    }


    public static function factory($args)
    {
        $parserClass = isset($args['PARSER_CLASS']) ? $args['PARSER_CLASS'] : $this->DEFAULT_PARSER_CLASS;
        switch ($parserClass) {
            case 'ArcGISParser':
                require_once realpath(LIB_DIR.'/ArcGISParser.php');
                $controller = new ArcGISDataController();
                break;
            case 'KMLDataParser':
                require_once realpath(LIB_DIR.'/KMLDataParser.php');
                $controller = new KMLDataController();
                break;
            default:
                require_once realpath(LIB_DIR.'/'.$parserClass.'.php');
                $controller = new MapLayerDataController();
                break;
        }
        $controller->parserClass = $parserClass;
        $controller->setParser(new $parserClass());
        $controller->init($args);
        return $controller;
    }

}

