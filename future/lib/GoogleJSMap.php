<?php

class GoogleJSMap extends JavascriptMapImageController {

// http://code.google.com/apis/maps/documentation/javascript/overlays.html

    private $locatesUser = false;

    protected $canAddAnnotations = true;
    protected $canAddPaths = true;
    protected $canAddLayers = true;

    protected $markers = array();
    protected $paths = array();

    public function setLocatesUser($locatesUser) {
        $this->locatesUser = ($locatesUser == true);
    }

    ////////////// overlays ///////////////

    public function addAnnotation($latitude, $longitude, $style=null)
    {
        $marker = array(
            'lat' => $latitude,
            'lon' => $longitude,
            );

        if (isset($style['title'])) {
            $marker['title'] = $style['title'];
        }

        $this->markers[] = $marker;
    }

    public function addPath($points, $style=null)
    {
        $path = array('coordinates' => $points);
        
        $pathStyle = array();
        if (isset($style[MapImageController::STYLE_LINE_COLOR])) {
            $color = $style[MapImageController::STYLE_LINE_COLOR];
            $pathStyle['strokeColor'] = '"#'.substr($color, 0, 6).'"';
            if (strlen($color) == 8) {
                $alphaHex = substr($color, 6);
                $alpha = hexdec($alphaHex) / 256;
                $pathStyle['strokeOpacity'] = round($alpha, 2);
            }
        }
        if (isset($style[MapImageController::STYLE_LINE_WEIGHT])) {
            $pathStyle['strokeWeight'] = $style[MapImageController::STYLE_LINE_WEIGHT];
        }
        $path['style'] = $pathStyle;
        
        $this->paths[] = $path;
    }

    private function getPathJS() {
        $js = "var coordinates;\nvar path;";
        foreach ($this->paths as $path) {
            $coords = array();
            foreach ($path['coordinates'] as $coord) {
                $coords[] .= 'new google.maps.LatLng('.$coord[0].','.$coord[1].')';
            }
            $coordString = implode(',', $coords);

            $properties = array();
            $properties[] = 'path: coordinates';
            foreach ($path['style'] as $attrib => $value) {
                $properties[] = "$attrib: $value";
            }
            $propString = implode(',', $properties);

            $js .= <<<JS

coordinates = [{$coordString}];
path = new google.maps.Polyline({{$propString}});
path.setMap(map);

JS;

        }
        return $js;
    }

    ////////////// output ///////////////

    // url of script to include in <script src="...
    public function getIncludeScript() {
        return 'http://maps.google.com/maps/api/js?sensor='
             . ($this->locatesUser ? 'true' : 'false');
    }

    public function getHeaderScript() {

        $script = <<<JS

var map;

function loadMap() {
    var mapImage = document.getElementById("{$this->mapElement}");
    mapImage.style.display = "inline-block";
    mapImage.style.width = "{$this->imageWidth}px";
    mapImage.style.height = "{$this->imageHeight}px";


    var latlng = new google.maps.LatLng({$this->center['lat']}, {$this->center['lon']});
    var options = {
        zoom: {$this->zoomLevel},
        center: latlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };

    map = new google.maps.Map(mapImage, options);
}

JS;

        return $script;
    }

    public function getFooterScript() {

        $script = <<<JS

hideMapTabChildren();
loadMap();

JS;

        if ($this->paths) {
            $script .= $this->getPathJS();
        }

        foreach ($this->markers as $index => $marker) {
            $title = 'marker';
            if (isset($marker['title'])) {
                $title = $marker['title'];
            }

            $script .= <<<JS

var marker{$index} = new google.maps.Marker({
    position: new google.maps.LatLng({$marker['lat']},{$marker['lon']}),
    map: map,
    title: "{$title}"
});

JS;
        }

        return $script;
    }

}

