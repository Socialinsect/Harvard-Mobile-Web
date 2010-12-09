<?php

class ArcGISDataController extends MapLayerDataController
{
    protected $parserClass = 'ArcGISParser';
    protected $filters = array('f' => 'json');

    protected function cacheFileSuffix()
    {
        return '.js'; // json
    }
    
    protected function cacheFolder()
    {
        return $GLOBALS['siteConfig']->getVar('ARCGIS_CACHE');
    }

    public function projectsFeatures() {
        return true;
    }
    
    public function getProjection() {
        return $this->parser->getProjection();
    }

    public function getSubLayerNames() {
        return $this->parser->getSubLayerNames();
    }
    
    public function selectSubLayer($layerId) {
        $this->parser->selectSubLayer($layerId);
    }

    public function getTitle() {
        if (!$this->parser->isPopulated()) {
            $data = $this->getData();
            $this->items = $this->parseData($data);
        }
        return $this->parser->getMapName();
    }
    
    public function items() {
        if (!$this->parser->isPopulated()) {
            $data = $this->getData();
            $this->parseData($data);
        }
        if (!$this->parser->selectedLayerIsInitialized()) {
            // set this directly so we don't interfere with cache
            $oldBaseURL = $this->baseURL;
            $this->baseURL = $this->parser->getURLForSelectedLayer($oldBaseURL);
            $data = $this->getData();
            $this->parseData($data);
            $this->baseURL = $oldBaseURL;
        }
        if (!$this->parser->selectedLayerIsPopulated()) {
            $oldBaseURL = $this->baseURL;
            $this->baseURL = $this->parser->getURLForLayerFeatures($oldBaseURL);
            $oldFilters = $this->filters;
            $this->filters = $this->parser->getFiltersForLayer();
            $data = $this->getData();
            $this->parseData($data);
            $this->filters = $oldFilters;
            $this->baseURL = $oldBaseURL;
        }
        return $this->parser->getFeatureList();
    }

}

