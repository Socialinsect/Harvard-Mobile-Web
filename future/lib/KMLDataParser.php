<?php

// http://schemas.opengis.net/kml/2.2.0/ogckml22.xsd
// http://portal.opengeospatial.org/files/?artifact_id=27810

require_once(LIB_DIR . '/XMLDataParser.php');

interface MapGeometry
{
    public function getCenterCoordinate();
    public function getType();
}

class KMLDocument extends XMLElement
{
    protected $name = 'Document';
    protected $description;
    protected $title; // use this for "name" element
    
    public function __construct($name, $attribs)
    {
        $this->setAttribs($attribs);
    }

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
        {
            case 'NAME':
                $this->title = $value;
                break;
            case 'DESCRIPTION':
                $this->description = $value;
                break;
            default:
                parent::addElement($element);
                break;
        }
    }

    public function getTitle() {
        return $this->title;
    }

    public function getPlacemarks() {
        return $this->placemarks;
    }
}

class KMLStyle extends XMLElement
{
    protected $isSimpleStyle = true;

    protected $iconStyle; // color, colorMode, scale, heading, hotSpot, icon>href
    protected $balloonStyle; // bgColor, textColor, text, displayMode
    protected $lineStyle; // color, colorMode, width
    protected $labelStyle;
    protected $listStyle;

    // pointers to simple style objects
    protected $normalStyle;
    protected $highlightStyle;
    protected $styleContainer; // pointer to whoever owns the lookup table of simple styles

    public function getPointStyle() {
        if ($this->isSimpleStyle) {
            $style = array_merge($this->balloonStyle, $this->iconStyle);
        } else {
            $styleRef = $this->styleContainer->getStyle($this->normalStyle);
            $style = $styleRef->getPointStyle();
        }
        return $style;
    }

    public function getLineStyle() {
        if ($this->isSimpleStyle) {
            $style = $this->lineStyle;
        } else {
            $styleRef = $this->styleContainer->getStyle($this->normalStyle);
            $style = $styleRef->getLineStyle();
        }
        return $style;
    }

    public function isSimpleStyle() {
        return $this->isSimpleStyle;
    }

    public function setStyleContainer($container) {
        $this->styleContainer = $container;
    }

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
        {
            case 'ICONSTYLE':
                $this->iconStyle = array('icon' => $element->getProperty('ICON'));
                break;
            case 'BALLOONSTYLE':
                $this->balloonStyle = array(
                    'color' => $element->getProperty('BGCOLOR'),
                    'textColor' => $element->getProperty('TEXTCOLOR'),
                    );
                break;
            case 'LINESTYLE':
                $this->lineStyle = array(
                    'color' => $element->getProperty('COLOR'),
                    'weight' => $element->getProperty('WEIGHT'),
                    );
                break;
            case 'LABELSTYLE':
                $this->labelStyle = array();
                break;
            case 'LISTSTYLE':
                $this->listStyle = array();
                break;
            case 'PAIR':
                $state = $element->getProperty('KEY');
                if ($state == 'normal') {
                    $this->normalStyle = substr($element->getProperty('STYLEURL'), 1);
                } else if ($state == 'highlighted') {
                    $this->highlightStyle = substr($element->getProperty('STYLEURL'), 1);
                }
                break;
            default:
                parent::addElement($element);
                break;
        }
        
    }
    
    public function __construct($name, $attribs)
    {
        $this->isSimpleStyle = ($name === 'STYLE');
        $this->setAttribs($attribs);
    }
}

class KMLPlacemark extends XMLElement implements MapFeature
{
    protected $name = 'Placemark';
    protected $description;
    protected $title; // use this for "name" element
    protected $style;
    protected $geometry;
    
    public function __construct($name, $attribs)
    {
        $this->setAttribs($attribs);
    }

    public function getTitle() {
        return $this->title;
    }

    public function getGeometry() {
        return $this->geometry;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setStyle(KMLStyle $style) {
        $this->style = $style;
    }

    public function getStyleAttribs() {
        switch ($this->geometry->getType()) {
            case 'Point':
                return $this->style->getPointStyle();
            case 'Polyline':
                return $this->style->getLineStyle();
            default:
                return null;
        }
    }

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
        {
            case 'NAME':
                $this->title = $value;
                break;
            case 'DESCRIPTION':
                $this->description = $value;
                break;
            case 'POINT':
            case 'LINESTRING':
                $this->geometry = $element;
                break;
            case 'LINEARRING':
            case 'POLYGON':
            case 'MULTIGEOMETRY':
            case 'MODEL':
            case 'GX:TRACK':
            case 'GX:MULTITRACK':
                throw new Exception("Geometry type $name not implemented yet");
                break;
            default:
                parent::addElement($element);
                break;
        }
    }
}

