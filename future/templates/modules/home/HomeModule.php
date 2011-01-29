<?php

require_once realpath(LIB_DIR.'/Module.php');

class HomeModule extends Module {
  protected $id = 'home';
     
  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;
        
      case 'index':
        $this->loadWebAppConfigFile('home-index', 'home');
        
        $this->addOnLoad('rotateScreen();');
        $this->addOnOrientationChange('rotateScreen();');

        $this->assign('modules', $this->getModuleNavList());
        $this->assign('topItem', null);
        break;
        
     case 'search':
        $searchTerms = $this->getArg('filter');
        
        $federatedResults = array();
     
        foreach ($this->getHomeScreenModules() as $id => $info) {
          if ($info['search']) {
            $results = array();
            $module = Module::factory($id, $this->page, $this->args);
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
