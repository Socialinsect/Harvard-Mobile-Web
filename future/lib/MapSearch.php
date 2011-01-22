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
                $results = $controller->search($query);
                $this->resultCount += count($results);
                foreach ($results as $index => $aResult) {
                    $this->searchResults[] = array(
                        'title' => $aResult->getTitle(),
                        'category' => $id,
                        'index' => $index,
                        );
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
            'category' => $aResult['category'],
            );
    }
	
}



