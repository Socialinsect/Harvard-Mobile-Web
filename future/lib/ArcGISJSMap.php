<?php

// http://help.arcgis.com/EN/webapi/javascript/arcgis/help/jshelp_start.htm
// http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi_start.htm

class ArcGISJSMap extends JavascriptMapImageController {
    
    // capabilities
    protected $canAddAnnotations = true;
    protected $canAddPaths = true;
    protected $canAddLayers = true;
    protected $supportsProjections = true;
    
    protected $projection = 4326;
    protected $markers = array();
    protected $paths = array();
    
    private $apiVersion = '2.1';
    private $themeName = 'claro'; // claro, tundra, soria, nihilo
    
    public function __construct($baseURL)
    {
        $this->baseURL = $baseURL;
    }
    
    public function setProjection($proj)
    {
        $this->projection = $proj;
    }

    ////////////// overlays ///////////////
    
    // TODO make the following two functions more concise

    public function addAnnotation($latitude, $longitude, $style=null)
    {
        $marker = array('x' => $longitude, 'y' => $latitude);
        
        $filteredStyles = array();
        if ($style !== null) {
            // http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi/simplemarkersymbol.htm
            // either all four of (color, size, outline, style) are set or zero are
            if (isset($style[MapImageController::STYLE_POINT_COLOR])) {
                $filteredStyles[] = 'color='.$style[MapImageController::STYLE_POINT_COLOR];
            } else {
                $filteredStyles[] = 'color=#FF0000';
            }
            
            if (isset($style[MapImageController::STYLE_POINT_SIZE])) {
                $filteredStyles[] = 'color='.$style[MapImageController::STYLE_POINT_SIZE];
            } else {
                $filteredStyles[] = 'size=12';
            }
            
            if (isset($style['style'])) {
                // TODO there isn't yet a good way to get valid values for this from outside
                $filteredStyles[] = 'style='.$style['style'];
            } else {
                $filteredStyles[] = 'style=STYLE_CIRCLE';
            }

            // if they use an image
            // http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi/picturemarkersymbol.htm
            if (isset($style[MapImageController::STYLE_POINT_ICON])) {
            	$filteredStyles[] = 'icon='.$style[MapImageController::STYLE_POINT_ICON];
            }
        }
        $styleString = implode('|', $filteredStyles);
        if (!isset($this->markers[$styleString])) {
        	$this->markers[$styleString] = array();
        }
        
        $this->markers[$styleString][] = $marker;
    }

    public function addPath($points, $style=null)
    {
        $filteredStyles = array();
        if ($style !== null) {
            // either three or zero parameters are all set
            if (isset($style[MapImageController::STYLE_LINE_CONSISTENCY])) {
                // TODO there isn't yet a good way to get valid values for this from outside
                $filteredStyles[] = 'style='.$style[MapImageController::STYLE_LINE_CONSISTENCY];
            } else {
                $filteredStyles[] = 'style=STYLE_SOLID';
            }

            if (isset($style[MapImageController::STYLE_LINE_COLOR])) {
                $filteredStyles[] = 'color='.$style[MapImageController::STYLE_LINE_COLOR];
            } else {
                $filteredStyles[] = 'color=#FF0000'; // these needs to be converted to dojo
            }
            
            if (isset($style[MapImageController::STYLE_LINE_WEIGHT])) {
                $filteredStyles[] = 'width='.$style[MapImageController::STYLE_LINE_WEIGHT];
            } else {
                $filteredStyles[] = 'width=4';
            }
        }
        $styleString = implode('|', $filteredStyles);
        
        if (!isset($this->paths[$styleString])) {
        	$this->paths[$styleString] = array();
        }
        $this->paths[$styleString][] = $points;
    }

    private function getPathJS()
    {
        $js = <<<JS

var lineSymbol;
var polyline;

JS;
    
        foreach ($this->paths as $styleString => $paths) {
            $styleParams = explode('|', $styleString);
            $styles = array();
            foreach ($styleParams as $styleParam) {
                $styleParts = explode('=', $styleParam);
                $styles[$styleParts[0]] = $styleParts[1];
            }
            if (count($styles)) {
                $symbolArgs = $styles['style'].','
                             .'new dojo.Color("'.$styles['color'].'"),'
                             .$styles['weight'];
            } else {
                $symbolArgs = '';
            }
            
            // http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi/polyline.htm
            $jsonObj = array(
                'points' => $paths,
                'spatialReference' => array('wkid' => $this->projection)
                );
            
            $json = json-decode($jsonObj);

            $js .= <<<JS

lineSymbol = new esri.symbol.SimpleLineSymbol({$symbolArgs});
polyline = new esri.geometry.Polyline({$json});
map.graphics.add(new esri.Graphic(polyline, lineSymbol));

JS;

        }

        return $js;
    }
    
    private function getMarkerJS()
    {
        $js = <<<JS

var pointSymbol;
var point;

JS;
    
        foreach ($this->markers as $styleString => $points) {
            $styleParams = explode('|', $styleString);
            $styles = array();
            foreach ($styleParams as $styleParam) {
                $styleParts = explode('=', $styleParam);
                $styles[$styleParts[0]] = $styleParts[1];
            }
            if (isset($styles['icon'])) {
                $symbolType = 'PictureMarkerSymbol';
                $symbolArgs = '"'.$styles['icon'].'",null,null';
            
            } else {
                $symbolType = 'SimpleMarkerSymbol';
                if (count($styles)) {
                    $symbolArgs = $styles['style'].','
                                 .$styles['size'].','
                                 .'new dojo.Color("'.$styles['color'].'"),'
                                 .'new esri.symbol.SimpleLineSymbol()';
                } else {
                    $symbolArgs = '';
                }
            }

            foreach ($points as $point) {
                $js .= <<<JS

point = new esri.geometry.Point({$point['x']}, {$point['y']}, new esri.SpatialReference({ wkid: {$this->projection} }));
pointSymbol = new esri.symbol.{$symbolType}({$symbolArgs});
map.graphics.add(new esri.Graphic(point, pointSymbol));

JS;
            }
        }
        
        return $js;
    }

    ////////////// output ///////////////

    // url of script to include in <script src="...
    function getIncludeScript() {
        return 'http://serverapi.arcgisonline.com/jsapi/arcgis/?v='.$this->apiVersion;
    }
    
    function getIncludeStyles() {
        return 'http://serverapi.arcgisonline.com/jsapi/arcgis/'
               .$this->apiVersion.'/js/dojo/dijit/themes/'
               .$this->themeName.'/'.$this->themeName.'.css';
    }

    function getHeaderScript() {

        $script = <<<JS

JS;

        return $script;
    }

    function getFooterScript() {

        // put dojo stuff in the footer since the header script
        // gets loaded before the included script

        $script = <<<JS

dojo.require("esri.map");
dojo.require("esri.geometry");

var map;

function loadMap() {
    var mapImage = document.getElementById("{$this->mapElement}");
    mapImage.style.display = "block";
    mapImage.style.width = "{$this->imageWidth}px";
    mapImage.style.height = "{$this->imageHeight}px";
    
    map = new esri.Map("{$this->mapElement}");
    var basemapURL = "{$this->baseURL}";
    var basemap = new esri.layers.ArcGISTiledMapServiceLayer(basemapURL);
    map.addLayer(basemap);
}

loadMap();

JS;

        if ($this->paths) {
            $script .= $this->getPathJS();
        }

        if ($this->markers) {
            $script .= $this->getMarkerJS();
        }

        return $script;
    }

}

