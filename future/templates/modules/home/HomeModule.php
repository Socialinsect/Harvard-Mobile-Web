<?php

require_once realpath(LIB_DIR.'/Module.php');

class HomeModule extends Module {
  protected $id = 'home';
  
  private function getTabletModulePanes($tabletConfig) {
    $modulePanes = array();
    
    foreach ($tabletConfig as $blockName => $moduleID) {
      $path = self::getPathSegmentForModuleID($moduleID);
    
      $module = self::factory($path, 'pane', $this->args);
      
      $paneContent = $module->fetchPage(); // sets pageTitle var
      
      $modulePanes[$blockName] = array(
        'id' => $moduleID,
        'url' => $this->buildURLForModule($moduleID, 'index'),
        'title' => $module->getTemplateVars('pageTitle'),
        'content' => $paneContent,
      );
    }
    
    return $modulePanes;
  }
  
  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;
        
      case 'pane':
        break;
        
      case 'index':
        $homeConfig = $this->loadWebAppConfigFile('home-index', 'home');
        
        $this->addOnLoad('rotateScreen(); moduleHandleWindowResize();');
        $this->addOnOrientationChange('rotateScreen();');

        if ($this->pagetype == 'tablet') {
          $this->assign('modulePanes', $this->getTabletModulePanes($homeConfig['tabletPanes']));
        } else {
          $this->assign('modules', $this->getModuleNavList());
        }
        $this->assign('topItem', null);
        break;
        
     case 'search':
        $searchTerms = $this->getArg('filter');
        
        $federatedResults = array();
     
        foreach ($this->getHomeScreenModules() as $id => $info) {
          if ($info['search']) {
            $results = array();
            $path = self::getPathSegmentForModuleID($id);
            $module = self::factory($path, $this->page, $this->args);
            $total = $module->federatedSearch($searchTerms, 2, $results);
            $federatedResults[] = array(
              'title'   => $info['title'],
              'results' => $results,
              'total'   => $total,
              'url'     => $module->urlForFederatedSearch($searchTerms),
            );
            unset($module);
          }
        }
        //error_log(print_r($federatedResults, true));
        $this->assign('federatedResults', $federatedResults);
        $this->assign('searchTerms',      $searchTerms);
        break;
    }
  }
}