class KMLPoint extends XMLElement implements MapGeometry
{
    private $coordinate;

    public function getCenterCoordinate()
    {
        return $this->coordinate;
    }

    public function getType()
    {
        return 'Point';
    }

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
       {
            // more tags see
            // http://code.google.com/apis/kml/documentation/kmlreference.html#point
            case 'COORDINATES':
                $xyz = explode(',', $value);
                $this->coordinate = array(
                    'lon' => $xyz[0],
                    'lat' => $xyz[1],
                    'altitude' => isset($xyz[2]) ? $xyz[2] : null,
                    );
                break;
            default:
                parent::addElement($element);
                break;
        }
    }
}

class KMLLineString extends XMLElement implements MapGeometry
{
    private $coordinates = array();

    public function getCenterCoordinate()
    {
        $lat = 0;
        $lon = 0;
        $n = 0;
        foreach ($this->coordinates as $coordinate) {
            $lat += $coordinate['lat'];
            $lon += $coordinate['lon'];
            $n += 1;
        }
        return array(
            'lat' => $lat / $n,
            'lon' => $lon / $n,
            );
    }

    public function getType()
    {
        return 'Polyline';
    }

    public function getPoints() {
        $points = array();
        foreach ($this->coordinates as $coordinate) {
            $points[] = array($coordinate['lat'], $coordinate['lon']);
        }
        return $points;
    }

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
        {
            // more tags see
            // http://code.google.com/apis/kml/documentation/kmlreference.html#linestring
            case 'COORDINATES':
                foreach (explode("\n", $value) as $line) {
                    $xyz = explode(',', trim($line));
                    if (count($xyz) >= 2) {
                        $this->coordinates[] = array(
                            'lon' => $xyz[0],
                            'lat' => $xyz[1],
                            'altitude' => isset($xyz[2]) ? $xyz[2] : null,
                            );
                    }
                }
                break;
            default:
                parent::addElement($element);
                break;
        }
    }
}

class KMLDataParser extends XMLDataParser
{
    protected $root;
    protected $elementStack = array();
    protected $data='';

    protected $styles = array();

    protected $document;

    // whitelists
    protected static $startElements=array('DOCUMENT','STYLE','STYLEMAP','PLACEMARK','POINT','LINESTRING');
    protected static $endElements=array('DOCUMENT','STYLE','STYLEMAP','PLACEMARK','STYLEURL');

    /*    
    public function init($args)
    {
    }
    */

    public function getTitle() {
        return $this->document->getTitle();
    }

    public function getStyle($id) {
        if (substr($id, 0, 1) == '#') {
            $id = substr($id, 1);
        }
        if (isset($this->styles[$id])) {
            return $this->styles[$id];
        }
        return null;
    }

    protected function shouldHandleStartElement($name)
    {
        return in_array($name, self::$startElements);
    }

    protected function handleStartElement($name, $attribs)
    {
        switch ($name)
        {
            case 'DOCUMENT':
                $this->elementStack[] = new KMLDocument($name, $attribs);
                break;
            case 'STYLE':
                $this->elementStack[] = new KMLStyle($name, $attribs);
                break;
            case 'STYLEMAP':
                $style = new KMLStyle($name, $attribs);
                $style->setStyleContainer($this);
                $this->elementStack[] = $style;
                break;
            case 'PLACEMARK':
                $this->elementStack[] = new KMLPlacemark($name, $attribs);
                break;
            case 'POINT':
                $this->elementStack[] = new KMLPoint($name, $attribs);
                break;
            case 'LINESTRING':
                $this->elementStack[] = new KMLLineString($name, $attribs);
                break;
        }
    }

    protected function shouldStripTags($element)
    {
        return false;
    }

    protected function shouldHandleEndElement($name)
    {
        return in_array($name, self::$endElements);
    }

    protected function handleEndElement($name, $element, $parent)
    {
        switch ($name)
        {
            case 'DOCUMENT':
                $this->document = $element;
                break;
            case 'STYLE':
            case 'STYLEMAP':
                $this->styles[$element->getAttrib('ID')] = $element;
                break;
            case 'PLACEMARK':
                $this->items[] = $element;
                break;
            case 'STYLEURL':
                $value = $element->value();
                if ($parent->name() == 'Placemark') {
                    $parent->setStyle($this->getStyle($value));
                } else {
                    $parent->addElement($element);
                }
                break;
        }
    }
}



