<?php

require_once realpath(LIB_DIR.'/Module.php');

class CustomizeModule extends Module {
  protected $id = 'customize';

  private function handleRequest($args) {
    if (isset($args['action'])) {
      $currentModules = $this->getHomeScreenModules();
      
      switch ($args['action']) {
        case 'swap':
         $currentIDs = array_keys($currentModules);
          
          if (isset($args['module1'], $args['module2']) && 
              in_array($args['module1'], $currentIDs) && 
              in_array($args['module2'], $currentIDs)) {
              
            foreach ($currentIDs as $index => &$id) {
              if ($id == $args['module1']) {
                $id = $args['module2'];
              } else if ($id == $args['module2']) {
                $id = $args['module1'];
              }
            }
            
            $this->setHomeScreenModuleOrder($currentIDs);
          }
          break;
          
        case 'on':
        case 'off':
          if (isset($args['module'])) {
            $hiddenModuleIDs = array();
            
            foreach ($currentModules as $id => &$info) {
              if ($id == $args['module']) {
                $info['disabled'] = $args['action'] != 'on';
              }
              if ($info['disabled']) { $hiddenModuleIDs[] = $id; }
            }
            
            $this->setHomeScreenHiddenModules($hiddenModuleIDs);
          }
          break;
        
        default:
          error_log(__FUNCTION__."(): Unknown action '{$_REQUEST['action']}'");
          break;
      }
    }
  }

  protected function initializeForPage() {
    $this->handleRequest($this->args);

    $modules = array();
    $moduleIDs = array();
    $disabledModuleIDs = array();
    $newCount = 0;

    foreach ($this->getHomeScreenModules() as $moduleID => $info) {
      if ($info['primary']) {
        $modules[$moduleID] = $info;
        
        $moduleIDs[] = $moduleID;
        if ($info['disabled']) { 
          $disabledModuleIDs[] = $moduleID; 
        }
        
        if ($info['new']) { 
          $newCount++; 
        }
      }
    }
    
    switch($this->pagetype) {
      case 'compliant':
         $this->addInlineJavascript(
          'var modules = '.json_encode($moduleIDs).';'.
          'var disabledModules = '.json_encode($disabledModuleIDs).';'.
          'var MODULE_ORDER_COOKIE = "'.MODULE_ORDER_COOKIE.'";'.
          'var DISABLED_MODULES_COOKIE = "'.DISABLED_MODULES_COOKIE.'";'.
          'var MODULE_ORDER_COOKIE_LIFESPAN = '.$GLOBALS['siteConfig']->getVar('MODULE_ORDER_COOKIE_LIFESPAN').';'.
          'var COOKIE_PATH = "'.COOKIE_PATH.'";'
        );
        $this->addInlineJavascriptFooter('init();');
        break;
      
      case 'touch':
      case 'basic':
        foreach ($moduleIDs as $index => $id) {
          $modules[$id]['toggleDisabledURL'] = $this->buildBreadcrumbURL('index', array(
            'action' => $modules[$id]['disabled'] ? 'on' : 'off',
            'module' => $id,
          ), false);
          
          if ($index > 0) {
            $modules[$id]['swapUpURL'] = $this->buildBreadcrumbURL('index', array(
              'action'    => 'swap',
              'module1'   => $id,
              'module2'   => $moduleIDs[$index-1],
            ), false);
          }
          if ($index < (count($moduleIDs)-1)) {
            $modules[$id]['swapDownURL'] = $this->buildBreadcrumbURL('index', array(
              'action'    => 'swap',
              'module1'   => $id,
              'module2'   => $moduleIDs[$index+1],
            ), false);
          }
        }
        break;
        
      default:
        break;
    }    
    
    $this->assignByRef('modules', $modules);
    $this->assignByRef('newCount', $newCount);
  }
}
