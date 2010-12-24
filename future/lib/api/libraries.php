<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

require_once realpath(LIB_DIR.'/feeds/LibrariesInfo.php');
error_log("COMMAND {$_REQUEST['command']}");
switch ($_REQUEST['command']) {
    case 'libraries':
        $data = Libraries::getAllLibraries();
        break;

    case 'archives':
        $data = Libraries::getAllArchives();
        break;

    case 'opennow':
        $data = Libraries::getOpenNow();
        break;

    case 'libdetail':
        $libId = urldecode($_REQUEST['id']);
        $libName = urldecode($_REQUEST['name']);
        $data = Libraries::getLibraryDetails($libId, $libName);
        break;

    case 'archivedetail':
        $archiveId = urldecode($_REQUEST['id']);
        $archiveName = urldecode($_REQUEST['name']);
        $data = Libraries::getArchiveDetails($archiveId, $archiveName);
        break;

    case 'search':
        $queryTerms =($_REQUEST['q']);
        $queryTerms = str_replace("\\", "", $queryTerms);
        $libTerm = isset($_REQUEST['lib']) ? $_REQUEST['lib'] : '';
        $fmtTerm = isset($_REQUEST['fmt']) ? $_REQUEST['fmt'] : '';
        
        $data = Libraries::searchItems($queryTerms, $libTerm, $fmtTerm);
        break;

    case 'fullavailability':
        $itemid = urldecode($_REQUEST['itemid']);
        $data = Libraries::getFullAvailability($itemid);
        break;

    case 'itemdetail':
        $itemid = urldecode($_REQUEST['itemid']);
        $data = Libraries::getItemRecord($itemid);
        break;

    case 'imagethumbnail':
        $imageId = urldecode($_REQUEST['itemid']);
        $data = Libraries::getImageThumbnail($imageId);
        break;

}

echo json_encode($data);
