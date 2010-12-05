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

class MapLayerDataController extends DataController
{
    protected $parser = null;
    protected $parserClass = null;
    protected $DEFAULT_PARSER_CLASS = 'KMLDataParser';
    protected $DEFAULT_MAP_CLASS = 'GoogleStaticMap';
    protected $items = null;
    protected $mapBaseUrl = null;
    protected $searchable = false;
    
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

    public function setParser($parserClass) {
        $this->parser = new $parserClass();
        $this->parserClass = $parserClass;
    }

    public function getStaticMapController() {
        if ($this->staticMapClass == 'WMSStaticMap') {
            $controller = new WMSStaticMap($this->parser);
        } else {
            $controller = new $this->staticMapClass();
        }
        return $controller;
    }

    public function supportsDynamicMap() {
        //return false;
        return ($this->dynamicMapClass !== null);
    }

    public function getDynamicMapController() {
        $controller = new $this->dynamicMapClass();
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

        if (isset($args['JS_MAP_CLASS']))
            $this->dynamicMapClass = $args['JS_MAP_CLASS'];
        
        if (isset($args['MAP_BASE_URL'])) {
            $this->mapBaseUrl = $args['MAP_BASE_URL'];
        }
        
        $this->searchable = ($args['SEARCHABLE'] == 1);
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
        $controller->init($args);
        $controller->setParser($parserClass);
        return $controller;
    }

}

