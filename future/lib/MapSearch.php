<?php

class MapSearch {

    protected $searchResults;
    protected $resultCount;
    protected $feeds;
    
    public function setFeedData($feeds) {
        $this->feeds = $feeds;
    }

    public function getSearchResults() {
        return $this->searchResults;
    }
    
    public function getResultCount() {
        return $this->resultCount;
    }

    public function searchCampusMap($query) {
        $this->searchResults = array();
    
    	foreach ($this->feeds as $id => $feedData) {
            $controller = MapLayerDataController::factory($feedData);
            $controller->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));
            
            if ($controller->canSearch()) {
                $isHierarchy = false;
                $results = $controller->search($query);
                $this->resultCount += count($results);
                foreach ($results as $index => $aResult) {
                    if (is_array($aResult)) {
                        foreach ($aResult as $featureID => $feature) {
                            $this->searchResults[] = array(
                                'title' => $feature->getTitle(),
                                'subtitle' => $feature->getSubtitle(),
                                'category' => $id,
                                'subcategory' => $index,
                                'index' => $featureID,
                            );
                        }
                    } else {
                        $this->searchResults[] = array(
                            'title' => $aResult->getTitle(),
                            'subtitle' => $aResult->getSubtitle(),
                            'category' => $id,
                            'index' => $index,
                            );
                    }
                }
            }
    	}
    	
    	return $this->searchResults;
    }
    
    public function getTitleForSearchResult($aResult) {
        return $aResult['title'];
    }
    
    public function getURLArgsForSearchResult($aResult) {
        return array(
            'selectvalues' => $aResult['index'],
            'subcategory' => $aResult['subcategory'],
            'category' => $aResult['category'],
            );
    }
	
}



