<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/


require_once realpath(LIB_DIR.'/api.php');
require_once realpath(LIB_DIR.'/feeds/Libraries.php');

switch (apiGetArg('command')) {
  case 'libraries':
    $data = Libraries::getLibraries();
    break;

  case 'archives':
    $data = Libraries::getArchives();
    break;

  case 'opennow':
    $data = Libraries::getOpenNow();
    break;

  case 'libdetail':
    $libId = apiGetArg('id');
    $libName = apiGetArg('name');
    $data = Libraries::getLibraryDetails($libId, $libName);
    break;

  case 'archivedetail':
    $archiveId = apiGetArg('id');
    $archiveName = apiGetArg('name');
    $data = Libraries::getArchiveDetails($archiveId, $archiveName);
    break;

  case 'search':
    // empty strings are ignored by searchItems() when building queries
    $data = Libraries::searchItems(array(
      'q'        => apiGetArg('q'),         // the full query
      'keywords' => apiGetArg('keywords'),  // space-separated list of keywords
      'title'    => apiGetArg('title'),
      'author'   => apiGetArg('author'),
      'location' => apiGetArg('location'),  // library/archive location code
      'format'   => apiGetArg('format'),    // format code
      'pubDate'  => apiGetArg('pubDate'),   // YYYY-YYYY (4 digit year range)
      'language' => apiGetArg('language'),  // language code
    ), apiGetArg('page', '1'));
    break;

  case 'fullavailability':
    $itemid = apiGetArg('itemId');
    $data = Libraries::getFullAvailability($itemid);
    break;
  
  case 'itemavailability':
    $data = Libraries::getItemAvailability(apiGetArg('itemId'));
    break;

  case 'itemavailabilitysummary':
    $data = Libraries::getItemAvailabilitySummary(apiGetArg('itemId'));
    break;

  case 'itemdetail':
    $itemid = apiGetArg('itemId');
    $data = Libraries::getItemRecord($itemid);
    break;

  case 'imagethumbnail':
    $imageId = apiGetArg('itemId');
    $data = Libraries::getImageThumbnail($imageId);
    break;
  
  case 'searchcodes':
    $data = array(
      'formats'   => Libraries::getFormatSearchCodes(),
      'locations' => Libraries::getLibrarySearchCodes(),
      'pubDates'  => Libraries::getPubDateSearchCodes(),
    );
    
    if (apiGetArg('version', 1) > 1) {
      // Provide in indexed array format because iOS dictionaries aren't ordered
      foreach ($data as $type => $formatArray) {
        $newFormatArray = array();
        foreach ($formatArray as $code => $name) {
          $newFormatArray[] = array(
            'code'  => $code,
            'name' => $name,
          );
        }
        $data[$type] = $newFormatArray;
      }
    }
    break;
}

echo json_encode($data);
