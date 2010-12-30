<?php

require_once realpath(LIB_DIR.'/ArcGISParser.php');

function searchCampusMap($query) {

    $results = array();
    $bldgIds = array();

    $params = array(
        'str' => $query,
        'fmt' => 'json',
        );
    
    $url = $GLOBALS['siteConfig']->getVar('MAP_SEARCH_URL').'?'.http_build_query($params);
    $rawContent = file_get_contents($url);
    $content = json_decode($rawContent, true);
    
    foreach ($content['results'] as $result) {
        if (strlen($result['bld_num']) && !in_array($result['bld_num'], $bldgIds))
            $bldgIds[] = $result['bld_num'];
    }

    if ($bldgIds) {
        foreach ($bldgIds as $bldgId) {
            $featureInfo = ArcGISDataController::getBldgByNumber($bldgId);
            $feature = new ArcGISFeature($featureInfo['attributes'], $featureInfo['geometry']);
            $feature->setTitleField('Building Name');
            $results[] = $feature;
        }
    }
    return $results;
}

// search for courses
function searchCampusMapForCourseLoc($query) {

    $results = array();
    $bldgIds = array();

    $params = array(
        'str' => $query,
        'loc' => 'course',
        );

    $url = $GLOBALS['siteConfig']->getVar('MAP_SEARCH_URL').'?'.http_build_query($params);
    $rawContent = file_get_contents($url);
    $content = json_decode($rawContent, true);

    foreach ($content['results'] as $resultObj) {
        if (!in_array($resultObj['bld_num'], $bldgIds))
            $bldgIds[] = $resultObj['bld_num'];
    }

    if ($bldgIds) {
        foreach ($bldgIds as $bldgId) {
            $featureInfo = ArcGISDataController::getBldgByNumber($bldgId);
            $feature = new ArcGISFeature($featureInfo['attributes'], $featureInfo['geometry']);
            $feature->setTitleField('Building Name');
            $results[] = $feature;
        }
    }

    return $results;
}




