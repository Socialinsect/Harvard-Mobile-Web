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
        
        $this->addOnLoad('rotateScreen();');
        $this->addOnOrientationChange('rotateScreen();');

        if ($this->pagetype == 'tablet') {
          $this->assign('modulePanes', $this->getTabletModulePanes($homeConfig['tabletPanes']));
          $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
          $this->addOnLoad('moduleHandleWindowResize();');
        } else {
          $this->assign('modules', $this->getModuleNavList());
        }
        $this->assign('displayType', $homeConfig['springboard'] ? 'springboard' : 'list');
        $this->assign('topItem', null);
        break;
        
     case 'search':
        $searchTerms = $this->getArg('filter');
        
        $federatedResults = array();
     
        foreach ($this->getNavigationModules(false) as $id => $info) {
          $path = self::getPathSegmentForModuleID($id);
          $module = self::factory($path, $this->page, $this->args);

          if ($info['search']) {
            $results = array();
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
