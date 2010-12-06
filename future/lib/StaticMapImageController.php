<?php

abstract class StaticMapImageController extends MapImageController
{
    protected $initialBBox;
    protected $bbox;

    protected $baseURL;

    protected $imageFormat = 'png';
    protected $supportedImageFormats = array('png', 'jpg');

    // final function that generates url for the img src argument
    abstract public function getImageURL();

    public function isStatic() {
        return true;
    }

    public function getHorizontalRange()
    {
        return 0.01;
    }

    public function getVerticalRange()
    {
        return 0.01;
    }

    // n, s, e, w, ne, nw, se, sw
    public function getCenterForPanning($direction) {
        $vertical = null;
        $horizontal = null;

        if (preg_match('/[ns]/', $direction, $matches)) {
            $vertical = $matches[0];
        }
        if (preg_match('/[ew]/', $direction, $matches)) {
            $horizontal = $matches[0];
        }

        $center = $this->center;

        if ($horizontal == 'e') {
            $center['lon'] += $this->getHorizontalRange() / 2;
        } else if ($horizontal == 'w') {
            $center['lon'] -= $this->getHorizontalRange() / 2;
        }

        if ($vertical == 'n') {
            $center['lat'] += $this->getVerticalRange() / 2;
        } else if ($vertical == 's') {
            $center['lat'] -= $this->getVerticalRange() / 2;
        }

        return $center;
    }

    public function getLevelForZooming($direction) {
        $zoomLevel = $this->zoomLevel;
        if ($direction == 'in') {
            if ($zoomLevel < $this->maxZoomLevel)
                $zoomLevel += 1;
        } else if ($direction == 'out') {
            if ($zoomLevel > $this->minZoomLevel)
                $zoomLevel -= 1;
        }
        return $zoomLevel;
    }

    // setters
    public function setImageFormat($format) {
        if (in_array($format, $this->supportedImageFormats)) {
            $this->imageFormat = $format;
        }
    }

    public function setBoundingBox($bbox)
    {
        $this->bbox = $bbox;
    }
}


