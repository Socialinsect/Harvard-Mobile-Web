<?php

class KMLDataController extends MapLayerDataController
{
    protected $parserClass = 'KMLDataParser';

    protected function cacheFileSuffix()
    {
        return '.kml';
    }
}

