<?php

// http://help.arcgis.com/EN/webapi/javascript/arcgis/help/jshelp_start.htm
// http://resources.esri.com/help/9.3/arcgisserver/apis/javascript/arcgis/help/jsapi_start.htm

// sandbox
// TODO move this to config
define("ESRI_PROJECTION_SERVER", 'http://tasks.arcgisonline.com/ArcGIS/rest/services/Geometry/GeometryServer/project');

require_once 'MapProjector.php';

class ArcGISJSMap extends JavascriptMapImageController {
    
    const DEFAULT_PROJECTION = 4326;
    
    // capabilities
    protected $canAddAnnotations = true;
    protected $canAddPaths = true;
    protected $canAddLayers = true;
    protected $supportsProjections = true;
    
    protected $markers = array();
    protected $paths = array();
    
    private $apiVersion = '2.1';
    private $themeName = 'claro'; // claro, tundra, soria, nihilo
    
    // map image projection data
    private $projspec = NULL;
    private $mapProjector;
    
    public function __construct($baseURL)
    {
        $this->baseURL = $baseURL;
        $arcgisParser = ArcGISDataController::parserFactory($this->baseURL);
        $wkid = $arcgisParser->getProjection();

        /*        
        // use the same filename generating algorithm as DataController
        // since there is a chance someone else's ArcGISDataController
        // has cached the same file.  also see ArcGISStaticMap which does the same.
        // TODO make the filename algorithm more accessible if we use it this way
        $diskCache = new DiskCache($GLOBALS['siteConfig']->getVar('ARCGIS_CACHE'), 86400 * 7, true);
        $diskCache->preserveFormat();
        $filename = md5($this->baseURL);
        $metafile = $filename.'-meta.txt';
        if (!$diskCache->isFresh($filename)) {
            $params = array('f' => 'json');
            $query = $this->baseURL.'?'.http_build_query($params);
            file_put_contents($diskCache->getFullPath($metafile), $query);
            $contents = file_get_contents($query);
            $diskCache->write($contents, $filename);
        } else {
            $contents = $diskCache->read($filename);
        }
        
        $json = json_decode($contents, true);
        if (isset($json['spatialReference']) && isset($json['spatialReference']['wkid'])) {
            $wkid = $json['spatialReference']['wkid'];
            $this->mapProjector = new MapProjector(ESRI_PROJECTION_SERVER);
            //$this->mapProjector = new MapProjector();
            $this->mapProjector->setDstProj($wkid);
        }
        */
        $this->mapProjector = new MapProjector(ESRI_PROJECTION_SERVER);
        $this->mapProjector->setDstProj($wkid);
    }

    ////////////// overlays ///////////////
    
    // TODO make the following two functions more concise

    public function addAnnotation($latitude, $longitude, $style=null)
    {
        $marker = array('lon' => $longitude, 'lat' => $latitude);
        
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

    ////////////// output ///////////////

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
                'spatialReference' => array('wkid' => $this->mapProjection)
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
                $symbolArgs = '"'.$styles['icon'].'",20,20'; // TODO allow size to be set
            
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
                if ($this->mapProjector) {
                    $point = $this->mapProjector->projectPoint($point);
                }
                else {
                    $point = array('x' => $point['lat'], 'y' => $point['lon']);
                }
            
                $js .= <<<JS

    point = new esri.geometry.Point({$point['x']}, {$point['y']}, spatialRef);
    pointSymbol = new esri.symbol.{$symbolType}({$symbolArgs});
    map.graphics.add(new esri.Graphic(point, pointSymbol));

JS;
            }
        }
        
        return $js;
    }
    
    private function getCenterJS() {
        if ($this->mapProjector) {
            $xy = $this->mapProjector->projectPoint($this->center);
        }
        else {
            $xy = array('x' => $this->center['lat'], 'y' => $this->center['lon']);
        }
    
        $js = 'new esri.geometry.Point('.$xy['x'].', '.$xy['y'].', spatialRef)';
    
        return $js;
    }
    
    private function getSpatialRefJS() {
        $wkid = $this->mapProjector->getDstProj();
        return "var spatialRef = new esri.SpatialReference({ wkid: $wkid });";
    }

    // url of script to include in <script src="...
    function getIncludeScript() {
        return 'http://serverapi.arcgisonline.com/jsapi/arcgis/?v='.$this->apiVersion.'compact';
    }
    
    function getIncludeStyles() {
        return 'http://serverapi.arcgisonline.com/jsapi/arcgis/'
               .$this->apiVersion.'/js/dojo/dijit/themes/'
               .$this->themeName.'/'.$this->themeName.'.css';
    }

    function getHeaderScript() {
        return '';
    }

    function getFooterScript() {

        // put dojo stuff in the footer since the header script
        // gets loaded before the included script

        $script = <<<JS

dojo.require("esri.map");
dojo.addOnLoad(loadMap);

var map;

function loadMap() {
    var mapImage = document.getElementById("mapimage");
    mapImage.style.display = "block";
    mapImage.style.width = "{$this->imageWidth}px";
    mapImage.style.height = "{$this->imageHeight}px";
    
    map = new esri.Map("{$this->mapElement}");
    var basemapURL = "{$this->baseURL}";
    var basemap = new esri.layers.ArcGISTiledMapServiceLayer(basemapURL);
    map.addLayer(basemap);

    dojo.connect(map, "onLoad", plotFeatures);
}

function plotFeatures() {

    {$this->getSpatialRefJS()}

    {$this->getPathJS()}
    
    {$this->getMarkerJS()}

    map.centerAndZoom({$this->getCenterJS()}, {$this->zoomLevel})
}

JS;

        return $script;
    }

}

